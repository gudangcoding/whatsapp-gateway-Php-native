<?php
header('Content-Type: application/json');
include '../helper/koneksi.php';
$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$dari = isset($_GET['dari']) ? $conn->real_escape_string($_GET['dari']) : '';
$sampai = isset($_GET['sampai']) ? $conn->real_escape_string($_GET['sampai']) : '';
$from_me = isset($_GET['from_me']) ? $conn->real_escape_string($_GET['from_me']) : '';
$sql = 'SELECT id, id_pesan, nomor, pesan, from_me, nomor_saya, tanggal FROM receive_chat WHERE 1=1';
if ($q !== '') {
    $sql .= " AND (nomor LIKE '%$q%' OR pesan LIKE '%$q%')";
}
if ($dari !== '') {
    $sql .= " AND tanggal >= '$dari'";
}
if ($sampai !== '') {
    $sql .= " AND tanggal <= '$sampai'";
}
if ($from_me !== '' && ($from_me === '0' || $from_me === '1')) {
    $sql .= " AND from_me = '$from_me'";
}
$sql .= ' ORDER BY tanggal DESC';
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data); 