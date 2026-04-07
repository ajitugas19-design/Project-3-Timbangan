<?php
require_once '../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch($action) {
    case 'list':
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;
    
    case 'get':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => $data ? 'success' : 'error', 'data' => $data ?: null]);
        break;
    
    case 'add':
        $input = json_decode(file_get_contents('php://input'), true);
        $nama = trim($input['nama'] ?? '');
        $keterangan = trim($input['keterangan'] ?? '');
        if (empty($nama)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nama wajib']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO customers (nama, keterangan) VALUES (?, ?)");
        $stmt->execute([$nama, $keterangan]);
        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
        break;
    
    case 'edit':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $nama = trim($input['nama'] ?? '');
        $keterangan = trim($input['keterangan'] ?? '');
        if (empty($nama) || empty($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID dan nama wajib']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE customers SET nama = ?, keterangan = ? WHERE id = ?");
        $stmt->execute([$nama, $keterangan, $id]);
        echo json_encode(['status' => 'success']);
        break;
    
    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Action invalid']);
}
?>

