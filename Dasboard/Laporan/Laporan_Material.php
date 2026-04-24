<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

date_default_timezone_set("Asia/Jakarta");

/* ================= AMBIL DATA ================= */
$data = $pdo->query("SELECT * FROM material ORDER BY id_Material DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Laporan Material</title>

<style>
body{
    font-family: Arial;
    font-size: 12px;
    margin: 20px;
    color:#000;
}

h2{
    text-align:center;
    margin-bottom:10px;
}

.info{
    text-align:center;
    margin-bottom:15px;
    font-size:11px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    border:1px solid #000;
    padding:6px;
    text-align:center;
}

th{
    font-weight:bold;
}

.left{
    text-align:left;
}

@media print{
    .no-print{
        display:none;
    }
}
</style>
</head>

<body onload="window.print()">

<h2>LAPORAN DATA MATERIAL</h2>

<div class="info">
Tanggal Cetak: <?= date('d-m-Y H:i:s') ?>
</div>

<table>
<thead>
<tr>
    <th>No</th>
    <th>Kode</th>
    <th>Nama Material</th>
</tr>
</thead>

<tbody>
<?php $no=1; foreach($data as $d): ?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($d['Kode']) ?></td>
    <td class="left"><?= htmlspecialchars($d['Material']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>

</table>

</body>
</html>