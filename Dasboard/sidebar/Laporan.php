<?php
session_start();
require_once '../../config.php';
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-30 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Build query
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

// Data
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

// Excel export (CSV with XLS header)
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $dari . '_to_' . $sampai . '.xls"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM
    
    echo "<table><tr><th>No</th><th>No Record</th><th>Sopir</th><th>Nopol</th><th>Supplier</th><th>Material</th><th>Customer</th><th>Tgl IN</th><th>Jam IN</th><th>Tgl OUT</th><th>Jam OUT</th><th>Bruto</th><th>Tara</th><th>Netto</th></tr>";
    
    $no = 1;
    foreach ($data as $row) {
        echo "<tr><td>" . $no++ . "</td><td>" . htmlspecialchars($row['no_record']) . "</td><td>" . ($row['Sopir'] ?? '') . "</td><td>" . ($row['Nopol'] ?? '') . "</td><td>" . ($row['supplier'] ?? '') . "</td><td>" . ($row['material'] ?? '') . "</td><td>" . ($row['customer'] ?? '') . "</td><td>" . ($row['tanggal_in'] ?? '') . "</td><td>" . ($row['jam_in'] ?? '') . "</td><td>" . ($row['tanggal_out'] ?? '') . "</td><td>" . ($row['jam_out'] ?? '') . "</td><td>" . number_format($row['bruto'] ?? 0, 2) . "</td><td>" . number_format($row['tara'] ?? 0, 2) . "</td><td>" . number_format($row['netto'] ?? 0, 2) . "</td></tr>";
    }
    echo "</table>";
    exit;
}

// Word export (HTML doc)
if (isset($_GET['export']) && $_GET['export'] == 'word') {
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $dari . '_to_' . $sampai . '.doc"');
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Laporan</title></head><body>";
    echo "<h2>Laporan Transaksi Penimbangan ($dari s/d $sampai)</h2>";
    echo "<table border='1' cellpadding='8'><tr><th>No</th><th>No Record</th><th>Sopir</th><th>Nopol</th><th>Supplier</th><th>Material</th><th>Customer</th><th>Tgl IN</th><th>Jam IN</th><th>Tgl OUT</th><th>Jam OUT</th><th>Bruto</th><th>Tara</th><th>Netto</th></tr>";
    
    $no = 1;
    foreach ($data as $row) {
        echo "<tr><td>" . $no++ . "</td><td>" . htmlspecialchars($row['no_record']) . "</td><td>" . ($row['Sopir'] ?? '') . "</td><td>" . ($row['Nopol'] ?? '') . "</td><td>" . ($row['supplier'] ?? '') . "</td><td>" . ($row['material'] ?? '') . "</td><td>" . ($row['customer'] ?? '') . "</td><td>" . ($row['tanggal_in'] ?? '') . "</td><td>" . ($row['jam_in'] ?? '') . "</td><td>" . ($row['tanggal_out'] ?? '') . "</td><td>" . ($row['jam_out'] ?? '') . "</td><td>" . number_format($row['bruto'] ?? 0, 2) . "</td><td>" . number_format($row['tara'] ?? 0, 2) . "</td><td>" . number_format($row['netto'] ?? 0, 2) . "</td></tr>";
    }
    echo "</table></body></html>";
    exit;
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $dari . '_to_' . $sampai . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM
    
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Penimbangan</title>
<style>
body{ font-family:Segoe UI; background:linear-gradient(135deg,#0f172a,#1e293b); margin:0; padding:20px; color:#e5e7eb; }
.container{ max-width:1400px; margin:auto; background:#1e293b; border-radius:12px; overflow:hidden; }
.header{ background:linear-gradient(135deg,#0ea5e9,#2563eb); padding:20px; text-align:center; color:white; }
.filter{ padding:20px; background:#0f172a; display:flex; gap:10px; flex-wrap:wrap; }
input{ padding:10px; border-radius:6px; border:1px solid #334155; background:#1e293b; color:white; }
button{ background:#22c55e; border:none; padding:10px 20px; border-radius:6px; color:white; cursor:pointer; }
.export-btn { background: #8b5cf6; margin-left:10px; }
.export-btn:hover { background: #7c3aed; }
table{ width:100%; border-collapse:collapse; }
th{ background:#0f172a; color:#38bdf8; padding:12px; }
td{ padding:10px; text-align:center; border-bottom:1px solid #334155; }
tr:hover td{ background:#334155; }
.summary{ display:grid; grid-template-columns:repeat(4,1fr); gap:10px; padding:20px; }
.card{ background:#064e3b; padding:15px; text-align:center; border-radius:10px; }
.card h2{margin:0;}
@media (max-width:768px) { .summary { grid-template-columns:repeat(2,1fr); } .filter { flex-direction:column; } }
@media print { .filter { display:none; } }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Laporan Transaksi Penimbangan (<?= $dari ?> s/d <?= $sampai ?>)</h2>
    </div>

    <div class="filter">
        <input type="date" id="dari" value="<?= htmlspecialchars($dari) ?>">
        <input type="date" id="sampai" value="<?= htmlspecialchars($sampai) ?>">
        <a href="?dari=" + document.getElementById("dari").value + "&sampai=" + document.getElementById("sampai").value class="btn" onclick="generateReport()">🔄 Generate</a>
        <a href="?export=csv&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>" class="export-btn">📥 CSV</a>
        <a href="?export=excel&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>" class="export-btn" style="background: #eab308;">📊 Excel</a>
        <button onclick="exportPDF()">📄 PDF</button>
        <a href="?export=word&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>" class="export-btn" style="background: #6366f1;">📝 Word</a>
        <button onclick="printReport()">🖨️ Print</button>
    </div>

    <div class="summary">
        <div class="card"><h2><?= $summary['total_transaksi'] ?></h2>Total Transaksi</div>
        <div class="card"><h2><?= number_format($summary['total_bruto'] ?? 0, 2) ?></h2>Bruto</div>
        <div class="card"><h2><?= number_format($summary['total_tara'] ?? 0, 2) ?></h2>Tara</div>
        <div class="card"><h2><?= number_format($summary['total_netto'] ?? 0, 2) ?></h2>Netto</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Record</th>
                <th>Sopir</th>
                <th>Nopol</th>
                <th>Supplier</th>
                <th>Material</th>
                <th>Customer</th>
                <th>Tanggal IN</th>
                <th>Jam IN</th>
                <th>Tanggal OUT</th>
                <th>Jam OUT</th>
                <th>Bruto</th>
                <th>Tara</th>
                <th>Netto</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['no_record']) ?></td>
                <td><?= htmlspecialchars($row['Sopir'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['Nopol'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['supplier'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['material'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['customer'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['tanggal_in'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['jam_in'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['tanggal_out'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['jam_out'] ?? '-') ?></td>
                <td><?= number_format($row['bruto'] ?? 0, 2) ?></td>
                <td><?= number_format($row['tara'] ?? 0, 2) ?></td>
                <td style="color:#22c55e;font-weight:bold;"><?= number_format($row['netto'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?>
            <tr><td colspan="14" style="text-align:center;padding:30px;color:#94a3b8;">No data found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function generateReport() {
    const dari = document.getElementById('dari').value;
    const sampai = document.getElementById('sampai').value;
    if (dari && sampai) {
        window.location.href = `?dari=${dari}&sampai=${sampai}`;
    }
}

function printReport() {
    window.print();
}
</script>
</body>
</html>
