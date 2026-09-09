<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('/katalog.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email dan kata sandi wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($password, $u['password'])) {
            $errors[] = 'Email atau kata sandi salah.';
        } else {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['nama'] = $u['nama'];
            $_SESSION['role'] = $u['role'];
            $_SESSION['email'] = $u['email'];

            // Gabungkan keranjang tamu ke keranjang pembeli jika ada
            if ($u['role'] === 'pembeli' && !empty($_SESSION['keranjang_guest'])) {
                foreach ($_SESSION['keranjang_guest'] as $pid => $qty) {
                    $pid = (int) $pid;
                    $qty = max(1, (int) $qty);
                    $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = ? AND status = 'aktif'");
                    $stmt->execute([$pid]);
                    $p = $stmt->fetch();
                    if (!$p) continue;
                    $qty = min($qty, (int) $p['stok']);
                    $stmt = $pdo->prepare("SELECT id, qty FROM keranjang WHERE user_id = ? AND produk_id = ?");
                    $stmt->execute([$u['id'], $pid]);
                    $existing = $stmt->fetch();
                    if ($existing) {
                        $newQty = min((int) $p['stok'], (int) $existing['qty'] + $qty);
                        $stmt = $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
                        $stmt->execute([$newQty, $existing['id']]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, qty) VALUES (?, ?, ?)");
                        $stmt->execute([$u['id'], $pid, $qty]);
                    }
                }
                unset($_SESSION['keranjang_guest']);
            }

            if ($u['role'] === 'admin') {
                redirect('/admin/index.php');
            } elseif ($u['role'] === 'pelaku_usaha') {
                redirect('/pelaku_usaha/produk_saya.php');
            } else {
                redirect('/katalog.php');
            }
        }
    }
}

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
    <div class="auth-card-wrapper">
        <div class="card shadow-sm auth-card overflow-hidden">
            <div class="row g-0">
                <!-- Visual Sidebar (Desktop only) -->
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative auth-sidebar" style="background: linear-gradient(135deg, var(--kaligawe-primary) 0%, #253a27 100%);">
                    <div class="sidebar-pattern"></div>
                    
                    <div class="brand-wrapper">
                        <a class="text-decoration-none d-flex align-items-center gap-2 fw-bold text-white fs-4" href="<?= BASE_URL ?>/katalog.php" style="font-family: var(--font-display);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
                            Pasar Kaligawe
                        </a>
                    </div>
                    
                    <div class="my-auto py-4">
                        <span class="badge mb-3 px-3 py-2 rounded-pill fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.05em; background-color: var(--kaligawe-accent) !important; color: #3d2b00 !important;">PORTAL MASUK</span>
                        <h2 class="display-6 fw-bold mb-3" style="font-family: var(--font-display); line-height: 1.25;">Selamat Datang di Pasar Kaligawe</h2>
                    </div>
                    
                    <div class="footer-wrapper text-white-50 small mt-auto">
                        &copy; <?= date('Y') ?> Desa Kaligawe.
                    </div>
                </div>
                
                <!-- Form Content -->
                <div class="col-lg-7 p-4 p-sm-5 d-flex align-items-center">
                    <div class="w-100">
                        <!-- Brand logo visible only on mobile -->
                        <div class="d-lg-none text-center mb-4">
                            <a class="text-decoration-none d-inline-flex align-items-center gap-2 fw-bold text-success fs-4" href="<?= BASE_URL ?>/katalog.php" style="font-family: var(--font-display);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
                                Pasar Kaligawe
                            </a>
                        </div>
                        
                        <h3 class="fw-bold mb-1" style="font-family: var(--font-display); color: var(--kaligawe-primary);">Selamat Datang Kembali</h3>
                        <p class="text-muted mb-4 small">Silakan masuk menggunakan kredensial email Anda yang terdaftar.</p>
         
                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger py-2"><?= sanitize($err) ?></div>
                        <?php endforeach; ?>
         
                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required placeholder="nama@email.com">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kata sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="login_password" class="form-control" required placeholder="Masukkan kata sandi">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="login_password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 btn-submit btn-loading-on-submit mt-2" data-loading-text="Masuk...">Masuk</button>
                        </form>
         
                        <p class="text-center mt-4 mb-0 small">
                            Belum punya akun? <a href="<?= BASE_URL ?>/register.php" class="text-success fw-semibold">Daftar di sini</a>
                        </p>
                    </div>
                </div>
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
