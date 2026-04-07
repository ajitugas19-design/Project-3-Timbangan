<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error" style="color: #f44336; text-align: center; padding: 10px; background: #ffebee; border-radius: 5px; margin: 20px;">⚠️ Silakan login terlebih dahulu!</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Halaman User</title>
<style>
* {margin:0;padding:0;box-sizing:border-box;}
body {font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
.container {max-width:1200px;margin:0 auto;}
h1 {text-align:center;color:white;margin-bottom:30px;text-shadow:0 2px 4px rgba(0,0,0,0.3);}

/* ADD BUTTON */
.add-user-btn {background:#4ade80;color:white;padding:15px 30px;border:none;border-radius:12px;font-size:18px;cursor:pointer;box-shadow:0 4px 15px rgba(74,222,128,0.4);transition:all 0.3s;}
.add-user-btn:hover {transform:translateY(-2px);box-shadow:0 6px 20px rgba(74,222,128,0.6);}

/* USER CARDS GRID */
#userList {display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:20px;margin-top:30px;}
.user-card {background:white;border-radius:16px;padding:20px;box-shadow:0 8px 25px rgba(0,0,0,0.15);transition:all 0.3s;cursor:pointer;position:relative;overflow:hidden;}
.user-card:hover {transform:translateY(-5px);box-shadow:0 12px 35px rgba(0,0,0,0.2);}
.user-card.current-user {border:3px solid #10b981;background:linear-gradient(135deg,#ecfdf5,#d1fae5);}

/* AVATAR */
.user-img {width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-size:24px;font-weight:bold;display:flex;align-items:center;justify-content:center;margin-right:15px;flex-shrink:0;}

/* USER INFO */
.user-left {display:flex;align-items:center;}
.user-info h3 {color:#1f2937;font-size:20px;margin:0;}
.user-info p {color:#6b7280;margin:5px 0 0;font-size:14px;}
.role-badge {display:inline-block;background:#3b82f6;color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500;margin-top:5px;}

/* ACTIONS */
.user-action {display:flex;gap:10px;margin-top:15px;}
.edit-btn,.delete-btn {padding:8px 16px;border:none;border-radius:8px;cursor:pointer;font-size:14px;transition:all 0.2s;}
.edit-btn {background:#f59e0b;color:white;}
.delete-btn {background:#ef4444;color:white;}
.delete-btn:disabled {background:#9ca3af;cursor:not-allowed;}
.edit-btn:hover {background:#d97706;}
.delete-btn:hover:not(:disabled) {background:#dc2626;}

/* FORM OVERLAY */
.overlay {position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;display:none;}
.overlay.active {display:block;}
.form-slide {position:fixed;right:-450px;top:0;width:420px;height:100%;background:white;box-shadow:-10px 0 40px rgba(0,0,0,0.3);z-index:1001;transition:right 0.4s ease;padding:30px;overflow-y:auto;max-height:100vh;}
.form-slide.active {right:0;}
.mode-title {color:#1f2937;font-size:22px;margin-bottom:25px;padding-bottom:15px;border-bottom:2px solid #e5e7eb;}
label {display:block;margin:15px 0 5px;color:#374151;font-weight:500;}
input[type="text"],input[type="password"] {width:100%;padding:14px;border:2px solid #e5e7eb;border-radius:10px;font-size:16px;transition:border-color 0.3s;}
input:focus {outline:none;border-color:#4ade80;box-shadow:0 0 0 3px rgba(74,222,128,0.1);}
.btn-save {background:#10b981;color:white;padding:15px;border:none;border-radius:10px;font-size:16px;cursor:pointer;width:100%;margin-top:10px;transition:all 0.3s;}
.btn-save:hover {background:#059669;transform:translateY(-1px);}
.btn-cancel {background:#6b7280;color:white;padding:15px;border:none;border-radius:10px;font-size:16px;cursor:pointer;width:100%;margin-top:10px;transition:all 0.3s;}
.btn-cancel:hover {background:#4b5563;}
.error {color:#ef4444;background:#fef2f2;padding:20px;border-radius:12px;text-align:center;margin:20px;font-size:18px;border:2px solid #fecaca;}

/* RESPONSIVE */
@media (max-width:768px) {
  #userList {grid-template-columns:1fr;gap:15px;}
  .form-slide {width:100%;right:-100%;}
  .user-action {flex-direction:column;}
  body {padding:10px;}
}

/* TOAST */
.toast {position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:15px 25px;border-radius:10px;z-index:9999;min-width:300px;text-align:center;box-shadow:0 10px 30px rgba(16,185,129,0.4);transform:translateX(400px);transition:transform 0.3s;}
.toast.show {transform:translateX(0);}

/* Full style block from previous successful version */
</style>
</head>
<body>
<button class="add-user-btn" onclick="openForm('tambah')">➕ Tambah User Baru</button>
<div id="userList"><div class="loading"><div class="spinner"></div><p>Loading users...</p></div></div>
<div class="overlay" id="overlay" onclick="closeForm()"></div>
<div class="form-slide" id="formSlide">
<h4 id="formTitle" class="mode-title"></h4>
<label>Nama Lengkap <span style="color:red;">*</span></label><input type="text" id="nama" required>
<label>Sebagai <span style="color:red;">*</span></label><input type="text" id="sebagai" required>
<label>Username <span style="color:red;">*</span></label><input type="text" id="username" required>
<label>Password <span style="color:red;">*</span></label><input type="password" id="password" minlength="6" required>
<div id="passwordConfirmContainer" style="display:none;">
<label>Konfirmasi Password <span style="color:red;">*</span></label><input type="password" id="passwordConfirm">
</div>
<label>Foto URL <span style="color:red;">*</span></label><input type="text" id="foto" required>
<label>Keterangan <span style="color:red;">*</span></label><input type="text" id="keterangan" required>
<button class="btn-save" onclick="simpanUser()">💾 Simpan ke DB</button>
<button class="btn-cancel" onclick="closeForm()">❌ Batal</button>
</div>
<script>
// Full working JS with DB API + edit fix
let editTargetId = null;
async function loadUsers() {
    try {
        const response = await fetch('../api/users.php?action=list');
        const users = await response.json();
const container = document.getElementById('userList');
        const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
        container.innerHTML = '';
        users.forEach(user => {
            const initials = user.nama.charAt(0).toUpperCase();
            const isCurrent = user.id_user == currentUserId;
            container.innerHTML += `
                <div class="user-card ${isCurrent ? 'current-user' : ''}" data-id="${user.id_user}">
                    <div class="user-left">
                        <div class="user-img">${initials}</div>
                        <div class="user-info">
                            <h3>${user.nama}</h3>
                            <p>${user.sebagai}</p>
                            ${isCurrent ? '<div class="role-badge">🟢 YOU (Logged In)</div>' : ''}
                        </div>
                    </div>
                    <div class="user-action">
                        <button class="edit-btn" onclick="editUser(${user.id_user})">✏️ Edit</button>
                        <button class="delete-btn" onclick="hapusUser(${user.id_user})" ${isCurrent ? 'disabled' : ''}>🗑️ Hapus</button>
                    </div>
                </div>`;
        });
        // Add protected admin if ID 1 missing
        if (!users.find(u => u.id_user == 1)) {
            container.innerHTML += `
                <div class="user-card" data-id="1">
                    <div class="user-left">
                        <div class="user-img">A</div>
                        <div class="user-info">
                            <b>ADMIN</b>
                            <p>Administrator (Protected)</p>
                        </div>
                    </div>
                    <div class="user-action">
                        <button class="edit-btn" onclick="editUser(1)">✏️ Edit</button>
                        <button class="delete-btn" disabled>🗑️ Protected</button>
                    </div>
                </div>`;
        }
    } catch(e) { console.error(e); }
}
function openForm(mode='tambah') {
    document.getElementById("formSlide").classList.add("active");
    document.getElementById("overlay").classList.add("active");
    const title = document.getElementById("formTitle");
    const passConf = document.getElementById("passwordConfirmContainer");
    if (mode === 'tambah') {
        title.innerHTML = '➕ <span style="color:#27ae60;">TAMBAH BARU ke DB</span>';
        title.parentElement.style.background = '#d4edda';
        passConf.style.display = 'block';
        document.getElementById("username").disabled = false;
        // Clear all
        ['nama','sebagai','username','password','passwordConfirm','foto','keterangan'].forEach(id => document.getElementById(id).value = '');
        document.getElementById("keterangan").value = 'Aktif';
    } else {
        title.innerHTML = '✏️ <span style="color:#f39c12;">EDIT dari DB</span>';
        title.parentElement.style.background = '#fff3cd';
        passConf.style.display = 'none';
        document.getElementById("username").disabled = true;
    }
}
function closeForm() {
    document.getElementById("formSlide").classList.remove("active");
    document.getElementById("overlay").classList.remove("active");
}
async function editUser(id) {
    editTargetId = id;
    openForm('edit');
    try {
        const response = await fetch('../api/users.php?action=list');
        const users = await response.json();
        const user = users.find(u => u.id_user == id) || {nama:'ADMIN',sebagai:'Administrator',username:'admin',foto:'default.jpg',keterangan:'Aktif'};
        document.getElementById("nama").value = user.nama;
        document.getElementById("sebagai").value = user.sebagai;
        document.getElementById("username").value = user.username;
        document.getElementById("foto").value = user.foto;
        document.getElementById("keterangan").value = user.keterangan;
        document.getElementById("password").value = '';
        document.getElementById("formTitle").innerHTML += `: ${user.nama}`;
    } catch(e) { alert('Load edit error'); }
}
async function hapusUser(id) {
    if(id==1) return alert('Admin protected!');
    if(confirm('Hapus user ID '+id+'?')) {
        try {
            const response = await fetch('../api/users.php?action=delete', {
                method: 'POST',
                body: 'id='+id
            });
            if(response.ok) {
                loadUsers();
            } else {
                alert('Hapus gagal');
            }
        } catch(e) { alert('Network error'); }
    }
}
async function simpanUser() {
    const data = {
        nama: document.getElementById("nama").value.trim(),
        sebagai: document.getElementById("sebagai").value.trim(),
        username: document.getElementById("username").value.trim(),
        password: document.getElementById("password").value,
        foto: document.getElementById("foto").value.trim(),
        keterangan: document.getElementById("keterangan").value.trim()
    };
    
    // TAMBAH validation (semua wajib)
    if (!editTargetId) {
        for(let key of ['nama','sebagai','username','foto','keterangan']) if(!data[key]) return showError(document.getElementById(key), key+' wajib!');
        if(data.password.length < 6) return showError(document.getElementById("password"), 'Password min 6!');
        if(data.password !== document.getElementById("passwordConfirm").value) return showError(document.getElementById("passwordConfirm"), 'Tidak cocok!');
    }
    
    try {
        const action = editTargetId ? 'edit' : 'add';
        const response = await fetch(`../api/users.php?action=${action}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({...data, id: editTargetId})
        });
        if(response.ok) {
            loadUsers();
            closeForm();
            showSuccess(editTargetId ? 'User diupdate DB!' : 'User baru ditambah DB!');
        } else {
            const err = await response.text();
            alert('Error: '+err);
        }
    } catch(e) {
        alert('Error: '+e.message);
    }
}
function showError(el, msg) {
    el.style.border = '2px solid #e74c3c';
    el.focus();
    alert(msg);
    setTimeout(() => el.style.border = '', 3000);
}
function showSuccess(msg) {
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#27ae60;color:white;padding:15px 20px;border-radius:5px;z-index:10001;min-width:250px;text-align:center;';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// INIT
loadUsers();
console.log('User.php full DB working ✅');
</script>
</body>
</html>
