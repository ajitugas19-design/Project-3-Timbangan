<?php
require_once '../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'list':
            $stmt = $pdo->query("SELECT id_user, nama, sebagai, username, foto FROM user ORDER BY nama");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
            break;

        case 'add':
            $data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("INSERT INTO user (nama, sebagai, username, password, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['nama'], 
        $data['sebagai'] ?? 'User', 
        $data['username'], 
        password_hash($data['password'] ?? '', PASSWORD_DEFAULT),
        $data['foto'] ?? 'default.jpg',
        $data['keterangan'] ?? 'Aktif'
    ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'edit':
            $data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("UPDATE user SET nama = ?, sebagai = ?, username = ?, password = ?, foto = ?, keterangan = ? WHERE id_user = ?");
    $stmt->execute([
        $data['nama'],
        $data['sebagai'] ?? 'User',
        $data['username'],
        password_hash($data['password'] ?? '', PASSWORD_DEFAULT),
        $data['foto'] ?? 'default.jpg',
        $data['keterangan'] ?? 'Aktif',
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

