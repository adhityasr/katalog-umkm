<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Hanya admin/pelaku_usaha yang tidak boleh mengakses keranjang
if (is_logged_in() && current_user()['role'] !== 'pembeli') {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Hanya pembeli yang dapat mengakses keranjang.'];
    redirect('/katalog.php');
}

$user = current_user();
$is_guest = !is_logged_in();
$items = [];

if ($user && $user['role'] === 'pembeli') {
    $user_id = $user['id'];
    $stmt = $pdo->prepare(
        "SELECT ke.id AS keranjang_id, ke.qty, p.id AS produk_id, p.nama_produk, p.harga, p.satuan, p.stok, p.foto
         FROM keranjang ke
         JOIN produk p ON ke.produk_id = p.id
         WHERE ke.user_id = ?
         ORDER BY ke.created_at DESC"
    );
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
} else {
    $items = guest_cart_items($pdo);
}

$total = 0;
foreach ($items as $it) {
    $total += $it['harga'] * $it['qty'];
}

$page_title = 'Keranjang Belanja';
require_once __DIR__ . '/includes/header.php';
?>

<div class="stepper">
    <div class="stepper-step active"><span class="stepper-circle">1</span> Keranjang</div>
    <div class="stepper-line"></div>
    <div class="stepper-step"><span class="stepper-circle">2</span> Checkout</div>
    <div class="stepper-line"></div>
    <div class="stepper-step"><span class="stepper-circle">3</span> Bayar</div>
</div>

<h3 class="page-section-title mb-4" style="font-family: var(--font-display);">Keranjang Belanja</h3>

<?php if (empty($items)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-basket"></i></div>
        <h5 class="fw-semibold mb-2" style="color: var(--kaligawe-primary);">Keranjang masih kosong</h5>
        <p class="text-muted small mb-4" style="max-width: 420px; margin: 0 auto;">Jelajahi katalog produk segar dan karya UMKM Desa Kaligawe. Tambahkan favorit Anda tanpa perlu login.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-success px-4" style="border-radius: 12px;"><i class="bi bi-search me-1"></i> Jelajahi Katalog</a>
            <a href="<?= BASE_URL ?>/katalog.php#katalog" class="btn btn-outline-success px-4" style="border-radius: 12px;">Lihat produk terlaris</a>
        </div>
    </div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom" style="background: linear-gradient(135deg, #F8FAF6 0%, #F0FAF4 100%);">
                <h6 class="mb-0 fw-semibold" style="color: var(--kaligawe-primary);"><i class="bi bi-basket me-2"></i>Keranjang • <?= count($items) ?> item</h6>
                <span class="small text-muted d-none d-sm-inline"><i class="bi bi-shield-check me-1 text-success"></i>Aman • Tanpa login</span>
            </div>
            <?php foreach ($items as $it): $isLow = (int) $it['stok'] <= 5; $subtotal = $it['harga'] * $it['qty']; ?>
                <div class="d-flex gap-3 p-3 align-items-center border-bottom" style="border-color: rgba(59,91,62,0.08) !important;">
                    <div style="width:72px;height:72px;border-radius:12px;overflow:hidden;background:#E8E3D8;flex-shrink:0;border:1px solid rgba(59,91,62,0.08);">
                        <?php if (!empty($it['foto'])): ?>
                            <img src="<?= BASE_URL ?>/assets/uploads/<?= sanitize($it['foto']) ?>" alt="<?= sanitize($it['nama_produk']) ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center w-100 h-100" style="color:#8C867B"><i class="bi bi-image" style="font-size:1.4rem"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-width-0" style="min-width:0">
                        <div class="fw-semibold text-truncate" style="font-size:0.95rem;color:#1A3320;"><?= sanitize($it['nama_produk']) ?></div>
                        <div class="small text-muted"><?= rupiah($it['harga']) ?> / <?= sanitize($it['satuan']) ?> • Stok <?= (int) $it['stok'] ?></div>
                        <?php if ($isLow): ?><span class="badge bg-warning text-dark mt-1" style="font-size:0.65rem"><i class="bi bi-exclamation-triangle me-1"></i>Sisa <?= (int) $it['stok'] ?></span><?php endif; ?>
                        <div class="fw-bold d-lg-none mt-1" style="color:var(--kaligawe-primary);font-size:0.95rem"><?= rupiah($subtotal) ?></div>
                    </div>
                    <div style="min-width: 150px;">
                        <form method="post" action="<?= BASE_URL ?>/update_keranjang.php" class="d-flex gap-1 align-items-center">
                            <?php if ($is_guest): ?>
                                <input type="hidden" name="produk_id" value="<?= $it['produk_id'] ?>">
                            <?php else: ?>
                                <input type="hidden" name="keranjang_id" value="<?= $it['keranjang_id'] ?>">
                            <?php endif; ?>
                            <div class="input-group input-group-sm" style="width:118px">
                                <button type="button" class="btn btn-outline-secondary btn-qty-minus" style="min-width:32px">-</button>
                                <input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" max="<?= (int) $it['stok'] ?>" class="form-control text-center fw-semibold" style="padding:0.25rem">
                                <button type="button" class="btn btn-outline-secondary btn-qty-plus" style="min-width:32px">+</button>
                            </div>
                            <button type="submit" class="btn btn-outline-success btn-sm" title="Update jumlah"><i class="bi bi-check-lg"></i></button>
                        </form>
                    </div>
                    <div class="text-end d-none d-lg-block" style="min-width: 110px;">
                        <div class="fw-bold" style="color:var(--kaligawe-primary)"><?= rupiah($subtotal) ?></div>
                        <form method="post" action="<?= BASE_URL ?>/hapus_keranjang.php" class="form-confirm-delete d-inline" data-message="Hapus produk ini dari keranjang?">
                            <?php if ($is_guest): ?>
                                <input type="hidden" name="produk_id" value="<?= $it['produk_id'] ?>">
                            <?php else: ?>
                                <input type="hidden" name="keranjang_id" value="<?= $it['keranjang_id'] ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-link text-danger p-0 mt-1" style="font-size:0.82rem;text-decoration:none"><i class="bi bi-trash3 me-1"></i>Hapus</button>
                        </form>
                    </div>
                    <div class="d-lg-none">
                        <form method="post" action="<?= BASE_URL ?>/hapus_keranjang.php" class="form-confirm-delete" data-message="Hapus produk ini dari keranjang?">
                            <?php if ($is_guest): ?>
                                <input type="hidden" name="produk_id" value="<?= $it['produk_id'] ?>">
                            <?php else: ?>
                                <input type="hidden" name="keranjang_id" value="<?= $it['keranjang_id'] ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="p-3 d-flex justify-content-between align-items-center" style="background:#FAF5EE">
                <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-outline-secondary btn-sm" style="border-radius: 10px;"><i class="bi bi-arrow-left me-1"></i> Lanjut belanja</a>
                <span class="small text-muted">Total <?= count($items) ?> produk</span>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="position: sticky; top: calc(var(--navbar-height) + 1rem); border-top: 4px solid var(--kaligawe-accent) !important; overflow: hidden;">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3" style="color: var(--kaligawe-primary);"><i class="bi bi-receipt me-2"></i>Ringkasan Belanja</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Subtotal (<?= count($items) ?> item)</span>
                    <span class="fw-semibold"><?= rupiah($total) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Pengiriman</span>
                    <span class="small text-success fw-medium">Dihitung penjual via WA</span>
                </div>
                <hr style="opacity:0.15">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold">Total</span>
                    <span class="fs-4 fw-bold" style="color: var(--kaligawe-primary); font-family: var(--font-display);"><?= rupiah($total) ?></span>
                </div>
                <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-success w-100" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(59,91,62,0.22);"><i class="bi bi-bag-check me-1"></i> Lanjut ke Checkout</a>
                <div class="text-center mt-3">
                    <span class="small text-muted"><i class="bi bi-lock me-1"></i>Pembayaran aman • Admin verifikasi</span>
                </div>
                <div class="trust-badges mt-3 justify-content-center">
                    <span class="trust-badge"><i class="bi bi-shield-check text-success"></i> Aman</span>
                    <span class="trust-badge"><i class="bi bi-truck text-primary"></i> Desa</span>
                    <span class="trust-badge"><i class="bi bi-whatsapp" style="color:#25D366"></i> WA</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>