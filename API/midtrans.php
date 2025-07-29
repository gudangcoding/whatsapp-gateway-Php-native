<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../admin/helper/koneksi.php';
require_once '../config/midtrans.php';

$midtrans_server_key = MIDTRANS_SERVER_KEY;
$midtrans_client_key = MIDTRANS_CLIENT_KEY;
$midtrans_merchant_id = MIDTRANS_MERCHANT_ID;
$is_production = MIDTRANS_PRODUCTION;
$base_url = $is_production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

function debug_log($msg) {
    file_put_contents(__DIR__ . '/midtrans_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

function generateSnapToken($transaction_details) {
    global $base_url, $midtrans_server_key;

    if ($midtrans_server_key === 'YOUR-MIDTRANS-SERVER-KEY-SANDBOX' ||
        $midtrans_server_key === 'YOUR-MIDTRANS-SERVER-KEY-PRODUCTION' ||
        empty($midtrans_server_key)) {
        debug_log('Midtrans server key not configured properly: ' . $midtrans_server_key);
        return [
            'error' => 'Midtrans server key not configured properly',
            'debug' => [
                'server_key' => $midtrans_server_key
            ]
        ];
    }

    $url = $base_url . '/v2/charge';

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($midtrans_server_key . ':')
    ];

    $payload = json_encode($transaction_details);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    debug_log('Midtrans API URL: ' . $url);
    debug_log('Midtrans Server Key: ' . substr($midtrans_server_key, 0, 10) . '...');
    debug_log('Midtrans Request: ' . $payload);
    debug_log('Midtrans API Response: ' . $response);
    debug_log('Midtrans HTTP Code: ' . $http_code);
    debug_log('Midtrans CURL Error: ' . $curl_error);

    if ($curl_error) {
        debug_log('CURL Error: ' . $curl_error);
        return [
            'error' => 'CURL Error: ' . $curl_error,
            'debug' => [
                'curl_error' => $curl_error,
                'http_code' => $http_code,
                'response' => $response
            ]
        ];
    }

    $result = json_decode($response, true);

    if ($http_code === 201 || $http_code === 200) {
        if (isset($result['token'])) {
            return $result['token'];
        } else {
            return $result;
        }
    } else {
        debug_log('HTTP Error ' . $http_code . ': ' . $response);
        return [
            'error' => 'HTTP Error ' . $http_code,
            'debug' => [
                'http_code' => $http_code,
                'response' => $response,
                'result' => $result
            ]
        ];
    }
}

// ... (functions handlePaymentNotification, activateUserAccount, handleSubscriptionUpgrade, getPackageLimits, sendUpgradeEmail, sendWelcomeEmail remain unchanged) ...

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

    $signature_key = $result['signature_key'] ?? '';
    $expected_signature = hash('sha512', $order_id . $result['status_code'] . $gross_amount . 'YOUR-MIDTRANS-SERVER-KEY');

    if ($signature_key !== $expected_signature) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        return;
    }

    $stmt = $conn->prepare("UPDATE payments SET 
        transaction_status = ?, 
        fraud_status = ?, 
        payment_type = ?,
        updated_at = NOW()
        WHERE order_id = ?");

    $stmt->bind_param('ssss', $transaction_status, $fraud_status, $payment_type, $order_id);

    if ($stmt->execute()) {
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

    $stmt = $conn->prepare("SELECT user_id, package_type FROM payments WHERE order_id = ?");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $package_type = $row['package_type'];

        $is_upgrade = strpos($order_id, 'UPG-') === 0;

        if ($is_upgrade) {
            handleSubscriptionUpgrade($user_id, $package_type);
        } else {
            $update_user = $conn->prepare("UPDATE users SET 
                status = 'active', 
                package_type = ?,
                activated_at = NOW()
                WHERE id = ?");

            $update_user->bind_param('si', $package_type, $user_id);
            $update_user->execute();
            $update_user->close();

            sendWelcomeEmail($user_id);
        }
    }

    $stmt->close();
}

function handleSubscriptionUpgrade($user_id, $package_type) {
    global $conn;

    $update_user = $conn->prepare("UPDATE users SET 
        package_type = ?,
        updated_at = NOW()
        WHERE id = ?");

    $update_user->bind_param('si', $package_type, $user_id);
    $update_user->execute();
    $update_user->close();

    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 month'));

    $check_subscription = $conn->prepare("SELECT id FROM user_subscriptions WHERE user_id = ? AND status = 'active'");
    $check_subscription->bind_param('i', $user_id);
    $check_subscription->execute();
    $result = $check_subscription->get_result();

    if ($result->num_rows > 0) {
        $update_subscription = $conn->prepare("UPDATE user_subscriptions SET 
            package_type = ?,
            start_date = ?,
            end_date = ?,
            updated_at = NOW()
            WHERE user_id = ? AND status = 'active'");

        $update_subscription->bind_param('sssi', $package_type, $start_date, $end_date, $user_id);
        $update_subscription->execute();
        $update_subscription->close();
    } else {
        $insert_subscription = $conn->prepare("INSERT INTO user_subscriptions (
            user_id, package_type, start_date, end_date, status, created_at
        ) VALUES (?, ?, ?, ?, 'active', NOW())");

        $insert_subscription->bind_param('isss', $user_id, $package_type, $start_date, $end_date);
        $insert_subscription->execute();
        $insert_subscription->close();
    }

    $check_subscription->close();

    $limits = getPackageLimits($package_type);

    $update_limits = $conn->prepare("INSERT INTO user_limits (
        user_id, package_type, max_devices, max_messages, used_messages, reset_date, created_at
    ) VALUES (?, ?, ?, ?, 0, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        package_type = VALUES(package_type),
        max_devices = VALUES(max_devices),
        max_messages = VALUES(max_messages),
        reset_date = VALUES(reset_date),
        updated_at = NOW()");

    $update_limits->bind_param('isiss', $user_id, $package_type, $limits['max_devices'], $limits['max_messages'], $start_date);
    $update_limits->execute();
    $update_limits->close();

    sendUpgradeEmail($user_id, $package_type);
}

function getPackageLimits($package_type) {
    $limits = [
        'starter' => ['max_devices' => 1, 'max_messages' => 1000],
        'business' => ['max_devices' => 5, 'max_messages' => 10000],
        'enterprise' => ['max_devices' => -1, 'max_messages' => -1]
    ];

    return $limits[$package_type] ?? $limits['starter'];
}

function sendUpgradeEmail($user_id, $package_type) {
    global $conn;

    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $to = $row['email'];
        $name = $row['full_name'];
        $package_name = ucfirst($package_type);

        $subject = "Upgrade Berhasil - WhatsApp Gateway";
        $message = "
        <html>
        <body>
            <h2>Upgrade Berhasil!</h2>
            <p>Halo {$name},</p>
            <p>Terima kasih telah melakukan upgrade ke paket <strong>{$package_name}</strong>.</p>
            <p>Akun Anda telah berhasil diupgrade dengan fitur-fitur baru:</p>
            <ul>
                <li>Paket: {$package_name}</li>
                <li>Status: Aktif</li>
                <li>Periode: 1 bulan</li>
            </ul>
            <p>Silakan login ke dashboard untuk menikmati fitur-fitur baru.</p>
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
    case 'upgrade':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }

        $package = $_GET['package'] ?? '';
        $user_id = $_GET['user_id'] ?? '';

        if (empty($package) || empty($user_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Package and user_id required']);
            break;
        }

        $packages = [
            'starter' => ['name' => 'Starter', 'price' => 99000],
            'business' => ['name' => 'Business', 'price' => 299000],
            'enterprise' => ['name' => 'Enterprise', 'price' => 999000]
        ];

        if (!isset($packages[$package])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid package']);
            break;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            break;
        }

        $order_id = 'UPG-' . time() . '-' . $user_id;

        $transaction_details = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $packages[$package]['price']
            ],
            'customer_details' => [
                'first_name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? ''
            ],
            'item_details' => [
                [
                    'id' => $package,
                    'price' => $packages[$package]['price'],
                    'quantity' => 1,
                    'name' => 'WhatsApp Gateway - ' . $packages[$package]['name'] . ' Plan'
                ]
            ]
        ];

        $snap_token_result = generateSnapToken($transaction_details);

        if (is_array($snap_token_result) && isset($snap_token_result['error'])) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to generate Snap token',
                'debug' => $snap_token_result['debug'] ?? [],
                'midtrans_request' => $transaction_details
            ]);
            break;
        }

        if (is_array($snap_token_result)) {
            $stmt = $conn->prepare("INSERT INTO payments (
                order_id, user_id, amount, package_type, snap_token, status, transaction_status, payment_type, created_at
            ) VALUES (?, ?, ?, ?, '', 'pending', ?, ?, NOW())");

            $transaction_status = $snap_token_result['transaction_status'] ?? '';
            $payment_type = $snap_token_result['payment_type'] ?? '';

            $stmt->bind_param(
                'sidsis',
                $order_id,
                $user_id,
                $packages[$package]['price'],
                $package,
                $transaction_status,
                $payment_type
            );

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'midtrans' => $snap_token_result,
                    'client_key' => $midtrans_client_key,
                    'order_id' => $order_id
                ];
                if (isset($snap_token_result['va_numbers'][0]['va_number'])) $response['va_number'] = $snap_token_result['va_numbers'][0]['va_number'];
                if (isset($snap_token_result['permata_va_number'])) $response['permata_va_number'] = $snap_token_result['permata_va_number'];
                if (isset($snap_token_result['expiry_time']) && !empty($snap_token_result['expiry_time'])) $response['expiry_time'] = $snap_token_result['expiry_time'];

                // Tambahan: trigger notifikasi sukses ke frontend
                $response['show_success_popup'] = true;

                echo json_encode($response);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save payment record']);
            }

            $stmt->close();
        } else {
            $snap_token = $snap_token_result;
            if ($snap_token) {
                $stmt = $conn->prepare("INSERT INTO payments (
                    order_id, user_id, amount, package_type, snap_token, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");

                $stmt->bind_param('sidsi',
                    $order_id,
                    $user_id,
                    $packages[$package]['price'],
                    $package,
                    $snap_token
                );

                if ($stmt->execute()) {
                    echo json_encode([
                        'status' => 'success',
                        'snap_token' => $snap_token,
                        'client_key' => $midtrans_client_key,
                        'order_id' => $order_id,
                        'show_success_popup' => true // Tambahan trigger popup sukses
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to save payment record']);
                }

                $stmt->close();
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to generate Snap token', 'debug' => 'Unknown error']);
            }
        }
        break;

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

        $required_fields = ['order_id', 'amount', 'customer_name', 'customer_email'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                break 2;
            }
        }

        $transaction_details = [
            'payment_type' => 'bank_transfer',
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
            ]
        ];

        $snap_token_result = generateSnapToken($transaction_details);

        if (is_array($snap_token_result) && isset($snap_token_result['error'])) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to generate Snap token',
                'debug' => $snap_token_result['debug'] ?? [],
                'midtrans_request' => $transaction_details
            ]);
            break;
        }

        if (is_array($snap_token_result)) {
            $stmt = $conn->prepare("INSERT INTO payments (
                order_id, user_id, amount, package_type, snap_token, status, transaction_status, payment_type, created_at
            ) VALUES (?, ?, ?, ?, '', 'pending', ?, ?, NOW())");

            $user_id = $input['user_id'] ?? 0;
            $package_type = $input['package_type'] ?? 'starter';
            $transaction_status = $snap_token_result['transaction_status'] ?? '';
            $payment_type = $snap_token_result['payment_type'] ?? '';

            $stmt->bind_param(
                'sidsis',
                $input['order_id'],
                $user_id,
                $input['amount'],
                $package_type,
                $transaction_status,
                $payment_type
            );

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'midtrans' => $snap_token_result,
                    'client_key' => $midtrans_client_key
                ];
                if (isset($snap_token_result['va_numbers'][0]['va_number'])) $response['va_number'] = $snap_token_result['va_numbers'][0]['va_number'];
                if (isset($snap_token_result['permata_va_number'])) $response['permata_va_number'] = $snap_token_result['permata_va_number'];
                if (isset($snap_token_result['expiry_time']) && !empty($snap_token_result['expiry_time'])) $response['expiry_time'] = $snap_token_result['expiry_time'];

                // Tambahan: trigger notifikasi sukses ke frontend
                $response['show_success_popup'] = true;

                echo json_encode($response);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save payment record']);
            }

            $stmt->close();
        } else {
            $snap_token = $snap_token_result;
            if ($snap_token) {
                $stmt = $conn->prepare("INSERT INTO payments (
                    order_id, user_id, amount, package_type, snap_token, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");

                $user_id = $input['user_id'] ?? 0;
                $package_type = $input['package_type'] ?? 'starter';

                $stmt->bind_param('sidsi',
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
                        'client_key' => $midtrans_client_key,
                        'show_success_popup' => true // Tambahan trigger popup sukses
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to save payment record']);
                }

                $stmt->close();
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to generate Snap token', 'debug' => 'Unknown error']);
            }
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