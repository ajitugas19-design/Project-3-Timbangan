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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Materials</title>

<style>
:root {
  --primary: #22c55e;
  --danger: #ef4444;
  --warning: #eab308;
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
.table-container{
  background:white;
  border-radius:12px;
  margin-top:20px;
  overflow:hidden;
  box-shadow:var(--shadow);
}

table{
  width:100%;
  border-collapse:collapse;
}

th{
  background:#374151;
  color:white;
  padding:12px;
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
  padding:8px 14px;
  cursor:pointer;
  border-radius:8px;
  font-size:14px;
  transition:all 0.2s;
  margin-right:5px;
}
.edit:hover{background:#d97706;}

.hapus{
  background:#ef4444;
  color:white;
  border:none;
  padding:8px 14px;
  cursor:pointer;
  border-radius:8px;
  font-size:14px;
  transition:all 0.2s;
}
.hapus:hover{background:#dc2626;}

/* RESPONSIVE TABLE */
@media (max-width:768px) {
  table, thead, tbody, th, td, tr {display:block;}
  thead tr {position:absolute;top:-9999px;left:-9999px;}
  tr {border:1px solid #e5e7eb;margin-bottom:10px;border-radius:10px;padding:15px;background:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
  td {border:none;position:relative;padding-left:50%;text-align:right;}
  td:before {content:attr(data-label);position:absolute;left:10px;width:45%;font-weight:bold;color:#374151;}
  td:nth-of-type(1):before {content:"No: ";}
  td:nth-of-type(2):before {content:"Kode: ";}
  td:nth-of-type(3):before {content:"Nama: ";}
  td:nth-of-type(4):before {content:"Total Transaksi: ";}
  td:nth-of-type(5):before {content:"Aksi: ";}
}

/* FORM */
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
.form-slide {
  z-index: 1002;
}
.form-overlay {
  z-index: 1001;
}

.form-overlay{
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
  padding:12px;
  width:100%;
  border:none;
  border-radius:8px;
  margin-top:10px;
  font-size:16px;
  cursor:pointer;
  transition:all 0.3s;
}
.btn-cancel:hover {background:#4b5563; transform:translateY(-1px);}
.btn-save:hover {background:#059669; transform:translateY(-1px);}
</style>
</head>

<body>

<button class="btn" onclick="openForm()">+ Tambah Material</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>Kode</th>
<th>Nama Material</th>
<th>Total Transaksi</th>
<th>Opsi</th>
</tr>
</thead>
<tbody id="materialsList">
<tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">📊 Loading materials...</td></tr>
</tbody>
</table>
</div>

<!-- OVERLAY -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>

<!-- FORM -->
<div class="form-slide" id="formSlide">
<h3 id="formTitle">Tambah Material</h3>

<input type="text" id="kode" placeholder="Kode Material">
<input type="text" id="nama" placeholder="Nama Material">

<button class="btn-save" onclick="simpanMaterial()">Simpan</button>
<button class="btn-cancel" onclick="closeForm()">Batal</button>
</div>

<script>
let editId = null;

// LOAD DATA
async function loadMaterials(){
  const res = await fetch('../api/materials.php?action=list');
  const result = await res.json();

  let html = "";
  let no = 1;

  if(result.status==='success') result.data.forEach(m=>{
    html += `
    <tr>
      <td>${no++}</td>
  <td><strong>${m.kode}</strong></td>
  <td>${m.nama}</td>
      <td><span style="color:#10b981;font-weight:bold;">📦 0 Transaksi<br><small>(Belum ada data transaksi)</small></span></td>
      <td>
        <button class="edit" onclick="editMaterial(${m.id})">✏️ Edit</button>
        <button class="hapus" onclick="hapusMaterial(${m.id})">🗑️ Hapus</button>
      </td>

    </tr>`;
  });

  document.getElementById("materialsList").innerHTML = html || "<tr><td colspan='5' style='text-align:center;padding:40px;color:#6b7280;'>📭 Data materials kosong. <button class='btn' onclick='openForm()' style='margin-top:10px;'>Tambah yang pertama!</button></td></tr>";
}

// OPEN FORM
function openForm(){
  document.getElementById("formSlide").classList.add("active");
  document.getElementById("overlay").classList.add("active");
  document.getElementById("kode").value="";
  document.getElementById("nama").value="";
  document.getElementById("formTitle").innerText="Tambah Material";
  editId = null;
}

// CLOSE
function closeForm(){
  document.getElementById("formSlide").classList.remove("active");
  document.getElementById("overlay").classList.remove("active");
}

// EDIT
async function editMaterial(id){
  editId = id;

  const res = await fetch('../api/materials.php?action=get&id='+id);
  const r = await res.json();

  openForm();

  document.getElementById("formTitle").innerText="Edit Material";
document.getElementById("kode").value=r.data.kode;
document.getElementById("nama").value=r.data.nama;
}

// DELETE
async function hapusMaterial(id){
  if(confirm("Hapus material?")){
    await fetch('../api/materials.php?action=delete',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id})
    });
    loadMaterials();
  }
}

// SIMPAN
async function simpanMaterial(){
  const kode = document.getElementById("kode").value;
  const nama = document.getElementById("nama").value;

  let action = editId ? 'edit' : 'add';

  await fetch('../api/materials.php?action='+action,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      id: editId,
      kode:kode,
      nama:nama
    })
  });

  closeForm();
  loadMaterials();
  alert("Berhasil disimpan!");
}

// INIT
loadMaterials();
</script>

</body>
</html>

