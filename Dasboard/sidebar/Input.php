<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error" style="color:#ef4444;background:#fef2f2;padding:20px;border-radius:12px;text-align:center;margin:20px;font-size:18px;border:2px solid #fecaca;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}
require_once '../../config.php';

date_default_timezone_set("Asia/Jakarta");

// AUTO NO RECORD
$stmt = $pdo->query("SELECT COALESCE(MAX(id_transaksi), 0) as max_id FROM transaksi");
$max = $stmt->fetch(PDO::FETCH_ASSOC);
$no_record = "TRX" . str_pad($max['max_id'] + 1, 5, '0', STR_PAD_LEFT);

// GET DROPDOWNS
$customers = $pdo->query("SELECT id_Customers as id, Customers as nama FROM customers ORDER BY nama");
$kendaraan = $pdo->query("SELECT id_Kendaraan as id, Nopol, Sopir FROM kendaraan ORDER BY Nopol");

$materials = $pdo->query("SELECT id_Material as id, Material as nama FROM material ORDER BY nama");
$suppliers = $pdo->query("SELECT id_Supplier as id, Nama_Supplier as nama FROM supplier ORDER BY nama");

// Recent transactions
$recent = $pdo->query("
    SELECT t.no_record, k.Sopir, k.Nopol, s.Nama_Supplier as supplier, m.Material, c.Customers as customer, 
           t.netto, wi.tanggal_in 
    FROM transaksi t 
    LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
    LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
    LEFT JOIN material m ON t.id_material = m.id_Material  
    LEFT JOIN customers c ON t.id_customers = c.id_Customers
    LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
    ORDER BY t.id_transaksi DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id_kendaraan = $_POST['id_kendaraan'] ?? 0;
    if (empty($id_kendaraan)) {
        $error = "Nopol belum dipilih!";
    } else {
        // Insert waktu_in
        $stmt = $pdo->prepare("INSERT INTO waktu_in (jam_in, tanggal_in) VALUES (?, ?)");
        $stmt->execute([$_POST['jam_in'], $_POST['tanggal_in']]);
        $id_in = $pdo->lastInsertId();

        // Insert waktu_out
        $stmt = $pdo->prepare("INSERT INTO waktu_out (jam_out, tanggal_out) VALUES (?, ?)");
        $stmt->execute([$_POST['jam_out'], $_POST['tanggal_out']]);
        $id_out = $pdo->lastInsertId();

        // Insert transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, id_in, id_out, bruto, tara, netto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['no_record'],
            $id_kendaraan,
            $_POST['supplier'],
            $_POST['material'],
            $_POST['customer'],
            $id_in,
            $id_out,
            $_POST['bruto'],
            $_POST['tara'],
            $_POST['netto']
        ]);

        $success = "Data berhasil disimpan!";
        // Reload recent after save
        $recent = $pdo->query("
            SELECT t.no_record, k.Sopir, k.Nopol, s.Nama_Supplier as supplier, m.Material, c.Customers as customer, 
                   t.netto, wi.tanggal_in 
            FROM transaksi t 
            LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
            LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
            LEFT JOIN material m ON t.id_material = m.id_Material  
            LEFT JOIN customers c ON t.id_customers = c.id_Customers
            LEFT JOIN waktu_in wi ON t.id_in = wi.id_in
            ORDER BY t.id_transaksi DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<title>Penimbangan - Input Transaksi</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body { 
    margin:0; 
    font-family:'Segoe UI',sans-serif; 
    background:linear-gradient(135deg,#1e3c72,#2a5298); 
    min-height:100vh; 
    padding:20px; 
}
.container { 
    max-width:1200px; 
    margin:0 auto; 
    background:#fff; 
    border-radius:12px; 
    padding:25px; 
    box-shadow:0 10px 25px rgba(0,0,0,0.3); 
}
.title { 
    text-align:center; 
    font-size:28px; 
    font-weight:bold; 
    color:#333; 
    margin-bottom:20px; 
}
.error { 
    color:#ef4444; 
    background:#fef2f2; 
    padding:15px; 
    border-radius:8px; 
    margin-bottom:20px; 
    text-align:center; 
    border:1px solid #fecaca; 
}
.success { 
    color:#10b981; 
    background:#ecfdf5; 
    padding:15px; 
    border-radius:8px; 
    margin-bottom:20px; 
    text-align:center; 
    border:1px solid #bbf7d0; 
}
.nav-buttons { 
    display:flex; 
    gap:15px; 
    justify-content:center; 
    margin:30px 0; 
    flex-wrap:wrap; 
}
.btn { 
    padding:12px 24px; 
    border:none; 
    border-radius:8px; 
    text-decoration:none; 
    font-weight:500; 
    cursor:pointer; 
    transition:0.3s; 
    box-shadow:0 4px 12px rgba(0,0,0,0.15); 
    display:inline-flex; 
    align-items:center; 
    gap:8px; 
}
.btn-primary { 
    background:#3b82f6; 
    color:white; 
}
.btn-primary:hover { 
    background:#2563eb; 
    transform:translateY(-2px); 
}
.btn-success { 
    background:#10b981; 
    color:white; 
}
.btn-success:hover { 
    background:#059669; 
    transform:translateY(-2px); 
}
.form-row { 
    display:flex; 
    align-items:center; 
    margin-bottom:15px; 
    gap:15px; 
}
label { 
    width:140px; 
    font-weight:600; 
    color:#444; 
    flex-shrink:0; 
}
input, select { 
    flex:1; 
    padding:10px 14px; 
    border-radius:8px; 
    border:1px solid #d1d5db; 
    font-size:14px; 
    transition:0.3s; 
}
input:focus, select:focus { 
    outline:none; 
    border-color:#3b82f6; 
    box-shadow:0 0 0 3px rgba(59,130,246,0.1); 
}
.btn-small { 
    padding:8px 16px; 
    border:none; 
    border-radius:6px; 
    background:#6b7280; 
    color:white; 
    cursor:pointer; 
    font-size:13px; 
}
.btn-small:hover { 
    background:#4b5563; 
}
.btn-save { 
    background:#10b981; 
    color:white; 
    padding:16px 40px; 
    border:none; 
    border-radius:12px; 
    font-size:18px; 
    font-weight:bold; 
    cursor:pointer; 
    width:100%; 
    margin-top:25px; 
    box-shadow:0 6px 20px rgba(16,185,129,0.3); 
}
.btn-save:hover { 
    background:#059669; 
    transform:translateY(-2px); 
}
.berat { 
    display:flex; 
    gap:20px; 
    margin:30px 0; 
}
.berat-box { 
    flex:1; 
    text-align:center; 
    background:#f8fafc; 
    padding:25px; 
    border-radius:12px; 
    border:2px solid #e2e8f0; 
}
.berat-box h3 { 
    margin:0 0 15px 0; 
    font-size:20px; 
    color:#475569; 
}
.berat-value { 
    font-size:32px; 
    font-weight:bold; 
    padding:16px; 
    width:100%; 
    border-radius:10px; 
    border:3px solid transparent; 
}
.bruto .berat-value { 
    border-color:#3b82f6; 
    background:#eff6ff; 
    color:#1d4ed8; 
}
.tara .berat-value { 
    border-color:#f59e0b; 
    background:#fef3c7; 
    color:#b45309; 
}
.netto .berat-value { 
    border-color:#10b981; 
    background:#ecfdf5; 
    color:#047857; 
}
.data-table { 
    overflow-x:auto; 
    margin-top:30px; 
    border-radius:12px; 
    box-shadow:0 4px 12px rgba(0,0,0,0.1); 
}
table { 
    width:100%; 
    border-collapse:collapse; 
    background:white; 
}
th { 
    background:#374151; 
    color:white; 
    padding:14px 12px; 
    text-align:left; 
    font-weight:600; 
    position:sticky; 
    top:0; 
}
td { 
    padding:14px 12px; 
    border-bottom:1px solid #f3f4f6; 
}
tr:hover { 
    background:#f9fafb; 
}
.netto-col { 
    color:#10b981; 
    font-weight:bold; 
}
.empty-state { 
    text-align:center; 
    padding:40px; 
    color:#6b7280; 
    font-size:16px; 
}
.footer { 
    text-align:center; 
    margin-top:30px; 
    color:#64748b; 
}
@media (max-width:768px) {
    .form-row { flex-direction:column; align-items:stretch; gap:10px; }
    label { width:100%; margin-bottom:5px; }
    .berat { flex-direction:column; gap:15px; }
    .nav-buttons { flex-direction:column; }
}
</style>
</head>
<body>
<div class="container">
    <div class="title">📦 Input Transaksi Penimbangan</div>

    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Nav Buttons -->
    <div class="nav-buttons">
        <a href="Informasi_Data.php" class="btn btn-primary" onclick="loadContent('sidebar/Informasi_Data.php'); window.close(); return false;">📊 Lihat Semua Data</a>
    </div>

    <form method="POST">
        <input type="hidden" name="id_kendaraan" id="id_kendaraan">
        <input type="hidden" name="no_record" value="<?= htmlspecialchars($no_record) ?>">

        <div class="form-row">
            <label>No Record</label>
            <input type="text" value="<?= htmlspecialchars($no_record) ?>" readonly style="background:#f3f4f6;">
        </div>

        <div class="form-row">
            <label>No Polisi</label>
            <input type="text" id="nopol" name="nopol" list="nopolList" placeholder="Ketik atau pilih Nopol" autofocus required>
            <datalist id="nopolList">
                <?php foreach ($kendaraan as $k): ?>
                    <option value="<?= htmlspecialchars($k['Nopol']) ?>" data-sopir="<?= htmlspecialchars($k['Sopir']) ?>" data-id="<?= $k['id'] ?>">
                <?php endforeach; ?>
            </datalist>
            <button type="button" class="btn-small" onclick="ambilNopol()">🔍 Cari</button>
        </div>

        <div class="form-row">
            <label>Sopir</label>
            <input type="text" id="sopir" name="sopir" list="sopirList" placeholder="Sopir">
            <datalist id="sopirList">
                <?php foreach ($kendaraan as $k): ?>
                    <option value="<?= htmlspecialchars($k['Sopir']) ?>" data-id="<?= $k['id'] ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="form-row">
            <label>Customer</label>
            <select name="customer" required>
                <option value="">Pilih Customer</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label>Supplier</label>
            <select name="supplier" required>
                <option value="">Pilih Supplier</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label>Material</label>
            <select name="material" required>
                <option value="">Pilih Material</option>
                <?php foreach ($materials as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label>Tanggal IN</label>
            <input type="date" name="tanggal_in" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-row">
            <label>Jam IN</label>
            <input type="time" name="jam_in" value="<?= date('H:i:s') ?>" required>
        </div>

        <div class="form-row">
            <label>Tanggal OUT</label>
            <input type="date" name="tanggal_out" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-row">
            <label>Jam OUT</label>
            <input type="time" name="jam_out" required>
        </div>

        <!-- BERAT -->
        <div class="berat">
            <div class="berat-box bruto">
                <h3>BRUTO</h3>
                <input type="number" name="bruto" class="berat-value" id="bruto" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="berat-box tara">
                <h3>TARA</h3>
                <input type="number" name="tara" class="berat-value" id="tara" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="berat-box netto">
                <h3>NETTO</h3>
                <input type="number" name="netto" class="berat-value" id="netto" step="0.01" readonly placeholder="Auto">
            </div>
        </div>

        <button type="submit" name="simpan" class="btn-save">💾 Simpan Transaksi</button>
    </form>

</body>
</html>
