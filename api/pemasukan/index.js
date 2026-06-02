const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();

  const pool = getPool();
  try {
    if (req.method === 'GET') {
      const bulan = req.query.bulan || '';
      let query, params = [];
      if (bulan) {
        query = "SELECT * FROM pemasukan WHERE TO_CHAR(tanggal,'YYYY-MM')=$1 ORDER BY tanggal DESC";
        params = [bulan];
      } else {
        query = 'SELECT * FROM pemasukan ORDER BY tanggal DESC';
      }
      const { rows } = await pool.query(query, params);
      return res.json(rows);

    } else if (req.method === 'POST') {
      const { tanggal, keterangan, sumber, jumlah, metode_pembayaran, nomor_invoice } = req.body || {};
      const { rows } = await pool.query(
        'INSERT INTO pemasukan (tanggal,keterangan,sumber,jumlah,metode_pembayaran,nomor_invoice) VALUES ($1,$2,$3,$4,$5,$6) RETURNING id',
        [tanggal, keterangan, sumber, parseInt(jumlah) || 0, metode_pembayaran, nomor_invoice || null]
      );
      return res.json({ success: true, id: rows[0].id });

    } else if (req.method === 'PUT') {
      const { id, tanggal, keterangan, sumber, jumlah, metode_pembayaran, nomor_invoice } = req.body || {};
      await pool.query(
        'UPDATE pemasukan SET tanggal=$1,keterangan=$2,sumber=$3,jumlah=$4,metode_pembayaran=$5,nomor_invoice=$6 WHERE id=$7',
        [tanggal, keterangan, sumber, parseInt(jumlah) || 0, metode_pembayaran, nomor_invoice || null, parseInt(id)]
      );
      return res.json({ success: true });

    } else if (req.method === 'DELETE') {
      const id = parseInt(req.query.id);
      await pool.query('DELETE FROM pemasukan WHERE id=$1', [id]);
      return res.json({ success: true });

    } else {
      return res.status(405).json({ error: 'Method not allowed' });
    }
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
