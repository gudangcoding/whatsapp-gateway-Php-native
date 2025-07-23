<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $result = $conn->query('SELECT * FROM auto_reply');
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}
if ($method === 'POST') {
    $keyword = $_POST['keyword'] ?? '';
    $reply = $_POST['reply'] ?? '';
    if (!$keyword || !$reply) {
        http_response_code(400);
        echo json_encode(['error' => 'Kata kunci dan balasan wajib diisi']);
        exit;
    }
    $stmt = $conn->prepare('INSERT INTO auto_reply (keyword, reply) VALUES (?, ?)');
    $stmt->bind_param('ss', $keyword, $reply);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']); 