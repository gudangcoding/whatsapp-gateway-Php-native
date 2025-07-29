<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'customer';
$is_admin = ($user_type === 'admin');
$user_level = $_SESSION['level'] ?? '';

// Get user details for customers
$user_info = null;
if (!$is_admin) {
    require_once 'helper/koneksi.php';
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
    $stmt->close();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- DataTables CSS CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">
    <div class="container-fluid min-vh-100">
        <div class="row min-vh-100">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-white sidebar shadow-sm p-0" id="mainSidebar">
                <div class="position-sticky h-100 d-flex flex-column">
                    <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="h4 text-success fw-bold mb-0">WA Gateway</h2>
                            <?php if (!$is_admin && $user_info): ?>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($user_info['full_name']); ?> 
                                    (<?php echo ucfirst($user_info['package_type']); ?>)
                                </small>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm d-none d-lg-block" id="toggleMainSidebar" title="Sembunyikan sidebar">
                            <i class="bi bi-chevron-left"></i>
                        </button>
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
                            <a href="?m=chat" class="nav-link text-dark fw-semibold">
                                <i class="bi bi-chat-dots me-2 text-info"></i> Chat
                            </a>
                        </li>
                        
                        <?php if ($is_admin): ?>
                            <!-- Admin Menu -->
                            <li class="nav-item mb-1">
                                <a href="?m=users" class="nav-link text-dark fw-semibold">
                                    <i class="bi bi-people me-2 text-success"></i> User Management
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="?m=payments" class="nav-link text-dark fw-semibold">
                                    <i class="bi bi-credit-card me-2 text-warning"></i> Payment Management
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="?m=settings" class="nav-link text-dark fw-semibold">
                                    <i class="bi bi-gear me-2 text-secondary"></i> System Settings
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- Customer Menu -->
                            <li class="nav-item mb-1">
                                <a href="?m=profile" class="nav-link text-dark fw-semibold">
                                    <i class="bi bi-person me-2 text-info"></i> Profile
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="?m=api-keys" class="nav-link text-dark fw-semibold">
                                    <i class="bi bi-key me-2 text-warning"></i> API Keys
                                </a>
                            </li>
                            <li class="nav-item mb-1">
                                <a href="?m=subscription" class="nav-link text-dark fw-semibold">
                                    <i class="bi bi-credit-card me-2 text-success"></i> Subscription
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item mb-1">
                            <a href="logout.php" class="nav-link text-danger fw-semibold">
                                <i class="bi bi-box-arrow-right me-2 text-danger"></i> Logout
                            </a>
                        </li>
                    </ul>
                    <div class="mt-auto p-4 border-top text-muted small">
                        &copy; <?php echo date('Y'); ?> WA Gateway
                    </div>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4" id="mainContent">
                <!-- Toggle Sidebar Button for Mobile -->
                <div class="d-lg-none mb-3">
                    <button class="btn btn-outline-secondary" id="showMainSidebarMobile" title="Tampilkan menu">
                        <i class="bi bi-list"></i> Menu
                    </button>
                </div>
                
                <?php
                include "helper/database.php";
                $m = @$_GET['m'];
                $a = @$_GET['a'];
                
                // Set default module based on user type
                if ($m == '') {
                    if ($is_admin) {
                        include "module/home/index.php";
                    } else {
                        include "module/home/index.php";
                    }
                } else {
                    // Check if user has access to the module
                    $allowed_modules = [];
                    
                    if ($is_admin) {
                        $allowed_modules = ['home', 'kirim-pesan', 'auto-reply', 'pesan-terjadwal', 'riwayat', 'chat', 'users', 'payments', 'settings', 'admin-dashboard'];
                    } else {
                        $allowed_modules = ['home', 'kirim-pesan', 'auto-reply', 'pesan-terjadwal', 'riwayat', 'chat', 'profile', 'api-keys', 'subscription'];
                    }
                    
                    if (in_array($m, $allowed_modules)) {
                        if ($a == '') {
                            include "module/$m/index.php";
                        } else {
                            include "module/$m/$a.php";
                        }
                    } else {
                        echo '<div class="alert alert-danger">Akses ditolak. Modul tidak ditemukan atau Anda tidak memiliki izin.</div>';
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
    
    <script>
    let mainSidebarCollapsed = false;
    
    document.addEventListener('DOMContentLoaded', function() {
        const mainSidebar = document.getElementById('mainSidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleMainSidebar = document.getElementById('toggleMainSidebar');
        const showMainSidebarMobile = document.getElementById('showMainSidebarMobile');
        
        // Toggle main sidebar (Desktop)
        if (toggleMainSidebar) {
            toggleMainSidebar.addEventListener('click', function() {
                mainSidebarCollapsed = true;
                mainSidebar.style.display = 'none';
                mainContent.classList.remove('col-md-9', 'ms-sm-auto', 'col-lg-10');
                mainContent.classList.add('col-12');
                
                // Show toggle button in content area
                const showButton = document.createElement('button');
                showButton.className = 'btn btn-outline-secondary btn-sm position-fixed';
                showButton.style.cssText = 'top: 20px; left: 20px; z-index: 1000;';
                showButton.innerHTML = '<i class="bi bi-chevron-right"></i>';
                showButton.title = 'Tampilkan sidebar';
                showButton.id = 'showMainSidebar';
                showButton.addEventListener('click', showMainSidebar);
                document.body.appendChild(showButton);
            });
        }
        
        // Show main sidebar (Desktop)
        function showMainSidebar() {
            mainSidebarCollapsed = false;
            mainSidebar.style.display = 'block';
            mainContent.classList.remove('col-12');
            mainContent.classList.add('col-md-9', 'ms-sm-auto', 'col-lg-10');
            
            // Remove show button
            const showButton = document.getElementById('showMainSidebar');
            if (showButton) {
                showButton.remove();
            }
        }
        
        // Mobile sidebar toggle
        if (showMainSidebarMobile) {
            showMainSidebarMobile.addEventListener('click', function() {
                if (mainSidebarCollapsed) {
                    mainSidebarCollapsed = false;
                    mainSidebar.style.display = 'block';
                    showMainSidebarMobile.style.display = 'none';
                } else {
                    mainSidebarCollapsed = true;
                    mainSidebar.style.display = 'none';
                    showMainSidebarMobile.style.display = 'block';
                }
            });
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) { // lg breakpoint
                // On desktop, show sidebar by default if not manually collapsed
                if (!mainSidebarCollapsed) {
                    mainSidebar.style.display = 'block';
                    if (showMainSidebarMobile) {
                        showMainSidebarMobile.style.display = 'none';
                    }
                }
            } else {
                // On mobile, hide sidebar by default
                if (!mainSidebarCollapsed) {
                    mainSidebar.style.display = 'none';
                    if (showMainSidebarMobile) {
                        showMainSidebarMobile.style.display = 'block';
                    }
                    mainSidebarCollapsed = true;
                }
            }
        });
        
        // Initialize mobile behavior
        if (window.innerWidth < 992) {
            mainSidebar.style.display = 'none';
            if (showMainSidebarMobile) {
                showMainSidebarMobile.style.display = 'block';
            }
            mainSidebarCollapsed = true;
        }
    });
    </script>
    
    <style>
    .sidebar {
        transition: all 0.3s ease-in-out;
    }
    
    #mainContent {
        transition: all 0.3s ease-in-out;
    }
    
    /* Mobile sidebar overlay */
    @media (max-width: 991.98px) {
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            height: 100vh;
            width: 280px;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        #mainContent {
            margin-left: 0 !important;
            width: 100%;
        }
    }
    
    /* Smooth transitions */
    .sidebar, #mainContent {
        transition: all 0.3s ease-in-out;
    }
    
    /* Fixed button styling */
    .position-fixed {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    </style>
</body>
</html>
<?php
$conn->close();
?>
