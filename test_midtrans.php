<?php
// Test Midtrans API
require_once 'config/midtrans.php';

echo "=== Midtrans Configuration Test ===\n";
echo "Server Key: " . substr(MIDTRANS_SERVER_KEY, 0, 10) . "...\n";
echo "Client Key: " . substr(MIDTRANS_CLIENT_KEY, 0, 10) . "...\n";
echo "Merchant ID: " . MIDTRANS_MERCHANT_ID . "\n";
echo "Production Mode: " . (MIDTRANS_PRODUCTION ? 'Yes' : 'No') . "\n";
echo "Demo Mode: " . (MIDTRANS_DEMO_MODE ? 'Yes' : 'No') . "\n";

// Test basic API call
$base_url = MIDTRANS_PRODUCTION ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

$test_data = [
    'transaction_details' => [
        'order_id' => 'TEST-' . time(),
        'gross_amount' => 99000
    ],
    'customer_details' => [
        'first_name' => 'Test',
        'email' => 'test@example.com'
    ],
    'item_details' => [
        [
            'id' => 'starter',
            'price' => 99000,
            'quantity' => 1,
            'name' => 'Test Package'
        ]
    ]
];

echo "\n=== Testing API Call ===\n";
echo "URL: " . $base_url . "/v2/charge\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n";

$headers = [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/v2/charge');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "\n=== API Response ===\n";
echo "HTTP Code: " . $http_code . "\n";
echo "CURL Error: " . ($curl_error ?: 'None') . "\n";
echo "Response: " . $response . "\n";

if ($http_code === 200) {
    $result = json_decode($response, true);
    if (isset($result['token'])) {
        echo "\n✅ SUCCESS: Token generated: " . $result['token'] . "\n";
    } else {
        echo "\n❌ ERROR: No token in response\n";
    }
} else {
    echo "\n❌ ERROR: HTTP " . $http_code . "\n";
}
?> 