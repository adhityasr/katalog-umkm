# Sistem Informasi E-Commerce Terintegrasi Desa Kaligawe

Sistem katalog & transaksi jual-beli untuk produk UMKM dan hasil pertanian
Desa Kaligawe, Kec. Susukanlebak, Kab. Cirebon. Dibangun dengan **PHP native
(PDO) + MySQL + Bootstrap 5**, sesuai proposal KKM Universitas Muhammadiyah
Cirebon.

Sudah diuji end-to-end (login semua peran, tambah ke keranjang, checkout,
pengurangan stok, upload bukti bayar, konfirmasi pembayaran admin, kelola
produk/kategori) dan berjalan tanpa error.

## 1. Kebutuhan Server

- PHP 8.0 ke atas dengan ekstensi `pdo_mysql`
- MySQL 5.7 / MariaDB 10.x ke atas
- Web server: Apache (XAMPP/Laragon) atau PHP built-in server

## 2. Cara Instalasi (XAMPP / Laragon — paling mudah)

1. Salin folder `kaligawe` ke dalam folder web server:
   - XAMPP: `C:\xampp\htdocs\kaligawe`
   - Laragon: `C:\laragon\www\kaligawe`
2. Jalankan Apache dan MySQL dari control panel XAMPP/Laragon.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), buat database baru
   atau langsung import: klik tab **Import**, pilih file `database/database.sql`,
   lalu klik **Go**. Database `kaligawe` beserta tabel dan data
   contoh akan otomatis terbuat.
4. Cek isi `config/database.php` — biarkan default jika memakai XAMPP/Laragon
   standar (`host: localhost`, `user: root`, `password: ""`).
5. Buka browser ke `http://localhost/kaligawe/katalog.php`.

## 3. Cara Instalasi (PHP Built-in Server — untuk uji cepat tanpa Apache)

```bash
# 1. Import database (pastikan MySQL sudah berjalan)
mysql -u root < database/database.sql

# 2. Jalankan server dari dalam folder proyek
cd kaligawe
php -S localhost:8080

# 3. Buka di browser
http://localhost:8080/katalog.php
```

## 4. Akun Contoh (Seed Data)

Password akun mengikuti kondisi DB berjalan (lihat catatan di `database/database.sql`):

| Peran           | Email                        | Password      | Keterangan                       |
|------------------|-------------------------------|---------------|----------------------------------|
| Admin desa       | admin@kaligawe.desa.id         | `123`         | Kelola produk, kategori, pesanan  |
| Pelaku UMKM      | rukmini@example.com            | `password123` | Penjual lumpia & rengginang       |
| Pelaku tani      | karto@example.com              | `password123` | Penjual beras & jagung             |
| Pelaku ternak    | budi@example.com               | `password123` | Penjual daging & susu kambing     |
| Pelaku kerajinan | dewi@example.com               | `password123` | Penjual anyaman bambu & pandan    |
| Pembeli          | sari@example.com               | `123`         | Akun uji coba belanja              |
| Pembeli          | agus@example.com               | `password123` | Akun uji coba belanja              |
| Pembeli          | fitri@example.com              | `password123` | Akun uji coba belanja              |

Saat mengimpor ulang `database/database.sql`, seluruh akun memakai
`password123` (file seed memakai hash yang seragam).

## 5. Struktur Folder

```
kaligawe/
├── database/
│   └── database.sql            # Skema database + data contoh
├── config/database.php        # Konfigurasi koneksi PDO
├── includes/                  # header, footer, auth, helper functions
├── admin/                     # Halaman khusus admin desa
│   ├── index.php               # Dashboard statistik
│   ├── kelola_kategori.php
│   ├── kelola_produk.php
│   ├── kelola_pesanan.php
│   └── konfirmasi_pesanan.php
├── pelaku_usaha/               # Halaman khusus UMKM/petani
│   ├── produk_saya.php
│   ├── tambah_produk.php
│   ├── edit_produk.php
│   ├── hapus_produk.php
│   ├── catat_penjualan.php     # Pencatatan penjualan offline (penjualan_manual)
│   └── pesanan_masuk.php
├── katalog.php                 # Landing + katalog publik (filter jenis/kategori/cari)
├── produk_detail.php
├── keranjang.php / update_keranjang.php / hapus_keranjang.php
├── tambah_keranjang.php
├── checkout.php                # Buat pesanan (transaksional)
├── upload_bukti.php            # Upload bukti pembayaran
├── pesanan_saya.php            # Riwayat pesanan pembeli
├── login.php / register.php / logout.php
└── assets/
    ├── css/style.css
    ├── js/app.js               # Interaksi: qty, konfirmasi hapus, toast
    └── uploads/                # Foto produk & bukti bayar tersimpan di sini
```

## 6. Alur Penggunaan Singkat

1. **Pembeli**: daftar/masuk → lihat katalog → tambah ke keranjang →
   checkout (isi data penerima) → unggah bukti transfer → tunggu
   konfirmasi admin di menu "Pesanan Saya".
2. **Pelaku UMKM/Petani**: masuk → menu "Produk Saya" untuk tambah/edit
   produk → menu "Pesanan Masuk" untuk memantau pesanan yang berisi
   produknya.
3. **Admin Desa**: masuk → Dashboard untuk ringkasan → "Kelola Pesanan"
   untuk verifikasi bukti bayar (Terima/Tolak) dan mengubah status
   pengiriman → "Kelola Produk/Kategori" untuk moderasi.

## 7. Catatan Pengembangan Lanjutan

- Folder `assets/uploads/` perlu diberi izin tulis (`chmod 755` atau lebih)
  di server Linux agar upload foto produk dan bukti bayar berhasil.
- Untuk produksi, ganti `DB_PASS` di `config/database.php` dengan password
  MySQL yang sesuai, jangan gunakan password kosong.
- Sistem ini dirancang mengikuti ERD pada proposal KKM: tabel `users`,
  `kategori`, `produk`, `keranjang`, `pesanan`, `detail_pesanan`,
  `konfirmasi`, dan `penjualan_manual` (pencatatan penjualan offline).

## Catatan Survey Lapangan (Agustus 2026)

- Pengolahan pupuk dari limbah pertanian belum ada, masih dilempar
  langsung ke lahan padi
- Petani jagung mayoritas menanam jagung pipil, dengan komoditas
  sampingan semangka
- Pola panen: padi 2x/tahun, jagung 3x/tahun
- Peternakan (termasuk kambing) terpusat di Dusun 3, dikelola kelompok
  peternakan mandiri dan BUMDes desa
- UMKM lumpia dan rengginang belum memiliki pencatatan penjualan
  otomatis
