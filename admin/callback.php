<?php
/**
 * Magic Link Callback - Validate magic link and establish session
 *
 * Processes magic link token, validates it, blacklists it, and creates session
 *
 * @version 1.2.0
 * @date 2025-01-18
 * @changelog
 *   1.2.0 (2025-01-18) - Added UID-based identity support (v4.0.0+)
 *                       - Uses email claim from token for language and audit logging
 *                       - Maintains backward compatibility with legacy email-based tokens
 *   1.1.0 (2025-10-15) - Added audit logging for successful logins
 *   1.0.0 (2025-10-12) - Initial complete implementation
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

// Check for token parameter
if (!isset($_GET['token'])) {
    header('Location: login.php?error=invalid');
    exit;
}

$magic_token_string = $_GET['token'];

try {
    // Decode and validate magic link token
    $magic_token = decode_jwt($magic_token_string);

    // Verify it's actually a magic link token
    if (!isset($magic_token->magic) || $magic_token->magic !== true) {
        throw new Exception('Not a valid magic link token');
    }

    // Blacklist the magic link token (single-use enforcement)
    blacklist_token($magic_token->jti, $magic_token->exp);

    // Get user email (v4.0.0+: from email claim, legacy: from sub)
    $user_email = $magic_token->email ?? $magic_token->sub;

    // Get user's preferred language
    $user_language = get_user_language($user_email);

    // Create new session token (without magic flag)
    $session_token = create_session_token($magic_token, $user_language);

    // Set session cookie
    set_session_cookie($session_token);

    // Log successful login
    error_log("Successful login via magic link: " . $user_email);

    // Audit log the login
    log_login($user_email, 'magic_link');

    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    // Log error
    error_log("Magic link validation failed: " . $e->getMessage());

    // Determine error type
    $error = 'invalid';
    $error_msg = $e->getMessage();

    if (strpos($error_msg, 'expired') !== false) {
        $error = 'expired';
    } elseif (strpos($error_msg, 'revoked') !== false) {
        $error = 'used'; // Magic link already used
    } elseif (strpos($error_msg, 'key rotated') !== false ||
               strpos($error_msg, 'Invalid token signature') !== false ||
               strpos($error_msg, 'Token invalid with both current and previous keys') !== false ||
               strpos($error_msg, 'invalid with both') !== false) {
        $error = 'key_rotated';
    }

    header('Location: login.php?error=' . $error);
    exit;
}
?>