<?php
session_start();
require_once '../../config.php';

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() { 
        return isset($_SESSION['user_id']); 
    }
}
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ================= AJAX HANDLER =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $response = ['success' => false];
        
        // Extract variables
        $id_kendaraan = !empty($_POST['id_kendaraan']) ? (int)$_POST['id_kendaraan'] : 0; // Required per schema
        $id_supplier = !empty($_POST['cek_supplier']) && !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : 0;
        $id_material = !empty($_POST['id_material']) ? (int)$_POST['id_material'] : 0;
        $id_customers = !empty($_POST['cek_customer']) && !empty($_POST['id_customers']) ? (int)$_POST['id_customers'] : 0;
        $bruto = ($_POST['bruto'] !== '') ? (float)$_POST['bruto'] : null;
        $tara = ($_POST['tara'] !== '') ? (float)$_POST['tara'] : null;
        $netto = $bruto && $tara ? $bruto - $tara : 0;
        
        // CSRF check
        if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
            throw new Exception('CSRF token invalid');
        }
        
        // Validation
        if (!$id_material) throw new Exception('Material harus dipilih');
        if (!$bruto || $bruto <= 0 || !$tara || $tara <= 0) throw new Exception('Bruto dan Tara harus > 0');
        if (empty($_POST['tgl_masuk']) || empty($_POST['jam_masuk']) || empty($_POST['tgl_keluar']) || empty($_POST['jam_keluar'])) {
            throw new Exception('Lengkapi waktu masuk dan keluar');
        }
        
        // FK validation - ALL REQUIRED per schema
        $check = $pdo->prepare('SELECT COUNT(*) FROM kendaraan WHERE id_Kendaraan = ?');
        $check->execute([$id_kendaraan]);
        if ($check->fetchColumn() == 0) throw new Exception('Kendaraan tidak ditemukan');
        
        $check = $pdo->prepare('SELECT COUNT(*) FROM material WHERE id_Material = ?');
        $check->execute([$id_material]);
        if ($check->fetchColumn() == 0) throw new Exception('Material tidak ditemukan');
        
        if ($id_supplier) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM supplier WHERE id_Supplier = ?');
            $check->execute([$id_supplier]);
            if ($check->fetchColumn() == 0) throw new Exception('Supplier tidak ditemukan');
        }
        if ($id_customers) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE id_Customers = ?');
            $check->execute([$id_customers]);
            if ($check->fetchColumn() == 0) throw new Exception('Customer tidak ditemukan');
        }
        
        // Generate unique no_record
        $date_prefix = date('Ymd');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE no_record LIKE ?');
        $stmt->execute(['TRAN' . $date_prefix . '%']);
        $seq = $stmt->fetchColumn() + 1;
        $no_record = 'TRAN' . $date_prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            
        // Insert times
        $pdo->prepare("INSERT INTO waktu_in (tanggal_in, jam_in) VALUES (?, ?)")->execute([$_POST['tgl_masuk'], $_POST['jam_masuk']]);
        $id_in = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO waktu_out (tanggal_out, jam_out) VALUES (?, ?)")->execute([$_POST['tgl_keluar'], $_POST['jam_keluar']]);
        $id_out = $pdo->lastInsertId();
        
        $id_transaksi = !empty($_POST['id_transaksi']) ? (int)$_POST['id_transaksi'] : null;
        
        if ($id_transaksi) {
            $stmt = $pdo->prepare("UPDATE transaksi SET no_record=?, id_kendaraan=?, id_supplier=?, id_material=?, id_customers=?, bruto=?, tara=?, netto=?, id_in=?, id_out=? WHERE id_transaksi=?");
            $stmt->execute([$no_record, $id_kendaraan, $id_supplier, $id_material, $id_customers, $bruto, $tara, $netto, $id_in, $id_out, $id_transaksi]);
            $response['message'] = '✅ Data berhasil diupdate!';
            $response['data'] = ['id_transaksi' => $id_transaksi, 'no_record' => $no_record];
        } else {
            $stmt = $pdo->prepare("INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, bruto, tara, netto, id_in, id_out) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$no_record, $id_kendaraan, $id_supplier, $id_material, $id_customers, $bruto, $tara, $netto, $id_in, $id_out]);
            $new_id = $pdo->lastInsertId();
            $response['message'] = '✅ Data tersimpan! ID: ' . $new_id;
            $response['data'] = ['id_transaksi' => $new_id, 'no_record' => $no_record];
        }
        $response['success'] = true;
        
    } catch (Exception $e) {
        $response['message'] = '🚫 ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Unfinished
$unfinished = $pdo->query("SELECT t.*, k.Nopol FROM transaksi t JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan WHERE tara = 0 OR tara IS NULL")->fetchAll();

// Auto no_record preview (same logic as POST)
$date_prefix = date('Ymd');
$stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE no_record LIKE ?');
$stmt->execute(['TRAN' . $date_prefix . '%']);
$seq = $stmt->fetchColumn() + 1;
$no_record = 'TRAN' . $date_prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

// Data queries for datalists
$kendaraan = $pdo->query('SELECT * FROM kendaraan ORDER BY Nopol ASC')->fetchAll();
$suppliers = $pdo->query('SELECT * FROM supplier ORDER BY Nama_Supplier ASC')->fetchAll();
$customers = $pdo->query('SELECT * FROM customers ORDER BY Customers ASC')->fetchAll();
$materials = $pdo->query('SELECT * FROM material ORDER BY Material ASC')->fetchAll();

// Unfinished JSON endpoint
if (isset($_GET['unfinished']) && $_GET['unfinished'] == '1') {
    header('Content-Type: application/json');
    $unfinished = $pdo->query("SELECT t.id_transaksi, t.no_record, t.bruto, k.Nopol 
                               FROM transaksi t 
                               JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan 
                               WHERE (t.tara = 0 OR t.tara IS NULL) 
                               ORDER BY t.id_transaksi DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($unfinished);
    exit;
}

// Edit AJAX endpoint
if (isset($_GET['edit']) && isLoggedIn()) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("
        SELECT t.*, k.Nopol, k.Sopir, 
               m.Material, c.Customers, s.Nama_Supplier,
               DATE_FORMAT(wi.tanggal_in, '%Y-m-d') as tgl_masuk, wi.jam_in,
               DATE_FORMAT(wo.tanggal_out, '%Y-m-d') as tgl_keluar, wo.jam_out
        FROM transaksi t 
        LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
        LEFT JOIN material m ON t.id_material = m.id_Material
        LEFT JOIN customers c ON t.id_customers = c.id_Customers
        LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
        LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
        LEFT JOIN waktu_out wo ON t.id_out = wo.id_out
        WHERE t.id_transaksi = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Record not found']);
    }
    exit;
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Input Timbangan - FIXED</title>
<style>
.container {max-width:1200px;margin:auto;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
.grid {display:grid;grid-template-columns:1fr 1fr;gap:20px;}
label {font-weight:bold;display:block;margin:5px 0;}
input,select {width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;margin-bottom:10px;box-sizing:border-box;}
.btn {padding:12px 24px;background:#28a745;color:white;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.message {padding:15px;margin:10px 0;border-radius:5px;}
.success {background:#d4edda;color:#155724;}
.error {background:#f8d7da;color:#721c24;}
.box {display:flex;gap:10px;}
.box > div {flex:1;}
.center {text-align:center;}
.toast {position:fixed;top:20px;right:20px;padding:15px;border-radius:8px;color:white;z-index:10000;max-width:300px;box-shadow:0 4px 12px rgba(0,0,0,0.3);transform:translateX(400px);transition:0.3s;}
.toast.show {transform:translateX(0);}
.toast.success {background:#10b981;}
.toast.error {background:#ef4444;}
.spinner {opacity:0.5;pointer-events:none;}
</style>
</head>
<body>
<div class="container">
<h1>Input Timbangan</h1>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
<input type="hidden" name="id_transaksi" id="id_transaksi">
<input type="hidden" name="action" value="save">

<div class="grid">

<div>
<label>No Record</label>
<input name="no_record" value="<?= $no_record ?>" readonly>

<label>No Polisi</label>
<input id="nopol" list="kendaraan-list" required>
<datalist id="kendaraan-list">
<?php foreach($kendaraan as $k): ?>
<option value="<?= $k['Nopol'] ?>" data-id="<?= $k['id_Kendaraan'] ?>" data-sopir="<?= $k['Sopir'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_kendaraan" id="id_kendaraan">

<label>Sopir</label>
<input id="sopir" type="text" placeholder="Auto dari nopol atau manual">

<label>Customer</label>
<label style="display:flex;align-items:center;gap:5px;"><input type="checkbox" id="cek_customer" name="cek_customer"> Pilih Customer</label>
<input id="customer-input" list="customer-list" style="display:none;width:100%;" placeholder="Ketik nama customer">
<datalist id="customer-list">
<?php foreach($customers as $c): ?>
<option value="<?= $c['Customers'] ?>" data-id="<?= $c['id_Customers'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_customers" id="id_customers">

<label>Supplier</label>
<label style="display:flex;align-items:center;gap:5px;"><input type="checkbox" id="cek_supplier" name="cek_supplier"> Pilih Supplier</label>
<input id="supplier-input" list="supplier-list" style="display:none;width:100%;" placeholder="Ketik nama supplier">
<datalist id="supplier-list">
<?php foreach($suppliers as $s): ?>
<option value="<?= $s['Nama_Supplier'] ?>" data-id="<?= $s['id_Supplier'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_supplier" id="id_supplier">

<label>Material</label>
<input id="material-input" list="material-list" required placeholder="Ketik nama material">
<datalist id="material-list">
<?php foreach($materials as $m): ?>
<option value="<?= $m['Material'] ?>" data-id="<?= $m['id_Material'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_material" id="id_material">

<label>Tanggal Masuk</label>
<input type="date" name="tgl_masuk" value="<?= date('Y-m-d') ?>">

<label>Jam Masuk</label>
<input type="time" name="jam_masuk">
</div>

<div>
<label>Edit Belum Selesai</label>
<select id="edit_select" onchange="loadEdit()" style="background-color: #fef3c7;">
<option value="">-- Pilih Unfinished (kuning) --</option>
<?php foreach($unfinished as $u): ?>
<option value="<?= $u['id_transaksi'] ?>" data-nopol="<?= $u['Nopol'] ?>">
<?= $u['Nopol'] ?> - <?= $u['no_record'] ?? 'NEW' ?> (Bruto: <?= number_format($u['bruto'] ?? 0, 0) ?>)
</option>
<?php endforeach; ?>
</select>
<label style="color: orange; font-size: 0.9em;">Kuning = berbeda dari Nopol input di atas</label>

<div class="box">
<div>
<label>Bruto</label>
<input type="number" step="0.01" name="bruto" id="bruto">
</div>
<div>
<label>Tara</label>
<input type="number" step="0.01" name="tara" id="tara">
</div>
<div>
<label>Netto</label>
<input type="number" step="0.01" name="netto" id="netto" readonly>
</div>
</div>

<div class="center">
<button type="button" class="btn" onclick="calculate()">HITUNG NETTO</button>
</div>

<label>Tanggal Keluar</label>
<input type="date" name="tgl_keluar" value="<?= date('Y-m-d', strtotime('+1 day')) ?>"> 

<label>Jam Keluar</label>
<input type="time" name="jam_keluar">
</div>

</div>

<button type="submit" class="btn">SIMPAN KE DATABASE</button>
</form>

<script>
// Auto sopir (overwrite if exact match, else keep manual)
document.getElementById('nopol').addEventListener('input', function(){
    const options = document.querySelectorAll('#kendaraan-list option');
    let found = false;
    for(let opt of options){
        if(opt.value === this.value){
            document.getElementById('id_kendaraan').value = opt.dataset.id;
            document.getElementById('sopir').value = opt.dataset.sopir || '';
            document.getElementById('sopir').title = 'Auto dari DB';
            found = true;
            break;
        }
    }
    if (!found) {
        document.getElementById('id_kendaraan').value = '';
        document.getElementById('sopir').title = 'Manual input';
    }
});

// Checkbox toggle show/hide + disable/enable
document.getElementById('cek_customer').addEventListener('change', function(){
    const sel = document.getElementById('customer-input');
    if (this.checked) {
        sel.style.display = 'block';
        sel.focus(); // Focus for immediate interaction
    } else {
        sel.style.display = 'none';
        sel.value = '';
        document.getElementById('id_customers').value = '';
    }
});
document.getElementById('cek_supplier').addEventListener('change', function(){
    const sel = document.getElementById('supplier-input');
    if (this.checked) {
        sel.style.display = 'block';
        sel.focus();
    } else {
        sel.style.display = 'none';
        sel.value = '';
        document.getElementById('id_supplier').value = '';
    }
});

// Auto ID set function
function setupDatalistListener(inputId, datalistId, hiddenId, titleId = null) {
    document.getElementById(inputId).addEventListener('input', function(){
        const options = document.querySelectorAll(`#${datalistId} option`);
        let found = false;
        for(let opt of options){
            if(opt.value === this.value){
                document.getElementById(hiddenId).value = opt.dataset.id;
                if (titleId) document.getElementById(titleId).title = 'Auto dari DB';
                found = true;
                break;
            }
        }
        if (!found) {
            document.getElementById(hiddenId).value = '';
            if (titleId) document.getElementById(titleId).title = 'Manual input';
        }
    });
}

setupDatalistListener('customer-input', 'customer-list', 'id_customers');
setupDatalistListener('supplier-input', 'supplier-list', 'id_supplier');
setupDatalistListener('material-input', 'material-list', 'id_material');

// Calculate
function calculate(){
    const b = parseFloat(document.getElementById('bruto').value) || 0;
    const t = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = (b - t).toFixed(2);
}

// Load edit with AJAX
function loadEdit(){
    const sel = document.getElementById('edit_select');
    const id = sel.value;
    if(!id) return;
    
    fetch('sidebar/Input.php?edit=' + id)
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                alert('Error: ' + data.error);
                return;
            }
            // Populate form
            document.getElementById('id_transaksi').value = data.id_transaksi;
            document.getElementById('nopol').value = data.nopol;
            document.getElementById('id_kendaraan').value = data.id_kendaraan;
            document.getElementById('sopir').value = data.sopir;
            document.getElementById('bruto').value = data.bruto || '';
            document.getElementById('tara').value = data.tara || '';
            document.getElementById('netto').value = data.netto || '';
            document.getElementById('tgl_masuk').value = data.tgl_masuk;
            document.getElementById('jam_masuk').value = data.jam_masuk;
            document.getElementById('tgl_keluar').value = data.tgl_keluar;
            document.getElementById('jam_keluar').value = data.jam_keluar;
            
// Populate datalist inputs - trigger 'input' to auto set IDs
            const materialInput = document.getElementById('material-input');
            materialInput.value = data.Material || '';
            materialInput.dispatchEvent(new Event('input'));
            
            if (data.Customers) {
                document.getElementById('cek_customer').checked = true;
                const customerInput = document.getElementById('customer-input');
                customerInput.value = data.Customers;
                customerInput.dispatchEvent(new Event('input'));
            }
            
            if (data.Nama_Supplier) {
                document.getElementById('cek_supplier').checked = true;
                const supplierInput = document.getElementById('supplier-input');
                supplierInput.style.display = 'block';
                supplierInput.value = data.Nama_Supplier;
                supplierInput.dispatchEvent(new Event('input'));
            }
            
            if (data.Customers) {
                document.getElementById('cek_customer').checked = true;
                const customerInput = document.getElementById('customer-input');
                customerInput.style.display = 'block';
                customerInput.value = data.Customers;
                customerInput.dispatchEvent(new Event('input'));
            }
            
            calculate();

            
            calculate();
            alert('Data loaded for editing');
        })
        .catch(err => alert('Load error: ' + err));
}

// ================= AJAX FORM SUBMIT =================
const form = document.querySelector('form');
const submitBtn = form.querySelector('button[type="submit"]');

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!validate()) return;
    
    // Spinner
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.classList.add('spinner');
    submitBtn.disabled = true;
    
    const fd = new FormData(form);
    
    try {
        const res = await fetch('sidebar/Input.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        
        showToast(data.message, data.success);
        
        if (data.success) {
            // Reset but keep no_record preview logic if needed
            document.getElementById('id_transaksi').value = '';
            document.getElementById('nopol').value = '';
            document.getElementById('sopir').value = '';
            document.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
            document.querySelectorAll('#customer-input, #supplier-input').forEach(input => {
                input.style.display = 'none';
                input.value = '';
            });
            document.getElementById('id_customers').value = document.getElementById('id_supplier').value = document.getElementById('id_material').value = '';
            loadUnfinished(); // Reload unfinished list dynamically
            calculate(); // Reset netto
        }
    } catch (err) {
        showToast('Network error: ' + err.message, false);
    } finally {
        submitBtn.textContent = 'SIMPAN KE DATABASE';
        submitBtn.classList.remove('spinner');
        submitBtn.disabled = false;
    }
});

// ================= TOAST =================
function showToast(msg, success = true) {
    const toast = document.createElement('div');
    toast.className = `toast ${success ? 'success' : 'error'}`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ================= RELOAD UNFINISHED =================
async function loadUnfinished() {
    const select = document.getElementById('edit_select');
    select.innerHTML = '<option value="">-- Loading unfinished... --</option>';
    
    try {
        const res = await fetch('sidebar/Input.php?unfinished=1');
        const unfinished = await res.json();
        
        select.innerHTML = '<option value="">-- Pilih Unfinished (kuning) --</option>';
        unfinished.forEach(item => {
            const option = new Option(
                `${item.Nopol} - ${item.no_record || 'NEW'} (Bruto: ${parseFloat(item.bruto || 0).toLocaleString()})`,
                item.id_transaksi,
                false,
                false
            );
            option.dataset.nopol = item.Nopol;
            select.appendChild(option);
        });
    } catch (err) {
        select.innerHTML = '<option value="">-- Error loading unfinished --</option>';
        console.error('Load unfinished error:', err);
    }
}

// ================= AUTO CALCULATE =================
['bruto', 'tara'].forEach(id => {
    document.getElementById(id).addEventListener('input', calculate);
});

function calculate() {
    const b = parseFloat(document.getElementById('bruto').value) || 0;
    const t = parseFloat(document.getElementById('tara').value) || 0;
    document.getElementById('netto').value = (b - t).toFixed(2);
}

// Validate (client-side)
function validate() {
    const id_kendaraan = document.getElementById('id_kendaraan').value;
    if(!id_kendaraan) return alert('Pilih No Polisi!'), false;
if(!document.getElementById('id_material').value) return alert('Pilih Material!'), false;
    if(!document.getElementById('bruto').value || !document.getElementById('tara').value) return alert('Isi Bruto & Tara!'), false;
    return true;
}
</script>
</body>
</html>
