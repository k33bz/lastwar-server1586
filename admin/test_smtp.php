<?php
/**
 * SMTP Test Script
 * Quick test to verify email configuration is working
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

// Get test email from command line or use default
$test_email = $argv[1] ?? 'matthewkastro@gmail.com';

echo "Testing SMTP configuration...\n";
echo "Sending test email to: $test_email\n\n";

try {
    $result = send_test_email($test_email);
    
    if ($result) {
        echo "✅ SUCCESS: Test email sent successfully!\n";
        echo "Check your inbox at: $test_email\n";
    } else {
        echo "❌ FAILED: Test email could not be sent\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\nSMTP Configuration:\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Port: " . SMTP_PORT . "\n";
echo "User: " . SMTP_USER . "\n";
echo "From: " . SMTP_FROM . "\n";
echo "From Name: " . SMTP_FROM_NAME . "\n";
?>