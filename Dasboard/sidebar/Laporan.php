<?php
session_start();
require_once '../../config.php';

// ✅ FIX LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../Index.php');
    exit;
}

// ================= FILTER =================
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-7 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// ================= QUERY FIX =================
$where = "WHERE 1=1";
$params = [];

if ($dari) {
    $where .= " AND wi.tanggal_in >= ?";
    $params[] = $dari;
}
if ($sampai) {
    $where .= " AND wi.tanggal_in <= ?";
    $params[] = $sampai;
}

// ================= DATA =================
$stmt = $pdo->prepare("
SELECT 
    t.no_record,
    k.Sopir,
    k.Nopol,
    s.Nama_Supplier,
    m.material as material,
    c.Customers,
    wi.tanggal_in,
    wi.jam_in,
    wo.tanggal_out,
    wo.jam_out,
    t.bruto,
    t.tara,
    t.netto
FROM transaksi t
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
$where
ORDER BY t.id_transaksi DESC
");

$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= SUMMARY =================
$summary = [
    'total' => count($data),
    'bruto' => array_sum(array_column($data, 'bruto')),
    'tara' => array_sum(array_column($data, 'tara')),
    'netto' => array_sum(array_column($data, 'netto')),
];
?>

<!DOCTYPE html>
<html>
<head>
<title>Laporan Penimbangan</title>

<style>
body{
font-family:Segoe UI;
background:#0f172a;
color:white;
padding:20px;
}
.container{
max-width:1300px;margin:auto;
background:#1e293b;
border-radius:12px;
overflow:hidden;
box-shadow:0 10px 30px rgba(0,0,0,0.4);
}

.header{
padding:20px;
background:linear-gradient(135deg,#2563eb,#06b6d4);
text-align:center;
}

.filter{
padding:15px;
display:flex;
gap:10px;
flex-wrap:wrap;
background:#020617;
}

input{
padding:10px;
border-radius:8px;
border:none;
}

button{
padding:10px 20px;
border:none;
border-radius:8px;
background:#22c55e;
color:white;
cursor:pointer;
}

.summary{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:10px;
padding:15px;
}

.card{
background:#065f46;
padding:15px;
border-radius:10px;
text-align:center;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#020617;
padding:12px;
color:#38bdf8;
}

td{
padding:10px;
border-bottom:1px solid #334155;
text-align:center;
}

tr:hover td{
background:#334155;
}

@media(max-width:768px){
.summary{grid-template-columns:repeat(2,1fr);}
}
</style>

</head>
<body>

<div class="container">

<div class="header">
<h2>Laporan Penimbangan</h2>
</div>

<div class="filter">
<input type="date" id="dari" value="<?= $dari ?>">
<input type="date" id="sampai" value="<?= $sampai ?>">
<button onclick="filter()">Tampilkan</button>
</div>

<div class="summary">
<div class="card">Total<br><b><?= $summary['total'] ?></b></div>
<div class="card">Bruto<br><b><?= number_format($summary['bruto'],0) ?></b></div>
<div class="card">Tara<br><b><?= number_format($summary['tara'],0) ?></b></div>
<div class="card">Netto<br><b><?= number_format($summary['netto'],0) ?></b></div>
</div>

<table>
<tr>
<th>No</th>
<th>Record</th>
<th>Sopir</th>
<th>Nopol</th>
<th>Supplier</th>
<th>Material</th>
<th>Customer</th>
<th>Tanggal</th>
<th>Netto</th>
</tr>

<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['Nama_Supplier'] ?></td>
<td><?= $d['material'] ?></td>
<td><?= $d['Customers'] ?></td>
<td><?= $d['tanggal_in'] ?></td>
<td style="color:#22c55e;font-weight:bold">
<?= number_format($d['netto'],0) ?>
</td>
</tr>
<?php endforeach; ?>

<?php if(empty($data)): ?>
<tr><td colspan="9">Tidak ada data</td></tr>
<?php endif; ?>

</table>

</div>

<script>
function filter(){
let dari=document.getElementById('dari').value;
let sampai=document.getElementById('sampai').value;

window.location=`?dari=${dari}&sampai=${sampai}`;
}
</script>

</body>
</html>