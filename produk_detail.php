<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT p.*, k.nama_kategori, k.jenis, u.nama AS nama_penjual, u.no_hp AS hp_penjual
     FROM produk p
     JOIN kategori k ON p.kategori_id = k.id
     JOIN users u ON p.user_id = u.id
     WHERE p.id = ? AND p.status = 'aktif'"
);
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $page_title = 'Produk tidak ditemukan';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-danger">Produk tidak ditemukan atau sudah tidak tersedia.</div>';
    echo '<a href="' . BASE_URL . '/index.php" class="btn btn-success">Kembali ke katalog</a>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $p['nama_produk'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 product-detail-card">
            <?php if ($p['foto']): ?>
                <img src="<?= BASE_URL ?>/assets/uploads/<?= sanitize($p['foto']) ?>" class="card-img-top product-detail-img" alt="<?= sanitize($p['nama_produk']) ?>">
            <?php else: ?>
                <div class="produk-img-placeholder product-detail-img">Tidak ada foto</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 product-detail-card h-100">
            <div class="card-body p-4 p-md-5">
                <h3 class="mb-2"><?= sanitize($p['nama_produk']) ?></h3>
                <h4 class="harga-produk mb-3"><?= rupiah($p['harga']) ?> / <?= sanitize($p['satuan']) ?></h4>
                <hr>
                <p class="mb-0" style="line-height:1.7"><?= nl2br(sanitize($p['deskripsi'])) ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
