<?php
/**
 * Manual Magic Link Generator for Admins
 *
 * Allows admins to generate magic links manually and copy them
 * for sending via other channels (Discord, SMS, etc.) when email fails
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/mailer.php';

// Require admin session
$user_token = require_admin_session();

$generated_link = null;
$error = null;
$success_message = null;
$target_email = '';

// Pre-fill email from URL parameter if provided
if (isset($_GET['email'])) {
    $target_email = strtolower(trim($_GET['email']));
}

// Handle email send request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email']) && isset($_POST['magic_link'])) {
    $target_email = strtolower(trim($_POST['target_email']));
    $generated_link = $_POST['magic_link'];

    if (send_magic_link_email($target_email, $generated_link)) {
        $success_message = "Magic link email sent successfully to $target_email";
    } else {
        $error = "Failed to send email. You can still copy the link manually.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['send_email'])) {
    $target_email = strtolower(trim($_POST['email']));

    // Validate email
    if (!filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address format.";
    } else {
        // Find user
        $user = get_user_by_email($target_email);

        if (!$user) {
            $error = "User not found: $target_email";
        } else {
            // Generate magic link token
            $magic_token = create_magic_link_token($target_email, $user);
            $generated_link = APP_URL . '/admin/callback.php?token=' . $magic_token;
        }
    }
}

// Get all users for dropdown
$users_data = read_json_file(USERS_FILE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Magic Link - <?php echo $_ENV['APP_NAME'] ?? 'Last War 1586 Admin'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; font-size: 24px; }
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        .form-group select,
        .form-group input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
        }
        .form-group select:focus,
        .form-group input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        .link-display {
            background: white;
            border: 2px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #333;
            margin: 15px 0;
        }
        .copy-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-right: 10px;
        }
        .copy-btn:hover {
            background: #218838;
        }
        .email-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .email-btn:hover {
            background: #2980b9;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #721c24;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #856404;
        }
        .warning-box strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Generate Magic Link</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="section">
            <h2>Manual Magic Link Generator</h2>

            <div class="info-box">
                <strong>ℹ️ About This Tool</strong><br>
                Use this tool when email delivery fails for specific users. You can generate a magic link manually and send it via Discord, SMS, or other messaging platforms.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="email">User Email:</label>
                    <input type="email"
                           name="email"
                           id="email"
                           placeholder="user@example.com"
                           value="<?= htmlspecialchars($target_email) ?>"
                           required
                           list="user-emails">
                    <datalist id="user-emails">
                        <?php foreach ($users_data['users'] as $user): ?>
                            <option value="<?= htmlspecialchars($user['email']) ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Start typing to see suggestions, or enter any email address
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">🔑 Generate Magic Link</button>
            </form>

            <?php if ($success_message): ?>
                <div class="success-box">
                    <strong>✅ <?= htmlspecialchars($success_message) ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-box">
                    <strong>❌ Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($generated_link): ?>
                <div class="success-box">
                    <h3>✅ Magic Link Generated Successfully</h3>
                    <p><strong>For user:</strong> <?= htmlspecialchars($target_email) ?></p>

                    <div class="link-display" id="magicLink"><?= htmlspecialchars($generated_link) ?></div>

                    <div class="button-group">
                        <button class="copy-btn" onclick="copyLink()">📋 Copy Link to Clipboard</button>
                        <form method="POST" style="display: inline; margin: 0;">
                            <input type="hidden" name="target_email" value="<?= htmlspecialchars($target_email) ?>">
                            <input type="hidden" name="magic_link" value="<?= htmlspecialchars($generated_link) ?>">
                            <button type="submit" name="send_email" class="email-btn">📧 Email Link to User</button>
                        </form>
                    </div>

                    <div class="warning-box" style="margin-top: 20px;">
                        <strong>⚠️ Important Security Notes:</strong>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>This link expires in <strong>10 minutes</strong></li>
                            <li>This link is <strong>single-use only</strong></li>
                            <li>Send this link via a <strong>secure channel</strong> (Discord DM, encrypted messaging)</li>
                            <li>Never share this link publicly or in group chats</li>
                            <li>The user will be logged in immediately when they click this link</li>
                        </ul>
                    </div>
                </div>

                <script>
                function copyLink() {
                    const linkText = document.getElementById('magicLink').textContent;
                    navigator.clipboard.writeText(linkText).then(() => {
                        const btn = event.target;
                        const originalText = btn.textContent;
                        btn.textContent = '✅ Copied!';
                        btn.style.background = '#218838';
                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.style.background = '#28a745';
                        }, 2000);
                    }).catch(err => {
                        alert('Failed to copy. Please select and copy manually.');
                    });
                }
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
