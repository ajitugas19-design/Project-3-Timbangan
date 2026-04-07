<?php
require_once '../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'list':
            $stmt = $pdo->query("SELECT *, '' as Jenis_Kendaraan FROM kendaraan ORDER BY Nopol");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
        
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $nopol = $_GET['nopol'] ?? '';
            
            if ($id) {
                $stmt = $pdo->prepare("SELECT *, '' as Jenis_Kendaraan FROM kendaraan WHERE id_Kendaraan = ?");
                $stmt->execute([$id]);
            } elseif ($nopol) {
                $stmt = $pdo->prepare("SELECT *, '' as Jenis_Kendaraan FROM kendaraan WHERE Nopol = ?");
                $stmt->execute([$nopol]);
            } else {
                throw new Exception('ID atau Nopol diperlukan');
            }
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data ?: null]);
            break;
        
        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $nopol = trim($input['Nopol'] ?? '');
            $sopir = trim($input['Sopir'] ?? '');
            
            if (empty($nopol) || empty($sopir)) {
                throw new Exception('Nopol dan Sopir wajib diisi');
            }
            
            $stmt = $pdo->prepare("INSERT INTO kendaraan (Nopol, Sopir) VALUES (?, ?)");
            $stmt->execute([$nopol, $sopir]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;
        
        case 'edit':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $nopol = trim($input['Nopol'] ?? '');
            $sopir = trim($input['Sopir'] ?? '');
            
            if (empty($id) || empty($nopol) || empty($sopir)) {
                throw new Exception('ID, Nopol, Sopir wajib diisi');
            }
            
            $stmt = $pdo->prepare("UPDATE kendaraan SET Nopol = ?, Sopir = ? WHERE id_Kendaraan = ?");
            $stmt->execute([$nopol, $sopir, $id]);
            echo json_encode(['status' => 'success']);
            break;
        
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if (empty($id)) {
                throw new Exception('ID wajib diisi');
            }
            
            $stmt = $pdo->prepare("DELETE FROM kendaraan WHERE id_Kendaraan = ?");
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

