<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM produk ORDER BY nama');
        echo json_encode($stmt->fetchAll());

    } elseif ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare(
            'INSERT INTO produk (nama, kategori, harga, stok, stok_minimum) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$d['nama'], $d['kategori'], (int)$d['harga'], (int)$d['stok'], (int)$d['stok_minimum']]);
        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);

    } elseif ($method === 'PUT') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare(
            'UPDATE produk SET nama=?, kategori=?, harga=?, stok=?, stok_minimum=? WHERE id=?'
        );
        $stmt->execute([$d['nama'], $d['kategori'], (int)$d['harga'], (int)$d['stok'], (int)$d['stok_minimum'], (int)$d['id']]);
        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM produk WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
