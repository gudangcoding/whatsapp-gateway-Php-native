<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../helper/koneksi.php';
require_once '../helper/auth.php';

// Require authentication
requireAuth();

$user_id = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get user's API keys
        $api_keys = getUserApiKeys($user_id);
        echo json_encode([
            'success' => true,
            'api_keys' => $api_keys
        ]);
        break;
        
    case 'POST':
        // Generate new API key
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? 'Default';
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Name is required'
            ]);
            break;
        }
        
        try {
            $api_key = generateApiKey($user_id, $name);
            
            // Log activity
            logActivity($user_id, 'generate_api_key', "Generated new API key: $name");
            
            echo json_encode([
                'success' => true,
                'message' => 'API key generated successfully',
                'api_key' => $api_key
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate API key: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'PUT':
        // Update API key name
        $input = json_decode(file_get_contents('php://input'), true);
        $api_key_id = $input['id'] ?? null;
        $name = $input['name'] ?? null;
        
        if (!$api_key_id || !$name) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID and name are required'
            ]);
            break;
        }
        
        $stmt = $conn->prepare("UPDATE user_api_keys SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sii', $name, $api_key_id, $user_id);
        
        if ($stmt->execute()) {
            // Log activity
            logActivity($user_id, 'update_api_key', "Updated API key name to: $name");
            
            echo json_encode([
                'success' => true,
                'message' => 'API key updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update API key'
            ]);
        }
        $stmt->close();
        break;
        
    case 'DELETE':
        // Revoke API key
        $api_key_id = $_GET['id'] ?? null;
        
        if (!$api_key_id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'API key ID is required'
            ]);
            break;
        }
        
        if (revokeApiKey($user_id, $api_key_id)) {
            // Log activity
            logActivity($user_id, 'revoke_api_key', "Revoked API key ID: $api_key_id");
            
            echo json_encode([
                'success' => true,
                'message' => 'API key revoked successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'API key not found or already revoked'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        break;
}

$conn->close();
?> 