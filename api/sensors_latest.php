<?php
// api/sensors_latest.php
require 'config.php';

$stmt = $pdo->query("SELECT * FROM sensor_readings ORDER BY reading_time DESC LIMIT 1");
$row = $stmt->fetch();

if (!$row) {
  echo json_encode(['exists' => false]);
  exit;
}

echo json_encode([
  'exists' => true,
  'ph'     => $row['ph'] !== null ? (float)$row['ph'] : null,
  'humi'   => $row['soil_moisture'] !== null ? (float)$row['soil_moisture'] : null,
  'temp'   => $row['soil_temp'] !== null ? (float)$row['soil_temp'] : null,
  'ec'     => $row['ec'] !== null ? (int)$row['ec'] : null,
  'n'      => $row['n'] !== null ? (int)$row['n'] : null,
  'p'      => $row['p'] !== null ? (int)$row['p'] : null,
  'k'      => $row['k'] !== null ? (int)$row['k'] : null,
  'time'   => $row['reading_time'],
]);
