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
            <div class="sidebar-brand-sub">Panel Pelaku Usaha</div>
        </div>
    </div>

    <div class="sidebar-group-label">Menu Usaha</div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php"
           class="sidebar-link <?= $current_page === 'produk_saya.php' ? 'active' : '' ?>">
           <i class="bi bi-basket"></i>
           <span>Produk Saya</span>
        </a>
        <a href="<?= BASE_URL ?>/pelaku_usaha/tambah_produk.php"
           class="sidebar-link <?= $current_page === 'tambah_produk.php' ? 'active' : '' ?>">
           <i class="bi bi-plus-circle"></i>
           <span>Tambah Produk</span>
        </a>
        <a href="<?= BASE_URL ?>/pelaku_usaha/catat_penjualan.php"
           class="sidebar-link <?= $current_page === 'catat_penjualan.php' ? 'active' : '' ?>">
           <i class="bi bi-journal-text"></i>
           <span>Catat Penjualan Manual</span>
        </a>
        <a href="<?= BASE_URL ?>/pelaku_usaha/pesanan_masuk.php"
           class="sidebar-link <?= $current_page === 'pesanan_masuk.php' ? 'active' : '' ?>">
            <i class="bi bi-inbox"></i>
            <span>Pesanan Masuk</span>
        </a>
        <a href="<?= BASE_URL ?>/pelaku_usaha/edit_profil.php"
           class="sidebar-link <?= $current_page === 'edit_profil.php' ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i>
            <span>Edit Profil</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= sanitize($user['nama'] ?? 'Pelaku Usaha') ?></div>
                <div class="sidebar-user-role">Pelaku Usaha</div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="sidebar-logout" title="Keluar"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</aside>