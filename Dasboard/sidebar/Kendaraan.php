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
            $nopol = trim($_POST['nopol']);
            $sopir = trim($_POST['sopir']);

            if (!$nopol) throw new Exception('Nopol wajib diisi');

            $pdo->prepare("INSERT INTO kendaraan (Nopol, Sopir) VALUES (?,?)")
                ->execute([$nopol, $sopir]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil tambah']);
        }

        elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $nopol = trim($_POST['nopol']);
            $sopir = trim($_POST['sopir']);

            if (!$id || !$nopol) throw new Exception('Data tidak valid');

            $pdo->prepare("UPDATE kendaraan SET Nopol=?, Sopir=? WHERE id_Kendaraan=?")
                ->execute([$nopol,$sopir,$id]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil update']);
        }

        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            $pdo->prepare("DELETE FROM kendaraan WHERE id_Kendaraan=?")
                ->execute([$id]);

            echo json_encode(['success'=>true,'message'=>'🗑️ Berhasil hapus']);
        }

        exit;

    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'❌ '.$e->getMessage()]);
        exit;
    }
}

// ================= LOAD DATA =================
$data = $pdo->query("SELECT * FROM kendaraan ORDER BY id_Kendaraan DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Kendaraan</title>

<style>
/* CSS KENDARAAN - TABEL LURUS & RAPIH */
:root{
  --primary:#3b82f6;
  --success:#10b981;
  --warning:#f59e0b;
  --danger:#ef4444;
  --dark:#1f2937;
  --light:#f3f4f6;
  --shadow:0 4px 6px rgba(0,0,0,.1);
}

/* BUTTON */
.btn{
  background:#3b82f6;
  color:#fff;
  padding:10px 18px;
  border:none;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
}

.btn-warning{ background:#f59e0b; }
.btn-danger{ background:#ef4444; }
.btn-success{ background:#10b981; }

/* TABLE */
.table-container{
  background:#fff;
  border-radius:12px;
  overflow-x:auto;
  margin-top:20px;
  box-shadow:var(--shadow);
}

table{
  width:100%;
  border-collapse:collapse;
  table-layout:fixed; /* bikin semua kolom sejajar */
}

thead{
  background:#1f2937;
  color:#fff;
}

th{
  padding:14px 12px;
  font-size:14px;
  text-align:center;
}

td{
  padding:12px;
  font-size:14px;
  border-bottom:1px solid #eee;
  vertical-align:middle;
  word-wrap:break-word;
}

/* UKURAN KOLOM */
th:nth-child(1),
td:nth-child(1){
  width:60px;
  text-align:center;
}

th:nth-child(2),
td:nth-child(2){
  width:220px;
  text-align:center;
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

/* HOVER */
tbody tr:hover{
  background:#f9fafb;
}

/* BUTTON ACTION */
.edit,
.hapus{
  border:none;
  padding:8px 14px;
  border-radius:8px;
  color:#fff;
  cursor:pointer;
  font-size:13px;
  margin:2px;
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
  background:rgba(0,0,0,.5);
  display:none;
  z-index:1000;
}

.form-overlay.active{
  display:block;
}

.form-slide{
  position:fixed;
  right:-350px;
  top:0;
  width:350px;
  height:100vh;
  background:#fff;
  padding:24px;
  transition:.3s;
  z-index:1001;
  box-shadow:-4px 0 20px rgba(0,0,0,.15);
  overflow-y:auto;
}

.form-slide.active{
  right:0;
}

input{
  width:100%;
  padding:12px;
  margin-bottom:15px;
  border:1px solid #ddd;
  border-radius:6px;
  box-sizing:border-box;
}

.btn-save{
  width:100%;
  background:#10b981;
  color:#fff;
  padding:12px;
  border:none;
  border-radius:8px;
}

.btn-cancel{
  width:100%;
  background:#ef4444;
  color:#fff;
  padding:12px;
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
  color:#fff;
  z-index:9999;
}

.success{ background:#10b981; }
.error{ background:#ef4444; }
</style>
</head>

<body>

<button class="btn" onclick="openAdd()"> + Tambah Kendaraan</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>Nopol</th>
<th>Sopir</th>
<th>Opsi</th>
</tr>
</thead>

<tbody id="tbody">
<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($d['Nopol']) ?></td>
<td><?= htmlspecialchars($d['Sopir']) ?></td>
<td>
<button class="edit"
onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'> EDIT </button>

<button class="hapus"
onclick="hapus(<?= (int)$d['id_Kendaraan'] ?>)">HAPUS</button>
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

<input type="text" name="nopol" id="nopol" placeholder="Nopol" required>
<input type="text" name="sopir" id="sopir" placeholder="Sopir">

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

const BASE_URL = window.location.pathname.includes('Kendaraan.php')
  ? ''
  : 'sidebar/Kendaraan.php';

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
    action.value = 'add';
    id.value = '';
    title.innerText = 'Tambah Kendaraan';
    openForm();
}

// ===== EDIT =====
window.openEdit = function(d){
    action.value = 'edit';
    id.value = d.id_Kendaraan;
    nopol.value = d.Nopol;
    sopir.value = d.Sopir;
    title.innerText = 'Edit Kendaraan';
    openForm();
}

// ===== DELETE =====
window.hapus = function(id){
    if(!confirm('Hapus data?')) return;

    let fd = new FormData();
    fd.append('action','delete');
    fd.append('id',id);

    fetch(BASE_URL,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(res=>{
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
    });
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