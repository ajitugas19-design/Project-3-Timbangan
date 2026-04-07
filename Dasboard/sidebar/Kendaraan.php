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
<tr><td colspan="4" style="text-align:center;padding:20px;color:#6b7280;">🚗 Loading kendaraan...</td></tr>
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

async function loadKendaraan(){
  const res = await fetch('../api/kendaraan.php?action=list');
  const result = await res.json();
  
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
  
  document.getElementById('kendaraanList').innerHTML = html || '<tr><td colspan="4" style="text-align:center;">Data kosong</td></tr>';
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

// INIT
loadKendaraan();
</script>

</body>
</html>

