<?php
/**
 * Test Audit Logger Initialization
 * Simple diagnostic to identify the issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";
echo "=== Audit Logger Initialization Test ===\n\n";

// Step 1: Test config.php
echo "1. Loading config.php...\n";
try {
    define('ADMIN_INIT', true);
    require_once __DIR__ . '/config.php';
    echo "   [OK] config.php loaded\n";
} catch (Exception $e) {
    echo "   [ERROR] config.php failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Test json_helpers.php
echo "\n2. Loading json_helpers.php...\n";
try {
    require_once __DIR__ . '/json_helpers.php';
    echo "   [OK] json_helpers.php loaded\n";
    echo "   Functions available: ";
    echo function_exists('read_json_file') ? "read_json_file " : "";
    echo function_exists('write_json_file') ? "write_json_file " : "";
    echo function_exists('update_json_file') ? "update_json_file " : "";
    echo "\n";
} catch (Exception $e) {
    echo "   [ERROR] json_helpers.php failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Test audit_logger.php
echo "\n3. Loading audit_logger.php...\n";
try {
    require_once __DIR__ . '/audit_logger.php';
    echo "   [OK] audit_logger.php loaded\n";
    echo "   Functions available: ";
    echo function_exists('log_audit_event') ? "log_audit_event " : "";
    echo function_exists('backup_alliances') ? "backup_alliances " : "";
    echo function_exists('log_login') ? "log_login " : "";
    echo "\n";
} catch (Exception $e) {
    echo "   [ERROR] audit_logger.php failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Check audit log file
echo "\n4. Checking audit_log.json...\n";
$audit_file = __DIR__ . '/audit_log.json';
if (file_exists($audit_file)) {
    echo "   [OK] File exists\n";
    echo "   Size: " . filesize($audit_file) . " bytes\n";
    echo "   Readable: " . (is_readable($audit_file) ? "Yes" : "No") . "\n";
    echo "   Writable: " . (is_writable($audit_file) ? "Yes" : "No") . "\n";

    $data = json_decode(file_get_contents($audit_file), true);
    echo "   Entries: " . count($data['logs'] ?? []) . "\n";
} else {
    echo "   [WARN] File does not exist yet\n";
}

// Step 5: Check backup directory
echo "\n5. Checking backup directory...\n";
$backup_dir = __DIR__ . '/backups/alliances';
if (is_dir($backup_dir)) {
    echo "   [OK] Directory exists\n";
    echo "   Writable: " . (is_writable($backup_dir) ? "Yes" : "No") . "\n";
} else {
    echo "   [WARN] Directory does not exist yet\n";
}

// Step 6: Test creating a log entry
echo "\n6. Testing log_audit_event()...\n";
try {
    $result = log_audit_event('test', 'test@example.com', ['test' => true]);
    if ($result) {
        echo "   [OK] Test log entry created\n";
    } else {
        echo "   [ERROR] log_audit_event() returned false\n";
    }
} catch (Exception $e) {
    echo "   [ERROR] log_audit_event() threw exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "</pre>\n";
?>
