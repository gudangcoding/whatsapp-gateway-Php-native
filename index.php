<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Gateway - Solusi Bisnis WhatsApp Terdepan</title>
    <meta name="description" content="WhatsApp Gateway terbaik untuk bisnis Anda. Kirim pesan otomatis, auto-reply, dan integrasi WhatsApp API dengan mudah.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-whatsapp me-2"></i>WA Gateway
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Harga</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="admin/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="display-4 fw-bold text-primary mb-4">
                        WhatsApp Gateway untuk Bisnis Anda
                    </h1>
                    <p class="lead mb-4">
                        Solusi lengkap untuk mengelola WhatsApp bisnis Anda. Kirim pesan otomatis, 
                        auto-reply, dan integrasi API dengan mudah. Tingkatkan efisiensi bisnis Anda sekarang!
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#pricing" class="btn btn-primary btn-lg">
                            <i class="bi bi-cart me-2"></i>Mulai Sekarang
                        </a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-play-circle me-2"></i>Lihat Demo
                        </a>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <img src="assets/images/hero-image.png" alt="WhatsApp Gateway" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold text-primary mb-3" data-aos="fade-up">Fitur Unggulan</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Nikmati berbagai fitur canggih untuk mengoptimalkan bisnis WhatsApp Anda
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-send fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Kirim Pesan Otomatis</h5>
                            <p class="card-text text-muted">
                                Kirim pesan ke ribuan nomor secara otomatis dengan jadwal yang dapat disesuaikan.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-reply-all fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Auto Reply Cerdas</h5>
                            <p class="card-text text-muted">
                                Balas pesan masuk secara otomatis dengan kata kunci yang dapat dikustomisasi.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-info text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-code-slash fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">API Integration</h5>
                            <p class="card-text text-muted">
                                Integrasikan dengan sistem Anda melalui API yang mudah digunakan.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-warning text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-clock-history fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Pesan Terjadwal</h5>
                            <p class="card-text text-muted">
                                Atur jadwal pengiriman pesan untuk waktu yang tepat dan efisien.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-danger text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-chat-dots fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Riwayat Chat</h5>
                            <p class="card-text text-muted">
                                Simpan dan kelola semua riwayat percakapan dengan mudah.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="700">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-secondary text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-shield-check fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Keamanan Terjamin</h5>
                            <p class="card-text text-muted">
                                Data Anda aman dengan sistem keamanan tingkat tinggi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold text-primary mb-3" data-aos="fade-up">Pilihan Paket</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Pilih paket yang sesuai dengan kebutuhan bisnis Anda
                    </p>
                </div>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card pricing-card h-100 border-0 shadow">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h5 class="card-title fw-bold text-primary">Starter</h5>
                                <div class="price">
                                    <span class="currency">Rp</span>
                                    <span class="amount">99.000</span>
                                    <span class="period">/bulan</span>
                                </div>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1 Device WhatsApp</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1000 Pesan/bulan</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Auto Reply</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Pesan Terjadwal</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Riwayat Chat</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Email Support</li>
                            </ul>
                            <div class="text-center mt-4">
                                <a href="register.php?plan=starter" class="btn btn-outline-primary w-100">Pilih Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card pricing-card h-100 border-0 shadow-lg position-relative">
                        <div class="position-absolute top-0 start-50 translate-middle">
                            <span class="badge bg-danger px-3 py-2">Terpopuler</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h5 class="card-title fw-bold text-primary">Business</h5>
                                <div class="price">
                                    <span class="currency">Rp</span>
                                    <span class="amount">199.000</span>
                                    <span class="period">/bulan</span>
                                </div>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>3 Device WhatsApp</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>5000 Pesan/bulan</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Auto Reply</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Pesan Terjadwal</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Riwayat Chat</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>API Access</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Priority Support</li>
                            </ul>
                            <div class="text-center mt-4">
                                <a href="register.php?plan=business" class="btn btn-primary w-100">Pilih Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="card pricing-card h-100 border-0 shadow">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <h5 class="card-title fw-bold text-primary">Enterprise</h5>
                                <div class="price">
                                    <span class="currency">Rp</span>
                                    <span class="amount">499.000</span>
                                    <span class="period">/bulan</span>
                                </div>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>10 Device WhatsApp</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Unlimited Pesan</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Auto Reply</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Pesan Terjadwal</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Riwayat Chat</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>API Access</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>24/7 Support</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Custom Integration</li>
                            </ul>
                            <div class="text-center mt-4">
                                <a href="register.php?plan=enterprise" class="btn btn-outline-primary w-100">Pilih Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold text-primary mb-3" data-aos="fade-up">Hubungi Kami</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Ada pertanyaan? Tim support kami siap membantu Anda
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold mb-4">Kirim Pesan</h5>
                            <form id="contactForm">
                                <div class="mb-3">
                                    <input type="text" class="form-control" placeholder="Nama Lengkap" required>
                                </div>
                                <div class="mb-3">
                                    <input type="email" class="form-control" placeholder="Email" required>
                                </div>
                                <div class="mb-3">
                                    <input type="tel" class="form-control" placeholder="Nomor WhatsApp" required>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" rows="4" placeholder="Pesan" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold mb-4">Informasi Kontak</h5>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-whatsapp text-success fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">WhatsApp</h6>
                                    <p class="text-muted mb-0">+62 812-3456-7890</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-envelope text-primary fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Email</h6>
                                    <p class="text-muted mb-0">support@wagateway.com</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-geo-alt text-danger fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Alamat</h6>
                                    <p class="text-muted mb-0">Jakarta, Indonesia</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock text-warning fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Jam Kerja</h6>
                                    <p class="text-muted mb-0">Senin - Jumat: 09:00 - 17:00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 WhatsApp Gateway. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
