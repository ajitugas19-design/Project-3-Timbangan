<?php
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
</head>
<body>
<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="profile">
        <img src="../Img/Logo.png" class="avatar" alt="<?= htmlspecialchars($_SESSION['user_nama']) ?>">
        <h4><?= htmlspecialchars($_SESSION['user_nama']) ?></h4>
    </div>

    <a class="nav-item" onclick="loadContent('sidebar/Input.php','Input')">📥 <span>Input Data</span></a>
    <a class="nav-item" onclick="loadContent('sidebar/Customers.php','Customers')">👥 <span>Customers</span></a>
    <a class="nav-item" onclick="loadContent('sidebar/Suppliers.php','Suppliers')">🏭 <span>Suppliers</span></a>
    <a class="nav-item" onclick="loadContent('sidebar/Materials.php','Materials')">📦 <span>Materials</span></a>
    <a class="nav-item" onclick="loadContent('sidebar/Informasi_Data.php','Data')">📊 <span>Informasi Data</span></a>
    <a class="nav-item" onclick="loadContent('sidebar/Laporan.php','Laporan')">📑 <span>Laporan</span></a>
    <a class="nav-item" onclick="loadContent('sidebar/User.php','User')">👤 <span>User</span></a>
    <a class="nav-item" onclick="confirmLogout()">🚪 <span>Log Out</span></a>
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
<?php if (isset($_GET['load']) && $_GET['load'] === 'Input'): ?>
<script>window.addEventListener('load', () => loadContent('sidebar/Input.php', 'Input'));</script>
<?php endif; ?>
</div>
</main>
</body>
</html>

