<?php
// api/users.php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // List semua user
  $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id ASC");
  $users = $stmt->fetchAll();
  echo json_encode($users);
  exit;
}

if ($method === 'POST') {
  // Tambah user baru (simple, tanpa validasi besar dulu)
  $input = json_decode(file_get_contents('php://input'), true);

  if (!$input || empty($input['name']) || empty($input['email']) || empty($input['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
  }

  $name  = $input['name'];
  $email = $input['email'];
  $role  = $input['role'];

  // password default sementara
  $password = password_hash('password123', PASSWORD_BCRYPT);

  $stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)"
  );

  try {
    $stmt->execute([$name, $email, $password, $role]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  } catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Gagal insert user, mungkin email sudah terdaftar']);
  }
  exit;
}

if ($method === 'DELETE') {
  if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID diperlukan']);
    exit;
  }
  $id = (int)$_GET['id'];

  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$id]);

  echo json_encode(['success' => true]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
