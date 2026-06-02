const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();

  const pool = getPool();
  try {
    if (req.method === 'GET') {
      const { rows } = await pool.query('SELECT * FROM bahanbaku ORDER BY nama');
      return res.json(rows);

    } else if (req.method === 'POST') {
      const { nama, kategori, harga, satuan = 'gram', stok, stok_minimum } = req.body || {};
      const { rows } = await pool.query(
        'INSERT INTO bahanbaku (nama, kategori, harga, satuan, stok, stok_minimum) VALUES ($1,$2,$3,$4,$5,$6) RETURNING id',
        [nama || '', kategori || '', parseInt(harga) || 0, satuan, parseFloat(stok) || 0, parseFloat(stok_minimum) || 0]
      );
      return res.json({ success: true, id: rows[0].id });

    } else if (req.method === 'PUT') {
      const { id, nama, kategori, harga, satuan = 'gram', stok, stok_minimum } = req.body || {};
      if (!id || !nama) return res.status(400).json({ success: false, error: 'Data tidak lengkap' });
      await pool.query(
        'UPDATE bahanbaku SET nama=$1, kategori=$2, harga=$3, satuan=$4, stok=$5, stok_minimum=$6 WHERE id=$7',
        [nama, kategori || '', parseInt(harga) || 0, satuan, parseFloat(stok) || 0, parseFloat(stok_minimum) || 0, parseInt(id)]
      );
      return res.json({ success: true });

    } else if (req.method === 'DELETE') {
      const id = parseInt(req.query.id);
      await pool.query('DELETE FROM bahanbaku WHERE id=$1', [id]);
      return res.json({ success: true });

    } else {
      return res.status(405).json({ error: 'Method not allowed' });
    }
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
