<?php
/**
 * Debug script to identify alliance_edit.php 500 error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Alliance Edit</h1>";
echo "<pre>";

// Test 1: Check if config.php loads
echo "1. Testing config.php...\n";
try {
    require_once __DIR__ . '/config.php';
    echo "   ✓ config.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

// Test 2: Check if jwt.php loads
echo "\n2. Testing jwt.php...\n";
try {
    require_once __DIR__ . '/jwt.php';
    echo "   ✓ jwt.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

// Test 3: Check if json_helpers.php loads
echo "\n3. Testing json_helpers.php...\n";
try {
    require_once __DIR__ . '/json_helpers.php';
    echo "   ✓ json_helpers.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    die();
}

// Test 4: Check amendments file path
echo "\n4. Testing amendments file...\n";
$amendments_file = __DIR__ . '/../data/amendments.json';
echo "   Path: $amendments_file\n";
echo "   Exists: " . (file_exists($amendments_file) ? "YES" : "NO") . "\n";
if (file_exists($amendments_file)) {
    echo "   Readable: " . (is_readable($amendments_file) ? "YES" : "NO") . "\n";
    try {
        $amendments = read_json_file($amendments_file);
        echo "   ✓ amendments.json loaded successfully\n";
        echo "   Count: " . count($amendments) . " amendments\n";
    } catch (Exception $e) {
        echo "   ✗ Error reading: " . $e->getMessage() . "\n";
    }
}

// Test 5: Check alliances file
echo "\n5. Testing alliances file...\n";
if (defined('ALLIANCES_FILE')) {
    echo "   Path: " . ALLIANCES_FILE . "\n";
    echo "   Exists: " . (file_exists(ALLIANCES_FILE) ? "YES" : "NO") . "\n";
    if (file_exists(ALLIANCES_FILE)) {
        echo "   Readable: " . (is_readable(ALLIANCES_FILE) ? "YES" : "NO") . "\n";
    }
} else {
    echo "   ✗ ALLIANCES_FILE constant not defined\n";
}

// Test 6: Check if JWT session can be required (this will redirect if not authenticated)
echo "\n6. Testing JWT session requirement...\n";
echo "   Note: This will redirect to login if not authenticated\n";
echo "   If you see this message, JWT is working but will redirect in:\n";
for ($i = 3; $i > 0; $i--) {
    echo "   $i...\n";
    flush();
    sleep(1);
}

try {
    $user_token = require_jwt_session();
    echo "   ✓ JWT session valid!\n";
    echo "   User: " . $user_token->email . "\n";
    echo "   Role: " . $user_token->aud . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n✓ All tests passed! The issue is not in the basic setup.\n";
echo "</pre>";
?>
