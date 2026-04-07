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
    die("Koneksi gagal: " . $e->getMessage());
}

// Mulai session jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>

