<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';

try {
    $bulan = date('Y-m');

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM pemasukan WHERE DATE_FORMAT(tanggal,'%Y-%m')=?");
    $stmt->execute([$bulan]);
    $pemasukan = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran WHERE DATE_FORMAT(tanggal,'%Y-%m')=?");
    $stmt->execute([$bulan]);
    $pengeluaran = (int)$stmt->fetchColumn();

    $total_produk = (int)$pdo->query('SELECT COUNT(*) FROM produk')->fetchColumn();

    /* ── Stok Produk stats ── */
    $total_nilai_produk = (int)$pdo->query('SELECT COALESCE(SUM(harga*stok),0) FROM produk')->fetchColumn();
    $stok_rendah_count  = (int)$pdo->query('SELECT COUNT(*) FROM produk WHERE stok <= stok_minimum')->fetchColumn();

    /* ── Stok Bahan Baku stats ── */
    $total_nilai_bahan      = (int)$pdo->query('SELECT COALESCE(SUM(harga*stok),0) FROM bahanbaku')->fetchColumn();
    $total_jenis_bahan      = (int)$pdo->query('SELECT COUNT(*) FROM bahanbaku')->fetchColumn();
    $bahan_hampir_habis_count = (int)$pdo->query('SELECT COUNT(*) FROM bahanbaku WHERE stok > 0 AND stok <= stok_minimum')->fetchColumn();

    /* ── Pemasukan stats (bulan ini) ── */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pemasukan WHERE DATE_FORMAT(tanggal,'%Y-%m')=?");
    $stmt->execute([$bulan]);
    $jumlah_transaksi_pm = (int)$stmt->fetchColumn();
    $rata_rata_pm = $jumlah_transaksi_pm > 0 ? (int)round($pemasukan / $jumlah_transaksi_pm) : 0;

    /* ── Pengeluaran stats (bulan ini) ── */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengeluaran WHERE DATE_FORMAT(tanggal,'%Y-%m')=?");
    $stmt->execute([$bulan]);
    $jumlah_transaksi_pn = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT kategori FROM pengeluaran WHERE DATE_FORMAT(tanggal,'%Y-%m')=? GROUP BY kategori ORDER BY SUM(jumlah) DESC LIMIT 1");
    $stmt->execute([$bulan]);
    $biaya_terbesar_kategori = $stmt->fetchColumn() ?: '—';

    $alerts = [];
    foreach ($pdo->query('SELECT nama, stok, stok_minimum FROM produk WHERE stok <= stok_minimum ORDER BY stok ASC')->fetchAll() as $r) {
        $alerts[] = [
            'type'  => 'produk',
            'nama'  => $r['nama'],
            'level' => (int)$r['stok'] === 0 ? 'danger' : 'warn',
            'stok'  => (float)$r['stok'],
        ];
    }
    foreach ($pdo->query('SELECT nama, stok, stok_minimum, satuan FROM bahanbaku WHERE stok <= stok_minimum ORDER BY stok ASC')->fetchAll() as $r) {
        $alerts[] = [
            'type'   => 'bahanbaku',
            'nama'   => $r['nama'],
            'level'  => (float)$r['stok'] == 0 ? 'danger' : 'warn',
            'stok'   => (float)$r['stok'],
            'satuan' => $r['satuan'],
        ];
    }

    echo json_encode([
        'pemasukan_bulan_ini'       => $pemasukan,
        'pengeluaran_bulan_ini'     => $pengeluaran,
        'laba_bersih'               => $pemasukan - $pengeluaran,
        'total_produk'              => $total_produk,
        'total_nilai_produk'        => $total_nilai_produk,
        'stok_rendah_count'         => $stok_rendah_count,
        'total_nilai_bahan'         => $total_nilai_bahan,
        'total_jenis_bahan'         => $total_jenis_bahan,
        'bahan_hampir_habis_count'  => $bahan_hampir_habis_count,
        'jumlah_transaksi_pm'       => $jumlah_transaksi_pm,
        'rata_rata_pm'              => $rata_rata_pm,
        'jumlah_transaksi_pn'       => $jumlah_transaksi_pn,
        'biaya_terbesar_kategori'   => $biaya_terbesar_kategori,
        'stok_alerts'               => $alerts,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
