<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard WA Gateway</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
</head>
<body class="bg-light">
    <div class="container-fluid min-vh-100">
        <div class="row min-vh-100">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-white sidebar shadow-sm p-0">
                <div class="position-sticky h-100 d-flex flex-column">
                    <div class="p-4 border-bottom">
                        <h2 class="h4 text-success fw-bold mb-0">WA Gateway</h2>
                    </div>
                    <ul class="nav flex-column py-3">
                        <li class="nav-item mb-1">
                            <a href="?m=home" class="nav-link active text-success fw-semibold">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a href="?m=kirim-pesan" class="nav-link text-dark fw-semibold">
                                <i class="bi bi-send me-2 text-primary"></i> Kirim Pesan
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a href="?m=auto-reply" class="nav-link text-dark fw-semibold">
                                <i class="bi bi-reply-all me-2 text-warning"></i> Auto Reply
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a href="?m=pesan-terjadwal" class="nav-link text-dark fw-semibold">
                                <i class="bi bi-clock-history me-2 text-purple"></i> Pesan Terjadwal
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a href="?m=riwayat" class="nav-link text-dark fw-semibold">
                                <i class="bi bi-chat-dots me-2 text-info"></i> Riwayat Pesan
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a href="logout.php" class="nav-link text-danger fw-semibold">
                                <i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout
                            </a>
                        </li>
                        <!-- Tambahkan menu lain di sini jika perlu -->
                    </ul>
                    <div class="mt-auto p-4 border-top text-muted small">
                        &copy; <?php echo date('Y'); ?> WA Gateway
                    </div>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">
                <?php
                include "helper/koneksi.php";
                $m = @$_GET['m'];
                $a = @$_GET['a'];
                if ($m == '') {
                    include "module/home/index.php";
                } else {
                    if ($a == '') {
                        include "module/$m/index.php";
                    } else {
                        include "module/$m/$a.php";
                    }
                }
                ?>
            </main>
        </div>
    </div>
    <!-- Bootstrap JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS CDN -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- Optionally add Bootstrap Icons CDN for icons used above -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>
<?php
$conn->close();
?>
?>
