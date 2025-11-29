<?php
// api/config.php
header('Content-Type: application/json');

$host = '127.0.0.1';
$db   = 'itani_db';
$user = 'root';
$pass = ''; // ganti kalau password MySQL kamu beda

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Gagal konek database']);
  exit;
}
