<?php
// Cek login dan hak akses admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../../login.php');
    exit();
}

include "../../helper/database.php";

$success = '';
$error = '';

// Handle form tambah/edit payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $account = trim($_POST['account'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name && $type && $account) {
        try {
            if ($id) {
                // Edit
                $stmt = $db->prepare("UPDATE payments SET name=?, type=?, account=?, status=? WHERE id=?");
                $stmt->execute([$name, $type, $account, $status, $id]);
                $success = "Payment method updated successfully.";
            } else {
                // Tambah
                $stmt = $db->prepare("INSERT INTO payments (name, type, account, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $type, $account, $status]);
                $success = "Payment method added successfully.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "All fields are required!";
    }
}

// Handle hapus
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM payments WHERE id=?");
        $stmt->execute([$delete_id]);
        $success = "Payment method deleted successfully.";
    } catch (PDOException $e) {
        $error = "Failed to delete: " . $e->getMessage();
    }
}

// Ambil data payment methods
$payments = [];
try {
    $stmt = $db->query("SELECT * FROM payments ORDER BY id DESC");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch payment methods: " . $e->getMessage();
}

// Untuk edit
$edit_payment = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM payments WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_payment = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-credit-card text-warning"></i> Payment Management</h2>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <?= $edit_payment ? 'Edit Payment Method' : 'Add Payment Method' ?>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?php if ($edit_payment): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_payment['id']) ?>">
                <?php endif; ?>
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required
                        value="<?= htmlspecialchars($edit_payment['name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="">-- Select --</option>
                        <option value="Bank" <?= (isset($edit_payment['type']) && $edit_payment['type']=='Bank') ? 'selected' : '' ?>>Bank</option>
                        <option value="E-Wallet" <?= (isset($edit_payment['type']) && $edit_payment['type']=='E-Wallet') ? 'selected' : '' ?>>E-Wallet</option>
                        <option value="Other" <?= (isset($edit_payment['type']) && $edit_payment['type']=='Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Account/Number</label>
                    <input type="text" name="account" class="form-control" required
                        value="<?= htmlspecialchars($edit_payment['account'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= (isset($edit_payment['status']) && $edit_payment['status']=='active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (isset($edit_payment['status']) && $edit_payment['status']=='inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?= $edit_payment ? 'Update' : 'Add' ?>
                    </button>
                    <?php if ($edit_payment): ?>
                        <a href="index.php?m=payments" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            Payment Methods List
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Account/Number</th>
                        <th>Status</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No payment methods found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $i => $p): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['type']) ?></td>
                                <td><?= htmlspecialchars($p['account']) ?></td>
                                <td>
                                    <?php if ($p['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?m=payments&edit=<?= $p['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?m=payments&delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment method?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
