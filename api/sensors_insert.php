<?php
// api/sensors_insert.php
header('Content-Type: application/json');
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ambil JSON body
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body', 'raw' => $raw]);
    exit;
}

// -------------------------------
// MAP FIELD JSON -> VARIABEL PHP
// -------------------------------

// pH
$ph = array_key_exists('ph', $input) && $input['ph'] !== null
    ? (float)$input['ph'] : null;

// Kelembapan tanah: dari JS (humi) atau langsung dari ESP (moisture / soil_moisture)
if (array_key_exists('humi', $input)) {
    $humi = $input['humi'] !== null ? (float)$input['humi'] : null;
} elseif (array_key_exists('moisture', $input)) {
    $humi = $input['moisture'] !== null ? (float)$input['moisture'] : null;
} elseif (array_key_exists('soil_moisture', $input)) {
    $humi = $input['soil_moisture'] !== null ? (float)$input['soil_moisture'] : null;
} else {
    $humi = null;
}

// Suhu tanah: dari JS (temp) atau dari ESP (temperature / soil_temp)
if (array_key_exists('temp', $input)) {
    $temp = $input['temp'] !== null ? (float)$input['temp'] : null;
} elseif (array_key_exists('temperature', $input)) {
    $temp = $input['temperature'] !== null ? (float)$input['temperature'] : null;
} elseif (array_key_exists('soil_temp', $input)) {
    $temp = $input['soil_temp'] !== null ? (float)$input['soil_temp'] : null;
} else {
    $temp = null;
}

// Nutrisi
$ec = array_key_exists('ec', $input) && $input['ec'] !== null ? (float)$input['ec'] : null;
$n  = array_key_exists('n',  $input) && $input['n']  !== null ? (int)$input['n']  : null;
$p  = array_key_exists('p',  $input) && $input['p']  !== null ? (int)$input['p']  : null;
$k  = array_key_exists('k',  $input) && $input['k']  !== null ? (int)$input['k']  : null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO sensor_readings
            (ph, soil_moisture, soil_temp, ec, n, p, k, reading_time)
        VALUES
            (:ph, :soil_moisture, :soil_temp, :ec, :n, :p, :k, NOW())
    ");

    $stmt->execute([
        ':ph'            => $ph,
        ':soil_moisture' => $humi,
        ':soil_temp'     => $temp,
        ':ec'            => $ec,
        ':n'             => $n,
        ':p'             => $p,
        ':k'             => $k,
    ]);

    echo json_encode([
        'success' => true,
        'saved'   => [
            'ph'   => $ph,
            'humi' => $humi,
            'temp' => $temp,
            'ec'   => $ec,
            'n'    => $n,
            'p'    => $p,
            'k'    => $k,
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
