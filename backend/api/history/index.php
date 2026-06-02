<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../../config/db.php';

try {
    $type = $_GET['type'] ?? '';
    $sql = 'SELECT * FROM stok_history';
    $params = [];
    if ($type === 'produk' || $type === 'bahanbaku') {
        $sql .= ' WHERE entity_type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
