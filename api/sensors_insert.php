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
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}

// Ambil nilai (boleh null)
$ph    = isset($input['ph'])            ? (float)$input['ph']            : null;
$humi  = isset($input['soil_moisture']) ? (float)$input['soil_moisture'] : (isset($input['humi']) ? (float)$input['humi'] : null);
$temp  = isset($input['soil_temp'])     ? (float)$input['soil_temp']     : (isset($input['temp']) ? (float)$input['temp'] : null);
$ec    = isset($input['ec'])           ? (int)$input['ec'] : null;
$n     = isset($input['n'])            ? (int)$input['n']  : null;
$p     = isset($input['p'])            ? (int)$input['p']  : null;
$k     = isset($input['k'])            ? (int)$input['k']  : null;

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

  echo json_encode(['success' => true]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error'   => $e->getMessage()
  ]);
}
