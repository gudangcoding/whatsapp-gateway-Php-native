<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil semua kontak atau kontak tertentu
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    $number = $_GET['number'] ?? '';
    
    if ($id) {
        // Ambil kontak berdasarkan ID
        $stmt = $conn->prepare("SELECT id, number, name, type FROM contacts WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Kontak tidak ditemukan']);
        }
        $stmt->close();
    } elseif ($number) {
        // Ambil kontak berdasarkan nomor
        $stmt = $conn->prepare("SELECT id, number, name, type FROM contacts WHERE number = ?");
        $stmt->bind_param('s', $number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Kontak tidak ditemukan']);
        }
        $stmt->close();
    } else {
        // Ambil semua kontak
        $result = $conn->query("SELECT id, number, name, type FROM contacts ORDER BY name ASC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
    exit;
}

// POST - Tambah kontak baru
if ($method === 'POST') {
    $number = trim($_POST['number'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'Personal');
    
    if (!$number || !$name) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor dan nama wajib diisi']);
        exit;
    }
    
    // Validasi type
    if (!in_array($type, ['Personal', 'Group'])) {
        $type = 'Personal';
    }
    
    // Cek duplikat nomor
    $stmt = $conn->prepare("SELECT id FROM contacts WHERE number = ?");
    $stmt->bind_param('s', $number);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Insert kontak baru
    $stmt = $conn->prepare("INSERT INTO contacts (number, name, type) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $number, $name, $type);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Kontak berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah kontak']);
    }
    $stmt->close();
    exit;
}

// PUT - Update kontak
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? '';
    $number = trim($data['number'] ?? '');
    $name = trim($data['name'] ?? '');
    $type = trim($data['type'] ?? 'Personal');
    
    if (!$id || !$number || !$name) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID, nomor, dan nama wajib diisi']);
        exit;
    }
    
    // Validasi type
    if (!in_array($type, ['Personal', 'Group'])) {
        $type = 'Personal';
    }
    
    // Cek duplikat nomor (kecuali kontak yang sedang diupdate)
    $stmt = $conn->prepare("SELECT id FROM contacts WHERE number = ? AND id != ?");
    $stmt->bind_param('si', $number, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Update kontak
    $stmt = $conn->prepare("UPDATE contacts SET number = ?, name = ?, type = ? WHERE id = ?");
    $stmt->bind_param('sssi', $number, $name, $type, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Kontak berhasil diupdate']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengupdate kontak']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus kontak
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID kontak wajib diisi']);
        exit;
    }
    
    // Hapus kontak
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Kontak berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus kontak']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 