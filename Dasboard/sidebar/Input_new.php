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
<title>Penimbangan - Input Transaksi</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { margin:0; font-family:'Segoe UI',sans-serif; background:linear-gradient(135deg,#1e3c72,#2a5298); min-height:100vh; padding:20px; }
.container { max-width:1200px; margin:0 auto; background:#fff; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.3); }
.title { text-align:center; font-size:28px; font-weight:bold; color:#333; margin-bottom:20px; }
.error, .success { padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid; }
.error { color:#ef4444; background:#fef2f2; border-color:#fecaca; }
.success { color:#10b981; background:#ecfdf5; border-color:#bbf7d0; }
.nav-buttons { display:flex; gap:15px; justify-content:center; margin:30px 0; flex-wrap:wrap; }
.btn { padding:12px 24px; border:none; border-radius:8px; text-decoration:none; font-weight:500; cursor:pointer; transition:0.3s; box-shadow:0 4px 12px rgba(0,0,0,0.15); display:inline-flex; align-items:center; gap:8px; }
.btn-primary { background:#3b82f6; color:white; }
.btn-primary:hover { background:#2563eb; transform:translateY(-2px); }
.form-row { display:flex; align-items:center; margin-bottom:15px; gap:15px; }
label { width:140px; font-weight:600; color:#444; flex-shrink:0; }
input, select { flex:1; padding:10px 14px; border-radius:8px; border:1px solid #d1d5db; font-size:14px; transition:0.3s; }
input:focus, select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
.btn-save { background:#10b981; color:white; padding:16px 40px; border:none; border-radius:12px; font-size:18px; font-weight:bold; cursor:pointer; width:100%; margin-top:25px; box-shadow:0 6px 20px rgba(16,185,129,0.3); }
.btn-save:hover { background:#059669; transform:translateY(-2px); }
.btn-save:disabled { background:#9ca3af; cursor:not-allowed; transform:none; }
.berat { display:flex; gap:20px; margin:30px 0; }
.berat-box { flex:1; text-align:center; background:#f8fafc; padding:25px; border-radius:12px; border:2px solid #e2e8f0; }
.berat-box h3 { margin:0 0 15px 0; font-size:20px; color:#475569; }
.berat-value { font-size:32px; font-weight:bold; padding:16px; width:100%; border-radius:10px; border:3px solid transparent; }
.bruto .berat-value { border-color:#3b82f6; background:#eff6ff; color:#1d4ed8; }
.tara .berat-value { border-color:#f59e0b; background:#fef3c7; color:#b45309; }
.netto .berat-value { border-color:#10b981; background:#ecfdf5; color:#047857; }
.recent-table { margin-top:30px; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
table { width:100%; border-collapse:collapse; background:white; }
th { background:#374151; color:white; padding:14px 12px; text-align:left; font-weight:600; }
td { padding:14px 12px; border-bottom:1px solid #f3f4f6; }
tr:hover { background:#f9fafb; }
.loading { text-align:center; padding:40px; color:#6b7280; }
.spinner { border:4px solid #f3f4f6; border-top:4px solid #3b82f6; border-radius:50%; width:40px; height:40px; animation:spin 1s linear infinite; margin:0 auto; }
@keyframes spin { 0% { transform:rotate(0deg); } 100% { transform:rotate(360deg); } }
@media (max-width:768px) { .form-row { flex-direction:column; align-items:stretch; gap:10px; } label { width:100%; margin-bottom:5px; } .berat { flex-direction:column; gap:15px; } .nav-buttons { flex-direction:column; } }
</style>
</head>
<body>
<div class="container">
    <div class="title">📦 Input Transaksi Penimbangan</div>
    
    <div class="nav-buttons">
        <a href="Informasi_Data.php" class="btn btn-primary">📊 Lihat Semua Data</a>
    </div>

    <div id="message"></div>

    <form id="transaksiForm">
        <input type="hidden" id="no_record" value="">
        <input type="hidden" id="id_in" value="">
        <input type="hidden" id="id_out" value="">

        <div class="form-row">
            <label>No Record</label>
            <input type="text" id="no_record_display" readonly style="background:#f3f4f6;">
        </div>

        <div class="form-row">
            <label>No Polisi *</label>
            <input type="text" id="nopol" placeholder="Ketik atau pilih Nopol" list="nopolList" required>
            <datalist id="nopolList"></datalist>
        </div>

        <div class="form-row">
            <label>Sopir *</label>
            <input type="text" id="sopir" readonly>
        </div>

        <div class="form-row">
            <label>Customer *</label>
            <select id="customer" required>
                <option value="">Loading...</option>
            </select>
        </div>

        <div class="form-row">
            <label>Supplier *</label>
            <select id="supplier" required>
                <option value="">Loading...</option>
            </select>
        </div>

        <div class="form-row">
            <label>Material *</label>
            <select id="material" required>
                <option value="">Loading...</option>
            </select>
        </div>

        <div class="form-row">
            <label>Tanggal IN *</label>
            <input type="date" id="tanggal_in" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-row">
            <label>Jam IN *</label>
            <input type="time" id="jam_in" value="<?= date('H:i:s') ?>" required>
        </div>

        <div class="form-row">
            <label>Tanggal OUT *</label>
            <input type="date" id="tanggal_out" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-row">
            <label>Jam OUT *</label>
            <input type="time" id="jam_out" required>
        </div>

        <div class="berat">
            <div class="berat-box bruto">
                <h3>BRUTO *</h3>
                <input type="number" id="bruto" class="berat-value" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="berat-box tara">
                <h3>TARA *</h3>
                <input type="number" id="tara" class="berat-value" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="berat-box netto">
                <h3>NETTO</h3>
                <input type="number" id="netto" class="berat-value" readonly placeholder="Auto">
            </div>
        </div>

        <button type="submit" class="btn-save" id="submitBtn">💾 Simpan Transaksi</button>
    </form>

    <div class="recent-table">
        <h3 style="margin:0 0 15px 0; color:#374151;">📋 Recent Transaksi (10 terakhir)</h3>
        <div id="recentLoading" class="loading">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
        <div id="recentTable"></div>
    </div>
</div>

<script>
let editMode = false;
let editId = null;
let idIn = null;
let idOut = null;

// Load dropdowns
async function loadDropdowns() {
    try {
        // Kendaraan
        const kendaraanRes = await fetch('../api/kendaraan.php?action=list');
        const kendaraan = await kendaraanRes.json();
        const nopolList = document.getElementById('nopolList');
        nopolList.innerHTML = '';
        kendaraan.data?.forEach(k => {
            const option = document.createElement('option');
            option.value = k.Nopol;
            option.dataset.id = k.id_Kendaraan;
            option.dataset.sopir = k.Sopir;
            nopolList.appendChild(option);
        });

        // Customers
        const customersRes = await fetch('../api/customers.php?action=list');
        const customers = await customersRes.json();
        const customerSelect = document.getElementById('customer');
        customerSelect.innerHTML = '<option value="">Pilih Customer</option>';
        customers.data?.forEach(c => customerSelect.innerHTML += `<option value="${c.id_Customers}">${c.Customers}</option>`);

        // Suppliers
        const suppliersRes = await fetch('../api/suppliers.php?action=list');
        const suppliers = await suppliersRes.json();
        const supplierSelect = document.getElementById('supplier');
        supplierSelect.innerHTML = '<option value="">Pilih Supplier</option>';
        suppliers.data?.forEach(s => supplierSelect.innerHTML += `<option value="${s.id_Supplier}">${s.Nama_Supplier}</option>`);

        // Materials
        const materialsRes = await fetch('../api/materials.php?action=list');
        const materials = await materialsRes.json();
        const materialSelect = document.getElementById('material');
        materialSelect.innerHTML = '<option value="">Pilih Material</option>';
        materials.data?.forEach(m => materialSelect.innerHTML += `<option value="${m.id}">${m.nama}</option>`);
    } catch(e) {
        showMessage('Error loading dropdowns: ' + e.message, 'error');
    }
}

// Get auto no_record
async function getNoRecord() {
    try {
        // Use informasi_data_full or custom endpoint
        const res = await fetch('../api/informasi_data_full.php?action=max_id');
        const result = await res.json();
        const maxId = result.max_id || 0;
        const no_record = 'TRX' + String(maxId + 1).padStart(5, '0');
        document.getElementById('no_record').value = no_record;
        document.getElementById('no_record_display').value = no_record;
    } catch(e) {
        document.getElementById('no_record').value = 'TRX00001';
        document.getElementById('no_record_display').value = 'TRX00001';
    }
}

// Nopol search
document.getElementById('nopol').addEventListener('input', function() {
    const nopol = this.value;
    const options = document.getElementById('nopolList').querySelectorAll('option');
    let found = false;
    options.forEach(option => {
        if (option.value === nopol) {
            document.getElementById('id_kendaraan').value = option.dataset.id;
            document.getElementById('sopir').value = option.dataset.sopir;
            found = true;
        }
    });
    if (!found) {
        document.getElementById('id_kendaraan').value = '';
        document.getElementById('sopir').value = '';
    }
});

// Auto calculate netto
document.getElementById('bruto').addEventListener('input', calculateNetto);
document.getElementById('tara').addEventListener('input', calculateNetto);

function calculateNetto() {
    const bruto = parseFloat(document.getElementById('bruto').value) || 0;
    const tara = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = (bruto - tara).toFixed(2);
}

// Create waktu_in/out
async function createWaktu(type, tanggal, jam) {
    const res = await fetch('../api/informasi_data_full.php?action=' + type, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({tanggal, jam})
    });
    const result = await res.json();
    return result.id;
}

// Save form
document.getElementById('transaksiForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Menyimpan...';
    
    try {
        const formData = {
            no_record: document.getElementById('no_record').value,
            id_kendaraan: document.getElementById('id_kendaraan').value,
            id_supplier: document.getElementById('supplier').value,
            id_material: document.getElementById('material').value,
            id_customers: document.getElementById('customer').value,
            bruto: document.getElementById('bruto').value,
            tara: document.getElementById('tara').value,
            netto: document.getElementById('netto').value,
            tanggal_in: document.getElementById('tanggal_in').value,
            jam_in: document.getElementById('jam_in').value,
            tanggal_out: document.getElementById('tanggal_out').value,
            jam_out: document.getElementById('jam_out').value
        };

        // Validate required
        if (!formData.id_kendaraan || !formData.id_supplier || !formData.id_material || !formData.id_customers || !formData.bruto || !formData.tara) {
            throw new Error('Lengkapi semua field wajib *');
        }

        // Create waktu_in/out
        idIn = await createWaktu('create_waktu_in', formData.tanggal_in, formData.jam_in);
        idOut = await createWaktu('create_waktu_out', formData.tanggal_out, formData.jam_out);
        
        formData.id_in = idIn;
        formData.id_out = idOut;

        // Save transaksi
        const res = await fetch('../api/informasi_data_full.php?action=add', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
        });
        
        const result = await res.json();
        
        if (result.status === 'success') {
            showMessage('✅ Data berhasil disimpan! ID: ' + result.id, 'success');
            document.getElementById('transaksiForm').reset();
            getNoRecord();
            loadRecent();
        } else {
            throw new Error(result.message || 'Save failed');
        }
    } catch(e) {
        showMessage('❌ ' + e.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

function showMessage(msg, type) {
    const message = document.getElementById('message');
    message.innerHTML = `<div class="${type}">${msg}</div>`;
    setTimeout(() => message.innerHTML = '', 5000);
}

// Load recent transactions
async function loadRecent() {
    try {
        const res = await fetch('../api/informasi_data_full.php?action=list_recent');
        const result = await res.json();
        let html = '';
        result.data?.forEach((r, i) => {
            html += `
                <tr>
                    <td>${i+1}</td>
                    <td>${r.no_record}</td>
                    <td>${r.sopir}</td>
                    <td>${r.nopol}</td>
                    <td>${r.supplier}</td>
                    <td>${r.muatan}</td>
                    <td>${r.customer}</td>
                    <td>${(r.netto || 0).toLocaleString()} kg</td>
                    <td>${r.tanggal_in}</td>
                </tr>`;
        });
        document.getElementById('recentTable').innerHTML = `
            <table>
                <thead><tr><th>#</th><th>Record</th><th>Sopir</th><th>Nopol</th><th>Supplier</th><th>Material</th><th>Customer</th><th>Netto</th><th>Tgl</th></tr></thead>
                <tbody>${html || '<tr><td colspan="9" class="empty-state">No recent data</td></tr>'}</tbody>
            </table>`;
        document.getElementById('recentLoading').style.display = 'none';
    } catch(e) {
        document.getElementById('recentLoading').innerHTML = '<p>Error loading recent</p>';
    }
}

// INIT
loadDropdowns();
getNoRecord();
loadRecent();
document.getElementById('tanggal_out').value = '<?= date('Y-m-d') ?>';
calculateNetto();
</script>
</body>
</html>
