<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $jenis = in_array($_POST['jenis'] ?? '', ['umkm', 'pertanian']) ? $_POST['jenis'] : '';

    if ($nama_kategori === '' || $jenis === '') {
        $errors[] = 'Nama kategori dan jenis wajib diisi.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, jenis) VALUES (?, ?)");
        $stmt->execute([$nama_kategori, $jenis]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil ditambahkan.'];
        redirect('/admin/kelola_kategori.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil dihapus.'];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Kategori tidak bisa dihapus karena masih dipakai oleh produk.'];
    }
    redirect('/admin/kelola_kategori.php');
}

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori")->fetchAll();

$page_title = 'Kelola Kategori';
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn d-lg-none" type="button">
                ☰ Menu
            </button>
            <h3 class="page-section-title m-0" style="font-family: var(--font-display);">Kelola Kategori</h3>
        </div>
    </div>

    <div class="row g-4">
        <!-- Add Category Form -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2" style="font-family: var(--font-display); color: var(--kaligawe-primary);"><i class="bi bi-plus-circle me-1"></i>Tambah Kategori</h5>
                    
                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger py-2"><?= sanitize($err) ?></div>
                    <?php endforeach; ?>
                    
                    <form method="post">
                        <input type="hidden" name="action" value="tambah">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Sayur Segar" required style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold">Jenis / Klaster</label>
                            <select name="jenis" class="form-select" required style="border-radius: 8px;">
                                <option value="umkm">UMKM</option>
                                <option value="pertanian">Pertanian</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2.5 fw-semibold" style="border-radius: 8px; background-color: var(--kaligawe-primary); border-color: var(--kaligawe-primary);">Tambah Kategori</button>
                    </form>
                </div>
            </div>
            
            <div class="p-3 bg-light rounded-3 border-start border-3 border-warning">
                <span class="fw-bold small d-block mb-1 text-warning"><i class="bi bi-info-circle me-1"></i>Catatan Penghapusan</span>
                <p class="small text-muted mb-0" style="font-size: 0.78rem; line-height: 1.4;">
                    Kategori yang masih dihuni produk aktif tidak dapat dihapus. Anda harus mengganti kategori produk tersebut terlebih dahulu.
                </p>
            </div>
        </div>

        <!-- List Categories Table -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background-color: rgba(59, 91, 62, 0.04);">
                                <tr class="text-muted small">
                                    <th class="py-2.5 ps-4" style="font-weight: 600;">Nama Kategori</th>
                                    <th class="py-2.5" style="font-weight: 600;">Jenis Klaster</th>
                                    <th class="py-2.5 pe-4 text-end" style="font-weight: 600;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($kategori_list as $k): ?>
                                <tr>
                                    <td class="fw-bold text-dark py-3 ps-4"><?= sanitize($k['nama_kategori']) ?></td>
                                    <td>
                                        <span class="badge <?= $k['jenis'] === 'umkm' ? 'badge-umkm' : 'badge-pertanian' ?>">
                                            <?= $k['jenis'] === 'umkm' ? 'UMKM' : 'Pertanian' ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <form method="post" class="form-confirm-delete d-inline" data-message="Hapus kategori ini?">
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-3" style="border-radius: 6px;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
