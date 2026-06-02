<?php
error_reporting(0);
ob_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { ob_end_clean(); exit(0); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../../config/db.php';

function send_json($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    $d      = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($d['id'] ?? 0);
    $action = $d['action'] ?? 'add';   // 'add' | 'remove'
    $jumlah = (float)($d['jumlah'] ?? 0);
    $reason = $d['reason'] ?? 'used';  // 'used' | 'spoiled'

    if (!$id || !in_array($action, ['add', 'remove']) || $jumlah <= 0) {
        send_json(['success' => false, 'message' => 'Input tidak valid'], 400);
    }

    $stmt = $pdo->prepare('SELECT nama, stok, satuan FROM bahanbaku WHERE id = ?');
    $stmt->execute([$id]);
    $bahan = $stmt->fetch();

    if (!$bahan) {
        send_json(['success' => false, 'message' => 'Bahan baku tidak ditemukan']);
    }

    $stok_before = (float)$bahan['stok'];
    $nama        = $bahan['nama'];
    $satuan      = $bahan['satuan'];

    if ($action === 'add') {
        $stok_after  = $stok_before + $jumlah;
        $action_type = 'tambah';
        $keterangan  = 'Penambahan stok ' . $jumlah . ' ' . $satuan;
    } else {
        if ($jumlah > $stok_before) {
            send_json(['success' => false, 'message' => 'Stok tidak mencukupi. Stok saat ini: ' . $stok_before . ' ' . $satuan]);
        }
        $stok_after  = $stok_before - $jumlah;
        $action_type = $reason === 'spoiled' ? 'remove_spoiled' : 'remove_used';
        $keterangan  = $reason === 'spoiled'
            ? 'Stok dibuang/rusak ' . $jumlah . ' ' . $satuan
            : 'Stok terpakai ' . $jumlah . ' ' . $satuan;
    }

    $pdo->prepare('UPDATE bahanbaku SET stok = ? WHERE id = ?')->execute([$stok_after, $id]);

    try {
        $pdo->prepare(
            'INSERT INTO stok_history (entity_type, entity_id, entity_nama, action_type, jumlah, stok_before, stok_after, satuan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute(['bahanbaku', $id, $nama, $action_type, $jumlah, $stok_before, $stok_after, $satuan, $keterangan]);
    } catch (PDOException $ignored) {}

    send_json(['success' => true, 'stok_after' => $stok_after]);

} catch (\Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}
