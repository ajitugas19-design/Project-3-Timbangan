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
                $asal = trim($_POST['lokasi_asal'] ?? '');
                $tujuan = trim($_POST['lokasi_tujuan'] ?? '');
                if (empty($nama) || empty($asal) || empty($tujuan)) throw new Exception('Semua field wajib diisi');
                $stmt = $pdo->prepare("INSERT INTO supplier (Nama_Supplier, Lokasi_Asal, Lokasi_Tujuan) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $asal, $tujuan]);
                echo json_encode(['success' => true, 'message' => '✅ Supplier baru berhasil ditambahkan!']);
                break;
            
            case 'edit':
                $id = (int)$_POST['id'];
                $nama = trim($_POST['nama'] ?? '');
                $asal = trim($_POST['lokasi_asal'] ?? '');
                $tujuan = trim($_POST['lokasi_tujuan'] ?? '');
                if (empty($nama) || empty($asal) || empty($tujuan) || empty($id)) throw new Exception('ID dan field wajib diisi');
                $stmt = $pdo->prepare("UPDATE supplier SET Nama_Supplier = ?, Lokasi_Asal = ?, Lokasi_Tujuan = ? WHERE id_Supplier = ?");
                $stmt->execute([$nama, $asal, $tujuan, $id]);
                echo json_encode(['success' => true, 'message' => '✅ Supplier berhasil diupdate!']);
                break;
            
            case 'delete':
                $id = (int)$_POST['id'];
                if (empty($id)) throw new Exception('ID wajib diisi');
                $stmt = $pdo->prepare("DELETE FROM supplier WHERE id_Supplier = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => '✅ Supplier berhasil dihapus!']);
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
