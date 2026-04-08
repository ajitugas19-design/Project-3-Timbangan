<?php
// config.php - Koneksi Database Timbangan (XAMPP default)
$host = 'localhost';

$dbname = 'penimbangan';

$username = 'root';  // XAMPP default
$password = '';      // XAMPP default (kosong)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("DB Error [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage() . " in " . __FILE__);
    die("Koneksi gagal: " . $e->getMessage() . " (check logs)");
}

function logDebug($msg) {
    error_log("Debug [" . date('Y-m-d H:i:s') . "]: " . $msg);
}

function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        logDebug("Session check failed - no user_id");
        return false;
    }
    logDebug("Session OK - user_id: " . $_SESSION['user_id']);
    return true;
}

// Mulai session jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

