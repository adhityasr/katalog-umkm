<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/katalog.php');
}

$produk_id = (int) ($_POST['produk_id'] ?? 0);
$qty = max(1, (int) ($_POST['qty'] ?? 1));

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND status = 'aktif'");
$stmt->execute([$produk_id]);
$produk = $stmt->fetch();

if (!$produk) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Produk tidak ditemukan.'];
    redirect('/katalog.php');
}

if ($qty > $produk['stok']) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Jumlah melebihi stok yang tersedia.'];
    redirect('/produk_detail.php?id=' . $produk_id);
}

// Jika login sebagai admin/pelaku_usaha, tolak
if (is_logged_in() && current_user()['role'] !== 'pembeli') {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Hanya pembeli yang dapat menambahkan ke keranjang.'];
    redirect('/katalog.php');
}

if (is_logged_in() && current_user()['role'] === 'pembeli') {
    $user_id = current_user()['id'];
    // Jika produk sudah ada di keranjang, tambahkan qty-nya
    $stmt = $pdo->prepare("SELECT * FROM keranjang WHERE user_id = ? AND produk_id = ?");
    $stmt->execute([$user_id, $produk_id]);
    $item = $stmt->fetch();

    if ($item) {
        $qty_baru = min($produk['stok'], $item['qty'] + $qty);
        $stmt = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
        $stmt->execute([$qty_baru, $item['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, qty) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $produk_id, $qty]);
    }
} else {
    // Tamu (guest) — simpan di session
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['keranjang_guest'] = $_SESSION['keranjang_guest'] ?? [];
    $existing = (int) ($_SESSION['keranjang_guest'][$produk_id] ?? 0);
    $qty_baru = min($produk['stok'], $existing + $qty);
    $_SESSION['keranjang_guest'][$produk_id] = $qty_baru;
}

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil ditambahkan ke keranjang.'];
redirect('/keranjang.php');