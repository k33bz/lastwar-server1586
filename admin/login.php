<?php
/**
 * Login Page - Email-Based Magic Link Authentication
 *
 * Displays login form for users to request magic link
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/includes/rate_limiter.php';
require_once __DIR__ . '/includes/i18n.php';

// Start session for i18n language preference
session_start();

// Initialize i18n
i18n_handle_language_change();
i18n_init();

// Handle magic link authentication
if (isset($_GET['magic'])) {
    // Rate limiting: 10 attempts per minute (more lenient for link clicks)
    rate_limit_check('magic_link', 10, 60);
    $magic_token = $_GET['magic'];
    $magic_links_file = __DIR__ . '/magic_links.json';
    
    if (file_exists($magic_links_file)) {
        $magic_links = json_decode(file_get_contents($magic_links_file), true) ?? [];
        
        if (isset($magic_links[$magic_token])) {
            $link_data = $magic_links[$magic_token];
            
            // Check if link is still valid
            if ($link_data['expires_at'] > time() && !$link_data['used']) {
                // Mark link as used
                $magic_links[$magic_token]['used'] = true;
                file_put_contents($magic_links_file, json_encode($magic_links, JSON_PRETTY_PRINT));
                
                // Get user data
                require_once 'json_helpers.php';
                $user_data = get_user_by_email($link_data['email']);
                
                if ($user_data) {
                    // Create JWT token
                    $jwt_payload = [
                        'sub' => $user_data['email'],
                        'aud' => $user_data['role'],
                        'alliances' => $user_data['alliances'],
                        'powereditor' => $user_data['powereditor'] ?? false,
                        'iat' => time(),
                        'exp' => time() + (8 * 60 * 60), // 8 hours
                        'jti' => bin2hex(random_bytes(16))
                    ];
                    
                    $jwt_token = create_jwt($jwt_payload);
                    set_session_cookie($jwt_token);
                    
                    // Log the login
                    require_once 'audit_logger.php';
                    log_audit_event('magic_link_login', $user_data['email'], [
                        'created_by' => $link_data['created_by'],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'unknown_email';
                }
            } else {
                $error = 'expired';
            }
        } else {
            $error = 'invalid';
        }
    } else {
        $error = 'invalid';
    }
}

// If already logged in, redirect to dashboard
if (isset($_COOKIE['jwt'])) {
    try {
        $token = decode_jwt($_COOKIE['jwt']);
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        // Invalid token, clear and continue to login
        clear_session_cookie();
    }
}

// Get error/success messages from query string
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

// Error and success messages are now handled via i18n
$error_messages = [
    'no_session' => __('login.errors.no_session'),
    'expired' => __('login.errors.expired'),
    'revoked' => __('login.errors.revoked'),
    'invalid' => __('login.errors.invalid'),
    'key_rotated' => __('login.errors.key_rotated'),
    'unknown_email' => __('login.errors.unknown_email'),
    'send_failed' => __('login.errors.send_failed'),
];

$success_messages = [
    'sent' => __('login.success.sent'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login.page_title'); ?> - <?php echo $_ENV['APP_NAME'] ?? 'Last War 1586'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 25px;
            border-radius: 5px;
            font-size: 13px;
            color: #555;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .info-box ul {
            margin-left: 20px;
        }

        .info-box li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><?php echo $_ENV['APP_NAME'] ?? 'Last War 1586'; ?></h1>
        <p class="subtitle"><?php echo __('login.subtitle'); ?></p>

        <div style="text-align: right; margin-bottom: 10px;">
            <?php echo i18n_render_language_switcher(); ?>
        </div>

        <?php if ($error && isset($error_messages[$error])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error_messages[$error]) ?>
            </div>
        <?php endif; ?>

        <?php if ($success && isset($success_messages[$success])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_messages[$success]) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="send_magic_link.php">
            <div class="form-group">
                <label for="email"><?php echo __('login.form.email_label'); ?></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="<?php echo __('login.form.email_placeholder'); ?>"
                    autofocus
                >
            </div>

            <!-- Pass current language selection to magic link processor -->
            <input type="hidden" name="language" value="<?php echo htmlspecialchars(i18n_get_current_language()); ?>">

            <button type="submit"><?php echo __('login.form.submit_button'); ?></button>
        </form>

        <div class="info-box">
            <strong><?php echo __('login.info.title'); ?></strong>
            <ul>
                <?php foreach (__('login.info.steps') as $step): ?>
                    <li><?php echo $step; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
