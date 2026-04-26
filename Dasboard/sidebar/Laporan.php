<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}


date_default_timezone_set("Asia/Jakarta");

/* ================= PARAM ================= */
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$search = $_GET['search'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;


/* ================= QUERY ================= */
$sql = "
SELECT 
t.id_transaksi,
t.no_record,
IFNULL(k.Sopir,'-') as Sopir,
IFNULL(k.Nopol,'-') as Nopol,
IFNULL(s.Nama_Supplier,'-') as Nama_Supplier,
IFNULL(m.Material,'-') as Material,
IFNULL(c.Customers,'-') as Customers,
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
        c.Customers LIKE :search OR
        DATE_FORMAT(wi.tanggal_in,'%d-%m-%Y') LIKE :search
    )";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY t.id_transaksi DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['excel']) && $_GET['excel'] == 1) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/* ================= TOTAL ================= */
$total_sql = "SELECT COUNT(*) FROM transaksi t 
LEFT JOIN waktu_in wi ON t.id_in=wi.id_in
LEFT JOIN waktu_out wo ON t.id_out=wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan=k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier=s.id_Supplier
LEFT JOIN material m ON t.id_material=m.id_Material
LEFT JOIN customers c ON t.id_customers=c.id_Customers
WHERE 1=1";

if ($dari != '') {
    $total_sql .= " AND DATE(wi.tanggal_in) >= :dari";
}
if ($sampai != '') {
    $total_sql .= " AND DATE(wi.tanggal_in) <= :sampai";
}
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
    $total_params[':search'] = "%$search%";
}

$total_stmt = $pdo->prepare($total_sql);
foreach ($params as $k => $v) {
    $total_stmt->bindValue($k, $v);
}
$total_stmt->execute();
$total = $total_stmt->fetchColumn();
$totalPages = ceil($total / $limit);



/* ================= SUMMARY ================= */
$sum_sql = "SELECT 
SUM(t.bruto) as b,
SUM(t.tara) as t,
SUM(t.netto) as n 
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
WHERE 1=1";

if ($dari != '') {
    $sum_sql .= " AND DATE(wi.tanggal_in) >= :dari";
    $sum_params[':dari'] = $dari;
}
if ($sampai != '') {
    $sum_sql .= " AND DATE(wi.tanggal_in) <= :sampai";
    $sum_params[':sampai'] = $sampai;
}
if ($search != '') {
    $sum_sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search OR
        s.Nama_Supplier LIKE :search OR
        m.Material LIKE :search OR
        c.Customers LIKE :search OR
        DATE_FORMAT(wi.tanggal_in,'%d-%m-%Y') LIKE :search
    )";
    $sum_params[':search'] = "%$search%";
}

$sum_stmt = $pdo->prepare($sum_sql);
foreach ($sum_params ?? [] as $k => $v) {
    $sum_stmt->bindValue($k, $v);
}
$sum_stmt->execute();
$totals = $sum_stmt->fetch(PDO::FETCH_ASSOC) ?: ['b' => 0, 't' => 0, 'n' => 0];


?>

<!DOCTYPE html>
<html>
<head>
<title>Laporan</title>

<style>
/* Scoped: Laporan */
.page-laporan{
    width:100%;
    max-width:100%;
    padding:15px;
    background:#eef2f7;
    font-size:13px;
    box-sizing:border-box;
}
.page-laporan .card{
    width:100%;
    background:#fff;
    border-radius:14px;
    padding:18px;
    box-shadow:0 6px 20px rgba(0,0,0,.08);
}
.page-laporan h3{
    margin-bottom:12px;
    font-size:16px;
    font-weight:600;
}
.page-laporan input,
.page-laporan select{
    padding:7px 10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:12px;
    outline:none;
    box-sizing:border-box;
}
.page-laporan input:focus,
.page-laporan select:focus{
    border-color:#2563eb;
}
.page-laporan table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
}
.page-laporan th{
    background:#111827;
    color:#fff;
    padding:8px;
    text-align:center;
}
.page-laporan td{
    padding:7px;
    border-bottom:1px solid #ddd;
    text-align:center;
}
.page-laporan tr:hover{
    background:#f1f5f9;
}
.page-laporan .summary{
    margin:10px 0;
    font-weight:600;
}
@media (max-width:768px){
    .page-laporan table{font-size:11px;}
    .page-laporan th, .page-laporan td{padding:6px;}
}
@media print{
    .page-laporan .filter-bar,
    .page-laporan .pagination,
    .page-laporon .btn{display:none;}
    .page-laporan{padding:0;background:#fff;}
    .page-laporan .card{box-shadow:none;padding:0;}
}
/* BUTTON STYLE */
.page-laporan .btn{
    padding:7px 12px;
    border:none;
    border-radius:8px;
    font-size:12px;
    cursor:pointer;
    transition:0.2s;
}

/* warna tombol */
.page-laporan .btn.primary{ background:#2563eb; color:#fff; }
.page-laporan .btn.dark{ background:#374151; color:#fff; }
.page-laporan .btn.success{ background:#16a34a; color:#fff; }
.page-laporan .btn:hover{ opacity:0.85; }
</style>
</head>

<body>

<div class="page-laporan">
<div class="card">

<h3>📊 Laporan Transaksi</h3>

<div class="filter-bar">

<div class="filter-group">
<input type="date" id="dari" value="<?= $dari ?>">
<input type="date" id="sampai" value="<?= $sampai ?>">
</div>

<div class="filter-group">
<input type="text" id="search" placeholder="🔍 Cari No Record/Sopir/Supplier..." value="<?= $search ?>" oninput="debounceSearch()">

</div>

<div class="filter-group">
<select id="limit">
<option value="10">10</option>
<option value="25">25</option>
<option value="50">50</option>
</select>

<button class="btn primary" onclick="loadTable()">Cari</button>
<button class="btn dark" onclick="resetFilter()">Reset</button>
</div>

<div class="filter-group">
<select id="limit_export_all">
    <option value="10">10 Data</option>
    <option value="25">25 Data</option>
    <option value="50">50 Data</option>
    <option value="all">Semua</option>
</select>


<button class="btn primary" onclick="exportPDF()">📄 PDF</button>
<button class="btn dark" onclick="exportWord()">📝 Word</button>
<button class="btn success" onclick="exportExcel()">📊 Excel</button>
<button class="btn dark" onclick="printData()">🖨️ Print</button>
</div>

</div>

<p class="summary">
Total: <?= $total ?> | 
Bruto: <?= number_format($totals['b']) ?> | 
Tara: <?= number_format($totals['t']) ?> | 
Netto: <?= number_format($totals['n']/1000,2) ?> Ton
</p>

<table>
<thead>
<tr>
<th>No</th><th>No Record</th><th>Sopir</th><th>Nopol</th>
<th>Supplier</th><th>Material</th><th>Customer</th>
<th>Jam In</th><th>Tgl In</th>
<th>Jam Out</th><th>Tgl Out</th>
<th>Bruto</th><th>Tara</th><th>Netto</th>
</tr>
</thead>

<tbody id="tbody">
<?php $no=$offset+1; foreach($data as $d): ?>
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
<td><?= number_format($d['netto']/1000,2) ?> Ton</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="javascript:void(0)" onclick="loadTable(<?= $i ?>)" class="<?= $page==$i?'active':'' ?>">
<?= $i ?>
</a>
<?php endfor; ?>
</div>

</div>
</div>

<script>
let searchTimeout;

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadTable(1), 300);
}

function loadTable(page=1){
let d=document.getElementById('dari').value;
let s=document.getElementById('sampai').value;
let q=document.getElementById('search').value;
let l = document.getElementById('limit_export_all').value;

fetch(`sidebar/Laporan.php?page=${page}&dari=${d}&sampai=${s}&search=${q}&limit=${l}`)
.then(res => {
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.text();
})
.then(html => {
    let doc = new DOMParser().parseFromString(html, 'text/html');
    document.querySelector('#tbody').innerHTML = doc.querySelector('#tbody').innerHTML;
    document.querySelector('.summary').innerHTML = doc.querySelector('.summary').innerHTML;
    document.querySelector('.pagination').innerHTML = doc.querySelector('.pagination').innerHTML;
})
.catch(err => console.error('Load error:', err));

}


function resetFilter(){
document.getElementById('dari').value='';
document.getElementById('sampai').value='';
document.getElementById('search').value='';
loadTable(1);
}

/* ===============================
   GANTI SEMUA FUNCTION EXPORT
   Penyebab NOT FOUND:
   karena path ../Laporan/ dipanggil dari URL yang sudah berada di Dashboard
   Jadi harus absolute dari root project
================================= */

function printData(){
let d = document.getElementById('dari').value;
let s = document.getElementById('sampai').value;
let q = document.getElementById('search').value;
let l = document.getElementById('limit_export_all').value;

let iframe = document.createElement('iframe');
iframe.style.display = 'none';

/* FIX */
iframe.src = `/Project_3/Dasboard/Laporan/Struktur.php?dari=${d}&sampai=${s}&search=${q}&limit=${l}`;

document.body.appendChild(iframe);

iframe.onload = function(){
    iframe.contentWindow.print();
};
}


function exportPDF(){
let d = document.getElementById('dari').value;
let s = document.getElementById('sampai').value;
let q = document.getElementById('search').value;
let l = document.getElementById('limit_export_all').value;

/* FIX */
window.open(`/Project_3/Dasboard/Laporan/laporan_pdf.php?dari=${d}&sampai=${s}&search=${q}&limit=${l}`, '_blank');
}


function exportWord(){
let d = document.getElementById('dari').value;
let s = document.getElementById('sampai').value;
let q = document.getElementById('search').value;
let l = document.getElementById('limit_export_all').value;

/* FIX */
window.location.href = `/Project_3/Dasboard/Laporan/laporan_word.php?dari=${d}&sampai=${s}&search=${q}&limit=${l}`;
}


function exportExcel(){
let d = document.getElementById('dari').value;
let s = document.getElementById('sampai').value;
let q = document.getElementById('search').value;

fetch(`/Project_3/Dasboard/sidebar/Laporan.php?dari=${d}&sampai=${s}&search=${q}&limit=100&excel=1`)
.then(res => res.json())
.then(rows => {

    if(typeof XLSX === 'undefined'){
        let script = document.createElement('script');
        script.src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.onload = ()=>buatExcel(rows);
        document.head.appendChild(script);
    }else{
        buatExcel(rows);
    }

});
}

function buatExcel(rows){
let wb = XLSX.utils.book_new();
let ws = XLSX.utils.json_to_sheet(rows);
XLSX.utils.book_append_sheet(wb, ws, 'Laporan');
XLSX.writeFile(wb, 'laporan.xlsx');
}

</script>

</body>
</html>