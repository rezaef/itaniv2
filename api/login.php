<?php
// api/login.php
session_start();
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username & password wajib diisi']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name, username, role, password_hash
    FROM users
    WHERE username = :u
    LIMIT 1
");
$stmt->execute([':u' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Username atau password salah']);
    exit;
}

// login sukses → simpan ke session
$_SESSION['user'] = [
    'id'       => $user['id'],
    'name'     => $user['name'],
    'username' => $user['username'],
    'role'     => $user['role'],
];

echo json_encode([
    'success' => true,
    'user'    => [
        'id'       => $user['id'],
        'name'     => $user['name'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ],
]);
