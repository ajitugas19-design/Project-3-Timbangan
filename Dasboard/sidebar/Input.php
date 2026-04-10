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
    (no_record,id_kendaraan,id_supplier,id_material,id_customers,bruto,tara,netto) 
    VALUES (?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $_POST['no_record'],
        $_POST['id_kendaraan'],
        $id_supplier,
        $_POST['id_material'],
        $id_customer,
        $_POST['bruto'],
        $_POST['tara'],
        $_POST['netto']
    ]);

    echo "<script>alert('Data berhasil disimpan');location='';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Input Timbangan</title>

<style>\n.content-container {\n    background:#e5e5e5;\n    font-family: Arial;\n    padding: 1.5rem;\n    border-radius: var(--radius);\n}\n\n.content-grid {\n    display: grid;\n    grid-template-columns: 1fr 1fr;\n    gap: clamp(1rem, 3vw, 1.5rem);\n}\n\n.content-label {\n    font-size: 0.9rem;\n    font-weight: 500;\n}\n\n.content-input, .content-select {\n    width: 100%;\n    background: #f3f4f6;\n    border: 1px solid var(--border);\n    padding: clamp(0.75rem, 2vw, 1rem);\n    margin-bottom: 0.75rem;\n    border-radius: 6px;\n}\n\n.content-row {\n    display: flex;\n    gap: 0.75rem;\n}\n\n.content-btn {\n    border: none;\n    padding: 0.75rem 1rem;\n    cursor: pointer;\n    border-radius: 6px;\n}\n\n.content-btn-primary { background:var(--primary); color:white; }\n.content-btn-success { background:var(--primary-dark); color:white; }\n.content-btn-danger { background:#ef4444; color:white; }\n\n.content-center { text-align:center; margin-top:1.5rem; }\n\n.content-box {\n    display:flex;\n    gap:0.75rem;\n}\n\n.content-box > div {\n    flex:1;\n}\n\n.content-small-text {\n    font-size:0.85rem;\n    text-align:center;\n    margin-bottom:0.75rem;\n    color: #6b7280;\n}\n\n.content-check-group {\n    display:flex;\n    align-items:center;\n    gap:0.5rem;\n    margin-bottom:0.5rem;\n}\n\n.content-check-group input {\n    width:auto;\n}\n\n@media (max-width: 768px) {\n  .content-grid, .content-box {\n    grid-template-columns: 1fr;\n    flex-direction: column;\n  }\n  .content-btn {\n    width: 100%;\n  }\n}\n</style>

</head>
<body>

<div class="container">

<form method="POST">

<div class="grid">

<!-- KIRI -->
<div>

<label>No. Record</label>
<input type="text" name="no_record" value="<?= $no_record ?>" readonly>

<label>No Polisi</label>
<div class="row">
<input type="text" id="nopol" list="listNopol">
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

<!-- CUSTOMER -->
<div class="check-group">
<input type="checkbox" id="cek_customer" name="cek_customer">
<label style="background:#ef4444;color:white;padding:3px;">Customer</label>
</div>
<select name="id_customers" id="customer" disabled>
<?php foreach($customers as $c): ?>
<option value="<?= $c['id_Customers'] ?>"><?= $c['Customers'] ?></option>
<?php endforeach; ?>
</select>

<!-- SUPPLIER -->
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
<input type="date" name="tgl_masuk">

</div>

<!-- KANAN -->
<div>

<div class="small-text">
Timbang Truk yang belum mengisi full, berdasarkan No polisi<br>
Seperti Tombol Edit
</div>

<select>
<option>-- pilih --</option>
</select>

<div class="box">
<div>
<label>Bruto</label>
<input type="number" id="bruto" name="bruto">
</div>

<div>
<label>Tara</label>
<input type="number" id="tara" name="tara">
</div>

<div>
<label>Netto</label>
<input type="number" id="netto" name="netto" readonly>
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
    let options = document.querySelectorAll('#listNopol option');
    
    options.forEach(opt=>{
        if(opt.value === val){
            document.getElementById('sopir').value = opt.dataset.sopir;
            document.getElementById('id_kendaraan').value = opt.dataset.id;
        }
    });
});

// HITUNG NETTO
function hitung(){
    let bruto = parseFloat(document.getElementById('bruto').value) || 0;
    let tara = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = bruto - tara;
}

// ENABLE DISABLE CHECKBOX
document.getElementById('cek_customer').addEventListener('change', function(){
    document.getElementById('customer').disabled = !this.checked;
});

document.getElementById('cek_supplier').addEventListener('change', function(){
    document.getElementById('supplier').disabled = !this.checked;
});
</script>

</body>
</html>