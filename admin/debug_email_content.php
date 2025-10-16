<?php
/**
 * Debug Email Content
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';

$email = 'matthewkastro@gmail.com';
$test_magic_link = 'http://localhost:8080/admin/callback.php?token=test123456789';

// Extract the email content generation logic
$username = explode('@', $email)[0];
$app_name = $_ENV['APP_NAME'] ?? 'Last War 1586 Admin';
$app_name_short = $_ENV['APP_NAME'] ?? 'Last War 1586';
$subject = 'Your Login Link for ' . $app_name;

echo "=== MAGIC LINK EMAIL DEBUG ===\n";
echo "To: $email\n";
echo "Subject: $subject\n";
echo "Username: $username\n";
echo "App Name: $app_name\n";
echo "App Name Short: $app_name_short\n";
echo "Magic Link: $test_magic_link\n\n";

// Check if there are any issues with the heredoc
echo "Testing heredoc content generation...\n";

$html_body = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; }
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
            <a href="$test_magic_link" class="button">🚀 Access Admin Dashboard</a>
        </div>
        <div class="footer">
            <p>Best regards,</p>
            <p><strong>The $app_name Team</strong></p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">This email was sent to $email</p>
        </div>
    </div>
</body>
</html>
EOT;

echo "HTML content length: " . strlen($html_body) . " characters\n";
echo "First 200 characters:\n" . substr($html_body, 0, 200) . "...\n\n";

// Test if there are any PHP syntax issues
if (strpos($html_body, 'Parse error') !== false) {
    echo "❌ SYNTAX ERROR detected in HTML content!\n";
} else {
    echo "✅ HTML content generated successfully\n";
}
?>