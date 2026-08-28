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
    echo '<a href="' . BASE_URL . '/katalog.php" class="btn btn-success">Kembali ke katalog</a>';
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
                <div class="mb-2 d-flex flex-wrap gap-2">
                    <span class="badge <?= $p['jenis'] === 'umkm' ? 'badge-umkm' : 'badge-pertanian' ?>"><?= sanitize($p['nama_kategori']) ?></span>
                    <span class="badge bg-secondary ms-1"><?= sanitize(ucwords(str_replace('_', ' ', $p['sumber_usaha'] ?? 'perorangan'))) ?></span>
                </div>
                <h3 class="mb-1"><?= sanitize($p['nama_produk']) ?></h3>
                <p class="text-muted mb-3">Penjual: <?= sanitize($p['nama_penjual']) ?> &middot; <?= sanitize($p['hp_penjual']) ?></p>
                <h4 class="harga-produk mb-3"><?= rupiah($p['harga']) ?> / <?= sanitize($p['satuan']) ?></h4>
                <hr>
                <p class="mb-3"><?= nl2br(sanitize($p['deskripsi'])) ?></p>
                <p class="small text-muted mb-3">
                    <i class="bi bi-box-seam me-1"></i>Stok tersedia: <?= (int) $p['stok'] ?> <?= sanitize($p['satuan']) ?>
                </p>
                <div class="d-flex align-items-center gap-3 p-3 mb-3" style="background: linear-gradient(135deg, #F8FAF6 0%, #F0FAF4 100%); border: 1px solid rgba(59,91,62,0.08); border-radius: 12px;">
                    <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--kaligawe-accent) 0%,#C9873A 100%);color:#fff;font-weight:700;font-size:1.1rem;"><?= strtoupper(substr($p['nama_penjual'],0,1)) ?></div>
                    <div>
                        <div class="fw-semibold" style="font-size:0.95rem;color:#1A3320;"><?= sanitize($p['nama_penjual']) ?></div>
                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= sanitize($p['hp_penjual']) ?> • <span class="text-success"><i class="bi bi-patch-check-fill me-1"></i>Penjual terverifikasi</span></div>
                    </div>
                </div>
                <div class="trust-badges mb-4">
                    <span class="trust-badge"><i class="bi bi-patch-check-fill text-success"></i> Produk asli desa</span>
                    <span class="trust-badge"><i class="bi bi-truck text-primary"></i> Pengiriman aman</span>
                    <span class="trust-badge"><i class="bi bi-shield-check" style="color:#A16207"></i> Pembayaran terverifikasi</span>
                </div>

                <?php
                $wa_hp = $p['hp_penjual'] ?? '';
                $wa_text = 'Halo Kak ' . $p['nama_penjual'] . ', saya tertarik dengan *' . $p['nama_produk'] . '* (' . rupiah($p['harga']) . '/' . $p['satuan'] . ') di Pasar Kaligawe. Apakah masih tersedia? ' . (defined('BASE_URL') ? BASE_URL : '') . '/produk_detail.php?id=' . $p['id'];
                $wa_url = wa_link($wa_hp, $wa_text);
                ?>
                <?php if (is_logged_in() && current_user()['role'] !== 'pembeli'): ?>
                    <div class="alert alert-info">Hanya akun pembeli yang dapat menambahkan produk ke keranjang.</div>
                    <?php if ($wa_url): ?>
                        <div class="wa-cta-card">
                            <div class="wa-cta-icon"><i class="bi bi-whatsapp"></i></div>
                            <div class="wa-cta-body">
                                <div class="wa-cta-title">Mau tanya penjual?</div>
                                <div class="wa-cta-sub">Chat <?= sanitize($p['nama_penjual']) ?> langsung via WhatsApp</div>
                            </div>
                            <a href="<?= $wa_url ?>" target="_blank" rel="noopener" class="btn btn-wa">Chat Sekarang <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    <?php endif; ?>
                <?php elseif ($p['stok'] < 1): ?>
                    <button class="btn btn-secondary" disabled>Stok habis</button>
                    <?php if ($wa_url): ?>
                        <div class="wa-cta-card">
                            <div class="wa-cta-icon"><i class="bi bi-whatsapp"></i></div>
                            <div class="wa-cta-body">
                                <div class="wa-cta-title">Stok habis? Tanya kapan restok</div>
                                <div class="wa-cta-sub">Hubungi <?= sanitize($p['nama_penjual']) ?> via WhatsApp</div>
                            </div>
                            <a href="<?= $wa_url ?>" target="_blank" rel="noopener" class="btn btn-wa">Chat Penjual <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="post" action="<?= BASE_URL ?>/tambah_keranjang.php" class="d-flex flex-wrap align-items-center gap-2">
                        <input type="hidden" name="produk_id" value="<?= $p['id'] ?>">
                        <div class="input-group" style="max-width: 140px">
                            <button type="button" class="btn btn-outline-secondary btn-qty-minus">-</button>
                            <input type="number" name="qty" value="1" min="1" max="<?= (int) $p['stok'] ?>" class="form-control text-center fw-semibold">
                            <button type="button" class="btn btn-outline-secondary btn-qty-plus">+</button>
                        </div>
                        <button type="submit" class="btn btn-cart"><i class="bi bi-basket3"></i> Tambah ke Keranjang</button>
                    </form>
                    <?php if (!is_logged_in()): ?>
                        <p class="small text-muted mt-2">
                    <?php endif; ?>
                    <?php if ($wa_url): ?>
                        <div class="wa-cta-card">
                            <div class="wa-cta-icon"><i class="bi bi-whatsapp"></i></div>
                            <div class="wa-cta-body">
                                <div class="wa-cta-title">Lanjut ke WhatsApp Penjual?</div>
                                <div class="wa-cta-sub">Chat <?= sanitize($p['nama_penjual']) ?></div>
                            </div>
                            <a href="<?= $wa_url ?>" target="_blank" rel="noopener" class="btn btn-wa">Chat Sekarang <i class="bi bi-whatsapp ms-1"></i></a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
