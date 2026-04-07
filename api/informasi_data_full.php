<?php
require_once '../config.php';

if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];
$tgl = $_GET['tgl'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Existing paginated list with search
            $where = "WHERE 1=1";
            $params = [];
            if ($tgl) {
                $where .= " AND (DATE(wi.tanggal_in) = ? OR DATE(wo.tanggal_out) = ?)";
                $params = [$tgl, $tgl];
            }
            if ($search) {
                $where .= " AND (t.no_record LIKE ? OR k.Sopir LIKE ? OR k.Nopol LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
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

            $stmt = $pdo->prepare("SELECT 
                    t.*, k.Sopir as sopir, k.Nopol as nopol,
                    s.Nama_Supplier as supplier, m.Material as muatan, c.Customers as customer,
                    wi.tanggal_in, wi.jam_in, wo.tanggal_out, wo.jam_out
                FROM transaksi t 
                LEFT JOIN waktu_in wi ON t.id_in = wi.id_in 
                LEFT JOIN waktu_out wo ON t.id_out = wo.id_out 
                LEFT JOIN supplier s ON t.id_supplier = s.id_Supplier
                LEFT JOIN material m ON t.id_material = m.id_Material
                LEFT JOIN customers c ON t.id_customers = c.id_Customers
                LEFT JOIN kendaraan k ON t.id_kendaraan = k.id_Kendaraan
                $where 
                ORDER BY wi.tanggal_in DESC, wo.tanggal_out DESC, t.id_transaksi DESC 
                LIMIT ? OFFSET ?");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Stats
            $statsStmt = $pdo->prepare("SELECT 
                COUNT(*) as total,
                SUM(t.netto) as total_netto,
                SUM(CASE WHEN DATE(wi.tanggal_in) = CURDATE() THEN 1 ELSE 0 END) as today_count
                FROM transaksi t LEFT JOIN waktu_in wi ON t.id_in = wi.id_in $where");
            $statsStmt->execute(array_slice($params, 0, count($params)-2));
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'total' => $stats['total'],
                'totalNetto' => $stats['total_netto'],
                'todayCount' => $stats['today_count']
            ]);
            break;

        case 'get':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("SELECT t.*, wi.jam_in, wi.tanggal_in, wo.jam_out, wo.tanggal_out FROM transaksi t 
                LEFT JOIN waktu_in wi ON t.id_in = wi.id_in 
                LEFT JOIN waktu_out wo ON t.id_out = wo.id_out 
                WHERE t.id_transaksi = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data ?: null]);
            break;

        case 'add':
            $input = json_decode(file_get_contents('php://input'), true);
            $required = ['no_record', 'id_kendaraan', 'id_supplier', 'id_material', 'id_customers', 'id_in', 'id_out', 'bruto', 'tara', 'netto'];
            foreach ($required as $field) {
                if (empty($input[$field])) throw new Exception("Field required: $field");
            }
            $stmt = $pdo->prepare("INSERT INTO transaksi (no_record, id_kendaraan, id_supplier, id_material, id_customers, id_in, id_out, bruto, tara, netto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['no_record'],
                $input['id_kendaraan'],
                $input['id_supplier'],
                $input['id_material'],
                $input['id_customers'],
                $input['id_in'],
                $input['id_out'],
                $input['bruto'],
                $input['tara'],
                $input['netto']
            ]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'edit':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id_transaksi'])) throw new Exception('ID required');
            $stmt = $pdo->prepare("UPDATE transaksi SET no_record = ?, id_kendaraan = COALESCE(?, id_kendaraan), id_supplier = COALESCE(?, id_supplier), id_material = COALESCE(?, id_material), id_customers = COALESCE(?, id_customers), bruto = COALESCE(?, bruto), tara = COALESCE(?, tara), netto = COALESCE(?, netto) WHERE id_transaksi = ?");
            $stmt->execute([
                $input['no_record'] ?? null,
                $input['id_kendaraan'] ?? null,
                $input['id_supplier'] ?? null,
                $input['id_material'] ?? null,
                $input['id_customers'] ?? null,
                $input['bruto'] ?? null,
                $input['tara'] ?? null,
                $input['netto'] ?? null,
                $input['id_transaksi']
            ]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete':
            if (!$id) throw new Exception('ID required');
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action not valid']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

