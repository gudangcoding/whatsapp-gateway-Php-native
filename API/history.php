<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../helper/koneksi.php';

// DataTables server-side parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

// Column ordering
$orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$orderDir = isset($_POST['order'][0]['dir']) && in_array($_POST['order'][0]['dir'], ['asc', 'desc']) ? $_POST['order'][0]['dir'] : 'desc';

// Column mapping (index to column name)
$columns = [
    0 => 'id', // No (not used for ordering)
    1 => 'id_pesan',
    2 => 'nomor',
    3 => 'pesan',
    4 => 'from_me',
    5 => 'nomor_saya',
    6 => 'tanggal'
];
$orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'tanggal';

// Filtering (optional: you can add more filters here if needed)
$where = "WHERE 1=1";
if ($searchValue !== '') {
    $where .= " AND (id_pesan LIKE '%$searchValue%' OR nomor LIKE '%$searchValue%' OR pesan LIKE '%$searchValue%' OR nomor_saya LIKE '%$searchValue%' OR tanggal LIKE '%$searchValue%')";
}

// Optional: custom filters (if you want to support GET params for filtering)
$dari = isset($_GET['dari']) ? $conn->real_escape_string($_GET['dari']) : '';
$sampai = isset($_GET['sampai']) ? $conn->real_escape_string($_GET['sampai']) : '';
$from_me = isset($_GET['from_me']) ? $conn->real_escape_string($_GET['from_me']) : '';

if ($dari !== '') {
    $where .= " AND tanggal >= '$dari'";
}
if ($sampai !== '') {
    $where .= " AND tanggal <= '$sampai'";
}
if ($from_me !== '' && ($from_me === '0' || $from_me === '1')) {
    $where .= " AND from_me = '$from_me'";
}

// Total records
$totalQuery = "SELECT COUNT(*) as total FROM receive_chat";
$totalResult = $conn->query($totalQuery);
$totalData = $totalResult->fetch_assoc();
$recordsTotal = intval($totalData['total']);

// Total filtered records
$filteredQuery = "SELECT COUNT(*) as total FROM receive_chat $where";
$filteredResult = $conn->query($filteredQuery);
$filteredData = $filteredResult->fetch_assoc();
$recordsFiltered = intval($filteredData['total']);

// Main data query
$sql = "SELECT id, id_pesan, nomor, pesan, from_me, nomor_saya, tanggal FROM receive_chat $where ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Output for DataTables
echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data" => $data
]);