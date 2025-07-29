<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil semua google form atau form tertentu
if ($method === 'GET') {
    $id = $_GET['id'] ?? '';
    
    if ($id) {
        // Ambil form tertentu
        $stmt = $conn->prepare("SELECT id, form_id, form_name, target, pesan FROM google_form WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Google Form tidak ditemukan']);
        }
        $stmt->close();
    } else {
        // Ambil semua google form
        $result = $conn->query("SELECT id, form_id, form_name, target, pesan FROM google_form ORDER BY id DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    }
    exit;
}

// POST - Tambah google form baru
if ($method === 'POST') {
    $form_id = trim($_POST['form_id'] ?? '');
    $form_name = trim($_POST['form_name'] ?? '');
    $target = trim($_POST['target'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    
    if (!$form_id || !$form_name || !$target || !$pesan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Semua field wajib diisi']);
        exit;
    }
    
    // Cek duplikat form_id
    $stmt = $conn->prepare("SELECT id FROM google_form WHERE form_id = ?");
    $stmt->bind_param('s', $form_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Form ID sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Insert google form baru
    $stmt = $conn->prepare("INSERT INTO google_form (form_id, form_name, target, pesan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $form_id, $form_name, $target, $pesan);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Google Form berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah Google Form']);
    }
    $stmt->close();
    exit;
}

// PUT - Update google form
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? '';
    $form_id = trim($data['form_id'] ?? '');
    $form_name = trim($data['form_name'] ?? '');
    $target = trim($data['target'] ?? '');
    $pesan = trim($data['pesan'] ?? '');
    
    if (!$id || !$form_id || !$form_name || !$target || !$pesan) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Semua field wajib diisi']);
        exit;
    }
    
    // Cek duplikat form_id (kecuali form yang sedang diupdate)
    $stmt = $conn->prepare("SELECT id FROM google_form WHERE form_id = ? AND id != ?");
    $stmt->bind_param('si', $form_id, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Form ID sudah terdaftar']);
        exit;
    }
    $stmt->close();
    
    // Update google form
    $stmt = $conn->prepare("UPDATE google_form SET form_id = ?, form_name = ?, target = ?, pesan = ? WHERE id = ?");
    $stmt->bind_param('ssssi', $form_id, $form_name, $target, $pesan, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Google Form berhasil diupdate']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengupdate Google Form']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus google form
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID Google Form wajib diisi']);
        exit;
    }
    
    // Hapus google form
    $stmt = $conn->prepare("DELETE FROM google_form WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Google Form berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus Google Form']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 