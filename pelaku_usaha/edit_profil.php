<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('pelaku_usaha');
$user_id = current_user()['id'];

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT id, nama, email, no_hp, alamat, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Data pengguna tidak ditemukan.'];
    redirect('/login.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        // Validasi
        if ($nama === '' || $email === '' || $no_hp === '' || $alamat === '') {
            $errors[] = 'Nama usaha, email, no. WhatsApp, dan alamat wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        } elseif (!preg_match('/^[0-9+\-\s]{10,15}$/', $no_hp)) {
            $errors[] = 'No. WhatsApp harus 10-15 digit angka (boleh diawali 0 atau +62).';
        } else {
            // Cek email unik (kecuali milik sendiri)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah digunakan akun lain.';
            }
        }

        // Validasi password jika diisi (opsional)
        $hash = null;
        if ($password !== '' || $konfirmasi !== '') {
            if (strlen($password) < 6) {
                $errors[] = 'Kata sandi baru minimal 6 karakter.';
            } elseif ($password !== $konfirmasi) {
                $errors[] = 'Konfirmasi kata sandi tidak sama.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        if (empty($errors)) {
            try {
                if ($hash) {
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, password = ? WHERE id = ?");
                    $stmt->execute([$nama, $email, $no_hp, $alamat, $hash, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ? WHERE id = ?");
                    $stmt->execute([$nama, $email, $no_hp, $alamat, $user_id]);
                }

                // Update session
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profil berhasil diperbarui.'];
                redirect('/pelaku_usaha/edit_profil.php');
            } catch (Exception $e) {
                $errors[] = 'Gagal memperbarui profil: ' . $e->getMessage();
            }
        } else {
            // Tetap tampilkan input yang diketik jika validasi gagal
            $user['nama'] = $nama;
            $user['email'] = $email;
            $user['no_hp'] = $no_hp;
            $user['alamat'] = $alamat;
        }
    }
}

$page_title = 'Edit Profil';
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/sidebar_pelaku_usaha.php'; ?>
<div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-secondary sidebar-toggle-btn d-lg-none" type="button">☰ Menu</button>
        <a href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card form-card shadow-sm">
                <div class="form-hero">
                    <div class="form-hero-icon"><i class="bi bi-person-gear"></i></div>
                    <div>
                        <h4>Edit Profil Usaha</h4>
                        <p>Perbarui nama usaha, nomor WhatsApp, alamat dusun, dan data profil lainnya.</p>
                    </div>
                </div>
                <div class="form-body">
                    <?php foreach ($errors as $err): ?>
                        <?= message_box('error', 'Gagal', $err) ?>
                    <?php endforeach; ?>

                    <?php if ($success): ?>
                        <?= message_box('success', 'Berhasil', 'Profil diperbarui.') ?>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-shop"></i></span><h6>Informasi Usaha</h6>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama usaha / Nama lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="nama" class="form-control" value="<?= sanitize($user['nama']) ?>" required placeholder="Contoh: Ibu Rukmini (Lumpia)">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-whatsapp" style="color:#25D366"></i></span>
                                    <input type="text" name="no_hp" class="form-control" value="<?= sanitize($user['no_hp'] ?? '') ?>" required placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="form-text">Ditampilkan di produk & dipakai link WA pembeli.</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Alamat / Dusun <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <textarea name="alamat" class="form-control" rows="2" required placeholder="Contoh: Dusun 1 Kaligawe, RT 02/RW 01"><?= sanitize($user['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <span class="fs-icon"><i class="bi bi-lock"></i></span><h6>Ubah Kata Sandi (opsional)</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kata sandi baru</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" id="edit_password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="edit_password" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi kata sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" name="konfirmasi_password" id="edit_konfirmasi" class="form-control" placeholder="Ulangi kata sandi baru">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="edit_konfirmasi" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button>
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
                                    btn.setAttribute('aria-label', isPass ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                                });
                            });
                        });
                        </script>

                        <div class="alert alert-light border d-flex gap-2 mt-4 mb-3" style="border-radius:10px">
                            <i class="bi bi-info-circle text-primary" style="font-size:1.2rem"></i>
                            <div class="small text-muted">No. WhatsApp akan dipakai tombol <span class="badge bg-success" style="font-size:0.7rem"><i class="bi bi-whatsapp me-1"></i>Chat Penjual</span> di katalog & produk detail. Pastikan nomor aktif & diawali 08/62.</div>
                        </div>

                        <button type="submit" class="btn btn-save w-100 btn-loading-on-submit" data-loading-text="Menyimpan..."><i class="bi bi-check-lg me-1"></i> Simpan Perubahan</button>
                        <a href="<?= BASE_URL ?>/pelaku_usaha/produk_saya.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:12px">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>