<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

$nomor = $_GET['nomor'] ?? '';
if (!$nomor) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor wajib diisi']);
    exit;
}

$stmt = $conn->prepare("SELECT id_pesan, pesan, from_me, tanggal FROM receive_chat WHERE nomor = ? ORDER BY tanggal ASC");
$stmt->bind_param('s', $nomor);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conn->close();
?> 