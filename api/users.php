<?php
// api/users.php
require 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // ambil semua user
    $stmt = $pdo->query("SELECT id, name, username, role, created_at FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $name     = trim($input['name']     ?? '');
    $username = trim($input['username'] ?? '');
    $role     = trim($input['role']     ?? 'Viewer');

    if ($name === '' || $username === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Nama & username wajib diisi']);
        exit;
    }

    // cek username unik
    $check = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $check->execute([':u' => $username]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Username sudah dipakai']);
        exit;
    }

    // password default untuk user baru
    $defaultPassword = 'password123';
    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (name, username, role, password_hash, created_at)
        VALUES (:name, :username, :role, :password_hash, NOW())
    ");
    $stmt->execute([
        ':name'          => $name,
        ':username'      => $username,
        ':role'          => $role,
        ':password_hash' => $passwordHash,
    ]);

    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID wajib dikirim']);
        exit;
    }

    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
