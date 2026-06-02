<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../../config/db.php';

try {
    $d         = json_decode(file_get_contents('php://input'), true) ?? [];
    $produk_id = (int)($d['produk_id'] ?? 0);
    $action    = $d['action'] ?? '';   // 'add' | 'remove'
    $jumlah    = (int)($d['jumlah'] ?? 0);
    $reason    = $d['reason'] ?? 'sold'; // 'sold' | 'spoiled' (for remove)

    if (!$produk_id || !in_array($action, ['add', 'remove']) || $jumlah <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Input tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT nama, stok FROM produk WHERE id = ?');
    $stmt->execute([$produk_id]);
    $produk = $stmt->fetch();

    if (!$produk) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit;
    }

    $stok_before = (int)$produk['stok'];
    $nama        = $produk['nama'];

    if ($action === 'add') {
        $stok_after  = $stok_before + $jumlah;
        $action_type = 'add';
        $keterangan  = 'Penambahan stok manual ' . $jumlah . ' pcs';
    } else {
        if ($jumlah > $stok_before) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi. Stok saat ini: ' . $stok_before . ' pcs']);
            exit;
        }
        $stok_after  = $stok_before - $jumlah;
        $action_type = $reason === 'spoiled' ? 'remove_spoiled' : 'remove_sold';
        $keterangan  = $reason === 'spoiled' ? 'Stok dibuang/rusak ' . $jumlah . ' pcs' : 'Stok terjual ' . $jumlah . ' pcs';
    }

    $pdo->prepare('UPDATE produk SET stok = ? WHERE id = ?')->execute([$stok_after, $produk_id]);

    try {
        $pdo->prepare(
            'INSERT INTO stok_history (entity_type, entity_id, entity_nama, action_type, jumlah, stok_before, stok_after, satuan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute(['produk', $produk_id, $nama, $action_type, $jumlah, $stok_before, $stok_after, 'pcs', $keterangan]);
    } catch (PDOException $e) {
        // stok_history table may not exist; stok was still updated
    }

    echo json_encode(['success' => true, 'stok_after' => $stok_after]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
