<?php
require_once '../config.php';

// Auth check
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$tgl = $_GET['tgl'] ?? '';
$id_data = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            // GET all or filter by tanggal_in
            $where = "WHERE 1=1";
            $params = [];
            if ($tgl) {
                $where .= " AND (ti.tanggal_in = ? OR to.tanggal_out = ?)";
                $params[] = $tgl;
                $params[] = $tgl;
            }
$stmt = $pdo->prepare("
                SELECT 
                    d.id_data, d.isi_muatan as muatan, d.bruto, d.tara, d.netto, d.keterangan,
                    k.sopir, k.jenis_kendaraan as kendaraan, k.nopol,
                    ti.tanggal_in, ti.jam_in, 
                    to.tanggal_out, to.jam_out
                FROM data d
                LEFT JOIN kendaraan k ON d.kendaraan_id = k.id_kendaraan
                LEFT JOIN transaksi_in ti ON d.id_data = ti.id_data
                LEFT JOIN transaksi_out to ON d.id_data = to.id_data
                $where
                ORDER BY ti.tanggal_in DESC, to.tanggal_out DESC, d.id_data DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Informasi data query: tgl=' . ($tgl ?? 'none') . ', rows: ' . count($data));
            echo json_encode($data ?: []);
            break;
            echo json_encode($data ?: []);
            break;

        case 'POST':
            // Create new
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("
                INSERT INTO data (isi_muatan, bruto, tara, netto, keterangan) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['muatan'] ?? '',
                $input['bruto'] ?? 0,
                $input['tara'] ?? 0,
                $input['netto'] ?? 0,
                $input['keterangan'] ?? ''
            ]);
            echo json_encode(['id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            // Edit by id_data
            if (!$id_data) throw new Exception('ID required');
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("
                UPDATE data SET 
                isi_muatan = ?, bruto = ?, tara = ?, netto = ?, keterangan = ? 
                WHERE id_data = ?
            ");
            $stmt->execute([
                $input['muatan'] ?? '',
                $input['bruto'] ?? 0,
                $input['tara'] ?? 0,
                $input['netto'] ?? 0,
                $input['keterangan'] ?? '',
                $id_data
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // Delete by id_data
            if (!$id_data) throw new Exception('ID required');
            $stmt = $pdo->prepare("DELETE FROM data WHERE id_data = ?");
            $stmt->execute([$id_data]);
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

