</main>
<footer class="site-footer <?= !empty($is_dashboard) ? 'site-footer-dashboard' : '' ?>">
    <div class="container py-5">
        <div class="row g-4 pb-2">
            <div class="col-lg-5 col-md-6">
                <a class="footer-brand d-inline-flex align-items-center gap-2 mb-3" href="<?= BASE_URL ?>/index.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DDA15E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M12 22a8 8 0 0 1-8-8c0-4.4 8-12 8-12s8 7.6 8 12a8 8 0 0 1-8 8Z"/><path d="M12 12c-4 0-6 2-6 5"/><path d="M12 12c4 0 6 2 6 5"/></svg>
                    Pasar Kaligawe
                </a>
                <p class="footer-desc mb-3">
                    Platform jual-beli produk UMKM, kelompok tani, dan BUMDes Desa Kaligawe,
                    Kec. Susukanlebak, Kab. Cirebon. Belanja langsung dari warga desa.
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="footer-title">Navigasi</h6>
                <ul class="footer-links list-unstyled">
                    <li><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#tentang">Tentang</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#katalog">Katalog</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-title">Kontak</h6>
                <ul class="footer-links footer-contact list-unstyled">
                    <li><i class="bi bi-geo-alt"></i> Desa Kaligawe, Kec. Susukanlebak, Kab. Cirebon</li>
                    <li><i class="bi bi-envelope"></i> admin@kaligawe.desa.id</li>
                    <li><i class="bi bi-telephone"></i> 0812-3450-0001</li>
                </ul>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-copyright text-center">
            &copy; <?= date('Y') ?> Pasar Kaligawe &mdash; Sistem Informasi E-Commerce Terintegrasi Desa Kaligawe
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const sidebar = document.querySelector('.left-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }
});
</script>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content alert-dialog">
            <button type="button" class="alert-dialog-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            <div class="alert-dialog-icon alert-dialog-icon--danger">
                <i class="bi bi-trash3"></i>
            </div>
            <h5 class="alert-dialog-title">Hapus produk?</h5>
            <p class="alert-dialog-text" id="confirmDeleteMessage">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="alert-dialog-actions">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="bi bi-trash3 me-1"></i> Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
