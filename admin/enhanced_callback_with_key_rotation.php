<?php
/**
 * Enhanced Magic Link Callback with Key Rotation Support
 *
 * Example of how to update callback.php to handle key rotation
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/enhanced_jwt_with_key_rotation.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

// Check for token parameter
if (!isset($_GET['token'])) {
    header('Location: login.php?error=invalid');
    exit;
}

$magic_token_string = $_GET['token'];

try {
    // Validate magic link with key rotation support
    $magic_token = validate_magic_link_with_key_rotation($magic_token_string);

    // Blacklist the magic link token (single-use enforcement)
    blacklist_token($magic_token->jti, $magic_token->exp);

    // Create new session token (without magic flag)
    $session_token = create_session_token($magic_token);

    // Set session cookie
    set_session_cookie($session_token);

    // Log successful login
    error_log("Successful login via magic link: " . $magic_token->sub);

    // Audit log the login
    log_login($magic_token->sub, 'magic_link');

    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    // Log error
    error_log("Magic link validation failed: " . $e->getMessage());

    // Determine error type
    $error = 'invalid';
    if (strpos($e->getMessage(), 'expired') !== false) {
        $error = 'expired';
    } elseif (strpos($e->getMessage(), 'revoked') !== false) {
        $error = 'used'; // Magic link already used
    } elseif (strpos($e->getMessage(), 'key rotated') !== false) {
        $error = 'key_rotated';
    }

    header('Location: login.php?error=' . $error);
    exit;
}
?>