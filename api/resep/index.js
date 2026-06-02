const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();

  const pool = getPool();
  try {
    if (req.method === 'GET') {
      const produk_id = parseInt(req.query.produk_id);
      if (!produk_id) return res.json([]);
      const { rows } = await pool.query(
        `SELECT r.*, b.nama AS bahan_nama, b.satuan
         FROM resep r
         JOIN bahanbaku b ON b.id = r.bahan_baku_id
         WHERE r.produk_id = $1`,
        [produk_id]
      );
      return res.json(rows);

    } else if (req.method === 'POST') {
      const { produk_id, bahan_baku_id, jumlah } = req.body || {};
      await pool.query(
        `INSERT INTO resep (produk_id, bahan_baku_id, jumlah) VALUES ($1,$2,$3)
         ON CONFLICT (produk_id, bahan_baku_id) DO UPDATE SET jumlah = EXCLUDED.jumlah`,
        [parseInt(produk_id), parseInt(bahan_baku_id), parseFloat(jumlah)]
      );
      return res.json({ success: true });

    } else if (req.method === 'DELETE') {
      const produk_id = parseInt(req.query.produk_id);
      const bahan_baku_id = parseInt(req.query.bahan_baku_id);
      await pool.query('DELETE FROM resep WHERE produk_id=$1 AND bahan_baku_id=$2', [produk_id, bahan_baku_id]);
      return res.json({ success: true });

    } else {
      return res.status(405).json({ error: 'Method not allowed' });
    }
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
