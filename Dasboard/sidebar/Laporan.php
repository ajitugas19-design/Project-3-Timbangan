<?php 
require_once '../../config.php';

// ================= CEK LOGIN =================
if (!isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

date_default_timezone_set("Asia/Jakarta");

// ================= PARAM =================
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$search = $_GET['search'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

// ================= QUERY UTAMA =================
$sql = "
SELECT 
t.id_transaksi,
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

// ================= FILTER AMAN =================
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

// ================= PAGINATION =================
$sql .= " ORDER BY t.id_transaksi DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// bind filter
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

// bind limit offset
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= TOTAL DATA =================
$total_sql = "
SELECT COUNT(*)
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
WHERE 1=1
";

if (!empty($dari)) {
    $total_sql .= " AND (wi.tanggal_in IS NULL OR DATE(wi.tanggal_in) >= :dari)";
}
if (!empty($sampai)) {
    $total_sql .= " AND (wi.tanggal_in IS NULL OR DATE(wi.tanggal_in) <= :sampai)";
}
if (!empty($search)) {
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan</title>

<link rel="stylesheet" href="../css/dashboard.css">

</head>


<body>

<div class="container">
<div class="card">
<div class="filter">
  <a class="btn" href="sidebar/export/export_pdf.php?dari=<?= $dari ?>&sampai=<?= $sampai ?>&search=<?= $search ?>">🖨️ PDF</a>
  <a class="btn" href="sidebar/export/export_excel.php?dari=<?= $dari ?>&sampai=<?= $sampai ?>&search=<?= $search ?>">📊 Excel</a>
  <a class="btn" href="sidebar/export/export_word.php?dari=<?= $dari ?>&sampai=<?= $sampai ?>&search=<?= $search ?>">📝 Word</a>
</div>

<h2>📊 Laporan Transaksi</h2>

<div class="filter">
<input type="date" id="dari" value="<?= $dari ?>">
<input type="date" id="sampai" value="<?= $sampai ?>">
<input type="text" id="search" placeholder="Cari..." value="<?= $search ?>">

<select id="limit">
<option value="10" <?= $limit==10?'selected':'' ?>>10</option>
<option value="25" <?= $limit==25?'selected':'' ?>>25</option>
<option value="50" <?= $limit==50?'selected':'' ?>>50</option>
</select>

<button class="btn" onclick="filterData()">Filter</button>
</div>

<table>
<tr>
<th>No</th>
<th>No Record</th>
<th>Sopir</th>
<th>Nopol</th>
<th>Masuk</th>
<th>Keluar</th>
<th>Bruto</th>
<th>Tara</th>
<th>Netto</th>
</tr>

<?php if(empty($data)): ?>
<tr>
<td colspan="9" style="text-align:center;">Data tidak ditemukan</td>
</tr>
<?php else: ?>
<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['tanggal_in'] ?></td>
<td><?= $d['tanggal_out'] ?></td>
<td><?= number_format($d['bruto']) ?></td>
<td><?= number_format($d['tara']) ?></td>
<td><?= number_format($d['netto']) ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?= $i ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&search=<?= $search ?>&limit=<?= $limit ?>" class="<?= $i==$page?'active':'' ?>">
<?= $i ?>
</a>
<?php endfor; ?>
</div>

</div>
</div>

<script>
function filterData(){
  const dari = document.getElementById('dari').value;
  const sampai = document.getElementById('sampai').value;
  const search = document.getElementById('search').value;
  const limit = document.getElementById('limit').value;

  window.location = `?dari=${dari}&sampai=${sampai}&search=${search}&limit=${limit}`;
}
</script>

</body>
</html>