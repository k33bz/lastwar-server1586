<?php
/**
 * Send Magic Link - Process login request and send email
 *
 * Validates email, generates magic link token, and sends email
 *
 * Documentation:
 * - Security Issue: https://github.com/k33bz/lastwar-server1586/issues/35
 *
 * @version 1.1.0
 * @date 2025-10-29
 * @changelog
 *   1.1.0 (2025-10-29) - Added rate limiting (5 requests/minute per IP)
 *   1.0.0 (2025-10-12) - Initial complete implementation
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/includes/rate_limiter.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Rate limiting: 5 attempts per minute
rate_limit_check('login', 5, 60);

// Validate email input
if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?error=unknown_email');
    exit;
}

$email = strtolower(trim($_POST['email']));

// Get selected language from login form (default to 'en' if not provided)
$selected_language = $_POST['language'] ?? 'en';

// Find user in users.json
$user = get_user_by_email($email);

if (!$user) {
    // Don't reveal if email exists or not (security best practice)
    // Still redirect with success message
    header('Location: login.php?success=sent');
    exit;
}

try {
    // Save user's language preference if user exists
    update_user_language($email, $selected_language);

    // Generate magic link token
    $magic_token = create_magic_link_token($email, $user);

    // Build magic link URL
    $magic_link_url = APP_URL . '/admin/callback.php?token=' . $magic_token;

    // In development mode, log the magic link URL for easy access
    if (APP_ENV === 'development') {
        error_log("==========================================");
        error_log("DEVELOPMENT MODE: Magic Link Generated");
        error_log("Email: $email");
        error_log("Language: $selected_language");
        error_log("Magic Link URL: $magic_link_url");
        error_log("Copy this URL to your browser to log in");
        error_log("==========================================");
    }

    // Send magic link email in selected language
    $email_result = send_magic_link_email($email, $magic_link_url, null, $selected_language);

    if (!$email_result) {
        throw new Exception("Email sending returned false");
    }

    // Log successful send
    error_log("Magic link sent successfully to: $email (language: $selected_language)");

    // Redirect with success message
    header('Location: login.php?success=sent');
    exit;

} catch (Exception $e) {
    // Log error
    error_log("FAILED to send magic link to $email: " . $e->getMessage());

    // Redirect with error
    header('Location: login.php?error=send_failed');
    exit;
}
?>