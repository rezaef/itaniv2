<?php
// api/pump_status_latest.php
header('Content-Type: application/json');
require 'config.php';

try {
    $stmt = $pdo->query("
        SELECT action, source, log_time, notes
        FROM watering_logs
        WHERE notes LIKE 'Perintah%'
        ORDER BY log_time DESC
        LIMIT 1
    ");

    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['exists' => false]);
        exit;
    }

    echo json_encode([
        'exists'   => true,
        'action'   => $row['action'],   // 'ON' atau 'OFF'
        'source'   => $row['source'],   // 'manual' / 'otomatis'
        'log_time' => $row['log_time'],
        'notes'    => $row['notes'],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'exists' => false,
        'error'  => $e->getMessage()
    ]);
}
