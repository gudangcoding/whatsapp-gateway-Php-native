<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$message_id = (int)$_GET['id'];

// Get message details
$query = "SELECT rc.*, n.nama as device_name 
          FROM receive_chat rc 
          LEFT JOIN nomor n ON rc.nomor_saya = n.nomor 
          WHERE rc.id = ? AND rc.user_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$message_id, $user_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    echo '<div class="alert alert-danger">Pesan tidak ditemukan</div>';
    exit();
}
?>

<div class="row">
    <div class="col-md-6">
        <h6>Informasi Pesan</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>ID Pesan:</strong></td>
                <td><?= htmlspecialchars($message['id_pesan'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Nomor:</strong></td>
                <td><?= htmlspecialchars($message['nomor']) ?></td>
            </tr>
            <tr>
                <td><strong>Tipe:</strong></td>
                <td>
                    <?php if ($message['from_me'] === '1'): ?>
                        <span class="badge bg-info">Pesan Keluar</span>
                    <?php else: ?>
                        <span class="badge bg-success">Pesan Masuk</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Device:</strong></td>
                <td><?= htmlspecialchars($message['device_name'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Waktu:</strong></td>
                <td><?= date('d/m/Y H:i:s', strtotime($message['tanggal'])) ?></td>
            </tr>
            <tr>
                <td><strong>Tipe Media:</strong></td>
                <td><?= htmlspecialchars($message['message_type'] ?? 'text') ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Isi Pesan</h6>
        <div class="border rounded p-3 bg-light">
            <?php if ($message['message_type'] === 'image' && $message['media_url']): ?>
                <img src="<?= htmlspecialchars($message['media_url']) ?>" class="img-fluid mb-2" alt="Media">
            <?php endif; ?>
            <p class="mb-0"><?= nl2br(htmlspecialchars($message['pesan'])) ?></p>
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="button" class="btn btn-primary" onclick="replyMessage('<?= htmlspecialchars($message['nomor']) ?>')">
        <i class="fas fa-reply"></i> Balas Pesan
    </button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        Tutup
    </button>
</div>

<script>
function replyMessage(nomor) {
    window.location.href = `../module/kirim-pesan.php?nomor=${encodeURIComponent(nomor)}`;
}
</script> 