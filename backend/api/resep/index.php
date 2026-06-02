<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $produk_id = (int)($_GET['produk_id'] ?? 0);
        if (!$produk_id) {
            echo json_encode([]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT r.*, b.nama AS bahan_nama, b.satuan
                 FROM resep r
                 JOIN bahanbaku b ON b.id = r.bahan_baku_id
                 WHERE r.produk_id = ?'
            );
            $stmt->execute([$produk_id]);
            echo json_encode($stmt->fetchAll());
        }

    } elseif ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare(
            'INSERT INTO resep (produk_id, bahan_baku_id, jumlah) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE jumlah = VALUES(jumlah)'
        );
        $stmt->execute([(int)$d['produk_id'], (int)$d['bahan_baku_id'], (float)$d['jumlah']]);
        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        $produk_id     = (int)($_GET['produk_id'] ?? 0);
        $bahan_baku_id = (int)($_GET['bahan_baku_id'] ?? 0);
        $pdo->prepare('DELETE FROM resep WHERE produk_id=? AND bahan_baku_id=?')->execute([$produk_id, $bahan_baku_id]);
        echo json_encode(['success' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
