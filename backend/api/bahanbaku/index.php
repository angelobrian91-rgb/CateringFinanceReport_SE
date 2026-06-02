<?php
error_reporting(0);
ob_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { ob_end_clean(); exit(0); }

require_once '../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function send_json($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM bahanbaku ORDER BY nama');
        send_json($stmt->fetchAll());

    } elseif ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare(
            'INSERT INTO bahanbaku (nama, kategori, harga, satuan, stok, stok_minimum) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['nama'] ?? '',
            $d['kategori'] ?? '',
            (int)($d['harga'] ?? 0),
            $d['satuan'] ?? 'gram',
            (float)($d['stok'] ?? 0),
            (float)($d['stok_minimum'] ?? 0),
        ]);
        send_json(['success' => true, 'id' => (int)$pdo->lastInsertId()]);

    } elseif ($method === 'PUT') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int)($d['id'] ?? 0);
        if (!$id || empty($d['nama'])) {
            send_json(['success' => false, 'error' => 'Data tidak lengkap'], 400);
        }
        $stmt = $pdo->prepare(
            'UPDATE bahanbaku SET nama=?, kategori=?, harga=?, satuan=?, stok=?, stok_minimum=? WHERE id=?'
        );
        $stmt->execute([
            $d['nama'],
            $d['kategori'] ?? '',
            (int)($d['harga'] ?? 0),
            $d['satuan'] ?? 'gram',
            (float)($d['stok'] ?? 0),
            (float)($d['stok_minimum'] ?? 0),
            $id,
        ]);
        send_json(['success' => true]);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM bahanbaku WHERE id=?')->execute([$id]);
        send_json(['success' => true]);

    } else {
        send_json(['error' => 'Method not allowed'], 405);
    }
} catch (\Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}
