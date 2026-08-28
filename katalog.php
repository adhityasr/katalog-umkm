<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$jenis = in_array($_GET['jenis'] ?? '', ['umkm', 'pertanian']) ? $_GET['jenis'] : '';
$kategori_id = isset($_GET['kategori_id']) ? (int) $_GET['kategori_id'] : 0;
$q = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, k.nama_kategori, k.jenis, u.nama AS nama_penjual, u.no_hp AS hp_penjual
        FROM produk p
        JOIN kategori k ON p.kategori_id = k.id
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'aktif'";
$params = [];

if ($jenis !== '') {
    $sql .= " AND k.jenis = ?";
    $params[] = $jenis;
}
if ($kategori_id > 0) {
    $sql .= " AND p.kategori_id = ?";
    $params[] = $kategori_id;
}
if ($q !== '') {
    $sql .= " AND p.nama_produk LIKE ?";
    $params[] = "%$q%";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk_list = $stmt->fetchAll();

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori")->fetchAll();

$stat_mitra = (int) $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'pelaku_usaha'")->fetch()['c'];
$stat_produk = count($produk_list);
$stat_kategori = (int) $pdo->query("SELECT COUNT(*) c FROM kategori")->fetch()['c'];

$page_title = 'Katalog Produk';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO SECTION ===== -->
<section class="hero-landing" id="beranda">
    <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6">
            <span class="hero-eyebrow">UMKM &amp; Hasil Pertanian Desa Kaligawe</span>
            <h1 class="hero-title">Hasil Bumi &amp; Karya Warga, Langsung dari Desa</h1>
            <p class="hero-subtitle">
                Jelajahi produk olahan dan panen segar yang dibudidayakan sepenuh hati
                oleh pelaku UMKM dan petani Kaligawe &mdash; segar, terpercaya, dan mendukung ekonomi desa.
            </p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="#katalog" class="btn btn-success btn-cta">Jelajahi Katalog</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-success btn-cta">Daftar Jadi Mitra</a>
            </div>

            <div class="hero-stats row g-3 mt-4">
                <div class="col-4">
                    <div class="hero-stat-value"><?= $stat_produk ?></div>
                    <div class="hero-stat-label">Produk Aktif</div>
                </div>
                <div class="col-4">
                    <div class="hero-stat-value"><?= $stat_mitra ?></div>
                    <div class="hero-stat-label">Mitra Tani &amp; UMKM</div>
                </div>
                <div class="col-4">
                    <div class="hero-stat-value"><?= $stat_kategori ?></div>
                    <div class="hero-stat-label">Klaster Kategori</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="hero-visual" aria-hidden="true">
                <div class="hero-visual-halo"></div>
                <div class="hero-card fc-1">
                    <i class="bi bi-basket-fill hero-card-icon" aria-hidden="true"></i>
                    <div>
                        <div class="hero-card-title">Beras Kaligawe</div>
                        <div class="hero-card-price">Rp 13.000 / kg</div>
                    </div>
                </div>
                <div class="hero-card fc-2">
                    <i class="bi bi-sprout hero-card-icon" aria-hidden="true"></i>
                    <div>
                        <div class="hero-card-title">Jagung Pipil</div>
                        <div class="hero-card-price">Rp 6.000 / kg</div>
                    </div>
                </div>
                <div class="hero-card fc-3">
                    <i class="bi bi-cookie hero-card-icon" aria-hidden="true"></i>
                    <div>
                        <div class="hero-card-title">Lumpia Kaligawe</div>
                        <div class="hero-card-price">Rp 2.000 / pcs</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== ABOUT SECTION ===== -->
<section class="about-section" id="tentang">
    <div class="row align-items-center g-5">
        <div class="col-lg-6">
            <div class="about-visual" aria-hidden="true">
                <div class="about-visual-panel">
                    <div class="about-illustration">
                        <div class="ill-ring ill-ring-1"></div>
                        <div class="ill-ring ill-ring-2"></div>
                        <div class="ill-circle ill-circle-1"><i class="bi bi-basket-fill"></i></div>
                        <div class="ill-circle ill-circle-2"><i class="bi bi-sprout"></i></div>
                        <div class="ill-circle ill-circle-3"><i class="bi bi-hand-thumbs-up-fill"></i></div>
                    </div>
                </div>
                <div class="about-float-card">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <div class="about-float-value"><?= $stat_mitra ?>+</div>
                        <div class="about-float-label">Mitra Lokal Aktif</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <span class="section-eyebrow">Siapa Kami</span>
            <h2 class="section-title">Pasar Kaligawe, Pasar Digital Milik Warga Desa</h2>
            <p class="about-text">
                Sistem Informasi E-Commerce Terintegrasi Desa Kaligawe mempertemukan
                pelaku UMKM, kelompok tani, dan BUMDes dengan pembeli dari mana saja.
                Kami lahir dari semangat gotong royong: setiap transaksi yang terjadi
                di sini adalah bentuk nyata dukungan terhadap perekonomian warga desa.
            </p>
            <p class="about-text">
                Dari lumpia dan rengginang khas, beras dan jagung hasil panen, hingga
                aneka kerajinan tangan &mdash; semua tersaji transparan, bersih, dan
                langsung dari sumbernya.
            </p>
            <ul class="about-checklist list-unstyled mt-4 mb-0">
                <li><i class="bi bi-check-circle-fill"></i> Produk segar langsung dari petani &amp; pelaku UMKM lokal</li>
                <li><i class="bi bi-check-circle-fill"></i> Pembayaran terverifikasi dengan bukti transfer</li>
                <li><i class="bi bi-check-circle-fill"></i> Mendukung pencatatan penjualan mitra secara otomatis</li>
            </ul>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how-section py-5" id="cara-belanja">
    <div class="text-center mb-5">
        <span class="section-eyebrow">Cara Belanja</span>
        <h2 class="section-title-center">Tiga Langkah Mudah Berbelanja</h2>
        <p class="how-intro text-muted">Dari desa ke rumah Anda dalam sekali klik.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="how-card h-100">
                <div class="how-card-icon"><i class="bi bi-search"></i></div>
                <div class="how-card-step">Langkah 1</div>
                <h5 class="how-card-title">Jelajahi Katalog</h5>
                <p class="how-card-text">Cari produk olahan atau hasil panen sesuai keinginan, lengkap dengan info penjual dan harga satuan.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="how-card h-100">
                <div class="how-card-icon"><i class="bi bi-bag-check"></i></div>
                <div class="how-card-step">Langkah 2</div>
                <h5 class="how-card-title">Pesan &amp; Unggah Bukti</h5>
                <p class="how-card-text">Isi data penerima, checkout, lalu unggah bukti transfer. Admin desa memverifikasi pembayaran Anda.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="how-card h-100">
                <div class="how-card-icon"><i class="bi bi-box-seam"></i></div>
                <div class="how-card-step">Langkah 3</div>
                <h5 class="how-card-title">Terima &amp; Nikmati</h5>
                <p class="how-card-text">Pesanan diproses penjual dan dikirim ke alamat Anda. Setiap transaksi mendukung ekonomi desa.</p>
            </div>
        </div>
    </div>
</section>

<section class="catalog-section py-5" id="katalog">
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title m-0">Katalog Produk</h2>
    <span class="text-muted small"><?= count($produk_list) ?> produk tersedia</span>
</div>

<form method="get" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Cari nama produk..." value="<?= sanitize($q) ?>">
    </div>
    <div class="col-md-3">
        <select name="jenis" class="form-select" onchange="this.form.submit()">
            <option value="">Semua jenis</option>
            <option value="umkm" <?= $jenis === 'umkm' ? 'selected' : '' ?>>Produk UMKM</option>
            <option value="pertanian" <?= $jenis === 'pertanian' ? 'selected' : '' ?>>Hasil Pertanian</option>
        </select>
    </div>
    <div class="col-md-3">
        <select name="kategori_id" class="form-select" onchange="this.form.submit()">
            <option value="0">Semua kategori</option>
            <?php foreach ($kategori_list as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $kategori_id === (int) $k['id'] ? 'selected' : '' ?>>
                    <?= sanitize($k['nama_kategori']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-success w-100">Cari</button>
    </div>
</form>

<?php if (empty($produk_list)): ?>
    <div class="alert alert-secondary">Belum ada produk yang cocok dengan pencarian Anda.</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
    <?php foreach ($produk_list as $p): $stockPct = min(100, max(8, ((int) $p['stok'] / 30) * 100)); $isLow = (int) $p['stok'] <= 5; ?>
        <div class="col stagger-item">
            <div class="card h-100 shadow-sm card-produk">
                <?php if ($p['foto']): ?>
                    <img src="<?= BASE_URL ?>/assets/uploads/<?= sanitize($p['foto']) ?>" class="card-img-top produk-img" alt="<?= sanitize($p['nama_produk']) ?>">
                <?php else: ?>
                    <div class="produk-img-placeholder">Tidak ada foto</div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                    <div class="mb-2 align-self-start">
                        <span class="badge <?= $p['jenis'] === 'umkm' ? 'badge-umkm' : 'badge-pertanian' ?>">
                            <?= sanitize($p['nama_kategori']) ?>
                        </span>
                        <span class="badge bg-secondary ms-1">
                            <?= sanitize(ucwords(str_replace('_', ' ', $p['sumber_usaha'] ?? 'perorangan'))) ?>
                        </span>
                    </div>
                    <h6 class="card-title"><?= sanitize($p['nama_produk']) ?></h6>
                    <p class="small text-muted mb-1"><i class="bi bi-person me-1"></i>Oleh: <?= sanitize($p['nama_penjual']) ?></p>
                    <p class="harga-produk mb-1"><?= rupiah($p['harga']) ?> / <?= sanitize($p['satuan']) ?></p>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="small text-muted" style="font-size:0.78rem"><i class="bi bi-box-seam me-1"></i><?= (int) $p['stok'] ?> <?= sanitize($p['satuan']) ?> tersedia</span>
                        <?php if ($isLow): ?><span class="badge bg-warning text-dark" style="font-size:0.65rem">Stok menipis</span><?php endif; ?>
                    </div>
                    <div class="stock-bar mb-3"><div class="stock-bar-fill <?= $isLow ? 'low' : '' ?>" style="width: <?= $stockPct ?>%"></div></div>
                    <?php
                    $wa_text_k = 'Halo Kak ' . $p['nama_penjual'] . ', saya tertarik dengan *' . $p['nama_produk'] . '* (' . rupiah($p['harga']) . '/' . $p['satuan'] . ') di Pasar Kaligawe. Apakah masih tersedia? ' . BASE_URL . '/produk_detail.php?id=' . $p['id'];
                    $wa_url_k = wa_link($p['hp_penjual'] ?? '', $wa_text_k);
                    ?>
                    <div class="d-flex gap-2 mt-auto">
                        <a href="<?= BASE_URL ?>/produk_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-success btn-sm flex-grow-1">Lihat detail</a>
                        <?php if ($wa_url_k): ?>
                            <a href="<?= $wa_url_k ?>" target="_blank" rel="noopener" class="katalog-wa-btn" title="Chat <?= sanitize($p['nama_penjual']) ?> via WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<!-- ===== CTA BANNER ===== -->
<section class="cta-banner">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <h2 class="cta-title">Punya produk UMKM atau hasil panen?</h2>
            <p class="cta-text mb-0">Daftar gratis dan jual langsung ke ribuan pembeli. Kami bantu catat penjualan Anda secara otomatis.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-light btn-cta">Daftar Jadi Mitra</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
