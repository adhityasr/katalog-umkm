<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];

$stmt = $pdo->prepare(
    "SELECT p.*, k.nama_kategori, k.jenis
     FROM produk p JOIN kategori k ON p.kategori_id = k.id
     WHERE p.user_id = ? ORDER BY p.created_at DESC"
);
$stmt->execute([$user_id]);
$produk_list = $stmt->fetchAll();

$stmt_manual = $pdo->prepare(
    "SELECT p.nama_produk, p.harga, SUM(pm.qty) as total_qty
     FROM penjualan_manual pm
     JOIN produk p ON pm.produk_id = p.id
     WHERE p.user_id = ?
     GROUP BY p.id
     ORDER BY total_qty DESC"
);
$stmt_manual->execute([$user_id]);
$manual_sales = $stmt_manual->fetchAll();

// ===== Statistik =====
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM produk WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_produk = (int) $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM produk WHERE user_id = ? AND status = 'aktif'");
$stmt->execute([$user_id]);
$total_aktif = (int) $stmt->fetch()['c'];

$stok_menipis = 0;
foreach ($produk_list as $p) {
    if ($p['status'] === 'aktif' && (int) $p['stok'] <= 5) {
        $stok_menipis++;
    }
}

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT dp.pesanan_id) c
     FROM detail_pesanan dp
     JOIN produk p ON dp.produk_id = p.id
     JOIN pesanan ps ON dp.pesanan_id = ps.id
     WHERE p.user_id = ? AND ps.status IN ('menunggu_pembayaran','diproses','dikirim')"
);
$stmt->execute([$user_id]);
$pesanan_masuk = (int) $stmt->fetch()['c'];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(dp.subtotal),0) t
     FROM detail_pesanan dp
     JOIN produk p ON dp.produk_id = p.id
     JOIN pesanan ps ON dp.pesanan_id = ps.id
     WHERE p.user_id = ? AND ps.status = 'selesai'"
);
$stmt->execute([$user_id]);
$pendapatan_online = (float) $stmt->fetch()['t'];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(pm.qty * p.harga),0) t
     FROM penjualan_manual pm
     JOIN produk p ON pm.produk_id = p.id
     WHERE p.user_id = ?"
);
$stmt->execute([$user_id]);
$pendapatan_offline = (float) $stmt->fetch()['t'];

$pendapatan_total = $pendapatan_online + $pendapatan_offline;

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>


<?php require_once __DIR__ . '/../includes/sidebar_pelaku_usaha.php'; ?>
<div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn d-lg-none" type="button">
            ☰ Menu
        </button>
        <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> Hari ini: <?= date('d M Y') ?></span>
    </div>

    <!-- Welcome Hero Banner -->
    <div class="card text-white mb-4 overflow-hidden border-0 shadow-sm" style="background: linear-gradient(135deg, var(--kaligawe-primary) 0%, #253a27 100%); border-radius: 16px;">
        <div class="card-body p-4 p-md-5 d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative">
            <div class="position-relative" style="z-index: 2;">
                <h2 class="fw-bold mb-2" style="font-family: var(--font-display);">Halo, <?= sanitize(current_user()['nama']) ?>!</h2>
                <p class="text-white-50 mb-0 font-body small" style="max-width: 600px; font-weight: 300; line-height: 1.5;">
                    Selamat berjualan di Pasar Kaligawe. Kelola produk, pantau pesanan masuk, dan catat penjualan manual Anda dari satu panel ini.
                </p>
            </div>
            <div class="d-flex gap-2 position-relative" style="z-index:2">
                <a href="<?= BASE_URL ?>/pelaku_usaha/edit_profil.php" class="btn btn-outline-light" style="border-radius:10px; padding:0.6rem 1.1rem; font-size:0.88rem; border-color: rgba(255,255,255,0.4); color:#fff;"><i class="bi bi-person-gear me-1"></i> Edit Profil</a>
                <a href="<?= BASE_URL ?>/pelaku_usaha/tambah_produk.php" class="btn" style="background: linear-gradient(135deg, var(--kaligawe-accent) 0%, #C9873A 100%); border: none; color: #3d2b00; font-weight: 600; border-radius: 10px; padding: 0.6rem 1.2rem; font-size: 0.92rem; box-shadow: 0 4px 12px rgba(221, 161, 94, 0.35);">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                </a>
            </div>
            <div class="d-none d-md-block opacity-25 position-absolute end-0" style="transform: rotate(12deg); margin-right: 24px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" fill="currentColor" class="bi bi-basket2" viewBox="0 0 16 16">
                    <path d="M5.757 1.071a.5.5 0 0 1 .172.686L3.383 6h9.234L10.07 1.757a.5.5 0 1 1 .858-.514L13.783 6H15.5a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H.5a.5.5 0 0 1-.5-.5v-1A.5.5 0 0 1 .5 6h1.717L5.07 1.243a.5.5 0 0 1 .686-.172zM3.394 15l-1.48-6h-.97l1.525 6.426a.75.75 0 0 0 .73.574h9.601a.75.75 0 0 0 .73-.574L15.056 9h-.972l-1.479 6h-9.21z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100 overflow-hidden" style="border-left: 4px solid var(--kaligawe-tani) !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-2 fw-bold text-success" style="font-family: var(--font-display);"><?= $total_produk ?></div>
                        <div class="text-muted small fw-medium">Total Produk</div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100 overflow-hidden" style="border-left: 4px solid var(--kaligawe-umkm) !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-2 fw-bold text-danger" style="font-family: var(--font-display);"><?= $total_aktif ?></div>
                        <div class="text-muted small fw-medium">Produk Aktif</div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-bag-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100 overflow-hidden" style="border-left: 4px solid var(--kaligawe-accent) !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-2 fw-bold text-warning" style="font-family: var(--font-display);"><?= $stok_menipis ?></div>
                        <div class="text-muted small fw-medium">Stok Menipis</div>
                        <?php if ($stok_menipis > 0): ?>
                            <div class="small mt-1 text-warning"><i class="bi bi-exclamation-triangle me-1"></i>segera isi ulang stok</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100 overflow-hidden" style="border-left: 4px solid #A44C4C !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-2 fw-bold" style="font-family: var(--font-display); color: #A44C4C !important;"><?= $pesanan_masuk ?></div>
                        <div class="text-muted small fw-medium">Pesanan Masuk</div>
                    </div>
                    <div class="stat-icon p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: rgba(164, 76, 76, 0.1); color: #A44C4C;">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Daftar Produk -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold" style="color: var(--kaligawe-primary);"><i class="bi bi-box-seam me-2"></i>Daftar Produk</h5>
                        <a href="<?= BASE_URL ?>/pelaku_usaha/tambah_produk.php" class="btn btn-sm btn-outline-success"><i class="bi bi-plus-lg me-1"></i>Tambah</a>
                    </div>

                    <?php if (empty($produk_list)): ?>
                        <div class="alert alert-secondary mb-0">Anda belum memiliki produk. Klik "Tambah Produk" untuk mulai.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($produk_list as $p):
                                    $low_stok = (int) $p['stok'] <= 5; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="produk-tile"><i class="bi bi-basket"></i></div>
                                                <span class="fw-semibold"><?= sanitize($p['nama_produk']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= sanitize($p['nama_kategori']) ?></td>
                                        <td class="text-nowrap"><?= rupiah($p['harga']) ?> / <span class="text-muted small"><?= sanitize($p['satuan']) ?></span></td>
                                        <td>
                                            <span class="fw-bold <?= $low_stok ? 'text-danger' : '' ?>"><?= (int) $p['stok'] ?></span>
                                            <?php if ($low_stok): ?><i class="bi bi-exclamation-triangle-fill text-danger small ms-1" title="Stok menipis"></i><?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $p['status'] === 'aktif' ? 'success' : 'secondary' ?> rounded-pill px-2 py-1" style="font-size: 0.72rem; font-weight: 500;" title="Klik Edit untuk mengubah status aktif/nonaktif" data-bs-toggle="tooltip">
                                                <?= sanitize($p['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="<?= BASE_URL ?>/pelaku_usaha/edit_produk.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <form method="post" action="<?= BASE_URL ?>/pelaku_usaha/hapus_produk.php" class="d-inline form-confirm-delete" data-message="Hapus produk ini?">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pendapatan & Penjualan -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-top: 4px solid var(--kaligawe-accent) !important; background: linear-gradient(160deg, #FFFDF9 0%, #F7EEDB 100%);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: rgba(221, 161, 94, 0.15); color: #B87A2E;">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-medium">Total Pendapatan</div>
                            <div class="fs-3 fw-bold" style="font-family: var(--font-display); color: var(--kaligawe-primary);"><?= rupiah($pendapatan_total) ?></div>
                        </div>
                    </div>
                    <hr class="my-3" style="opacity: 0.6;">
                    <div class="small d-flex justify-content-between mb-2">
                        <span class="text-muted"><i class="bi bi-bag-check me-1 text-success"></i>Pesanan online (selesai)</span>
                        <span class="fw-semibold text-success"><?= rupiah($pendapatan_online) ?></span>
                    </div>
                    <div class="small d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-shop me-1 text-warning"></i>Penjualan manual (offline)</span>
                        <span class="fw-semibold text-warning"><?= rupiah($pendapatan_offline) ?></span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-semibold" style="color: var(--kaligawe-primary);"><i class="bi bi-shop me-2"></i>Penjualan Manual</h6>
                        <a href="<?= BASE_URL ?>/pelaku_usaha/catat_penjualan.php" class="btn btn-sm btn-outline-warning"><i class="bi bi-plus-lg me-1"></i>Catat</a>
                    </div>
                    <?php if (empty($manual_sales)): ?>
                        <p class="text-muted small mb-0">Belum ada penjualan manual tercatat.</p>
                    <?php else: ?>
                        <?php $max = max(array_column($manual_sales, 'total_qty')); ?>
                        <?php foreach ($manual_sales as $i => $m): ?>
                            <div class="d-flex align-items-center justify-content-between py-2 <?= $i > 0 ? 'border-top' : '' ?>" style="border-color: rgba(59, 91, 62, 0.08) !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-warning text-dark" style="min-width: 26px;"><?= $i + 1 ?></span>
                                    <span class="small fw-medium"><?= sanitize($m['nama_produk']) ?></span>
                                </div>
                                <span class="small text-muted"><?= (int) $m['total_qty'] ?> terjual</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>