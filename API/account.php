<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil semua account atau account tertentu
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    
    if ($id) {
        // Ambil account tertentu
        $stmt = $conn->prepare("SELECT id, username, level FROM account WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Account tidak ditemukan']);
        }
        $stmt->close();
    } else {
        // Ambil semua account (tanpa password)
        $result = $conn->query("SELECT id, username, level FROM account ORDER BY id DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
    exit;
}

// POST - Tambah account baru
if ($method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $level = trim($_POST['level'] ?? '2');
    
    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username dan password wajib diisi']);
        exit;
    }
    
    // Validasi level
    if (!in_array($level, ['1', '2'])) {
        $level = '2';
    }
    
    // Cek duplikat username
    $stmt = $conn->prepare("SELECT id FROM account WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Hash password
    $hashedPassword = sha1($password);
    
    // Insert account baru
    $stmt = $conn->prepare("INSERT INTO account (username, password, level) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $hashedPassword, $level);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Account berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah account']);
    }
    $stmt->close();
    exit;
}

// PUT - Update account
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? '';
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $level = trim($data['level'] ?? '2');
    
    if (!$id || !$username) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID dan username wajib diisi']);
        exit;
    }
    
    // Validasi level
    if (!in_array($level, ['1', '2'])) {
        $level = '2';
    }
    
    // Cek duplikat username (kecuali account yang sedang diupdate)
    $stmt = $conn->prepare("SELECT id FROM account WHERE username = ? AND id != ?");
    $stmt->bind_param('si', $username, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Update account
    if ($password) {
        // Update dengan password baru
        $hashedPassword = sha1($password);
        $stmt = $conn->prepare("UPDATE account SET username = ?, password = ?, level = ? WHERE id = ?");
        $stmt->bind_param('sssi', $username, $hashedPassword, $level, $id);
    } else {
        // Update tanpa mengubah password
        $stmt = $conn->prepare("UPDATE account SET username = ?, level = ? WHERE id = ?");
        $stmt->bind_param('ssi', $username, $level, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account berhasil diupdate']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengupdate account']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus account
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID account wajib diisi']);
        exit;
    }
    
    // Jangan hapus account admin utama
    if ($id == 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tidak dapat menghapus account admin utama']);
        exit;
    }
    
    // Hapus account
    $stmt = $conn->prepare("DELETE FROM account WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus account']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 