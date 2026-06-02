const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  const pool = getPool();
  try {
    const { produk_id, action, jumlah, reason = 'sold' } = req.body || {};
    const produkId = parseInt(produk_id);
    const qty = parseInt(jumlah);

    if (!produkId || !['add', 'remove'].includes(action) || !(qty > 0)) {
      return res.status(400).json({ success: false, message: 'Input tidak valid' });
    }

    const { rows } = await pool.query('SELECT nama, stok FROM produk WHERE id = $1', [produkId]);
    if (!rows[0]) return res.json({ success: false, message: 'Produk tidak ditemukan' });

    const stok_before = parseInt(rows[0].stok);
    const nama = rows[0].nama;
    let stok_after, action_type, keterangan;

    if (action === 'add') {
      stok_after = stok_before + qty;
      action_type = 'add';
      keterangan = `Penambahan stok manual ${qty} pcs`;
    } else {
      if (qty > stok_before) {
        return res.json({ success: false, message: `Stok tidak mencukupi. Stok saat ini: ${stok_before} pcs` });
      }
      stok_after = stok_before - qty;
      action_type = reason === 'spoiled' ? 'remove_spoiled' : 'remove_sold';
      keterangan = reason === 'spoiled' ? `Stok dibuang/rusak ${qty} pcs` : `Stok terjual ${qty} pcs`;
    }

    await pool.query('UPDATE produk SET stok = $1 WHERE id = $2', [stok_after, produkId]);
    try {
      await pool.query(
        `INSERT INTO stok_history (entity_type,entity_id,entity_nama,action_type,jumlah,stok_before,stok_after,satuan,keterangan)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)`,
        ['produk', produkId, nama, action_type, qty, stok_before, stok_after, 'pcs', keterangan]
      );
    } catch (_) {}

    return res.json({ success: true, stok_after });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
