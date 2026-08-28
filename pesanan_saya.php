<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Admin/pelaku_usaha tidak boleh akses halaman pembeli
if (is_logged_in() && current_user()['role'] !== 'pembeli') {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Halaman ini hanya untuk pembeli.'];
    redirect('/katalog.php');
}

$pesanan_list = [];

if (is_logged_in() && current_user()['role'] === 'pembeli') {
    $user_id = current_user()['id'];
    $stmt = $pdo->prepare(
        "SELECT ps.*, kf.status_konfirmasi
         FROM pesanan ps
         LEFT JOIN konfirmasi kf ON kf.pesanan_id = ps.id
         WHERE ps.user_id = ?
         ORDER BY ps.tanggal DESC"
    );
    $stmt->execute([$user_id]);
    $pesanan_list = $stmt->fetchAll();
} else {
    // Tamu: tampilkan pesanan dari session guest_orders
    $guest_ids = $_SESSION['guest_orders'] ?? [];
    if (!empty($guest_ids)) {
        $placeholders = implode(',', array_fill(0, count($guest_ids), '?'));
        $stmt = $pdo->prepare(
            "SELECT ps.*, kf.status_konfirmasi
             FROM pesanan ps
             LEFT JOIN konfirmasi kf ON kf.pesanan_id = ps.id
             WHERE ps.id IN ($placeholders) AND ps.user_id IS NULL
             ORDER BY ps.tanggal DESC"
        );
        $stmt->execute($guest_ids);
        $pesanan_list = $stmt->fetchAll();
    }
}

// Ambil kontak penjual per pesanan untuk tombol WhatsApp
$seller_map = [];
if (!empty($pesanan_list)) {
    $ids = array_column($pesanan_list, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT dp.pesanan_id, u.nama AS nama_penjual, u.no_hp AS hp_penjual
         FROM detail_pesanan dp
         JOIN produk p ON dp.produk_id = p.id
         JOIN users u ON p.user_id = u.id
         WHERE dp.pesanan_id IN ($ph)"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $seller_map[$row['pesanan_id']][] = $row;
    }
}

$page_title = 'Pesanan Saya';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <h3 class="page-section-title mb-0" style="border-left-width: 5px;">Pesanan Saya</h3>
    <span class="badge bg-white border shadow-sm px-3 py-2" style="font-size:0.85rem;color:var(--kaligawe-primary);border-radius:999px"><i class="bi bi-bag-check me-1"></i><?= count($pesanan_list) ?> pesanan</span>
</div>

<?php if (!is_logged_in()): ?>
    <div class="alert alert-info d-flex align-items-center gap-2" style="border-radius:12px"><i class="bi bi-info-circle" style="font-size:1.2rem"></i><div>Anda melihat pesanan sebagai <strong>tamu</strong>. Simpan nomor pesanan Anda untuk melacak status — hubungi penjual via WhatsApp untuk konfirmasi cepat.</div></div>
<?php endif; ?>

<?php if (empty($pesanan_list)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
        <h5 class="fw-semibold mb-2" style="color: var(--kaligawe-primary);">Belum ada pesanan</h5>
        <p class="text-muted small mb-4" style="max-width: 420px; margin: 0 auto;">Mulai belanja produk segar dan UMKM Desa Kaligawe. Pesanan Anda akan tercatat di sini — tanpa login pun tetap terlacak via sesi tamu.</p>
        <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-success px-4" style="border-radius: 12px;"><i class="bi bi-search me-1"></i> Jelajahi Katalog</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle cart-table table-hover">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status Pesanan</th>
                    <th>Status Pembayaran</th>
                    <th>Hubungi Penjual</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pesanan_list as $ps):
                [$label, $badge] = label_status_pesanan($ps['status']);
                $status_bayar = $ps['status_konfirmasi'] ?? 'belum unggah';
                $bayar_badge = [
                    'diterima' => 'success',
                    'ditolak' => 'danger',
                    'pending' => 'warning',
                ][$ps['status_konfirmasi'] ?? ''] ?? 'secondary';
                $sellers = $seller_map[$ps['id']] ?? [];
            ?>
                <tr>
                    <td>#<?= $ps['id'] ?></td>
                    <td><?= date('d M Y H:i', strtotime($ps['tanggal'])) ?></td>
                    <td><?= rupiah($ps['total']) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= sanitize($label) ?></span></td>
                    <td><span class="badge bg-<?= $bayar_badge ?>"><?= sanitize($status_bayar) ?></span></td>
                    <td>
                        <?php if (!empty($sellers)): ?>
                            <div class="d-flex flex-column gap-1">
                            <?php foreach ($sellers as $s):
                                $wa_text = 'Halo Kak ' . $s['nama_penjual'] . ', saya pembeli pesanan #' . $ps['id'] . ' di Pasar Kaligawe (total ' . rupiah($ps['total']) . '). Mohon konfirmasi pesanan saya.';
                                $wa_url = wa_link($s['hp_penjual'], $wa_text);
                            ?>
                                <?php if ($wa_url): ?>
                                    <a href="<?= $wa_url ?>" target="_blank" rel="noopener" class="wa-seller-chip" title="Chat <?= sanitize($s['nama_penjual']) ?> via WhatsApp">
                                        <span class="wa-chip-icon"><i class="bi bi-whatsapp"></i></span>
                                        <?= sanitize($s['nama_penjual']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="small text-muted"><?= sanitize($s['nama_penjual']) ?> (HP tidak tersedia)</span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="small text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$ps['status_konfirmasi']): ?>
                            <a href="<?= BASE_URL ?>/upload_bukti.php?pesanan_id=<?= $ps['id'] ?>" class="btn btn-sm btn-outline-success">Unggah Bukti</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>