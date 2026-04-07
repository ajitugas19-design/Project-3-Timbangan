<?php
header('Content-Type: application/json');
require_once '../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Check table
$stmt = $pdo->query("SHOW TABLES LIKE 'material'");
if ($stmt->rowCount() == 0) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Table material not found']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM material ORDER BY kode");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM material WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data ?: null]);
            break;

        case 'add':
        case 'edit':
            $input = json_decode(file_get_contents('php://input'), true);
            $kode = trim($input['kode'] ?? '');
            $nama = trim($input['nama'] ?? '');
            $id = (int)($input['id'] ?? 0);

            if (empty($kode) || empty($nama)) {
                throw new Exception('Kode dan nama wajib diisi');
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO material (kode, nama) VALUES (?, ?)");
                $stmt->execute([$kode, $nama]);
                echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            } else {
                if (!$id) throw new Exception('ID diperlukan untuk edit');
                $stmt = $pdo->prepare("UPDATE material SET kode = ?, nama = ? WHERE id = ?");
                $stmt->execute([$kode, $nama, $id]);
                echo json_encode(['status' => 'success']);
            }
            break;

        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new Exception('ID diperlukan');
            $stmt = $pdo->prepare("DELETE FROM material WHERE id = ?");
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

