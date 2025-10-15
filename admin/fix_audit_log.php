<?php
/**
 * Fix Empty Audit Log File
 *
 * Simple script to fix the empty audit_log.json file
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';

$audit_file = __DIR__ . '/audit_log.json';

echo "<pre>\n";
echo "Fixing audit_log.json...\n\n";

// Create proper initial structure
$initial_data = ['logs' => []];
$json = json_encode($initial_data, JSON_PRETTY_PRINT);

// Write directly
$result = file_put_contents($audit_file, $json);

if ($result !== false) {
    echo "[OK] audit_log.json initialized successfully\n";
    echo "Written: $result bytes\n";
    echo "Content: $json\n";
} else {
    echo "[ERROR] Failed to write audit_log.json\n";
}

// Verify
$contents = file_get_contents($audit_file);
$verify = json_decode($contents, true);

echo "\nVerification:\n";
echo "File size: " . filesize($audit_file) . " bytes\n";
echo "Valid JSON: " . (json_last_error() === JSON_ERROR_NONE ? "Yes" : "No") . "\n";
echo "Logs array exists: " . (isset($verify['logs']) ? "Yes" : "No") . "\n";

echo "\n[OK] Fix complete! You can now log in and audit logging will work.\n";
echo "</pre>\n";
?>
