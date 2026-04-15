<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="padding:40px;text-align:center;color:red;">⚠️ Login dulu!</div>';
    exit;
}

/* ===========================
   HAPUS DATA
=========================== */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi=?");
    $stmt->execute([$id]);

    header("Location: Transaksi.php?msg=hapus");
    exit;
}

/* ===========================
   FILTER
=========================== */
$tgl    = $_GET['tgl'] ?? '';
$search = $_GET['search'] ?? '';

$where  = " WHERE 1=1 ";
$params = [];

if ($tgl != '') {
    $where .= " AND DATE(wi.tanggal_in)=? ";
    $params[] = $tgl;
}

if ($search != '') {
    $where .= " AND (
        t.no_record LIKE ? OR
        k.Sopir LIKE ? OR
        k.Nopol LIKE ? OR
        s.Nama_Supplier LIKE ? OR
        m.Material LIKE ? OR
        c.Customers LIKE ?
    )";

    for ($i=0;$i<6;$i++) {
        $params[] = "%$search%";
    }
}

/* ===========================
   LOAD DATA
=========================== */
$stmt = $pdo->prepare("
SELECT
t.id_transaksi,
t.no_record,
k.Sopir,
k.Nopol,
s.Nama_Supplier,
m.Material,
c.Customers,
wi.tanggal_in,
wo.tanggal_out,
t.bruto,
t.tara,
t.netto

FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers

$where
ORDER BY t.id_transaksi DESC
");

$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Transaksi</title>

<style>

/* CARD */
.card{
    background:#fff;
    border-radius:4px;
    padding:16px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

/* HEADER */
.header{
    margin-bottom:18px;
}

.header h1{
    font-size:28px;
    color:#111827;
}

.header p{
    color:#6b7280;
    margin-top:5px;
}

/* FILTER */
.controls{
    display:grid;
    grid-template-columns:180px 1fr 130px;
    gap:10px;
    margin-bottom:18px;
}

.controls input,
.controls button{
    padding:11px;
    border-radius:10px;
    border:1px solid #d1d5db;
}

.controls button{
    background:#2563eb;
    color:#fff;
    border:none;
    font-weight:700;
    cursor:pointer;
}

/* TABLE */
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

/* ACTION */
.action{
    display:flex;
    gap:6px;
    justify-content:center;
}

.btn{
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    color:#fff;
    font-size:13px;
    font-weight:700;
    display:inline-block;
}

.btn-edit{
    background:#f59e0b;
}

.btn-delete{
    background:#ef4444;
}

.netto{
    color:#10b981;
    font-weight:700;
}

.alert{
    background:#dcfce7;
    color:#166534;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

@media(max-width:768px){
    .controls{
        grid-template-columns:1fr;
    }

    body{
        padding:12px;
    }
}
</style>
</head>

<body>

<div class="card">

<div class="header">
<h1>📊 Data Transaksi</h1>
<p>Kelola data transaksi kendaraan masuk & keluar</p>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg']=='hapus'): ?>
<div class="alert">✅ Data berhasil dihapus</div>
<?php endif; ?>

<!-- FILTER -->
<form method="GET" class="controls">
<input type="date" name="tgl" value="<?= $tgl ?>">

<input type="text" name="search"
placeholder="Cari No Record / Sopir / Nopol..."
value="<?= htmlspecialchars($search) ?>">

<button type="submit">🔍 Cari</button>
</form>

<!-- TABLE -->
<div class="table-wrap">
<table>

<tr>
<th>ID</th>
<th>No Record</th>
<th>Sopir</th>
<th>Nopol</th>
<th>Supplier</th>
<th>Material</th>
<th>Customer</th>
<th>Masuk</th>
<th>Keluar</th>
<th>Bruto</th>
<th>Tara</th>
<th>Netto</th>
<th>Aksi</th>
</tr>

<?php if(!$data): ?>
<tr>
<td colspan="13">Data tidak ditemukan</td>
</tr>
<?php endif; ?>

<?php foreach($data as $d): ?>
<tr>

<td><?= $d['id_transaksi'] ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['Nama_Supplier'] ?></td>
<td><?= $d['Material'] ?></td>
<td><?= $d['Customers'] ?></td>
<td><?= $d['tanggal_in'] ?></td>
<td><?= $d['tanggal_out'] ?></td>
<td><?= number_format($d['bruto']) ?></td>
<td><?= number_format($d['tara']) ?></td>
<td class="netto"><?= number_format($d['netto']/1000,2) ?> Ton</td>

<td>
<div class="action">

<!-- TOMBOL EDIT FIX -->
<a href="edit.php?id=<?= $d['id_transaksi'] ?>" class="btn btn-edit">
✏ Edit
</a>

<!-- TOMBOL HAPUS FIX -->
<a href="Transaksi.php?hapus=<?= $d['id_transaksi'] ?>"
class="btn btn-delete"
onclick="return confirm('Yakin hapus data ini?')">
🗑 Hapus
</a>

</div>
</td>

</tr>
<?php endforeach; ?>

</table>
</div>

</div>

</body>
</html>