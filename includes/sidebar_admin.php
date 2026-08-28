<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user = current_user();
?>
<aside class="left-sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-brand-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
        </span>
        <div>
            <div class="sidebar-brand-name">Pasar Kaligawe</div>
            <div class="sidebar-brand-sub">Panel Admin Desa</div>
        </div>
    </div>

    <div class="sidebar-group-label">Menu Admin</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/index.php"
           class="sidebar-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
           <i class="bi bi-speedometer2"></i>
           <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/kelola_produk.php"
           class="sidebar-link <?= $current_page === 'kelola_produk.php' ? 'active' : '' ?>">
           <i class="bi bi-box-seam"></i>
           <span>Kelola Produk</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/kelola_kategori.php"
           class="sidebar-link <?= $current_page === 'kelola_kategori.php' ? 'active' : '' ?>">
           <i class="bi bi-tags"></i>
           <span>Kelola Kategori</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/kelola_pesanan.php"
           class="sidebar-link <?= in_array($current_page, ['kelola_pesanan.php', 'konfirmasi_pesanan.php']) ? 'active' : '' ?>">
           <i class="bi bi-receipt-cutoff"></i>
           <span>Kelola Pesanan</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($user['nama'] ?? 'A', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= sanitize($user['nama'] ?? 'Admin') ?></div>
                <div class="sidebar-user-role">Admin Desa</div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="sidebar-logout" title="Keluar"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</aside>