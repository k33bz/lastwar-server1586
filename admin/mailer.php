<?php
/**
 * Email Sending Functions using PHPMailer
 *
 * Handles email delivery for magic links and notifications
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation with proper error handling
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using PHPMailer with SMTP
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (plain text or HTML)
 * @param bool $is_html Whether body is HTML (default: false)
 * @return bool Success status
 * @throws Exception if email cannot be sent
 */
function send_email($to, $subject, $body, $is_html = false) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Sender and recipient
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Reply-to (optional, can be different from sender)
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer error: " . $mail->ErrorInfo);
        throw new Exception("Could not send email: " . $mail->ErrorInfo);
    }
}

/**
 * Send magic link email
 *
 * @param string $to Recipient email address
 * @param string $magic_link_url Magic link URL
 * @return bool Success status
 */
function send_magic_link_email($to, $magic_link_url) {
    $subject = 'Your Login Link for Last War 1586 Admin';

    $body = <<<EOT
Hello,

Click the link below to access your admin dashboard. This link will expire in 10 minutes:

$magic_link_url

If you did not request this login link, you can safely ignore this email.

For security:
- This link is single-use and will expire after one successful login
- Never share this link with anyone
- The link will expire in 10 minutes

Best regards,
The Last War 1586 Admin Team
EOT;

    return send_email($to, $subject, $body, false);
}

/**
 * Send test email (for debugging)
 *
 * @param string $to Recipient email address
 * @return bool Success status
 */
function send_test_email($to) {
    $subject = 'Last War 1586 Admin - Test Email';
    $body = 'This is a test email from the Last War 1586 admin system. If you received this, email configuration is working correctly.';

    return send_email($to, $subject, $body, false);
}
?>
