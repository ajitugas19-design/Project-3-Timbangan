<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:#fff;background:#ef4444;padding:15px;text-align:center;border-radius:10px;">
    ⚠️ Silakan login terlebih dahulu!
    </div>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Data</title>

<style>
body {
    font-family: 'Segoe UI', Arial;
    background: #f4f6fb;
    margin: 0;
    padding: 25px;
    color: #1e293b;
}

h2 { margin-bottom: 20px; }

/* CARD */
.card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    padding: 20px;
    margin-bottom: 15px;
}

/* FILTER */
.filter {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: end;
}

input {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

button {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    color: white;
    background: #2563eb;
}

button:hover { transform: scale(1.05); }

/* RANGE */
.range {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* LAYOUT */
.container {
    display: flex;
    gap: 20px;
}

/* TABLE */
.left { flex: 3; }
.right { flex: 1; }

.table-box {
    overflow: auto;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #2563eb;
    color: white;
    padding: 10px;
    position: sticky;
    top: 0;
}

td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover { background: #f1f5f9; }

/* FOOTER */
.footer {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

/* PAGINATION */
.pagination span {
    margin: 3px;
    padding: 6px 10px;
    background: #e2e8f0;
    border-radius: 5px;
    cursor: pointer;
}

.pagination .active {
    background: #2563eb;
    color: white;
}

/* EXPORT */
.print button {
    width: 100%;
    margin-top: 10px;
}

.excel { background: #22c55e; }
.word { background: #334155; }
.pdf { background: #ef4444; }

/* LOADING */
.loading {
    text-align: center;
    padding: 20px;
    color: #888;
}

@media(max-width:900px){
    .container { flex-direction: column; }
}
</style>
</head>

<body>

<h2>📊 Laporan Data</h2>

<!-- FILTER -->
<div class="card filter">
    <div>
        <small>Dari</small><br>
        <input type="date" id="dari">
    </div>
    <div>
        <small>Sampai</small><br>
        <input type="date" id="sampai">
    </div>
    <button onclick="tampilkan()">Tampilkan</button>
</div>

<!-- RANGE -->
<div class="card range">
    <span>Tampilkan:</span>
    <input type="range" min="1" max="100" value="10" id="rangeData" oninput="updateRange()">
    <span id="rangeValue">1-10</span>
</div>

<div class="container">

<!-- LEFT -->
<div class="left">

<div class="card table-box">
<table>
<thead>
<tr>
<th>No</th>
<th>Sopir</th>
<th>Kendaraan</th>
<th>Nopol</th>
<th>Tgl In</th>
<th>Jam In</th>
<th>Tgl Out</th>
<th>Jam Out</th>
<th>Muatan</th>
<th>Bruto</th>
<th>Tara</th>
<th>Netto</th>
<th>Keterangan</th>
</tr>
</thead>
<tbody id="tbody">
<tr><td colspan="13" class="loading">Loading...</td></tr>
</tbody>
</table>
</div>

<div class="footer">
<div>Jumlah Data: <span id="jumlah">0</span></div>
<div class="pagination" id="pagination"></div>
</div>

</div>

<!-- RIGHT -->
<div class="right card">
<h3>Filter</h3>
<p>Dari: <span id="ketDari">-</span></p>
<p>Sampai: <span id="ketSampai">-</span></p>

<h3>Export</h3>
<div class="print">
<button class="excel" onclick="exportExcel()">Excel</button>
<button class="word" onclick="exportWord()">Word</button>
<button class="pdf" onclick="exportPDF()">PDF</button>
</div>
</div>

</div>

<script>
let allData = [];
let currentPage = 1;
let perPage = 10;

// LOAD DATA
async function loadLaporan(dari='', sampai=''){
    document.getElementById("tbody").innerHTML = '<tr><td colspan="13" class="loading">Loading...</td></tr>';
    
    try {
        const res = await fetch(`../api/laporan.php?dari=${dari}&sampai=${sampai}`);
        const data = await res.json();
        allData = data;
        renderTable();
    } catch {
        document.getElementById("tbody").innerHTML = '<tr><td colspan="13">Error load data</td></tr>';
    }
}

// RANGE
function updateRange(){
    perPage = document.getElementById("rangeData").value;
    document.getElementById("rangeValue").innerText = `1-${perPage}`;
    renderTable();
}

// FILTER
function tampilkan(){
    let dari = document.getElementById("dari").value;
    let sampai = document.getElementById("sampai").value;

    document.getElementById("ketDari").innerText = dari || "-";
    document.getElementById("ketSampai").innerText = sampai || "-";

    loadLaporan(dari, sampai);
}

// TABLE
function renderTable(){
    let start = (currentPage-1)*perPage;
    let data = allData.slice(start, start+perPage);

    let html = data.map((row,i)=>`
    <tr>
        <td>${start+i+1}</td>
        <td>${row.sopir||'-'}</td>
        <td>${row.jenis_kendaraan||'-'}</td>
        <td>${row.nopol||'-'}</td>
        <td>${row.tanggal_in||'-'}</td>
        <td>${row.jam_in||'-'}</td>
        <td>${row.tanggal_out||'-'}</td>
        <td>${row.jam_out||'-'}</td>
        <td>${row.isi_muatan||'-'}</td>
        <td>${format(row.bruto)}</td>
        <td>${format(row.tara)}</td>
        <td>${format(row.netto)}</td>
        <td>${row.keterangan||'-'}</td>
    </tr>`).join('');

    document.getElementById("tbody").innerHTML = html || '<tr><td colspan="13">Tidak ada data</td></tr>';
    document.getElementById("jumlah").innerText = allData.length;

    renderPagination();
}

// FORMAT ANGKA
function format(num){
    return num ? Number(num).toLocaleString('id-ID') : 0;
}

// PAGINATION
function renderPagination(){
    let total = Math.ceil(allData.length/perPage);
    let html = '';

    for(let i=1;i<=total;i++){
        html += `<span class="${i==currentPage?'active':''}" onclick="currentPage=${i};renderTable()">${i}</span>`;
    }

    document.getElementById("pagination").innerHTML = html;
}

// EXPORT (SIMPLE)
function exportExcel(){ window.print(); }
function exportWord(){ window.print(); }
function exportPDF(){ window.print(); }

// INIT
loadLaporan();
</script>

</body>
</html>