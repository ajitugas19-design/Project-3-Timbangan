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
<button class="add-user-btn" onclick="openForm()">➕ Tambah User</button>

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

    <input type="text" id="username" placeholder="Username">
    <input type="password" id="password" placeholder="Password">

    <button class="btn btn-save" onclick="simpanUser()">💾 Simpan</button>
    <br><br>
    <button class="btn btn-cancel" onclick="closeForm()">❌ Kembali</button>
</div>

<script>
// Global scope for injected context
window.editTarget = null;

window.openForm = function(){
    const formSlide = document.getElementById("formSlide");
    const overlay = document.getElementById("overlay");
    if(formSlide) formSlide.classList.add("active");
    if(overlay) overlay.classList.add("active");
    console.log('openForm called');
};

window.closeForm = function(){
    const formSlide = document.getElementById("formSlide");
    const overlay = document.getElementById("overlay");
    if(formSlide) formSlide.classList.remove("active");
    if(overlay) overlay.classList.remove("active");
    const username = document.getElementById("username");
    const password = document.getElementById("password");
    const formTitle = document.getElementById("formTitle");
    if(username) username.value = "";
    if(password) password.value = "";
    window.editTarget = null;
    if(formTitle) formTitle.innerText = "Tambah User";
};

window.simpanUser = function(){
    const usernameEl = document.getElementById("username");
    if(!usernameEl) return;
    let username = usernameEl.value;
    if(username === ""){
        alert("Username wajib diisi!");
        return;
    }

    if(window.editTarget){
        window.editTarget.querySelector("b").innerText = username;
        const avatar = window.editTarget.querySelector(".user-img");
        if(avatar) avatar.textContent = username.charAt(0).toUpperCase();
    }else{
        let card = `
        <div class="user-card">
            <div class="user-left">
                <div class="user-img">${username.charAt(0).toUpperCase()}</div>
                <div class="user-info">
                    <b>${username}</b>
                    <p style="font-size:12px;">USER BARU</p>
                </div>
            </div>
            <div class="user-action">
                <button class="edit-btn" onclick="window.editUser(this)">✏️ Edit</button>
                <button class="delete-btn" onclick="window.hapusUser(this)">🗑️ Hapus</button>
            </div>
        </div>
        `;
        document.getElementById("userList").insertAdjacentHTML("afterbegin", card);
    }

    window.closeForm();
};

window.editUser = function(btn){
    const card = btn.closest(".user-card");
    const namaEl = card.querySelector("b");
    if(!namaEl) return;
    let nama = namaEl.innerText;
    window.editTarget = card;
    const usernameEl = document.getElementById("username");
    const formTitleEl = document.getElementById("formTitle");
    if(usernameEl) usernameEl.value = nama;
    if(formTitleEl) formTitleEl.innerText = "Edit User";
    window.openForm();
    console.log('editUser called for', nama);
};

window.hapusUser = function(btn){
    if(confirm("Yakin hapus user ini?")){
        btn.closest(".user-card").remove();
    }
};

// Auto exec for injected context
console.log('User.php loaded, functions ready');
</script>

</body>
</html>

