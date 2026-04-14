<?php
session_start();
require_once '../../../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_POST) {
    $action = $_POST['action'] ?? '';
    try {
        switch($action) {
            case 'add':
                $nama = trim($_POST['nama'] ?? '');
                $keterangan = trim($_POST['keterangan'] ?? '');
                if (empty($nama)) throw new Exception('Nama wajib diisi');
                $stmt = $pdo->prepare("INSERT INTO customers (Customers, Keterangan) VALUES (?, ?)");
                $stmt->execute([$nama, $keterangan]);
                echo json_encode(['success' => true, 'message' => '✅ Customer baru berhasil ditambahkan!']);
                break;
            
            case 'edit':
                $id = (int)$_POST['id'];
                $nama = trim($_POST['nama'] ?? '');
                $keterangan = trim($_POST['keterangan'] ?? '');
                if (empty($nama) || empty($id)) throw new Exception('ID dan nama wajib diisi');
                $stmt = $pdo->prepare("UPDATE customers SET Customers = ?, Keterangan = ? WHERE id_Customers = ?");
                $stmt->execute([$nama, $keterangan, $id]);
                echo json_encode(['success' => true, 'message' => '✅ Customer berhasil diupdate!']);
                break;
            
            case 'delete':
                $id = (int)$_POST['id'];
                if (empty($id)) throw new Exception('ID wajib diisi');
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id_Customers = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => '✅ Customer berhasil dihapus!']);
                break;
                
            default:
                throw new Exception('Action tidak valid');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
