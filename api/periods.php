<?php
// api/periods.php
session_start();
header('Content-Type: application/json');

require 'config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$role   = $_SESSION['user']['role'] ?? 'Petani';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET: ambil list periode (kalau Admin: semua, kalau Petani: hanya miliknya)
    try {
        if ($role === 'Admin') {
            $stmt = $pdo->query("
                SELECT id, user_id, nama_periode, tanggal_mulai, tanggal_selesai,
                       deskripsi, status, created_at, updated_at
                FROM periods
                ORDER BY tanggal_mulai DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT id, user_id, nama_periode, tanggal_mulai, tanggal_selesai,
                       deskripsi, status, created_at, updated_at
                FROM periods
                WHERE user_id = :uid
                ORDER BY tanggal_mulai DESC
            ");
            $stmt->execute([':uid' => $userId]);
        }

        $rows = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data'    => $rows
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'DB error (GET)',
            'detail'  => $e->getMessage()
        ]);
    }
    exit;
}

if ($method === 'POST') {
    // POST: create / update / delete berdasarkan "action"
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    $action = $input['action'] ?? 'create';
    if ($action === 'set_active') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
            exit;
        }

        try {
            if ($role === 'Admin') {
                // Admin: bisa set aktif apa saja
                $pdo->exec("UPDATE periods SET is_active = 0");
                $stmt = $pdo->prepare("
                    UPDATE periods 
                    SET is_active = 1,
                        status = 'berjalan'
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $id]);
            } else {
                // Petani: hanya boleh set periode miliknya
                $stmt = $pdo->prepare("
                    UPDATE periods
                    SET is_active = 0
                    WHERE user_id = :uid
                ");
                $stmt->execute([':uid' => $userId]);

                $stmt = $pdo->prepare("
                    UPDATE periods
                    SET is_active = 1,
                        status = 'berjalan'
                    WHERE id = :id AND user_id = :uid
                ");
                $stmt->execute([
                    ':id'  => $id,
                    ':uid' => $userId
                ]);
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => 'DB error (SET_ACTIVE)',
                'detail'  => $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
            exit;
        }

        try {
            // kalau bukan admin, pastikan hanya boleh hapus milik sendiri
            if ($role === 'Admin') {
                $stmt = $pdo->prepare("DELETE FROM periods WHERE id = :id");
                $stmt->execute([':id' => $id]);
            } else {
                $stmt = $pdo->prepare("
                    DELETE FROM periods 
                    WHERE id = :id AND user_id = :uid
                ");
                $stmt->execute([
                    ':id'  => $id,
                    ':uid' => $userId
                ]);
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => 'DB error (DELETE)',
                'detail'  => $e->getMessage()
            ]);
        }
        exit;
    }

    // create / update
    $nama       = trim($input['nama_periode'] ?? '');
    $mulai      = $input['tanggal_mulai'] ?? null;
    $selesai    = $input['tanggal_selesai'] ?? null;
    $deskripsi  = $input['deskripsi'] ?? null;
    $status     = $input['status'] ?? 'planning';

    if ($nama === '' || !$mulai) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'Nama periode dan tanggal mulai wajib diisi'
        ]);
        exit;
    }

    if (!in_array($status, ['planning','berjalan','selesai','gagal'], true)) {
        $status = 'planning';
    }

    try {
        if ($action === 'update') {
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID tidak valid']);
                exit;
            }

            if ($role === 'Admin') {
                $stmt = $pdo->prepare("
                    UPDATE periods
                    SET nama_periode = :nama,
                        tanggal_mulai = :mulai,
                        tanggal_selesai = :selesai,
                        deskripsi = :deskripsi,
                        status = :status
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nama'      => $nama,
                    ':mulai'     => $mulai,
                    ':selesai'   => $selesai ?: null,
                    ':deskripsi' => $deskripsi,
                    ':status'    => $status,
                    ':id'        => $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE periods
                    SET nama_periode = :nama,
                        tanggal_mulai = :mulai,
                        tanggal_selesai = :selesai,
                        deskripsi = :deskripsi,
                        status = :status
                    WHERE id = :id AND user_id = :uid
                ");
                $stmt->execute([
                    ':nama'      => $nama,
                    ':mulai'     => $mulai,
                    ':selesai'   => $selesai ?: null,
                    ':deskripsi' => $deskripsi,
                    ':status'    => $status,
                    ':id'        => $id,
                    ':uid'       => $userId
                ]);
            }

            echo json_encode(['success' => true, 'mode' => 'update']);
        } else {
            // create
            $stmt = $pdo->prepare("
                INSERT INTO periods (user_id, nama_periode, tanggal_mulai, tanggal_selesai, deskripsi, status)
                VALUES (:uid, :nama, :mulai, :selesai, :deskripsi, :status)
            ");
            $stmt->execute([
                ':uid'       => $userId,
                ':nama'      => $nama,
                ':mulai'     => $mulai,
                ':selesai'   => $selesai ?: null,
                ':deskripsi' => $deskripsi,
                ':status'    => $status
            ]);

            echo json_encode([
                'success' => true,
                'mode'    => 'create',
                'id'      => (int)$pdo->lastInsertId()
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'DB error (SAVE)',
            'detail'  => $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);