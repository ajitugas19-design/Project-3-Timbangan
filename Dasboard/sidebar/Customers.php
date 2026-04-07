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
<title>Customers</title>

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
  background:orange;
  border:none;
  padding:6px 12px;
  cursor:pointer;
  border-radius:6px;
}

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
.edit{background:#f59e0b;color:white;border:none;padding:8px 14px;cursor:pointer;border-radius:8px;font-size:14px;transition:all 0.2s;margin-right:5px;}
.edit:hover{background:#d97706;}

/* RESPONSIVE TABLE */
@media (max-width:768px) {
  table, thead, tbody, th, td, tr {display:block;}
  thead tr {position:absolute;top:-9999px;left:-9999px;}
  tr {border:1px solid #e5e7eb;margin-bottom:10px;border-radius:10px;padding:15px;background:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
  td {border:none;position:relative;padding-left:50%;text-align:right;}
  td:before {content:attr(data-label);position:absolute;left:10px;width:45%;font-weight:bold;color:#374151;}
  td:nth-of-type(1):before {content:"No: ";}
  td:nth-of-type(2):before {content:"Nama: ";}
  td:nth-of-type(3):before {content:"Keterangan: ";}
  td:nth-of-type(4):before {content:"Pesanan: ";}
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

<button class="btn" onclick="openForm()">+ Tambah</button>

<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
  <th>Nama Customer</th>
  <th>Keterangan</th>
  <th>Total Pesanan</th>
  <th>Opsi</th>
</tr>
</thead>
<tbody id="userList">
<tr><td colspan="5" style="text-align:center;padding:40px;color:#6b7280;"><i>Loading data customers...</i></td></tr>
</tbody>
</table>
</div>

<!-- OVERLAY -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>

<!-- FORM -->
<div class="form-slide" id="formSlide">
<h3 id="formTitle">Tambah Customer</h3>

<input type="text" id="nama" placeholder="Nama">
<input type="text" id="keterangan" placeholder="Keterangan">

<button class="btn-save" onclick="simpanCustomer()">Simpan</button>
<button class="btn-cancel" onclick="closeForm()">Batal</button>
</div>

<script>
let editId = null;

// LOAD DATA
async function loadCustomers(){
  const res = await fetch('../api/customers.php?action=list');
  const result = await res.json();

  let html = "";
  let no = 1;

  result.data.forEach(c=>{
    html += `
    <tr>
      <td>${no++}</td>
      <td><strong>${c.nama}</strong></td>
      <td>${c.keterangan || '-'}</td>
      <td><span style="color:#10b981;font-weight:bold;">📦 0 Pesanan<br><small>(Belum ada data pesanan)</small></span></td>
      <td>
        <button class="edit" onclick="editCustomer(${c.id})">✏️ Edit</button>
        <button class="hapus" onclick="hapusCustomer(${c.id})">🗑️ Hapus</button>
      </td>
    </tr>`;
  });

  document.getElementById("userList").innerHTML = html || "<tr><td colspan='5' style='text-align:center;padding:40px;color:#6b7280;'>📭 Data customers kosong. <button class='btn' onclick='openForm()' style='margin-top:10px;'>Tambah yang pertama!</button></td></tr>";
}

// OPEN FORM
function openForm(){
  document.getElementById("formSlide").classList.add("active");
  document.getElementById("overlay").classList.add("active");
  document.getElementById("nama").value="";
  document.getElementById("keterangan").value="";
  editId = null;
}

// CLOSE
function closeForm(){
  document.getElementById("formSlide").classList.remove("active");
  document.getElementById("overlay").classList.remove("active");
}

// EDIT
async function editCustomer(id){
  editId = id;

  const res = await fetch('../api/customers.php?action=get&id='+id);
  const r = await res.json();

  openForm();

  document.getElementById("formTitle").innerText="Edit Customer";
  document.getElementById("nama").value=r.data.nama;
  document.getElementById("keterangan").value=r.data.keterangan;
}

// DELETE
async function hapusCustomer(id){
  if(confirm("Hapus?")){
    await fetch('../api/customers.php?action=delete',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id})
    });
    loadCustomers();
  }
}

// SIMPAN
async function simpanCustomer(){
  const nama = document.getElementById("nama").value;
  const ket = document.getElementById("keterangan").value;

  let action = editId ? 'edit' : 'add';

  await fetch('../api/customers.php?action='+action,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      id: editId,
      nama:nama,
      keterangan:ket
    })
  });

  closeForm();
  loadCustomers();
  alert("Berhasil disimpan!");
}

// INIT
loadCustomers();
</script>

</body>
</html>