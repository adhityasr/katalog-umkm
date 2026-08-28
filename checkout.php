<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Admin/pelaku_usaha tidak boleh checkout
if (is_logged_in() && current_user()['role'] !== 'pembeli') {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Hanya pembeli yang dapat melakukan checkout.'];
    redirect('/katalog.php');
}

$user = current_user();
$is_guest = !is_logged_in();
$user_id = $user['id'] ?? null;

if ($user && $user['role'] === 'pembeli') {
    $stmt = $pdo->prepare(
        "SELECT ke.id AS keranjang_id, ke.qty, p.id AS produk_id, p.nama_produk, p.harga, p.satuan, p.stok, p.foto
         FROM keranjang ke
         JOIN produk p ON ke.produk_id = p.id
         WHERE ke.user_id = ?"
    );
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
} else {
    $items = guest_cart_items($pdo);
}

if (empty($items)) {
    redirect('/keranjang.php');
}

$total = 0;
foreach ($items as $it) {
    $total += $it['harga'] * $it['qty'];
}

$errors = [];
$profil = ['nama' => '', 'alamat' => '', 'no_hp' => ''];
if ($user && $user['role'] === 'pembeli') {
    $stmt = $pdo->prepare("SELECT nama, alamat, no_hp FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $profil = $stmt->fetch() ?: $profil;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $alamat_pengiriman = trim($_POST['alamat_pengiriman'] ?? '');
    $no_hp_penerima = trim($_POST['no_hp_penerima'] ?? '');

    if ($nama_penerima === '' || $alamat_pengiriman === '' || $no_hp_penerima === '') {
        $errors[] = 'Semua data penerima wajib diisi.';
    }

    // Validasi ulang stok sebelum membuat pesanan
    foreach ($items as $it) {
        if ($it['qty'] > $it['stok']) {
            $errors[] = "Stok {$it['nama_produk']} tidak mencukupi (tersisa {$it['stok']}).";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO pesanan (user_id, total, nama_penerima, alamat_pengiriman, no_hp_penerima, status)
                 VALUES (?, ?, ?, ?, ?, 'menunggu_pembayaran')"
            );
            $stmt->execute([$user_id, $total, $nama_penerima, $alamat_pengiriman, $no_hp_penerima]);
            $pesanan_id = $pdo->lastInsertId();

            $stmt_detail = $pdo->prepare(
                "INSERT INTO detail_pesanan (pesanan_id, produk_id, qty, harga_satuan, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt_stok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?");

            foreach ($items as $it) {
                $subtotal = $it['harga'] * $it['qty'];
                $stmt_detail->execute([$pesanan_id, $it['produk_id'], $it['qty'], $it['harga'], $subtotal]);

                $stmt_stok->execute([$it['qty'], $it['produk_id'], $it['qty']]);
                if ($stmt_stok->rowCount() === 0) {
                    throw new Exception("Stok {$it['nama_produk']} berubah, silakan coba lagi.");
                }
            }

            if ($user && $user['role'] === 'pembeli') {
                $stmt = $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
                $stmt->execute([$user_id]);
            } else {
                $_SESSION['keranjang_guest'] = [];
                // Simpan daftar pesanan tamu agar bisa unggah bukti
                $_SESSION['guest_orders'] = $_SESSION['guest_orders'] ?? [];
                $_SESSION['guest_orders'][] = (int) $pesanan_id;
            }

            $pdo->commit();

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pesanan berhasil dibuat. Silakan unggah bukti pembayaran.'];
            redirect('/upload_bukti.php?pesanan_id=' . $pesanan_id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal membuat pesanan: ' . $e->getMessage();
        }
    }
}

$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="stepper">
    <div class="stepper-step done"><span class="stepper-circle"><i class="bi bi-check-lg"></i></span> Keranjang</div>
    <div class="stepper-line filled"></div>
    <div class="stepper-step active"><span class="stepper-circle">2</span> Checkout</div>
    <div class="stepper-line"></div>
    <div class="stepper-step"><span class="stepper-circle">3</span> Bayar</div>
</div>

<h3 class="page-section-title mb-4" style="font-family: var(--font-display);">Checkout</h3>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-octagon me-1"></i><?= sanitize($err) ?></div>
<?php endforeach; ?>

<?php if ($is_guest): ?>
    <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Anda checkout sebagai <strong>tamu</strong> — tidak perlu login. Isi data penerima dengan lengkap.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card form-card shadow-sm overflow-hidden">
            <div class="form-hero">
                <div class="form-hero-icon"><i class="bi bi-truck"></i></div>
                <div>
                    <h4>Data Penerima</h4>
                    <p>Alamat pengiriman akan diteruskan ke penjual via WhatsApp</p>
                </div>
            </div>
            <div class="form-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Nama penerima</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="nama_penerima" class="form-control" placeholder="Nama lengkap penerima"
                                   value="<?= sanitize($_POST['nama_penerima'] ?? $profil['nama']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat pengiriman</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <textarea name="alamat_pengiriman" class="form-control" rows="2" placeholder="Jalan, RT/RW, Desa, Kecamatan..." required><?= sanitize($_POST['alamat_pengiriman'] ?? $profil['alamat']) ?></textarea>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">No. HP / WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="no_hp_penerima" class="form-control" placeholder="08xxxxxxxxxx"
                                   value="<?= sanitize($_POST['no_hp_penerima'] ?? $profil['no_hp']) ?>" required>
                        </div>
                        <div class="form-text"><i class="bi bi-whatsapp me-1" style="color:#25D366"></i>Nomor ini akan dipakai penjual menghubungi Anda via WhatsApp.</div>
                    </div>
                    <button type="submit" class="btn btn-save w-100 btn-loading-on-submit" data-loading-text="Memproses..." style="padding:0.85rem">
                        <i class="bi bi-bag-check me-1"></i> Buat Pesanan — <?= rupiah($total) ?>
                    </button>
                    <a href="<?= BASE_URL ?>/keranjang.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px"><i class="bi bi-arrow-left me-1"></i> Kembali ke keranjang</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm" style="position: sticky; top: calc(var(--navbar-height) + 1rem); border-top: 4px solid var(--kaligawe-accent) !important; overflow: hidden;">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3" style="color: var(--kaligawe-primary);"><i class="bi bi-receipt me-2"></i>Ringkasan Pesanan • <?= count($items) ?> item</h6>
                <div class="d-flex flex-column gap-3 mb-3">
                    <?php foreach ($items as $it): ?>
                        <div class="d-flex gap-3 align-items-center">
                            <div style="width:56px;height:56px;border-radius:10px;overflow:hidden;background:#E8E3D8;flex-shrink:0;border:1px solid rgba(59,91,62,0.08);">
                                <?php if (!empty($it['foto'])): ?>
                                    <img src="<?= BASE_URL ?>/assets/uploads/<?= sanitize($it['foto']) ?>" alt="<?= sanitize($it['nama_produk']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center w-100 h-100" style="color:#8C867B"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-semibold text-truncate" style="font-size:0.9rem;color:#1A3320;"><?= sanitize($it['nama_produk']) ?></div>
                                <div class="small text-muted"><?= rupiah($it['harga']) ?> × <?= $it['qty'] ?> <?= sanitize($it['satuan']) ?></div>
                            </div>
                            <div class="fw-semibold" style="color:var(--kaligawe-primary)"><?= rupiah($it['harga'] * $it['qty']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr style="opacity:0.12">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Subtotal</span>
                    <span class="fw-semibold"><?= rupiah($total) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Pengiriman</span>
                    <span class="small text-success">Via WA penjual</span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 mb-3" style="background: linear-gradient(135deg, #F8FAF6 0%, #F0FAF4 100%); border: 1px solid rgba(59,91,62,0.08); border-radius: 12px;">
                    <span class="fw-bold">Total</span>
                    <span class="fs-4 fw-bold" style="color: var(--kaligawe-primary); font-family: var(--font-display);"><?= rupiah($total) ?></span>
                </div>
                <div class="d-flex align-items-center gap-2 small text-muted" style="background: rgba(37,211,102,0.06); border: 1px solid rgba(37,211,102,0.15); border-radius: 10px; padding: 0.6rem 0.75rem;">
                    <i class="bi bi-shield-check text-success" style="font-size:1.1rem"></i>
                    <span>Setelah pesan, lanjut ke <strong>WhatsApp penjual</strong> untuk konfirmasi cepat.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>