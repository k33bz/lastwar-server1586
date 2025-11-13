<?php
/**
 * Logout - Revoke session and clear cookie
 *
 * @version 1.1.0
 * @date 2025-10-15
 * @changelog
 *   1.1.0 (2025-10-15) - Added audit logging for logout events
 *   1.0.0 (2025-10-12) - Initial complete implementation with token revocation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Get user email before revoking session
$user_email = null;
if (isset($_COOKIE['jwt'])) {
    try {
        $token = decode_jwt($_COOKIE['jwt']);
        $user_email = $token->sub;
    } catch (Exception $e) {
        // Token invalid, continue with logout
    }
}

// Revoke current session token (adds to blacklist)
revoke_current_session();

// Log logout if we got the user email
if ($user_email) {
    log_logout($user_email);
}

// Redirect to login
header('Location: login.php');

// Fallback if header redirect fails
echo '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0;url=login.php">
    <title>Logging out...</title>
</head>
<body>
    <p>Logging out... <a href="login.php">Click here if not redirected</a></p>
</body>
</html>';
exit;
?>
