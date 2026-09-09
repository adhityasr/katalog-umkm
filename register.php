<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('/index.php');
}

$errors = [];
$old = ['nama' => '', 'email' => '', 'no_hp' => '', 'alamat' => '', 'role' => 'pelaku_usaha'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama'] = trim($_POST['nama'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['no_hp'] = trim($_POST['no_hp'] ?? '');
    $old['alamat'] = trim($_POST['alamat'] ?? '');
    $old['role'] = 'pelaku_usaha'; // hanya pelaku UMKM, pembeli tidak perlu daftar
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($old['nama'] === '' || $old['email'] === '' || $password === '') {
        $errors[] = 'Nama, email, dan kata sandi wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi kata sandi tidak sama.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain atau masuk.';
        }
    }

        if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (nama, email, no_hp, alamat, password, role) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$old['nama'], $old['email'], $old['no_hp'], $old['alamat'], $hash, $old['role']]);

        // Auto-login setelah daftar — langsung ke dashboard sesuai role
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['nama'] = $old['nama'];
        $_SESSION['role'] = $old['role'];
        $_SESSION['email'] = $old['email'];

        // Keranjang tamu tidak perlu digabung untuk pelaku (hanya pembeli yang belanja)
        if (false && $old['role'] === 'pembeli' && !empty($_SESSION['keranjang_guest'])) {
            foreach ($_SESSION['keranjang_guest'] as $pid => $qty) {
                $pid = (int) $pid; $qty = max(1, (int) $qty);
                $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = ? AND status = 'aktif'");
                $stmt->execute([$pid]); $p = $stmt->fetch();
                if (!$p) continue;
                $qty = min($qty, (int) $p['stok']);
                $stmt = $pdo->prepare("SELECT id, qty FROM keranjang WHERE user_id = ? AND produk_id = ?");
                $stmt->execute([$user_id, $pid]); $existing = $stmt->fetch();
                if ($existing) {
                    $newQty = min((int) $p['stok'], (int) $existing['qty'] + $qty);
                    $stmt = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
                    $stmt->execute([$newQty, $existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, qty) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $pid, $qty]);
                }
            }
            unset($_SESSION['keranjang_guest']);
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pendaftaran berhasil. Selamat datang, ' . $old['nama'] . '!'];
        redirect('/pelaku_usaha/produk_saya.php');
    }
}

$page_title = 'Daftar';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' - ' : '' ?>E-Commerce Desa Kaligawe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600&family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
    body {
        background: radial-gradient(circle at 10% 20%, #FAEDDB 0%, #F5E3C8 100%) !important;
        padding-top: 0 !important;
    }
    </style>
</head>
<body>

<div class="bg-decor">
    <div class="decor-blob-1"></div>
    <div class="decor-blob-2"></div>
    <div class="decor-blob-3"></div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
<?php if ($flash = $_SESSION['flash'] ?? null): unset($_SESSION['flash']); ?>
    <div class="toast align-items-center border-0 text-bg-<?= sanitize($flash['type']) ?>"
         role="alert" data-bs-delay="5000" id="flashToast">
        <div class="d-flex">
            <div class="toast-body"><?= sanitize($flash['message']) ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>
    </div>
<?php endif; ?>
</div>

<div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="auth-card-wrapper" style="max-width: 1040px;">
        <div class="card shadow-sm auth-card overflow-hidden">
            <div class="row g-0">
                <!-- Visual Sidebar (Desktop only) -->
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative auth-sidebar" style="background: linear-gradient(135deg, var(--kaligawe-primary) 0%, #253a27 100%);">
                    <div class="sidebar-pattern"></div>
                    
                    <div class="brand-wrapper">
                        <a class="text-decoration-none d-flex align-items-center gap-2 fw-bold text-white fs-4" href="<?= BASE_URL ?>/index.php" style="font-family: var(--font-display);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
                            Pasar Kaligawe
                        </a>
                    </div>
                    
                    <div class="my-auto py-4">
                        <span class="badge mb-3 px-3 py-2 rounded-pill fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.05em; background-color: var(--kaligawe-accent) !important; color: #3d2b00 !important;">PENDAFTARAN</span>
                        <h2 class="display-6 fw-bold mb-3" style="font-family: var(--font-display); line-height: 1.25;">Bergabunglah dengan Ekosistem Kami</h2>
                        <p class="text-white-50 small" style="font-family: var(--font-body); font-weight: 300;">Pendaftaran khusus <strong>Pelaku UMKM / Petani</strong> untuk kelola toko di Pasar Kaligawe.</p>
                    </div>
                    
                    <div class="footer-wrapper text-white-50 small mt-auto">
                        &copy; <?= date('Y') ?> Desa Kaligawe.
                    </div>
                </div>
                
                <!-- Form Content -->
                <div class="col-lg-7 p-4 p-sm-5">
                    <!-- Brand logo visible only on mobile -->
                    <div class="d-lg-none text-center mb-4">
                        <a class="text-decoration-none d-inline-flex align-items-center gap-2 fw-bold text-success fs-4" href="<?= BASE_URL ?>/index.php" style="font-family: var(--font-display);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
                            Pasar Kaligawe
                        </a>
                    </div>
                    
                    <h3 class="fw-bold mb-1" style="font-family: var(--font-display); color: var(--kaligawe-primary);">Daftar Pelaku Usaha</h3>

                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger py-2"><?= sanitize($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post" novalidate>
                        <input type="hidden" name="role" value="pelaku_usaha">

                        <div class="mb-3">
                            <label class="form-label">Nama lengkap</label>
                            <input type="text" name="nama" class="form-control" value="<?= sanitize($old['nama']) ?>" required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required placeholder="user@contoh.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= sanitize($old['no_hp']) ?>" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Tulis alamat lengkap Anda"><?= sanitize($old['alamat']) ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kata sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="reg_password" class="form-control" required placeholder="Min. 6 karakter">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="reg_password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Konfirmasi kata sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="konfirmasi_password" id="reg_konfirmasi" class="form-control" required placeholder="Ulangi kata sandi">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="reg_konfirmasi" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="setuju" id="setuju" value="1" required>
                            <label class="form-check-label small" for="setuju">
                                Saya menyetujui <a href="#" class="text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#syaratModal">Syarat & Ketentuan</a> Pasar Kaligawe
                            </label>
                        </div>
                        <button type="submit" class="btn btn-success w-100 btn-submit btn-loading-on-submit mt-2" data-loading-text="Mendaftar...">Daftar</button>
                    </form>

                    <p class="text-center mt-4 mb-0 small">
                        Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" class="text-success fw-semibold">Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="syaratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(59,91,62,0.08);">
                <h5 class="modal-title" style="font-family: var(--font-display); color: var(--kaligawe-primary);"><i class="bi bi-file-text me-2"></i>Syarat & Ketentuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body small" style="line-height: 1.6; color: #3A4A3B;">
                <p>Dengan mendaftar sebagai Pelaku UMKM di Pasar Kaligawe, Anda menyetujui:</p>
                <ul class="mb-3">
                    <li>Data usaha (nama, WA, alamat) ditampilkan di katalog untuk pembeli.</li>
                    <li>Produk yang diunggah harus asli, halal, dan sesuai deskripsi.</li>
                    <li>Admin berhak menonaktifkan produk yang melanggar ketentuan desa.</li>
                    <li>Menjaga komunikasi via WhatsApp yang tertera agar pembeli mudah hubungi.</li>
                </ul>
                <p class="mb-0">Pelanggaran berulang dapat menyebabkan akun dinonaktifkan oleh admin desa.</p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(59,91,62,0.08);">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.toggle-password').forEach(function(btn){
        btn.addEventListener('click', function(){
            var input = document.getElementById(btn.dataset.target);
            if(!input) return;
            var isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            btn.innerHTML = isPass ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
