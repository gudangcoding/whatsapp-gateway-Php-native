<?php
// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if ($full_name && $email) {
        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $user_id]);
            $success = true;
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui profil: ' . $e->getMessage();
        }
    } else {
        $error = 'Nama dan email wajib diisi!';
    }
}

// Ambil data user
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<script>window.location.href='../../login.php';</script>";
        exit();
    }
} catch (PDOException $e) {
    $error = 'Gagal mengambil data user: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - WhatsApp Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-card {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-save {
            min-width: 120px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card settings-card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-cog"></i> Pengaturan Akun
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> Profil berhasil diperbarui!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user"></i> Username
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                           readonly>
                                    <div class="form-text">Username tidak dapat diubah</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="package_type" class="form-label">
                                        <i class="fas fa-crown"></i> Paket
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="package_type" 
                                           value="<?= htmlspecialchars(ucfirst($user['package_type'] ?? '')) ?>" 
                                           readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">
                                    <i class="fas fa-id-card"></i> Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Nama lengkap wajib diisi
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Email yang valid wajib diisi
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> No. HP
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                       placeholder="08xxxxxxxxxx">
                                <div class="form-text">Opsional</div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-circle"></i> Status Akun
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="status" 
                                       value="<?= htmlspecialchars(ucfirst($user['status'] ?? '')) ?>" 
                                       readonly>
                            </div>

                            <div class="mb-3">
                                <label for="created_at" class="form-label">
                                    <i class="fas fa-calendar"></i> Tanggal Daftar
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="created_at" 
                                       value="<?= date('d/m/Y H:i', strtotime($user['created_at'] ?? 'now')) ?>" 
                                       readonly>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="../index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary btn-save">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Informasi Tambahan -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i> Informasi
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-shield-alt text-success"></i> Data Anda aman dan terenkripsi</li>
                            <li><i class="fas fa-clock text-warning"></i> Perubahan akan langsung tersimpan</li>
                            <li><i class="fas fa-question-circle text-info"></i> Hubungi admin untuk bantuan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
