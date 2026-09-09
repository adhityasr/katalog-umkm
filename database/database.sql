-- =========================================================
-- Database: kaligawe
-- Sistem Informasi E-Commerce Terintegrasi UMKM & Pertanian
-- Desa Kaligawe, Kec. Susukanlebak, Kab. Cirebon
--
-- Password seed di bawah: "password123" untuk semua akun.
-- Catatan: pada DB berjalan saat ini, akun admin & sari
-- telah diubah manual menjadi password "123" (di luar file seed).
-- =========================================================

CREATE DATABASE IF NOT EXISTS kaligawe
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kaligawe;

-- ---------------------------------------------------------
-- Tabel USERS
-- ---------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  no_hp VARCHAR(20) DEFAULT NULL,
  alamat VARCHAR(255) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','pelaku_usaha','pembeli') NOT NULL DEFAULT 'pembeli',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel KATEGORI
-- ---------------------------------------------------------
CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL,
  jenis ENUM('umkm','pertanian') NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel PRODUK
-- ---------------------------------------------------------
CREATE TABLE produk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  kategori_id INT NOT NULL,
  sumber_usaha ENUM('perorangan','kelompok_usaha','bumdes') NOT NULL DEFAULT 'perorangan',
  nama_produk VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  harga DECIMAL(12,2) NOT NULL,
  satuan VARCHAR(30) NOT NULL DEFAULT 'pcs',
  stok INT NOT NULL DEFAULT 0,
  foto VARCHAR(255) DEFAULT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel KERANJANG
-- ---------------------------------------------------------
CREATE TABLE keranjang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  produk_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel PESANAN
-- ---------------------------------------------------------
CREATE TABLE pesanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  nama_penerima VARCHAR(100) NOT NULL,
  alamat_pengiriman VARCHAR(255) NOT NULL,
  no_hp_penerima VARCHAR(20) NOT NULL,
  status ENUM('menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel DETAIL_PESANAN
-- ---------------------------------------------------------
CREATE TABLE detail_pesanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL,
  produk_id INT NOT NULL,
  qty INT NOT NULL,
  harga_satuan DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  FOREIGN KEY (produk_id) REFERENCES produk(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel KONFIRMASI (bukti pembayaran)
-- ---------------------------------------------------------
CREATE TABLE konfirmasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL,
  bukti_bayar VARCHAR(255) DEFAULT NULL,
  status_konfirmasi ENUM('pending','diterima','ditolak') NOT NULL DEFAULT 'pending',
  tanggal_konfirmasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel PENJUALAN_MANUAL (offline sales pencatatan)
-- ---------------------------------------------------------
CREATE TABLE penjualan_manual (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produk_id INT NOT NULL,
  qty INT NOT NULL,
  tanggal DATE NOT NULL DEFAULT (CURRENT_DATE),
  catatan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produk_id) REFERENCES produk(id)
) ENGINE=InnoDB;

-- =========================================================
-- DATA AWAL (SEED)
-- =========================================================

-- Password untuk semua akun contoh: "password123"
-- (hash $2y$ dibuat dengan password_hash('password123', PASSWORD_DEFAULT))
INSERT INTO users (nama, email, no_hp, alamat, password, role) VALUES
('Admin Desa Kaligawe', 'admin@kaligawe.desa.id', '081234500001', 'Balai Desa Kaligawe', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'admin'),
('Ibu Rukmini (Lumpia)', 'rukmini@example.com', '081234500002', 'Dusun 1 Kaligawe', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pelaku_usaha'),
('Pak Karto (Gapoktan)', 'karto@example.com', '081234500003', 'Dusun 2 Kaligawe', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pelaku_usaha'),
('Sari (Pembeli)', 'sari@example.com', '081234500004', 'Cirebon Kota', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pembeli'),
('Budi (Peternakan)', 'budi@example.com', '081234500005', 'Dusun 3 Kaligawe', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pelaku_usaha'),
('Dewi (Kerajinan)', 'dewi@example.com', '081234500006', 'Dusun 2 Kaligawe', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pelaku_usaha'),
('Agus (Pembeli)', 'agus@example.com', '081234500007', 'Cirebon Kota', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pembeli'),
('Fitri (Pembeli)', 'fitri@example.com', '081234500008', 'Sumber, Cirebon', '$2y$10$7Y2zxKCm9LoJfkjq4yUL1etMwpZ95acMo/qpSk58lEBYTFmuKLcHO', 'pembeli');

INSERT INTO kategori (nama_kategori, jenis) VALUES
('Makanan Olahan', 'umkm'),
('Kerajinan Tangan', 'umkm'),
('Padi & Palawija', 'pertanian'),
('Peternakan', 'pertanian'),
('Buah-buahan', 'pertanian');

INSERT INTO produk (user_id, kategori_id, sumber_usaha, nama_produk, deskripsi, harga, satuan, stok, foto) VALUES
(2, 1, 'perorangan', 'Lumpia Kaligawe', 'Lumpia rebung khas Desa Kaligawe, isi rebung dan ayam, renyah di luar lembut di dalam.', 2000, 'pcs', 100, 'produk_1.jpg'),
(2, 1, 'perorangan', 'Rengginang', 'Rengginang beras ketan gurih, kemasan 250 gram, cocok untuk oleh-oleh.', 15000, 'bungkus', 50, 'produk_2.jpg'),
(2, 1, 'perorangan', 'Kacang Sangrai', 'Kacang tanah sangrai asin, kemasan 250 gram, camilan sehat keluarga.', 18000, 'bungkus', 40, 'produk_3.jpg'),
(3, 3, 'kelompok_usaha', 'Beras Kaligawe', 'Beras hasil panen sawah Desa Kaligawe, kualitas premium, pulen dan wangi.', 13000, 'kg', 500, 'produk_4.jpg'),
(3, 3, 'kelompok_usaha', 'Jagung Pipil', 'Jagung pipil kering siap olah, cocok untuk pakan ternak atau bahan pangan.', 6000, 'kg', 300, 'produk_5.jpg'),
(3, 3, 'kelompok_usaha', 'Jagung Manis', 'Jagung manis segar hasil panen petani Kaligawe.', 8000, 'kg', 150, 'produk_6.jpg'),
(3, 5, 'kelompok_usaha', 'Semangka', 'Semangka merah manis, dibudidayakan sebagai komoditas sampingan petani jagung.', 10000, 'kg', 80, 'produk_7.jpg'),
(5, 4, 'kelompok_usaha', 'Daging Kambing', 'Daging kambing segar dari peternakan kelompok Dusun 3, dipotong sesuai pesanan.', 120000, 'kg', 40, 'produk_8.jpg'),
(5, 4, 'kelompok_usaha', 'Susu Kambing Etawa', 'Susu kambing etawa segar, dikemas botol 1 liter, kaya manfaat.', 25000, 'liter', 60, 'produk_9.jpg'),
(6, 2, 'kelompok_usaha', 'Tas Anyaman Bambu', 'Tas anyaman bambu buatan tangan warga Dusun 2, kuat dan estetik.', 45000, 'pcs', 25, 'produk_10.jpg'),
(6, 2, 'kelompok_usaha', 'Tikar Pandan', 'Tikar anyaman daun pandan, ukuran 180x200 cm.', 35000, 'pcs', 30, 'produk_11.jpg'),
(6, 2, 'bumdes', 'Keranjang Rotan BUMDes', 'Keranjang rotan produksi BUMDes Desa Kaligawe, berbagai ukuran.', 55000, 'pcs', 20, 'produk_12.jpg');

INSERT INTO pesanan (id, user_id, tanggal, total, nama_penerima, alamat_pengiriman, no_hp_penerima, status) VALUES
(1, 4, '2026-08-05 09:12:00', 85000, 'Sari', 'Jl. Pekiringan No. 12, Cirebon Kota', '081234500004', 'selesai'),
(2, 7, '2026-08-10 14:30:00', 48000, 'Agus', 'Jl. Siliwangi No. 8, Cirebon Kota', '081234500007', 'dikirim'),
(3, 8, '2026-08-15 10:05:00', 240000, 'Fitri', 'Jl. Sunyaragi No. 3, Sumber', '081234500008', 'diproses'),
(4, 4, '2026-08-18 16:45:00', 100000, 'Sari', 'Jl. Pekiringan No. 12, Cirebon Kota', '081234500004', 'menunggu_pembayaran'),
(5, 7, '2026-08-12 08:20:00', 80000, 'Agus', 'Jl. Siliwangi No. 8, Cirebon Kota', '081234500007', 'dibatalkan');

INSERT INTO detail_pesanan (pesanan_id, produk_id, qty, harga_satuan, subtotal) VALUES
(1, 1, 10, 2000, 20000),
(1, 4, 5, 13000, 65000),
(2, 2, 2, 15000, 30000),
(2, 5, 3, 6000, 18000),
(3, 8, 2, 120000, 240000),
(4, 9, 4, 25000, 100000),
(5, 10, 1, 45000, 45000),
(5, 11, 1, 35000, 35000);

INSERT INTO konfirmasi (pesanan_id, bukti_bayar, status_konfirmasi) VALUES
(1, NULL, 'diterima'),
(2, NULL, 'diterima'),
(3, NULL, 'diterima'),
(4, NULL, 'pending'),
(5, NULL, 'ditolak');

INSERT INTO penjualan_manual (produk_id, qty, tanggal, catatan) VALUES
(1, 50, '2026-07-20', 'Penjualan offline pasar minggu'),
(2, 30, '2026-07-25', 'Titip di warung'),
(4, 100, '2026-07-28', 'Panen raya, terjual ke tengkulak'),
(5, 80, '2026-08-02', 'Pakan ternak lokal'),
(8, 10, '2026-08-10', 'Pesan kambing kurban'),
(9, 20, '2026-08-12', 'Langganan rumah sehat');