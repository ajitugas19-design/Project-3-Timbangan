<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Navbar</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}

/* USER STYLES */
.user-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:20px 30px;
}
.btn {
    color:white;
    padding:10px;
    border:none;
    border-radius:4px;
    cursor:pointer;
    width:100%;
}
.btn-save{ background:#3498db; }
.btn-cancel{ background:#e74c3c; }
#userList {
    margin-top:10px;
}
.user-card {
    background:#efefef;
    margin:15px 30px;
    padding:15px;
    border-radius:6px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border:1px solid #ddd;
}
.user-left {
    display:flex;
    align-items:center;
    gap:15px;
}
.user-img {
    width:65px;
    height:65px;
    background:#ddd;
    border-radius:4px;
}
.user-action button {
    margin-left:5px;
    padding:6px 10px;
    cursor:pointer;
}
.form-slide {
    position:fixed;
    top:0;
    right:-350px;
    width:300px;
    height:100vh;
    background:white;
    padding:20px;
    transition:0.3s;
    box-shadow:-5px 0 15px rgba(0,0,0,0.2);
    z-index: 2000;
}
.form-slide.active {
    right:0;
}
.form-slide input {
    width:100%;
    padding:10px;
    margin:10px 0;
    box-sizing: border-box;
}
.overlay {
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.4);
    opacity:0;
    visibility:hidden;
    transition:0.3s;
    z-index: 1500;
}
.overlay.active {
    opacity:1;
    visibility:visible;
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: #2f3e46;
    color: white;
    padding-top: 20px;
    z-index: 1000;
    transform: translateX(-100%);
    transition: 0.3s;
}

.sidebar.active {
    transform: translateX(0);
}

/* PROFILE */
.profile {
    text-align: center;
    margin-bottom: 20px;
}

.profile img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    margin-bottom: 10px;
}

.profile p {
    font-size: 12px;
    color: #ccc;
    margin: 0;
}

.profile h4 {
    margin: 5px 0;
}

/* MENU */
.sidebar a {
    display: block;
    padding: 12px 20px;
    color: white;
    text-decoration: none;
}

.sidebar a:hover {
    background: #3a5a40;
}

/* ===== HEADER ===== */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #d3d3d3;
    padding: 15px 20px;
}

/* LEFT HEADER */
.header-left {
    display: flex;
    align-items: center;
}

/* Hamburger */
.hamburger {
    cursor: pointer;
}

.hamburger div {
    width: 25px;
    height: 3px;
    background: black;
    margin: 5px;
}

/* ===== MAIN ===== */
.main {
    transition: margin-left 0.3s;
}

.main.shift {
    margin-left: 250px;
}

/* Menu header */
.menu a {
    margin: 0 10px;
    text-decoration: none;
    color: black;
}

/* Content */
.content {
    padding: 20px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

    <div class="profile">
        <img src="Img/user.png" alt="Profile">
        <p>Login sebagai</p>
        <h4>Admin</h4>
    </div>

    <a href="#" onclick="loadDashboard()">Dashboard</a>
    <a href="#" onclick="loadContent('sidebar/User.php')">User</a>
    <a href="#">Data Buku</a>
    <a href="#">Laporan</a>
    <a href="index.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main" id="main">

    <!-- HEADER -->
    <div class="header">
        <div class="header-left">
            <div class="hamburger" onclick="toggleSidebar()">
                <div></div>
                <div></div>
                <div></div>
            </div>
            <b style="margin-left:10px;">Dashboard</b>
        </div>

        <div class="menu">
            <a href="#">Home</a>
            <a href="#">Profile</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content" id="content">
        <h2>Selamat Datang</h2>
        <p>Ini halaman dashboard setelah login.</p>
    </div>

</div>

<!-- SCRIPT -->
<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("main").classList.toggle("shift");
}

/* LOAD DASHBOARD DEFAULT */
function loadDashboard(){
    document.getElementById("content").innerHTML = `
        <h2>Selamat Datang</h2>
        <p>Ini halaman dashboard setelah login.</p>
    `;
}

/* LOAD HALAMAN USER */
async function loadContent(url) {
    const content = document.getElementById('content');
    content.innerHTML = '<p>Loading...</p>';

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error("File tidak ditemukan");

        const html = await response.text();

        // inject isi
        content.innerHTML = html;

        // jalankan script
        const scripts = content.querySelectorAll("script");
        scripts.forEach(script => {
            eval(script.innerText);
        });

    } catch (e) {
        content.innerHTML = '<p style="color:red;">Error: ' + e.message + '</p>';
    }
}

/* FORM FUNCTIONS (for User.php) */
function openForm(){
    const el = document.getElementById("formSlide");
    if(el) el.classList.add("active");
    const overlay = document.getElementById("overlay");
    if(overlay) overlay.classList.add("active");
}

function closeForm(){
    const el = document.getElementById("formSlide");
    if(el) el.classList.remove("active");
    const overlay = document.getElementById("overlay");
    if(overlay) overlay.classList.remove("active");
}
</script>

</body>
</html>
