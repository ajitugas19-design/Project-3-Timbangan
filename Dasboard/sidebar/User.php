<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Halaman User</title>

<style>
body {
    margin:0 !important;
    font-family:Arial, sans-serif !important;
    background:#e9e9e9 !important;
    position: relative !important;
    padding: 20px 0 !important;
}

/* TAMBAH USER BUTTON */
.add-user-btn {
    background:#3498db;
    color:white;
    padding:12px 25px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:16px;
    margin: 0 20px 20px 20px;
    display: block;
}

/* USER LIST */
#userList {
    margin: 0;
}

/* CARD */
.user-card{
    background:#ffffff;
    margin:0 20px 15px 20px !important;
    padding:20px;
    border-radius:8px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border:1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s;
}

.user-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* LEFT */
.user-left{
    display:flex;
    align-items:center;
    gap:15px;
    flex:1;
}

.user-img{
    width:60px;
    height:60px;
    border-radius:50%;
    background: linear-gradient(45deg, #3498db, #2980b9);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:bold;
    font-size:18px;
    flex-shrink:0;
}

/* USER INFO */
.user-info b {
    font-size:16px;
    color:#2c3e50;
}

.user-info p {
    font-size:12px;
    color:#7f8c8d;
    margin:0;
}

/* ACTION BUTTON */
.user-action {
    display:flex;
    gap:8px;
}

.user-action button{
    padding:8px 16px;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-size:14px;
    transition:0.2s;
}

.edit-btn {
    background:gold !important;
    color:#333 !important;
}

.edit-btn:hover {
    background:#f1c40f !important;
}

.delete-btn {
    background:#e74c3c !important;
    color:white !important;
}

.delete-btn:hover {
    background:#c0392b !important;
}

/* SLIDE FORM - HIGH Z-INDEX */
.form-slide {
    position:fixed !important;
    top:0 !important;
    right:-350px;
    width:320px;
    height:100vh !important;
    background:#fff;
    padding:25px;
    transition:0.3s;
    box-shadow:-8px 0 25px rgba(0,0,0,0.3);
    z-index: 9999 !important;
    box-sizing:border-box;
    overflow-y:auto;
}

.form-slide.active {
    right:0 !important;
}

.form-slide h4 {
    margin-top:0;
    color:#2c3e50;
}

.form-slide input {
    width:100%;
    padding:12px;
    margin:12px 0;
    border:1px solid #ddd;
    border-radius:6px;
    box-sizing:border-box;
    font-size:14px;
}

.form-slide input:focus {
    border-color:#3498db;
    outline:none;
    box-shadow:0 0 0 2px rgba(52,152,219,0.2);
}

/* OVERLAY */
.overlay {
    position:fixed !important;
    top:0 !important;
    left:0 !important;
    width:100vw !important;
    height:100vh !important;
    background:rgba(0,0,0,0.6) !important;
    opacity:0;
    visibility:hidden;
    transition:0.3s;
    z-index: 5000 !important;
    cursor:pointer;
}

.overlay.active {
    opacity:1 !important;
    visibility:visible !important;
}

/* RESPONSIVE */
@media (max-width:768px) {
    .user-card {
        margin:0 10px 15px 10px !important;
        flex-direction:column;
        gap:15px;
        text-align:center;
    }
    
    .user-left {
        justify-content:center;
    }
    
    .user-action {
        justify-content:center;
    }
    
    .form-slide {
        width:100% !important;
        right:-100% !important;
    }
    
    .form-slide.active {
        right:0 !important;
    }
}
</style>
</head>

<body>

<!-- TAMBAH USER BUTTON -->
<button class="add-user-btn" onclick="openForm('tambah')">➕ Tambah User Baru</button>

<!-- LIST USER -->
<div id="userList">

    <!-- DEFAULT ADMIN -->
    <div class="user-card">
        <div class="user-left">
            <div class="user-img">A</div>
            <div class="user-info">
                <b>ADMIN</b>
                <p style="font-size:12px;">Administrator</p>
            </div>
        </div>

        <div class="user-action">
            <button class="edit-btn" onclick="editUser(this)">✏️ Edit</button>
            <button class="delete-btn" onclick="hapusUser(this)">🗑️ Hapus</button>
        </div>
    </div>

</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="closeForm()"></div>

<!-- FORM SLIDE -->
<div class="form-slide" id="formSlide">
    <h4 id="formTitle">Tambah User</h4>

    <input type="text" id="nama" placeholder="Nama Lengkap">
    <input type="text" id="sebagai" placeholder="Sebagai (misal: Admin)">
    <input type="text" id="username" placeholder="Username">
    <input type="password" id="password" placeholder="Password">
    <input type="text" id="foto" placeholder="Foto URL (default.jpg)">
    <input type="text" id="keterangan" placeholder="Keterangan (Aktif/Nonaktif)">

    <button class="btn btn-save" onclick="simpanUser()">💾 Simpan</button>
    <br><br>
    <button class="btn btn-cancel" onclick="closeForm()">❌ Kembali</button>
</div>

<script>
// Load users on page load
async function loadUsers() {
    try {
        const response = await fetch('../api/users.php?action=list');
        const users = await response.json();
        const userList = document.getElementById('userList');
        userList.innerHTML = '';

        users.forEach(user => {
            const initials = user.nama.charAt(0).toUpperCase();
            const card = `
                <div class="user-card" data-id="${user.id_user}">
                    <div class="user-left">
                        <div class="user-img">${initials}</div>
                        <div class="user-info">
                            <b>${user.nama}</b>
                            <p>${user.sebagai}</p>
                        </div>
                    </div>
                    <div class="user-action">
                        <button class="edit-btn" onclick="editUser(${user.id_user}, '${user.nama}')">✏️ Edit</button>
                        <button class="delete-btn" onclick="hapusUser(${user.id_user})">🗑️ Hapus</button>
                    </div>
                </div>
            `;
            userList.insertAdjacentHTML('beforeend', card);
        });
    } catch (e) {
        console.error('Error loading users:', e);
    }
}

// Global scope for injected context
let editTargetId = null;

window.openForm = function(mode = 'tambah'){
    const formSlide = document.getElementById("formSlide");
    const overlay = document.getElementById("overlay");
    if(formSlide) formSlide.classList.add("active");
    if(overlay) overlay.classList.add("active");
    document.getElementById("formTitle").innerText = mode === 'edit' ? 'Edit User' : 'Tambah User Baru';
};

window.closeForm = function(){
    const formSlide = document.getElementById("formSlide");
    const overlay = document.getElementById("overlay");
    if(formSlide) formSlide.classList.remove("active");
    if(overlay) overlay.classList.remove("active");
    if(!editTargetId) {
        // Reset untuk tambah baru
        document.getElementById("nama").value = "";
        document.getElementById("sebagai").value = "";
        document.getElementById("username").value = "";
        document.getElementById("password").value = "";
        document.getElementById("foto").value = "";
        document.getElementById("keterangan").value = "Aktif";
    }
    document.getElementById("formTitle").innerText = "Tambah User Baru";
    editTargetId = null;
};

window.simpanUser = async function(){
    const namaEl = document.getElementById("nama");
    const sebagaiEl = document.getElementById("sebagai");
    const userEl = document.getElementById("username");
    const passEl = document.getElementById("password");
    const fotoEl = document.getElementById("foto");
    const ketEl = document.getElementById("keterangan");
    
    const nama = namaEl.value.trim();
    if(!nama || !userEl.value.trim()) {
        alert("Nama & Username wajib diisi!");
        return;
    }

    try {
        const data = {
            nama,
            sebagai: sebagaiEl.value || 'User',
            username: userEl.value,
            password: passEl.value,
            foto: fotoEl.value || 'default.jpg',
            keterangan: ketEl.value || 'Aktif'
        };
        let url = '../api/users.php?action=' + (editTargetId ? 'edit' : 'add');
        
        if(editTargetId) data.id = editTargetId;

        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        if(response.ok) {
            loadUsers();
            window.closeForm();
        } else {
            const err = await response.text();
            alert('Error: ' + err);
        }
    } catch(e) {
        alert('Error: ' + e.message);
    }
};

window.editUser = async function(id, nama) {
    editTargetId = id;
    try {
        const response = await fetch('../api/users.php?action=list');
        const users = await response.json();
        const user = users.find(u => u.id_user == id);
        if(user) {
            document.getElementById("nama").value = user.nama;
            document.getElementById("sebagai").value = user.sebagai;
            document.getElementById("username").value = user.username;
            document.getElementById("foto").value = user.foto;
            document.getElementById("keterangan").value = user.keterangan;
            document.getElementById("formTitle").innerText = "Edit User: " + user.nama;
        }
    } catch(e) {}
    window.openForm();
};

window.hapusUser = async function(id) {
    if(confirm("Yakin hapus user ini?")) {
        try {
            const response = await fetch('../api/users.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            });
            if(response.ok) {
                loadUsers();
            }
        } catch(e) {
            alert('Error hapus user');
        }
    }
};

// Auto exec: load users when injected
loadUsers();
console.log('User.php dengan DB loaded');
</script>

</body>
</html>

