<?php
/**
 * SMTP Test Script
 *
 * Quick test to verify email configuration is working
 * Run: php test-smtp.php your-email@example.com
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

// Check command line argument
if ($argc < 2) {
    echo "Usage: php test-smtp.php <recipient-email>\n";
    echo "Example: php test-smtp.php admin@example.com\n";
    exit(1);
}

$recipient = $argv[1];

// Validate email
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address: $recipient\n";
    exit(1);
}

echo "Testing SMTP configuration...\n";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP User: " . SMTP_USER . "\n";
echo "Sending to: $recipient\n";
echo "\n";

try {
    $result = send_test_email($recipient);

    if ($result) {
        echo "✅ SUCCESS! Test email sent successfully.\n";
        echo "Check your inbox at: $recipient\n";
        exit(0);
    } else {
        echo "❌ FAILED: Email could not be sent.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
