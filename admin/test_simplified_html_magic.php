<?php
/**
 * Test Simplified HTML Magic Link Email
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$email = 'matthewkastro@gmail.com';
$test_magic_link = 'http://localhost:8080/admin/callback.php?token=test123456789';

echo "Testing simplified HTML magic link email...\n\n";

$subject = 'Your Login Link for Last War 1586 Admin';

// Simplified HTML version
$simple_html = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login Link</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f9f9f9; padding: 30px; border-radius: 8px;">
        <h1 style="color: #333;">🔐 Last War 1586 Admin</h1>
        <p><strong>Hello matthewkastro,</strong></p>
        <p>You requested access to the Last War 1586 Admin Dashboard.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="$test_magic_link" 
               style="display: inline-block; padding: 15px 30px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
               🚀 Access Admin Dashboard
            </a>
        </div>
        
        <p style="font-size: 12px; color: #666;">
            Alternative: Copy this link: <br>
            <a href="$test_magic_link">$test_magic_link</a>
        </p>
        
        <p style="font-size: 12px; color: #666;">
            This link expires in 10 minutes and can only be used once.
        </p>
        
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
        
        <p style="font-size: 12px; color: #666;">
            Best regards,<br>
            The Last War 1586 Admin Team
        </p>
    </div>
</body>
</html>
EOT;

try {
    $result = send_email($email, $subject, $simple_html, true); // HTML
    echo "Simplified HTML magic link email result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    
    if ($result) {
        echo "✅ Simplified HTML magic link email sent to $email\n";
        echo "Check your inbox for the simplified HTML version\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
?>