<?php
/**
 * Magic Link Email Test Script
 * Test the specific magic link email function
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

// Get test email from command line or use default
$test_email = $argv[1] ?? 'matthewkastro@gmail.com';
$test_magic_link = 'http://localhost:8080/admin/login.php?magic=test123456789';

echo "Testing Magic Link Email...\n";
echo "Sending to: $test_email\n";
echo "Magic Link: $test_magic_link\n\n";

try {
    $result = send_magic_link_email($test_email, $test_magic_link);
    
    if ($result) {
        echo "✅ SUCCESS: Magic link email sent successfully!\n";
        echo "Check your inbox at: $test_email\n";
    } else {
        echo "❌ FAILED: Magic link email could not be sent\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>