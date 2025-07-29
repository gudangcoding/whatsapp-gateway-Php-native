<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil semua blast atau blast tertentu
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    
    if ($id) {
        // Ambil blast tertentu
        $stmt = $conn->prepare("SELECT id, nomor, pesan, media, jadwal, make_by FROM blast WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Blast tidak ditemukan']);
        }
        $stmt->close();
    } else {
        // Ambil semua blast
        $result = $conn->query("SELECT id, nomor, pesan, media, jadwal, make_by FROM blast ORDER BY jadwal DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
    exit;
}

// POST - Tambah blast baru
if ($method === 'POST') {
    $nomor = trim($_POST['nomor'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    $media = trim($_POST['media'] ?? '');
    $jadwal = trim($_POST['jadwal'] ?? '');
    $make_by = trim($_POST['make_by'] ?? '');
    
    if (!$nomor || !$pesan || !$jadwal) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor, pesan, dan jadwal wajib diisi']);
        exit;
    }
    
    // Insert blast baru
    $stmt = $conn->prepare("INSERT INTO blast (nomor, pesan, media, jadwal, make_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $nomor, $pesan, $media, $jadwal, $make_by);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Blast berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah blast']);
    }
    $stmt->close();
    exit;
}

// PUT - Update blast
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? '';
    $nomor = trim($data['nomor'] ?? '');
    $pesan = trim($data['pesan'] ?? '');
    $media = trim($data['media'] ?? '');
    $jadwal = trim($data['jadwal'] ?? '');
    $make_by = trim($data['make_by'] ?? '');
    
    if (!$id || !$nomor || !$pesan || !$jadwal) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID, nomor, pesan, dan jadwal wajib diisi']);
        exit;
    }
    
    // Update blast
    $stmt = $conn->prepare("UPDATE blast SET nomor = ?, pesan = ?, media = ?, jadwal = ?, make_by = ? WHERE id = ?");
    $stmt->bind_param('sssssi', $nomor, $pesan, $media, $jadwal, $make_by, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blast berhasil diupdate']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengupdate blast']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus blast
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID blast wajib diisi']);
        exit;
    }
    
    // Hapus blast
    $stmt = $conn->prepare("DELETE FROM blast WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blast berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus blast']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 