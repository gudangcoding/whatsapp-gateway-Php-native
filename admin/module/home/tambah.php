<?php
// filepath: c:\laragon\www\wa-gateway\module\home\tambah_device.php
header('Content-Type: application/json');
include '../../helper/koneksi.php';

$nama = trim($_POST['nama'] ?? '');
$nomor = trim($_POST['nomor'] ?? '');
$pesan = trim($_POST['pesan'] ?? '');

if (!$nama || !$nomor) {
    echo json_encode(['success' => false, 'error' => 'Nama dan nomor wajib diisi']);
    exit;
}

// Cek duplikat nomor
$stmt = $conn->prepare("SELECT id FROM nomor WHERE nomor = ?");
$stmt->bind_param('s', $nomor);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Nomor sudah terdaftar']);
    exit;
}
$stmt->close();

// Insert nomor baru
$stmt = $conn->prepare("INSERT INTO nomor (nama, nomor, pesan) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $nama, $nomor, $pesan);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal menambah nomor']);
}
$stmt->close();
$conn->close();
?>