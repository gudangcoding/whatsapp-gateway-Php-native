<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../admin/helper/koneksi.php';

// Midtrans Configuration
$midtrans_server_key = 'YOUR-MIDTRANS-SERVER-KEY';
$midtrans_client_key = 'YOUR-MIDTRANS-CLIENT-KEY';
$midtrans_merchant_id = 'YOUR-MIDTRANS-MERCHANT-ID';

// Set to true for production
$is_production = false;

$base_url = $is_production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

function generateSnapToken($transaction_details) {
    global $base_url, $midtrans_server_key;
    
    $url = $base_url . '/v2/charge';
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($midtrans_server_key . ':')
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_details));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        return $result['token'] ?? null;
    }
    
    return null;
}

function handlePaymentNotification() {
    global $conn;
    
    $json_result = file_get_contents('php://input');
    $result = json_decode($json_result, true);
    
    if (!$result) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    $order_id = $result['order_id'] ?? '';
    $transaction_status = $result['transaction_status'] ?? '';
    $fraud_status = $result['fraud_status'] ?? '';
    $payment_type = $result['payment_type'] ?? '';
    $gross_amount = $result['gross_amount'] ?? '';
    
    // Verify signature key
    $signature_key = $result['signature_key'] ?? '';
    $expected_signature = hash('sha512', $order_id . $result['status_code'] . $gross_amount . 'YOUR-MIDTRANS-SERVER-KEY');
    
    if ($signature_key !== $expected_signature) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        return;
    }
    
    // Update payment status in database
    $stmt = $conn->prepare("UPDATE payments SET 
        transaction_status = ?, 
        fraud_status = ?, 
        payment_type = ?,
        updated_at = NOW()
        WHERE order_id = ?");
    
    $stmt->bind_param('ssss', $transaction_status, $fraud_status, $payment_type, $order_id);
    
    if ($stmt->execute()) {
        // If payment is successful, activate user account
        if ($transaction_status === 'capture' || $transaction_status === 'settlement') {
            activateUserAccount($order_id);
        }
        
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database update failed']);
    }
    
    $stmt->close();
}

function activateUserAccount($order_id) {
    global $conn;
    
    // Get payment details
    $stmt = $conn->prepare("SELECT user_id, package_type FROM payments WHERE order_id = ?");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $package_type = $row['package_type'];
        
        // Update user status to active
        $update_user = $conn->prepare("UPDATE users SET 
            status = 'active', 
            package_type = ?,
            activated_at = NOW()
            WHERE id = ?");
        
        $update_user->bind_param('si', $package_type, $user_id);
        $update_user->execute();
        $update_user->close();
        
        // Send welcome email
        sendWelcomeEmail($user_id);
    }
    
    $stmt->close();
}

function sendWelcomeEmail($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $to = $row['email'];
        $name = $row['full_name'];
        
        $subject = "Selamat Datang di WhatsApp Gateway!";
        $message = "
        <html>
        <body>
            <h2>Selamat Datang di WhatsApp Gateway!</h2>
            <p>Halo {$name},</p>
            <p>Terima kasih telah mendaftar di WhatsApp Gateway. Akun Anda telah berhasil diaktifkan.</p>
            <p>Anda sekarang dapat:</p>
            <ul>
                <li>Login ke dashboard admin</li>
                <li>Setup device WhatsApp Anda</li>
                <li>Mulai kirim pesan otomatis</li>
                <li>Menggunakan fitur auto-reply</li>
            </ul>
            <p>Jika ada pertanyaan, silakan hubungi support kami.</p>
            <p>Best regards,<br>Tim WhatsApp Gateway</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: support@wagateway.com" . "\r\n";
        
        mail($to, $subject, $message, $headers);
    }
    
    $stmt->close();
}

// Handle different API endpoints
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create_token':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            break;
        }
        
        // Validate required fields
        $required_fields = ['order_id', 'amount', 'customer_name', 'customer_email'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                break 2;
            }
        }
        
        // Create transaction details for Midtrans
        $transaction_details = [
            'transaction_details' => [
                'order_id' => $input['order_id'],
                'gross_amount' => (int)$input['amount']
            ],
            'customer_details' => [
                'first_name' => $input['customer_name'],
                'email' => $input['customer_email'],
                'phone' => $input['customer_phone'] ?? ''
            ],
            'item_details' => [
                [
                    'id' => $input['package_type'] ?? 'wa_gateway',
                    'price' => (int)$input['amount'],
                    'quantity' => 1,
                    'name' => 'WhatsApp Gateway Subscription'
                ]
            ],
            'callbacks' => [
                'finish' => 'https://yourdomain.com/payment-success.php',
                'error' => 'https://yourdomain.com/payment-error.php',
                'pending' => 'https://yourdomain.com/payment-pending.php'
            ]
        ];
        
        // Generate Snap token
        $snap_token = generateSnapToken($transaction_details);
        
        if ($snap_token) {
            // Save payment record to database
            $stmt = $conn->prepare("INSERT INTO payments (
                order_id, user_id, amount, package_type, snap_token, status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            
            $user_id = $input['user_id'] ?? 0;
            $package_type = $input['package_type'] ?? 'starter';
            
            $stmt->bind_param('sidsis', 
                $input['order_id'], 
                $user_id, 
                $input['amount'], 
                $package_type, 
                $snap_token
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'snap_token' => $snap_token,
                    'client_key' => $midtrans_client_key
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save payment record']);
            }
            
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to generate Snap token']);
        }
        break;
        
    case 'notification':
        handlePaymentNotification();
        break;
        
    case 'status':
        $order_id = $_GET['order_id'] ?? '';
        
        if (empty($order_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID required']);
            break;
        }
        
        $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
        $stmt->bind_param('s', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
        }
        
        $stmt->close();
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

$conn->close();
?> 