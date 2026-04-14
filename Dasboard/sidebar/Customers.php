<?php
session_start();
require_once '../../config.php';
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}



// Load data
$stmt = $pdo->query("SELECT * FROM customers ORDER BY id_Customers DESC");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id_Customers = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers</title>
<style>
/* Empty - use dashboard CSS */
  --danger: #ef4444;
  --warning: #eab308;
  --dark: #374151;
  --light: #f3f4f6;
  --shadow: 0 4px 6px rgba(0,0,0,0.1);
}
body { font-family: Arial; background: linear-gradient(135deg,#f3f4f6,#e5e7eb); padding:20px; }
.btn{ background:var(--primary); color:white; padding:10px 20px; border:none; border-radius:10px; cursor:pointer; }
.table-container{ background:white; border-radius:12px; margin-top:20px; overflow:hidden; box-shadow:var(--shadow); }
table{ width:100%; border-collapse:collapse; }
th{ background:#374151; color:white; padding:12px; }
td{ padding:12px; border-bottom:1px solid #eee; }
.edit{ background:#f59e0b; color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:8px; font-size:14px; transition:all 0.2s; margin-right:5px; }
.edit:hover{ background:#d97706; }
.hapus{ background:#ef4444; color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:8px; font-size:14px; transition:all 0.2s; }
.hapus:hover{ background:#dc2626; }
.form-slide{ position:fixed; right:-400px; top:0; width:350px; height:100%; background:white; padding:20px; transition:0.3s; box-shadow:-5px 0 20px rgba(0,0,0,0.2); z-index:1002; }
.form-slide.active{ right:0; }
.form-overlay{ position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; z-index:1001; }
.form-overlay.active{ display:block; }
input{ width:100%; padding:10px; margin-bottom:10px; }
.btn-save{ background:green; color:white; padding:10px; width:100%; border:none; border-radius:8px; cursor:pointer; }
.btn-save:hover{ background:#059669; transform:translateY(-1px); }
.btn-cancel{ background:#6b7280; color:white; padding:12px; width:100%; border:none; border-radius:8px; margin-top:10px; font-size:16px; cursor:pointer; transition:all 0.3s; }
.btn-cancel:hover{ background:#4b5563; transform:translateY(-1px); }
.message{ padding:15px; margin:20px 0; border-radius:12px; text-align:center; font-weight:bold; }
.success{ background:#d1fae5; color:#065f46; border:2px solid #a7f3d0; }
.error{ background:#fef2f2; color:#991b1b; border:2px solid #fecaca; }
@media (max-width:768px) {
  table, thead, tbody, th, td, tr {display:block;}
  thead tr {position:absolute;top:-9999px;left:-9999px;}
  tr {border:1px solid #e5e7eb;margin-bottom:10px;border-radius:10px;padding:15px;background:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
  td {border:none;position:relative;padding-left:50%;text-align:right;}
  td:before {content:attr(data-label);position:absolute;left:10px;width:45%;font-weight:bold;color:#374151;}
  td:nth-of-type(1):before {content:"No: ";}
  td:nth-of-type(2):before {content:"Nama: ";}
  td:nth-of-type(3):before {content:"Keterangan: ";}
  td:nth-of-type(4):before {content:"Pesanan: ";}
  td:nth-of-type(5):before {content:"Aksi: ";}
}
</style>
</head>
<body>

<div id="messageContainer"></div>

<button class="btn" onclick="openForm()" <?= $edit_data ? 'style="display:none;"' : '' ?>>+ Tambah</button>
<input type="hidden" id="tableContainer" value=".table-container">
<input type="hidden" id="apiEndpoint" value="api/customers_crud.php">
<?php if ($edit_data): ?>
<button class="btn" style="background:orange;" onclick="window.location.href='?';">Kembali ke List</button>
<?php endif; ?>

<?php if (!$edit_data): ?>
<div class="table-container">
<table>
<thead>
<tr>
<th>No</th><th>Nama Customer</th><th>Keterangan</th><th>Total Pesanan</th><th>Opsi</th>
</tr>
</thead>
<tbody>
<?php if (empty($customers)): ?>
<tr><td colspan="5" style="text-align:center;padding:40px;color:#6b7280;">📭 Data customers kosong. <button class="btn" onclick="openForm()" style="margin-top:10px;">Tambah yang pertama!</button></td></tr>
<?php else: ?>
<?php $no = 1; foreach($customers as $c): ?>
<tr>
<td data-label="No"><?= $no++ ?></td>
<td data-label="Nama"><strong><?= htmlspecialchars($c['Customers']) ?></strong></td>
<td data-label="Keterangan"><?= htmlspecialchars($c['Keterangan'] ?? '-') ?></td>
<td data-label="Pesanan"><span style="color:#10b981;font-weight:bold;">📦 0 Pesanan<br><small>(Belum ada data pesanan)</small></span></td>
<td data-label="Aksi">
    <button class="edit" onclick="editCustomer(<?= $c['id_Customers'] ?>)">✏️ Edit</button>
    <button class="hapus" onclick="hapusCustomer(<?= $c['id_Customers'] ?>)" data-action="delete">🗑️ Hapus</button>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<!-- FORM (shown if edit or add) -->
<div class="form-overlay" id="overlay" onclick="closeForm()"></div>
<div class="form-slide" id="formSlide">
<h3 id="formTitle"><?= $edit_data ? 'Edit Customer' : 'Tambah Customer' ?></h3>
<form method="POST" id="customerForm" data-crud-form>
<input type="hidden" name="action" id="actionInput" value="<?= $edit_data ? 'edit' : 'add' ?>">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id_Customers'] ?>"><?php endif; ?>
<input type="text" name="nama" id="nama" placeholder="Nama" value="<?= htmlspecialchars($edit_data['Customers'] ?? '') ?>" required>
<input type="text" name="keterangan" id="keterangan" placeholder="Keterangan" value="<?= htmlspecialchars($edit_data['Keterangan'] ?? '') ?>">
<button type="submit" class="btn-save">Simpan</button>
<button type="button" class="btn-cancel" onclick="closeForm()">Batal</button>
</form>
</div>

<script>
let editId = <?= $edit_data ? (int)$edit_data['id_Customers'] : 'null' ?>;

function openForm() {
    document.getElementById('formSlide').classList.add('active');
    document.getElementById('overlay').classList.add('active');
}

function closeForm() {
    document.getElementById('formSlide').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
    editId = null;
    // No reload - stay on list
}

function editCustomer(id) {
    fetch(`?edit=${id}`)
      .then(res => res.text())
      .then(html => {
        document.open();
        document.write(html);
        document.close();
        openForm(); // Re-attach listeners
      });
}

function hapusCustomer(id) {
    if (confirm('Yakin hapus customer ini?')) {
        crudAjax({action: 'delete', id: id});
    }
}

<?php if ($edit_data): ?>
openForm();
<?php endif; ?>

// Universal CRUD AJAX
function crudAjax(data) {
  const apiUrl = document.getElementById('apiEndpoint').value;
  const tableSel = document.getElementById('tableContainer').value;
  const form = document.getElementById('customerForm');
  
  const formData = new FormData(form || new FormData());
  Object.entries(data).forEach(([k,v]) => formData.append(k,v));
  
  const btn = form?.querySelector('button[type="submit"]') || document.activeElement;
  const origText = btn?.innerHTML;
  btn && (btn.innerHTML = '⏳ ...');
  btn && (btn.disabled = true);
  
  fetch(apiUrl, {method: 'POST', body: formData})
    .then(res => res.json())
    .then(result => {
      showMessage(result.message, result.success ? 'success' : 'error');
      if (result.success) {
        form?.reset();
        closeForm();
        if (window.parent?.loadContent) {
          window.parent.loadContent('sidebar/Customers.php');
        } else {
          location.reload();
        }
      }
    })
    .catch(err => showMessage('❌ Gagal: ' + err, 'error'))
    .finally(() => {
      btn && (btn.innerHTML = origText);
      btn && (btn.disabled = false);
    });
}

function showMessage(msg, type) {
  const cont = document.getElementById('messageContainer');
  cont.innerHTML = `<div class="message ${type}">${msg}</div>`;
  setTimeout(() => cont.innerHTML = '', 5000);
}

// Form submit handler
document.getElementById('customerForm')?.addEventListener('submit', e => {
  e.preventDefault();
  crudAjax({});
});
</script>
</body>
</html>
