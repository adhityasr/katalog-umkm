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
    $stmt = $pdo->prepare("SELECT id AS produk_id, nama_produk, harga, satuan, stok, foto FROM produk WHERE id IN ($placeholders) AND status = 'aktif'");
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
