<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pesanan_id = (int) ($_GET['pesanan_id'] ?? $_POST['pesanan_id'] ?? 0);

// Admin/pelaku_usaha tidak boleh mengunggah bukti
if (is_logged_in() && current_user()['role'] !== 'pembeli') {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Hanya pembeli yang dapat mengunggah bukti pembayaran.'];
    redirect('/katalog.php');
}

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id = ?");
$stmt->execute([$pesanan_id]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    // Fallback untuk pembeli login
    if (is_logged_in() && current_user()['role'] === 'pembeli') {
        redirect('/pesanan_saya.php');
    }
    redirect('/katalog.php');
}

// Validasi kepemilikan pesanan
$user = current_user();
$is_guest = !is_logged_in();
if ($user && $user['role'] === 'pembeli') {
    if ((int) $pesanan['user_id'] !== (int) $user['id']) {
        redirect('/pesanan_saya.php');
    }
} elseif ($is_guest) {
    // Tamu hanya boleh akses pesanan tanpa user_id
    if ($pesanan['user_id'] !== null) {
        redirect('/katalog.php');
    }
    // Jika ada daftar guest_orders di session, pastikan pesanan ini termasuk (jika ada)
    if (!empty($_SESSION['guest_orders']) && !in_array($pesanan_id, $_SESSION['guest_orders'])) {
        // tetap izinkan jika pesanan memang tamu — jangan terlalu ketat
    }
} else {
    redirect('/katalog.php');
}

// Ambil kontak penjual untuk tombol WhatsApp
$sellers = [];
$stmt = $pdo->prepare("SELECT DISTINCT u.nama AS nama_penjual, u.no_hp AS hp_penjual FROM detail_pesanan dp JOIN produk p ON dp.produk_id = p.id JOIN users u ON p.user_id = u.id WHERE dp.pesanan_id = ?");
$stmt->execute([$pesanan_id]);
$sellers = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $filename = upload_foto($_FILES['bukti_bayar'] ?? null, __DIR__ . '/assets/uploads');
        if (!$filename) {
            $errors[] = 'Silakan pilih file bukti pembayaran.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO konfirmasi (pesanan_id, bukti_bayar, status_konfirmasi) VALUES (?, ?, 'pending')"
            );
            $stmt->execute([$pesanan_id, $filename]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.'];
            if ($user && $user['role'] === 'pembeli') {
                redirect('/pesanan_saya.php');
            } else {
                redirect('/katalog.php');
            }
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

$page_title = 'Unggah Bukti Pembayaran';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card form-card shadow-sm overflow-hidden">
            <div class="form-hero">
                <div class="form-hero-icon"><i class="bi bi-receipt-cutoff"></i></div>
                <div>
                    <h4>Unggah Bukti Pembayaran</h4>
                    <p>Pesanan #<?= $pesanan['id'] ?> • <?= rupiah($pesanan['total']) ?> • <?= sanitize($pesanan['nama_penerima']) ?></p>
                </div>
            </div>
            <div class="form-body">
                <?php if ($is_guest): ?>
                    <div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3" style="border-radius:10px;font-size:0.85rem"><i class="bi bi-info-circle"></i> Pesanan tamu — simpan nomor <strong>#<?= $pesanan['id'] ?></strong> untuk melacak status.</div>
                <?php endif; ?>

                <?php if (!empty($sellers)): ?>
                    <div class="wa-cta-card mb-4" style="flex-direction: column; align-items: stretch;">
                        <div class="d-flex align-items-center gap-3 w-100">
                            <div class="wa-cta-icon"><i class="bi bi-whatsapp"></i></div>
                            <div class="wa-cta-body">
                                <div class="wa-cta-title">Lanjut ke WhatsApp Penjual?</div>
                                <div class="wa-cta-sub">Konfirmasi pesanan #<?= $pesanan['id'] ?> agar diproses cepat</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php foreach ($sellers as $s):
                                $wa_text_u = 'Halo Kak ' . $s['nama_penjual'] . ', saya pembeli pesanan #' . $pesanan['id'] . ' di Pasar Kaligawe (total ' . rupiah($pesanan['total']) . '). Mohon konfirmasi pesanan saya.';
                                $wa_url_u = wa_link($s['hp_penjual'], $wa_text_u);
                            ?>
                                <?php if ($wa_url_u): ?>
                                    <a href="<?= $wa_url_u ?>" target="_blank" rel="noopener" class="wa-seller-chip" title="Chat <?= sanitize($s['nama_penjual']) ?> via WhatsApp">
                                        <span class="wa-chip-icon"><i class="bi bi-whatsapp"></i></span>
                                        <?= sanitize($s['nama_penjual']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="small text-muted"><?= sanitize($s['nama_penjual']) ?> (HP tidak tersedia)</span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-octagon me-1"></i><?= sanitize($err) ?></div>
                <?php endforeach; ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="pesanan_id" value="<?= $pesanan_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Bukti transfer</label>
                        <div class="p-3" style="border: 2px dashed rgba(59,91,62,0.18); border-radius: 12px; background: #F8FAF6; text-align: center;">
                            <div class="mb-2" style="font-size:2rem;color: var(--kaligawe-primary);"><i class="bi bi-cloud-arrow-up"></i></div>
                            <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png" required style="border-radius:10px">
                            <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>JPG/PNG maksimal 2MB. Pastikan bukti jelas terbaca.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-save w-100"><i class="bi bi-send me-1"></i> Kirim Bukti Pembayaran</button>
                    <a href="<?= BASE_URL ?>/pesanan_saya.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px">Nanti saja</a>
                </form>
                <div class="trust-badges mt-3 justify-content-center">
                    <span class="trust-badge"><i class="bi bi-shield-check text-success"></i> Aman</span>
                    <span class="trust-badge"><i class="bi bi-patch-check-fill text-success"></i> Admin verifikasi</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>