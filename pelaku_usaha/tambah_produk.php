<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori")->fetchAll();
$errors = [];
$old = ['nama_produk' => '', 'kategori_id' => '', 'sumber_usaha' => 'perorangan', 'deskripsi' => '', 'harga' => '', 'satuan' => 'pcs', 'stok' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama_produk'] = trim($_POST['nama_produk'] ?? '');
    $old['kategori_id'] = (int) ($_POST['kategori_id'] ?? 0);
    $old['sumber_usaha'] = in_array($_POST['sumber_usaha'] ?? '', ['perorangan', 'kelompok_usaha', 'bumdes']) ? $_POST['sumber_usaha'] : 'perorangan';
    $old['deskripsi'] = trim($_POST['deskripsi'] ?? '');
    $old['harga'] = $_POST['harga'] ?? '';
    $old['satuan'] = trim($_POST['satuan'] ?? 'pcs');
    $old['stok'] = $_POST['stok'] ?? '';

    if ($old['nama_produk'] === '' || $old['kategori_id'] < 1 || $old['harga'] === '' || $old['stok'] === '') {
        $errors[] = 'Nama produk, kategori, harga, dan stok wajib diisi.';
    } elseif (!is_numeric($old['harga']) || $old['harga'] < 0) {
        $errors[] = 'Harga tidak valid.';
    } elseif (!ctype_digit((string) $old['stok'])) {
        $errors[] = 'Stok harus berupa angka bulat.';
    }

    if (empty($errors)) {
        try {
            $foto = upload_foto($_FILES['foto'] ?? null, __DIR__ . '/../assets/uploads');

            $stmt = $pdo->prepare(
                "INSERT INTO produk (user_id, kategori_id, sumber_usaha, nama_produk, deskripsi, harga, satuan, stok, foto)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $user_id, $old['kategori_id'], $old['sumber_usaha'], $old['nama_produk'], $old['deskripsi'],
                $old['harga'], $old['satuan'], $old['stok'], $foto,
            ]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil ditambahkan.'];
            redirect('/pelaku_usaha/produk_saya.php');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$page_title = 'Tambah Produk';
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
                    <div class="form-hero-icon"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <h4>Tambah Produk Baru</h4>
                        <p>Lengkapi informasi produk Anda agar tampil menarik di katalog Pasar Kaligawe.</p>
                    </div>
                </div>
                <div class="form-body">
                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-octagon me-1"></i><?= sanitize($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-tag"></i></span><h6>Informasi Dasar</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label">Nama produk</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-box2"></i></span>
                                    <input type="text" name="nama_produk" class="form-control" placeholder="cth: Gula Jawa Organik" value="<?= sanitize($old['nama_produk']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Sumber Usaha</label>
                                <select name="sumber_usaha" class="form-select" required>
                                    <option value="perorangan" <?= $old['sumber_usaha'] === 'perorangan' ? 'selected' : '' ?>>Perorangan</option>
                                    <option value="kelompok_usaha" <?= $old['sumber_usaha'] === 'kelompok_usaha' ? 'selected' : '' ?>>Kelompok Usaha</option>
                                    <option value="bumdes" <?= $old['sumber_usaha'] === 'bumdes' ? 'selected' : '' ?>>BUMDes</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Kategori</label>
                                <select name="kategori_id" class="form-select" required>
                                    <option value="">-- Pilih kategori --</option>
                                    <?php foreach ($kategori_list as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= $old['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($k['nama_kategori']) ?> (<?= $k['jenis'] === 'umkm' ? 'UMKM' : 'Pertanian' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Ceritakan singkat produk Anda, bahan, atau keunggulannya..."><?= sanitize($old['deskripsi']) ?></textarea>
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
                                    <input type="number" step="0.01" min="0" name="harga" class="form-control" placeholder="0" value="<?= sanitize($old['harga']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-rulers"></i></span>
                                    <input type="text" name="satuan" class="form-control" placeholder="kg / pcs / bungkus" value="<?= sanitize($old['satuan']) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stok</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-boxes"></i></span>
                                    <input type="number" name="stok" id="stokInput" min="0" class="form-control" placeholder="0" value="<?= sanitize($old['stok']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div id="stokWarning" style="display: none; margin-top: 0.85rem;">
                            <div class="message-box message-box--warning" role="alert">
                                <div class="message-box-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div class="message-box-content">
                                    <div class="message-box-title">Stok Menipis</div>
                                    <div class="message-box-text">Stok di bawah 5 — produk akan tampil badge <strong>“Stok menipis”</strong> dan <span style="color:#EF4444">stock-bar merah</span> di katalog. Segera isi ulang.</div>
                                </div>
                            </div>
                        </div>
                        <script>
                        (function(){
                            var input = document.getElementById('stokInput');
                            var warn = document.getElementById('stokWarning');
                            if(!input || !warn) return;
                            function check(){
                                var v = parseInt(input.value, 10);
                                if(!isNaN(v) && v >= 0 && v < 5){
                                    warn.style.display = 'block';
                                } else {
                                    warn.style.display = 'none';
                                }
                            }
                            input.addEventListener('input', check);
                            input.addEventListener('change', check);
                            check();
                        })();
                        </script>

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-image"></i></span><h6>Foto Produk</h6>
                        </div>
                        <div class="mb-3">
                            <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png">
                            <div class="form-text"><i class="bi bi-info-circle me-1"></i>Opsional. JPG/PNG maksimal 2MB.</div>
                        </div>

                        <button type="submit" class="btn btn-save w-100 btn-loading-on-submit" data-loading-text="Menyimpan...">
                            <i class="bi bi-check-lg me-1"></i>Simpan Produk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>