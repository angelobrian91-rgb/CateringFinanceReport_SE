-- Run this on an existing catfinr database to add stok_history support
USE catfinr;

CREATE TABLE IF NOT EXISTS stok_history (
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
