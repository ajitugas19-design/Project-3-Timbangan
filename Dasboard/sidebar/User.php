<?php
session_start();
require_once '../../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Location: ../../Index.php');
    exit;
}

// ================= HANDLE AJAX =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $nama = trim($_POST['nama']);
            $pass = trim($_POST['password']);
            $user = trim($_POST['username'] ?? '');
            $role = trim($_POST['sebagai'] ?? '');
            $ket = trim($_POST['keterangan'] ?? '');

            if (!$nama || !$pass) throw new Exception('Nama & Password wajib');

            $foto = isset($_FILES['foto']) && $_FILES['foto']['name'] 
                ? uploadFoto($_FILES['foto']) 
                : null;

            $pdo->prepare("INSERT INTO user (nama,user,password,sebagai,foto,keterangan) VALUES (?,?,?,?,?,?)")
                ->execute([$nama, $user ?: null, password_hash($pass, PASSWORD_DEFAULT), $role, $foto, $ket]);

            echo json_encode(['success'=>true,'message'=>'✅ User baru ditambah']);
        }

        elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama']);
            $user = trim($_POST['username'] ?? '');
            $role = trim($_POST['sebagai'] ?? '');
            $ket = trim($_POST['keterangan'] ?? '');
            $pass = trim($_POST['password'] ?? '');

            if (!$id || !$nama) throw new Exception('Data tidak valid');

            $query = "UPDATE user SET nama=?, keterangan=?";
            $params = [$nama, $ket];

            if ($user !== '') {
                $query .= ", user=?";
                $params[] = $user;
            }

            if ($role !== '') {
                $query .= ", sebagai=?";
                $params[] = $role;
            }

            if ($pass !== '') {
                $query .= ", password=?";
                $params[] = password_hash($pass, PASSWORD_DEFAULT);
            }

            $foto = isset($_FILES['foto']) && $_FILES['foto']['name'] 
                ? uploadFoto($_FILES['foto']) 
                : null;

            if ($foto) {
                $query .= ", foto=?";
                $params[] = $foto;
            }

            $query .= " WHERE id_user=?";
            $params[] = $id;

            $pdo->prepare($query)->execute($params);

            echo json_encode(['success'=>true,'message'=>'✅ User berhasil diupdate']);
        }

        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            if ($id == 1) throw new Exception("Admin tidak bisa dihapus");

            $pdo->prepare("DELETE FROM user WHERE id_user=?")->execute([$id]);

            echo json_encode(['success'=>true,'message'=>'🗑️ User dihapus']);
        }

        exit;

    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'❌ ' . $e->getMessage()]);
        exit;
    }
}

function uploadFoto($file) {
    $folder = __DIR__ . "/../../uploads/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $namaFile = time() . '_' . basename($file['name']);
    $path = $folder . $namaFile;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $namaFile; // simpan nama file saja
    }

    return null;
}

// ================= LOAD DATA =================
$users = $pdo->query("SELECT * FROM user ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manajemen User</title>

<style>
/* Scoped: User */
.page-user {
  max-width: 1000px;
  margin: 0 auto;
  background: white;
  border-radius: 12px;
  box-shadow: var(--shadow);
  overflow: hidden;
  padding: 20px;
}

.page-user img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
.page-user .action { display: flex; gap: 6px; }

.page-user th:nth-child(1), .page-user td:nth-child(1){ width:70px; text-align:center; }
.page-user th:nth-child(2), .page-user td:nth-child(2){ text-align:left; }
.page-user th:nth-child(3), .page-user td:nth-child(3){ text-align:left; }
.page-user th:nth-child(4), .page-user td:nth-child(4){ text-align:left; }
.page-user th:nth-child(5), .page-user td:nth-child(5){ width:180px; text-align:center; }
</style>
</head>

<body>

<div class="page-user">
<button class="btn btn-success" onclick="openAdd()"> Tambah User </button>

<div class="table-container">
<table class="data-table">
<thead>
<tr>
<th>Foto</th>
<th>Nama</th>
<th>Username</th>
<th>Role</th>
<th>Opsi</th>
</tr>
</thead>
<tbody id="tbody">
<?php $no=1; foreach($users as $u): ?>
<tr>
<td><img src="/Project_3/uploads/<?= basename($u['foto'] ?: 'default.png') ?>" onerror="this.src='/Project_3/uploads/default.png'" style="width:45px;height:45px;border-radius:50%;object-fit:cover;"></td>
<td><?= htmlspecialchars($u['nama']) ?></td>
<td><?= htmlspecialchars($u['user'] ?? '-') ?></td>
<td><?= htmlspecialchars($u['sebagai'] ?? '-') ?></td>
<td>
<div class="action">
<button class="edit-btn" onclick='openEdit(<?= json_encode($u) ?>)'> Edit</button>
<button class="delete-btn" onclick="hapus(<?= $u['id_user'] ?>)"> Hapus</button>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- FORM -->
<div id="overlay" class="form-overlay" onclick="closeForm()"></div>

<div id="slide" class="form-slide">
<h3 id="title">Tambah User</h3>
<form id="form">
<input type="hidden" name="action" id="action">
<input type="hidden" name="id" id="id">

<input type="text" name="nama" id="nama" placeholder="Nama Lengkap *" required>
<input type="text" name="username" id="username" placeholder="Username">
<input type="password" name="password" id="password" placeholder="Password Baru">
<input type="text" name="sebagai" id="sebagai" placeholder="Role (Admin/User)">
<input type="file" name="foto" id="foto" accept="image/*">
<textarea name="keterangan" id="ket" placeholder="Keterangan"></textarea>

<button type="submit" class="btn-save">💾 Simpan</button>
<button type="button" class="btn-cancel-form" onclick="closeForm()">✖️ Batal</button>

</form>
</div>

<script src="/Project_3/Dasboard/js/enter-next.js"></script>
<script>
(function(){
const BASE_URL = 'sidebar/User.php';
const form = document.getElementById('form');
const overlay = document.getElementById('overlay');
const slide = document.getElementById('slide');
const tbody = document.getElementById('tbody');
const title = document.getElementById('title');

if (typeof initEnterNext === 'function') initEnterNext(form);

// ===== FORM TOGGLE =====
function openForm() {
    slide.classList.add('active');
    overlay.classList.add('active');
}
// FIX GLOBAL AGAR BISA DIPANGGIL DARI HTML
window.closeForm = function() {
    slide.classList.remove('active');
    overlay.classList.remove('active');
    form.reset();
};

// ===== ADD =====
window.openAdd = function() {
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    title.innerText = 'Tambah User';
    document.getElementById('password').required = true;
    openForm();
};

// ===== EDIT =====
window.openEdit = function(d) {
    document.getElementById('action').value = 'edit';
    document.getElementById('id').value = d.id_user;
    document.getElementById('nama').value = d.nama;
    document.getElementById('username').value = d.user || '';
    document.getElementById('sebagai').value = d.sebagai || '';
    document.getElementById('ket').value = d.keterangan || '';
    document.getElementById('password').required = false;
    title.innerText = 'Edit User';
    openForm();
};

// ===== DELETE =====
window.hapus = function(id) {
    if (!confirm('Yakin hapus user ini?')) return;
    
    let fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    
    fetch(BASE_URL, {method: 'POST', body: fd})
    .then(r => r.json())
    .then(res => {
        show(res.message, res.success);
        if (res.success) loadTable();
    }).catch(err => show('Gagal hapus', false));
};

// ===== FORM SUBMIT =====
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    let fd = new FormData(form);
    
    fetch(BASE_URL, {method: 'POST', body: fd})
    .then(r => r.json())
    .then(res => {
        show(res.message, res.success);
        if (res.success) {
            closeForm();
            loadTable();
        }
    }).catch(err => show('Gagal simpan', false));
});

// ===== TABLE RELOAD =====
function loadTable() {
    fetch(BASE_URL)
    .then(r => r.text())
    .then(html => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newTbody = doc.querySelector('#tbody');
        if (newTbody) tbody.innerHTML = newTbody.innerHTML;
    });
}

// ===== NOTIFICATION =====
function show(msg, success = true) {
    let div = document.createElement('div');
    div.className = `message-toast ${success ? 'success' : 'error'} show`;
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}
})();
</script>

</body>
</html>

