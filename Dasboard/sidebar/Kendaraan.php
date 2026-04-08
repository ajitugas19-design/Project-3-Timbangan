<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error" style="color:#ef4444;background:#fef2f2;padding:20px;border-radius:12px;text-align:center;margin:20px;font-size:18px;border:2px solid #fecaca;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kendaraan</title>

<style>
:root {
  --primary: #22c55e;
  --danger: #ef4444;
  --dark: #374151;
  --shadow: 0 4px 6px rgba(0,0,0,0.1);
}

body { font-family: Arial; background: linear-gradient(135deg,#f3f4f6,#e5e7eb); padding:20px; }

.btn{ background:var(--primary); color:white; padding:10px 20px; border:none; border-radius:10px; cursor:pointer; }

.table-container{ background:white; border-radius:12px; margin-top:20px; overflow:hidden; box-shadow:var(--shadow); }

table{ width:100%; border-collapse:collapse; }
th{ background:#374151; color:white; padding:12px; }
td{ padding:12px; border-bottom:1px solid #eee; }

.edit{ background:#f59e0b; color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:8px; margin-right:5px; }
.hapus{ background:var(--danger); color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:8px; }

.form-slide{ position:fixed; right:-400px; top:0; width:350px; height:100%; background:white; padding:20px; transition:0.3s; box-shadow:-5px 0 20px rgba(0,0,0,0.2); }
.form-slide.active{ right:0; }
.form-overlay{ position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; }
.form-overlay.active{ display:block; }

input{ width:100%; padding:10px; margin-bottom:10px; }
.btn-save{ background:green; color:white; padding:10px; width:100%; border:none; }
.btn-cancel{ background:#6b7280; color:white; padding:12px; width:100%; border:none; margin-top:10px; }
</style>
</head>

<body>

<button class="btn" onclick="openForm()">+ Tambah Kendaraan</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>Nopol</th>
<th>Sopir</th>
<th>Aksi</th>
</tr>
</thead>
<tbody id="kendaraanList">
</tbody>
</table>
</div>

<!-- FORM -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>
<div class="form-slide" id="formSlide">
<h3 id="formTitle">Tambah Kendaraan</h3>
<input type="text" id="nopol" placeholder="No Polisi">
<input type="text" id="sopir" placeholder="Nama Sopir">
<button class="btn-save" onclick="simpan()">Simpan</button>
<button class="btn-cancel" onclick="closeForm()">Batal</button>
</div>

<script>
let editId = null;

async function showApiError(error, retryFunc) {
  console.error('API Error:', error);
  const tbody = document.getElementById('kendaraanList');
  tbody.innerHTML = `
    <tr>
      <td colspan="4" style="text-align:center;padding:40px;color:#dc2626;background:#fee2e2;border:2px solid #fecaca;border-radius:12px;">
        ❌ Error: ${error.message || error}<br>
        <small>Check console (F12). DB/API issue?</small><br><br>
        <button onclick="${retryFunc ? retryFunc + '()' : 'loadKendaraan()'}" style="background:#3b82f6;color:white;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;">🔄 Retry</button>
      </td>
    </tr>`;
}

async function loadKendaraan(){
  try {
    const res = await fetch('../api/kendaraan.php?action=list');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const result = await res.json();
    if (!result || !result.data) throw new Error('Invalid API response');
    
    let html = '';
    let no = 1;
    result.data.forEach(k => {
      html += `
        <tr>
          <td>${no++}</td>
          <td><strong>${k.Nopol}</strong></td>
          <td>${k.Sopir}</td>
          <td>
            <button class="edit" onclick="editKendaraan(${k.id_Kendaraan})">Edit</button>
            <button class="hapus" onclick="hapusKendaraan(${k.id_Kendaraan})">Hapus</button>
          </td>
        </tr>`;
    });
    
    document.getElementById('kendaraanList').innerHTML = html || '<tr><td colspan="4" style="text-align:center;color:#6b7280;padding:20px;">📭 Data kosong</td></tr>';
  } catch (error) {
    showApiError(error, 'loadKendaraan');
  }
}

function openForm(){
  document.getElementById('formSlide').classList.add('active');
  document.getElementById('overlay').classList.add('active');
  document.getElementById('nopol').value = '';
  document.getElementById('sopir').value = '';
  document.getElementById('formTitle').textContent = 'Tambah Kendaraan';
  editId = null;
}

function closeForm(){
  document.getElementById('formSlide').classList.remove('active');
  document.getElementById('overlay').classList.remove('active');
}

async function editKendaraan(id){
  const res = await fetch(`../api/kendaraan.php?action=get&id=${id}`);
  const data = await res.json();
  
  if(data){
    openForm();
    document.getElementById('formTitle').textContent = 'Edit Kendaraan';
    document.getElementById('nopol').value = data.Nopol;
    document.getElementById('sopir').value = data.Sopir;
    editId = id;
  }
}

async function hapusKendaraan(id){
  if(confirm('Hapus kendaraan?')){
    await fetch('../api/kendaraan.php?action=delete', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({id})
    });
    loadKendaraan();
  }
}

async function simpan(){
  const nopol = document.getElementById('nopol').value.trim();
  const sopir = document.getElementById('sopir').value.trim();
  
  if(!nopol || !sopir){
    alert('Nopol dan sopir wajib!');
    return;
  }
  
  const action = editId ? 'edit' : 'add';
  
  await fetch(`../api/kendaraan.php?action=${action}`, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      id: editId,
      Nopol: nopol,
      Sopir: sopir
    })
  });
  
  closeForm();
  loadKendaraan();
  alert('Berhasil!');
}

// Preload data - no loading
loadKendaraan();
</script>

</body>
</html>

<?php
session_start();
require_once '../../config.php';
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

// Handle actions
$message = '';
if ($_POST) {
    $action = $_POST['action'] ?? '';
    try {
        switch($action) {
            case 'add':
                $nopol = trim($_POST['nopol'] ?? '');
                $sopir = trim($_POST['sopir'] ?? '');
                if (empty($nopol) || empty($sopir)) throw new Exception('Nopol dan Sopir wajib diisi');
                $stmt = $pdo->prepare("INSERT INTO kendaraan (Nopol, Sopir) VALUES (?, ?)");
                $stmt->execute([$nopol, $sopir]);
                $message = '✅ Kendaraan baru berhasil ditambahkan!';
                break;
            
            case 'edit':
                $id = (int)$_POST['id'];
                $nopol = trim($_POST['nopol'] ?? '');
                $sopir = trim($_POST['sopir'] ?? '');
                if (empty($id) || empty($nopol) || empty($sopir)) throw new Exception('ID, Nopol, Sopir wajib diisi');
                $stmt = $pdo->prepare("UPDATE kendaraan SET Nopol = ?, Sopir = ? WHERE id_Kendaraan = ?");
                $stmt->execute([$nopol, $sopir, $id]);
                $message = '✅ Kendaraan berhasil diupdate!';
                break;
            
            case 'delete':
                $id = (int)$_POST['id'];
                if (empty($id)) throw new Exception('ID wajib diisi');
                $stmt = $pdo->prepare("DELETE FROM kendaraan WHERE id_Kendaraan = ?");
                $stmt->execute([$id]);
                $message = '✅ Kendaraan berhasil dihapus!';
                break;
        }
    } catch (Exception $e) {
        $message = '❌ Error: ' . $e->getMessage();
    }
}

// Load data
$stmt = $pdo->query("SELECT *, '' as Jenis_Kendaraan FROM kendaraan ORDER BY Nopol");
$kendaraan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT *, '' as Jenis_Kendaraan FROM kendaraan WHERE id_Kendaraan = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kendaraan</title>
<style>
:root {
  --primary: #22c55e;
  --danger: #ef4444;
  --dark: #374151;
  --shadow: 0 4px 6px rgba(0,0,0,0.1);
}
body { font-family: Arial; background: linear-gradient(135deg,#f3f4f6,#e5e7eb); padding:20px; }
.btn{ background:var(--primary); color:white; padding:10px 20px; border:none; border-radius:10px; cursor:pointer; }
.table-container{ background:white; border-radius:12px; margin-top:20px; overflow:hidden; box-shadow:var(--shadow); }
table{ width:100%; border-collapse:collapse; }
th{ background:#374151; color:white; padding:12px; }
td{ padding:12px; border-bottom:1px solid #eee; }
.edit{ background:#f59e0b; color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:8px; margin-right:5px; }
.hapus{ background:var(--danger); color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:8px; }
.form-slide{ position:fixed; right:-400px; top:0; width:350px; height:100%; background:white; padding:20px; transition:0.3s; box-shadow:-5px 0 20px rgba(0,0,0,0.2); }
.form-slide.active{ right:0; }
.form-overlay{ position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; }
.form-overlay.active{ display:block; }
input{ width:100%; padding:10px; margin-bottom:10px; }
.btn-save{ background:green; color:white; padding:10px; width:100%; border:none; }
.btn-cancel{ background:#6b7280; color:white; padding:12px; width:100%; border:none; margin-top:10px; }
.message{ padding:15px; margin:20px 0; border-radius:12px; text-align:center; font-weight:bold; }
.success{ background:#d1fae5; color:#065f46; border:2px solid #a7f3d0; }
.error{ background:#fef2f2; color:#991b1b; border:2px solid #fecaca; }
@media (max-width:768px) {
  table, thead, tbody, th, td, tr {display:block;}
  thead tr {position:absolute;top:-9999px;left:-9999px;}
  tr {border:1px solid #e5e7eb;margin-bottom:10px;border-radius:10px;padding:15px;background:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
  td {border:none;position:relative;padding-left:50%;text-align:right;}
  td:before {content:attr(data-label);position:absolute;left:10px;width:45%;font-weight:bold;color:#374151;}
  td:nth-of-type(1):before {content:"No: ";}
  td:nth-of-type(2):before {content:"Nopol: ";}
  td:nth-of-type(3):before {content:"Sopir: ";}
  td:nth-of-type(4):before {content:"Aksi: ";}
}
</style>
</head>
<body>
<?php if ($message): ?>
<div class="message <?= strpos($message, '✅') === 0 ? 'success' : 'error' ?>">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<button class="btn" onclick="openForm()" <?= $edit_data ? 'style="display:none;"' : '' ?>>+ Tambah Kendaraan</button>
<?php if ($edit_data): ?>
<button class="btn" style="background:orange;" onclick="window.location.href='?';">Kembali ke List</button>
<?php endif; ?>

<?php if (!$edit_data): ?>
<div class="table-container">
<table>
<thead>
<tr>
<th>No</th><th>Nopol</th><th>Sopir</th><th>Aksi</th>
</tr>
</thead>
<tbody>
<?php if (empty($kendaraan)): ?>
<tr><td colspan="4" style="text-align:center;padding:40px;color:#6b7280;">📭 Data kosong. <button class="btn" onclick="openForm()" style="margin-top:10px;">Tambah yang pertama!</button></td></tr>
<?php else: ?>
<?php $no = 1; foreach($kendaraan as $k): ?>
<tr>
<td data-label="No"><?= $no++ ?></td>
<td data-label="Nopol"><strong><?= htmlspecialchars($k['Nopol']) ?></strong></td>
<td data-label="Sopir"><?= htmlspecialchars($k['Sopir']) ?></td>
<td data-label="Aksi">
    <button class="edit" onclick="editKendaraan(<?= $k['id_Kendaraan'] ?>)">Edit</button>
    <button class="hapus" onclick="hapusKendaraan(<?= $k['id_Kendaraan'] ?>)">Hapus</button>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>
<div class="form-slide" id="formSlide">
<h3 id="formTitle"><?= $edit_data ? 'Edit Kendaraan' : 'Tambah Kendaraan' ?></h3>
<form method="POST" id="kendaraanForm">
<input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id_Kendaraan'] ?>"><?php endif; ?>
<input type="text" name="nopol" id="nopol" placeholder="No Polisi" value="<?= htmlspecialchars($edit_data['Nopol'] ?? '') ?>" required>
<input type="text" name="sopir" id="sopir" placeholder="Nama Sopir" value="<?= htmlspecialchars($edit_data['Sopir'] ?? '') ?>" required>
<button type="submit" class="btn-save">Simpan</button>
<button type="button" class="btn-cancel" onclick="closeForm()">Batal</button>
</form>
</div>

<script>
function openForm() {
    document.getElementById('formSlide').classList.add('active');
    document.getElementById('overlay').classList.add('active');
}

function closeForm() {
    document.getElementById('formSlide').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
    window.location.href = '?';
}

function editKendaraan(id) {
    window.location.href = '?edit=' + id;
}

function hapusKendaraan(id) {
    if (confirm('Hapus kendaraan?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

<?php if ($edit_data): ?>
openForm();
<?php endif; ?>
</script>
</body>
</html>
