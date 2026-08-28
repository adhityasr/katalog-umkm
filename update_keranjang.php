<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in() && current_user()['role'] !== 'pembeli' && !empty(current_user())) {
    redirect('/katalog.php');
}

$qty = max(1, (int) ($_POST['qty'] ?? 1));

// Tamu: update via produk_id di session
if (!is_logged_in()) {
    $produk_id = (int) ($_POST['produk_id'] ?? 0);
    if ($produk_id > 0) {
        $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = ? AND status = 'aktif'");
        $stmt->execute([$produk_id]);
        $p = $stmt->fetch();
        if ($p) {
            $qty = min($qty, (int) $p['stok']);
            $_SESSION['keranjang_guest'] = $_SESSION['keranjang_guest'] ?? [];
            if (isset($_SESSION['keranjang_guest'][$produk_id])) {
                $_SESSION['keranjang_guest'][$produk_id] = $qty;
            }
        }
    }
    redirect('/keranjang.php');
}

// Pembeli login: update via keranjang_id
require_role('pembeli');
$user_id = current_user()['id'];

$keranjang_id = (int) ($_POST['keranjang_id'] ?? 0);

// Pastikan item milik user yang sedang login
$stmt = $pdo->prepare(
    "SELECT ke.*, p.stok FROM keranjang ke JOIN produk p ON ke.produk_id = p.id
     WHERE ke.id = ? AND ke.user_id = ?"
);
$stmt->execute([$keranjang_id, $user_id]);
$item = $stmt->fetch();

if ($item) {
    $qty = min($qty, (int) $item['stok']);
    $stmt = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
    $stmt->execute([$qty, $keranjang_id]);
}

redirect('/keranjang.php');