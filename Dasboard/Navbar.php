<?php
require_once '../config.php';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
$stmt = $pdo->prepare("SELECT id_user, nama, sebagai, `user`, password, foto, keterangan FROM user WHERE `user` = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

if ($user && hash_equals(md5($password), $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_username'] = $user['user'];
            session_write_close();
            header('Location: Navbar.php');
            exit;
        } else {
            $error = "Username atau password salah!";
            header('Location: ../Index.php?error=' . urlencode($error));
            exit;
        }
    }
}

// Cek login, jika belum redirect ke index
if (!isLoggedIn()) {
    header('Location: ../Index.php?error=' . urlencode($error ?? 'Silakan login'));
    exit;
}

// Logout confirm JS
if (isset($_GET['logout'])) {
    if(isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
        session_unset();
        session_destroy(); 
        session_write_close();
        setcookie(session_name(), '', 0, '/');
        header('Location: ../Index.php');
        exit;
    } else {
        header('Location: ../Index.php?error=Keluar dibatalkan');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard - Timbangan</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}

.content {
    padding: 20px;
}

/* USER STYLES - from original */
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
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div class="profile">
        <img src="Img/user.png" alt="Profile">
        <p>Login sebagai</p>
        <h4><?php echo htmlspecialchars($_SESSION['user_nama'] ?? 'Admin'); ?></h4>
    </div>

    <a href="#" onclick="loadDashboard('Input Informasi')">Input informasi</a>
    <a href="#" onclick="loadContent('sidebar/Customers.php')">Customers</a>
    <a onclick="loadContent('sidebar/Suppliers.php', 'Suppliers')">Suppliers</a>
<a onclick="loadContent('sidebar/Materials.php', 'Materials')">Materials</a>
    <a href="#" onclick="loadContent('sidebar/Informasi_Data.php', 'Informasi Data')">Informasi Data</a>
    <a href="#" onclick="loadContent('sidebar/Laporan.php')">Laporan</a>
    <a href="#" onclick="loadContent('sidebar/User.php', 'User')">User</a>
    <a href="#" onclick="confirmLogout()">Logout</a>
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
            <b id="pageTitle" style="margin-left:10px;">Input Data</b>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content" id="content">
        <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_nama'] ?? 'User'); ?>!</h2>
        <p>Dashboard Timbangan sudah terkoneksi database.</p>
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

function confirmLogout() {
    if(confirm('Apakah anda yakin ingin keluar?')) {
        window.location.href = '?logout=1&confirm=yes';
    }
}
</script>

</body>
</html>
