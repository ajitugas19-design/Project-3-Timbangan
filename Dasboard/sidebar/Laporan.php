<?php
require_once '../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

date_default_timezone_set("Asia/Jakarta");

/* ===================== PARAM ===================== */
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$search = $_GET['search'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

/* ===================== QUERY DATA ===================== */
$sql = "
SELECT 
t.id_transaksi,
t.no_record,
IFNULL(k.Sopir,'-') as Sopir,
IFNULL(k.Nopol,'-') as Nopol,
DATE_FORMAT(wi.tanggal_in,'%d-%m-%Y') as tgl_in,
DATE_FORMAT(wi.tanggal_in,'%H:%i:%s') as jam_in,

DATE_FORMAT(wo.tanggal_out,'%d-%m-%Y') as tgl_out,
DATE_FORMAT(wo.tanggal_out,'%H:%i:%s') as jam_out,
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
        k.Nopol LIKE :search
    )";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY t.id_transaksi DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===================== TOTAL ===================== */
$total_sql = "
SELECT COUNT(*)
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
WHERE 1=1
";

if ($dari != '') $total_sql .= " AND DATE(wi.tanggal_in) >= :dari";
if ($sampai != '') $total_sql .= " AND DATE(wi.tanggal_in) <= :sampai";

if ($search != '') {
    $total_sql .= " AND (
        t.no_record LIKE :search OR
        k.Sopir LIKE :search OR
        k.Nopol LIKE :search
    )";
}

$total_stmt = $pdo->prepare($total_sql);

foreach ($params as $key => $val) {
    $total_stmt->bindValue($key, $val);
}

$total_stmt->execute();
$total = $total_stmt->fetchColumn();
$totalPages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Transaksi</title>

<style>
  *{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


body.sidebar-close #main{
    margin-left:80px;
    width:calc(100% - 80px);
}

/* ================= WRAPPER ================= */
.wrapper{
    padding:20px;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}

/* ================= HEADER ================= */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:15px;
}

.title h1{
    font-size:22px;
    font-weight:600;
}

.title p{
    font-size:13px;
    color:#6b7280;
}

/* ================= BUTTON ================= */
.btn{
    padding:8px 14px;
    border:none;
    border-radius:8px;
    font-size:13px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}

.btn-dark{background:#111827;color:#fff;}
.btn-primary{background:#2563eb;color:#fff;}
.btn:hover{opacity:.9;}

/* ================= FILTER ================= */
.filter-box{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:15px;
    margin-bottom:15px;
}

.filter-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
    gap:10px;
}

input,select{
    padding:8px;
    border:1px solid #d1d5db;
    border-radius:6px;
    font-size:13px;
}

input:focus,select:focus{
    outline:none;
    border-color:#2563eb;
}

/* ================= SUMMARY ================= */
.summary{
    font-size:13px;
    margin-bottom:10px;
    color:#374151;
}

/* ================= TABLE ================= */
.table-wrap{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}

th{
    background:#111827;
    color:#fff;
    padding:10px;
}

td{
    padding:10px;
    border-bottom:1px solid #e5e7eb;
    text-align:center;
}

tr:hover{
    background:#eef4ff;
}

/* angka */
td:nth-child(7),
td:nth-child(8),
td:nth-child(9){
    font-weight:600;
}

/* ================= PAGINATION ================= */
.pagination{
    margin-top:15px;
    text-align:center;
}

.pagination a{
    display:inline-block;
    padding:6px 10px;
    margin:2px;
    border-radius:6px;
    background:#e5e7eb;
    text-decoration:none;
    font-size:12px;
    color:#111;
}

.pagination a.active{
    background:#2563eb;
    color:#fff;
}

/* ================= RESPONSIVE ================= */
@media(max-width:768px){
    #main{
        margin-left:0;
        width:100%;
    }

    body.sidebar-close #main{
        margin-left:0;
    }

    .wrapper{
        padding:10px;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }
}
</style>
</head>

<body>

<div id="main">

<div class="wrapper">
<div class="card">

<div class="topbar">
    <div class="title">
        <h1>📊 Laporan Transaksi</h1>
        <p>Data transaksi kendaraan masuk & keluar</p>
    </div>

    <div class="export">
    <a class="btn btn-dark" href="../export/export_pdf.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&search=<?= urlencode($search) ?>">🖨 PDF</a>
    <a class="btn btn-dark" href="../export/export_excel.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&search=<?= urlencode($search) ?>">📊 Excel</a>
    <a class="btn btn-dark" href="../export/export_word.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&search=<?= urlencode($search) ?>">📝 Word</a>
</div>
</div>

<div class="filter-box">
<div class="filter-grid">

<input type="date" id="dari" value="<?= $dari ?>">
<input type="date" id="sampai" value="<?= $sampai ?>">
<input type="text" id="search" placeholder="Cari No Record / Sopir / Nopol" value="<?= $search ?>">

<select id="limit">
<option value="10" <?= $limit==10?'selected':'' ?>>10 Data</option>
<option value="25" <?= $limit==25?'selected':'' ?>>25 Data</option>
<option value="50" <?= $limit==50?'selected':'' ?>>50 Data</option>
</select>

<button class="btn btn-primary" onclick="filterData()">🔍 Filter</button>

</div>
</div>

<div class="summary">
Total Data : <?= number_format($total) ?>
</div>

<div class="table-wrap">
<table>
<tr>
<th>No</th>
<th>No Record</th>
<th>Sopir</th>
<th>Nopol</th>
<th>Jam Masuk</th>
<th>Tanggal Masuk</th>
<th>Jam Keluar</th>
<th>Tanggal Keluar</th>
<th>Bruto</th>
<th>Tara</th>
<th>Netto</th>
</tr>

<?php if(empty($data)): ?>
<tr>
<td colspan="11">Tidak ada data</td>
</tr>
<?php else: ?>

<?php $no = $offset + 1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['jam_in'] ?: '-' ?></td>
<td><?= $d['tgl_in'] ?: '-' ?></td>

<td><?= $d['jam_out'] ?: '-' ?></td>
<td><?= $d['tgl_out'] ?: '-' ?></td>
<td><?= number_format($d['bruto']) ?></td>
<td><?= number_format($d['tara']) ?></td>
<td><?= number_format($d['netto']) ?></td>
</tr>
<?php endforeach; ?>

<?php endif; ?>
</table>
</div>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?= $i ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&search=<?= $search ?>&limit=<?= $limit ?>" class="<?= $page==$i?'active':'' ?>">
<?= $i ?>
</a>
<?php endfor; ?>
</div>

</div>
</div>

</div>

<script>
function filterData(){
    let dari = document.getElementById("dari").value;
    let sampai = document.getElementById("sampai").value;
    let search = document.getElementById("search").value;
    let limit = document.getElementById("limit").value;

    window.location =
    `?dari=${dari}&sampai=${sampai}&search=${search}&limit=${limit}`;
}



/* AUTO IKUT SIDEBAR */
document.addEventListener("DOMContentLoaded", function(){

    const btn = document.querySelector(".hamburger-btn,.menu-toggle,#toggleSidebar");

    if(btn){
        btn.addEventListener("click", function(){
            document.body.classList.toggle("sidebar-close");
        });
    }

});
</script>

</body>
</html>