<?php 
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;margin-top:50px;">Silakan login terlebih dahulu!</div>';
    exit;
}

// LOAD DATA
$kendaraan = $pdo->query("SELECT id_Kendaraan, Nopol, Sopir FROM kendaraan")->fetchAll(PDO::FETCH_ASSOC);
$customers = $pdo->query("SELECT id_Customers, Customers FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$suppliers = $pdo->query("SELECT id_Supplier, Nama_Supplier FROM supplier")->fetchAll(PDO::FETCH_ASSOC);
$materials = $pdo->query("SELECT id_Material as id, Material as nama FROM material")->fetchAll(PDO::FETCH_ASSOC);

// AUTO NO RECORD
$max = $pdo->query("SELECT MAX(id_transaksi) as max FROM transaksi")->fetch();
$no_record = 'TRAN' . str_pad(($max['max'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

// SIMPAN
if (isset($_POST['simpan'])) {
    $id_customer = isset($_POST['cek_customer']) ? $_POST['id_customers'] : null;
    $id_supplier = isset($_POST['cek_supplier']) ? $_POST['id_supplier'] : null;

    $stmt = $pdo->prepare("INSERT INTO transaksi 
    (no_record,id_kendaraan,id_supplier,id_material,id_customers,bruto,tara,netto,tgl_masuk,jam_masuk,tgl_keluar,jam_keluar) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $_POST['no_record'],
        $_POST['id_kendaraan'],
        $id_supplier,
        $_POST['id_material'],
        $id_customer,
        $_POST['bruto'],
        $_POST['tara'],
        $_POST['netto'],
        $_POST['tgl_masuk'],
        $_POST['jam_masuk'],
        $_POST['tgl_keluar'],
        $_POST['jam_keluar']
    ]);

    echo "<script>alert('Data berhasil disimpan!');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Input Timbangan</title>

<style>
body {
  font-family: Arial, sans-serif;
  background: #f3f4f6;
  margin: 0;
}

.container {
  max-width: 1000px;
  margin: 30px auto;
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

h2 {
  margin-bottom: 15px;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

label {
  font-weight: bold;
  font-size: 13px;
}

input, select {
  width: 100%;
  padding: 7px;
  margin-top: 5px;
  margin-bottom: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.row {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 7px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.blue { background: #3b82f6; color: white; }
.green { background: #22c55e; color: white; }

.box {
  display: flex;
  gap: 10px;
}

.box div {
  flex: 1;
  position: relative;
}

.unit {
  position: absolute;
  right: 10px;
  top: 30px;
  font-size: 12px;
  color: gray;
}

.center {
  text-align: center;
  margin: 10px 0;
}

.small-text {
  font-size: 12px;
  margin-bottom: 10px;
  color: gray;
}

.check-group {
  display: flex;
  align-items: center;
  gap: 5px;
}

@media(max-width:768px){
  .content-grid {
    grid-template-columns: 1fr;
  }
}
</style>

</head>

<body>

<div class="container">
<h2>Input Transaksi</h2>

<form method="POST">

<div class="content-grid">

<!-- KIRI -->
<div>

<label>No. Record</label>
<input type="text" name="no_record" value="<?= $no_record ?>" readonly>

<label>No Polisi</label>
<div class="row">
<input type="text" id="nopol" list="listNopol" required>
<button type="button" class="btn blue">Enter</button>
</div>

<datalist id="listNopol">
<?php foreach($kendaraan as $k): ?>
<option value="<?= $k['Nopol'] ?>" data-id="<?= $k['id_Kendaraan'] ?>" data-sopir="<?= $k['Sopir'] ?>">
<?php endforeach; ?>
</datalist>

<input type="hidden" name="id_kendaraan" id="id_kendaraan">

<label>Sopir</label>
<input type="text" id="sopir" readonly>

<div class="check-group">
<input type="checkbox" id="cek_customer" name="cek_customer">
<label>Customer</label>
</div>

<select name="id_customers" id="customer" disabled>
<?php foreach($customers as $c): ?>
<option value="<?= $c['id_Customers'] ?>"><?= $c['Customers'] ?></option>
<?php endforeach; ?>
</select>

<div class="check-group">
<input type="checkbox" id="cek_supplier" name="cek_supplier">
<label>Supplier</label>
</div>

<select name="id_supplier" id="supplier" disabled>
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

<label>Jam Masuk</label>
<input type="time" name="jam_masuk">

<label>Tanggal Masuk</label>
<input type="date" name="tgl_masuk" value="<?= date('Y-m-d') ?>">

</div>

<!-- KANAN -->
<div>

<div class="small-text">Timbang berdasarkan No Polisi</div>

<div class="box">
<div>
<label>Bruto</label>
<input type="number" id="bruto" name="bruto">
<span class="unit">kg</span>
</div>

<div>
<label>Tara</label>
<input type="number" id="tara" name="tara">
<span class="unit">kg</span>
</div>

<div>
<label>Netto</label>
<input type="number" id="netto" name="netto" readonly>
<span class="unit">kg</span>
</div>
</div>

<div class="center">
<button type="button" class="btn green" onclick="hitung()">TIMBANG</button>
</div>

<label>Jam Keluar</label>
<input type="time" name="jam_keluar">

<label>Tanggal Keluar</label>
<input type="date" name="tgl_keluar">

</div>

</div>

<div class="center">
<button name="simpan" class="btn green">SIMPAN</button>
</div>

</form>

</div>

<script>
// AUTO SOPIR
document.getElementById('nopol').addEventListener('input', function(){
    let val = this.value;
    document.querySelectorAll('#listNopol option').forEach(opt=>{
        if(opt.value === val){
            document.getElementById('sopir').value = opt.dataset.sopir;
            document.getElementById('id_kendaraan').value = opt.dataset.id;
        }
    });
});

// HITUNG
function hitung(){
    let bruto = parseFloat(document.getElementById('bruto').value) || 0;
    let tara = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = bruto - tara;
}

document.getElementById('bruto').addEventListener('input', hitung);
document.getElementById('tara').addEventListener('input', hitung);

// CHECKBOX
document.getElementById('cek_customer').onchange = function(){
    document.getElementById('customer').disabled = !this.checked;
};

document.getElementById('cek_supplier').onchange = function(){
    document.getElementById('supplier').disabled = !this.checked;
};
</script>

</body>
</html>