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
    redirect('/katalog.php');
}

// Validasi kepemilikan pesanan
$user = current_user();
$is_guest = !is_logged_in();
if ($user && $user['role'] === 'pembeli') {
    if ((int) $pesanan['user_id'] !== (int) $user['id']) {
        redirect('/katalog.php');
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

// Ambil detail per penjual untuk WA dinamis (jumlah & harga sesuai pesanan)
$wa_groups = [];
$stmt = $pdo->prepare("SELECT dp.qty, dp.harga_satuan, dp.subtotal, p.nama_produk, u.id AS penjual_id, u.nama AS nama_penjual, u.no_hp AS hp_penjual FROM detail_pesanan dp JOIN produk p ON dp.produk_id = p.id JOIN users u ON p.user_id = u.id WHERE dp.pesanan_id = ? ORDER BY u.nama");
$stmt->execute([$pesanan_id]);
foreach ($stmt->fetchAll() as $row) {
    $sid = $row['penjual_id'];
    if (!isset($wa_groups[$sid])) {
        $wa_groups[$sid] = ['nama_penjual' => $row['nama_penjual'], 'hp_penjual' => $row['hp_penjual'], 'items' => [], 'total' => 0];
    }
    $wa_groups[$sid]['items'][] = ['nama_produk' => $row['nama_produk'], 'qty' => $row['qty'], 'harga' => $row['harga_satuan'], 'subtotal' => $row['subtotal']];
    $wa_groups[$sid]['total'] += $row['subtotal'];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else try {
        $filename = upload_foto($_FILES['bukti_bayar'] ?? null, __DIR__ . '/assets/uploads');
        if (!$filename) {
            $errors[] = 'Silakan pilih file bukti pembayaran.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO konfirmasi (pesanan_id, bukti_bayar, status_konfirmasi) VALUES (?, ?, 'pending')"
            );
            $stmt->execute([$pesanan_id, $filename]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.'];
            redirect('/katalog.php');
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

                <?php if (!empty($wa_groups)): ?>
                    <div class="wa-cta-card mb-3" style="flex-direction: column; align-items: stretch;">
                        <div class="d-flex align-items-center gap-3 w-100">
                            <div class="wa-cta-icon"><i class="bi bi-whatsapp"></i></div>
                            <div class="wa-cta-body">
                                <div class="wa-cta-title">Lanjut ke WhatsApp Penjual?</div>
                                <div class="wa-cta-sub">Pesan terisi otomatis sesuai pesanan #<?= $pesanan['id'] ?> — jumlah & harga</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php foreach ($wa_groups as $g):
                                $wa_text_u = wa_text_order_seller($g['nama_penjual'], $pesanan['id'], $g['items'], $g['total']);
                                $wa_url_u = wa_link($g['hp_penjual'], $wa_text_u);
                            ?>
                                <?php if ($wa_url_u): ?>
                                    <a href="<?= $wa_url_u ?>" target="_blank" rel="noopener" class="wa-seller-chip" title="Chat <?= sanitize($g['nama_penjual']) ?> via WhatsApp">
                                        <span class="wa-chip-icon"><i class="bi bi-whatsapp"></i></span>
                                        <?= sanitize($g['nama_penjual']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="small text-muted"><?= sanitize($g['nama_penjual']) ?> (HP tidak tersedia)</span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <?php foreach ($wa_groups as $g):
                                $preview = wa_text_order_seller($g['nama_penjual'], $pesanan['id'], $g['items'], $g['total']);
                            ?>
                            <div class="wa-preview mb-2">
                                <div class="wa-preview-header">
                                    <div class="wa-preview-avatar"><?= strtoupper(substr($g['nama_penjual'],0,1)) ?></div>
                                    <div>
                                        <div class="wa-preview-name"><?= sanitize($g['nama_penjual']) ?></div>
                                        <div class="wa-preview-sub">preview pesan top-tier</div>
                                    </div>
                                    <i class="bi bi-three-dots-vertical ms-auto opacity-75"></i>
                                </div>
                                <div class="wa-preview-body">
                                    <div class="wa-bubble"><?= wa_preview_html($preview) ?></div>
                                    <div class="wa-time">09:41 <i class="bi bi-check2-all" style="color:#53BDEB"></i></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="small text-muted mt-2"><i class="bi bi-lightning-charge me-1"></i>Preview top-tier — pesan mengikuti jumlah & harga.</div>
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
                    <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px">Nanti saja</a>
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