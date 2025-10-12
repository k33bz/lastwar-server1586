<?php
/**
 * Logout - Revoke session and clear cookie
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation with token revocation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

// Revoke current session token (adds to blacklist)
revoke_current_session();

// Redirect to login
header('Location: login.php');
exit;
?>
