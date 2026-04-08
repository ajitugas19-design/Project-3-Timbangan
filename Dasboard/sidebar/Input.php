<?php 
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;margin-top:50px;">Silakan login terlebih dahulu!</div>';
    exit;
}

// ================== LOAD DATA ==================
$kendaraan = $pdo->query("SELECT id_Kendaraan, Nopol, Sopir FROM kendaraan")->fetchAll(PDO::FETCH_ASSOC);
$customers = $pdo->query("SELECT id_Customers, Customers FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $pdo->query("SELECT id_Supplier, Nama_Supplier FROM supplier")->fetchAll(PDO::FETCH_ASSOC);
$materials = $pdo->query("SELECT id_Material as id, Material as nama FROM material")->fetchAll(PDO::FETCH_ASSOC);



// ================== AUTO NO RECORD ==================
$max = $pdo->query("SELECT MAX(id_transaksi) as max FROM transaksi")->fetch();
$no_record = 'TRX' . str_pad(($max['max'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);

// ================== INSERT ==================
if (isset($_POST['simpan'])) {

    $stmt = $pdo->prepare("INSERT INTO transaksi 
        (no_record, id_kendaraan, id_supplier, id_material, id_customers, bruto, tara, netto) 
        VALUES (?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $_POST['no_record'],
        $_POST['id_kendaraan'],
        $_POST['id_supplier'],
        $_POST['id_material'],
        $_POST['id_customers'],
        $_POST['bruto'],
        $_POST['tara'],
        $_POST['netto']
    ]);

    echo "<script>alert('Data berhasil disimpan');location='';</script>";
}

// ================== DELETE ==================
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi=?")->execute([$_GET['hapus']]);
    echo "<script>alert('Data dihapus');location='';</script>";
}

// ================== LOAD TABLE ==================
$data = $pdo->query("
SELECT t.*, k.Nopol, k.Sopir, s.Nama_Supplier, m.material AS material, c.Customers
FROM transaksi t
LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
LEFT JOIN material m ON t.id_material = m.id_Material
LEFT JOIN customers c ON t.id_customers = c.id_Customers
ORDER BY t.id_transaksi DESC

")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Input Transaksi</title>

<style>
body{
    font-family:Segoe UI;
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    padding:20px;
}
.container{
    background:white;
    padding:25px;
    border-radius:12px;
}
input,select{
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border-radius:8px;
    border:1px solid #ccc;
}
button{
    padding:12px;
    border:none;
    border-radius:8px;
    background:#10b981;
    color:white;
    font-weight:bold;
    width:100%;
}
table{
    width:100%;
    margin-top:20px;
    border-collapse:collapse;
}
th,td{
    padding:10px;
    border-bottom:1px solid #ddd;
}
th{
    background:#333;
    color:white;
}
</style>

</head>
<body>

<div class="container">

<h2>Input Transaksi</h2>

<form method="POST">

<label>No Record</label>
<input type="text" name="no_record" value="<?= $no_record ?>" readonly>

<label>No Polisi</label>
<input type="text" id="nopol" list="listNopol">
<datalist id="listNopol">
<?php foreach($kendaraan as $k): ?>
<option value="<?= $k['Nopol'] ?>" data-id="<?= $k['id_Kendaraan'] ?>" data-sopir="<?= $k['Sopir'] ?>">
<?php endforeach; ?>
</datalist>

<input type="hidden" name="id_kendaraan" id="id_kendaraan">

<label>Sopir</label>
<input type="text" id="sopir" readonly>

<label>Customer</label>
<select name="id_customers">
<?php foreach($customers as $c): ?>
<option value="<?= $c['id_Customers'] ?>"><?= $c['Customers'] ?></option>
<?php endforeach; ?>
</select>

<label>Supplier</label>
<select name="id_supplier">
<?php foreach($suppliers as $s): ?>
<option value="<?= $s['id_Supplier'] ?>"><?= $s['Nama_Supplier'] ?></option>
<?php endforeach; ?>
</select>

<label>Material</label>
<select name="id_material">
<?php foreach($materials as $m): ?>
<option value="<?= $m['id'] ?>"><?= $m['nama'] ?></option>
<?php endforeach; ?>
</select>

<label>Bruto</label>
<input type="number" id="bruto" name="bruto">

<label>Tara</label>
<input type="number" id="tara" name="tara">

<label>Netto</label>
<input type="number" id="netto" name="netto" readonly>

<button name="simpan">Simpan</button>

</form>

<table>
<tr>
<th>No</th>
<th>Record</th>
<th>Nopol</th>
<th>Sopir</th>
<th>Supplier</th>
<th>Material</th>
<th>Customer</th>
<th>Netto</th>
<th>Aksi</th>
</tr>

<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nama_Supplier'] ?></td>
<td><?= $d['material'] ?></td>
<td><?= $d['Customers'] ?></td>
<td><?= $d['netto'] ?></td>
<td>
<a href="?hapus=<?= $d['id_transaksi'] ?>" onclick="return confirm('Hapus?')">Hapus</a>
</td>
</tr>
<?php endforeach; ?>

</table>

</div>

<script>
// AUTO SOPIR
document.getElementById('nopol').addEventListener('input', function(){
    let val = this.value;
    let options = document.querySelectorAll('#listNopol option');
    
    options.forEach(opt=>{
        if(opt.value === val){
            document.getElementById('sopir').value = opt.dataset.sopir;
            document.getElementById('id_kendaraan').value = opt.dataset.id;
        }
    });
});

// AUTO NETTO
function hitung(){
    let bruto = parseFloat(document.getElementById('bruto').value) || 0;
    let tara = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = bruto - tara;
}
document.getElementById('bruto').addEventListener('input', hitung);
document.getElementById('tara').addEventListener('input', hitung);
</script>

</body>
</html>