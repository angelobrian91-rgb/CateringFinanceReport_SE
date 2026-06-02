-- CatFin-R Schema for Supabase (PostgreSQL)
-- Run this in: Supabase Dashboard → SQL Editor → New Query

-- ─── Tables ───────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS produk (
  id            SERIAL PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  kategori      VARCHAR(50),
  harga         INTEGER NOT NULL DEFAULT 0,
  stok          INTEGER NOT NULL DEFAULT 0,
  stok_minimum  INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS bahanbaku (
  id            SERIAL PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  kategori      VARCHAR(50),
  harga         INTEGER NOT NULL DEFAULT 0,
  satuan        VARCHAR(20) DEFAULT 'gram',
  stok          DECIMAL(10,2) NOT NULL DEFAULT 0,
  stok_minimum  DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS resep (
  produk_id     INTEGER NOT NULL REFERENCES produk(id) ON DELETE CASCADE,
  bahan_baku_id INTEGER NOT NULL REFERENCES bahanbaku(id) ON DELETE CASCADE,
  jumlah        DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (produk_id, bahan_baku_id)
);

CREATE TABLE IF NOT EXISTS pemasukan (
  id                  SERIAL PRIMARY KEY,
  tanggal             DATE NOT NULL,
  keterangan          VARCHAR(200),
  sumber              VARCHAR(100),
  jumlah              INTEGER NOT NULL DEFAULT 0,
  metode_pembayaran   VARCHAR(50),
  nomor_invoice       VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS pengeluaran (
  id                  SERIAL PRIMARY KEY,
  tanggal             DATE NOT NULL,
  keterangan          VARCHAR(200),
  kategori            VARCHAR(100),
  jumlah              INTEGER NOT NULL DEFAULT 0,
  metode_pembayaran   VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS stok_history (
  id            SERIAL PRIMARY KEY,
  entity_type   TEXT NOT NULL CHECK (entity_type IN ('produk', 'bahanbaku')),
  entity_id     INTEGER NOT NULL,
  entity_nama   VARCHAR(100),
  action_type   VARCHAR(30) NOT NULL,
  jumlah        DECIMAL(10,2) NOT NULL DEFAULT 0,
  stok_before   DECIMAL(10,2),
  stok_after    DECIMAL(10,2),
  satuan        VARCHAR(20),
  keterangan    VARCHAR(200),
  created_at    TIMESTAMPTZ DEFAULT NOW()
);

-- ─── Seed: produk ─────────────────────────────────────────────────────────────

INSERT INTO produk (nama, kategori, harga, stok, stok_minimum) VALUES
('Nasi Goreng Special', 'Nasi Box', 25000, 0, 10),
('Nasi Katsu',          'Nasi Box', 30000, 0, 10),
('Bihun Goreng',        'Mie',      20000, 0, 10),
('Mie Goreng',          'Mie',      22000, 0, 10);

-- ─── Seed: bahan baku ─────────────────────────────────────────────────────────

INSERT INTO bahanbaku (nama, kategori, harga, satuan, stok, stok_minimum) VALUES
('Bawang Merah',  'Bumbu',            28, 'gram',  2000.00, 3000.00),
('Minyak Goreng', 'Minyak & Lemak',   20, 'ml',    1000.00, 5000.00),
('Bayam',         'Sayuran',           5, 'gram',   800.00,  500.00),
('Nasi Putih',    'Karbohidrat',       2, 'gram',  5000.00,  500.00),
('Telur Ayam',    'Daging & Protein', 33, 'gram',  6000.00, 1200.00),
('Daging Ayam',   'Daging & Protein', 38, 'gram',  3000.00,  500.00),
('Bawang Putih',  'Bumbu',            60, 'gram',   400.00,  100.00),
('Kecap Manis',   'Bumbu',            15, 'ml',    1000.00,  100.00),
('Garam',         'Bumbu',             1, 'gram',  2000.00,  100.00),
('Tepung Terigu', 'Karbohidrat',       2, 'gram',  2000.00,  200.00),
('Tepung Panir',  'Karbohidrat',       4, 'gram',  1000.00,  100.00),
('Saus Teriyaki', 'Bumbu',            25, 'ml',     500.00,   50.00),
('Bihun',         'Karbohidrat',       3, 'gram',  1000.00,  100.00),
('Sawi Hijau',    'Sayuran',           5, 'gram',   500.00,   50.00),
('Bakso Sapi',    'Daging & Protein', 60, 'gram',  2500.00,  500.00),
('Sayur Kol',     'Sayuran',           4, 'gram',   500.00,   50.00),
('Sosis Ayam',    'Daging & Protein', 67, 'gram',  3000.00,  600.00),
('Saus Tiram',    'Bumbu',            20, 'ml',     500.00,   50.00),
('Merica Bubuk',  'Bumbu',             5, 'gram',   200.00,   20.00),
('Mie',           'Karbohidrat',       3, 'gram',  1000.00,  100.00);

-- ─── Seed: pemasukan ──────────────────────────────────────────────────────────

INSERT INTO pemasukan (tanggal, keterangan, sumber, jumlah, metode_pembayaran, nomor_invoice) VALUES
('2026-04-19', 'Penjualan catering acara nikah', 'Invoice Terbayar', 1500000, 'Transfer', 'INV-OUT-042'),
('2026-04-18', 'DP paket tumpeng ulang tahun',   'Uang Muka (DP)',    500000, 'Tunai',    'INV-OUT-041'),
('2026-04-18', 'Penjualan nasi box 30 pcs',       'Penjualan Produk', 1050000, 'E-Wallet',  NULL),
('2026-04-17', 'Pelunasan invoice kantor',         'Invoice Terbayar',  750000, 'Transfer', 'INV-OUT-040'),
('2026-04-17', 'Penjualan snack box 20 pcs',       'Penjualan Produk',  450000, 'Tunai',    NULL);

-- ─── Seed: pengeluaran ────────────────────────────────────────────────────────

INSERT INTO pengeluaran (tanggal, keterangan, kategori, jumlah, metode_pembayaran) VALUES
('2026-04-19', 'Pembelian beras 10kg',         'Bahan Baku',   150000, 'Tunai'),
('2026-04-18', 'Gaji karyawan April',           'Tenaga Kerja', 800000, 'Transfer'),
('2026-04-17', 'Bensin pengiriman',             'Transportasi',  85000, 'Tunai'),
('2026-04-16', 'Pembelian bahan via supplier',  'Bahan Baku',   650000, 'Transfer'),
('2026-04-15', 'Listrik & air',                 'Utilitas',     215000, 'Transfer');

-- ─── Seed: resep ──────────────────────────────────────────────────────────────
-- bahanbaku IDs: 1=Bawang Merah, 2=Minyak Goreng, 3=Bayam, 4=Nasi Putih
-- 5=Telur Ayam, 6=Daging Ayam, 7=Bawang Putih, 8=Kecap Manis, 9=Garam
-- 10=Tepung Terigu, 11=Tepung Panir, 12=Saus Teriyaki, 13=Bihun, 14=Sawi Hijau
-- 15=Bakso Sapi, 16=Sayur Kol, 17=Sosis Ayam, 18=Saus Tiram, 19=Merica Bubuk, 20=Mie

INSERT INTO resep (produk_id, bahan_baku_id, jumlah) VALUES
-- Nasi Goreng Special (produk 1)
(1,  4, 200), (1,  5, 120), (1,  6,  50), (1,  2,  50),
(1,  1,  60), (1,  7,  10), (1,  8,  15), (1,  9,   2),
-- Nasi Katsu (produk 2)
(2,  4, 200), (2,  5,  60), (2,  6, 150), (2,  2, 150),
(2,  9,   2), (2, 10,  30), (2, 11,  50), (2, 12,  30),
-- Bihun Goreng (produk 3)
(3, 13, 100), (3,  5, 120), (3, 14,  50), (3, 15,  75),
(3,  2,  15), (3,  1,  60), (3,  7,  10), (3,  8,  15),
(3, 18,  10), (3,  9,   2), (3, 19,   1),
-- Mie Goreng (produk 4)
(4, 20, 100), (4,  5, 120), (4, 16,  50), (4, 17,  90),
(4,  2,  15), (4,  1,  60), (4,  7,  10), (4,  8,  20),
(4, 18,  10), (4,  9,   2), (4, 19,   1);

-- ─── Reset sequences ──────────────────────────────────────────────────────────

SELECT setval(pg_get_serial_sequence('produk',     'id'), MAX(id)) FROM produk;
SELECT setval(pg_get_serial_sequence('bahanbaku',  'id'), MAX(id)) FROM bahanbaku;
SELECT setval(pg_get_serial_sequence('pemasukan',  'id'), MAX(id)) FROM pemasukan;
SELECT setval(pg_get_serial_sequence('pengeluaran','id'), MAX(id)) FROM pengeluaran;
