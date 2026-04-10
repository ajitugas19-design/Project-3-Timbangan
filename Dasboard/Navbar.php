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
        <p>Login sebagai</p>
        <h4><?= htmlspecialchars($_SESSION['user_nama']) ?></h4>
    </div>
    <a class="nav-item" onclick="loadContent('sidebar/Input.php','Input Transaksi')">📥 Input</a>
    <a class="nav-item" onclick="loadContent('sidebar/Customers.php','Customers')">👥 Customers</a>
    <a class="nav-item" onclick="loadContent('sidebar/Suppliers.php','Suppliers')">🏭 Suppliers</a>
    <a class="nav-item" onclick="loadContent('sidebar/Materials.php','Materials')">📦 Materials</a>
    <a class="nav-item" onclick="loadContent('sidebar/Informasi_Data.php','Informasi Data')">📊 Informasi Data</a>
    <a class="nav-item" onclick="loadContent('sidebar/Laporan.php','Laporan')">📑 Laporan</a>
    <a class="nav-item" onclick="loadContent('sidebar/User.php','User')">👤 User</a>
    <a class="nav-item" onclick="confirmLogout()">🚪 Logout</a>
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
        <div class="loading flex flex-col">
            <div class="spinner"></div>
            <p>Memuat...</p>
        </div>
    </div>
</main>
</body>
</html>

