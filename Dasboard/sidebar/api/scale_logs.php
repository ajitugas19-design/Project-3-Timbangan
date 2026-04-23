<?php
session_start();
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'latest_weight') {
    // Latest successful weight
    $stmt = $pdo->query("SELECT parsed_weight, timestamp, raw_data FROM scale_logs WHERE status='success' ORDER BY id DESC LIMIT 1");
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($latest ?: ['parsed_weight' => null]);
    exit;
}

if ($action === 'logs') {
    // Last 20 logs
    $limit = (int)($_GET['limit'] ?? 20);
    $stmt = $pdo->query("
        SELECT * FROM scale_logs 
        ORDER BY id DESC LIMIT $limit
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($logs);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
