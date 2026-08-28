<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in() && current_user()['role'] !== 'pembeli' && !empty(current_user())) {
    redirect('/katalog.php');
}

// Tamu: hapus via produk_id di session
if (!is_logged_in()) {
    $produk_id = (int) ($_POST['produk_id'] ?? 0);
    if ($produk_id > 0 && isset($_SESSION['keranjang_guest'][$produk_id])) {
        unset($_SESSION['keranjang_guest'][$produk_id]);
    }
    redirect('/keranjang.php');
}

require_role('pembeli');
$user_id = current_user()['id'];

$keranjang_id = (int) ($_POST['keranjang_id'] ?? 0);

$stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND user_id = ?");
$stmt->execute([$keranjang_id, $user_id]);

redirect('/keranjang.php');