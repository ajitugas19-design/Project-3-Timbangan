<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>Login Fix Setup</title>
<style>
body {font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5;}
.box {background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);}
.btn { background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;}
.btn:hover {background: #45a049;}
.error { color: #d32f2f; background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;}
.success { color: #2e7d32; background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;}
table {width: 100%; border-collapse: collapse; margin: 15px 0;}
th, td {border: 1px solid #ddd; padding: 8px; text-align: left;}
th {background: #f2f2f2;}
</style>
</head>
<body>
<div class="box">
<h1>🔧 Login Fix - Setup Wizard</h1>
<p>Diagnose & fix login issue automatically!</p>

<?php
$db_ok = true;
$users = [];
try {
    $stmt = $pdo->query("SELECT id_user, nama, `user`, sebagai, keterangan, password FROM user ORDER BY id_user");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_ok = false;
    echo '<div class="error">DB Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p><strong>Solution:</strong> Run XAMPP MySQL, create DB `penimbangan`, table `user`. See api/users.php for structure.</p>';
}

if ($db_ok) {
    echo '<h3>📊 Current Users (' . count($users) . '):</h3>';
    if (empty($users)) {
        echo '<div class="error">No users found! Cannot login.</div>';
        echo '<form method="post"><button name="create_admin" class="btn">➕ Create Default Admin</button></form>';
        if (isset($_POST['create_admin'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO user (nama, sebagai, `user`, password, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    'Administrator',
                    'Super Admin',
                    'admin',
                    md5('password'),
                    'admin.jpg',
                    'Default admin - password: password'
                ]);
                echo '<div class="success">✅ Admin created! Login: <strong>username: admin, password: password</strong></div>';
                echo '<meta http-equiv="refresh" content="2;url=Index.php">';
            } catch (PDOException $e) {
                echo '<div class="error">Insert failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>Nama</th><th>Username</th><th>Role</th><th>Password Hash</th></tr>';
        foreach ($users as $u) {
            echo '<tr><td>' . $u['id_user'] . '</td>';
            echo '<td>' . htmlspecialchars($u['nama']) . '</td>';
            echo '<td><strong>' . htmlspecialchars($u['user']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($u['sebagai']) . '</td>';
            echo '<td style="font-family:monospace;font-size:12px;">' . htmlspecialchars(substr($u['password'],0,16)) . '...</td></tr>';
        }
        echo '</table>';
        echo '<div class="success">✅ Users OK! <a href="Index.php"><button class="btn">Test Login</button></a></div>';

        // PASSWORD RESET FORM (MD5)
        echo '<hr><h3>🔑 Reset Password to "password"</h3>';
        echo '<form method="post">';
        echo 'Username: <input name="reset_user" value="aji" required style="width:200px;"> ';
        echo '<button name="reset_pass" class="btn">Reset (MD5)</button>';
        echo '</form>';
        if (isset($_POST['reset_pass']) && !empty($_POST['reset_user'])) {
            try {
                $reset_user = $_POST['reset_user'];
$new_hash = md5('12345');
                $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE `user` = ?");
                $rows = $stmt->execute([$new_hash, $reset_user]);
echo '<div class="success">✅ Password RESET for <strong>' . htmlspecialchars($reset_user) . '</strong>! Rows: ' . $rows . '<br>Login: <strong>username=' . htmlspecialchars($reset_user) . ', password=12345</strong></div>';
                echo '<meta http-equiv="refresh" content="1">';
            } catch (PDOException $e) {
                echo '<div class="error">Reset failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

echo '<hr>';
echo '<h3>Status:</h3>';
echo '<ul>';
echo '<li><strong>XAMPP MySQL:</strong> Port 3306 OK</li>';
echo '<li><strong>Login Type:</strong> MD5 (Navbar.php fixed)</li>';
echo '<li><strong>Test:</strong> <a href="Index.php">Index.php</a> | <a href="Dasboard/Navbar.php">Dashboard</a></li>';
echo '<li><strong>Cleanup:</strong> Delete setup-login.php after success</li>';
echo '</ul>';

?>
</div>
</body>
</html>

