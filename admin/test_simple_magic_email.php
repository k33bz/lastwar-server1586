<?php
/**
 * Test Simple Magic Link Email
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$email = 'matthewkastro@gmail.com';
$test_magic_link = 'http://localhost:8080/admin/callback.php?token=test123456789';

echo "Testing simplified magic link email...\n\n";

// Create a simple version of the magic link email
$subject = 'Your Login Link for Last War 1586 Admin';
$simple_body = "Hello,

You requested access to the Last War 1586 Admin Dashboard.

Click this link to log in:
$test_magic_link

This link expires in 10 minutes.

Best regards,
The Last War 1586 Admin Team";

try {
    $result = send_email($email, $subject, $simple_body, false); // Plain text
    echo "Simple magic link email result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    
    if ($result) {
        echo "✅ Simple magic link email sent to $email\n";
        echo "Check your inbox for the plain text version\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
?>