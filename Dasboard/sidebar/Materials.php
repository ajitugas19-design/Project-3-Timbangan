<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:#ef4444;background:#fef2f2;padding:20px;border-radius:12px;text-align:center;margin:20px;font-size:18px;border:2px solid #fecaca;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Material</title>

<style>
:root {
  --primary: #22c55e;
  --danger: #ef4444;
  --dark: #374151;
  --light: #f3f4f6;
  --shadow: 0 4px 6px rgba(0,0,0,0.1);
}

body {
  font-family: Arial;
  background: linear-gradient(135deg,#f3f4f6,#e5e7eb);
  padding:20px;
}

/* BUTTON */
.btn{
  background:var(--primary);
  color:white;
  padding:10px 20px;
  border:none;
  border-radius:10px;
  cursor:pointer;
}

/* TABLE */
.table-container {
  background:white;
  border-radius:12px;
  margin-top:20px;
  padding:10px;
  box-shadow: var(--shadow);
}

table{
  width:100%;
  border-collapse:collapse;
}

th{
 background:#374151;
 color:white;
 padding:12px;
 text-align:left;
}

td{
 padding:12px;
 border-bottom:1px solid #eee;
}

/* BUTTON ACTION */
.edit{
 background:#f59e0b;
 color:white;
 border:none;
 padding:8px14px;
 cursor:pointer;
 border-radius:8px;
 margin-right:5px;
}

.hapus{
 background:#ef4444;
 color:white;
 border:none;
 padding:8px14px;
 cursor:pointer;
 border-radius:8px;
}

/* FORM SLIDE */
.form-slide{
 position:fixed;
 right:-400px;
 top:0;
 width:350px;
 height:100%;
 background:white;
 padding:20px;
 transition:0.3s;
 box-shadow:-5px 0 20px rgba(0,0,0,0.2);
}

.form-slide.active{
 right:0;
}

.form-overlay {
 position:fixed;
 top:0;
 left:0;
 width:100%;
 height:100%;
 background:rgba(0,0,0,0.5);
 display:none;
}

.form-overlay.active{
 display:block;
}

input{
 width:100%;
 padding:10px;
 margin-bottom:10px;
}

.btn-save{
 background:green;
 color:white;
 padding:10px;
 width:100%;
 border:none;
}

.btn-cancel{
 background:#6b7280;
 color:white;
 padding:10px;
 width:100%;
 border:none;
 margin-top:10px;
}
</style>
</head>

<body>

<h2>Data Material</h2>

<button class="btn" onclick="openForm()">+ Tambah Material</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>KODE</th>
<th>NAMA</th>
<th>Opsi</th>
</tr>
</thead>
<tbody id="materialsList">
<tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
</tbody>
</table>
</div>

<!-- OVERLAY -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>

<!-- FORM -->
<div class="form-slide" id="formSlide">
<h3 id="formTitle">Tambah Material</h3>
<input type="text" id="kode" placeholder="KODE Material">
<input type="text" id="nama" placeholder="Nama Material">
<button class="btn-save" onclick="simpanMaterial()">Simpan</button>
<button class="btn-cancel" onclick="closeForm()">Batal</button>
</div>

<script>
let editId = null;

// LOAD DATA
async function loadMaterials() {
  try {
    const res = await fetch('../api/materials.php?action=list');
    const result = await res.json();

    let html = '';
    let no = 1;

    if(result.data && result.data.length > 0){
      result.data.forEach(m => {
        html += `
        <tr>
          <td>${no++}</td>
          <td>${m.kode}</td>
          <td>${m.nama}</td>
          <td>
            <button class="edit" onclick="editMaterial(${m.id})">Edit</button>
            <button class="hapus" onclick="hapusMaterial(${m.id})">Hapus</button>
          </td>
        </tr>`;
      });
    } else {
      html = `<tr><td colspan="4" style="text-align:center;">Data kosong</td></tr>`;
    }

    document.getElementById('materialsList').innerHTML = html;

  } catch(e) {
    document.getElementById('materialsList').innerHTML =
      `<tr><td colspan="4" style="color:red;">Error load data</td></tr>`;
  }
}

// OPEN FORM
function openForm() {
  document.getElementById('formSlide').classList.add('active');
  document.getElementById('overlay').classList.add('active');
  document.getElementById('kode').value = '';
  document.getElementById('nama').value = '';
  document.getElementById('formTitle').innerText = 'Tambah Material';
  editId = null;
}

// CLOSE FORM
function closeForm() {
  document.getElementById('formSlide').classList.remove('active');
  document.getElementById('overlay').classList.remove('active');
}

// EDIT
async function editMaterial(id) {
  const res = await fetch(`../api/materials.php?action=get&id=${id}`);
  const r = await res.json();

  if (r.data) {
    editId = id;
    openForm();
    document.getElementById('formTitle').innerText = 'Edit Material';
    document.getElementById('kode').value = r.data.kode;
    document.getElementById('nama').value = r.data.nama;
  }
}

// DELETE
async function hapusMaterial(id) {
  if(confirm('Hapus data?')){
    await fetch('../api/materials.php?action=delete', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id})
    });
    loadMaterials();
  }
}

// SAVE
async function simpanMaterial() {
  const kode = document.getElementById('kode').value;
  const nama = document.getElementById('nama').value;

  if(!kode || !nama){
    alert('Harus diisi!');
    return;
  }

  const action = editId ? 'edit' : 'add';

  await fetch(`../api/materials.php?action=${action}`, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id:editId, kode, nama})
  });

  closeForm();
  loadMaterials();
}

// INIT
loadMaterials();
</script>

</body>
</html>