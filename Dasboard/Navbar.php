<?php 
ob_start();
session_start();
require_once '../config.php';

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Index.php?error=' . urlencode("Silakan login"));
    exit;
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../Index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Timbangan</title>
<link rel="stylesheet" href="css/dashboard.css">
<script src="js/dashboard.js" defer></script>
<script>window.BASE_PATH = '<?= dirname($_SERVER['SCRIPT_NAME']) ?>';</script>
</head>
<body>
<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="profile">
<img src="/Project_3/uploads/<?= basename($_SESSION['user_foto'] ?? 'default.png') ?>" class="avatar" 
         alt="<?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?>" onerror="this.src='/Project_3/uploads/default.png'">

    <small style="color:#ccc;">
        Login sebagai 
        <b><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></b>
    </small>
</div>

    <a class="nav-item" onclick="loadContent('sidebar/Input.php','Input', this)">Input Data</a>
<a class="nav-item" onclick="loadContent('sidebar/Customers.php','Customers', this)">Customers</a>
<a class="nav-item" onclick="loadContent('sidebar/Suppliers.php','Suppliers', this)">Suppliers</a>
<a class="nav-item" onclick="loadContent('sidebar/Materials.php','Materials', this)">Materials</a>
<a class="nav-item" onclick="loadContent('sidebar/Kendaraan.php','Kendaraan', this)">Kendaraan</a>
<a class="nav-item" onclick="loadContent('sidebar/Informasi_Data.php','Data', this)">Informasi Data</a>
<a class="nav-item" onclick="loadContent('sidebar/Laporan.php','Laporan', this)">Laporan</a>
<a class="nav-item" onclick="loadContent('sidebar/User.php','User', this)">User</a>
<a class="nav-item" onclick="confirmLogout()" style="color: #ff4444; border-top: 1px solid #ccc; padding-top: 10px;">Logout</a>
</nav>

<!-- MAIN -->
<main class="main" id="main">
    <header class="header flex">
        <button class="hamburger-btn" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h3 id="pageTitle">Dashboard Timbangan</h3>
    </header>
    <div class="content" id="content">
<?php if (isset($_GET['load'])) {
    $page = $_GET['load'];

    $allowed = ['Input', 'Home', 'Transaksi']; // whitelist

    if (in_array($page, $allowed)) {
        include __DIR__ . '/' . $page . '.php';
    } else {
        echo "Halaman tidak ditemukan";
    }
} ?>
</div>
</main>
</body>
</html>

