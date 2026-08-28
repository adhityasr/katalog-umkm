<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$produk = $stmt->fetch();

if (!$produk) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Produk tidak ditemukan.'];
    redirect('/pelaku_usaha/produk_saya.php');
}

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $kategori_id = (int) ($_POST['kategori_id'] ?? 0);
    $sumber_usaha = in_array($_POST['sumber_usaha'] ?? '', ['perorangan', 'kelompok_usaha', 'bumdes']) ? $_POST['sumber_usaha'] : 'perorangan';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = $_POST['harga'] ?? '';
    $satuan = trim($_POST['satuan'] ?? 'pcs');
    $stok = $_POST['stok'] ?? '';
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';

    if ($nama_produk === '' || $kategori_id < 1 || $harga === '' || $stok === '') {
        $errors[] = 'Nama produk, kategori, harga, dan stok wajib diisi.';
    } elseif (!is_numeric($harga) || $harga < 0) {
        $errors[] = 'Harga tidak valid.';
    } elseif (!ctype_digit((string) $stok)) {
        $errors[] = 'Stok harus berupa angka bulat.';
    }

    if (empty($errors)) {
        try {
            $foto = upload_foto($_FILES['foto'] ?? null, __DIR__ . '/../assets/uploads');

            if ($foto) {
                $stmt = $pdo->prepare(
                    "UPDATE produk SET kategori_id=?, sumber_usaha=?, nama_produk=?, deskripsi=?, harga=?, satuan=?, stok=?, status=?, foto=?
                     WHERE id=? AND user_id=?"
                );
                $stmt->execute([$kategori_id, $sumber_usaha, $nama_produk, $deskripsi, $harga, $satuan, $stok, $status, $foto, $id, $user_id]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE produk SET kategori_id=?, sumber_usaha=?, nama_produk=?, deskripsi=?, harga=?, satuan=?, stok=?, status=?
                     WHERE id=? AND user_id=?"
                );
                $stmt->execute([$kategori_id, $sumber_usaha, $nama_produk, $deskripsi, $harga, $satuan, $stok, $status, $id, $user_id]);
            }

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil diperbarui.'];
            redirect('/pelaku_usaha/produk_saya.php');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    // supaya form menampilkan input yang baru diketik jika gagal
    $produk = array_merge($produk, compact('nama_produk', 'kategori_id', 'sumber_usaha', 'deskripsi', 'harga', 'satuan', 'stok', 'status'));
}

$page_title = 'Edit Produk';
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
                    <div class="form-hero-icon"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <h4>Edit Produk</h4>
                        <p>Perbarui informasi produk &ldquo;<?= sanitize($produk['nama_produk']) ?>&rdquo;.</p>
                    </div>
                </div>
                <div class="form-body">
                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-octagon me-1"></i><?= sanitize($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $id ?>">

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-tag"></i></span><h6>Informasi Dasar</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label">Nama produk</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-box2"></i></span>
                                    <input type="text" name="nama_produk" class="form-control" value="<?= sanitize($produk['nama_produk']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Sumber Usaha</label>
                                <select name="sumber_usaha" class="form-select" required>
                                    <option value="perorangan" <?= $produk['sumber_usaha'] === 'perorangan' ? 'selected' : '' ?>>Perorangan</option>
                                    <option value="kelompok_usaha" <?= $produk['sumber_usaha'] === 'kelompok_usaha' ? 'selected' : '' ?>>Kelompok Usaha</option>
                                    <option value="bumdes" <?= $produk['sumber_usaha'] === 'bumdes' ? 'selected' : '' ?>>BUMDes</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Kategori</label>
                                <select name="kategori_id" class="form-select" required>
                                    <?php foreach ($kategori_list as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= $produk['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($k['nama_kategori']) ?> (<?= $k['jenis'] === 'umkm' ? 'UMKM' : 'Pertanian' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"><?= sanitize($produk['deskripsi']) ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-cash-coin"></i></span><h6>Harga &amp; Stok</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Harga (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="0.01" min="0" name="harga" class="form-control" value="<?= sanitize($produk['harga']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-rulers"></i></span>
                                    <input type="text" name="satuan" class="form-control" value="<?= sanitize($produk['satuan']) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stok</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                    <input type="number" name="stok" min="0" class="form-control" value="<?= sanitize($produk['stok']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-toggle-on"></i></span><h6>Status Produk</h6>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <label class="status-option">
                                <input type="radio" name="status" value="aktif" <?= $produk['status'] === 'aktif' ? 'checked' : '' ?>>
                                <span class="status-card">
                                    <i class="bi bi-eye"></i>
                                    <span><strong>Aktif</strong><small>Tampil di katalog</small></span>
                                </span>
                            </label>
                            <label class="status-option">
                                <input type="radio" name="status" value="nonaktif" <?= $produk['status'] === 'nonaktif' ? 'checked' : '' ?>>
                                <span class="status-card">
                                    <i class="bi bi-eye-slash"></i>
                                    <span><strong>Nonaktif</strong><small>Disembunyikan dari katalog</small></span>
                                </span>
                            </label>
                        </div>

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-image"></i></span><h6>Foto Produk</h6>
                        </div>
                        <?php if ($produk['foto']): ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= BASE_URL ?>/assets/uploads/<?= sanitize($produk['foto']) ?>" class="rounded" style="width: 72px; height: 72px; object-fit: cover; border: 1px solid rgba(59,91,62,0.15);">
                                <div class="small text-muted"><i class="bi bi-camera me-1"></i>Foto saat ini. Unggah foto baru untuk menggantinya.</div>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png">
                            <div class="form-text"><i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak mengganti foto. JPG/PNG maksimal 2MB.</div>
                        </div>

                        <button type="submit" class="btn btn-save w-100 btn-loading-on-submit" data-loading-text="Menyimpan...">
                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>