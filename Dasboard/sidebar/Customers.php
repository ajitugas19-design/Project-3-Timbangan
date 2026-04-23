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
            $nama = trim($_POST['nama']);
            $ket  = trim($_POST['keterangan']);

            if (!$nama) throw new Exception('Nama wajib diisi');

            $pdo->prepare("INSERT INTO customers (Customers,Keterangan) VALUES (?,?)")
                ->execute([$nama,$ket]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil tambah']);
        }

        elseif ($action === 'edit') {
            $id   = (int)$_POST['id'];
            $nama = trim($_POST['nama']);
            $ket  = trim($_POST['keterangan']);

            if (!$id || !$nama) throw new Exception('Data tidak valid');

            $pdo->prepare("UPDATE customers SET Customers=?,Keterangan=? WHERE id_Customers=?")
                ->execute([$nama,$ket,$id]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil update']);
        }

        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            $pdo->prepare("DELETE FROM customers WHERE id_Customers=?")
                ->execute([$id]);

            echo json_encode(['success'=>true,'message'=>'🗑️ Berhasil hapus']);
        }

        exit;

    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'❌ '.$e->getMessage()]);
        exit;
    }
}

// ================= LOAD =================
$data = $pdo->query("SELECT * FROM customers ORDER BY id_Customers DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Customers</title>

<style>
/* CSS MIRROR Suppliers.php */
:root {
  --danger: #ef4444;
  --warning: #eab308;
  --dark: #374151;
  --light: #f3f4f6;
  --shadow: 0 4px 6px rgba(0,0,0,0.1);
  --form-slide-width: 350px;
  --primary: #10b981;
}

/* BUTTON */
.btn{
  background:var(--primary);
  color:white;
  padding:10px 20px;
  border:none;
  border-radius:10px;
  cursor:pointer;
  font-weight:600;
}

/* TABLE */
.table-container{
  background:white;
  border-radius:12px;
  margin-top:20px;
  overflow-x:auto;
  box-shadow:var(--shadow);
}

table{
  width:100%;
  border-collapse:collapse;
  table-layout:fixed; /* bikin lurus sejajar */
}

thead tr{
  background:#374151;
}

th{
  color:white;
  padding:14px 12px;
  text-align:center;
  font-size:14px;
  font-weight:600;
}

td{
  padding:12px;
  border-bottom:1px solid #eee;
  font-size:14px;
  vertical-align:middle;
  word-wrap:break-word;
}

/* POSISI KOLOM */
th:nth-child(1),
td:nth-child(1){
  width:60px;
  text-align:center;
}

th:nth-child(2),
td:nth-child(2){
  width:250px;
  text-align:left;
}

th:nth-child(3),
td:nth-child(3){
  text-align:left;
}

th:nth-child(4),
td:nth-child(4){
  width:180px;
  text-align:center;
}

/* ROW HOVER */
tbody tr:hover{
  background:#f9fafb;
}

/* BUTTON ACTION */
.edit,
.hapus{
  border:none;
  padding:8px 14px;
  border-radius:8px;
  color:white;
  cursor:pointer;
  font-size:13px;
  margin:2px;
}

.edit{
  background:#f59e0b;
}

.hapus{
  background:#ef4444;
}

/* FORM */
.form-overlay{
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100vh;
  background:rgba(0,0,0,.5);
  display:none;
  z-index:1000;
}

.form-overlay.active{
  display:block;
}

.form-slide{
  position:fixed;
  right:calc(-1 * var(--form-slide-width));
  top:0;
  width:var(--form-slide-width);
  height:100vh;
  background:white;
  padding:24px;
  transition:.3s;
  z-index:1001;
  box-shadow:-4px 0 20px rgba(0,0,0,.15);
  overflow-y:auto;
}

.form-slide.active{
  right:0;
}

input,
textarea{
  width:100%;
  padding:10px;
  margin-bottom:10px;
  border:1px solid #ddd;
  border-radius:6px;
  box-sizing:border-box;
}

.btn-save{
  background:#10b981;
  color:white;
  padding:10px;
  width:100%;
  border:none;
  border-radius:8px;
}

.btn-cancel{
  background:#6b7280;
  color:white;
  padding:10px;
  width:100%;
  border:none;
  border-radius:8px;
  margin-top:10px;
}

/* MESSAGE */
.message{
  position:fixed;
  top:20px;
  right:20px;
  padding:15px;
  border-radius:10px;
  color:white;
  z-index:9999;
}

.success{
  background:#10b981;
}

.error{
  background:#ef4444;
}
</style>
</head>

<body>

<button class="btn" onclick="openAdd()">+ Tambah Customer</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>Nama Customer</th>
<th>Keterangan</th>
<th>Opsi</th>
</tr>
</thead>

<tbody id="tbody">
<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($d['Customers']) ?></td>
<td><?= htmlspecialchars($d['Keterangan']) ?></td>
<td>

<button class="edit"
onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
EDIT
</button>

<button class="hapus"
onclick="hapus(<?= isset($d['id_Customers']) ? (int)$d['id_Customers'] : 0 ?>)">
HAPUS
</button>

</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- FORM -->
<div id="overlay" class="form-overlay"></div>

<div id="slide" class="form-slide">
<h3 id="title"></h3>

<form id="form">
<input type="hidden" name="action" id="action">
<input type="hidden" name="id" id="id">

<input type="text" name="nama" id="nama" placeholder="Nama Customer" required>
<textarea name="keterangan" id="ket" placeholder="Keterangan" rows="3"></textarea>

<button type="submit" class="btn-save">Simpan</button>
<button type="button" class="btn-cancel" onclick="closeForm()">Batal</button>
</form>
</div>

<script>
(function(){

const form = document.getElementById('form');
const overlay = document.getElementById('overlay');
const slide = document.getElementById('slide');
const tbody = document.getElementById('tbody');
const title = document.getElementById('title');

const BASE_URL = window.location.pathname.includes('Customers.php')
  ? ''
  : 'sidebar/Customers.php';

// ===== FORM =====
function openForm(){
    slide.classList.add('active');
    overlay.classList.add('active');
}
function closeForm(){
    slide.classList.remove('active');
    overlay.classList.remove('active');
}

overlay.onclick = closeForm;
slide.onclick = e => e.stopPropagation();

// ===== ADD =====
window.openAdd = function(){
    form.reset();
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    title.innerText = 'Tambah Customer';
    openForm();
}

// ===== EDIT =====
window.openEdit = function(d){
    console.log(d); // 🔥 DEBUG

    document.getElementById('action').value = 'edit';
    document.getElementById('id').value = d.id_Customers;

    document.getElementById('nama').value = d.Customers;
    document.getElementById('ket').value = d.Keterangan || '';

    title.innerText = 'Edit Customer';
    openForm();
}

// ===== DELETE =====
window.hapus = function(id){
    console.log("DELETE ID:", id); // 🔥 DEBUG

    if(!confirm('Hapus data?')) return;

    let fd = new FormData();
    fd.append('action','delete');
    fd.append('id',id);

    fetch(BASE_URL,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(res=>{
        console.log(res); // 🔥 DEBUG
        show(res.message,res.success);
        if(res.success) loadTable();
    });
}

// ===== SUBMIT =====
form.addEventListener('submit', function(e){
    e.preventDefault();

    let fd = new FormData(form);

    fetch(BASE_URL,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(res=>{
        show(res.message,res.success);
        if(res.success){
            closeForm();
            loadTable();
        }
    })
    .catch(()=>show('❌ Gagal simpan',false));
});

// ===== RELOAD TABLE =====
function loadTable(){
    fetch(BASE_URL)
    .then(r=>r.text())
    .then(html=>{
        const doc = new DOMParser().parseFromString(html,'text/html');
        const newTbody = doc.getElementById('tbody');
        if (newTbody) tbody.innerHTML = newTbody.innerHTML;
    });
}

// ===== MESSAGE =====
function show(msg, ok=true){
    let d=document.createElement('div');
    d.className='message '+(ok?'success':'error');
    d.innerText=msg;
    document.body.appendChild(d);
    setTimeout(()=>d.remove(),3000);
}

})();
</script>

</body>
</html>

