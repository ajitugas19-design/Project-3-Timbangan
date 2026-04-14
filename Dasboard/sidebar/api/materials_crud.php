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
            case 'edit':
                $kode = trim($_POST['kode'] ?? '');
                $material = trim($_POST['nama'] ?? '');
                $id = (int)($_POST['id'] ?? 0);
                if (empty($kode) || empty($material)) throw new Exception('Kode dan nama material wajib diisi');
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO material (Kode, Material) VALUES (?, ?)");
                    $stmt->execute([$kode, $material]);
                    echo json_encode(['success' => true, 'message' => '✅ Material baru berhasil ditambahkan!']);
                } else {
                    if (!$id) throw new Exception('ID diperlukan untuk edit');
                    $stmt = $pdo->prepare("UPDATE material SET Kode = ?, Material = ? WHERE id_Material = ?");
                    $stmt->execute([$kode, $material, $id]);
                    echo json_encode(['success' => true, 'message' => '✅ Material berhasil diupdate!']);
                }
                break;
            
            case 'delete':
                $id = (int)$_POST['id'];
                if (!$id) throw new Exception('ID diperlukan');
                $stmt = $pdo->prepare("DELETE FROM material WHERE id_Material = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => '✅ Material berhasil dihapus!']);
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
