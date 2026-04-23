<?php
session_start();
require_once 'config.php';

// ================= CSRF =================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: Index.php?error=' . urlencode('CSRF tidak valid'));
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username == '' || $password == '') {
        header('Location: Index.php?error=' . urlencode('Username & Password wajib diisi'));
        exit;
    }

    try {

        // 🔥 PASTIKAN DATABASE = penimbangan di config.php
        // contoh: mysql:host=localhost;dbname=penimbangan

        $stmt = $pdo->prepare("SELECT * FROM user WHERE `user` = :user LIMIT 1");
        $stmt->bindValue(':user', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $valid = false;

            // ===== PASSWORD BCRYPT =====
            if (password_verify($password, $row['password'])) {
                $valid = true;
            }

            // ===== PASSWORD MD5 (LAMA) =====
            elseif (strlen($row['password']) === 32 && md5($password) === $row['password']) {
                $valid = true;
            }

            // ===== PASSWORD PLAIN TEXT (JAGA-JAGA) =====
            elseif ($password === $row['password']) {
                $valid = true;
            }

            if ($valid) {

                // ===== SET SESSION =====
                $_SESSION['user_id']   = $row['id_user'];
$_SESSION['user_nama'] = $row['nama'];
$_SESSION['user_foto'] = $row['foto'] ?? null;

                // regen token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: Dasboard/Navbar.php?load=Input');
                exit;

            } else {
                header('Location: Index.php?error=' . urlencode('Password salah'));
                exit;
            }

        } else {
            header('Location: Index.php?error=' . urlencode('User tidak ditemukan'));
            exit;
        }

    } catch (PDOException $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        header('Location: Index.php?error=' . urlencode('Server error'));
        exit;
    }

} else {
    header('Location: Index.php');
    exit;
}