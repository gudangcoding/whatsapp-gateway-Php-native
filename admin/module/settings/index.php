<?php
// session_start();
// require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter
$where = [];
$params = [];
if (!$is_admin) {
    $where[] = "p.user_id = ?";
    $params[] = $user_id;
}
$where_clause = $where ? "WHERE " . implode(' AND ', $where) : "";

// Total
$stmt = $db->prepare("SELECT COUNT(*) as total FROM payments p $where_clause");
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $limit);

// Data
$stmt = $db->prepare("SELECT p.*, u.username FROM payments p LEFT JOIN users u ON p.user_id = u.id $where_clause ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Riwayat Pembayaran</h2>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <?php if ($is_admin): ?><th>User</th><?php endif; ?>
                <th>Nominal</th>
                <th>Status</th>
                <th>Metode</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <?php if ($is_admin): ?><td><?= htmlspecialchars($p['username']) ?></td><?php endif; ?>
                <td>Rp<?= number_format($p['amount']) ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td><?= htmlspecialchars($p['method']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
</body>
</html>
