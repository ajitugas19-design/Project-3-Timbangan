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

.action{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
}

.edit, .hapus{
    padding:4px 8px;      /* kecil */
    font-size:11px;       /* kecil */
    border:none;
    border-radius:6px;
    cursor:pointer;
    display:inline-block;
}

.edit{
    background:#f59e0b;
    color:#fff;
}

.hapus{
    background:#ef4444;
    color:#fff;
}
.table-container { background:white; border-radius:12px; margin-top:20px; box-shadow:0 4px 12px rgba(0,0,0,0.1); overflow:hidden; }

.form-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; z-index:1000; }
.form-overlay.active { display:block; }

.form-slide { position:fixed; right:-400px; top:0; width:400px; height:100vh; background:white; box-shadow:-4px 0 20px rgba(0,0,0,0.3); padding:30px; transition:0.3s ease; z-index:1001; overflow-y:auto; }
.form-slide.active { right:0; }

input, select { width:100%; padding:12px; margin-bottom:15px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; font-size:14px; }
.btn-save { background:#10b981 !important; width:100%; }
.btn-cancel { background:#6b7280 !important; width:100%; margin-top:10px; }

.message { position:fixed; top:20px; right:20px; padding:15px 20px; border-radius:10px; color:white; font-weight:600; z-index:2000; transform:translateX(400px); transition:0.3s; }
.message.show { transform:translateX(0); }
.success { background:#10b981; }
.error { background:#ef4444; }


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
<td class="netto" style="font-weight:bold;color:#10b981;"><?= number_format($d['netto']/1000,2) ?> Ton</td>
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
