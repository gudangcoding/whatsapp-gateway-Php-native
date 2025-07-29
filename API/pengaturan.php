<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Ambil pengaturan
if ($method === 'GET') {
    $id = $_GET['id'] ?? '1'; // Default ambil pengaturan dengan ID 1
    
    $stmt = $conn->prepare("SELECT id, chunk, hook_group, nomor, api_key, callback FROM pengaturan WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        // Jika tidak ada data, buat pengaturan default
        $defaultApiKey = '310ea2abbaafe1844ac63f57ff20860b78e77c40';
        $stmt = $conn->prepare("INSERT INTO pengaturan (id, chunk, hook_group, nomor, api_key, callback) VALUES (1, 100, '', '', ?, '')");
        $stmt->bind_param('s', $defaultApiKey);
        $stmt->execute();
        
        $data = [
            'id' => 1,
            'chunk' => 100,
            'hook_group' => '',
            'nomor' => '',
            'api_key' => $defaultApiKey,
            'callback' => ''
        ];
        echo json_encode(['success' => true, 'data' => $data]);
    }
    $stmt->close();
    exit;
}

// POST - Tambah pengaturan baru
if ($method === 'POST') {
    $chunk = intval($_POST['chunk'] ?? 100);
    $hook_group = trim($_POST['hook_group'] ?? '');
    $nomor = trim($_POST['nomor'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '310ea2abbaafe1844ac63f57ff20860b78e77c40');
    $callback = trim($_POST['callback'] ?? '');
    
    // Insert pengaturan baru
    $stmt = $conn->prepare("INSERT INTO pengaturan (chunk, hook_group, nomor, api_key, callback) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $chunk, $hook_group, $nomor, $api_key, $callback);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil ditambahkan', 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menambah pengaturan']);
    }
    $stmt->close();
    exit;
}

// PUT - Update pengaturan
if ($method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? 1;
    $chunk = intval($data['chunk'] ?? 100);
    $hook_group = trim($data['hook_group'] ?? '');
    $nomor = trim($data['nomor'] ?? '');
    $api_key = trim($data['api_key'] ?? '310ea2abbaafe1844ac63f57ff20860b78e77c40');
    $callback = trim($data['callback'] ?? '');
    
    // Cek apakah pengaturan sudah ada
    $stmt = $conn->prepare("SELECT id FROM pengaturan WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Update pengaturan yang sudah ada
        $stmt = $conn->prepare("UPDATE pengaturan SET chunk = ?, hook_group = ?, nomor = ?, api_key = ?, callback = ? WHERE id = ?");
        $stmt->bind_param('issssi', $chunk, $hook_group, $nomor, $api_key, $callback, $id);
    } else {
        // Insert pengaturan baru jika tidak ada
        $stmt = $conn->prepare("INSERT INTO pengaturan (id, chunk, hook_group, nomor, api_key, callback) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissss', $id, $chunk, $hook_group, $nomor, $api_key, $callback);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil disimpan']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan pengaturan']);
    }
    $stmt->close();
    exit;
}

// DELETE - Hapus pengaturan
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID pengaturan wajib diisi']);
        exit;
    }
    
    // Hapus pengaturan
    $stmt = $conn->prepare("DELETE FROM pengaturan WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus pengaturan']);
    }
    $stmt->close();
    exit;
}

// Method tidak didukung
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
?> 