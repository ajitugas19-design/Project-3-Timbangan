<?php
session_start();
require_once 'config.php';

// Generate CSRF if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: Index.php?error=' . urlencode('Invalid CSRF token'));
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header('Location: Index.php?error=' . urlencode('Username dan password wajib diisi'));
        exit;
    }

    if (strlen($password) < 3) {
        header('Location: Index.php?error=' . urlencode('Password terlalu pendek'));
        exit;
    }

    try {
        // Verify with MD5 (match existing users)
        $stmt = $pdo->prepare('SELECT id_user, nama FROM user WHERE `user` = ? AND password = MD5(?)');
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Set session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_nama'] = $user['nama'];
            // Renew CSRF
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
header('Location: Dasboard/Navbar.php?load=Input');
            exit;
        } else {
            // Log failed attempt (security)
            error_log("Failed login for username: $username at " . date('Y-m-d H:i:s'));
            header('Location: Index.php?error=' . urlencode('Username atau password salah'));
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login DB error: " . $e->getMessage());
        header('Location: Index.php?error=' . urlencode('Server error, coba lagi'));
        exit;
    }
} else {
    // GET redirect to login page
    header('Location: Index.php');
    exit;
}
?>

