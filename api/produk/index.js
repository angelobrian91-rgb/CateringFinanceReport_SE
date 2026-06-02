const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();

  const pool = getPool();
  try {
    if (req.method === 'GET') {
      const { rows } = await pool.query('SELECT * FROM produk ORDER BY nama');
      return res.json(rows);

    } else if (req.method === 'POST') {
      const { nama, kategori, harga, stok, stok_minimum } = req.body || {};
      const { rows } = await pool.query(
        'INSERT INTO produk (nama, kategori, harga, stok, stok_minimum) VALUES ($1,$2,$3,$4,$5) RETURNING id',
        [nama, kategori, parseInt(harga) || 0, parseInt(stok) || 0, parseInt(stok_minimum) || 0]
      );
      return res.json({ success: true, id: rows[0].id });

    } else if (req.method === 'PUT') {
      const { id, nama, kategori, harga, stok, stok_minimum } = req.body || {};
      await pool.query(
        'UPDATE produk SET nama=$1, kategori=$2, harga=$3, stok=$4, stok_minimum=$5 WHERE id=$6',
        [nama, kategori, parseInt(harga) || 0, parseInt(stok) || 0, parseInt(stok_minimum) || 0, parseInt(id)]
      );
      return res.json({ success: true });

    } else if (req.method === 'DELETE') {
      const id = parseInt(req.query.id);
      await pool.query('DELETE FROM produk WHERE id=$1', [id]);
      return res.json({ success: true });

    } else {
      return res.status(405).json({ error: 'Method not allowed' });
    }
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
