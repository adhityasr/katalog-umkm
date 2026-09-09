# Sistem Informasi E-Commerce Terintegrasi Desa Kaligawe

> **LIVE:** `https://pasar-kaligawe.desa.id` — *Sudah di-hosting, siap pakai*  
> Mirror lokal: `http://localhost/kaligawe/katalog.php`

Sistem katalog & transaksi jual-beli untuk produk UMKM dan hasil pertanian
Desa Kaligawe, Kec. Susukanlebak, Kab. Cirebon. Dibangun dengan **PHP native
(PDO) + MySQL + Bootstrap 5**, sesuai proposal KKM Universitas Muhammadiyah
Cirebon. **Telah di-deploy di hosting Desa (cPanel, PHP 8.2, MySQL 8) dan lolos uji end-to-end.**

> `config/database.php` auto-detect `HTTP_HOST` — `localhost` → `BASE_URL=/kaligawe`, hosting `pasar-kaligawe.desa.id` → `BASE_URL=''` + `SITE_URL` untuk link WA absolut.

## 1. Kebutuhan Server

- PHP 8.0 ke atas dengan ekstensi `pdo_mysql`
- MySQL 5.7 / MariaDB 10.x ke atas
- Web server: Apache (XAMPP/Laragon) atau PHP built-in server

## 2. Akses Live (Sudah Hosting)

Buka langsung: **https://pasar-kaligawe.desa.id/katalog.php**
- Admin: `admin@kaligawe.desa.id / 123`
- Pelaku: `rukmini@example.com / password123` (4 pelaku)
- Pembeli: **tanpa login** — langsung WA per penjual, cek via `Cek Pesanan`

## 2b. Cara Instalasi Lokal (XAMPP / Laragon — untuk develop)

1. Salin folder `kaligawe` ke dalam folder web server:
   - XAMPP: `C:\xampp\htdocs\kaligawe`
   - Laragon: `C:\laragon\www\kaligawe`
2. Jalankan Apache dan MySQL dari control panel XAMPP/Laragon.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), buat database baru
   atau langsung import: klik tab **Import**, pilih file `database/database.sql`,
   lalu klik **Go**. Database `kaligawe` beserta tabel dan data
   contoh akan otomatis terbuat.
4. Cek isi `config/database.php` — di hosting sudah set `DB_HOST` sesuai cPanel, lokal biarkan default (`host: localhost`, `user: root`, `password: ""`).
5. Lokal: `http://localhost/kaligawe/katalog.php` — Hosting: `https://pasar-kaligawe.desa.id/katalog.php`.

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

Saat mengimpor ulang `database/database.sql`, seluruh akun memakai
`password123` (file seed memakai hash yang seragam).

## 5. Struktur Folder

```
kaligawe/
├── database/
│   └── database.sql            # Skema database + data contoh (foto produk_*.jpg)
├── config/database.php        # Konfigurasi koneksi PDO
├── includes/                  # header, footer, auth, helper functions (wa_link, message_box, csrf)
├── admin/                     # Halaman khusus admin desa
│   ├── index.php               # Dashboard statistik
│   ├── kelola_kategori.php
│   ├── kelola_produk.php
│   ├── kelola_pesanan.php
│   └── konfirmasi_pesanan.php
├── pelaku_usaha/               # Halaman khusus UMKM/petani
│   ├── produk_saya.php         # Dashboard penjual (tanpa Aksi Cepat)
│   ├── tambah_produk.php
│   ├── edit_produk.php
│   ├── hapus_produk.php
│   ├── catat_penjualan.php     # Pencatatan penjualan offline (penjualan_manual)
│   └── pesanan_masuk.php
├── katalog.php                 # Landing + katalog publik (filter jenis/kategori/cari, foto produk)
├── produk_detail.php           # Detail + WA penjual top-tier (preview chat)
├── keranjang.php / update_keranjang.php / hapus_keranjang.php  # Keranjang tamu (session) & pembeli (DB) → WA langsung per penjual
├── tambah_keranjang.php
├── cek_pesanan.php             # Cek status pesanan tamu via No. Pesanan + No. HP (tanpa login)
├── upload_bukti.php            # Upload bukti pembayaran + WA penjual
├── pesanan_saya.php            # Riwayat pesanan (tamu via session, pembeli via user_id)
├── login.php / register.php / logout.php
└── assets/
    ├── css/style.css           # Premium Organic Biophilic, MessageBox & AlertDialog top-tier, WA preview
    ├── js/app.js               # Qty, confirm hapus (AlertDialog), MessageBox toast, CSRF
    └── uploads/                # Foto produk (produk_*.jpg) & bukti bayar
```

## 6. Alur Penggunaan Singkat

1. **Pembeli (tanpa login)**: buka `katalog.php` → `produk_detail.php` → `Tambah ke Keranjang` (ikon keranjang simpel) → `keranjang.php` → **Lanjut ke WhatsApp Penjual** (pesan otomatis `wa_text_checkout_seller()` per penjual: list `• {produk} — {qty} × Rp = Rp` + `Total`, jumlah & harga mengikuti state keranjang) + preview bubble top-tier → chat WA → jika penjual minta, buat pesanan & `upload_bukti.php` → lacak via `cek_pesanan.php` (No. Pesanan + No. HP) atau `pesanan_saya.php` (session tamu).
2. **Pembeli (login)**: sama, tapi `pesanan_saya.php` permanen & `keranjang` auto-merge saat login (`login.php:31`).
3. **Pelaku UMKM/Petani**: masuk → `Produk Saya` untuk tambah/edit produk → `Pesanan Masuk` untuk pantau pesanan berisi produknya.
4. **Admin Desa**: masuk → Dashboard → `Kelola Pesanan` untuk verifikasi bukti bayar (Terima/Tolak) dan ubah status → `Kelola Produk/Kategori` untuk moderasi. `checkout.php` **sudah dihapus** — alur sekarang WA langsung dari keranjang.

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
