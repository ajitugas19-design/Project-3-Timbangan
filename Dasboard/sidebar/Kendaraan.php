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
/* Scoped: Kendaraan */
.page-kendaraan th:nth-child(1),
.page-kendaraan td:nth-child(1){ width:60px; text-align:center; }
.page-kendaraan th:nth-child(2),
.page-kendaraan td:nth-child(2){ width:220px; text-align:center; }
.page-kendaraan th:nth-child(3),
.page-kendaraan td:nth-child(3){ text-align:left; }
.page-kendaraan th:nth-child(4),
.page-kendaraan td:nth-child(4){ width:180px; text-align:center; }
</style>
</head>

<body>

<div class="page-kendaraan">
<button class="btn btn-primary" onclick="openAdd()"> + Tambah Kendaraan</button>

<div class="table-container">
<table class="data-table">
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
<button class="edit-btn"
onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'> EDIT </button>

<button class="delete-btn"
onclick="hapus(<?= (int)$d['id_Kendaraan'] ?>")>HAPUS</button>
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

