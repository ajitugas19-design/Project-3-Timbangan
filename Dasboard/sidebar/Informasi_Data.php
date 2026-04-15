<?php 
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;margin:50px;">⚠️ Login dulu!</div>';
    exit;
}

// ================= LOAD DATA =================
$tgl = $_GET['tgl'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($tgl) {
    $where .= " AND DATE(wi.tanggal_in)=?";
    $params[] = $tgl;
}

if ($search) {
    $where .= " AND (
        t.no_record LIKE ? OR 
        k.Sopir LIKE ? OR 
        k.Nopol LIKE ? OR
        s.Nama_Supplier LIKE ? OR
        m.Material LIKE ? OR
        c.Customers LIKE ?
    )";
    for ($i=0;$i<6;$i++) $params[]="%$search%";
}

$stmt = $pdo->prepare("
SELECT 
t.id_transaksi,t.no_record,
k.Sopir,k.Nopol,
s.Nama_Supplier,
m.Material,
c.Customers,
wi.tanggal_in,wo.tanggal_out,
t.bruto,t.tara,t.netto
FROM transaksi t
LEFT JOIN waktu_in wi ON t.id_in=wi.id_in
LEFT JOIN waktu_out wo ON t.id_out=wo.id_out
LEFT JOIN kendaraan k ON t.id_kendaraan=k.id_Kendaraan
LEFT JOIN supplier s ON t.id_supplier=s.id_Supplier
LEFT JOIN material m ON t.id_material=m.id_Material
LEFT JOIN customers c ON t.id_customers=c.id_Customers
$where
ORDER BY t.id_transaksi DESC
");

$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
body{
  font-family:Segoe UI;
  background:#f1f5f9;
  margin:0;
}

/* 🔥 FIX LAYOUT */
.container{
  width:100%;
  padding:20px;
}

/* HEADER */
.header{
  background:#1f2937;
  color:white;
  padding:15px;
  border-radius:10px;
  font-weight:bold;
}

/* FILTER */
.controls{
  margin-top:10px;
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

input,button{
  padding:8px;
  border-radius:6px;
  border:1px solid #ccc;
}

.btn{
  background:#3b82f6;
  color:white;
  border:none;
  cursor:pointer;
}

/* TABLE */
.table-container{
  margin-top:15px;
  background:white;
  border-radius:10px;
  overflow:hidden;
}

table{
  width:100%;
  border-collapse:collapse;
}

th, td{
  padding:10px;
  border-bottom:1px solid #eee;
  text-align:center;
}

th{
  background:#1f2937;
  color:white;
}

/* BUTTON */
.btn-edit{
  background:#f59e0b;
  color:white;
  padding:5px 8px;
  border-radius:5px;
  text-decoration:none;
}

.btn-danger{
  background:#ef4444;
  color:white;
  border:none;
  padding:5px 8px;
  border-radius:5px;
  cursor:pointer;
}

.netto{
  color:#10b981;
  font-weight:bold;
}

/* MESSAGE */
.message{
  position:fixed;
  top:20px;
  right:20px;
  padding:10px;
  border-radius:8px;
  color:white;
}
.success{background:#10b981;}
.error{background:#ef4444;}
</style>

<div class="container">

<div class="header">📊 Data Transaksi</div>

<!-- FILTER -->
<form method="GET" class="controls">
<input type="date" name="tgl" value="<?= $tgl ?>">
<input type="text" name="search" placeholder="Cari..." value="<?= $search ?>">
<button class="btn">Cari</button>
</form>

<!-- TABLE -->
<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th><th>No</th><th>Sopir</th><th>Nopol</th>
<th>Supplier</th><th>Material</th><th>Customer</th>
<th>Masuk</th><th>Keluar</th>
<th>Bruto</th><th>Tara</th><th>Netto</th><th>Aksi</th>
</tr>
</thead>

<tbody id="tbody">
<?php foreach($data as $d): ?>
<tr>
<td><?= $d['id_transaksi'] ?></td>
<td><?= $d['no_record'] ?></td>
<td><?= $d['Sopir'] ?></td>
<td><?= $d['Nopol'] ?></td>
<td><?= $d['Nama_Supplier'] ?></td>
<td><?= $d['Material'] ?></td>
<td><?= $d['Customers'] ?></td>

<td><?= $d['tanggal_in'] ?></td>
<td><?= $d['tanggal_out'] ?></td>

<td><?= number_format($d['bruto']) ?></td>
<td><?= number_format($d['tara']) ?></td>
<td class="netto"><?= number_format($d['netto']/1000,2) ?> Ton</td>

<td>
<a href="edit.php?id=<?= $d['id_transaksi'] ?>" class="btn-edit">✏️</a>
<button class="btn-danger" onclick="hapus(<?= $d['id_transaksi'] ?>)">🗑️</button>
</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>

<script>
const BASE_URL = 'sidebar/Transaksi.php';

// DELETE AJAX
function hapus(id){
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

// RELOAD TABLE
function loadTable(){
    fetch(BASE_URL)
    .then(r=>r.text())
    .then(html=>{
        const doc = new DOMParser().parseFromString(html,'text/html');
        const newTbody = doc.getElementById('tbody');
        if(newTbody){
            document.getElementById('tbody').innerHTML = newTbody.innerHTML;
        }
    });
}

// MESSAGE
function show(msg, ok=true){
    let d=document.createElement('div');
    d.className='message '+(ok?'success':'error');
    d.innerText=msg;
    document.body.appendChild(d);
    setTimeout(()=>d.remove(),3000);
}
</script>