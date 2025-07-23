<?php
// Tambahkan header CORS agar bisa diakses cross-origin jika perlu
// Set CORS headers for preflight and actual requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Handle preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../helper/koneksi.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}
$input = file_get_contents('php://input');
$dataInput = json_decode($input, true);

// Support both JSON and form-urlencoded
$nomor = $dataInput['nomor'] ?? ($_POST['nomor'] ?? '');
$pesan = $dataInput['pesan'] ?? ($_POST['pesan'] ?? '');
$pengirim = $dataInput['pengirim'] ?? ($_POST['pengirim'] ?? '');

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

// Kirim ke backend Node.js dengan format JSON agar sesuai dengan backend
$url = 'http://localhost:3000/api/send-message';
$payload = json_encode([
    'pengirim' => $pengirim,
    'nomor' => $nomor,
    'pesan' => $pesan
]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $payload,
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