<?php
session_start();
require_once 'helper/koneksi.php';

// Jika sudah login, redirect ke index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Hash password dengan SHA1
        $password_hash = sha1($password);

        // Cek di tabel users (pelanggan)
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND status = 'active'");
        $stmt->bind_param('ss', $username, $password_hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Set session untuk user
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['package_type'] = $row['package_type'];
            $_SESSION['user_type'] = 'customer';
            
            // Log activity
            logUserActivity($row['id'], 'login', 'User logged in successfully');
            
            header('Location: index.php');
            exit;
        } else {
            // Cek di tabel account (admin/CS)
            // Sesuaikan query dengan struktur tabel 'account' Anda, misal kolomnya 'user_name', 'user_pass', dan 'is_active'
            $stmt = $conn->prepare("SELECT id, username, status FROM users WHERE username = ? AND password = ? AND status = 1");
            $stmt->bind_param('ss', $username, $password_hash);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Set session untuk admin
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['level'] = $row['level'];
                $_SESSION['user_type'] = 'admin';
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah, atau akun belum diaktifkan.';
            }
        }
        $stmt->close();
    }
}

// Function untuk log aktivitas user
function logUserActivity($user_id, $action, $description) {
    global $conn;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $user_id, $action, $description, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - WA Gateway</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px 30px;
        }
        .login-title {
            font-weight: bold;
            color: #25D366;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #25D366;
            box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
        }
        .btn-login {
            background: #25D366;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: #128C7E;
            transform: translateY(-2px);
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo i {
            font-size: 3rem;
            color: #25D366;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-whatsapp"></i>
        </div>
        <h2 class="login-title">Login WA Gateway</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-login text-white w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="../index.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
            </a>
        </div>
        <div class="mt-3 text-center text-secondary" style="font-size: 0.9em;">
            &copy; <?php echo date('Y'); ?> WA Gateway. All rights reserved.
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
