<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $result = $conn->query("SELECT id, nomor, pesan, jadwal, tiap_bulan, status FROM pesan WHERE status='MENUNGGU JADWAL' ORDER BY jadwal ASC");
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
    $jadwal = $_POST['jadwal'] ?? '';
    $tiap_bulan = $_POST['tiap_bulan'] ?? '0';
    if (!$nomor || !$pesan || !$jadwal) {
        http_response_code(400);
        echo json_encode(['error' => 'Nomor, pesan, dan jadwal wajib diisi']);
        exit;
    }
    $stmt = $conn->prepare('INSERT INTO pesan (nomor, pesan, jadwal, tiap_bulan, status) VALUES (?, ?, ?, ?, ?)');
    $status = 'MENUNGGU JADWAL';
    $stmt->bind_param('sssss', $nomor, $pesan, $jadwal, $tiap_bulan, $status);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']); 