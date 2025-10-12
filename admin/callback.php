<?php
/**
 * Magic Link Callback - Validate magic link and establish session
 *
 * Processes magic link token, validates it, blacklists it, and creates session
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

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

    // Create new session token (without magic flag)
    $session_token = create_session_token($magic_token);

    // Set session cookie
    set_session_cookie($session_token);

    // Log successful login
    error_log("Successful login via magic link: " . $magic_token->sub);

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
    }

    header('Location: login.php?error=' . $error);
    exit;
}
?>