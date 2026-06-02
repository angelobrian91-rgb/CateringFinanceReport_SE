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
        $bulan = $_GET['bulan'] ?? '';  // format: YYYY-MM
        if ($bulan) {
            $stmt = $pdo->prepare(
                "SELECT * FROM pemasukan WHERE DATE_FORMAT(tanggal,'%Y-%m')=? ORDER BY tanggal DESC"
            );
            $stmt->execute([$bulan]);
        } else {
            $stmt = $pdo->query('SELECT * FROM pemasukan ORDER BY tanggal DESC');
        }
        echo json_encode($stmt->fetchAll());

    } elseif ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare(
            'INSERT INTO pemasukan (tanggal, keterangan, sumber, jumlah, metode_pembayaran, nomor_invoice)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['tanggal'], $d['keterangan'], $d['sumber'],
            (int)$d['jumlah'], $d['metode_pembayaran'],
            $d['nomor_invoice'] ?: null
        ]);
        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);

    } elseif ($method === 'PUT') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $stmt = $pdo->prepare(
            'UPDATE pemasukan SET tanggal=?, keterangan=?, sumber=?, jumlah=?, metode_pembayaran=?, nomor_invoice=? WHERE id=?'
        );
        $stmt->execute([
            $d['tanggal'], $d['keterangan'], $d['sumber'],
            (int)$d['jumlah'], $d['metode_pembayaran'],
            $d['nomor_invoice'] ?: null, (int)$d['id']
        ]);
        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM pemasukan WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
