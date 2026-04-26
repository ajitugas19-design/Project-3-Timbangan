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
            $kode = trim($_POST['kode']);
            $nama = trim($_POST['nama']);

            if (!$kode || !$nama) throw new Exception('Data wajib diisi');

            $pdo->prepare("INSERT INTO material (Kode, Material) VALUES (?,?)")
                ->execute([$kode, $nama]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil tambah']);
        }

        elseif ($action === 'edit') {
            $id   = (int)$_POST['id'];
            $kode = trim($_POST['kode']);
            $nama = trim($_POST['nama']);

            if (!$id || !$kode || !$nama) throw new Exception('Data tidak valid');

            $pdo->prepare("UPDATE material SET Kode=?, Material=? WHERE id_Material=?")
                ->execute([$kode, $nama, $id]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil update']);
        }

        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            $pdo->prepare("DELETE FROM material WHERE id_Material=?")
                ->execute([$id]);

            echo json_encode(['success'=>true,'message'=>'🗑️ Berhasil hapus']);
        }

        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000 || strpos($e->getMessage(), '1451') !== false) {
            echo json_encode(['success'=>false,'message'=>'❌ Data masih digunakan dalam transaksi, tidak bisa dihapus']);
        } else {
            echo json_encode(['success'=>false,'message'=>'❌ '.$e->getMessage()]);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'❌ '.$e->getMessage()]);
        exit;
    }
}

// ================= LOAD DATA =================
$data = $pdo->query("SELECT * FROM material ORDER BY id_Material DESC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Materials</title>

<style>
/* Scoped: Materials */
.page-materials th:nth-child(1),
.page-materials td:nth-child(1){ width:60px; text-align:center; }
.page-materials th:nth-child(2),
.page-materials td:nth-child(2){ width:180px; text-align:center; }
.page-materials th:nth-child(3),
.page-materials td:nth-child(3){ text-align:left; padding-left:15px; }
.page-materials th:nth-child(4),
.page-materials td:nth-child(4){ width:180px; text-align:center; }
</style>
</head>

<body>

<div class="page-materials">
<div id="messageContainer"></div>
<button class="btn btn-success" onclick="openAdd()">+ Tambah Material</button>

<a href="/Project_3/Dasboard/Laporan/laporan_material.php" target="_blank">
    <button class="btn btn-print">🖨 Print Material</button>
</a>

<div class="table-container">
<table class="data-table">
<thead>
<tr>
<th>No</th>
<th>Kode</th>
<th>Nama Material</th>
<th>Opsi</th>
</tr>
</thead>

<tbody id="tbody">
<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($d['Kode']) ?></td>
<td><?= htmlspecialchars($d['Material']) ?></td>
<td>
<button class="edit-btn"
onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'> EDIT </button>

<button class="delete-btn"
onclick="hapus(<?= (int)$d['id_Material'] ?>)"> HAPUS </button>
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

<input type="text" name="kode" id="kode" placeholder="Kode Material" required>
<input type="text" name="nama" id="nama" placeholder="Nama Material" required>

<button type="submit" class="btn-save">Simpan</button>
<button type="button" class="btn-cancel-form" onclick="closeForm()">Batal</button>
</form>
</div>
</div>

<script src="/Project_3/Dasboard/js/enter-next.js"></script>
<script>
(function(){

const form = document.getElementById('form');
const overlay = document.getElementById('overlay');
const slide = document.getElementById('slide');
const tbody = document.getElementById('tbody');

if (typeof initEnterNext === 'function') initEnterNext(form);

// ===== URL FIX =====
const BASE_URL = window.location.pathname.includes('Materials.php')
  ? ''
  : 'sidebar/Materials.php';

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
    form.action.value = 'add';
    document.getElementById('title').innerText = 'Tambah Material';
    openForm();
}

// ===== EDIT =====
window.openEdit = function(d){
    form.reset();
    form.action.value = 'edit';
    form.id.value = d.id_Material;
    form.kode.value = d.Kode;
    form.nama.value = d.Material;

    document.getElementById('title').innerText = 'Edit Material';
    openForm();
}

window.closeForm = function(){
    document.getElementById('slide').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
};

// ===== DELETE =====
window.hapus = function(id){
    if(!confirm('Hapus data?')) return;

    let fd = new FormData();
    fd.append('action','delete');
    fd.append('id',id);

    fetch(BASE_URL,{
        method:'POST',
        body:fd
    })
    .then(r=>r.json())
    .then(res=>{
        show(res.message,res.success);
        if(res.success) loadTable();
    });
}

window.openEditById = function(id){
    fetch(BASE_URL + '?json=1')
    .then(r=>r.json())
    .then(rows=>{
        const d = rows.find(x => x.id_Material == id);
        if(!d) return;

        form.reset();
        form.action.value = 'edit';
        form.id.value = d.id_Material;
        form.kode.value = d.Kode;
        form.nama.value = d.Material;

        document.getElementById('title').innerText = 'Edit Material';
        openForm();
    });
}

// ===== SUBMIT =====
form.addEventListener('submit', function(e){
    e.preventDefault();

    let fd = new FormData(form);

    fetch(BASE_URL,{
        method:'POST',
        body:fd
    })
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
    fetch(BASE_URL + '?json=1')
    .then(r=>r.json())
    .then(rows=>{
        let html = '';
        let no = 1;

        rows.forEach(d=>{
            html += `
            <tr>
                <td>${no++}</td>
                <td>${escapeHtml(d.Kode)}</td>
                <td>${escapeHtml(d.Material)}</td>
                <td>
                    <button class="edit-btn"
                    onclick="openEditById(${d.id_Material})"> EDIT </button>

                    <button class="delete-btn"
                    onclick="hapus(${d.id_Material})"> HAPUS </button>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
    });
}

function escapeHtml(text){
    if(!text) return '';
    return text
        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");
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

