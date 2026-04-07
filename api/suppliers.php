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
            $stmt = $pdo->query("SELECT s.*, COUNT(t.id_transaksi) as total_transaksi FROM supplier s LEFT JOIN transaksi t ON s.id_Supplier = t.id_supplier GROUP BY s.id_Supplier ORDER BY s.id_Supplier DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
        
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM supplier WHERE id_Supplier = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data ?: null]);
            break;
        
        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $nama = trim($input['nama'] ?? '');
            $asal = trim($input['lokasi_asal'] ?? '');
            $tujuan = trim($input['lokasi_tujuan'] ?? '');
            
            if (empty($nama) || empty($asal) || empty($tujuan)) {
                throw new Exception('Semua field wajib diisi');
            }
            
            $stmt = $pdo->prepare("INSERT INTO supplier (Nama_Supplier, Lokasi_Asal, Lokasi_Tujuan) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $asal, $tujuan]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;
        
        case 'edit':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $nama = trim($input['nama'] ?? '');
            $asal = trim($input['lokasi_asal'] ?? '');
            $tujuan = trim($input['lokasi_tujuan'] ?? '');
            
            if (empty($nama) || empty($asal) || empty($tujuan) || empty($id)) {
                throw new Exception('ID dan field wajib diisi');
            }
            
            $stmt = $pdo->prepare("UPDATE supplier SET Nama_Supplier = ?, Lokasi_Asal = ?, Lokasi_Tujuan = ? WHERE id_Supplier = ?");
            $stmt->execute([$nama, $asal, $tujuan, $id]);
            echo json_encode(['status' => 'success']);
            break;
        
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            
            if (empty($id)) {
                throw new Exception('ID wajib diisi');
            }
            
            $stmt = $pdo->prepare("DELETE FROM supplier WHERE id_Supplier = ?");
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

