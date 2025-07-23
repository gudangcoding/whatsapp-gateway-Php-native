<?php
header('Content-Type: application/json');
include '../helper/koneksi.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}
$nomor = $_POST['nomor'] ?? '';
$pesan = $_POST['pesan'] ?? '';
$pengirim = $_POST['pengirim'] ?? '';
if (!$nomor || !$pesan) {
    http_response_code(400);
    echo json_encode(['error' => 'Nomor dan pesan wajib diisi']);
    exit;
}
// Jika pengirim kosong, ambil device pertama dari database
if (!$pengirim) {
    $q = $conn->query("SELECT nomor FROM device LIMIT 1");
    $row = $q ? $q->fetch_assoc() : null;
    $pengirim = $row ? $row['nomor'] : 'default';
}
// Kirim ke backend Node.js
$url = 'http://localhost:3000/api/send-message';
$data = [
    'pengirim' => $pengirim,
    'nomor' => $nomor,
    'pesan' => $pesan
];
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
        'timeout' => 10
    ]
];
$context  = stream_context_create($options);
$result = @file_get_contents($url, false, $context);
if ($result === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menghubungi backend Node.js']);
    exit;
}
echo $result; 