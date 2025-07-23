<?php
header('Content-Type: application/json');
include '../helper/koneksi.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $result = $conn->query('SELECT * FROM pesan_terjadwal');
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}
if ($method === 'POST') {
    $nomor = $_POST['nomor'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    $waktu = $_POST['waktu'] ?? '';
    if (!$nomor || !$pesan || !$waktu) {
        http_response_code(400);
        echo json_encode(['error' => 'Nomor, pesan, dan waktu wajib diisi']);
        exit;
    }
    $stmt = $conn->prepare('INSERT INTO pesan_terjadwal (nomor, pesan, waktu) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $nomor, $pesan, $waktu);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']); 