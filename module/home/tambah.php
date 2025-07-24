<?php
// filepath: c:\laragon\www\wa-gateway\module\home\tambah_device.php
header('Content-Type: application/json');
include '../../helper/koneksi.php';

$pemilik = trim($_POST['pemilik'] ?? '');
$nomor = trim($_POST['nomor'] ?? '');
$link_webhook = trim($_POST['link_webhook'] ?? '');

if (!$pemilik || !$nomor) {
    echo json_encode(['success' => false, 'error' => 'Pemilik dan nomor wajib diisi']);
    exit;
}

// Cek duplikat nomor
$stmt = $conn->prepare("SELECT id FROM device WHERE nomor = ?");
$stmt->bind_param('s', $nomor);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Nomor sudah terdaftar']);
    exit;
}
$stmt->close();

// Insert device baru
$stmt = $conn->prepare("INSERT INTO device (pemilik, nomor, link_webhook) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $pemilik, $nomor, $link_webhook);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal menambah device']);
}
$stmt->close();
$conn->close();
?>