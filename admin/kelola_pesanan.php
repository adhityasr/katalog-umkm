<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $status_baru = $_POST['status'] ?? '';
    $valid = ['menunggu_pembayaran', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
    if (in_array($status_baru, $valid)) {
        $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
        $stmt->execute([$status_baru, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Status pesanan #$id diperbarui."];
    }
    redirect('/admin/kelola_pesanan.php');
}

$pesanan_list = $pdo->query(
    "SELECT ps.*, u.nama AS nama_pembeli, kf.id AS konfirmasi_id, kf.bukti_bayar, kf.status_konfirmasi
     FROM pesanan ps
     LEFT JOIN users u ON ps.user_id = u.id
     LEFT JOIN (
         SELECT k1.id, k1.pesanan_id, k1.id AS konfirmasi_id, k1.bukti_bayar, k1.status_konfirmasi
         FROM konfirmasi k1
         JOIN (
             SELECT pesanan_id, MAX(id) AS max_id FROM konfirmasi GROUP BY pesanan_id
         ) k2 ON k2.max_id = k1.id
     ) kf ON kf.pesanan_id = ps.id
     ORDER BY ps.tanggal DESC"
)->fetchAll();

$page_title = 'Kelola Pesanan';
require_once __DIR__ . '/../includes/header.php';
?>


<?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<div class="app-content">
<h3 class="page-section-title mb-4" style="font-family: var(--font-display);">Kelola Pesanan</h3>

<?php if (empty($pesanan_list)): ?>
    <div class="alert alert-secondary">Belum ada pesanan masuk.</div>
<?php else: ?>
    <?php foreach ($pesanan_list as $ps): [$label, $badge] = label_status_pesanan($ps['status']); ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap mb-2">
                    <div>
                        <h6 class="mb-1">Pesanan #<?= $ps['id'] ?> &middot; <?= sanitize($ps['nama_pembeli'] ?? ($ps['nama_penerima'] . ' (Tamu)')) ?></h6>
                        <p class="small text-muted mb-1"><?= date('d M Y H:i', strtotime($ps['tanggal'])) ?></p>
                        <p class="small mb-1">Kirim ke: <?= sanitize($ps['nama_penerima']) ?>, <?= sanitize($ps['alamat_pengiriman']) ?> (<?= sanitize($ps['no_hp_penerima']) ?>)</p>
                        <p class="fw-semibold mb-0">Total: <?= rupiah($ps['total']) ?></p>
                    </div>
                    <span class="badge bg-<?= $badge ?> align-self-start"><?= sanitize($label) ?></span>
                </div>

                <div class="row align-items-center">
                    <div class="col-md-6 mb-2">
                        <?php if ($ps['bukti_bayar']): ?>
                            <p class="small mb-1">
                                Bukti bayar:
                                <a href="<?= BASE_URL ?>/assets/uploads/<?= sanitize($ps['bukti_bayar']) ?>" target="_blank" title="Buka bukti pembayaran di tab baru" data-bs-toggle="tooltip">Lihat file</a>
                                &middot; Status:
                                <span class="badge bg-<?= $ps['status_konfirmasi'] === 'diterima' ? 'success' : ($ps['status_konfirmasi'] === 'ditolak' ? 'danger' : 'warning') ?>">
                                    <?= sanitize($ps['status_konfirmasi']) ?>
                                </span>
                            </p>
                            <?php if ($ps['status_konfirmasi'] === 'pending'): ?>
                                <form method="post" action="<?= BASE_URL ?>/admin/konfirmasi_pesanan.php" class="d-inline">
                                    <input type="hidden" name="konfirmasi_id" value="<?= $ps['konfirmasi_id'] ?>">
                                    <input type="hidden" name="pesanan_id" value="<?= $ps['id'] ?>">
                                    <input type="hidden" name="status_konfirmasi" value="diterima">
                                    <button type="submit" class="btn btn-sm btn-success">Terima Pembayaran</button>
                                </form>
                                <form method="post" action="<?= BASE_URL ?>/admin/konfirmasi_pesanan.php" class="d-inline">
                                    <input type="hidden" name="konfirmasi_id" value="<?= $ps['konfirmasi_id'] ?>">
                                    <input type="hidden" name="pesanan_id" value="<?= $ps['id'] ?>">
                                    <input type="hidden" name="status_konfirmasi" value="ditolak">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="small text-muted mb-0">Bukti pembayaran belum diunggah pembeli.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-2">
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $ps['id'] ?>">
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach (['menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $ps['status'] === $st ? 'selected' : '' ?>><?= label_status_pesanan($st)[0] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Ubah Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>


