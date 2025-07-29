<?php
session_start();

// Konfigurasi paket
$packages = [
    'starter' => [
        'name' => 'Starter',
        'price' => 99000,
        'devices' => 1,
        'messages' => 1000,
        'features' => ['Auto Reply', 'Pesan Terjadwal', 'Riwayat Chat', 'Email Support']
    ],
    'business' => [
        'name' => 'Business',
        'price' => 199000,
        'devices' => 3,
        'messages' => 5000,
        'features' => ['Auto Reply', 'Pesan Terjadwal', 'Riwayat Chat', 'API Access', 'Priority Support']
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 499000,
        'devices' => 10,
        'messages' => 'Unlimited',
        'features' => ['Auto Reply', 'Pesan Terjadwal', 'Riwayat Chat', 'API Access', '24/7 Support', 'Custom Integration']
    ]
];

$selectedPlan = $_GET['plan'] ?? 'business';
$package = $packages[$selectedPlan] ?? $packages['business'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - WhatsApp Gateway</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Midtrans JS -->
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="YOUR-MIDTRANS-CLIENT-KEY"></script>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-whatsapp me-2"></i>WA Gateway
            </a>
            <a href="index.php" class="btn btn-outline-light">Kembali ke Beranda</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Daftar Akun</h2>
                            <p class="text-muted">Lengkapi data diri Anda untuk memulai</p>
                        </div>

                        <!-- Package Summary -->
                        <div class="alert alert-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle me-2"></i>
                                <div>
                                    <strong>Paket <?php echo $package['name']; ?></strong><br>
                                    <small class="text-muted">
                                        Rp <?php echo number_format($package['price']); ?> /bulan
                                    </small>
                                </div>
                                <a href="index.php#pricing" class="btn btn-sm btn-outline-primary ms-auto">Ubah Paket</a>
                            </div>
                        </div>

                        <form id="registerForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fullName" class="form-label">Nama Lengkap *</label>
                                        <input type="text" class="form-control" id="fullName" name="fullName" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Nomor WhatsApp *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="6281234567890" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company" class="form-label">Nama Perusahaan</label>
                                        <input type="text" class="form-control" id="company" name="company">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Saya setuju dengan <a href="#" class="text-primary">Syarat dan Ketentuan</a> serta <a href="#" class="text-primary">Kebijakan Privasi</a>
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-credit-card me-2"></i>Lanjut ke Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Package Details -->
            <div class="col-lg-4">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold text-primary mb-3">Detail Paket <?php echo $package['name']; ?></h5>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Device WhatsApp:</span>
                                <span class="fw-bold"><?php echo $package['devices']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Pesan/bulan:</span>
                                <span class="fw-bold"><?php echo $package['messages']; ?></span>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3">Fitur yang Didapat:</h6>
                        <ul class="list-unstyled">
                            <?php foreach ($package['features'] as $feature): ?>
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <?php echo $feature; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <hr>

                        <div class="text-center">
                            <div class="h4 fw-bold text-primary mb-2">
                                Rp <?php echo number_format($package['price']); ?>
                            </div>
                            <small class="text-muted">per bulan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validasi form
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Simulasi data untuk Midtrans
            const transactionDetails = {
                transaction_details: {
                    order_id: 'WA-GATEWAY-' + Date.now(),
                    gross_amount: <?php echo $package['price']; ?>
                },
                customer_details: {
                    first_name: data.fullName,
                    email: data.email,
                    phone: data.phone
                },
                item_details: [{
                    id: '<?php echo $selectedPlan; ?>',
                    price: <?php echo $package['price']; ?>,
                    quantity: 1,
                    name: 'Paket <?php echo $package['name']; ?> - WhatsApp Gateway'
                }],
                callbacks: {
                    finish: function(result) {
                        // Redirect ke halaman sukses
                        window.location.href = 'payment-success.php?order_id=' + result.order_id;
                    }
                }
            };

            // Trigger Midtrans Snap
            snap.pay('<?php echo generateSnapToken($transactionDetails); ?>', {
                onSuccess: function(result) {
                    console.log('Payment success:', result);
                },
                onPending: function(result) {
                    console.log('Payment pending:', result);
                },
                onError: function(result) {
                    console.log('Payment error:', result);
                },
                onClose: function() {
                    console.log('Customer closed the popup without finishing payment');
                }
            });
        });

        // Format nomor telepon
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = '62' + value.substring(1);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>

<?php
function generateSnapToken($transactionDetails) {
    // Implementasi generate Snap Token dari Midtrans
    // Ini adalah contoh, Anda perlu mengimplementasikan sesuai dengan dokumentasi Midtrans
    
    $serverKey = 'YOUR-MIDTRANS-SERVER-KEY';
    $clientKey = 'YOUR-MIDTRANS-CLIENT-KEY';
    
    // Untuk demo, kita return token dummy
    // Dalam implementasi nyata, Anda perlu call Midtrans API
    return 'dummy-snap-token-' . time();
}
?> 