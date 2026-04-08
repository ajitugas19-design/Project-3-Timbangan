<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // List users
        $stmt = $pdo->query("SELECT id_user, nama, sebagai, `user`, foto, keterangan FROM user ORDER BY nama");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $users]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'add':
                $nama = trim($input['nama'] ?? '');
                $sebagai = trim($input['sebagai'] ?? '');
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                $foto = trim($input['foto'] ?? '');
                $keterangan = trim($input['keterangan'] ?? '');

                if (empty($nama) || empty($sebagai) || empty($username) || empty($password) || empty($foto) || empty($keterangan)) {
                    throw new Exception('Semua field wajib diisi');
                }
                if (strlen($password) < 6) {
                    throw new Exception('Password minimal 6 karakter');
                }
                $check = $pdo->prepare('SELECT COUNT(*) FROM user WHERE nama = ? OR `user` = ?');
                $check->execute([$nama, $username]);
                if ($check->fetchColumn() > 0) {
                    throw new Exception('Nama atau username sudah ada');
                }
                $stmt = $pdo->prepare("INSERT INTO user (nama, sebagai, `user`, password, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nama, $sebagai, $username, password_hash($password, PASSWORD_DEFAULT), $foto, $keterangan]);
                echo json_encode(['success' => true, 'message' => '✅ User baru berhasil ditambahkan!']);
                break;

            case 'edit':
                $id = (int)($input['id'] ?? 0);
                $nama = trim($input['nama'] ?? '');
                $sebagai = trim($input['sebagai'] ?? '');
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                $foto = trim($input['foto'] ?? '');
                $keterangan = trim($input['keterangan'] ?? '');

                if (!$id || empty($nama) || empty($username)) {
                    throw new Exception('ID, nama, username wajib');
                }
                $query = "UPDATE user SET nama = ?, sebagai = COALESCE(?, sebagai), `user` = ?, foto = COALESCE(?, foto), keterangan = COALESCE(?, keterangan) WHERE id_user = ?";
                $params = [$nama, $sebagai ?: null, $username, $foto ?: null, $keterangan ?: null, $id];
                if (!empty($password)) {
                    $query = "UPDATE user SET nama = ?, sebagai = COALESCE(?, sebagai), `user` = ?, password = ?, foto = COALESCE(?, foto), keterangan = COALESCE(?, keterangan) WHERE id_user = ?";
                    array_splice($params, 3, 0, password_hash($password, PASSWORD_DEFAULT));
                    array_unshift(array_slice($params, 6), $id); // Fix param count
                }
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => '✅ User berhasil diupdate!']);
                break;

            case 'delete':
                $id = (int)($input['id'] ?? 0);
                if (!$id || $id == 1) {
                    throw new Exception('Admin ID=1 dilindungi');
                }
                $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => '✅ User berhasil dihapus!']);
                break;

            default:
                throw new Exception('Action tidak valid');
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
}
?>

