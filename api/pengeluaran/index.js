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
        query = "SELECT * FROM pengeluaran WHERE TO_CHAR(tanggal,'YYYY-MM')=$1 ORDER BY tanggal DESC";
        params = [bulan];
      } else {
        query = 'SELECT * FROM pengeluaran ORDER BY tanggal DESC';
      }
      const { rows } = await pool.query(query, params);
      return res.json(rows);

    } else if (req.method === 'POST') {
      const { tanggal, keterangan, kategori, jumlah, metode_pembayaran } = req.body || {};
      const { rows } = await pool.query(
        'INSERT INTO pengeluaran (tanggal,keterangan,kategori,jumlah,metode_pembayaran) VALUES ($1,$2,$3,$4,$5) RETURNING id',
        [tanggal, keterangan || '', kategori, parseInt(jumlah) || 0, metode_pembayaran]
      );
      return res.json({ success: true, id: rows[0].id });

    } else if (req.method === 'PUT') {
      const { id, tanggal, keterangan, kategori, jumlah, metode_pembayaran } = req.body || {};
      await pool.query(
        'UPDATE pengeluaran SET tanggal=$1,keterangan=$2,kategori=$3,jumlah=$4,metode_pembayaran=$5 WHERE id=$6',
        [tanggal, keterangan || '', kategori, parseInt(jumlah) || 0, metode_pembayaran, parseInt(id)]
      );
      return res.json({ success: true });

    } else if (req.method === 'DELETE') {
      const id = parseInt(req.query.id);
      await pool.query('DELETE FROM pengeluaran WHERE id=$1', [id]);
      return res.json({ success: true });

    } else {
      return res.status(405).json({ error: 'Method not allowed' });
    }
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
