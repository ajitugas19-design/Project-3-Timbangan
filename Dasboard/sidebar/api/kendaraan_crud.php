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
                $nopol = trim($_POST['nopol'] ?? '');
                $sopir = trim($_POST['sopir'] ?? '');
                if (empty($nopol) || empty($sopir)) throw new Exception('Nopol dan Sopir wajib diisi');
                $stmt = $pdo->prepare("INSERT INTO kendaraan (Nopol, Sopir) VALUES (?, ?)");
                $stmt->execute([$nopol, $sopir]);
                echo json_encode(['success' => true, 'message' => '✅ Kendaraan baru berhasil ditambahkan!']);
                break;
            
            case 'edit':
                $id = (int)$_POST['id'];
                $nopol = trim($_POST['nopol'] ?? '');
                $sopir = trim($_POST['sopir'] ?? '');
                if (empty($nopol) || empty($sopir) || empty($id)) throw new Exception('ID, Nopol dan Sopir wajib diisi');
                $stmt = $pdo->prepare("UPDATE kendaraan SET Nopol = ?, Sopir = ? WHERE id_Kendaraan = ?");
                $stmt->execute([$nopol, $sopir, $id]);
                echo json_encode(['success' => true, 'message' => '✅ Kendaraan berhasil diupdate!']);
                break;
            
            case 'delete':
                $id = (int)$_POST['id'];
                if (empty($id)) throw new Exception('ID wajib diisi');
                $stmt = $pdo->prepare("DELETE FROM kendaraan WHERE id_Kendaraan = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => '✅ Kendaraan berhasil dihapus!']);
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
