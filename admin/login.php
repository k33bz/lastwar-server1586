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
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

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

$error_messages = [
    'no_session' => 'Please log in to access this page.',
    'expired' => 'Your session has expired. Please log in again.',
    'revoked' => 'Your session has been revoked. Please log in again.',
    'invalid' => 'Invalid session. Please log in again.',
    'unknown_email' => 'Email address not recognized or not authorized.',
    'send_failed' => 'Failed to send magic link. Please try again.',
];

$success_messages = [
    'sent' => 'Magic link sent! Check your email and click the link to log in.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Last War 1586</title>
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
        <h1>Last War 1586</h1>
        <p class="subtitle">Alliance Admin Portal</p>

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
                <label for="email">Alliance Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="your.email@example.com"
                    autofocus
                >
            </div>

            <button type="submit">Send Magic Link</button>
        </form>

        <div class="info-box">
            <strong>How it works:</strong>
            <ul>
                <li>Enter your alliance email address</li>
                <li>Receive a secure login link via email</li>
                <li>Click the link to access your dashboard</li>
                <li>Links expire after 10 minutes</li>
            </ul>
        </div>
    </div>
</body>
</html>
