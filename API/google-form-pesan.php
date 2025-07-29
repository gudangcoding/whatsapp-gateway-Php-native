<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil semua google form pesan atau pesan tertentu
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    $id_pesan = $_GET['id_pesan'] ?? '';
    
    if ($id) {
        // Ambil pesan berdasarkan ID
        $stmt = $conn->prepare("SELECT id, id_pesan, nomor, pesan FROM google_form_pesan WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pesan tidak ditemukan']);
        }
        $stmt->close();
    } elseif ($id_pesan) {
        // Ambil pesan berdasarkan id_pesan
        $stmt = $conn->prepare("SELECT id, id_pesan, nomor, pesan FROM google_form_pesan WHERE id_pesan = ?");
        $stmt->bind_param('s', $id_pesan);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        $stmt->close();
    } else {
        // Ambil semua pesan
        $result = $conn->query("SELECT id, id_pesan, nomor, pesan FROM google_form_pesan ORDER BY id DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
    exit;
}

// POST - Tambah google form pesan baru
if ($method === 'POST') {
    $id_pesan = trim($_POST['id_pesan'] ?? '');
    $nomor = trim($_POST['nomor'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    
    if (!$id_pesan || !$nomor || !$pesan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID pesan, nomor, dan pesan wajib diisi']);
        exit;
    }
    
    // Insert google form pesan baru
    $stmt = $conn->prepare("INSERT INTO google_form_pesan (id_pesan, nomor, pesan) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $id_pesan, $nomor, $pesan);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Pesan berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah pesan']);
    }
    $stmt->close();
    exit;
}

// PUT - Update google form pesan
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? '';
    $id_pesan = trim($data['id_pesan'] ?? '');
    $nomor = trim($data['nomor'] ?? '');
    $pesan = trim($data['pesan'] ?? '');
    
    if (!$id || !$id_pesan || !$nomor || !$pesan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID, ID pesan, nomor, dan pesan wajib diisi']);
        exit;
    }
    
    // Update google form pesan
    $stmt = $conn->prepare("UPDATE google_form_pesan SET id_pesan = ?, nomor = ?, pesan = ? WHERE id = ?");
    $stmt->bind_param('sssi', $id_pesan, $nomor, $pesan, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pesan berhasil diupdate']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengupdate pesan']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus google form pesan
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID pesan wajib diisi']);
        exit;
    }
    
    // Hapus google form pesan
    $stmt = $conn->prepare("DELETE FROM google_form_pesan WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pesan berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus pesan']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 