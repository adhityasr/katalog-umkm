<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];
$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil dihapus.'];
} catch (PDOException $e) {
    // Produk sudah pernah dipesan (terkait detail_pesanan) sehingga tidak bisa dihapus permanen
    $stmt = $pdo->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Produk sudah pernah dipesan sehingga tidak bisa dihapus permanen. Produk dinonaktifkan sebagai gantinya.'];
}
redirect('/pelaku_usaha/produk_saya.php');
