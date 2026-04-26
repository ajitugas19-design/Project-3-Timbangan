<?php
ob_start();
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
        
        $id_kendaraan = !empty($_POST['id_kendaraan']) ? (int)$_POST['id_kendaraan'] : null; 
        $id_material  = !empty($_POST['id_material']) ? (int)$_POST['id_material'] : null;
        $bruto = ($_POST['bruto'] !== '') ? (float)$_POST['bruto'] : null;
        $tara  = ($_POST['tara'] !== '') ? (float)$_POST['tara'] : null;
        $netto = ($_POST['netto'] !== '') ? (float)$_POST['netto'] : null;

        /* ================= AUTO INSERT MANUAL ENTRIES ================= */
        if (empty($id_kendaraan) && !empty($_POST['nopol_manual'])) {
            $sopir_manual = !empty($_POST['sopir_manual']) ? $_POST['sopir_manual'] : '';
            $stmt = $pdo->prepare("INSERT INTO kendaraan (Nopol, Sopir) VALUES (?, ?)");
            $stmt->execute([$_POST['nopol_manual'], $sopir_manual]);
            $id_kendaraan = (int)$pdo->lastInsertId();
        }

        if (empty($id_material) && !empty($_POST['material_manual'])) {
            $kode = 'MANUAL-' . time();
            $stmt = $pdo->prepare("INSERT INTO material (Kode, Material) VALUES (?, ?)");
            $stmt->execute([$kode, $_POST['material_manual']]);
            $id_material = (int)$pdo->lastInsertId();
        }

        $id_supplier = null;
        if (!empty($_POST['cek_supplier'])) {
            if (!empty($_POST['id_supplier'])) {
                $id_supplier = (int)$_POST['id_supplier'];
            } elseif (!empty($_POST['supplier_manual'])) {
                $stmt = $pdo->prepare("INSERT INTO supplier (Nama_Supplier, Lokasi_Asal, Lokasi_Tujuan) VALUES (?, '', '')");
                $stmt->execute([$_POST['supplier_manual']]);
                $id_supplier = (int)$pdo->lastInsertId();
            }
        }

        $id_customers = null;
        if (!empty($_POST['cek_customer'])) {
            if (!empty($_POST['id_customers'])) {
                $id_customers = (int)$_POST['id_customers'];
            } elseif (!empty($_POST['customer_manual'])) {
                $stmt = $pdo->prepare("INSERT INTO customers (Customers, Keterangan) VALUES (?, '')");
                $stmt->execute([$_POST['customer_manual']]);
                $id_customers = (int)$pdo->lastInsertId();
            }
        }

        /* ================= HITUNG 3 ARAH ================= */
        if ($bruto !== null && $tara !== null && $netto === null) {
            $netto = $bruto - $tara;
        } elseif ($bruto !== null && $netto !== null && $tara === null) {
            $tara = $bruto - $netto;
        } elseif ($tara !== null && $netto !== null && $bruto === null) {
            $bruto = $tara + $netto;
        }
        // If fewer than 2 fields filled, keep as-is (NULL or submitted value)

        if ($bruto < 0 || $tara < 0 || $netto < 0) {
            throw new Exception('Nilai tidak boleh negatif');
        }
        
        // Allow empty/null values - save as-is

        $date_prefix = date('Ymd');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE no_record LIKE ?');
        $stmt->execute(['TRAN' . $date_prefix . '%']);
        $seq = $stmt->fetchColumn() + 1;
        $no_record = 'TRAN' . $date_prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            
        $pdo->prepare("INSERT INTO waktu_in (tanggal_in, jam_in) VALUES (?, ?)")->execute([$_POST['tgl_masuk'], $_POST['jam_masuk']]);
        $id_in = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO waktu_out (tanggal_out, jam_out) VALUES (?, ?)")->execute([$_POST['tgl_keluar'], $_POST['jam_keluar']]);
        $id_out = $pdo->lastInsertId();
        
        $id_transaksi = !empty($_POST['id_transaksi']) ? (int)$_POST['id_transaksi'] : null;
        
        if ($id_transaksi) {
            $stmt = $pdo->prepare("UPDATE transaksi SET no_record=?, id_kendaraan=?, id_supplier=?, id_material=?, id_customers=?, bruto=?, tara=?, netto=?, id_in=?, id_out=? WHERE id_transaksi=?");
            $stmt->execute([$no_record, $id_kendaraan, $id_supplier, $id_material, $id_customers, $bruto, $tara, $netto, $id_in, $id_out, $id_transaksi]);
            $response['message'] = '✅ Data berhasil diupdate!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, bruto, tara, netto, id_in, id_out) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$no_record, $id_kendaraan, $id_supplier, $id_material, $id_customers, $bruto, $tara, $netto, $id_in, $id_out]);
            $new_id = $pdo->lastInsertId();
            $response['message'] = '✅ Data tersimpan! ID: ' . $new_id;
        }
        $response['success'] = true;
        
    } catch (Exception $e) {
        $response['message'] = '🚫 ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

$unfinished = $pdo->query("
    SELECT t.*, k.Nopol FROM transaksi t
    LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
    WHERE t.bruto IS NULL OR t.bruto = 0 OR t.tara IS NULL OR t.tara = 0 OR t.netto IS NULL OR t.netto = 0
    ORDER BY t.id_transaksi DESC
")->fetchAll(PDO::FETCH_ASSOC);

$date_prefix = date('Ymd');
$stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE no_record LIKE ?');
$stmt->execute(['TRAN' . $date_prefix . '%']);
$seq = $stmt->fetchColumn() + 1;
$no_record = 'TRAN' . $date_prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

$kendaraan = $pdo->query('SELECT * FROM kendaraan ORDER BY Nopol ASC')->fetchAll();
$suppliers = $pdo->query('SELECT * FROM supplier ORDER BY Nama_Supplier ASC')->fetchAll();
$customers = $pdo->query('SELECT * FROM customers ORDER BY Customers ASC')->fetchAll();
$materials = $pdo->query('SELECT * FROM material ORDER BY Material ASC')->fetchAll();

if (isset($_GET['unfinished']) && $_GET['unfinished'] == '1') {
    header('Content-Type: application/json');
    $unfinished = $pdo->query("
        SELECT t.id_transaksi, t.no_record, t.bruto, t.tara, t.netto, k.Nopol
        FROM transaksi t LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
        WHERE t.bruto IS NULL OR t.bruto = 0 OR t.tara IS NULL OR t.tara = 0 OR t.netto IS NULL OR t.netto = 0
        ORDER BY t.id_transaksi DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($unfinished);
    exit;
}

if (isset($_GET['edit']) && isLoggedIn()) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("
        SELECT t.*, k.Nopol, k.Sopir, m.Material, c.Customers, s.Nama_Supplier,
               DATE_FORMAT(wi.tanggal_in, '%Y-%m-%d') as tgl_masuk, wi.jam_in,
               DATE_FORMAT(wo.tanggal_out, '%Y-%m-%d') as tgl_keluar, wo.jam_out
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
    header('Content-Type: application/json');
    echo json_encode($data ?: ['error' => 'Record not found']);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Input Timbangan - Manual Support</title>
<style>
/* Scoped: Input */
.page-input {max-width:1100px;margin:auto;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
.page-input .grid {display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.page-input label {font-weight:bold;display:block;margin:5px 0;}
.page-input input:not([type="checkbox"]),
.page-input select {width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;margin-bottom:10px;box-sizing:border-box;}
.page-input .box {display:flex;gap:10px;}
.page-input .box > div {flex:1;}
.page-input .center {text-align:center;}
.page-input .spinner {opacity:0.5;pointer-events:none;}

/* Panel timbangan spesifik */
.page-input .scale-panel {border:2px solid #28a745;border-radius:8px;padding:15px;margin-bottom:15px;background:#f8fff8;}
.page-input .scale-panel h3 {margin-top:0;color:#28a745;font-size:1.1em;}
.page-input .scale-display {font-size:2em;font-weight:bold;color:#1976d2;text-align:center;padding:10px;background:#e3f2fd;border-radius:5px;margin-bottom:10px;}
.page-input .scale-logs {max-height:150px;overflow-y:auto;font-size:0.85em;border:1px solid #ddd;border-radius:5px;padding:8px;background:#fff;}
</style>
</head>
<body>
<div class="page-input">

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
<input type="hidden" name="id_transaksi" id="id_transaksi">
<input type="hidden" name="action" value="save">

<div class="grid">
<div>
<label>No Record</label>
<input name="no_record" value="<?= $no_record ?>" readonly>

<label>No Polisi</label>
<input id="nopol" list="kendaraan-list" placeholder="Pilih atau ketik No Polisi baru">
<datalist id="kendaraan-list">
<?php foreach($kendaraan as $k): ?>
<option value="<?= $k['Nopol'] ?>" data-id="<?= $k['id_Kendaraan'] ?>" data-sopir="<?= $k['Sopir'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_kendaraan" id="id_kendaraan">
<input type="hidden" name="nopol_manual" id="nopol_manual">

<label>Sopir</label>
<input id="sopir" name="sopir" type="text" placeholder="Auto dari nopol atau ketik manual">
<input type="hidden" name="sopir_manual" id="sopir_manual">

<label>Customer</label>
<h>Click Centang Kalau Ada Customers</h>
<label style="display:flex;align-items:center;gap:5px;"><input type="checkbox" id="cek_customer" name="cek_customer"></label>
<input id="customer-input" list="customer-list" style="display:none;width:100%;" placeholder="Pilih atau ketik nama customer">
<datalist id="customer-list">
<?php foreach($customers as $c): ?>
<option value="<?= $c['Customers'] ?>" data-id="<?= $c['id_Customers'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_customers" id="id_customers">
<input type="hidden" name="customer_manual" id="customer_manual">

<label>Supplier</label>
<h>Click Centang Kalau Ada Supplier</h>
<label style="display:flex;align-items:center;gap:5px;"><input type="checkbox" id="cek_supplier" name="cek_supplier"></label>
<input id="supplier-input" list="supplier-list" style="display:none;width:100%;" placeholder="Pilih atau ketik nama supplier">
<datalist id="supplier-list">
<?php foreach($suppliers as $s): ?>
<option value="<?= $s['Nama_Supplier'] ?>" data-id="<?= $s['id_Supplier'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_supplier" id="id_supplier">
<input type="hidden" name="supplier_manual" id="supplier_manual">

<label>Material</label>
<input id="material-input" list="material-list" placeholder="Pilih atau ketik nama material">
<datalist id="material-list">
<?php foreach($materials as $m): ?>
<option value="<?= $m['Material'] ?>" data-id="<?= $m['id_Material'] ?>">
<?php endforeach; ?>
</datalist>
<input type="hidden" name="id_material" id="id_material">
<input type="hidden" name="material_manual" id="material_manual">

<br><br>
<hr></hr>
<label>Tanggal Masuk</label>
<input type="date" name="tgl_masuk" id="tgl_masuk" value="<?= date('Y-m-d') ?>">
<label>Jam Masuk</label>
<input type="time" name="jam_masuk" id="jam_masuk">
</div>

<div>
<label>Timbang 2X</label>
<select id="edit_select" onchange="loadEdit()" style="background:#fef3c7;">
<option value="">-- Pilih Data --</option>
<?php foreach($unfinished as $u): ?>
<option value="<?= $u['id_transaksi'] ?>"><?= ($u['Nopol'] ?: '-') ?> - <?= ($u['no_record'] ?: 'NEW') ?> (Bruto: <?= number_format($u['bruto'] ?: 0,0) ?>)</option>
<?php endforeach; ?>
</select>
<br><br>

<!-- Panel Timbangan RS232 -->
<div class="scale-panel">
  <h3>📟 Timbangan Digital (RS232)</h3>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
    <span id="scaleStatus" style="font-weight:bold;">⚪ Menghubungkan...</span>
    <span id="scaleTime" style="font-size:0.85em;color:#666;"></span>
  </div>
  <div class="scale-display">
    <span id="latestWeight">--</span> <span style="font-size:0.5em;color:#666;">kg</span>
  </div>
  <button type="button" class="btn btn-primary" onclick="useLatestWeight()" style="width:100%;margin-bottom:10px;background:#1976d2;">Input Ke Bruto</button>
  <div id="logsTable" class="scale-logs">
    <em>Memuat log timbangan...</em>
  </div>
</div>

<div class="box">
<div>
<label>Bruto <span id="brutoSource" style="font-size:0.8em;color:#666;">(manual)</span></label>
<input type="number" step="0.01" name="bruto" id="bruto">
</div>
<div>
<label>Tara</label>
<input type="number" step="0.01" name="tara" id="tara">
</div>
<div>
<label>Netto</label>
<input type="number" step="0.01" name="netto" id="netto">
</div>
</div>

<div class="center">
<button type="button" class="btn btn-success" onclick="calculate()">HITUNG</button>
</div>
<h1></h1>
<hr>

<label>Tanggal Keluar</label>
<input type="date" name="tgl_keluar" id="tgl_keluar" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
<label>Jam Keluar</label>
<input type="time" name="jam_keluar" id="jam_keluar">
</div>
</div>

<button type="submit" class="btn btn-success"> SIMPAN </button>
</form>

<script src="/Project_3/Dasboard/js/enter-next.js"></script>
<script src="/Project_3/Dasboard/js/Input_FIXED.js"></script>
<script>
(function(){
  const form = document.querySelector('form');
  if (form && typeof initEnterNext === 'function') initEnterNext(form);
})();
</script>
</body>
</html>

