<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check table
$stmt = $pdo->query("SHOW TABLES LIKE 'material'");
if ($stmt->rowCount() == 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Table material not found.']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM material ORDER BY kode");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $data]);
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM material WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $data ?: null]);
            break;

        case 'add':
        case 'edit':
            $input = json_decode(file_get_contents('php://input'), true);
            $kode = trim($input['kode'] ?? '');
            $nama = trim($input['nama'] ?? '');

            if (empty($kode) || empty($nama)) {
                throw new Exception('Kode dan nama wajib');
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO material (kode, nama) VALUES (?, ?)");
                $stmt->execute([$kode, $nama]);
            } else {
                $id = $input['id'] ?? 0;
                if (!$id) throw new Exception('ID required');
                $stmt = $pdo->prepare("UPDATE material SET kode = ?, nama = ? WHERE id = ?");
                $stmt->execute([$kode, $nama, $id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("DELETE FROM material WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

