<?php
header('Content-Type: application/json');
require_once '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'add':
    case 'edit':
        try {
            $id = $data['id_transaksi'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, bruto, tara, netto) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bruto=VALUES(bruto), tara=VALUES(tara), netto=VALUES(netto)");
            $stmt->execute([
                $data['no_record'],
                $data['id_kendaraan'],
                $data['id_supplier'],
                $data['id_material'],
                $data['id_customers'],
                $data['bruto'],
                $data['tara'],
                $data['netto']
            ]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $id = $_GET['id'] ?? $data['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
        break;

    case 'list_recent':
        $stmt = $pdo->query("SELECT t.*, k.Nopol as nopol, k.Sopir as sopir, s.Nama_Supplier as supplier, m.Material as muatan, c.Customers as customer FROM transaksi t LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier LEFT JOIN material m ON t.id_material = m.id_Material LEFT JOIN customers c ON t.id_customers = c.id_Customers ORDER BY t.id_transaksi DESC LIMIT 10");
        echo json_encode(['data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
        $stmt->execute([$id]);
        echo json_encode(['data' => $stmt->fetch()]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>

