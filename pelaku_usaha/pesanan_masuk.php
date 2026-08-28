<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];

// Ambil semua baris detail_pesanan untuk produk milik pelaku usaha ini
$stmt = $pdo->prepare(
     "SELECT dp.qty, dp.harga_satuan, dp.subtotal, p.nama_produk,
            ps.id AS pesanan_id, ps.tanggal, ps.status, ps.nama_penerima, ps.alamat_pengiriman, ps.no_hp_penerima,
            u.nama AS nama_pembeli
     FROM detail_pesanan dp
     JOIN produk p ON dp.produk_id = p.id
     JOIN pesanan ps ON dp.pesanan_id = ps.id
     LEFT JOIN users u ON ps.user_id = u.id
     WHERE p.user_id = ?
     ORDER BY ps.tanggal DESC"
);
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll();

// Kelompokkan berdasarkan pesanan_id agar tampil rapi per pesanan
$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['pesanan_id']]['info'] = $r;
    $grouped[$r['pesanan_id']]['items'][] = $r;
}

$page_title = 'Pesanan Masuk';
require_once __DIR__ . '/../includes/header.php';
?>


<?php require_once __DIR__ . '/../includes/sidebar_pelaku_usaha.php'; ?>
<div class="app-content">
<button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn" type="button">
    ☰ Menu
</button>

<h3 class="mb-4">Pesanan Masuk (untuk produk saya)</h3>

<?php if (empty($grouped)): ?>
    <div class="alert alert-secondary">Belum ada pesanan masuk untuk produk Anda.</div>
<?php else: ?>
    <?php foreach ($grouped as $pesanan_id => $g): $info = $g['info']; [$label, $badge] = label_status_pesanan($info['status']); ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap">
                    <div>
                        <h6 class="mb-1">Pesanan #<?= $pesanan_id ?> &middot; <?= sanitize($info['nama_pembeli'] ?? ($info['nama_penerima'] . ' (Tamu)')) ?></h6>
                        <p class="small text-muted mb-1"><?= date('d M Y H:i', strtotime($info['tanggal'])) ?></p>
                        <p class="small mb-1">Kirim ke: <?= sanitize($info['nama_penerima']) ?>, <?= sanitize($info['alamat_pengiriman']) ?> (<?= sanitize($info['no_hp_penerima']) ?>)</p>
                    </div>
                    <span class="badge bg-<?= $badge ?> align-self-start"><?= sanitize($label) ?></span>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Produk</th><th>Qty</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($g['items'] as $it): ?>
                            <tr>
                                <td><?= sanitize($it['nama_produk']) ?></td>
                                <td><?= (int) $it['qty'] ?></td>
                                <td><?= rupiah($it['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<p class="text-muted small">Catatan: perubahan status pesanan (diproses/dikirim/selesai) dan verifikasi pembayaran dilakukan oleh admin desa melalui menu Kelola Pesanan.</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
