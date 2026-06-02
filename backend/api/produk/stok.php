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
    $qty       = (int)($d['qty'] ?? 0);

    if (!$produk_id || $qty <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'produk_id dan qty wajib diisi']);
        exit;
    }

    // Fetch recipe for this product
    $stmt = $pdo->prepare(
        'SELECT r.bahan_baku_id, r.jumlah, b.nama, b.stok, b.satuan, b.stok_minimum
         FROM resep r
         JOIN bahanbaku b ON b.id = r.bahan_baku_id
         WHERE r.produk_id = ?'
    );
    $stmt->execute([$produk_id]);
    $resep = $stmt->fetchAll();

    // No recipe: just increment stok directly
    if (empty($resep)) {
        $pdo->prepare('UPDATE produk SET stok = stok + ? WHERE id = ?')->execute([$qty, $produk_id]);
        echo json_encode(['success' => true, 'warnings' => []]);
        exit;
    }

    // Check if every ingredient has enough stock
    $max_porsi = PHP_INT_MAX;
    $detail    = [];
    foreach ($resep as $r) {
        $needed    = (float)$r['jumlah'] * $qty;
        $available = (float)$r['stok'];
        $bisa      = $r['jumlah'] > 0 ? (int)floor($available / (float)$r['jumlah']) : PHP_INT_MAX;
        if ($bisa < $max_porsi) $max_porsi = $bisa;
        $detail[] = [
            'nama'       => $r['nama'],
            'dibutuhkan' => $needed,
            'tersedia'   => $available,
            'satuan'     => $r['satuan'],
            'cukup'      => $available >= $needed,
        ];
    }
    if ($max_porsi === PHP_INT_MAX) $max_porsi = 0;

    $insufficient = array_values(array_filter($detail, fn($x) => !$x['cukup']));
    if (!empty($insufficient)) {
        echo json_encode([
            'success'   => false,
            'message'   => "Stok bahan baku tidak cukup untuk $qty porsi. Stok hanya cukup untuk $max_porsi porsi.",
            'max_porsi' => $max_porsi,
            'detail'    => $insufficient,
        ]);
        exit;
    }

    // Get current produk stok for history
    $stmtP = $pdo->prepare('SELECT nama, stok FROM produk WHERE id = ?');
    $stmtP->execute([$produk_id]);
    $produkRow   = $stmtP->fetch();
    $stok_before = (int)$produkRow['stok'];
    $produk_nama = $produkRow['nama'];

    // Deduct ingredients and add product stock atomically
    $pdo->beginTransaction();
    $stmtDeduct = $pdo->prepare('UPDATE bahanbaku SET stok = stok - ? WHERE id = ?');
    foreach ($resep as $r) {
        $stmtDeduct->execute([(float)$r['jumlah'] * $qty, $r['bahan_baku_id']]);
    }
    $pdo->prepare('UPDATE produk SET stok = stok + ? WHERE id = ?')->execute([$qty, $produk_id]);
    $pdo->commit();

    try {
        $pdo->prepare(
            'INSERT INTO stok_history (entity_type, entity_id, entity_nama, action_type, jumlah, stok_before, stok_after, satuan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute(['produk', $produk_id, $produk_nama, 'produksi', $qty, $stok_before, $stok_before + $qty, 'pcs', 'Produksi ' . $qty . ' porsi']);
    } catch (PDOException $e) {
        // stok_history table may not exist; stok and ingredients were still updated
    }

    // Collect warnings for ingredients that hit or dropped below minimum
    $warnings = [];
    foreach ($resep as $r) {
        $newStok = (float)$r['stok'] - ((float)$r['jumlah'] * $qty);
        if ($newStok <= (float)$r['stok_minimum']) {
            $warnings[] = [
                'nama'   => $r['nama'],
                'stok'   => round($newStok, 2),
                'satuan' => $r['satuan'],
                'level'  => $newStok <= 0 ? 'danger' : 'warn',
            ];
        }
    }

    echo json_encode(['success' => true, 'warnings' => $warnings]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
