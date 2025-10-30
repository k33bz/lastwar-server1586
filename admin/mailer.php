<?php
/**
 * Email Sending Functions using PHPMailer
 *
 * Handles email delivery for magic links and notifications
 *
 * Documentation:
 * - DKIM Setup: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/DKIM-SETUP.md
 * - Environment Configuration: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ENV-CONFIG.md
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.3.0
 * @date 2025-10-13
 * @changelog
 *   1.3.0 (2025-10-13) - Add personalized email greeting with username
 *                      - Extract username from email if not provided
 *                      - Update send_magic_link_email() signature with optional username parameter
 *   1.2.0 (2025-10-13) - Enhanced magic link email design with gradient buttons
 *                      - Added expiry badge and improved security messaging
 *                      - Updated styling to match test email aesthetic
 *                      - Added link box for alternative access method
 *   1.1.0 (2025-10-12) - Convert to HTML emails with professional styling
 *                      - Add no-reply notice to all emails
 *                      - Update to use SMTP_SSL (port 465) instead of STARTTLS (port 587)
 *                      - Switch from no-reploy@ to noreply@ (correct spelling, no hyphen)
 *   1.0.0 (2025-10-12) - Initial complete implementation with proper error handling
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
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
 * @param string $username Username for personalized greeting (optional, defaults to email prefix)
 * @return bool Success status
 */
function send_magic_link_email($to, $magic_link_url, $username = null) {
    // If no username provided, extract from email
    if ($username === null) {
        $username = explode('@', $to)[0];
    }
    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $app_name_short = $_ENV['APP_NAME'] ?? 'Last War 1586';
    $subject = 'Your Login Link for ' . $app_name;

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
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        .content {
            margin-bottom: 25px;
        }
        .content p {
            margin: 15px 0;
            font-size: 15px;
        }
        .button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        .button:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .link-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
        }
        .link-box p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .link-box a {
            color: #667eea;
            font-size: 13px;
            text-decoration: none;
        }
        .security-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .security-notice h3 {
            margin-top: 0;
            color: #856404;
            font-size: 17px;
            font-weight: 700;
        }
        .security-notice ul {
            margin: 15px 0;
            padding-left: 25px;
        }
        .security-notice li {
            color: #856404;
            margin: 10px 0;
            font-size: 14px;
        }
        .security-notice strong {
            font-weight: 700;
        }
        .expiry-badge {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 5px;
        }
        .no-reply-notice {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 25px 0;
            border-radius: 6px;
            font-size: 14px;
            color: #2c3e50;
        }
        .no-reply-notice strong {
            font-weight: 700;
        }
        .footer {
            text-align: center;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        .footer strong {
            color: #667eea;
            font-weight: 700;
        }
        .footer p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 $app_name_short</h1>
            <div class="header-badge">ADMIN LOGIN REQUEST</div>
        </div>

        <div class="content">
            <p><strong>Hello $username,</strong></p>
            <p>You requested access to the $app_name Dashboard. Click the button below to securely log in:</p>
        </div>

        <div class="button-container">
            <a href="$magic_link_url" class="button">🚀 Access Admin Dashboard</a>
        </div>

        <div class="link-box">
            <p><strong>Alternative:</strong> Copy and paste this link into your browser:</p>
            <a href="$magic_link_url">$magic_link_url</a>
        </div>

        <div class="security-notice">
            <h3>🛡️ Security Information</h3>
            <ul>
                <li>This link is <strong>single-use only</strong> and will become invalid after your first successful login</li>
                <li>Link expires in <span class="expiry-badge">⏱️ 10 MINUTES</span></li>
                <li><strong>Never share this link</strong> with anyone - it grants full access to your admin account</li>
                <li>If you didn't request this login, you can safely ignore this email</li>
                <li>For security, the link cannot be reused once clicked</li>
            </ul>
        </div>

        <div class="no-reply-notice">
            <strong>⚠️ Note:</strong> This is an automated message from a no-reply email address. Please do not reply to this email as this mailbox is not monitored. For support, contact your server administrator.
        </div>

        <div class="footer">
            <p>Best regards,</p>
            <p><strong>The $app_name Team</strong></p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">This email was sent to $to</p>
        </div>
    </div>
</body>
</html>
EOT;

    return send_email($to, $subject, $html_body, true);
}

/**
 * Send role change notification email
 *
 * @param string $to User email address
 * @param array $changes Array of changes (old_role, new_role, old_alliances, new_alliances, old_powereditor, new_powereditor)
 * @param string $changed_by Admin who made the change
 * @return bool Success status
 */
function send_role_change_email($to, $changes, $changed_by) {
    $username = explode('@', $to)[0];
    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $subject = $app_name . ' - Your Access Has Been Updated';

    // Build change summary
    $change_items = [];

    if (isset($changes['role'])) {
        $old_role = strtoupper($changes['role']['old']);
        $new_role = strtoupper($changes['role']['new']);
        $change_items[] = "<strong>Role:</strong> {$old_role} → {$new_role}";
    }

    if (isset($changes['powereditor'])) {
        $old_pe = $changes['powereditor']['old'] ? 'Yes' : 'No';
        $new_pe = $changes['powereditor']['new'] ? 'Yes' : 'No';
        $change_items[] = "<strong>Power Editor:</strong> {$old_pe} → {$new_pe}";
    }

    if (isset($changes['alliances'])) {
        $old_alliances = implode(', ', $changes['alliances']['old']);
        $new_alliances = implode(', ', $changes['alliances']['new']);
        if (empty($old_alliances)) $old_alliances = '(none)';
        if (empty($new_alliances)) $new_alliances = '(none)';
        $change_items[] = "<strong>Alliance Access:</strong> {$old_alliances} → {$new_alliances}";
    }

    $changes_html = implode('<br>', $change_items);

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
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        .content {
            margin-bottom: 25px;
        }
        .content p {
            margin: 15px 0;
            font-size: 15px;
        }
        .changes-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .changes-box h3 {
            margin: 0 0 15px 0;
            color: #856404;
            font-size: 16px;
        }
        .changes-box p {
            margin: 10px 0;
            color: #856404;
            line-height: 1.8;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #0c5da5;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 13px;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$app_name}</h1>
            <span class="header-badge">ACCESS UPDATE</span>
        </div>

        <div class="content">
            <p>Hello <strong>{$username}</strong>,</p>

            <p>An administrator has updated your access permissions for the {$app_name} admin panel.</p>

            <div class="changes-box">
                <h3>What Changed:</h3>
                <p>{$changes_html}</p>
            </div>

            <div class="info-box">
                <p><strong>Changed by:</strong> {$changed_by}</p>
                <p><strong>Date:</strong> {date('F j, Y \a\t g:i A T')}</p>
            </div>

            <p>These changes are effective immediately. If you have any questions or believe this change was made in error, please contact an administrator.</p>

            <p>To access the admin panel, request a magic link at the login page or contact an admin to send you a login link.</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from {$app_name}</p>
            <p>If you did not expect this change, please contact your alliance administrator immediately</p>
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
    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $subject = $app_name . ' - SMTP Test Email';

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
            <p>Best regards,<br><strong>The $app_name Team</strong></p>
            <p style="font-size: 12px; color: #999;">Test email sent to $to</p>
        </div>
    </div>
</body>
</html>
EOT;

    return send_email($to, $subject, $html_body, true);
}
?>
