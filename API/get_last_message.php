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

$stmt = $conn->prepare("SELECT pesan FROM receive_chat WHERE nomor = ? ORDER BY tanggal DESC LIMIT 1");
$stmt->bind_param('s', $nomor);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['pesan' => $row['pesan']]);
} else {
    echo json_encode(['pesan' => '']);
}

$stmt->close();
$conn->close();
?> 