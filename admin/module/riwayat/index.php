<?php
// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter
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

// Get total count
$count_query = "SELECT COUNT(*) as total FROM receive_chat rc WHERE $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Get messages
$query = "SELECT rc.*, n.nama as device_name 
          FROM receive_chat rc 
          LEFT JOIN nomor n ON rc.nomor_saya = n.nomor 
          WHERE $where_clause 
          ORDER BY rc.tanggal DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user devices for filter
$devices_query = "SELECT DISTINCT n.nomor, n.nama 
                  FROM nomor n 
                  INNER JOIN user_devices ud ON n.id = ud.device_id 
                  WHERE ud.user_id = ? AND ud.status = 'active'";
$stmt = $db->prepare($devices_query);
$stmt->execute([$user_id]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


    <div class="container-fluid">
        <div class="row">
           

            <!-- Main content -->
            <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-history"></i> Riwayat Pesan
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToCSV()">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="nomor" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="nomor" name="nomor" 
                                   value="<?= htmlspecialchars($filter_nomor) ?>" placeholder="Cari nomor...">
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Tipe Pesan</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Semua</option>
                                <option value="0" <?= $filter_type === '0' ? 'selected' : '' ?>>Masuk</option>
                                <option value="1" <?= $filter_type === '1' ? 'selected' : '' ?>>Keluar</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?= htmlspecialchars($filter_date) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Statistics -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Pesan</h5>
                                <p class="card-text h3"><?= number_format($total_records) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Pesan Masuk</h5>
                                <p class="card-text h3">
                                    <?php
                                    $incoming_query = "SELECT COUNT(*) as count FROM receive_chat rc WHERE rc.user_id = ? AND rc.from_me = '0'";
                                    $stmt = $db->prepare($incoming_query);
                                    $stmt->execute([$user_id]);
                                    echo number_format($stmt->fetch(PDO::FETCH_ASSOC)['count']);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Pesan Keluar</h5>
                                <p class="card-text h3">
                                    <?php
                                    $outgoing_query = "SELECT COUNT(*) as count FROM receive_chat rc WHERE rc.user_id = ? AND rc.from_me = '1'";
                                    $stmt = $db->prepare($outgoing_query);
                                    $stmt->execute([$user_id]);
                                    echo number_format($stmt->fetch(PDO::FETCH_ASSOC)['count']);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Device Aktif</h5>
                                <p class="card-text h3">
                                    <?php
                                    $device_query = "SELECT COUNT(*) as count FROM user_devices ud WHERE ud.user_id = ? AND ud.status = 'active'";
                                    $stmt = $db->prepare($device_query);
                                    $stmt->execute([$user_id]);
                                    echo number_format($stmt->fetch(PDO::FETCH_ASSOC)['count']);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages List -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>Nomor</th>
                                <th>Device</th>
                                <th>Pesan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <br>Tidak ada pesan ditemukan
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <tr class="<?= $message['from_me'] === '1' ? 'message-outgoing' : 'message-incoming' ?>">
                                        <td>
                                            <div class="message-time">
                                                <?= date('d/m/Y H:i', strtotime($message['tanggal'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($message['from_me'] === '1'): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-arrow-up"></i> Keluar
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-arrow-down"></i> Masuk
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($message['nomor']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($message['device_name']): ?>
                                                <span class="badge bg-secondary device-badge">
                                                    <?= htmlspecialchars($message['device_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="message-content">
                                                <?php if (strlen($message['pesan']) > 100): ?>
                                                    <?= htmlspecialchars(substr($message['pesan'], 0, 100)) ?>...
                                                    <button class="btn btn-sm btn-link" onclick="showFullMessage('<?= htmlspecialchars($message['pesan']) ?>')">
                                                        Lihat Selengkapnya
                                                    </button>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($message['pesan']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="replyMessage('<?= htmlspecialchars($message['nomor']) ?>')">
                                                    <i class="fas fa-reply"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="showMessageDetails(<?= $message['id'] ?>)">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&nomor=<?= urlencode($filter_nomor) ?>&type=<?= urlencode($filter_type) ?>&date=<?= urlencode($filter_date) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&nomor=<?= urlencode($filter_nomor) ?>&type=<?= urlencode($filter_type) ?>&date=<?= urlencode($filter_date) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&nomor=<?= urlencode($filter_nomor) ?>&type=<?= urlencode($filter_type) ?>&date=<?= urlencode($filter_date) ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal for full message -->
    <div class="modal fade" id="fullMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pesan Lengkap</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="fullMessageText"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for message details -->
    <div class="modal fade" id="messageDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

   
    <script>
        function showFullMessage(message) {
            document.getElementById('fullMessageText').textContent = message;
            new bootstrap.Modal(document.getElementById('fullMessageModal')).show();
        }

        function replyMessage(nomor) {
            // Redirect to send message page with pre-filled number
            window.location.href = `../module/kirim-pesan.php?nomor=${encodeURIComponent(nomor)}`;
        }

        function showMessageDetails(messageId) {
            // Load message details via AJAX
            fetch(`get_message_details.php?id=${messageId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('messageDetailsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('messageDetailsModal')).show();
                });
        }

        function exportToCSV() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            window.location.href = `export_csv.php?${params.toString()}`;
        }
    </script>
