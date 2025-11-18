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
if (!defined('ADMIN_BASE_PATH')) {
    define('ADMIN_BASE_PATH', __DIR__);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/i18n.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Initialize i18n for email sending
 * Detects language from browser header or uses default
 *
 * @param string|null $language Language code to use (null = auto-detect from browser)
 * @return string The language code that was loaded
 */
function email_load_i18n($language = null, $user_email = null) {
    global $i18n_supported_languages;

    // Priority 1: If explicit language specified, use it
    if ($language !== null) {
        i18n_load_translations($language);
        return $language;
    }

    // Priority 2: Check user's stored language preference
    if ($user_email !== null) {
        $user_lang = get_user_language($user_email);
        if ($user_lang !== null && isset($i18n_supported_languages[$user_lang])) {
            i18n_load_translations($user_lang);
            return $user_lang;
        }
    }

    // Priority 3: Try to detect from HTTP_ACCEPT_LANGUAGE header
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $langs = explode(',', $accept_language);

        foreach ($langs as $lang) {
            $lang_code = strtolower(substr(trim($lang), 0, 2));
            if (isset($i18n_supported_languages[$lang_code])) {
                $language = $lang_code;
                break;
            }
        }
    }

    // Priority 4: Default to English
    if ($language === null) {
        $language = 'en';
    }

    // Load translations for the specified language
    i18n_load_translations($language);

    return $language;
}

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
function send_magic_link_email($to, $magic_link_url, $username = null, $language = null) {
    // Load i18n translations (with user email for preference lookup)
    email_load_i18n($language, $to);

    // If no username provided, extract from email
    if ($username === null) {
        $username = explode('@', $to)[0];
    }
    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $app_name_short = $_ENV['APP_NAME'] ?? 'Last War 1586';

    // Get translated subject
    $subject = __('emails.magic_link.subject', ['app_name' => $app_name]);

    // Get all translated strings
    $t_badge = __('emails.magic_link.badge');
    $t_greeting = __('emails.magic_link.greeting', ['username' => $username]);
    $t_intro = __('emails.magic_link.intro', ['app_name' => $app_name]);
    $t_button = __('emails.magic_link.button');
    $t_alt_label = __('emails.magic_link.alternative_label');
    $t_alt_text = __('emails.magic_link.alternative_text');
    $t_security_title = __('emails.magic_link.security_title');
    $t_security_single_use = __('emails.magic_link.security_items.single_use');
    $t_security_expiry = __('emails.magic_link.security_items.expiry');
    $t_security_no_share = __('emails.magic_link.security_items.no_share');
    $t_security_ignore = __('emails.magic_link.security_items.ignore');
    $t_security_no_reuse = __('emails.magic_link.security_items.no_reuse');
    $t_no_reply_title = __('emails.magic_link.no_reply_title');
    $t_no_reply_text = __('emails.magic_link.no_reply_text');
    $t_footer_regards = __('emails.magic_link.footer_regards');
    $t_footer_team = __('emails.magic_link.footer_team', ['app_name' => $app_name]);
    $t_footer_sent_to = __('emails.magic_link.footer_sent_to', ['email' => $to]);

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
            <div class="header-badge">{$t_badge}</div>
        </div>

        <div class="content">
            <p>{$t_greeting}</p>
            <p>{$t_intro}</p>
        </div>

        <div class="button-container">
            <a href="$magic_link_url" class="button">{$t_button}</a>
        </div>

        <div class="link-box">
            <p><strong>{$t_alt_label}</strong> {$t_alt_text}</p>
            <a href="$magic_link_url">$magic_link_url</a>
        </div>

        <div class="security-notice">
            <h3>{$t_security_title}</h3>
            <ul>
                <li>{$t_security_single_use}</li>
                <li>{$t_security_expiry}</li>
                <li>{$t_security_no_share}</li>
                <li>{$t_security_ignore}</li>
                <li>{$t_security_no_reuse}</li>
            </ul>
        </div>

        <div class="no-reply-notice">
            <strong>{$t_no_reply_title}</strong> {$t_no_reply_text}
        </div>

        <div class="footer">
            <p>{$t_footer_regards}</p>
            <p><strong>{$t_footer_team}</strong></p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">{$t_footer_sent_to}</p>
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
function send_role_change_email($to, $changes, $changed_by, $language = null) {
    // Load i18n translations (with user email for preference lookup)
    email_load_i18n($language, $to);

    $username = explode('@', $to)[0];
    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $subject = __('emails.role_change.subject', ['app_name' => $app_name]);

    // Build change summary
    $change_items = [];

    if (isset($changes['role'])) {
        $old_role = strtoupper($changes['role']['old']);
        $new_role = strtoupper($changes['role']['new']);
        $role_label = __('emails.role_change.role_label');
        $change_items[] = "<strong>{$role_label}</strong> {$old_role} → {$new_role}";
    }

    if (isset($changes['powereditor'])) {
        $old_pe = $changes['powereditor']['old'] ? __('emails.role_change.yes') : __('emails.role_change.no');
        $new_pe = $changes['powereditor']['new'] ? __('emails.role_change.yes') : __('emails.role_change.no');
        $pe_label = __('emails.role_change.powereditor_label');
        $change_items[] = "<strong>{$pe_label}</strong> {$old_pe} → {$new_pe}";
    }

    if (isset($changes['alliances'])) {
        $old_alliances = implode(', ', $changes['alliances']['old']);
        $new_alliances = implode(', ', $changes['alliances']['new']);
        if (empty($old_alliances)) $old_alliances = __('emails.role_change.none');
        if (empty($new_alliances)) $new_alliances = __('emails.role_change.none');
        $alliances_label = __('emails.role_change.alliances_label');
        $change_items[] = "<strong>{$alliances_label}</strong> {$old_alliances} → {$new_alliances}";
    }

    $changes_html = implode('<br>', $change_items);

    // Get all translated strings
    $t_badge = __('emails.role_change.badge');
    $t_greeting = __('emails.role_change.greeting', ['username' => $username]);
    $t_intro = __('emails.role_change.intro', ['app_name' => $app_name]);
    $t_changes_title = __('emails.role_change.changes_title');
    $t_changed_by = __('emails.role_change.changed_by_label');
    $t_date = __('emails.role_change.date_label');
    $t_questions = __('emails.role_change.questions_text');
    $t_access = __('emails.role_change.access_text');
    $t_footer_notification = __('emails.role_change.footer_notification', ['app_name' => $app_name]);
    $t_footer_unexpected = __('emails.role_change.footer_unexpected');

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
            <span class="header-badge">{$t_badge}</span>
        </div>

        <div class="content">
            <p>{$t_greeting}</p>

            <p>{$t_intro}</p>

            <div class="changes-box">
                <h3>{$t_changes_title}</h3>
                <p>{$changes_html}</p>
            </div>

            <div class="info-box">
                <p><strong>{$t_changed_by}</strong> {$changed_by}</p>
                <p><strong>{$t_date}</strong> {date('F j, Y \a\t g:i A T')}</p>
            </div>

            <p>{$t_questions}</p>

            <p>{$t_access}</p>
        </div>

        <div class="footer">
            <p>{$t_footer_notification}</p>
            <p>{$t_footer_unexpected}</p>
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
function send_test_email($to, $language = null) {
    // Load i18n translations (with user email for preference lookup)
    email_load_i18n($language, $to);

    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $subject = __('emails.test.subject', ['app_name' => $app_name]);

    // Get all translated strings
    $t_title = __('emails.test.title');
    $t_badge = __('emails.test.badge');
    $t_congratulations = __('emails.test.congratulations');
    $t_config_title = __('emails.test.config_title');
    $t_config_smtp = __('emails.test.config_smtp_server');
    $t_config_port = __('emails.test.config_port');
    $t_config_auth = __('emails.test.config_auth');
    $t_config_from = __('emails.test.config_from');
    $t_next_steps_title = __('emails.test.next_steps_title');
    $t_next_steps_text = __('emails.test.next_steps_text');
    $t_no_reply_title = __('emails.test.no_reply_title');
    $t_no_reply_text = __('emails.test.no_reply_text');
    $t_footer_regards = __('emails.test.footer_regards');
    $t_footer_team = __('emails.test.footer_team', ['app_name' => $app_name]);
    $t_footer_sent_to = __('emails.test.footer_sent_to', ['email' => $to]);

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
            <h1>{$t_title}</h1>
            <div class="success-badge">{$t_badge}</div>
        </div>

        <div class="content">
            <p>{$t_congratulations}</p>
        </div>

        <div class="config-box">
            <h3>{$t_config_title}</h3>
            <ul>
                <li>{$t_config_smtp}</li>
                <li>{$t_config_port}</li>
                <li>{$t_config_auth}</li>
                <li>{$t_config_from}</li>
            </ul>
        </div>

        <div class="content">
            <h3>{$t_next_steps_title}</h3>
            <p>{$t_next_steps_text}</p>
        </div>

        <div class="no-reply-notice">
            <strong>{$t_no_reply_title}</strong> {$t_no_reply_text}
        </div>

        <div class="footer">
            <p>{$t_footer_regards}<br><strong>{$t_footer_team}</strong></p>
            <p style="font-size: 12px; color: #999;">{$t_footer_sent_to}</p>
        </div>
    </div>
</body>
</html>
EOT;

    return send_email($to, $subject, $html_body, true);
}

/**
 * Send council rotation regeneration notification email
 *
 * @param string $to Recipient email address
 * @param array $stats Regeneration statistics
 * @param string $regenerated_by Email of admin/president who regenerated
 * @return bool Success status
 */
function send_council_rotation_notification($to, $stats, $regenerated_by, $language = null) {
    // Load i18n translations (with user email for preference lookup)
    email_load_i18n($language, $to);

    $app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
    $subject = __('emails.council_rotation.subject', ['app_name' => $app_name]);

    $next_rotation_date = $stats['next_rotation_date'] ?? 'Unknown';
    $next_rotation_week = $stats['next_rotation_week'] ?? 'Unknown';
    $weeks_generated = $stats['new_weeks_generated'] ?? 0;
    $rotation_counts = $stats['future_rotation_counts'] ?? [];

    // Build rotation distribution table
    $distribution_rows = '';
    foreach ($rotation_counts as $alliance_tag => $count) {
        $distribution_rows .= "<tr><td style='padding:8px;border-bottom:1px solid #e0e0e0;'>{$alliance_tag}</td><td style='padding:8px;border-bottom:1px solid #e0e0e0;text-align:center;'>{$count}</td></tr>";
    }

    // Get all translated strings
    $t_title = __('emails.council_rotation.title');
    $t_badge = __('emails.council_rotation.badge');
    $t_greeting = __('emails.council_rotation.greeting');
    $t_intro = __('emails.council_rotation.intro', ['weeks' => $weeks_generated]);
    $t_next_rotation_title = __('emails.council_rotation.next_rotation_title');
    $t_week_label = __('emails.council_rotation.week_label');
    $t_date_label = __('emails.council_rotation.date_label');
    $t_regenerated_by = __('emails.council_rotation.regenerated_by_label');
    $t_distribution_title = __('emails.council_rotation.distribution_title');
    $t_table_alliance = __('emails.council_rotation.table_alliance');
    $t_table_rotations = __('emails.council_rotation.table_rotations', ['weeks' => $weeks_generated]);
    $t_notice_title = __('emails.council_rotation.notice_title');
    $t_notice_text = __('emails.council_rotation.notice_text');
    $t_button_text = __('emails.council_rotation.button_text');
    $t_footer_notification = __('emails.council_rotation.footer_notification', ['app_name' => $app_name]);
    $t_footer_no_reply = __('emails.council_rotation.footer_no_reply');

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
            font-size: 24px;
        }
        .badge {
            display: inline-block;
            background-color: #667eea;
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
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .stats {
            margin: 20px 0;
        }
        .stat-item {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .stat-label {
            font-weight: 600;
            color: #555;
        }
        .stat-value {
            color: #667eea;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background-color: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$t_title}</h1>
            <div class="badge">{$t_badge}</div>
        </div>

        <div class="content">
            <p>{$t_greeting}</p>

            <p>{$t_intro}</p>

            <div class="info-box">
                <h3>{$t_next_rotation_title}</h3>
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-label">{$t_week_label}</span>
                        <span class="stat-value">#{$next_rotation_week}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">{$t_date_label}</span>
                        <span class="stat-value">{$next_rotation_date}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">{$t_regenerated_by}</span>
                        <span class="stat-value">{$regenerated_by}</span>
                    </div>
                </div>
            </div>

            <h3>{$t_distribution_title}</h3>
            <table>
                <thead>
                    <tr>
                        <th>{$t_table_alliance}</th>
                        <th style="text-align:center;">{$t_table_rotations}</th>
                    </tr>
                </thead>
                <tbody>
                    {$distribution_rows}
                </tbody>
            </table>

            <div class="notice">
                <strong>{$t_notice_title}</strong> {$t_notice_text}
            </div>

            <div style="text-align: center;">
                <a href="https://www.lastwar1586.online/admin/council_rotation.php" class="button">{$t_button_text}</a>
            </div>
        </div>

        <div class="footer">
            <p>{$t_footer_notification}</p>
            <p style="color: #999; font-size: 12px;">{$t_footer_no_reply}</p>
        </div>
    </div>
</body>
</html>
EOT;

    return send_email($to, $subject, $html_body, true);
}
?>
