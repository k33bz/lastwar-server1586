<?php
/**
 * Compare Test Email vs Magic Link Email
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$email = 'matthewkastro@gmail.com';
$test_magic_link = 'http://localhost:8080/admin/callback.php?token=test123456789';

echo "Testing both email functions...\n\n";

// Test 1: Regular test email
echo "1. Testing send_test_email()...\n";
try {
    $result1 = send_test_email($email);
    echo "Result: " . ($result1 ? "SUCCESS" : "FAILED") . "\n\n";
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n\n";
}

// Test 2: Magic link email
echo "2. Testing send_magic_link_email()...\n";
try {
    $result2 = send_magic_link_email($email, $test_magic_link);
    echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . "\n\n";
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

echo "Comparison complete.\n";
?>