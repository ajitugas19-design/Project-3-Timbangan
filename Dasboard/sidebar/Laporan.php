<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div style="color:red;text-align:center;margin-top:50px;">Silakan login terlebih dahulu!</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Penimbangan</title>

<style>
body{
    font-family:Segoe UI;
    background:linear-gradient(135deg,#0f172a,#1e293b);
    margin:0;
    padding:20px;
    color:#e5e7eb;
}

.container{
    max-width:1400px;
    margin:auto;
    background:#1e293b;
    border-radius:12px;
    overflow:hidden;
}

/* HEADER */
.header{
    background:linear-gradient(135deg,#0ea5e9,#2563eb);
    padding:20px;
    text-align:center;
    color:white;
}

/* FILTER */
.filter{
    padding:20px;
    background:#0f172a;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

input{
    padding:10px;
    border-radius:6px;
    border:1px solid #334155;
    background:#1e293b;
    color:white;
}

button{
    background:#22c55e;
    border:none;
    padding:10px 20px;
    border-radius:6px;
    color:white;
    cursor:pointer;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#0f172a;
    color:#38bdf8;
    padding:12px;
}

td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #334155;
}

tr:hover td{
    background:#334155;
}

/* SUMMARY */
.summary{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
    padding:20px;
}

.card{
    background:#064e3b;
    padding:15px;
    text-align:center;
    border-radius:10px;
}

.card h2{margin:0;}
</style>
</head>

<body>

<div class="container">

<div class="header">
<h2>Laporan Transaksi Penimbangan</h2>
</div>

<div class="filter">
<input type="date" id="dari">
<input type="date" id="sampai">
<button onclick="generateReport()">🔄 Generate</button>
<button onclick="exportReport('csv')" class="export-btn">📥 CSV</button>
<button onclick="printReport()">🖨️ PDF</button>
<button onclick="exportExcel()">📊 Excel</button>
<button onclick="exportWord()">📄 Word</button>
</div>

<style>
.export-btn { background: #8b5cf6; margin-left:10px; }
.export-btn:hover { background: #7c3aed; }
@media print { .filter { display:none; } .summary { break-inside: avoid; } }
</style>

<div class="summary" id="summary"></div>

<table>
<thead>
<tr>
<th>No</th>
<th>No Record</th>
<th>Sopir</th>
<th>Nopol</th>
<th>Supplier</th>
<th>Material</th>
<th>Customer</th>
<th>Tanggal IN</th>
<th>Jam IN</th>
<th>Tanggal OUT</th>
<th>Jam OUT</th>
<th>Bruto</th>
<th>Tara</th>
<th>Netto</th>
</tr>
</thead>

<tbody id="table"></tbody>
</table>

</div>

<script>
let dataLaporan = [];

async function generateReport(){

let dari = document.getElementById("dari").value;
let sampai = document.getElementById("sampai").value;

if(!dari || !sampai){
alert("Pilih tanggal dulu!");
return;
}

let res = await fetch(`../api/laporan.php?dari=${dari}&sampai=${sampai}`);
let result = await res.json();

dataLaporan = result.data;

renderTable();
renderSummary(result.summary);
}

// TABLE
function renderTable(){
let html="";
let no=1;

dataLaporan.forEach(d=>{
html+=`
<tr>
<td>${no++}</td>
<td>${d.no_record}</td>
<td>${d.Sopir ?? '-'}</td>
<td>${d.Nopol ?? '-'}</td>
<td>${d.supplier ?? '-'}</td>
<td>${d.material ?? '-'}</td>
<td>${d.customer ?? '-'}</td>
<td>${d.tanggal_in ?? '-'}</td>
<td>${d.jam_in ?? '-'}</td>
<td>${d.tanggal_out ?? '-'}</td>
<td>${d.jam_out ?? '-'}</td>
<td>${format(d.bruto)}</td>
<td>${format(d.tara)}</td>
<td style="color:#22c55e;font-weight:bold;">${format(d.netto)}</td>
</tr>
`;
});

document.getElementById("table").innerHTML = html;
}

// SUMMARY
function renderSummary(s){
document.getElementById("summary").innerHTML = `
<div class="card"><h2>${s.total_transaksi}</h2>Total</div>
<div class="card"><h2>${format(s.total_bruto)}</h2>Bruto</div>
<div class="card"><h2>${format(s.total_tara)}</h2>Tara</div>
<div class="card"><h2>${format(s.total_netto)}</h2>Netto</div>
`;
}

// FORMAT ANGKA
function format(x){
return x ? parseFloat(x).toLocaleString('id-ID') : 0;
}

// Export functions
async function exportReport(format) {
    let dari = document.getElementById("dari").value;
    let sampai = document.getElementById("sampai").value;
    
    if(!dari || !sampai) {
        alert('Pilih tanggal dulu!');
        return;
    }
    
    const url = `../api/laporan.php?export=${format}&dari=${dari}&sampai=${sampai}`;
    
    if (format === 'csv') {
        window.open(url, '_blank');
        return;
    }
    
    // Load data first
    try {
        let res = await fetch(url.replace('export=' + format, ''));
        let result = await res.json();
        
        if (format === 'excel') {
            // SheetJS for Excel
            const data = [Object.keys(result.data[0] || {})];
            result.data.forEach(row => data.push(Object.values(row)));
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, 'Laporan');
            XLSX.writeFile(wb, `laporan_${dari}_to_${sampai}.xlsx`);
        } else if (format === 'word') {
            // HTML to DOC
            let html = '<h1>Laporan Penimbangan ' + dari + ' s/d ' + sampai + '</h1>' +
                document.querySelector('.summary').outerHTML + 
                '<table border="1">' + document.querySelector('#table').outerHTML + '</table>';
            const blob = new Blob([html], {type: 'application/msword'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `laporan_${dari}_to_${sampai}.doc`;
            link.click();
        }
    } catch(e) {
        alert('Export error: ' + e.message);
    }
}

function printReport() {
    window.print();
}

// AUTO TODAY
let today = new Date().toISOString().split('T')[0];
document.getElementById("dari").value = today;
document.getElementById("sampai").value = today;

// Load SheetJS lib for Excel
const script = document.createElement('script');
script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
document.head.appendChild(script);
</script>

</body>
</html>