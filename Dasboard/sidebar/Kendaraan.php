<?php
session_start();
require_once '../../config.php';
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}



// Load data
$stmt = $pdo->query("SELECT * FROM kendaraan ORDER BY id_Kendaraan DESC");
$kendaraan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM kendaraan WHERE id_Kendaraan = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kendaraan</title>
<style>
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f3f4f6;
  margin: 0;
  padding: 20px;
}

/* BUTTON */
.btn {
  background: #3b82f6;
  color: white;
  padding: 10px 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}
.btn:hover { background: #2563eb; }

.btn-warning { background: #f59e0b; }
.btn-danger { background: #ef4444; }
.btn-success { background: #10b981; }

/* MESSAGE */
.message {
  padding: 14px;
  border-radius: 10px;
  text-align: center;
  margin-bottom: 15px;
  font-weight: 600;
}
.success { background: #d1fae5; color: #065f46; }
.error { background: #fee2e2; color: #991b1b; }

/* TABLE */
.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  margin-top: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #1f2937;
  color: white;
}

th, td {
  padding: 14px;
  text-align: left;
}

tbody tr {
  border-bottom: 1px solid #eee;
}

tbody tr:hover {
  background: #f9fafb;
}

/* ACTION BUTTON */
.edit {
  background: #f59e0b;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}

.hapus {
  background: #ef4444;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}

/* EMPTY */
.empty-state {
  text-align: center;
  padding: 40px;
  color: #6b7280;
}
.empty-state .emoji {
  font-size: 40px;
  display: block;
}

/* FORM SLIDE */
.form-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  opacity: 0;
  visibility: hidden;
  transition: 0.3s;
}

.form-overlay.active {
  opacity: 1;
  visibility: visible;
}

.form-slide {
  position: fixed;
  right: -400px;
  top: 0;
  width: 350px;
  height: 100%;
  background: white;
  padding: 25px;
  transition: 0.3s;
  box-shadow: -4px 0 10px rgba(0,0,0,0.1);
}

.form-slide.active {
  right: 0;
}

.form-slide h2 {
  margin-bottom: 20px;
}

/* INPUT */
.form-slide input {
  width: 100%;
  padding: 12px;
  margin-bottom: 15px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

.form-slide input:focus {
  border-color: #3b82f6;
  outline: none;
}

/* FORM BUTTON */
.btn-save {
  width: 100%;
  background: #10b981;
  color: white;
  padding: 12px;
  border-radius: 8px;
  border: none;
  margin-bottom: 10px;
  cursor: pointer;
}

.btn-cancel {
  width: 100%;
  background: #ef4444;
  color: white;
  padding: 12px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .form-slide {
    width: 100%;
  }
}
</style>
</style>

</style>
</head>
<body>

<div id="messageContainer"></div>

<button class="btn" onclick="openForm()">🚗 + Tambah Kendaraan</button>
<input type="hidden" id="tableContainer" value=".table-container">
<input type="hidden" id="apiEndpoint" value="api/kendaraan_crud.php">
<?php if ($edit_data): ?>
<button class="btn btn-warning" onclick="window.location.href='?';">← Kembali</button>
<?php endif; ?>

<?php if (!$edit_data): ?>
<div class="table-container">
<table>
<thead>
<tr>
<th>No</th>
<th>Nopol</th> 
<th>Sopir</th>
<th>Opsi</th>
</tr>
</thead>
<tbody>
<?php if (empty($kendaraan)): ?>
<tr><td colspan="4" class="empty-state">
    <span class="emoji">🚗</span>
    Data kendaraan kosong. <button class="btn" onclick="openForm()" style="margin-top: 16px; padding: 12px 24px;">Tambah yang pertama!</button>
</td></tr>
<?php else: ?>
<?php $no = 1; foreach($kendaraan as $k): ?>
<tr>
<td data-label="No"><?= $no++ ?></td>
<td data-label="Nopol"><strong><?= htmlspecialchars($k['Nopol']) ?></strong></td>
<td data-label="Sopir"><?= htmlspecialchars($k['Sopir']) ?></td>
<td data-label="Opsi">
    <button class="edit" onclick="editKendaraan(<?= $k['id_Kendaraan'] ?>)">✏️ Edit</button>
    <button class="hapus" onclick="hapusKendaraan(<?= $k['id_Kendaraan'] ?>)">🗑️ Hapus</button>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<!-- FORM SLIDE -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>
<div class="form-slide" id="formSlide">
<h2 id="formTitle"><?= $edit_data ? 'Edit Kendaraan' : 'Tambah Kendaraan Baru' ?></h2>
<form method="POST" id="kendaraanForm">
<input type="hidden" name="action" id="actionInput" value="<?= $edit_data ? 'edit' : 'add' ?>">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id_Kendaraan'] ?>"><?php endif; ?>
<input type="text" name="nopol" id="nopol" placeholder="Contoh: AB 1234 CD" value="<?= htmlspecialchars($edit_data['Nopol'] ?? '') ?>" required maxlength="50">
<input type="text" name="sopir" id="sopir" placeholder="Nama Sopir" value="<?= htmlspecialchars($edit_data['Sopir'] ?? '') ?>" required maxlength="100">
<button type="submit" class="btn-save">💾 Simpan Kendaraan</button>
<button type="button" class="btn-cancel" onclick="closeForm()">❌ Batal</button>
</form>
</div>

<script>
let editId = <?= $edit_data ? (int)$edit_data['id_Kendaraan'] : 'null' ?>;

function openForm() {
    document.getElementById('formSlide').classList.add('active');
    document.getElementById('overlay').classList.add('active');
    document.getElementById('nopol').focus();
}

function closeForm() {
    document.getElementById('formSlide').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
    if (editId) window.location.href = '?';
}

function editKendaraan(id) {
    window.location.href = '?edit=' + id;
}

function hapusKendaraan(id) {
    if (confirm('Yakin hapus kendaraan ini?\nData transaksi terkait TIDAK terpengaruh.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

<?php if ($edit_data): ?>
openForm();
<?php endif; ?>
</script>
</body>
</html>

