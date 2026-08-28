<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $status_baru = ($_POST['status_sekarang'] ?? '') === 'aktif' ? 'nonaktif' : 'aktif';
    $stmt = $pdo->prepare("UPDATE produk SET status = ? WHERE id = ?");
    $stmt->execute([$status_baru, $id]);
    redirect('/admin/kelola_produk.php');
}

$produk_list = $pdo->query(
    "SELECT p.*, k.nama_kategori, k.jenis, u.nama AS nama_penjual
     FROM produk p
     JOIN kategori k ON p.kategori_id = k.id
     JOIN users u ON p.user_id = u.id
     ORDER BY p.created_at DESC"
)->fetchAll();

$page_title = 'Kelola Produk';
require_once __DIR__ . '/../includes/header.php';
?>


<?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn d-lg-none" type="button">
                ☰ Menu
            </button>
            <h3 class="page-section-title m-0" style="font-family: var(--font-display);">Kelola Semua Produk</h3>
        </div>
        <span class="text-muted small">Total: <strong><?= count($produk_list) ?></strong> produk terdaftar</span>
    </div>

    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: rgba(59, 91, 62, 0.04);">
                        <tr class="text-muted small">
                            <th class="py-3 ps-4" style="font-weight: 600;">Detail Produk</th>
                            <th class="py-3" style="font-weight: 600;">Penjual / Mitra</th>
                            <th class="py-3" style="font-weight: 600;">Klaster</th>
                            <th class="py-3" style="font-weight: 600;">Harga Unit</th>
                            <th class="py-3" style="font-weight: 600;">Stok</th>
                            <th class="py-3" style="font-weight: 600;">Status</th>
                            <th class="py-3 pe-4 text-end" style="font-weight: 600;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($produk_list)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inboxes fs-2 d-block mb-2 text-black-50"></i>
                                Belum ada data produk terdaftar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produk_list as $p): ?>
                            <tr>
                                <td class="py-3 ps-4">
                                    <div class="fw-bold text-dark"><?= sanitize($p['nama_produk']) ?></div>
                                    <span class="text-muted small">ID Produk: #<?= $p['id'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-secondary"><?= sanitize($p['nama_penjual']) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $p['jenis'] === 'umkm' ? 'badge-umkm' : 'badge-pertanian' ?>">
                                        <?= sanitize($p['nama_kategori']) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold"><?= rupiah($p['harga']) ?> <span class="text-muted small">/ <?= sanitize($p['satuan']) ?></span></td>
                                <td>
                                    <span class="<?= (int)$p['stok'] === 0 ? 'text-danger fw-bold' : '' ?>">
                                        <?= (int) $p['stok'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $p['status'] === 'aktif' ? 'success' : 'secondary' ?> rounded-pill px-2.5 py-1.5" style="font-size: 0.72rem; font-weight: 500;">
                                        <?= sanitize($p['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="status_sekarang" value="<?= $p['status'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $p['status'] === 'aktif' ? 'btn-outline-danger' : 'btn-success' ?> px-3" style="border-radius: 6px;">
                                            <?= $p['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Info board notices -->
    <div class="p-3 bg-light rounded-3 border-start border-3 border-success mb-4" style="border-left-color: var(--kaligawe-primary) !important;">
        <span class="fw-bold small d-block mb-1 text-success" style="color: var(--kaligawe-primary) !important;"><i class="bi bi-info-circle me-1"></i>Informasi Moderasi</span>
        <p class="small text-muted mb-0" style="font-size: 0.8rem; line-height: 1.45;">
            Admin berhak menonaktifkan atau mengaktifkan kembali produk mitra UMKM/Petani demi menjaga ketertiban kualitas di katalog Pasar Kaligawe.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
