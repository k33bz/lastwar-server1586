<?php
/**
 * Initialize Audit Logging System
 *
 * Run this once to set up audit logging files and directories
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

// Initialize audit log file
$audit_log_file = __DIR__ . '/audit_log.json';
if (!file_exists($audit_log_file)) {
    $initial_data = ['logs' => []];
    file_put_contents($audit_log_file, json_encode($initial_data, JSON_PRETTY_PRINT));
    chmod($audit_log_file, 0644);
    echo "✅ Created audit_log.json\n";
} else {
    echo "✅ audit_log.json already exists\n";
}

// Initialize backup directory
$backup_dir = __DIR__ . '/backups/alliances';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    echo "✅ Created backup directory: $backup_dir\n";
} else {
    echo "✅ Backup directory already exists\n";
}

// Test audit logging
$test_result = log_audit_event('system_initialization', 'system', [
    'message' => 'Audit logging system initialized',
    'version' => '1.0.0',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
]);

if ($test_result) {
    echo "✅ Test audit log entry created successfully\n";
} else {
    echo "❌ Failed to create test audit log entry\n";
}

// Display status
echo "\n📊 Audit System Status:\n";
echo "Audit log file: " . $audit_log_file . "\n";
echo "Backup directory: " . $backup_dir . "\n";
echo "Log file size: " . filesize($audit_log_file) . " bytes\n";

// Read and display logs
$data = read_json_file($audit_log_file);
echo "Current log entries: " . count($data['logs'] ?? []) . "\n";

if (!empty($data['logs'])) {
    echo "\n📝 Most recent log entry:\n";
    echo json_encode($data['logs'][0], JSON_PRETTY_PRINT) . "\n";
}

echo "\n✅ Audit logging system is ready!\n";
?>
