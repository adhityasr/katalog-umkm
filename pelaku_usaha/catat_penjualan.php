<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];

// Get user's products
$stmt = $pdo->prepare("SELECT id, nama_produk, stok, satuan FROM produk WHERE user_id = ? AND status = 'aktif' ORDER BY nama_produk");
$stmt->execute([$user_id]);
$produk_list = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk_id = (int) ($_POST['produk_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($produk_id < 1) {
        $errors[] = 'Pilih produk terlebih dahulu.';
    }
    if ($qty < 1) {
        $errors[] = 'Jumlah tidak valid.';
    }
    if (empty($tanggal) || !strtotime($tanggal)) {
        $errors[] = 'Tanggal tidak valid.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Check product ownership and stock
            $stmt = $pdo->prepare("SELECT stok, nama_produk FROM produk WHERE id = ? AND user_id = ? FOR UPDATE");
            $stmt->execute([$produk_id, $user_id]);
            $p = $stmt->fetch();
            
            if (!$p) {
                throw new Exception("Produk tidak ditemukan atau tidak tersedia.");
            }
            if ($p['stok'] < $qty) {
                throw new Exception("Stok untuk " . sanitize($p['nama_produk']) . " tidak mencukupi. Stok saat ini: " . $p['stok']);
            }
            
            // Deduct stock
            $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $stmt->execute([$qty, $produk_id]);
            
            // Insert records
            $stmt = $pdo->prepare(
                "INSERT INTO penjualan_manual (produk_id, qty, tanggal, catatan) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$produk_id, $qty, $tanggal, $catatan]);
            
            $pdo->commit();
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Penjualan manual berhasil dicatat stok telah dikurangi.'];
            redirect('/pelaku_usaha/produk_saya.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$page_title = 'Catat Penjualan Manual';
require_once __DIR__ . '/../includes/header.php';
?>


<?php require_once __DIR__ . '/../includes/sidebar_pelaku_usaha.php'; ?>
<div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn d-lg-none" type="button">
            ☰ Menu
        </button>
        <a href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card form-card shadow-sm">
                <div class="form-hero">
                    <div class="form-hero-icon"><i class="bi bi-shop"></i></div>
                    <div>
                        <h4>Catat Penjualan Manual</h4>
                        <p>Catat penjualan yang terjadi di luar platform (offline). Stok produk akan otomatis berkurang.</p>
                    </div>
                </div>
                <div class="form-body">
                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-octagon me-1"></i><?= sanitize($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post">
                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-basket"></i></span><h6>Produk Terjual</h6>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Produk</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                <select name="produk_id" class="form-select" required>
                                    <option value="">-- Pilih produk yang terjual --</option>
                                    <?php foreach ($produk_list as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= sanitize($p['nama_produk']) ?> (Sisa Stok: <?= (int) $p['stok'] ?> <?= sanitize($p['satuan']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-text"><i class="bi bi-info-circle me-1"></i>Hanya produk berstatus <strong>Aktif</strong> yang tampil. Tersedia <?= count($produk_list) ?> produk.</div>
                        </div>

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-receipt"></i></span><h6>Detail Penjualan</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Terjual</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-plus-slash-minus"></i></span>
                                    <input type="number" name="qty" class="form-control" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Penjualan</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                                    <input type="text" name="catatan" class="form-control" placeholder="cth: Terjual di pasar minggu">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-save w-100 btn-loading-on-submit" data-loading-text="Menyimpan...">
                                <i class="bi bi-check-lg me-1"></i>Simpan Pencatatan
                            </button>
                            <a href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-x-lg me-1"></i>Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>