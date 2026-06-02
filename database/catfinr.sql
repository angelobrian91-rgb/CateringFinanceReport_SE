-- CatFin-R Database Schema + Seed Data
-- Import this file in phpMyAdmin (drop & recreate each time)
-- or run: mysql -u root < catfinr.sql

DROP DATABASE IF EXISTS catfinr;
CREATE DATABASE catfinr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE catfinr;

CREATE TABLE produk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  kategori VARCHAR(50),
  harga INT NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  stok_minimum INT NOT NULL DEFAULT 0
);

CREATE TABLE bahanbaku (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  kategori VARCHAR(50),
  harga INT NOT NULL DEFAULT 0,
  satuan VARCHAR(20) DEFAULT 'gram',
  stok DECIMAL(10,2) NOT NULL DEFAULT 0,
  stok_minimum DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE resep (
  produk_id INT NOT NULL,
  bahan_baku_id INT NOT NULL,
  jumlah DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (produk_id, bahan_baku_id),
  FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
  FOREIGN KEY (bahan_baku_id) REFERENCES bahanbaku(id) ON DELETE CASCADE
);

CREATE TABLE pemasukan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  keterangan VARCHAR(200),
  sumber VARCHAR(100),
  jumlah INT NOT NULL DEFAULT 0,
  metode_pembayaran VARCHAR(50),
  nomor_invoice VARCHAR(50)
);

CREATE TABLE pengeluaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  keterangan VARCHAR(200),
  kategori VARCHAR(100),
  jumlah INT NOT NULL DEFAULT 0,
  metode_pembayaran VARCHAR(50)
);

-- Seed: produk
INSERT INTO produk (nama, kategori, harga, stok, stok_minimum) VALUES
('Nasi Goreng Special',  'Nasi Box',   25000,  0,  10),
('Nasi Katsu',           'Nasi Box',   30000,  0,  10),
('Bihun Goreng',         'Mie',        20000,  0,  10),
('Mie Goreng',           'Mie',        22000,  0,  10);

-- Seed: bahan baku — semua dalam gram atau ml
-- id  1-5 : stok umum (kg/L/ikat dikonversi)
-- id  6-24: bahan resep
INSERT INTO bahanbaku (nama, kategori, harga, satuan, stok, stok_minimum) VALUES
-- ('Beras Premium',        'Karbohidrat',      15, 'gram',  25000.00, 10000.00), --  1
-- ('Ayam Broiler',         'Daging & Protein', 38, 'gram',  12000.00,  8000.00), --  2
('Bawang Merah',         'Bumbu',            28, 'gram',   2000.00,  3000.00), --  3
('Minyak Goreng',        'Minyak & Lemak',   20, 'ml',    1000.00,  5000.00), --  4
('Bayam',                'Sayuran',           5, 'gram',    800.00,   500.00), --  5
('Nasi Putih',           'Karbohidrat',       2, 'gram',   5000.00,   500.00), --  6
('Telur Ayam',           'Daging & Protein', 33, 'gram',   6000.00,  1200.00), --  7  (1 butir≈60g)
('Daging Ayam',          'Daging & Protein', 38, 'gram',   3000.00,   500.00), --  8
-- ('Minyak Goreng (resep)','Minyak & Lemak',   20, 'ml',    2000.00,   200.00), --  9
-- ('Bawang Merah (resep)', 'Bumbu',            28, 'gram',   2000.00,   400.00), -- 10  (1 siung≈20g)
('Bawang Putih',         'Bumbu',            60, 'gram',    400.00,   100.00), -- 11  (1 siung≈5g)
('Kecap Manis',          'Bumbu',            15, 'ml',    1000.00,   100.00), -- 12
('Garam',                'Bumbu',             1, 'gram',   2000.00,   100.00), -- 13
('Tepung Terigu',        'Karbohidrat',       2, 'gram',   2000.00,   200.00), -- 14
('Tepung Panir',         'Karbohidrat',       4, 'gram',   1000.00,   100.00), -- 15
('Saus Teriyaki',        'Bumbu',            25, 'ml',     500.00,    50.00), -- 16
('Bihun',                'Karbohidrat',       3, 'gram',   1000.00,   100.00), -- 17
('Sawi Hijau',           'Sayuran',           5, 'gram',    500.00,    50.00), -- 18
('Bakso Sapi',           'Daging & Protein', 60, 'gram',   2500.00,   500.00), -- 19  (1 buah≈25g)
('Sayur Kol',            'Sayuran',           4, 'gram',    500.00,    50.00), -- 20
('Sosis Ayam',           'Daging & Protein', 67, 'gram',   3000.00,   600.00), -- 21  (1 buah≈30g)
('Saus Tiram',           'Bumbu',            20, 'ml',     500.00,    50.00), -- 22
('Merica Bubuk',         'Bumbu',             5, 'gram',    200.00,    20.00), -- 23
('Mie',                  'Karbohidrat',       3, 'gram',   1000.00,   100.00); -- 24

-- Seed: pemasukan
INSERT INTO pemasukan (tanggal, keterangan, sumber, jumlah, metode_pembayaran, nomor_invoice) VALUES
('2026-04-19', 'Penjualan catering acara nikah',  'Invoice Terbayar',  1500000, 'Transfer', 'INV-OUT-042'),
('2026-04-18', 'DP paket tumpeng ulang tahun',    'Uang Muka (DP)',     500000, 'Tunai',    'INV-OUT-041'),
('2026-04-18', 'Penjualan nasi box 30 pcs',       'Penjualan Produk',  1050000, 'E-Wallet',  NULL),
('2026-04-17', 'Pelunasan invoice kantor',         'Invoice Terbayar',   750000, 'Transfer', 'INV-OUT-040'),
('2026-04-17', 'Penjualan snack box 20 pcs',       'Penjualan Produk',   450000, 'Tunai',    NULL);

-- Seed: pengeluaran
INSERT INTO pengeluaran (tanggal, keterangan, kategori, jumlah, metode_pembayaran) VALUES
('2026-04-19', 'Pembelian beras 10kg',         'Bahan Baku',   150000, 'Tunai'),
('2026-04-18', 'Gaji karyawan April',          'Tenaga Kerja', 800000, 'Transfer'),
('2026-04-17', 'Bensin pengiriman',            'Transportasi',  85000, 'Tunai'),
('2026-04-16', 'Pembelian bahan via supplier', 'Bahan Baku',   650000, 'Transfer'),
('2026-04-15', 'Listrik & air',                'Utilitas',     215000, 'Transfer');

-- Seed: resep (semua dalam gram / ml)
-- Bahanbaku IDs (actual, setelah baris 1-2 dikomentari):
--  1=Bawang Merah, 2=Minyak Goreng, 3=Bayam, 4=Nasi Putih, 5=Telur Ayam
--  6=Daging Ayam, 7=Bawang Putih, 8=Kecap Manis, 9=Garam, 10=Tepung Terigu
-- 11=Tepung Panir, 12=Saus Teriyaki, 13=Bihun, 14=Sawi Hijau, 15=Bakso Sapi
-- 16=Sayur Kol, 17=Sosis Ayam, 18=Saus Tiram, 19=Merica Bubuk, 20=Mie
INSERT INTO resep (produk_id, bahan_baku_id, jumlah) VALUES
-- Nasi Goreng Special (produk 1)
(1,  4, 200),  -- nasi putih 200g
(1,  5, 120),  -- telur ayam 2 butir = 120g
(1,  6,  50),  -- daging ayam 50g
(1,  2,  50),  -- minyak goreng 50ml
(1,  1,  60),  -- bawang merah 3 siung = 60g
(1,  7,  10),  -- bawang putih 2 siung = 10g
(1,  8,  15),  -- kecap manis 15ml
(1,  9,   2),  -- garam 2g
-- Nasi Katsu (produk 2)
(2,  4, 200),  -- nasi putih 200g
(2,  5,  60),  -- telur ayam 1 butir = 60g
(2,  6, 150),  -- daging ayam 150g
(2,  2, 150),  -- minyak goreng 150ml
(2,  9,   2),  -- garam 2g
(2, 10,  30),  -- tepung terigu 30g
(2, 11,  50),  -- tepung panir 50g
(2, 12,  30),  -- saus teriyaki 30ml
-- Bihun Goreng (produk 3)
(3, 13, 100),  -- bihun 100g
(3,  5, 120),  -- telur ayam 2 butir = 120g
(3, 14,  50),  -- sawi hijau 50g
(3, 15,  75),  -- bakso sapi 3 buah = 75g
(3,  2,  15),  -- minyak goreng 15ml
(3,  1,  60),  -- bawang merah 3 siung = 60g
(3,  7,  10),  -- bawang putih 2 siung = 10g
(3,  8,  15),  -- kecap manis 15ml
(3, 18,  10),  -- saus tiram 10ml
(3,  9,   2),  -- garam 2g
(3, 19,   1),  -- merica bubuk 1g
-- Mie Goreng (produk 4)
(4, 20, 100),  -- mie 100g
(4,  5, 120),  -- telur ayam 2 butir = 120g
(4, 16,  50),  -- sayur kol 50g
(4, 17,  90),  -- sosis ayam 3 buah = 90g
(4,  2,  15),  -- minyak goreng 15ml
(4,  1,  60),  -- bawang merah 3 siung = 60g
(4,  7,  10),  -- bawang putih 2 siung = 10g
(4,  8,  20),  -- kecap manis 20ml
(4, 18,  10),  -- saus tiram 10ml
(4,  9,   2),  -- garam 2g
(4, 19,   1);  -- merica bubuk 1g

-- Riwayat update stok
CREATE TABLE stok_history (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('produk','bahanbaku') NOT NULL,
  entity_id   INT NOT NULL,
  entity_nama VARCHAR(100),
  action_type VARCHAR(30) NOT NULL,
  jumlah      DECIMAL(10,2) NOT NULL DEFAULT 0,
  stok_before DECIMAL(10,2),
  stok_after  DECIMAL(10,2),
  satuan      VARCHAR(20),
  keterangan  VARCHAR(200),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
