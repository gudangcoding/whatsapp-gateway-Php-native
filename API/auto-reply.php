<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $result = $conn->query('SELECT * FROM autoreply');
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}
if ($method === 'POST') {
    $keyword = $_POST['keyword'] ?? '';
    $response = $_POST['response'] ?? '';
    $media = $_POST['media'] ?? '';
    $case_sensitive = $_POST['case_sensitive'] ?? '0';
    if (!$keyword || !$response) {
        http_response_code(400);
        echo json_encode(['error' => 'Kata kunci dan balasan wajib diisi']);
        exit;
    }
    $stmt = $conn->prepare('INSERT INTO autoreply (keyword, response, media, case_sensitive) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $keyword, $response, $media, $case_sensitive);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']); 