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
 * Get client IP address
 *
 * @return string Client IP address
 */
function get_client_ip() {
    $ip = 'unknown';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
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
?>
