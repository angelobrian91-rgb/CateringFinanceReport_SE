module.exports = function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

  const { username, password } = req.body || {};
  const APP_USERNAME = process.env.APP_USERNAME || 'AngeloNvMirza';
  const APP_PASSWORD = process.env.APP_PASSWORD || 'user12345';

  if ((username || '').trim() === APP_USERNAME && password === APP_PASSWORD) {
    return res.json({ success: true, user: { username: APP_USERNAME, role: 'owner' } });
  }
  return res.status(401).json({ success: false, message: 'Username atau password salah.' });
};
