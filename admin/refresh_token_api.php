<?php
/**
 * JWT Token Refresh API
 * Version: 1.0.0
 *
 * Allows users to refresh their JWT token before it expires
 * Issues a new token with fresh expiration time
 */

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'json_helpers.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Validate current session
    $user = require_jwt_session_api();

    // Get user info
    $userEmail = $user->email ?? $user->sub;
    $userData = get_user_by_email($userEmail);

    if (!$userData) {
        throw new Exception('User not found');
    }

    // Create a new session token with fresh expiration
    // Preserve language preference from old token
    $preferredLanguage = $user->lang ?? null;
    $newToken = create_session_token($user, $preferredLanguage);

    // Set the new cookie
    set_session_cookie($newToken);

    // Decode the new token to get expiration time
    $decoded = decode_jwt($newToken);

    // Log the refresh event
    log_audit_event('token_refreshed', $userEmail, [
        'old_exp' => $user->exp,
        'new_exp' => $decoded->exp,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Token refreshed successfully',
        'expires_at' => $decoded->exp,
        'extended_by' => SESSION_TOKEN_EXPIRY
    ]);

} catch (Exception $e) {
    error_log('[TOKEN_REFRESH] Error: ' . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
