const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Content-Type', 'application/json');

  const pool = getPool();
  try {
    const bulan = new Date().toISOString().slice(0, 7); // YYYY-MM

    const [pm, pn, totalProduk, nilaiProduk, stokRendah, nilaiBahan, jenisBahan,
           bahanHampir, trxPm, trxPn, topKategori, alertProduk, alertBahan] = await Promise.all([
      pool.query("SELECT COALESCE(SUM(jumlah),0) AS total FROM pemasukan WHERE TO_CHAR(tanggal,'YYYY-MM')=$1", [bulan]),
      pool.query("SELECT COALESCE(SUM(jumlah),0) AS total FROM pengeluaran WHERE TO_CHAR(tanggal,'YYYY-MM')=$1", [bulan]),
      pool.query('SELECT COUNT(*) AS total FROM produk'),
      pool.query('SELECT COALESCE(SUM(harga*stok),0) AS total FROM produk'),
      pool.query('SELECT COUNT(*) AS total FROM produk WHERE stok <= stok_minimum'),
      pool.query('SELECT COALESCE(SUM(harga*stok),0) AS total FROM bahanbaku'),
      pool.query('SELECT COUNT(*) AS total FROM bahanbaku'),
      pool.query('SELECT COUNT(*) AS total FROM bahanbaku WHERE stok > 0 AND stok <= stok_minimum'),
      pool.query("SELECT COUNT(*) AS total FROM pemasukan WHERE TO_CHAR(tanggal,'YYYY-MM')=$1", [bulan]),
      pool.query("SELECT COUNT(*) AS total FROM pengeluaran WHERE TO_CHAR(tanggal,'YYYY-MM')=$1", [bulan]),
      pool.query("SELECT kategori FROM pengeluaran WHERE TO_CHAR(tanggal,'YYYY-MM')=$1 GROUP BY kategori ORDER BY SUM(jumlah) DESC LIMIT 1", [bulan]),
      pool.query('SELECT nama, stok, stok_minimum FROM produk WHERE stok <= stok_minimum ORDER BY stok ASC'),
      pool.query('SELECT nama, stok, stok_minimum, satuan FROM bahanbaku WHERE stok <= stok_minimum ORDER BY stok ASC'),
    ]);

    const pemasukan = parseInt(pm.rows[0].total);
    const pengeluaran = parseInt(pn.rows[0].total);
    const jumlah_transaksi_pm = parseInt(trxPm.rows[0].total);
    const jumlah_transaksi_pn = parseInt(trxPn.rows[0].total);

    const alerts = [
      ...alertProduk.rows.map(r => ({
        type: 'produk', nama: r.nama,
        level: parseInt(r.stok) === 0 ? 'danger' : 'warn',
        stok: parseFloat(r.stok),
      })),
      ...alertBahan.rows.map(r => ({
        type: 'bahanbaku', nama: r.nama,
        level: parseFloat(r.stok) === 0 ? 'danger' : 'warn',
        stok: parseFloat(r.stok), satuan: r.satuan,
      })),
    ];

    return res.json({
      pemasukan_bulan_ini:      pemasukan,
      pengeluaran_bulan_ini:    pengeluaran,
      laba_bersih:              pemasukan - pengeluaran,
      total_produk:             parseInt(totalProduk.rows[0].total),
      total_nilai_produk:       parseInt(nilaiProduk.rows[0].total),
      stok_rendah_count:        parseInt(stokRendah.rows[0].total),
      total_nilai_bahan:        parseInt(nilaiBahan.rows[0].total),
      total_jenis_bahan:        parseInt(jenisBahan.rows[0].total),
      bahan_hampir_habis_count: parseInt(bahanHampir.rows[0].total),
      jumlah_transaksi_pm,
      rata_rata_pm:             jumlah_transaksi_pm > 0 ? Math.round(pemasukan / jumlah_transaksi_pm) : 0,
      jumlah_transaksi_pn,
      biaya_terbesar_kategori:  topKategori.rows[0]?.kategori || '—',
      stok_alerts:              alerts,
    });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
