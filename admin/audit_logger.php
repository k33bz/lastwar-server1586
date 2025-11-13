<?php
/**
 * Audit Logging System
 *
 * Tracks all admin actions including logins, data changes, and user management
 * with real-time tail-like viewing support
 *
 * @version 1.0.0
 * @date 2025-10-15
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation
 *                       - Login tracking (user, IP, user-agent, timestamp)
 *                       - Data change tracking (who, what, when, diff)
 *                       - Alliance backup system on every edit
 *                       - Real-time log viewer support
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/includes/rate_limiter.php';

// Audit log file path
define('AUDIT_LOG_FILE', __DIR__ . '/audit_log.json');

// Alliance backup directory
define('BACKUP_DIR', __DIR__ . '/backups/alliances');

/**
 * Log an audit event
 *
 * @param string $action Action type (login, logout, edit_alliance, edit_user, etc.)
 * @param string $user_email User who performed the action
 * @param array $details Additional details about the action
 * @param string|null $ip_address IP address (auto-detected if null)
 * @return bool Success status
 */
function log_audit_event($action, $user_email, $details = [], $ip_address = null) {
    try {
        // Get IP address if not provided
        if ($ip_address === null) {
            $ip_address = get_client_ip();
        }

        // Create log entry
        $log_entry = [
            'id' => generate_log_id(),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'action' => $action,
            'user' => $user_email,
            'ip' => $ip_address,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];

        // Append to audit log
        return update_json_file(AUDIT_LOG_FILE, function(&$data) use ($log_entry) {
            if (!isset($data['logs'])) {
                $data['logs'] = [];
            }

            // Add new log entry at the beginning (most recent first)
            array_unshift($data['logs'], $log_entry);

            // Keep only last 10,000 entries to prevent file from growing too large
            if (count($data['logs']) > 10000) {
                $data['logs'] = array_slice($data['logs'], 0, 10000);
            }

            return true;
        });
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate unique log ID
 *
 * @return string Unique log identifier
 */
function generate_log_id() {
    return uniqid('log_', true);
}

/**
 * Create backup of alliances.json before editing
 *
 * @param array $data Current alliance data
 * @param string $user_email User making the change
 * @param string $reason Reason for backup (e.g., "power_edit", "add_alliance")
 * @return string|false Backup file path or false on failure
 */
function backup_alliances($data, $user_email, $reason = 'edit') {
    try {
        // Create backup directory if it doesn't exist
        if (!is_dir(BACKUP_DIR)) {
            mkdir(BACKUP_DIR, 0755, true);
        }

        // Generate backup filename with timestamp
        $timestamp = gmdate('Y-m-d_H-i-s');
        $backup_filename = "{$timestamp}_{$reason}_by_" . sanitize_filename($user_email) . ".json";
        $backup_path = BACKUP_DIR . '/' . $backup_filename;

        // Save backup with metadata
        $backup_data = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'user' => $user_email,
            'reason' => $reason,
            'ip' => get_client_ip(),
            'data' => $data
        ];

        if (file_put_contents($backup_path, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            // Set restrictive permissions (only owner can read/write)
            @chmod($backup_path, 0600);
            return $backup_path;
        }

        return false;
    } catch (Exception $e) {
        error_log("Alliance backup failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize filename for safe filesystem storage
 *
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitize_filename($filename) {
    // Remove @domain from email for shorter filenames
    $filename = preg_replace('/@.*$/', '', $filename);
    // Replace non-alphanumeric with underscore
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
}

/**
 * Get recent audit logs for display
 *
 * @param int $limit Number of logs to return
 * @return array Array of recent log entries
 */
function get_recent_audit_logs($limit = 10) {
    try {
        $data = read_json_file(AUDIT_LOG_FILE);
        $logs = $data['logs'] ?? [];

        // Return most recent logs
        return array_slice($logs, 0, $limit);
    } catch (Exception $e) {
        error_log("Failed to get recent audit logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get audit logs with filtering support
 *
 * @param array $filters Filters to apply (user, action)
 * @param int $limit Number of logs to return
 * @param int $offset Offset for pagination
 * @return array Array of filtered log entries
 */
function get_audit_logs($filters = [], $limit = 100, $offset = 0) {
    $audit_file = AUDIT_LOG_FILE;
    $logs = [];

    if (file_exists($audit_file)) {
        $data = json_decode(file_get_contents($audit_file), true);
        $raw_logs = $data['logs'] ?? [];

        // Normalize log entries to handle inconsistent field names
        foreach ($raw_logs as $log) {
            $normalized_log = [
                'id' => $log['id'] ?? 'unknown',
                'timestamp' => $log['timestamp'] ?? 'unknown',
                'action' => $log['action'] ?? 'unknown',
                'user' => $log['user'] ?? $log['user_email'] ?? 'unknown',
                'ip' => $log['ip'] ?? $log['ip_address'] ?? 'unknown',
                'user_agent' => $log['user_agent'] ?? 'unknown',
                'details' => $log['details'] ?? []
            ];
            $logs[] = $normalized_log;
        }

        // Apply filters
        if (!empty($filters['user'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return stripos($log['user'], $filters['user']) !== false;
            });
        }

        if (!empty($filters['action'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return $log['action'] === $filters['action'];
            });
        }

        // Sort by timestamp (newest first)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Apply limit and offset
        $logs = array_slice($logs, $offset, $limit);
    }

    return $logs;
}

/**
 * Get alliance backups with metadata
 *
 * @param int $limit Number of backups to return
 * @return array Array of backup info
 */
function get_alliance_backups($limit = 50) {
    try {
        if (!is_dir(BACKUP_DIR)) {
            return [];
        }

        $backups = [];
        $files = glob(BACKUP_DIR . '/*.json');

        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $files = array_slice($files, 0, $limit);

        foreach ($files as $file) {
            $content = json_decode(file_get_contents($file), true);

            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'timestamp' => $content['timestamp'] ?? null,
                'user' => $content['user'] ?? 'unknown',
                'reason' => $content['reason'] ?? 'unknown',
                'size' => filesize($file),
                'alliance_count' => count($content['data'] ?? [])
            ];
        }

        return $backups;
    } catch (Exception $e) {
        error_log("Failed to get alliance backups: " . $e->getMessage());
        return [];
    }
}

/**
 * Restore alliances from backup
 *
 * @param string $backup_filename Backup filename to restore
 * @param string $user_email User performing the restore
 * @return bool Success status
 */
function restore_alliance_backup($backup_filename, $user_email) {
    try {
        $backup_path = BACKUP_DIR . '/' . basename($backup_filename);

        if (!file_exists($backup_path)) {
            throw new Exception("Backup file not found: $backup_filename");
        }

        // Read backup
        $backup = json_decode(file_get_contents($backup_path), true);

        if (!isset($backup['data'])) {
            throw new Exception("Invalid backup format");
        }

        // Create a backup of current state before restoring
        $current_data = read_json_file(ALLIANCES_FILE);
        backup_alliances($current_data, $user_email, 'pre_restore');

        // Restore data
        write_json_file(ALLIANCES_FILE, $backup['data']);

        // Log the restore action
        log_audit_event('restore_alliance_backup', $user_email, [
            'backup_file' => $backup_filename,
            'backup_timestamp' => $backup['timestamp'],
            'original_user' => $backup['user'],
            'alliance_count' => count($backup['data'])
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Failed to restore alliance backup: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user login event
 *
 * @param string $email User email
 * @param string $method Login method (magic_link, session)
 * @return bool Success status
 */
function log_login($email, $method = 'magic_link') {
    return log_audit_event('login', $email, [
        'method' => $method
    ]);
}

/**
 * Log user logout event
 *
 * @param string $email User email
 * @return bool Success status
 */
function log_logout($email) {
    return log_audit_event('logout', $email, []);
}

/**
 * Log alliance edit event
 *
 * @param string $email User email
 * @param string $alliance_tag Alliance tag
 * @param array $changes What was changed
 * @return bool Success status
 */
function log_alliance_edit($email, $alliance_tag, $changes) {
    return log_audit_event('edit_alliance', $email, [
        'alliance_tag' => $alliance_tag,
        'changes' => $changes
    ]);
}

/**
 * Log user management event
 *
 * @param string $action add_user, edit_user, delete_user
 * @param string $admin_email Admin performing action
 * @param string $target_email User being managed
 * @param array $changes Changes made
 * @return bool Success status
 */
function log_user_management($action, $admin_email, $target_email, $changes = []) {
    return log_audit_event($action, $admin_email, [
        'target_user' => $target_email,
        'changes' => $changes
    ]);
}

/**
 * Log key rotation implementation event
 *
 * @param string $admin_email Admin who implemented the system
 * @return bool Success status
 */
function log_key_rotation_implementation($admin_email) {
    return log_audit_event('key_rotation_system_implemented', $admin_email, [
        'features_added' => [
            'automatic_key_rotation',
            'emergency_rotation',
            'grace_period_support',
            'admin_panel_interface',
            'cron_job_automation',
            'complete_token_invalidation'
        ],
        'configuration' => [
            'auto_rotation_enabled' => AUTO_KEY_ROTATION_ENABLED,
            'rotation_interval_days' => KEY_ROTATION_INTERVAL_DAYS,
            'grace_period_seconds' => KEY_ROTATION_GRACE_PERIOD
        ],
        'security_improvements' => [
            'reduced_token_exposure_window',
            'emergency_response_capability',
            'complete_session_invalidation',
            'audit_trail_enhancement'
        ]
    ]);
}

/**
 * Log CSV operation event
 *
 * Tracks CSV file operations for power history tracking
 *
 * @param string $operation Operation type (append, sync, sort, merge, cleanup)
 * @param string $user_email User performing the operation
 * @param array $details Operation-specific details
 * @param bool $success Whether operation succeeded
 * @return bool Success status
 */
function log_csv_operation($operation, $user_email, $details = [], $success = true) {
    $action = $success ? "csv_{$operation}" : "csv_{$operation}_failed";

    // Add execution time and memory usage for performance tracking
    $details['performance'] = [
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'time' => microtime(true)
    ];

    // Add success indicator
    $details['success'] = $success;

    return log_audit_event($action, $user_email, $details);
}

/**
 * Log CSV lock acquisition event
 *
 * @param string $user_email User acquiring lock
 * @param string $file File being locked
 * @param bool $acquired Whether lock was acquired
 * @return bool Success status
 */
function log_csv_lock($user_email, $file, $acquired = true) {
    return log_csv_operation('lock', $user_email, [
        'file' => basename($file),
        'acquired' => $acquired,
        'lock_file' => basename($file) . '.lock'
    ], $acquired);
}

/**
 * Log CSV sync operation
 *
 * @param string $user_email User performing sync
 * @param int $added Number of alliances added
 * @param array $tags Alliance tags that were added
 * @return bool Success status
 */
function log_csv_sync($user_email, $added, $tags = []) {
    return log_csv_operation('sync', $user_email, [
        'alliances_added' => $added,
        'tags' => $tags,
        'total_columns' => count($tags) + 1 // +1 for datetime
    ]);
}

/**
 * Log CSV sort operation
 *
 * @param string $user_email User performing sort
 * @param int $rows Number of rows sorted
 * @param int $columns Number of columns reordered
 * @return bool Success status
 */
function log_csv_sort($user_email, $rows, $columns) {
    return log_csv_operation('sort', $user_email, [
        'rows_sorted' => $rows,
        'columns_reordered' => $columns,
        'sort_criteria' => 'date_desc_then_power_desc'
    ]);
}

/**
 * Log CSV duplicate merge operation
 *
 * @param string $user_email User performing merge
 * @param string $datetime Datetime that was merged
 * @param bool $merged Whether merge was performed
 * @return bool Success status
 */
function log_csv_merge($user_email, $datetime, $merged = true) {
    return log_csv_operation('merge', $user_email, [
        'datetime' => $datetime,
        'action' => $merged ? 'merged_data' : 'duplicate_detected',
        'merge_strategy' => 'take_non_zero_values'
    ], true);
}

/**
 * Log CSV lock cleanup operation
 *
 * @param string $user_email User or system performing cleanup
 * @param int $removed Number of lock files removed
 * @return bool Success status
 */
function log_csv_cleanup($user_email, $removed) {
    return log_csv_operation('cleanup', $user_email, [
        'lock_files_removed' => $removed,
        'age_threshold_minutes' => 5,
        'triggered_by' => $removed > 0 ? 'stale_locks_found' : 'routine_check'
    ]);
}

/**
 * Log CSV error event
 *
 * @param string $operation Operation that failed
 * @param string $user_email User performing operation
 * @param string $error_message Error message
 * @param array $context Additional context
 * @return bool Success status
 */
function log_csv_error($operation, $user_email, $error_message, $context = []) {
    return log_csv_operation($operation . '_error', $user_email, [
        'error' => $error_message,
        'context' => $context,
        'severity' => 'error'
    ], false);
}
?>
