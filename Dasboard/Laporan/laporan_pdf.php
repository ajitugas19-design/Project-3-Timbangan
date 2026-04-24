<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

date_default_timezone_set("Asia/Jakarta");

/* ================= PARAM ================= */
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$search = $_GET['search'] ?? '';
$limit  = $_GET['limit'] ?? 'all';

/* ================= QUERY ================= */
$sql = "
SELECT 
t.no_record,
k.Sopir,
k.Nopol,
s.Nama_Supplier,
m.Material,
c.Customers,
DATE_FORMAT(wi.tanggal_in,'%d-%m-%Y') as tgl_in,
TIME(wi.tanggal_in) as jam_in,
DATE_FORMAT(wo.tanggal_out,'%d-%m-%Y') as tgl_out,
TIME(wo.tanggal_out) as jam_out,
IFNULL(t.bruto,0) as bruto,
IFNULL(t.tara,0) as tara,
IFNULL(t.netto,0) as netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
WHERE 1=1
";

$params = [];

/* ================= FILTER ================= */
if ($dari != '') {
    $sql .= " AND DATE(wi.tanggal_in) >= :dari";
    $params[':dari'] = $dari;
}

if ($sampai != '') {
    $sql .= " AND DATE(wi.tanggal_in) <= :sampai";
    $params[':sampai'] = $sampai;
}

if ($search != '') {
    $sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search OR
        s.Nama_Supplier LIKE :search OR
        m.Material LIKE :search OR
        c.Customers LIKE :search
    )";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY t.id_transaksi DESC";

if ($limit !== 'all') {
    $sql .= " LIMIT " . intval($limit);
}

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= TOTAL ================= */
$total_sql = "
SELECT 
SUM(t.bruto) as total_bruto,
SUM(t.tara) as total_tara,
SUM(t.netto) as total_netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
WHERE 1=1
";

if ($dari != '') $total_sql .= " AND DATE(wi.tanggal_in) >= :dari";
if ($sampai != '') $total_sql .= " AND DATE(wi.tanggal_in) <= :sampai";

if ($search != '') {
    $total_sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search OR
        s.Nama_Supplier LIKE :search OR
        m.Material LIKE :search OR
        c.Customers LIKE :search
    )";
}

$total_stmt = $pdo->prepare($total_sql);
foreach ($params as $k => $v) {
    $total_stmt->bindValue($k, $v);
}
$total_stmt->execute();

$totals = $total_stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_bruto' => 0,
    'total_tara' => 0,
    'total_netto' => 0
];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Laporan PDF</title>

<style>
body{
    font-family:Arial, sans-serif;
    font-size:11px;
    margin:15px;
    color:#000;
}

.header{
    text-align:center;
    margin-bottom:10px;
}

.header h2{
    margin:0;
    font-size:20px;
    letter-spacing:1px;
}

.header p{
    margin:2px 0;
    font-size:11px;
}

.info{
    margin-bottom:12px;
    font-size:11px;
    line-height:1.6;
}

table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

th,td{
    border:1px solid #000;
    padding:4px;
    text-align:center;
    vertical-align:middle;
    word-wrap:break-word;
}

th{
    background:#eaeaea;
    font-size:10px;
}

.left{
    text-align:left;
}

.right{
    text-align:right;
}

.netto{
    font-weight:bold;
}

tfoot td{
    font-weight:bold;
    background:#f3f3f3;
}

.signature{
    width:100%;
    margin-top:40px;
}

.signature td{
    border:none;
    text-align:center;
    padding-top:40px;
    font-size:12px;
}

@media print{
    body{
        margin:10px;
    }
}
</style>
</head>

<body onload="window.print()">

<div class="header">
    <h2>LAPORAN TRANSAKSI TIMBANGAN</h2>
    <p>Periode : <?= $dari ?: '-' ?> s/d <?= $sampai ?: '-' ?></p>
    <p>Filter : <?= $search ?: '-' ?></p>
</div>

<div class="info">
Jumlah Data : <?= count($data) ?>
</div>

<table>
<thead>
<tr>
    <th style="width:35px;">No</th>
    <th>No Record</th>
    <th>Sopir</th>
    <th>Nopol</th>
    <th>Supplier</th>
    <th>Material</th>
    <th>Customer</th>
    <th>Jam In</th>
    <th>Tgl In</th>
    <th>Jam Out</th>
    <th>Tgl Out</th>
    <th>Bruto</th>
    <th>Tara</th>
    <th>Netto</th>
</tr>
</thead>

<tbody>
<?php $no=1; foreach($data as $d): ?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= $d['no_record'] ?></td>
    <td class="left"><?= $d['Sopir'] ?></td>
    <td><?= $d['Nopol'] ?></td>
    <td class="left"><?= $d['Nama_Supplier'] ?></td>
    <td class="left"><?= $d['Material'] ?></td>
    <td class="left"><?= $d['Customers'] ?></td>
    <td><?= $d['jam_in'] ?></td>
    <td><?= $d['tgl_in'] ?></td>
    <td><?= $d['jam_out'] ?></td>
    <td><?= $d['tgl_out'] ?></td>
    <td class="right"><?= number_format($d['bruto']) ?></td>
    <td class="right"><?= number_format($d['tara']) ?></td>
    <td class="right netto"><?= number_format($d['netto']/1000,2) ?> T</td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot>
<tr>
    <td colspan="11" class="right">TOTAL</td>
    <td class="right"><?= number_format($totals['total_bruto']) ?></td>
    <td class="right"><?= number_format($totals['total_tara']) ?></td>
    <td class="right netto"><?= number_format($totals['total_netto']/1000,2) ?> T</td>
</tr>
</tfoot>
</table>

<table class="signature">
<tr>
    <td>Mengetahui,<br><br><br><br>(_______________)</td>
    <td>Admin,<br><br><br><br>(_______________)</td>
</tr>
</table>

</body>
</html>