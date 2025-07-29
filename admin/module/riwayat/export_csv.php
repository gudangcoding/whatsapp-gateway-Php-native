<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];

// Filter parameters
$filter_nomor = isset($_GET['nomor']) ? $_GET['nomor'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$where_conditions = ["rc.user_id = ?"];
$params = [$user_id];

if ($filter_nomor) {
    $where_conditions[] = "rc.nomor LIKE ?";
    $params[] = "%$filter_nomor%";
}

if ($filter_type) {
    $where_conditions[] = "rc.from_me = ?";
    $params[] = $filter_type;
}

if ($filter_date) {
    $where_conditions[] = "DATE(rc.tanggal) = ?";
    $params[] = $filter_date;
}

$where_clause = implode(' AND ', $where_conditions);

// Get messages
$query = "SELECT rc.*, n.nama as device_name 
          FROM receive_chat rc 
          LEFT JOIN nomor n ON rc.nomor_saya = n.nomor 
          WHERE $where_clause 
          ORDER BY rc.tanggal DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
$filename = 'riwayat_pesan_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'ID',
    'ID Pesan',
    'Nomor',
    'Pesan',
    'Tipe',
    'Device',
    'Tanggal',
    'Tipe Media',
    'URL Media'
]);

// Add data rows
foreach ($messages as $message) {
    $tipe = $message['from_me'] === '1' ? 'Keluar' : 'Masuk';
    
    fputcsv($output, [
        $message['id'],
        $message['id_pesan'] ?? '',
        $message['nomor'],
        $message['pesan'],
        $tipe,
        $message['device_name'] ?? '',
        $message['tanggal'],
        $message['message_type'] ?? 'text',
        $message['media_url'] ?? ''
    ]);
}

fclose($output);
exit(); 