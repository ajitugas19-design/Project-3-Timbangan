<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'list':
            $stmt = $pdo->query("SELECT id_user, nama, sebagai, `user`, foto, keterangan FROM user ORDER BY nama");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['nama', 'sebagai', 'username', 'password', 'foto', 'keterangan'];
            foreach ($required as $field) {
                if (empty(trim($data[$field] ?? ''))) {
                    throw new Exception("Field wajib: $field");
                }
            }
            
            $check = $pdo->prepare('SELECT COUNT(*) FROM user WHERE nama = ? OR `user` = ?');
            $check->execute([$data['nama'], $data['username']]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Nama atau username sudah ada');
            }
            
            $stmt = $pdo->prepare("INSERT INTO user (nama, sebagai, `user`, password, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($data['nama']),
                trim($data['sebagai']),
                trim($data['username']),
                password_hash($data['password'], PASSWORD_DEFAULT),
                trim($data['foto']),
                trim($data['keterangan'])
            ]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'edit':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['nama']) || empty($data['username']) || empty($data['id'])) {
                throw new Exception('Nama, username, ID wajib');
            }
            
            $passwordHash = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
            
            $stmt = $pdo->prepare("UPDATE user SET nama = ?, sebagai = COALESCE(?, sebagai), `user` = ?, 
                password = COALESCE(?, password), foto = COALESCE(?, foto), keterangan = COALESCE(?, keterangan) 
                WHERE id_user = ?");
            $stmt->execute([
                trim($data['nama']),
                trim($data['sebagai'] ?? null),
                trim($data['username']),
                $passwordHash,
                trim($data['foto'] ?? null),
                trim($data['keterangan'] ?? null),
                (int)$data['id']
            ]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if (!$id || $id == 1) {
                throw new Exception('Admin ID=1 dilindungi');
            }
            $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

