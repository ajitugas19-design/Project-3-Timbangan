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
/* Scoped: Customers */
.page-customers th:nth-child(1),
.page-customers td:nth-child(1){ width:60px; text-align:center; }
.page-customers th:nth-child(2),
.page-customers td:nth-child(2){ width:250px; text-align:left; }
.page-customers th:nth-child(3),
.page-customers td:nth-child(3){ text-align:left; }
.page-customers th:nth-child(4),
.page-customers td:nth-child(4){ width:180px; text-align:center; }
</style>
</head>

<body>

<div class="page-customers">
<button class="btn btn-success" onclick="openAdd()"> + Tambah Customer</button>

<div class="table-container">
<table class="data-table">
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

<button class="edit-btn"
onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
EDIT
</button>

<button class="delete-btn"
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
<button type="button" class="btn-cancel-form" onclick="closeForm()">Batal</button>
</form>
</div>
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
    d.className='message-toast '+(ok?'success':'error');
    d.innerText=msg;
    document.body.appendChild(d);
    void d.offsetWidth;
    d.classList.add('show');
    setTimeout(()=>d.remove(),3000);
}

})();
</script>

</body>
</html>

