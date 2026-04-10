<?php 
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;margin:50px;font-size:18px;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}

date_default_timezone_set("Asia/Jakarta");

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = "";

// ================= DELETE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $message = '❌ CSRF token invalid!';
    } else {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi=?")->execute([$id]);
            $message = '✅ Data berhasil dihapus!';
        }
    }
}

// ================= PARAM =================
$tgl = $_GET['tgl'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

// ================= WHERE =================
$where = "WHERE 1=1";
$params = [];

if ($tgl) {
    $where .= " AND DATE(wi.tanggal_in)=?";
    $params[] = $tgl;
}

if ($search) {
    $where .= " AND (
        t.no_record LIKE ? OR 
        k.Sopir LIKE ? OR 
        k.Nopol LIKE ? OR
        s.Nama_Supplier LIKE ? OR
        m.Material LIKE ? OR
        c.Customers LIKE ?
    )";
    for ($i=0;$i<6;$i++) $params[]="%$search%";
}

// ================= DATA =================
$stmt = $pdo->prepare("
SELECT 
t.id_transaksi,t.no_record,
k.Sopir,k.Nopol,
s.Nama_Supplier,
m.Material,
c.Customers,
wi.tanggal_in,wo.tanggal_out,
t.bruto,t.tara,t.netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in=wi.id_in
LEFT JOIN waktu_out wo ON t.id_out=wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan=k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier=s.id_Supplier
LEFT JOIN material m ON t.id_material=m.id_Material
LEFT JOIN customers c ON t.id_customers=c.id_Customers
$where
ORDER BY t.id_transaksi DESC
LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= TOTAL =================
$total = $pdo->prepare("
SELECT COUNT(*) FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in=wi.id_in
LEFT JOIN kendaraan k ON t.id_kendaraan=k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier=s.id_Supplier
LEFT JOIN material m ON t.id_material=m.id_Material
LEFT JOIN customers c ON t.id_customers=c.id_Customers
$where
");
$total->execute($params);
$total = $total->fetchColumn();

$totalPages = max(1, ceil($total / $limit));
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Transaksi</title>

<style>
body{
  font-family:Segoe UI;
  background:#f1f5f9;
  margin:0;
}

.container{
  max-width:1300px;
  margin:20px auto;
  background:white;
  border-radius:12px;
  box-shadow:0 5px 20px rgba(0,0,0,0.1);
  overflow:hidden;
}

.header{
  background:#1f2937;
  color:white;
  padding:20px;
  text-align:center;
  font-size:20px;
  font-weight:bold;
}

.controls{
  padding:15px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  background:#f9fafb;
}

input,select,button{
  padding:10px;
  border-radius:8px;
  border:1px solid #ccc;
}

.btn{
  background:#3b82f6;
  color:white;
  border:none;
  cursor:pointer;
}

.btn-reset{
  background:#6b7280;
}

.table-container{
  width:100%;
  overflow-x:auto;
  padding:10px;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:1100px;
}

th, td{
  padding:10px;
  text-align:center;
  border-bottom:1px solid #e5e7eb;
  white-space:nowrap;
}

th{
  background:#1f2937;
  color:white;
  position:sticky;
  top:0;
}

tr:hover{
  background:#f9fafb;
}

td:nth-child(10),
td:nth-child(11),
td:nth-child(12){
  text-align:right;
}

.netto{
  color:#10b981;
  font-weight:bold;
}

.aksi a, .aksi button{
  display:inline-block;
  margin:2px;
}

.btn-edit{
  background:#f59e0b;
  color:white;
  padding:5px 8px;
  border-radius:5px;
  text-decoration:none;
}

.btn-danger{
  background:#ef4444;
  color:white;
  border:none;
  padding:5px 8px;
  border-radius:5px;
}

.pagination{
  padding:15px;
  text-align:center;
}

.pagination a{
  padding:6px 10px;
  background:#ddd;
  margin:2px;
  text-decoration:none;
}

.pagination .active{
  background:#3b82f6;
  color:white;
}

.message{
  padding:10px;
  text-align:center;
  color:green;
}

@media(max-width:768px){
  .controls{
    flex-direction:column;
  }
}
</style>
</head>

<body>

<div class="container">

<div class="header">📊 Data Transaksi</div>

<?php if($message): ?>
<div class="message"><?= $message ?></div>
<?php endif; ?>

<form method="GET" class="controls">
<input type="date" name="tgl" value="<?= $tgl ?>">
<input type="text" name="search" placeholder="Cari data..." value="<?= $search ?>">
<select name="limit">
<option value="10">10</option>
<option value="25">25</option>
<option value="50">50</option>
</select>
<button class="btn">Cari</button>
<a href="?" class="btn btn-reset">Reset</a>
</form>

<div class="table-container">
<table>
<tr>
<th>ID</th><th>No</th><th>Sopir</th><th>Nopol</th>
<th>Supplier</th><th>Material</th><th>Customer</th>
<th>Masuk</th><th>Keluar</th>
<th>Bruto</th><th>Tara</th><th>Netto</th><th>Aksi</th>
</tr>

<?php if(empty($data)): ?>
<tr><td colspan="13">Tidak ada data</td></tr>
<?php else: foreach($data as $d): ?>
<tr>
<td><?= $d['id_transaksi'] ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['Nama_Supplier'] ?></td>
<td><?= $d['Material'] ?></td>
<td><?= $d['Customers'] ?></td>

<td><?= $d['tanggal_in'] ? date('d/m/Y H:i', strtotime($d['tanggal_in'])) : '-' ?></td>
<td><?= $d['tanggal_out'] ? date('d/m/Y H:i', strtotime($d['tanggal_out'])) : '-' ?></td>

<td><?= number_format($d['bruto']) ?></td>
<td><?= number_format($d['tara']) ?></td>
<td class="netto"><?= number_format($d['netto']/1000,2) ?> Ton</td>

<td class="aksi">
<a href="edit.php?id=<?= $d['id_transaksi'] ?>" class="btn-edit">✏️</a>

<form method="POST" style="display:inline;">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
<input type="hidden" name="id" value="<?= $d['id_transaksi'] ?>">
<input type="hidden" name="action" value="delete">
<button class="btn-danger" onclick="return confirm('Hapus data ini?')">🗑️</button>
</form>
</td>

</tr>
<?php endforeach; endif; ?>

</table>
</div>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?= $i ?>&search=<?= $search ?>&tgl=<?= $tgl ?>" 
class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</div>
</body>
</html>