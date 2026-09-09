<?php
// =========================================================
// Kumpulan fungsi bantu (helper)
// =========================================================
require_once __DIR__ . '/../config/database.php';

function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Upload foto produk sederhana, hanya jpg/jpeg/png, maks 2MB
function upload_foto($file, $target_dir) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada file diupload, bukan error
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Gagal mengupload file.');
    }
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('Format file harus jpg, jpeg, atau png.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('Ukuran file maksimal 2MB.');
    }
    $filename = uniqid('img_') . '.' . $ext;
    $destination = rtrim($target_dir, '/') . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Gagal menyimpan file ke server.');
    }
    return $filename;
}

function label_status_pesanan($status) {
    $map = [
        'menunggu_pembayaran' => ['Menunggu pembayaran', 'warning'],
        'diproses' => ['Diproses', 'info'],
        'dikirim' => ['Dikirim', 'primary'],
        'selesai' => ['Selesai', 'success'],
        'dibatalkan' => ['Dibatalkan', 'danger'],
    ];
    return $map[$status] ?? [$status, 'secondary'];
}

// =========================================================
// Keranjang guest (tanpa login) — disimpan di session
// =========================================================
function guest_cart_get() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['keranjang_guest'] ?? [];
}

function guest_cart_count() {
    return array_sum(guest_cart_get());
}

function guest_cart_items($pdo) {
    $cart = guest_cart_get();
    if (empty($cart)) return [];
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT p.id AS produk_id, p.nama_produk, p.harga, p.satuan, p.stok, p.foto, u.nama AS nama_penjual, u.no_hp AS hp_penjual, u.id AS penjual_id FROM produk p JOIN users u ON p.user_id = u.id WHERE p.id IN ($placeholders) AND p.status = 'aktif'");
    $stmt->execute($ids);
    $produk_map = [];
    foreach ($stmt->fetchAll() as $p) {
        $produk_map[$p['produk_id']] = $p;
    }
    $items = [];
    foreach ($cart as $produk_id => $qty) {
        if (!isset($produk_map[$produk_id])) continue;
        $p = $produk_map[$produk_id];
        $items[] = [
            'keranjang_id' => $produk_id,
            'produk_id' => $produk_id,
            'nama_produk' => $p['nama_produk'],
            'harga' => $p['harga'],
            'satuan' => $p['satuan'],
            'stok' => $p['stok'],
            'foto' => $p['foto'],
            'qty' => $qty,
            'nama_penjual' => $p['nama_penjual'],
            'hp_penjual' => $p['hp_penjual'],
            'penjual_id' => $p['penjual_id'],
            'subtotal' => $p['harga'] * $qty,
        ];
    }
    return $items;
}

function cart_count_for_header($pdo) {
    $user = current_user();
    if ($user && $user['role'] === 'pembeli') {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(qty),0) AS total FROM keranjang WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        return (int) $stmt->fetch()['total'];
    }
    if (!$user) {
        return guest_cart_count();
    }
    return 0;
}

function wa_normalize($hp) {
    $hp = preg_replace('/[^0-9]/', '', $hp ?? '');
    if ($hp === '') return '';
    if (str_starts_with($hp, '0')) {
        $hp = '62' . substr($hp, 1);
    } elseif (str_starts_with($hp, '62')) {
        // already 62
    } elseif (str_starts_with($hp, '8')) {
        $hp = '62' . $hp;
    }
    return $hp;
}

function wa_link($hp, $text) {
    $hp = wa_normalize($hp);
    if ($hp === '') return '';
    return 'https://wa.me/' . $hp . '?text=' . rawurlencode($text);
}

// =========================================================
// WA text builders — reusable, dynamic sesuai state (TOP TIER)
// =========================================================
function wa_text_product($nama_penjual, $nama_produk, $harga, $satuan, $produkUrl) {
    $hargaFmt = rupiah($harga);
    $sapaan = trim(str_replace(['(Lumpia)','(Gapoktan)','(Peternakan)','(Kerajinan)'], '', $nama_penjual));
    if ($sapaan === '') $sapaan = $nama_penjual;
    if (str_starts_with($produkUrl, '/')) {
        // Pakai SITE_URL jika ada (sudah hosting), fallback ke host dinamis
        if (defined('SITE_URL')) {
            $produkUrl = rtrim(SITE_URL, '/') . $produkUrl;
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? 'pasar-kaligawe.desa.id';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
            $produkUrl = $scheme . '://' . $host . $produkUrl;
        }
    }
    return "Halo Kak $sapaan, saya tertarik dengan produk *$nama_produk* di *Pasar Kaligawe*\nHarga: $hargaFmt/$satuan\nLink: $produkUrl\n\nApakah stok masih tersedia? Terima kasih!";
}

function wa_text_checkout_seller($nama_penjual, $items, $total) {
    $sapaan = trim(str_replace(['(Lumpia)','(Gapoktan)','(Peternakan)','(Kerajinan)'], '', $nama_penjual));
    if ($sapaan === '') $sapaan = $nama_penjual;
    $lines = [];
    foreach ($items as $it) {
        $sub = $it['subtotal'] ?? ($it['harga'] * $it['qty']);
        $lines[] = "- {$it['nama_produk']} x{$it['qty']} (" . rupiah($it['harga']) . ") = " . rupiah($sub);
    }
    $list = implode("\n", $lines);
    $totalFmt = rupiah($total);
    return "Halo Kak $sapaan, saya mau pesan di Pasar Kaligawe:\n$list\nTotal: $totalFmt\nMohon konfirmasi ketersediaan & ongkir. Terima kasih!";
}

function wa_text_order_seller($nama_penjual, $pesananId, $items, $total) {
    $sapaan = trim(str_replace(['(Lumpia)','(Gapoktan)','(Peternakan)','(Kerajinan)'], '', $nama_penjual));
    if ($sapaan === '') $sapaan = $nama_penjual;
    $lines = [];
    foreach ($items as $it) {
        $sub = $it['subtotal'] ?? ($it['harga'] * $it['qty']);
        $lines[] = "- {$it['nama_produk']} x{$it['qty']} = " . rupiah($sub);
    }
    $list = implode("\n", $lines);
    $totalFmt = rupiah($total);
    return "Halo Kak $sapaan, saya pembeli pesanan #$pesananId di Pasar Kaligawe:\n$list\nTotal: $totalFmt\nMohon konfirmasi pesanan saya. Terima kasih!";
}

function wa_preview_html($text) {
    $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // *bold* -> <strong>
    $html = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $html);
    $html = nl2br($html);
    return $html;
}

function message_box($type, $title, $text, $dismissible = true, $autoProgress = false) {
    $map = [
        'success' => ['success', 'bi-check-circle-fill'],
        'danger'  => ['error', 'bi-x-octagon-fill'],
        'error'   => ['error', 'bi-x-octagon-fill'],
        'warning' => ['warning', 'bi-exclamation-triangle-fill'],
        'info'    => ['info', 'bi-info-circle-fill'],
        'secondary' => ['neutral', 'bi-chat-dots-fill'],
        'neutral' => ['neutral', 'bi-chat-dots-fill'],
    ];
    $variant = $map[$type][0] ?? 'neutral';
    $icon = $map[$type][1] ?? 'bi-chat-dots-fill';
    $iconHtml = '<i class="bi ' . $icon . '"></i>';

    $close = $dismissible ? '<button type="button" class="message-box-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>' : '';
    $progress = $autoProgress ? '<div class="message-box-progress"></div>' : '';
    $titleHtml = $title !== '' ? '<div class="message-box-title">' . sanitize($title) . '</div>' : '';
    $textHtml = $text !== '' ? '<div class="message-box-text">' . $text . '</div>' : '';
    return '<div class="message-box message-box--' . $variant . '" role="alert">'
        . '<div class="message-box-icon">' . $iconHtml . '</div>'
        . '<div class="message-box-content">' . $titleHtml . $textHtml . '</div>'
        . $close
        . $progress
        . '</div>';
}

function flash_message_box() {
    $flash = $_SESSION['flash'] ?? null;
    if (!$flash) return '';
    unset($_SESSION['flash']);
    $type = $flash['type'] ?? 'info';
    $msg = $flash['message'] ?? '';
    // Title based on type
    $titles = ['success'=>'Berhasil','error'=>'Gagal','danger'=>'Gagal','warning'=>'Perhatian','info'=>'Info','secondary'=>'Info','neutral'=>'Info'];
    $title = $titles[$type] ?? 'Info';
    // For flash toast, auto progress true
    return message_box($type, $title, sanitize($msg), true, true);
}

function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}
