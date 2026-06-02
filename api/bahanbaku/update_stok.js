const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  const pool = getPool();
  try {
    const { id, action = 'add', jumlah, reason = 'used' } = req.body || {};
    const bahanbakuId = parseInt(id);
    const qty = parseFloat(jumlah);

    if (!bahanbakuId || !['add', 'remove'].includes(action) || !(qty > 0)) {
      return res.status(400).json({ success: false, message: 'Input tidak valid' });
    }

    const { rows } = await pool.query('SELECT nama, stok, satuan FROM bahanbaku WHERE id = $1', [bahanbakuId]);
    if (!rows[0]) return res.json({ success: false, message: 'Bahan baku tidak ditemukan' });

    const stok_before = parseFloat(rows[0].stok);
    const nama = rows[0].nama;
    const satuan = rows[0].satuan;
    let stok_after, action_type, keterangan;

    if (action === 'add') {
      stok_after = stok_before + qty;
      action_type = 'tambah';
      keterangan = `Penambahan stok ${qty} ${satuan}`;
    } else {
      if (qty > stok_before) {
        return res.json({ success: false, message: `Stok tidak mencukupi. Stok saat ini: ${stok_before} ${satuan}` });
      }
      stok_after = stok_before - qty;
      action_type = reason === 'spoiled' ? 'remove_spoiled' : 'remove_used';
      keterangan = reason === 'spoiled' ? `Stok dibuang/rusak ${qty} ${satuan}` : `Stok terpakai ${qty} ${satuan}`;
    }

    await pool.query('UPDATE bahanbaku SET stok = $1 WHERE id = $2', [stok_after, bahanbakuId]);
    try {
      await pool.query(
        `INSERT INTO stok_history (entity_type,entity_id,entity_nama,action_type,jumlah,stok_before,stok_after,satuan,keterangan)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)`,
        ['bahanbaku', bahanbakuId, nama, action_type, qty, stok_before, stok_after, satuan, keterangan]
      );
    } catch (_) {}

    return res.json({ success: true, stok_after });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
};
