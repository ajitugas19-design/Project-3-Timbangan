<?php
session_start();
require_once '../../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

// ================= HANDLE AJAX =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $no_record = trim($_POST['no_record']);
            $id_kendaraan = (int)$_POST['id_kendaraan'];
            $id_supplier = (int)$_POST['id_supplier'];
            $id_material = (int)$_POST['id_material'];
            $id_customers = (int)$_POST['id_customers'];
            $bruto = (float)$_POST['bruto'];
            $tara = (float)$_POST['tara'];
            $netto = (float)$_POST['netto'];

            if (!$no_record || !$id_kendaraan || !$id_supplier || !$id_material || !$id_customers || $bruto <= 0 || $tara <= 0) {
                throw new Exception('Lengkapi data wajib (no_record, kendaraan, supplier, material, customer, bruto, tara)');
            }

            $pdo->prepare("INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, bruto, tara, netto) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$no_record, $id_kendaraan, $id_supplier, $id_material, $id_customers, $bruto, $tara, $netto]);

            echo json_encode(['success'=>true,'message'=>'✅ Transaksi baru ditambahkan']);
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $no_record = trim($_POST['no_record']);
            $id_kendaraan = (int)$_POST['id_kendaraan'];
            $id_supplier = (int)$_POST['id_supplier'];
            $id_material = (int)$_POST['id_material'];
            $id_customers = (int)$_POST['id_customers'];
            $bruto = (float)$_POST['bruto'];
            $tara = (float)$_POST['tara'];
            $netto = (float)$_POST['netto'];

            if (!$id || !$no_record || !$id_kendaraan || $bruto <= 0) {
                throw new Exception('Data tidak valid');
            }

            $pdo->prepare("UPDATE transaksi SET no_record=?, id_kendaraan=?, id_supplier=?, id_material=?, id_customers=?, bruto=?, tara=?, netto=? WHERE id_transaksi=?")
                ->execute([$no_record, $id_kendaraan, $id_supplier, $id_material, $id_customers, $bruto, $tara, $netto, $id]);

            echo json_encode(['success'=>true,'message'=>'✅ Data transaksi diupdate']);
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi=?")->execute([$id]);
            echo json_encode(['success'=>true,'message'=>'🗑️ Data transaksi dihapus']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'❌ ' . $e->getMessage()]);
        exit;
    }
}




// ================= LOAD DROPDOWNS FOR FORM =================
$kendaraan = $pdo->query("SELECT id_Kendaraan, Nopol, Sopir FROM kendaraan ORDER BY Nopol")->fetchAll();
$suppliers = $pdo->query("SELECT id_Supplier, Nama_Supplier FROM supplier ORDER BY Nama_Supplier")->fetchAll();
$materials = $pdo->query("SELECT id_Material, Material FROM material ORDER BY Material")->fetchAll();
$customers = $pdo->query("SELECT id_Customers, Customers FROM customers ORDER BY Customers")->fetchAll();

// ================= LOAD TABLE DATA =================
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
    for ($i=0;$i<6;$i++) $params[] = "%$search%";
}

$stmt = $pdo->prepare("
SELECT
t.*,
k.Nopol, k.Sopir,
s.Nama_Supplier,
m.Material,
c.Customers,
DATE(wi.tanggal_in) as tanggal_in,
DATE(wo.tanggal_out) as tanggal_out
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
<title>Informasi Data - Transaksi</title>

<style>
/* ===============================
   CSS TABEL TRANSAKSI FULL DATA
   SEMUA KOLOM MUAT LAYAR
=================================*/

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

/* CARD */
.card{
  background:#fff;
  border-radius:12px;
  padding:12px;
  box-shadow:0 3px 10px rgba(0,0,0,.08);
}

/* FILTER */
input[type="date"],
input[type="text"],
input[type="number"],
select{
  width:100%;
  padding:8px 10px;
  border:1px solid #d1d5db;
  border-radius:6px;
  outline:none;
  font-size:12px;
}

/* BUTTON */
button,
.btn{
  border:none;
  cursor:pointer;
  border-radius:6px;
  padding:8px 12px;
  font-weight:600;
  font-size:12px;
}

.btn-save{
  background:#10b981;
  color:#fff;
  width:100%;
}

.btn-cancel{
  background:#6b7280;
  color:#fff;
  width:100%;
  margin-top:8px;
}

/* TABLE */
.table-container{
  margin-top:12px;
  overflow-x:auto;
  border-radius:10px;
  box-shadow:0 3px 10px rgba(0,0,0,.08);
  background:#fff;
}

table{
  width:100%;
  border-collapse:collapse;
  table-layout:auto; /* biar fleksibel semua tampil */
  font-size:11px;
}

thead tr{
  background:#111827;
}

th{
  color:#fff;
  padding:8px 4px;
  text-align:center;
  font-weight:600;
  white-space:nowrap;
}

td{
  padding:7px 4px;
  border-bottom:1px solid #e5e7eb;
  text-align:center;
  vertical-align:middle;
  white-space:nowrap;
}

tbody tr:hover{
  background:#eff6ff;
}

/* KOLOM KECIL */
th:nth-child(1), td:nth-child(1){width:35px;}
th:nth-child(2), td:nth-child(2){width:45px;}
th:nth-child(3), td:nth-child(3){width:75px;}
th:nth-child(4), td:nth-child(4){width:95px;}
th:nth-child(5), td:nth-child(5){width:80px;}
th:nth-child(6), td:nth-child(6){width:110px;}
th:nth-child(7), td:nth-child(7){width:95px;}
th:nth-child(8), td:nth-child(8){width:95px;}
th:nth-child(9), td:nth-child(9){width:75px;}
th:nth-child(10), td:nth-child(10){width:75px;}
th:nth-child(11), td:nth-child(11){width:70px; text-align:right;}
th:nth-child(12), td:nth-child(12){width:70px; text-align:right;}
th:nth-child(13), td:nth-child(13){width:80px; text-align:right;}
th:nth-child(14), td:nth-child(14){width:115px;}

/* NETTO */
.netto{
  color:#10b981;
  font-weight:700;
}

/* ACTION */
.edit,
.hapus{
  font-size:10px;
  padding:5px 7px;
  border:none;
  border-radius:5px;
  color:#fff;
  margin:1px;
}

.edit{ background:#f59e0b; }
.hapus{ background:#ef4444; }

/* FORM */
.form-overlay{
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100vh;
  background:rgba(0,0,0,.45);
  display:none;
  z-index:1000;
}

.form-overlay.active{
  display:block;
}

.form-slide{
  position:fixed;
  top:0;
  right:-360px;
  width:360px;
  height:100vh;
  background:#fff;
  padding:18px;
  overflow-y:auto;
  box-shadow:-5px 0 20px rgba(0,0,0,.15);
  transition:.3s;
  z-index:1001;
}

.form-slide.active{
  right:0;
}

/* TOAST */
.message{
  position:fixed;
  top:15px;
  right:15px;
  padding:10px 14px;
  color:#fff;
  border-radius:8px;
  z-index:9999;
  font-size:12px;
  font-weight:600;
}

.success{ background:#10b981; }
.error{ background:#ef4444; }

/* MOBILE */
@media(max-width:768px){

  body{
    padding:6px;
  }

  table{
    font-size:10px;
  }

  th,td{
    padding:6px 3px;
  }

  .form-slide{
    width:100%;
    right:-100%;
  }

  .form-slide.active{
    right:0;
  }
}
</style>
</head>

<body>

<div class="card">

<!--<div class="header">
<h1>📊 Informasi Data Transaksi</h1>
<p>Kelola data transaksi lengkap dengan cari & CRUD</p>

<button class="btn" onclick="openAdd()" style="background:#10b981;color:white;padding:12px 24px;border:none;border-radius:10px;font-size:16px;cursor:pointer;margin-bottom:20px;">➕ Tambah Transaksi</button>
</div>--->



<!-- FILTER -->
<div style="display:flex;gap:10px;margin-bottom:20px;">
<input type="date" name="tgl" id="tgl" value="<?= $tgl ?>" onchange="loadTable()">
<input type="text" name="search" id="search" placeholder="🔍 Cari No Record / Sopir / Supplier / Material..." value="<?= htmlspecialchars($search) ?>" oninput="debounceSearch()">
<button onclick="loadTable()" style="background:#2563eb;color:white;padding:11px 20px;border:none;border-radius:10px;cursor:pointer;">Cari</button>
</div>

<!-- TABLE -->
<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
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
</thead>
<tbody id="tbody">
<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $d['id_transaksi'] ?></td>
<td><?= htmlspecialchars($d['no_record']) ?></td>
<td><?= htmlspecialchars($d['Sopir']) ?></td>
<td><?= htmlspecialchars($d['Nopol']) ?></td>
<td><?= htmlspecialchars($d['Nama_Supplier']) ?></td>
<td><?= htmlspecialchars($d['Material']) ?></td>
<td><?= htmlspecialchars($d['Customers']) ?></td>
<td><?= htmlspecialchars($d['tanggal_in']) ?></td>
<td><?= htmlspecialchars($d['tanggal_out']) ?></td>
<td style="text-align:right;"><?= number_format($d['bruto']) ?></td>
<td style="text-align:right;"><?= number_format($d['tara']) ?></td>
<td class="netto" style="font-weight:bold;color:#10b981;"><?= number_format($d['netto']/1000,2) ?> </td>
<td>
<button class="edit" onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️ EDIT</button>
<button class="hapus" onclick="hapus(<?= (int)$d['id_transaksi'] ?>)">🗑 HAPUS</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- FORM MODAL -->
<div id="overlay" class="form-overlay"></div>
<div id="slide" class="form-slide">
<h3 id="title">Tambah Transaksi</h3>
<form id="form">
<input type="hidden" name="action" id="action">
<input type="hidden" name="id" id="id">

<label>No Record:</label>
<input type="text" name="no_record" id="no_record" placeholder="TRX-001" required>

<label>Kendaraan:</label>
<select name="id_kendaraan" id="id_kendaraan" required>
<option value="">Pilih Kendaraan</option>
<?php foreach($kendaraan as $k): ?>
<option value="<?= $k['id_Kendaraan'] ?>"><?= htmlspecialchars($k['Nopol']) ?> - <?= htmlspecialchars($k['Sopir']) ?></option>
<?php endforeach; ?>
</select>

<label>Supplier:</label>
<select name="id_supplier" id="id_supplier" required>
<option value="">Pilih Supplier</option>
<?php foreach($suppliers as $s): ?>
<option value="<?= $s['id_Supplier'] ?>"><?= htmlspecialchars($s['Nama_Supplier']) ?></option>
<?php endforeach; ?>
</select>

<label>Material:</label>
<select name="id_material" id="id_material" required>
<option value="">Pilih Material</option>
<?php foreach($materials as $m): ?>
<option value="<?= $m['id_Material'] ?>"><?= htmlspecialchars($m['Material']) ?></option>
<?php endforeach; ?>
</select>

<label>Customer:</label>
<select name="id_customers" id="id_customers" required>
<option value="">Pilih Customer</option>
<?php foreach($customers as $c): ?>
<option value="<?= $c['id_Customers'] ?>"><?= htmlspecialchars($c['Customers']) ?></option>
<?php endforeach; ?>
</select>

<label>Bruto (kg):</label>
<input type="number" name="bruto" id="bruto" min="1" step="0.01" required>

<label>Tara (kg):</label>
<input type="number" name="tara" id="tara" min="0" step="0.01" required>

<label>Netto (kg):</label>
<input type="number" name="netto" id="netto" readonly>

<button type="submit" class="btn btn-save">💾 Simpan</button>
<button type="button" class="btn btn-cancel" onclick="closeForm()">❌ Batal</button>
</form>
</div>

<script>
(function(){
const form = document.getElementById('form');
const overlay = document.getElementById('overlay');
const slide = document.getElementById('slide');
const tbody = document.getElementById('tbody');
const title = document.getElementById('title');

// BASE URL untuk AJAX
const BASE_URL = window.location.pathname.includes('Informasi_Data.php') ? '' : 'sidebar/Informasi_Data.php';

// ===== FORM CONTROL =====
function openForm() { slide.classList.add('active'); overlay.classList.add('active'); }
function closeForm() { 
    slide.classList.remove('active'); 
    overlay.classList.remove('active'); 
    form.reset(); 
}

overlay.onclick = closeForm;
slide.onclick = e => e.stopPropagation();

// ===== ADD =====
window.openAdd = function() {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    title.innerText = 'Tambah Transaksi Baru';
    openForm();
}

// ===== EDIT =====
window.openEdit = function(d) {
    document.getElementById('action').value = 'edit';
    document.getElementById('id').value = d.id_transaksi;
    document.getElementById('no_record').value = d.no_record;
    document.getElementById('id_kendaraan').value = d.id_kendaraan;
    document.getElementById('id_supplier').value = d.id_supplier;
    document.getElementById('id_material').value = d.id_material;
    document.getElementById('id_customers').value = d.id_customers;
    document.getElementById('bruto').value = d.bruto;
    document.getElementById('tara').value = d.tara;
    document.getElementById('netto').value = d.netto;
    title.innerText = 'Edit Transaksi ' + d.no_record;
    openForm();
}

// ===== DELETE =====
window.hapus = function(id) {
    if(!confirm('Yakin hapus transaksi ini?')) return;
    
    let fd = new FormData();
    fd.append('action','delete');
    fd.append('id',id);
    
    fetch(BASE_URL, {method:'POST', body:fd})
    .then(r=>r.json())
    .then(res=>{
        show(res.message, res.success);
        if(res.success) loadTable();
    }).catch(err=>show('❌ Gagal hapus', false));
}

// ===== FORM SUBMIT =====
form.onsubmit = function(e) {
    e.preventDefault();
    
    // Auto calculate netto
    let bruto = parseFloat(document.getElementById('bruto').value) || 0;
    let tara = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = (bruto - tara).toFixed(2);
    
    let fd = new FormData(form);
    
    fetch(BASE_URL, {method:'POST', body:fd})
    .then(r=>r.json())
    .then(res=>{
        show(res.message, res.success);
        if(res.success) {
            closeForm();
            loadTable();
        }
    }).catch(err=>show('❌ Gagal simpan', false));
}

// ===== TABLE RELOAD WITH FILTER =====
function loadTable() {
    let url = BASE_URL + '?tgl=' + encodeURIComponent(document.getElementById('tgl').value) + 
              '&search=' + encodeURIComponent(document.getElementById('search').value);
    
    fetch(url)
    .then(r=>r.text())
    .then(html=>{
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTbody = doc.querySelector('#tbody');
        if(newTbody) tbody.innerHTML = newTbody.innerHTML;
    });
}

// ===== DEBOUNCE SEARCH =====
let searchTimeout;
window.debounceSearch = function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadTable, 500);
}

// ===== AUTO CALC NETTO =====
document.getElementById('bruto').oninput = document.getElementById('tara').oninput = function() {
    let bruto = parseFloat(document.getElementById('bruto').value) || 0;
    let tara = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = (bruto - tara).toFixed(2);
}

// ===== MESSAGE TOAST =====
function show(msg, success=true) {
    let div = document.createElement('div');
    div.className = `message ${success ? 'success' : 'error'} show`;
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(()=>div.remove(), 4000);
}

// Initial load
loadTable();
})();
</script>

</body>
</html>
