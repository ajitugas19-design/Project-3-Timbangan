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
            $nama = trim($_POST['nama_supplier']);
            $asal = trim($_POST['lokasi_asal']);
            $tujuan = trim($_POST['lokasi_tujuan']);

            if (!$nama) throw new Exception('Nama wajib diisi');

            $pdo->prepare("INSERT INTO supplier (Nama_Supplier, Lokasi_Asal, Lokasi_Tujuan) VALUES (?,?,?)")
                ->execute([$nama,$asal,$tujuan]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil tambah']);
        }

        elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama_supplier']);
            $asal = trim($_POST['lokasi_asal']);
            $tujuan = trim($_POST['lokasi_tujuan']);

            if (!$id || !$nama) throw new Exception('Data tidak valid');

            $pdo->prepare("UPDATE supplier SET Nama_Supplier=?, Lokasi_Asal=?, Lokasi_Tujuan=? WHERE id_Supplier=?")
                ->execute([$nama,$asal,$tujuan,$id]);

            echo json_encode(['success'=>true,'message'=>'✅ Berhasil update']);
        }

        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            $pdo->prepare("DELETE FROM supplier WHERE id_Supplier=?")
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
$data = $pdo->query("SELECT * FROM supplier ORDER BY id_Supplier DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Supplier</title>

<style>
/* CSS STANDARDIZED */
:root {
  --danger: #ef4444;
  --warning: #eab308;
  --dark: #374151;
  --light: #f3f4f6;
  --shadow: 0 4px 6px rgba(0,0,0,0.1);
  --form-slide-width: 350px;
}

.btn{ background:var(--primary,#10b981); color:white; padding:10px 20px; border:none; border-radius:10px; cursor:pointer; }
.table-container{ background:white; border-radius:12px; margin-top:20px; overflow:hidden; box-shadow:var(--shadow); }
table{ width:100%; border-collapse:collapse; }
th{ background:#374151; color:white; padding:12px; }
td{ padding:12px; border-bottom:1px solid #eee; }
.edit{ background:#f59e0b; color:white; border:none; padding:8px 14px; border-radius:8px; }
.hapus{ background:#ef4444; color:white; border:none; padding:8px 14px; border-radius:8px; }

.form-overlay{ position:fixed; top:0; left:0; width:100%; height:100vh; background:rgba(0,0,0,0.5); display:none; z-index:1000; }
.form-overlay.active{ display:block; }

.form-slide{ position:fixed; right: calc(-1 * var(--form-slide-width)); top:0; width:var(--form-slide-width); height:100vh; background:white; padding:24px; transition:right 0.3s ease; z-index:1001; box-shadow:-4px 0 20px rgba(0,0,0,0.15); overflow-y:auto; }
.form-slide.active{ right:0; }

input{ width:100%; padding:10px; margin-bottom:10px; }

.btn-save{ background:#10b981; color:white; padding:10px; width:100%; border:none; border-radius:8px; }
.btn-cancel{ background:#6b7280; color:white; padding:10px; width:100%; border:none; border-radius:8px; margin-top:10px; }

.message{position:fixed;top:20px;right:20px;padding:15px;border-radius:10px;color:white;}
.success{background:#10b981;}
.error{background:#ef4444;}
</style>
</head>

<body>

<button class="btn" onclick="openAdd()">+ Tambah</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>Nama Supplier</th>
<th>Lokasi Asal</th>
<th>Lokasi Tujuan</th>
<th>Opsi</th>
</tr>
</thead>

<tbody id="tbody">
<?php $no=1; foreach($data as $d): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($d['Nama_Supplier']) ?></td>
<td><?= htmlspecialchars($d['Lokasi_Asal']) ?></td>
<td><?= htmlspecialchars($d['Lokasi_Tujuan']) ?></td>
<td>

<button class="edit"
onclick='openEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
EDIT
</button>

<button class="hapus"
onclick="hapus(<?= isset($d['id_Supplier']) ? (int)$d['id_Supplier'] : 0 ?>)">
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

<input type="text" name="nama_supplier" id="nama_supplier" placeholder="Nama Supplier" required>
<input type="text" name="lokasi_asal" id="lokasi_asal" placeholder="Lokasi Asal">
<input type="text" name="lokasi_tujuan" id="lokasi_tujuan" placeholder="Lokasi Tujuan">

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

const BASE_URL = window.location.pathname.includes('Suppliers.php')
  ? ''
  : 'sidebar/Suppliers.php';

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
    title.innerText = 'Tambah Supplier';
    openForm();
}

// ===== EDIT =====
window.openEdit = function(d){
    console.log(d); // 🔥 DEBUG

    document.getElementById('action').value = 'edit';
    document.getElementById('id').value = d.id_supplier || d.id_Supplier;

    document.getElementById('nama_supplier').value = d.Nama_Supplier;
    document.getElementById('lokasi_asal').value = d.Lokasi_Asal;
    document.getElementById('lokasi_tujuan').value = d.Lokasi_Tujuan;

    title.innerText = 'Edit Supplier';
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