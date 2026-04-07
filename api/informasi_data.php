<?php
require_once '../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$tgl = $_GET['tgl'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$id = $_GET['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $where = "WHERE 1=1";
            $params = [];
            if ($tgl) {
                $where .= " AND (DATE(wi.tanggal_in) = ? OR DATE(wo.tanggal_out) = ?)";
                $params = [$tgl, $tgl];
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi t 
                LEFT JOIN waktu_in wi ON t.id_in = wi.id_in 
                LEFT JOIN waktu_out wo ON t.id_out = wo.id_out 
                LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
                LEFT JOIN material m ON t.id_material = m.id_Material
                LEFT JOIN customers c ON t.id_customers = c.id_Customers
                LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
                $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $limit_int = (int)$limit;
            $offset_int = (int)$offset;
            $stmt = $pdo->prepare("SELECT 
                    t.id_transaksi as id_data, t.no_record,
                    k.Sopir as sopir, k.Nopol as nopol,
                    s.Nama_Supplier as supplier, m.Material as muatan, c.Customers as customer,
                    wi.tanggal_in, wi.jam_in, 
                    wo.tanggal_out, wo.jam_out,
                    t.bruto, t.tara, t.netto, t.no_record as keterangan
                FROM transaksi t 
                LEFT JOIN waktu_in wi ON t.id_in = wi.id_in 
                LEFT JOIN waktu_out wo ON t.id_out = wo.id_out 
                LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
                LEFT JOIN material m ON t.id_material = m.id_Material
                LEFT JOIN customers c ON t.id_customers = c.id_Customers
                LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
                $where 
                ORDER BY wi.tanggal_in DESC, wo.tanggal_out DESC, t.id_transaksi DESC 
                LIMIT $limit_int OFFSET $offset_int");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]);
            break;

        case 'DELETE':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

