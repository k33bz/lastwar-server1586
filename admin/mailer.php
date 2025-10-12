<?php
/**
 * Email Sending Functions using PHPMailer
 *
 * Handles email delivery for magic links and notifications
 *
 * @version 1.1.0
 * @date 2025-10-12
 * @changelog
 *   1.1.0 (2025-10-12) - Convert to HTML emails with professional styling
 *                      - Add no-reply notice to all emails
 *                      - Update to use SMTP_SSL (port 465) instead of STARTTLS (port 587)
 *                      - Switch from no-reploy@ to noreply@ (correct spelling, no hyphen)
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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
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

    $html_body = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #3498db;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .security-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice h3 {
            margin-top: 0;
            color: #856404;
            font-size: 16px;
        }
        .security-notice ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .security-notice li {
            color: #856404;
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        .no-reply-notice {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Last War 1586 Admin Login</h1>
        </div>

        <div class="content">
            <p>Hello,</p>
            <p>Click the button below to access your admin dashboard. This link will expire in <strong>10 minutes</strong>:</p>
        </div>

        <div class="button-container">
            <a href="$magic_link_url" class="button">Access Admin Dashboard</a>
        </div>

        <div class="content">
            <p style="color: #666; font-size: 14px;">Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #3498db; font-size: 13px;">$magic_link_url</p>
        </div>

        <div class="security-notice">
            <h3>🛡️ Security Information</h3>
            <ul>
                <li>This link is <strong>single-use</strong> and will expire after one successful login</li>
                <li>The link will expire in <strong>10 minutes</strong></li>
                <li>Never share this link with anyone</li>
                <li>If you did not request this login link, you can safely ignore this email</li>
            </ul>
        </div>

        <div class="no-reply-notice">
            <strong>Note:</strong> This is an automated message. Please do not reply to this email as this mailbox is not monitored.
        </div>

        <div class="footer">
            <p>Best regards,<br><strong>The Last War 1586 Admin Team</strong></p>
            <p style="font-size: 12px; color: #999;">This email was sent to $to</p>
        </div>
    </div>
</body>
</html>
EOT;

    return send_email($to, $subject, $html_body, true);
}

/**
 * Send test email (for debugging)
 *
 * @param string $to Recipient email address
 * @return bool Success status
 */
function send_test_email($to) {
    $subject = 'Last War 1586 Admin - SMTP Test Email';

    $html_body = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #27ae60;
        }
        .header h1 {
            color: #27ae60;
            margin: 0;
            font-size: 24px;
        }
        .success-badge {
            display: inline-block;
            background-color: #27ae60;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        .content {
            margin: 30px 0;
        }
        .config-box {
            background-color: #ecf0f1;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .config-box h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .config-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .config-box li {
            margin: 8px 0;
            color: #555;
        }
        .config-box code {
            background-color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
        }
        .no-reply-notice {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #2c3e50;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ SMTP Test Successful</h1>
            <div class="success-badge">Configuration Working</div>
        </div>

        <div class="content">
            <p><strong>Congratulations!</strong> If you received this email, your SMTP configuration is working correctly.</p>
        </div>

        <div class="config-box">
            <h3>📧 Configuration Tested</h3>
            <ul>
                <li><strong>SMTP Server:</strong> <code>example.com</code></li>
                <li><strong>Port:</strong> <code>465</code> (SSL)</li>
                <li><strong>Authentication:</strong> ✅ Successful</li>
                <li><strong>From Address:</strong> <code>noreply@example.com</code></li>
            </ul>
        </div>

        <div class="content">
            <h3>Next Steps:</h3>
            <p>You can now proceed with deploying the JWT admin system. The magic link authentication will work correctly.</p>
        </div>

        <div class="no-reply-notice">
            <strong>Note:</strong> This is an automated message. Please do not reply to this email as this mailbox is not monitored.
        </div>

        <div class="footer">
            <p>Best regards,<br><strong>The Last War 1586 Admin Team</strong></p>
            <p style="font-size: 12px; color: #999;">Test email sent to $to</p>
        </div>
    </div>
</body>
</html>
EOT;

    return send_email($to, $subject, $html_body, true);
}
?>
