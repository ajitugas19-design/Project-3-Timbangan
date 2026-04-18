<?php
require_once '../../config.php';

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

/* ================= FILTER TANGGAL ================= */
if ($dari != '') {
    $sql .= " AND DATE(wi.tanggal_in) >= :dari";
    $params[':dari'] = $dari;
}

if ($sampai != '') {
    $sql .= " AND DATE(wi.tanggal_in) <= :sampai";
    $params[':sampai'] = $sampai;
}

/* ================= SEARCH ================= */
if ($search != '') {
    $sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search OR
        s.Nama_Supplier LIKE :search OR
        m.Material LIKE :search OR
        c.Customers LIKE :search OR
        DATE_FORMAT(wi.tanggal_in,'%d-%m-%Y') LIKE :search OR
        DATE_FORMAT(wo.tanggal_out,'%d-%m-%Y') LIKE :search
    )";
    $params[':search'] = "%$search%";
}

/* ================= LIMIT ================= */
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

// Add totals
$total_sql = "SELECT 
    SUM(t.bruto) as total_bruto,
    SUM(t.tara) as total_tara,
    SUM(t.netto) as total_netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
WHERE 1=1";

if ($dari != '') $total_sql .= " AND DATE(wi.tanggal_in) >= :dari";
if ($sampai != '') $total_sql .= " AND DATE(wi.tanggal_in) <= :sampai";
if ($search != '') {
    $total_sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search OR
        s.Nama_Supplier LIKE :search OR
        m.Material LIKE :search OR
        c.Customers LIKE :search OR
        DATE_FORMAT(wi.tanggal_in,'%d-%m-%Y') LIKE :search
    )";
}

$total_stmt = $pdo->prepare($total_sql);
foreach ($params as $k => $v) {
    $total_stmt->bindValue($k, $v);
}
$total_stmt->execute();
$totals = $total_stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_bruto' => 0, 'total_tara' => 0, 'total_netto' => 0];
?>
<div class="totals">
Total Bruto: <?= number_format($totals['total_bruto']) ?> | 
Tara: <?= number_format($totals['total_tara']) ?> | 
Netto: <?= number_format($totals['total_netto']/1000, 2) ?> Ton
</div>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Print Laporan</title>

<style>
body{
    font-family:Arial;
    font-size:11px;
}

h2{
    text-align:center;
    margin-bottom:5px;
}

.info{
    margin-bottom:10px;
    font-size:11px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    border:1px solid #000;
    padding:5px;
    text-align:center;
}

.netto{
    font-weight:bold;
    color:#16a34a;
}

.footer{
    margin-top:30px;
    display:flex;
    justify-content:space-between;
    font-size:12px;
}

@media print{
    body{ margin:0; }
}
</style>
</head>

<body onload="window.print()">

<h2>LAPORAN TRANSAKSI TIMBANGAN</h2>

<div class="info">
Periode: <?= $dari ?: '-' ?> s/d <?= $sampai ?: '-' ?><br>
Filter: <?= $search ?: '-' ?><br>
Jumlah Data: <?= $limit ?>
</div>

<table>
<tr>
<th>No</th>
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

<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['Nama_Supplier'] ?></td>
<td><?= $d['Material'] ?></td>
<td><?= $d['Customers'] ?></td>
<td><?= $d['jam_in'] ?></td>
<td><?= $d['tgl_in'] ?></td>
<td><?= $d['jam_out'] ?></td>
<td><?= $d['tgl_out'] ?></td>
<td><?= number_format($d['bruto']) ?></td>
<td><?= number_format($d['tara']) ?></td>
<td class="netto"><?= number_format($d['netto']/1000,2) ?> Ton</td>
</tr>
<?php endforeach; ?>

</table>

<div class="footer">
<div>
Mengetahui,<br><br><br>
(______________)
</div>

<div>
Admin,<br><br><br>
(______________)
</div>
</div>

</body>
</html>
