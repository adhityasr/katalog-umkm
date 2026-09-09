<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$admin_nama = current_user()['nama'] ?? 'Admin';
$total_produk = $pdo->query("SELECT COUNT(*) c FROM produk WHERE status='aktif'")->fetch()['c'];
$total_umkm = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='pelaku_usaha'")->fetch()['c'];
$total_pesanan = $pdo->query("SELECT COUNT(*) c FROM pesanan")->fetch()['c'];
$pesanan_menunggu = $pdo->query("SELECT COUNT(*) c FROM pesanan WHERE status='menunggu_pembayaran'")->fetch()['c'];
$konfirmasi_pending = $pdo->query("SELECT COUNT(*) c FROM konfirmasi WHERE status_konfirmasi='pending'")->fetch()['c'];
$pendapatan_selesai = (float) $pdo->query("SELECT COALESCE(SUM(total),0) t FROM pesanan WHERE status='selesai'")->fetch()['t'];
$total_transaksi = (float) $pdo->query("SELECT COALESCE(SUM(total),0) t FROM pesanan WHERE status <> 'dibatalkan'")->fetch()['t'];
$offline_sales = $pdo->query(
    "SELECT COUNT(*) AS n, COALESCE(SUM(pm.qty * p.harga),0) AS nilai
     FROM penjualan_manual pm JOIN produk p ON pm.produk_id = p.id"
)->fetch();
$recent_orders = $pdo->query("
    SELECT ps.*, u.nama AS nama_pembeli
    FROM pesanan ps
    LEFT JOIN users u ON ps.user_id = u.id
    ORDER BY ps.tanggal DESC
    LIMIT 6
")->fetchAll();

$page_title = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>


<?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn d-lg-none" type="button">
            ☰ Menu
        </button>
        <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> Hari ini: <?= date('d M Y') ?></span>
    </div>

    <!-- Welcome Hero Banner -->
    <div class="card text-white mb-4 overflow-hidden border-0 shadow-sm" style="background: linear-gradient(135deg, var(--kaligawe-primary) 0%, #253a27 100%); border-radius: 16px;">
        <div class="card-body p-4 p-md-5 d-flex align-items-center justify-content-between position-relative">
            <div class="position-relative" style="z-index: 2;">
                <h2 class="fw-bold mb-2" style="font-family: var(--font-display);">Halo, <?= sanitize($admin_nama) ?>!</h2>
                <p class="text-white-50 mb-0 font-body small" style="max-width: 650px; font-weight: 300; line-height: 1.5;">
                    Selamat datang di Panel Kontrol Terintegrasi. Di sini Anda dapat mengawasi kemajuan dagang kelompok tani & produk kreatif UMKM Desa Kaligawe, memoderasi kategori produk, serta memverifikasi pembayaran pelanggan.
                </p>
            </div>
            <div class="d-none d-md-block opacity-25" style="transform: rotate(15deg); margin-right: 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
                    <path d="M5.338 1.59a.5.5 0 0 0-.242.047L1.096 3.62A.5.5 0 0 0 1 4.093v3.913c0 2.68.79 5.3 2.146 7.426a.5.5 0 0 0 .848-.004c1.328-2.112 2.112-4.7 2.112-7.375V4.093a.5.5 0 0 0-.096-.282l-2-2.5zm1.189 6.276a.5.5 0 0 1 .02.706l-2 2a.5.5 0 0 1-.707 0l-1-1a.5.5 0 1 1 .708-.708l.646.647 1.646-1.646a.5.5 0 0 1 .708 0z"/>
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
                        <div class="text-muted small fw-medium">Produk Aktif</div>
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
                        <div class="fs-2 fw-bold text-danger" style="font-family: var(--font-display);"><?= $total_umkm ?></div>
                        <div class="text-muted small fw-medium">Mitra UMKM / Tani</div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100 overflow-hidden" style="border-left: 4px solid var(--kaligawe-accent) !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-2 fw-bold text-warning" style="font-family: var(--font-display);"><?= $total_pesanan ?></div>
                        <div class="text-muted small fw-medium">Total Pesanan</div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card shadow-sm border-0 h-100 overflow-hidden" style="border-left: 4px solid #A44C4C !important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fs-2 fw-bold text-danger" style="font-family: var(--font-display); color: #A44C4C !important;"><?= $pesanan_menunggu ?></div>
                        <div class="text-muted small fw-medium">Pesanan Menunggu Bayar</div>
                        <?php if ($konfirmasi_pending > 0): ?>
                            <div class="small mt-1" style="color:#A44C4C;"><i class="bi bi-envelope-check me-1"></i><?= $konfirmasi_pending ?> bukti bayar menunggu verifikasi</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: rgba(164, 76, 76, 0.1); color: #A44C4C;">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- main dashboard sections split -->
    <div class="row g-4 mb-4">
        <!-- left column -->
        <div class="col-lg-8">
            <!-- financial chart card -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden" style="border-radius: 12px;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background-color: #FAF5EE;">
                    <div>
                        <div class="text-uppercase text-muted fw-bold small" style="font-size: 0.72rem; letter-spacing: 0.05em;"><i class="bi bi-graph-up-arrow me-1"></i>Ringkasan Transaksi</div>
                        <h5 class="text-secondary small mb-2 mt-1">Pendapatan dari pesanan berstatus selesai</h5>
                        <div class="display-6 fw-bold harga-produk m-0 text-success" style="font-family: var(--font-display); color: var(--kaligawe-primary) !important;"><?= rupiah($pendapatan_selesai) ?></div>
                        <div class="mt-3 d-flex flex-wrap gap-3 small text-muted">
                            <span><i class="bi bi-shop me-1"></i>Penjualan offline: <strong class="text-dark"><?= rupiah($offline_sales['nilai']) ?></strong> (<?= (int) $offline_sales['n'] ?> catatan)</span>
                        </div>
                    </div>
                    <div class="d-none d-sm-flex align-items-center justify-content-center bg-white p-4 rounded-circle shadow-sm border" style="width: 72px; height: 72px; color: var(--kaligawe-primary) !important;">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- right column -->
        <div class="col-lg-4">
            <!-- quick actions menu card -->
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <!-- offline manual sales summary -->
                    <div class="card shadow-sm border-0 mt-4 overflow-hidden" style="border-radius: 12px; background-color: #FAF5EE;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3 pb-2 border-bottom" style="font-family: var(--font-display); color: var(--kaligawe-primary);"><i class="bi bi-shop me-2 text-warning"></i>Penjualan Manual (Offline)</h5>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fs-3 fw-bold harga-produk m-0" style="color: var(--kaligawe-primary) !important;"><?= rupiah($offline_sales['nilai']) ?></div>
                                    <div class="text-muted small"><?= (int) $offline_sales['n'] ?> catatan penjualan tercatat</div>
                                </div>
                                <div class="d-none d-sm-flex align-items-center justify-content-center bg-white p-3 rounded-circle shadow-sm border" style="width: 56px; height: 56px; color: var(--kaligawe-primary) !important;">
                                    <i class="bi bi-bag-check fs-4"></i>
                                </div>
                            </div>
                            <p class="small text-muted mb-0 mt-3" style="font-size: 0.76rem; line-height: 1.45;">
                                Nilai penjualan offline dihitung dari catatan jumlah &times; harga produk di tabel yang dicatat oleh masing-masing mitra.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
