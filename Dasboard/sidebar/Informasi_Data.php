<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error" style="color:#ef4444;background:#fef2f2;padding:20px;border-radius:12px;text-align:center;margin:20px;font-size:18px;border:2px solid #fecaca;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}
require_once '../../config.php';
date_default_timezone_set("Asia/Jakarta");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Informasi Data - Semua Transaksi</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; font-family:'Segoe UI',sans-serif; background:linear-gradient(135deg,#1e3c72,#2a5298); min-height:100vh; padding:20px; }
        .container { max-width:1200px; margin:0 auto; background:#fff; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.3); }
        .title { text-align:center; font-size:24px; font-weight:bold; color:#333; margin-bottom:20px; }
        .stats { display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap; }
        .stat-box { flex:1; min-width:200px; background:#f8fafc; padding:15px; border-radius:8px; text-align:center; border:1px solid #e2e8f0; }
        .stat-value { font-size:24px; font-weight:bold; color:#10b981; }
        .stat-label { color:#64748b; font-size:14px; }
        
        .nav-buttons { display:flex; gap:10px; margin-bottom:20px; justify-content:center; flex-wrap:wrap; }
        .btn { background:#3b82f6; color:white; border:none; cursor:pointer; font-weight:500; transition:0.3s; padding:12px 24px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); }
        .btn:hover { background:#2563eb; transform:translateY(-2px); }
        .btn-success { background:#10b981; }
        .btn-success:hover { background:#059669; }
        
        .controls { display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
        input, select, button { padding:10px 15px; border-radius:6px; border:1px solid #ccc; }
        input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .btn-small { background:#6b7280; color:white; padding:8px 16px; font-size:13px; }
        .btn-small:hover { background:#4b5563; }
        .btn-danger { background:#ef4444; }
        .btn-danger:hover { background:#dc2626; }
        
        .data-table { overflow-x:auto; margin-top:30px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
        table { width:100%; border-collapse:collapse; background:white; }
        th { background:#374151; color:white; padding:14px 12px; text-align:left; font-weight:600; position:sticky; top:0; }
        td { padding:14px 12px; border-bottom:1px solid #f3f4f6; }
        tr:hover { background:#f9fafb; }
        .netto-col { color:#10b981; font-weight:bold; }
        .actions { white-space:nowrap; }
        
        .pagination { display:flex; gap:10px; justify-content:center; margin-top:20px; flex-wrap:wrap; }
        .page-btn { padding:8px 12px; border:1px solid #d1d5db; background:#f9fafb; cursor:pointer; border-radius:4px; }
        .page-btn.active, .page-btn:hover { background:#3b82f6; color:white; }
        .empty-state { text-align:center; padding:40px; color:#6b7280; font-size:16px; }
        
        @media (max-width:768px) { .stats, .controls { flex-direction:column; align-items:stretch; } .stat-box { min-width:auto; } .nav-buttons { flex-direction:column; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">📊 Informasi Data - Semua Transaksi Penimbangan</div>
        
<div class="nav-buttons">
            <a href="#" class="btn btn-success" onclick="if (typeof loadContent === 'function') loadContent('sidebar/Input.php', 'Input Data'); return false;">➕ Input Data Baru</a>
            <a href="#" class="btn" onclick="if (typeof loadContent === 'function') loadContent('sidebar/Laporan.php', 'Laporan'); return false;">📈 Laporan</a>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-value" id="totalTransaksi">0</div>
                <div class="stat-label">Total Transaksi</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" id="hariIni">0</div>
                <div class="stat-label">Hari Ini</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" id="totalNetto">0 kg</div>
                <div class="stat-label">Total Netto</div>
            </div>
        </div>
        
        <div class="controls">
            <input type="date" id="dateFilter" value="<?= date('Y-m-d') ?>">
            <input type="text" id="searchInput" placeholder="Cari No Record, Sopir, Nopol...">
            <select id="rowsPerPage">
                <option value="10">10 baris</option>
                <option value="25">25 baris</option>
                <option value="50">50 baris</option>
            </select>
            <button class="btn" onclick="loadTable(1)">🔄 Muat Ulang</button>
            <button class="btn btn-danger" onclick="exportCSV()">📥 Export CSV</button>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>No Record</th>
                        <th>Sopir</th>
                        <th>Nopol</th>
                        <th>Supplier</th>
                        <th>Material</th>
                        <th>Customer</th>
                        <th>Bruto</th>
                        <th>Tara</th>
                        <th class="netto-col">Netto</th>
                        <th>Tgl In</th>
                        <th>Tgl Out</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="12" class="empty-state">Muat data...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>

    <script>
        let currentPage = 1;
        
        // Safe load function with null checks
        function safeGetElement(id) {
            return document.getElementById(id);
        }
        
async function loadTable(page = 1) {
            try {
                currentPage = page;
                const dateFilter = safeGetElement('dateFilter')?.value || '';
                const searchTerm = safeGetElement('searchInput')?.value || '';
                const limit = safeGetElement('rowsPerPage')?.value || 10;
                
                const params = new URLSearchParams({ page, limit, tgl: dateFilter, search: searchTerm });
                
const res = await fetch(`../api/informasi_data_full.php?${params}`);
                if (!res.ok) throw new Error(`API error: ${res.status}`);
                
                const result = await res.json();
                if (!result) throw new Error('Invalid JSON response');
                
                populateTable(result.data || []);
                updatePagination(result);
                updateStats(result);
            } catch (error) {
                console.error('LoadTable error:', error);
                const tbody = safeGetElement('tableBody');
                if (tbody) tbody.innerHTML = `<tr><td colspan="12" class="empty-state">❌ Load error: ${error.message}</td></tr>`;
            }
        }
        
        function populateTable(data) {
            const tbody = safeGetElement('tableBody');
            if (!tbody) return;
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" class="empty-state">📭 Tidak ada data ditemukan</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(row => `
                <tr>
                    <td><strong>${row.no_record || ''}</strong></td>
                    <td>${row.sopir || '-'}</td>
                    <td>${row.nopol || '-'}</td>
                    <td>${row.supplier || '-'}</td>
                    <td>${row.muatan || '-'}</td>
                    <td>${row.customer || '-'}</td>
                    <td>${parseFloat(row.bruto || 0).toLocaleString()} kg</td>
                    <td>${parseFloat(row.tara || 0).toLocaleString()} kg</td>
                    <td class="netto-col">${parseFloat(row.netto || 0).toLocaleString()} kg</td>
                    <td>${row.tanggal_in || ''}</td>
                    <td>${row.tanggal_out || '-'}</td>
                    <td class="actions">
                        <button class="btn btn-danger" onclick="deleteRow(${row.id_data})" title="Hapus">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }
        
        function updatePagination(result) {
            const pagination = safeGetElement('pagination');
            if (!pagination || !result || result.pages <= 1) return;
            
            let html = '';
            const maxPages = Math.min(10, result.pages); // Limit visible pages
            const startPage = Math.max(1, currentPage - 4);
            
            // Previous
            if (currentPage > 1) html += `<button class="page-btn" onclick="loadTable(${currentPage-1})">←</button>`;
            
            for (let i = startPage; i <= Math.min(startPage + 9, result.pages); i++) {
                const active = i === currentPage ? 'active' : '';
                html += `<button class="page-btn ${active}" onclick="loadTable(${i})">${i}</button>`;
            }
            
            // Next
            if (currentPage < result.pages) html += `<button class="page-btn" onclick="loadTable(${currentPage+1})">→</button>`;
            
            pagination.innerHTML = html;
        }
        
        function updateStats(result) {
            safeGetElement('totalTransaksi')?.textContent = result.total?.toLocaleString() || 0;
            safeGetElement('hariIni')?.textContent = result.todayCount || 0;
            safeGetElement('totalNetto')?.textContent = result.totalNetto ? result.totalNetto.toLocaleString() + ' kg' : '0 kg';
        }
        
        async function deleteRow(id) {
            if (!confirm('Yakin hapus transaksi ini?')) return;
            
            try {
const res = await fetch('../api/informasi_data_full.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                if (res.ok) {
                    loadTable(currentPage);
                } else {
                    alert('Gagal hapus data');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        function exportCSV() {
            const dateFilter = safeGetElement('dateFilter')?.value || '';
            window.open(`../api/informasi_data.php?export=1&tgl=${dateFilter}`, '_blank');
        }
        
        // Event listeners with safety checks
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = safeGetElement('searchInput');
            const rowsPerPage = safeGetElement('rowsPerPage');
            const dateFilter = safeGetElement('dateFilter');
            
            if (searchInput) {
                let debounceTimer;
                searchInput.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => loadTable(currentPage), 500);
                });
            }
            
            if (rowsPerPage) {
                rowsPerPage.addEventListener('change', () => loadTable(1));
            }
            
            if (dateFilter) {
                dateFilter.value = '<?= date('Y-m-d') ?>';
                dateFilter.addEventListener('change', () => loadTable(1));
            }
            
            loadTable(1);
        });
    </script>
</body>
</html>
