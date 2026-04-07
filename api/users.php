<?php
require_once '../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'list':
$stmt = $pdo->query("SELECT id_user, nama, sebagai, `user`, foto FROM user ORDER BY nama");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
            break;

        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Server-side validation - ALL required for tambah user baru
            $required = ['nama', 'sebagai', 'username', 'password', 'foto', 'keterangan'];
            foreach ($required as $field) {
                if (empty(trim($data[$field] ?? ''))) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Semua field wajib diisi untuk tambah user baru: ' . $field]);
                    return;
                }
            }
            
            // Unique check
            $check = $pdo->prepare('SELECT COUNT(*) FROM user WHERE nama = ? OR `user` = ?');
            $check->execute([$data['nama'], $data['username']]);
            if ($check->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Nama atau username sudah ada']);
                return;
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
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'edit':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Minimal validation for edit
            if (empty($data['nama']) || empty($data['username']) || empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nama, username, dan ID wajib untuk edit']);
                return;
            }
            
            // Optional password change only if provided
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
                $data['id']
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ? AND id_user != 1");  // Protect admin ID=1
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action tidak valid']);
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

