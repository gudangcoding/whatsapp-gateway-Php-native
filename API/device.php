<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil semua device atau device tertentu
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    
    if ($id) {
        // Ambil device tertentu
        $stmt = $conn->prepare("SELECT id, nama, nomor, pesan FROM nomor WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Device tidak ditemukan']);
        }
        $stmt->close();
    } else {
        // Ambil semua device
        $result = $conn->query("SELECT id, nama, nomor, pesan FROM nomor ORDER BY id DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
    exit;
}

// POST - Tambah device baru
if ($method === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nomor = trim($_POST['nomor'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    
    if (!$nama || !$nomor) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nama dan nomor wajib diisi']);
        exit;
    }
    
    // Cek duplikat nomor
    $stmt = $conn->prepare("SELECT id FROM nomor WHERE nomor = ?");
    $stmt->bind_param('s', $nomor);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Insert device baru
    $stmt = $conn->prepare("INSERT INTO nomor (nama, nomor, pesan) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $nama, $nomor, $pesan);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Device berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah device']);
    }
    $stmt->close();
    exit;
}

// PUT - Update device
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? '';
    $nama = trim($data['nama'] ?? '');
    $nomor = trim($data['nomor'] ?? '');
    $pesan = trim($data['pesan'] ?? '');
    
    if (!$id || !$nama || !$nomor) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID, nama, dan nomor wajib diisi']);
        exit;
    }
    
    // Cek duplikat nomor (kecuali device yang sedang diupdate)
    $stmt = $conn->prepare("SELECT id FROM nomor WHERE nomor = ? AND id != ?");
    $stmt->bind_param('si', $nomor, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Update device
    $stmt = $conn->prepare("UPDATE nomor SET nama = ?, nomor = ?, pesan = ? WHERE id = ?");
    $stmt->bind_param('sssi', $nama, $nomor, $pesan, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Device berhasil diupdate']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengupdate device']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus device
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID device wajib diisi']);
        exit;
    }
    
    // Hapus device
    $stmt = $conn->prepare("DELETE FROM nomor WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Device berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus device']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 