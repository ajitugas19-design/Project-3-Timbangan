<?php
require_once '../../../config.php';

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=laporan.xls");

// ================= PARAM =================
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$search = $_GET['search'] ?? '';

// ================= QUERY FULL FILTERED =================
$sql = "
SELECT 
t.no_record,
IFNULL(k.Sopir,'-') as Sopir,
IFNULL(k.Nopol,'-') as Nopol,
IFNULL(wi.tanggal_in,'-') as tanggal_in,
IFNULL(wo.tanggal_out,'-') as tanggal_out,
IFNULL(t.bruto,0) as bruto,
IFNULL(t.tara,0) as tara,
IFNULL(t.netto,0) as netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
WHERE 1=1
";

$params = [];

if (!empty($dari)) {
    $sql .= " AND (wi.tanggal_in IS NULL OR DATE(wi.tanggal_in) >= :dari)";
    $params[':dari'] = $dari;
}

if (!empty($sampai)) {
    $sql .= " AND (wi.tanggal_in IS NULL OR DATE(wi.tanggal_in) <= :sampai)";
    $params[':sampai'] = $sampai;
}

if (!empty($search)) {
    $sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search
    )";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY t.id_transaksi DESC";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filter_text = 'Semua Data';
if (!empty($dari) || !empty($sampai) || !empty($search)) {
    $filter_text = 'Filtered';
    if (!empty($dari)) $filter_text .= ' dari ' . $dari;
    if (!empty($sampai)) $filter_text .= ' sampai ' . $sampai;
    if (!empty($search)) $filter_text .= ' cari "' . $search . '"';
}

echo "<h2>Laporan Transaksi - $filter_text</h2>";
echo "<table border='1'>";
echo "<tr><th>No</th><th>No Record</th><th>Sopir</th><th>Nopol</th><th>Masuk</th><th>Keluar</th><th>Bruto</th><th>Tara</th><th>Netto</th></tr>";

$no=1;
foreach($data as $d){
echo "<tr>
<td>$no</td>
<td>" . htmlspecialchars($d['no_record']) . "</td>
<td>" . htmlspecialchars($d['Sopir']) . "</td>
<td>" . htmlspecialchars($d['Nopol']) . "</td>
<td>" . htmlspecialchars($d['tanggal_in']) . "</td>
<td>" . htmlspecialchars($d['tanggal_out']) . "</td>
<td>" . number_format($d['bruto']) . "</td>
<td>" . number_format($d['tara']) . "</td>
<td>" . number_format($d['netto']) . "</td>
</tr>";
$no++;
}

echo "</table>";
