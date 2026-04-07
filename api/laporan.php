<?php
require_once '../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$export = $_GET['export'] ?? '';
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $where = "WHERE 1=1";
    $params = [];
    
    if ($dari) {
        $where .= " AND DATE(wi.tanggal_in) >= ?";
        $params[] = $dari;
    }
    if ($sampai) {
        $where .= " AND DATE(wi.tanggal_in) <= ?";
        $params[] = $sampai;
    }

    $stmt = $pdo->prepare("
        SELECT 
            t.id_transaksi, t.no_record, t.bruto, t.tara, t.netto,
            k.Sopir, k.Nopol,
            s.Nama_Supplier as supplier,
            m.Material as material,
            c.Customers as customer,
            wi.tanggal_in, wi.jam_in,
            wo.tanggal_out, wo.jam_out
        FROM transaksi t 
        LEFT JOIN waktu_in wi ON t.id_in = wi.id_in 
        LEFT JOIN waktu_out wo ON t.id_out = wo.id_out 
        LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
        LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
        LEFT JOIN material m ON t.id_material = m.id_Material  
        LEFT JOIN customers c ON t.id_customers = c.id_Customers
        $where
        ORDER BY wi.tanggal_in DESC, t.id_transaksi DESC
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary
    $sumStmt = $pdo->prepare("
        SELECT 
            SUM(t.bruto) as total_bruto,
            SUM(t.tara) as total_tara,
            SUM(t.netto) as total_netto,
            COUNT(*) as total_transaksi
        FROM transaksi t LEFT JOIN waktu_in wi ON t.id_in = wi.id_in $where
    ");
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    if ($export === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM for UTF-8
        
        // Headers
        fputcsv($output, ['No', 'No Record', 'Sopir', 'Nopol', 'Supplier', 'Material', 'Customer', 'Tgl IN', 'Jam IN', 'Tgl OUT', 'Jam OUT', 'Bruto', 'Tara', 'Netto']);
        
        $no = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $no++,
                $row['no_record'],
                $row['Sopir'] ?? '',
                $row['Nopol'] ?? '',
                $row['supplier'] ?? '',
                $row['material'] ?? '',
                $row['customer'] ?? '',
                $row['tanggal_in'] ?? '',
                $row['jam_in'] ?? '',
                $row['tanggal_out'] ?? '',
                $row['jam_out'] ?? '',
                number_format($row['bruto'] ?? 0, 2),
                number_format($row['tara'] ?? 0, 2),
                number_format($row['netto'] ?? 0, 2)
            ]);
        }
        exit;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
?>
