const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();

  const pool = getPool();
  try {
    const type = req.query.type || '';
    let query = 'SELECT * FROM stok_history';
    const params = [];
    if (type === 'produk' || type === 'bahanbaku') {
      query += ' WHERE entity_type = $1';
      params.push(type);
    }
    query += ' ORDER BY created_at DESC LIMIT 200';
    const { rows } = await pool.query(query, params);
    return res.json(rows);
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
