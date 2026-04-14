<?php
session_start();
require_once '../../config.php';

header('Content-Type: text/html; charset=utf-8');

// AUTH CHECK - AJAX FRIENDLY
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '⚠️ Session expired. Silakan login ulang.']);
        exit;
    } else {
        echo '<div style="color:red;text-align:center;padding:50px;font-size:18px;">⚠️ Silakan login terlebih dahulu!</div>';
        exit;
    }
}

$current_user_id = $_SESSION['user_id'];

// ================= HANDLE AJAX POST =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        $response = ['success' => true];

        if ($action === 'add') {
            $nama = trim($_POST['nama']);
            $sebagai = trim($_POST['sebagai']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $foto = trim($_POST['foto']);
            $keterangan = trim($_POST['keterangan']);

            if (empty($nama) || empty($username) || empty($password)) {
                throw new Exception('Nama, username, dan password wajib diisi');
            }

            // Check duplicate
            $check = $pdo->prepare('SELECT id_user FROM user WHERE `user` = ? OR nama = ?');
            $check->execute([$username, $nama]);
            if ($check->rowCount() > 0) {
                throw new Exception('Username atau nama sudah ada');
            }

            // Use md5 to match Navbar.php login
            $stmt = $pdo->prepare("INSERT INTO user (nama, sebagai, `user`, password, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $sebagai, $username, md5($password), $foto, $keterangan]);
            $response['message'] = '✅ User baru berhasil ditambahkan!';

        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            if (!$id || $id === $current_user_id) {
                throw new Exception('ID tidak valid atau tidak bisa edit diri sendiri');
            }

            $nama = trim($_POST['nama']);
            $sebagai = trim($_POST['sebagai']);
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $foto = trim($_POST['foto']);
            $keterangan = trim($_POST['keterangan']);

            if (empty($nama) || empty($username)) {
                throw new Exception('Nama dan username wajib diisi');
            }

            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE user SET nama=?, sebagai=?, `user`=?, password=?, foto=?, keterangan=? WHERE id_user=?");
                $stmt->execute([$nama, $sebagai, $username, md5($password), $foto, $keterangan, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE user SET nama=?, sebagai=?, `user`=?, foto=?, keterangan=? WHERE id_user=?");
                $stmt->execute([$nama, $sebagai, $username, $foto, $keterangan, $id]);
            }
            $response['message'] = '✅ User berhasil diupdate!';

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            if (!$id || $id === $current_user_id || $id === 1) {
                throw new Exception('Tidak bisa hapus admin atau diri sendiri');
            }
            $stmt = $pdo->prepare("DELETE FROM user WHERE id_user=?");
            $stmt->execute([$id]);
            $response['message'] = '✅ User berhasil dihapus!';
        }

        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '❌ ' . $e->getMessage()]);
        exit;
    }
}

// ================= LOAD DATA =================
$users = $pdo->query("SELECT * FROM user ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<style>
/* Empty - use dashboard CSS */
.container{max-width:1100px;margin:auto;background:white;padding:30px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
h2{text-align:center;color:#1f2937;margin-bottom:25px;}
.btn{background:#10b981;color:white;padding:12px 24px;border:none;border-radius:10px;cursor:pointer;font-size:16px;transition:0.2s;}
.btn:hover{background:#059669;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th{background:#111827;color:white;padding:15px;text-align:left;font-weight:600;}
td{padding:15px;border-bottom:1px solid #e5e7eb;}
.user-info{display:flex;align-items:center;gap:12px;}
.user-info img{width:50px;height:50px;border-radius:50%;object-fit:cover;border:3px solid #10b981;}
.status{padding:6px 12px;border-radius:20px;font-size:12px;font-weight:bold;display:inline-block;}
.online{background:#d1fae5;color:#065f46;}
.offline{background:#fee2e2;color:#991b1b;}
.edit-btn{background:#f59e0b;color:white;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;margin-right:5px;}
.delete-btn{background:#ef4444;color:white;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;}
.form-slide{position:fixed;right:-400px;top:0;width:380px;height:100%;background:white;padding:25px;transition:.3s;z-index:1002;box-shadow:-10px 0 30px rgba(0,0,0,0.3);overflow-y:auto;}
.form-slide.active{right:0;}
.overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;z-index:1001;}
.overlay.active{display:block;}
input,textarea,select{width:100%;padding:12px;margin-bottom:15px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;font-size:14px;}
input:focus,textarea:focus,select:focus{outline:none;border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,0.1);}
.message{position:fixed;top:20px;right:20px;padding:15px 20px;border-radius:10px;color:white;font-weight:500;z-index:1003;max-width:350px;transform:translateX(400px);transition:.3s;}
.message.success{background:#10b981;}
.message.error{background:#ef4444;}
.alert{display:flex;align-items:center;gap:10px;padding:12px;border-radius:8px;margin:10px 0;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
</style>
</head>
<body>

<div class="container">
<h2>👥 Manajemen User</h2>
<button class="btn" onclick="openForm('add')">➕ Tambah User</button>

<table>
<tr>
<th>Nama</th><th>Role</th><th>Username</th><th>Status</th><th>Info</th><th>Aksi</th>
</tr>
<?php foreach($users as $u): ?>
<tr>
<td>
<div class="user-info">
<img src="<?= htmlspecialchars($u['foto'] ?: 'https://ui-avatars.com/api/?name='.urlencode($u['nama']).'&size=50&background=10b981&color=fff') ?>" alt="">
<div><b><?= htmlspecialchars($u['nama']) ?></b><br><small><?= htmlspecialchars($u['keterangan']) ?></small></div>
</div>
</td>
<td><?= htmlspecialchars($u['sebagai']) ?></td>
<td><code><?= htmlspecialchars($u['user']) ?></code></td>
<td><?= $u['id_user'] == $current_user_id ? '<span class="status online">🟢 Online</span>' : '<span class="status offline">🔴 Offline</span>' ?></td>
<td><?= $u['id_user'] == $current_user_id ? 'Anda' : 'User lain' ?></td>
<td>
<button class="edit-btn" onclick="openForm('edit', {id_user:<?= $u['id_user'] ?>, nama:'<?= addslashes($u['nama']) ?>', sebagai:'<?= addslashes($u['sebagai']) ?>', user:'<?= addslashes($u['user']) ?>', foto:'<?= addslashes($u['foto']) ?>', keterangan:'<?= addslashes($u['keterangan']) ?>'})">Edit</button>
<button class="delete-btn" onclick="deleteUser(<?= $u['id_user'] ?>)">Hapus</button>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- MODAL FORM -->
<div class="overlay" id="overlay" onclick="closeForm()"></div>
<div class="form-slide" id="formSlide">
<h3 id="formTitle">Tambah User Baru</h3>
<form id="userForm">
<input type="hidden" name="action" id="actionType">
<input type="hidden" name="id" id="editId">

<div class="input-group">
<label>Nama Lengkap *</label>
<input name="nama" id="nama" required>
</div>

<div class="input-group">
<label>Role / Jabatan</label>
<input name="sebagai" id="sebagai">
</div>

<div class="input-group">
<label>Username *</label>
<input name="username" id="username" required>
</div>

<div class="input-group">
<label>Password <small>(kosongkan untuk edit tanpa ganti password)</small></label>
<input type="password" name="password" id="password">
</div>

<div class="input-group">
<label>URL Foto Profil</label>
<input name="foto" id="foto" placeholder="https://example.com/photo.jpg">
</div>

<div class="input-group">
<label>Keterangan</label>
<textarea name="keterangan" id="keterangan" rows="3" placeholder="Deskripsi tambahan..."></textarea>
</div>

<button type="submit" class="btn" style="background:#10b981;">💾 Simpan</button>
<button type="button" class="btn" onclick="closeForm()" style="background:#6b7280;">❌ Batal</button>
</form>
</div>

<script>
// Universal form submit handler
document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '⏳ Menyimpan...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('sidebar/User.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message || 'Berhasil!', 'success');
            closeForm();
            // Reload parent content
            if (window.parent && window.parent.loadContent) {
                window.parent.loadContent('sidebar/User.php', 'User');
            } else {
                location.reload();
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showMessage(error.message || 'Terjadi kesalahan', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

function openForm(mode = 'add', userData = null) {
    const form = document.getElementById('userForm');
    const slide = document.getElementById('formSlide');
    const overlay = document.getElementById('overlay');
    
    form.reset();
    document.getElementById('actionType').value = mode;
    
    if (mode === 'edit' && userData) {
        document.getElementById('formTitle').textContent = 'Edit User: ' + userData.nama;
        document.getElementById('editId').value = userData.id_user;
        document.getElementById('nama').value = userData.nama;
        document.getElementById('sebagai').value = userData.sebagai;
        document.getElementById('username').value = userData.user;
        document.getElementById('foto').value = userData.foto;
        document.getElementById('keterangan').value = userData.keterangan;
        document.getElementById('password').placeholder = 'Kosongkan untuk tidak ubah password';
    } else {
        document.getElementById('formTitle').textContent = 'Tambah User Baru';
        document.getElementById('password').placeholder = 'Password minimal 6 karakter';
    }
    
    slide.classList.add('active');
    overlay.classList.add('active');
}

function closeForm() {
    document.getElementById('formSlide').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

function deleteUser(id) {
    if (confirm('Yakin hapus user ini?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('sidebar/User.php', {method: 'POST', body: formData})
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showMessage(result.message, 'success');
                    if (window.parent && window.parent.loadContent) {
                        window.parent.loadContent('sidebar/User.php', 'User');
                    } else {
                        location.reload();
                    }
                } else {
                    alert(result.message);
                }
            })
            .catch(err => alert('Error: ' + err));
    }
}

function showMessage(text, type) {
    const msg = document.createElement('div');
    msg.className = `message ${type}`;
    msg.textContent = text;
    document.body.appendChild(msg);
    
    setTimeout(() => {
        msg.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        msg.remove();
    }, 4000);
}
</script>

</body>
</html>

