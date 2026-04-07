<?php
require_once '../config.php';

header('Content-Type: application/json');

$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

try {
    $where = "WHERE 1=1";
    $params = [];

    if($dari) {
        $where .= " AND transaksi_in.tanggal_in >= ?";
        $params[] = $dari;
    }
    if($sampai) {
        $where .= " AND transaksi_in.tanggal_in <= ?";
        $params[] = $sampai;
    }

    $stmt = $pdo->prepare("
        SELECT 
            d.isi_muatan, d.bruto, d.tara, d.netto, d.keterangan,
            k.sopir, k.jenis_kendaraan, k.nopol
        FROM data d
        LEFT JOIN kendaraan k ON d.kendaraan_id = k.id_kendaraan
        LEFT JOIN transaksi_in ti ON d.id_data = ti.id_data
        $where
        ORDER BY ti.tanggal_in DESC
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch(Exception $e) {
    error_log("Laporan API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>


