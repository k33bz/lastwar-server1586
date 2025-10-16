<?php
/**
 * Session Refresh Endpoint
 * Version: 1.0.0
 * Extends user session when requested
 */

// Require JWT authentication
require_once 'jwt.php';

try {
    $user = require_jwt_session();
    
    // Security check
    if ($user->aud !== 'admin') {
        http_response_code(401);
        exit('Unauthorized');
    }
} catch (Exception $e) {
    http_response_code(401);
    exit('Unauthorized');
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Refresh session timestamp
$_SESSION['last_activity'] = time();

// Log session refresh (if audit logging is available)
try {
    if (file_exists('../includes/audit_logger.php')) {
        require_once '../includes/audit_logger.php';
        if (function_exists('logAuditEvent')) {
            logAuditEvent($_SESSION['user_id'], 'session_refresh', 'Session refreshed via AJAX', $_SERVER['REMOTE_ADDR']);
        }
    }
} catch (Exception $e) {
    // Ignore audit logging errors for now
}

// Return success
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Session refreshed',
    'timestamp' => time()
]);
?>