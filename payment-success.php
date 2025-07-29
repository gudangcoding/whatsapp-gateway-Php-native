<?php
session_start();

$orderId = $_GET['order_id'] ?? '';
$transactionStatus = $_GET['transaction_status'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - WhatsApp Gateway</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-whatsapp me-2"></i>WA Gateway
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow text-center">
                    <div class="card-body p-5">
                        <?php if ($transactionStatus === 'success'): ?>
                            <!-- Success State -->
                            <div class="mb-4">
                                <div class="success-icon bg-success text-white rounded-circle mx-auto mb-3">
                                    <i class="bi bi-check-lg fs-1"></i>
                                </div>
                                <h2 class="fw-bold text-success mb-3">Pembayaran Berhasil!</h2>
                                <p class="text-muted mb-4">
                                    Terima kasih telah mendaftar di WhatsApp Gateway. 
                                    Akun Anda akan segera diaktifkan dalam waktu 1x24 jam.
                                </p>
                            </div>

                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-info-circle me-2 mt-1"></i>
                                    <div>
                                        <strong>Order ID:</strong> <?php echo htmlspecialchars($orderId); ?><br>
                                        <small class="text-muted">
                                            Simpan Order ID ini untuk referensi Anda
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="row text-start mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">Langkah Selanjutnya:</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="bi bi-1-circle-fill text-primary me-2"></i>
                                            Cek email Anda untuk detail akun
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-2-circle-fill text-primary me-2"></i>
                                            Login ke dashboard admin
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-3-circle-fill text-primary me-2"></i>
                                            Setup device WhatsApp Anda
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-4-circle-fill text-primary me-2"></i>
                                            Mulai kirim pesan otomatis
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">Dukungan:</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="bi bi-whatsapp text-success me-2"></i>
                                            WhatsApp: +62 812-3456-7890
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-envelope text-primary me-2"></i>
                                            Email: support@wagateway.com
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-book text-info me-2"></i>
                                            <a href="#" class="text-decoration-none">Dokumentasi</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="admin/login.php" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login ke Dashboard
                                </a>
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="bi bi-house me-2"></i>Kembali ke Beranda
                                </a>
                            </div>

                        <?php elseif ($transactionStatus === 'pending'): ?>
                            <!-- Pending State -->
                            <div class="mb-4">
                                <div class="success-icon bg-warning text-white rounded-circle mx-auto mb-3">
                                    <i class="bi bi-clock fs-1"></i>
                                </div>
                                <h2 class="fw-bold text-warning mb-3">Pembayaran Pending</h2>
                                <p class="text-muted mb-4">
                                    Pembayaran Anda sedang diproses. 
                                    Mohon tunggu konfirmasi dari tim kami.
                                </p>
                            </div>

                            <div class="alert alert-warning mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                    <div>
                                        <strong>Order ID:</strong> <?php echo htmlspecialchars($orderId); ?><br>
                                        <small class="text-muted">
                                            Kami akan menghubungi Anda segera setelah pembayaran dikonfirmasi
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="bi bi-house me-2"></i>Kembali ke Beranda
                                </a>
                            </div>

                        <?php else: ?>
                            <!-- Error State -->
                            <div class="mb-4">
                                <div class="success-icon bg-danger text-white rounded-circle mx-auto mb-3">
                                    <i class="bi bi-x-lg fs-1"></i>
                                </div>
                                <h2 class="fw-bold text-danger mb-3">Pembayaran Gagal</h2>
                                <p class="text-muted mb-4">
                                    Maaf, pembayaran Anda tidak dapat diproses. 
                                    Silakan coba lagi atau hubungi support kami.
                                </p>
                            </div>

                            <div class="alert alert-danger mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                    <div>
                                        <strong>Order ID:</strong> <?php echo htmlspecialchars($orderId); ?><br>
                                        <small class="text-muted">
                                            Jika Anda yakin telah melakukan pembayaran, 
                                            silakan hubungi support kami
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="register.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Coba Lagi
                                </a>
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="bi bi-house me-2"></i>Kembali ke Beranda
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 