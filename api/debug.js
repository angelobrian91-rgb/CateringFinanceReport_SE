const { Pool } = require('pg');

module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Content-Type', 'application/json');

  const url = process.env.DATABASE_URL;

  // Step 1: cek env variable ada atau tidak
  if (!url) {
    return res.status(500).json({
      step: 'env_check',
      ok: false,
      error: 'DATABASE_URL is NOT SET — env variable tidak terbaca di Vercel',
    });
  }

  // Step 2: tampilkan partial URL (sensor password)
  const safeUrl = url.replace(/:([^@]+)@/, ':[HIDDEN]@');

  // Step 3: coba connect
  const pool = new Pool({
    connectionString: url,
    ssl: { rejectUnauthorized: false },
    connectionTimeoutMillis: 8000,
  });

  try {
    const client = await pool.connect();
    const result = await client.query('SELECT NOW() as time, current_database() as db');
    client.release();
    await pool.end();

    return res.json({
      step: 'query',
      ok: true,
      url_used: safeUrl,
      db: result.rows[0].db,
      server_time: result.rows[0].time,
      message: 'Database connected successfully!',
    });
  } catch (e) {
    await pool.end().catch(() => {});
    return res.status(500).json({
      step: 'connect',
      ok: false,
      url_used: safeUrl,
      error: e.message,
      hint: getHint(e.message),
    });
  }
};

function getHint(msg) {
  if (msg.includes('password authentication failed'))
    return 'Password salah — reset database password di Supabase > Settings > Database > Reset password';
  if (msg.includes('ECONNREFUSED') || msg.includes('ENOTFOUND'))
    return 'Host tidak bisa dicapai — pastikan pakai Pooler URL (port 6543), bukan direct (port 5432)';
  if (msg.includes('timeout') || msg.includes('ETIMEDOUT'))
    return 'Connection timeout — pastikan pakai Transaction Pooler URL dari Supabase, bukan direct connection';
  if (msg.includes('SSL'))
    return 'SSL error — coba tambah ?sslmode=require di akhir DATABASE_URL';
  if (msg.includes('does not exist'))
    return 'Database tidak ditemukan — cek nama database di connection string';
  return 'Cek format DATABASE_URL dan pastikan password benar';
}
