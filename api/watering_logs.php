<?php
// api/watering_logs.php
header('Content-Type: application/json');

require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET: ambil log terbaru
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    if ($limit < 1 || $limit > 100) $limit = 20;

    try {
        $stmt = $pdo->prepare("
            SELECT id, log_time, source, action, duration_seconds, notes
            FROM watering_logs
            ORDER BY log_time DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'DB error (GET)',
            'detail' => $e->getMessage()
        ]);
    }
    exit;
}

if ($method === 'POST') {
    // POST: simpan log baru
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if ($input === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'JSON tidak valid',
            'raw'   => $raw
        ]);
        exit;
    }

    if (empty($input['source']) || empty($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Field source & action wajib diisi']);
        exit;
    }

    $source   = $input['source'];   // 'manual' atau 'otomatis'
    $action   = $input['action'];   // 'ON' atau 'OFF'
    $duration = isset($input['duration_seconds']) ? (int)$input['duration_seconds'] : null;
    $notes    = isset($input['notes']) ? $input['notes'] : null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO watering_logs (source, action, duration_seconds, notes)
            VALUES (:source, :action, :duration, :notes)
        ");
        $stmt->execute([
            ':source'   => $source,
            ':action'   => $action,
            ':duration' => $duration,
            ':notes'    => $notes,
        ]);

        echo json_encode([
            'success' => true,
            'id'      => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error'  => 'DB error (POST)',
            'detail' => $e->getMessage()
        ]);
    }
    exit;
}

// selain GET & POST
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
