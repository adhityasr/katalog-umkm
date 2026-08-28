<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$konfirmasi_id = (int) ($_POST['konfirmasi_id'] ?? 0);
$pesanan_id = (int) ($_POST['pesanan_id'] ?? 0);
$status = in_array($_POST['status_konfirmasi'] ?? '', ['diterima', 'ditolak']) ? $_POST['status_konfirmasi'] : null;

if ($status && $konfirmasi_id) {
    $stmt = $pdo->prepare("UPDATE konfirmasi SET status_konfirmasi = ? WHERE id = ?");
    $stmt->execute([$status, $konfirmasi_id]);

    // Jika pembayaran diterima, otomatis ubah status pesanan menjadi 'diproses'
    if ($status === 'diterima') {
        $stmt = $pdo->prepare("UPDATE pesanan SET status = 'diproses' WHERE id = ? AND status = 'menunggu_pembayaran'");
        $stmt->execute([$pesanan_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE pesanan SET status = 'dibatalkan' WHERE id = ?");
        $stmt->execute([$pesanan_id]);
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => "Konfirmasi pembayaran pesanan #$pesanan_id berhasil diproses."];
}

redirect('/admin/kelola_pesanan.php');
