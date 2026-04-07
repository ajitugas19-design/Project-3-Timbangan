<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Informasi Data Timbangan (DB)</title>

<style>
body {
    font-family: Arial;
    background: #f4f6f9;
    padding: 20px;
}

.container {
    max-width: 1200px;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
}

h2 {
    text-align: center;
}

/* FILTER */
.filter {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 15px;
    gap: 10px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}

thead {
    background: #4CAF50;
    color: white;
}

.aksi {
    display: flex;
    justify-content: space-between;
    gap: 5px;
}

.aksi button {
    padding: 4px 8px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.btn-edit {
    background: #2196F3;
    color: white;
}

.btn-delete {
    background: #f44336;
    color: white;
}

/* PAGINATION */
.pagination {
    margin-top: 15px;
    text-align: center;
}

.pagination button {
    padding: 5px 10px;
    margin: 2px;
    background: #ddd;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.pagination button.active {
    background: #4CAF50;
    color: white;
}

.error {
    color: #f44336;
    text-align: center;
    padding: 10px;
    background: #ffebee;
    border-radius: 5px;
    margin-bottom: 20px;
}
</style>
</head>

<body>

<div class="container">
<h2>📊 Informasi Data Timbangan (Database)</h2>

<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}
?>

<div class="filter">
    <div>
        🔍 Cari Tanggal:
        <input type="date" id="searchTanggal" onchange="loadData()">
    </div>

    <div>
        Tampilkan:
        <select id="rowsPerPage" onchange="loadData()">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="25">25</option>
        </select> baris
    </div>
</div>

<div class="table-wrapper">
<table>
<thead>
<tr>
<th>No</th>
<th>Sopir</th>
<th>Kendaraan</th>
<th>Nopol</th>
<th>Tanggal In</th>
<th>Jam In</th>
<th>Tanggal Out</th>
<th>Jam Out</th>
<th>Isi Muatan</th>
<th>Bruto</th>
<th>Tara</th>
<th>Netto</th>
<th>Keterangan</th>
<th>Aksi</th>
</tr>
</thead>

<tbody id="tbody">
<tr><td colspan="14" style="text-align:center;">Loading...</td></tr>
</tbody>
</table>
</div>

<div class="pagination" id="pagination"></div>

</div>

<script>
let currentPage = 1;
let rowsPerPage = 10;
let allData = [];

async function loadData(page = 1) {
    currentPage = page;
    rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
    const tgl = document.getElementById("searchTanggal").value;
    
    try {
        const url = `../api/informasi_data.php?tgl=${tgl}&page=${page}&limit=${rowsPerPage}`;
        const response = await fetch(url);
        const data = await response.json();
        
        allData = data.data || [];
        renderTable();
        renderPagination();
    } catch (error) {
        document.getElementById("tbody").innerHTML = '<tr><td colspan="14" class="error">Error loading data: ' + error.message + '</td></tr>';
    }
}

function renderTable() {
    const tbody = document.getElementById("tbody");
    const start = (currentPage - 1) * rowsPerPage;
    const pageData = allData.slice(start, start + rowsPerPage);
    
    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="14" style="text-align:center;">Tidak ada data</td></tr>';
        return;
    }
    
    tbody.innerHTML = pageData.map((d, i) => `
        <tr>
            <td>${start + i + 1}</td>
            <td>${d.sopir || '-'}</td>
            <td>${d.kendaraan || '-'}</td>
            <td>${d.nopol || '-'}</td>
            <td>${d.tanggal_in || '-'}</td>
            <td>${d.jam_in || '-'}</td>
            <td>${d.tanggal_out || '-'}</td>
            <td>${d.jam_out || '-'}</td>
            <td>${d.muatan || '-'}</td>
            <td>${d.bruto || 0}</td>
            <td>${d.tara || 0}</td>
            <td>${d.netto || 0}</td>
            <td>${d.keterangan || '-'}</td>
            <td>
                <div class="aksi">
                    <button class="btn-edit" onclick="editData(${d.id_data})">Edit</button>
                    <button class="btn-delete" onclick="deleteData(${d.id_data})">Hapus</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination() {
    const totalPages = Math.ceil(allData.length / rowsPerPage);
    const pagination = document.getElementById("pagination");
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    pagination.innerHTML = Array.from({length: totalPages}, (_, i) => 
        `<button class="${i+1 === currentPage ? 'active' : ''}" onclick="loadData(${i+1})">${i+1}</button>`
    ).join('');
}

async function deleteData(id) {
    if (confirm("Yakin hapus data ini?")) {
        try {
            const response = await fetch(`../api/informasi_data.php?id=${id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            if (result.success) {
                loadData(currentPage);
            } else {
                alert('Error: ' + (result.error || 'Gagal hapus'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
}

function editData(id) {
    // Redirect to edit form, pass ID
    alert('Edit: ID ' + id + ' - Fitur edit lengkap di Input_informasi.html');
    // Removed localStorage - use URL params or session instead
}

// Initial load
loadData();
</script>

</body>
</html>
