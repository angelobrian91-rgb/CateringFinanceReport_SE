const { getPool } = require('../_lib/db');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  const pool = getPool();
  let client;
  try {
    const { produk_id, qty } = req.body || {};
    const produkId = parseInt(produk_id);
    const quantity = parseInt(qty);

    if (!produkId || !(quantity > 0)) {
      return res.status(400).json({ success: false, message: 'produk_id dan qty wajib diisi' });
    }

    client = await pool.connect();

    const { rows: resep } = await client.query(
      `SELECT r.bahan_baku_id, r.jumlah, b.nama, b.stok, b.satuan, b.stok_minimum
       FROM resep r
       JOIN bahanbaku b ON b.id = r.bahan_baku_id
       WHERE r.produk_id = $1`,
      [produkId]
    );

    if (resep.length === 0) {
      await client.query('UPDATE produk SET stok = stok + $1 WHERE id = $2', [quantity, produkId]);
      return res.json({ success: true, warnings: [] });
    }

    let max_porsi = Number.MAX_SAFE_INTEGER;
    const detail = [];
    for (const r of resep) {
      const needed = parseFloat(r.jumlah) * quantity;
      const available = parseFloat(r.stok);
      const bisa = parseFloat(r.jumlah) > 0 ? Math.floor(available / parseFloat(r.jumlah)) : Number.MAX_SAFE_INTEGER;
      if (bisa < max_porsi) max_porsi = bisa;
      detail.push({ nama: r.nama, dibutuhkan: needed, tersedia: available, satuan: r.satuan, cukup: available >= needed });
    }
    if (max_porsi === Number.MAX_SAFE_INTEGER) max_porsi = 0;

    const insufficient = detail.filter(x => !x.cukup);
    if (insufficient.length > 0) {
      return res.json({
        success: false,
        message: `Stok bahan baku tidak cukup untuk ${quantity} porsi. Stok hanya cukup untuk ${max_porsi} porsi.`,
        max_porsi,
        detail: insufficient,
      });
    }

    const { rows: produkRows } = await client.query('SELECT nama, stok FROM produk WHERE id = $1', [produkId]);
    const stok_before = parseInt(produkRows[0].stok);
    const produk_nama = produkRows[0].nama;

    await client.query('BEGIN');
    for (const r of resep) {
      await client.query('UPDATE bahanbaku SET stok = stok - $1 WHERE id = $2', [parseFloat(r.jumlah) * quantity, r.bahan_baku_id]);
    }
    await client.query('UPDATE produk SET stok = stok + $1 WHERE id = $2', [quantity, produkId]);
    await client.query('COMMIT');

    try {
      await client.query(
        `INSERT INTO stok_history (entity_type,entity_id,entity_nama,action_type,jumlah,stok_before,stok_after,satuan,keterangan)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)`,
        ['produk', produkId, produk_nama, 'produksi', quantity, stok_before, stok_before + quantity, 'pcs', `Produksi ${quantity} porsi`]
      );
    } catch (_) {}

    const warnings = [];
    for (const r of resep) {
      const newStok = parseFloat(r.stok) - parseFloat(r.jumlah) * quantity;
      if (newStok <= parseFloat(r.stok_minimum)) {
        warnings.push({ nama: r.nama, stok: Math.round(newStok * 100) / 100, satuan: r.satuan, level: newStok <= 0 ? 'danger' : 'warn' });
      }
    }

    return res.json({ success: true, warnings });
  } catch (e) {
    if (client) try { await client.query('ROLLBACK'); } catch (_) {}
    return res.status(500).json({ error: e.message });
  } finally {
    if (client) client.release();
  }
};
