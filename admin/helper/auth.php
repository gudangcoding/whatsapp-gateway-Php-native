<?php
// ob_clean();
// session_start();

/**
 * Helper functions untuk authentication dan authorization multi-tenant
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user type
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? 'customer';
}

// Get current user info
function getCurrentUserInfo() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = getCurrentUserId();
    $user_type = getCurrentUserType();
    
    if ($user_type === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM account WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

// Check if user has access to specific resource
function hasAccess($resource_type, $resource_id = null) {
    if (isAdmin()) {
        return true; // Admin has access to everything
    }
    
    $user_id = getCurrentUserId();
    if (!$user_id) {
        return false;
    }
    
    switch ($resource_type) {
        case 'device':
            return hasDeviceAccess($user_id, $resource_id);
        case 'auto_reply':
            return hasAutoReplyAccess($user_id, $resource_id);
        case 'scheduled_message':
            return hasScheduledMessageAccess($user_id, $resource_id);
        case 'chat_history':
            return hasChatHistoryAccess($user_id, $resource_id);
        case 'contact':
            return hasContactAccess($user_id, $resource_id);
        default:
            return false;
    }
}

// Check device access
function hasDeviceAccess($user_id, $device_id = null) {
    global $conn;
    
    if ($device_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND device_id = ?");
        $stmt->bind_param('ii', $user_id, $device_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Check auto reply access
function hasAutoReplyAccess($user_id, $auto_reply_id = null) {
    global $conn;
    
    if ($auto_reply_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM autoreply WHERE user_id = ? AND id = ?");
        $stmt->bind_param('ii', $user_id, $auto_reply_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM autoreply WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Check scheduled message access
function hasScheduledMessageAccess($user_id, $message_id = null) {
    global $conn;
    
    if ($message_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pesan WHERE user_id = ? AND id = ?");
        $stmt->bind_param('ii', $user_id, $message_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pesan WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Check chat history access
function hasChatHistoryAccess($user_id, $chat_id = null) {
    global $conn;
    
    if ($chat_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM receive_chat WHERE user_id = ? AND id = ?");
        $stmt->bind_param('ii', $user_id, $chat_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM receive_chat WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Check contact access
function hasContactAccess($user_id, $contact_id = null) {
    global $conn;
    
    if ($contact_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contacts WHERE user_id = ? AND id = ?");
        $stmt->bind_param('ii', $user_id, $contact_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contacts WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Get user's devices
function getUserDevices($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT n.* FROM nomor n 
        INNER JOIN user_devices ud ON n.id = ud.device_id 
        WHERE ud.user_id = ? AND ud.status = 'active'
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $devices = [];
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
    $stmt->close();
    
    return $devices;
}

// Get user's package limits
function getUserLimits($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT ul.* FROM user_limits ul 
        WHERE ul.user_id = ? 
        ORDER BY ul.created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $limits = $result->fetch_assoc();
    $stmt->close();
    
    return $limits;
}

// Check if user can perform action based on limits
function canPerformAction($user_id, $action_type) {
    $limits = getUserLimits($user_id);
    if (!$limits) {
        return false;
    }
    
    switch ($action_type) {
        case 'send_message':
            return $limits['used_messages'] < $limits['max_messages'];
        case 'add_device':
            $devices = getUserDevices($user_id);
            return count($devices) < $limits['max_devices'];
        default:
            return true;
    }
}

// Log user activity
function logActivity($user_id, $action, $description, $resource_type = null, $resource_id = null) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO user_activity_logs (user_id, action, description, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('issss', $user_id, $action, $description, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Require authentication
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Require admin access
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo '<div class="alert alert-danger">Akses ditolak. Anda tidak memiliki izin admin.</div>';
        exit;
    }
}

// Get user's API keys
function getUserApiKeys($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM user_api_keys WHERE user_id = ? AND is_active = 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $api_keys = [];
    while ($row = $result->fetch_assoc()) {
        $api_keys[] = $row;
    }
    $stmt->close();
    
    return $api_keys;
}

// Validate API key
function validateApiKey($api_key) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT uak.*, u.status as user_status, u.package_type 
        FROM user_api_keys uak 
        INNER JOIN users u ON uak.user_id = u.id 
        WHERE uak.api_key = ? AND uak.is_active = 1 AND u.status = 'active'
    ");
    $stmt->bind_param('s', $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $api_key_data = $result->fetch_assoc();
    $stmt->close();
    
    return $api_key_data;
}

// Generate new API key
function generateApiKey($user_id, $name = 'Default') {
    global $conn;
    
    $api_key = 'wa_' . bin2hex(random_bytes(32));
    $created_at = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO user_api_keys (user_id, api_key, name, created_at) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('isss', $user_id, $api_key, $name, $created_at);
    $stmt->execute();
    $stmt->close();
    
    return $api_key;
}

// Revoke API key
function revokeApiKey($user_id, $api_key_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE user_api_keys 
        SET is_active = 0, revoked_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $api_key_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    return $stmt->affected_rows > 0;
}
?> 