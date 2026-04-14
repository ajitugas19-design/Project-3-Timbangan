<?php
session_start();
require_once '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

header('Content-Type: application/json');

if ($_POST && isset($_POST['simpan'])) {
    try {
        $id_customer = isset($_POST['cek_customer']) ? $_POST['id_customers'] : null;
        $id_supplier = isset($_POST['cek_supplier']) ? $_POST['id_supplier'] : null;

        // AUTO NO RECORD
        $max = $pdo->query("SELECT MAX(id_transaksi) as max FROM transaksi")->fetch();
        $no_record = 'TRAN' . str_pad(($max['max'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO transaksi 
            (no_record,id_kendaraan,id_supplier,id_material,id_customers,bruto,tara,netto,tgl_masuk,jam_masuk,tgl_keluar,jam_keluar) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            $no_record, // auto-generated
            $_POST['id_kendaraan'],
            $id_supplier,
            $_POST['id_material'],
            $id_customer,
            $_POST['bruto'],
            $_POST['tara'],
            $_POST['netto'],
            $_POST['tgl_masuk'],
            $_POST['jam_masuk'],
            $_POST['tgl_keluar'],
            $_POST['jam_keluar']
        ]);

        echo json_encode([
            'success' => true, 
            'message' => '✅ Data berhasil disimpan!',
            'no_record' => $no_record
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
