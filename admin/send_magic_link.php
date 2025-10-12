<?php
/**
 * Send Magic Link - Process login request and send email
 *
 * Validates email, generates magic link token, and sends email
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/json_helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Validate email input
if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?error=unknown_email');
    exit;
}

$email = strtolower(trim($_POST['email']));

// Find user in users.json
$user = get_user_by_email($email);

if (!$user) {
    // Don't reveal if email exists or not (security best practice)
    // Still redirect with success message
    header('Location: login.php?success=sent');
    exit;
}

try {
    // Generate magic link token
    $magic_token = create_magic_link_token($email, $user);

    // Build magic link URL
    $magic_link_url = APP_URL . '/admin/callback.php?token=' . $magic_token;

    // Send magic link email
    send_magic_link_email($email, $magic_link_url);

    // Log successful send (optional)
    error_log("Magic link sent to: $email");

    // Redirect with success message
    header('Location: login.php?success=sent');
    exit;

} catch (Exception $e) {
    // Log error
    error_log("Failed to send magic link to $email: " . $e->getMessage());

    // Redirect with error
    header('Location: login.php?error=send_failed');
    exit;
}
?>