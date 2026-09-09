<?php
require_once __DIR__ . '/auth.php';
$user = current_user();

$role_label = [
    'admin' => 'Admin Desa',
    'pelaku_usaha' => 'Pelaku Usaha',
    'pembeli' => 'Pembeli',
][$user['role'] ?? ''] ?? '';


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
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark kaligawe-navbar fixed-top shadow-sm" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/katalog.php">
            <span class="navbar-brand-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
            </span>
            <span class="navbar-brand-text">
                <span class="navbar-brand-name">Pasar Kaligawe</span>
                <span class="navbar-brand-tag">UMKM &amp; Pertanian Desa</span>
            </span>
            <?php if (!str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost') && !str_contains($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1')): ?>
                <span class="badge bg-success ms-2 d-none d-md-inline-flex align-items-center gap-1" style="font-size:0.68rem; padding:0.3em 0.6em; border:1px solid rgba(255,255,255,0.2)"><span style="width:6px;height:6px;background:#22C55E;border-radius:50%;display:inline-block;box-shadow:0 0 6px #22C55E"></span> LIVE</span>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link nav-link-pill" href="<?= BASE_URL ?>/katalog.php">Katalog Produk</a></li>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link nav-link-pill" href="<?= BASE_URL ?>/admin/index.php">Dashboard Admin</a></li>
                <?php elseif ($user && $user['role'] === 'pelaku_usaha'): ?>
                    <li class="nav-item"><a class="nav-link nav-link-pill" href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php">Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link user-chip dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></span>
                            <span class="user-chip-text">
                                <span class="user-chip-name"><?= sanitize($user['nama']) ?></span>
                                <span class="user-chip-role"><?= sanitize($role_label) ?></span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header"><?= sanitize($user['nama']) ?></h6></li>
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/edit_profil.php"><i class="bi bi-person-gear me-2"></i>Edit Profil</a></li>
                            <?php elseif ($user['role'] === 'pelaku_usaha'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php"><i class="bi bi-basket me-2"></i>Produk Saya</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/pelaku_usaha/edit_profil.php"><i class="bi bi-person-gear me-2"></i>Edit Profil</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-lg-2"><a class="btn btn-sm btn-nav-login" href="<?= BASE_URL ?>/login.php">Masuk</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-nav-register" href="<?= BASE_URL ?>/register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_dashboard = in_array($current_dir, ['admin', 'pelaku_usaha']);
$main_class = $is_dashboard ? "dashboard-main" : "container py-4";
?>
<main class="<?= $main_class ?>">
    <div class="message-box-toast" id="flashToastContainer">
        <?= flash_message_box() ?>
    </div>
