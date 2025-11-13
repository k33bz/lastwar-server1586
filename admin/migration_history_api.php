<?php
/**
 * Migration History API
 * Version: 1.0.0
 *
 * Provides migration tracking and history for the admin panel
 * Supports viewing migration history, creating backups, and initiating new migrations
 *
 * Access: Admin role only
 *
 * Actions:
 * - get_history: Get all migration history
 * - get_current: Get current system version
 * - create_backup: Create a manual backup before migration
 * - initiate_migration: Start a new migration (with required backup)
 */

require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';

header('Content-Type: application/json');

$user = require_jwt_session_api();

// Require admin role
if (!has_role($user, ['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin role required.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$history_file = __DIR__ . '/../data/migration-history.json';

/**
 * Load migration history
 */
function load_migration_history($file) {
    if (!file_exists($file)) {
        return [
            'currentVersion' => '3.0.0',
            'migrations' => [],
            'pendingMigrations' => [],
            'lastBackup' => null,
            'backupPolicy' => [
                'requireBackupBeforeMigration' => true,
                'retentionDays' => 30,
                'autoBackupEnabled' => true
            ]
        ];
    }

    return json_read($file);
}

/**
 * Save migration history
 */
function save_migration_history($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new Exception('Failed to encode migration history');
    }

    if (file_put_contents($file, $json) === false) {
        throw new Exception('Failed to write migration history file');
    }
}

/**
 * Create a backup of all critical data files
 */
function create_backup() {
    $backup_dir = __DIR__ . '/backups/migrations';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $backup_name = "pre-migration_{$timestamp}";
    $backup_path = "{$backup_dir}/{$backup_name}";

    mkdir($backup_path, 0755, true);

    // Files to backup
    $files_to_backup = [
        'data/alliances.json',
        'data/rotation-schedule.json',
        'data/signature-history.json',
        'data/alliance-power-history.csv',
        'data/migration-history.json',
        'admin/users.json'
    ];

    $backed_up = [];
    $failed = [];

    foreach ($files_to_backup as $file) {
        $source = __DIR__ . '/../' . $file;
        $dest = $backup_path . '/' . basename($file);

        if (file_exists($source)) {
            if (copy($source, $dest)) {
                $backed_up[] = $file;
            } else {
                $failed[] = $file;
            }
        }
    }

    // Create backup manifest
    $manifest = [
        'timestamp' => date('c'),
        'version' => load_migration_history(__DIR__ . '/../data/migration-history.json')['currentVersion'],
        'files' => $backed_up,
        'failed' => $failed
    ];

    file_put_contents(
        $backup_path . '/manifest.json',
        json_encode($manifest, JSON_PRETTY_PRINT)
    );

    return [
        'path' => $backup_name,
        'timestamp' => $timestamp,
        'files_backed_up' => count($backed_up),
        'files_failed' => count($failed),
        'backed_up' => $backed_up,
        'failed' => $failed
    ];
}

switch ($action) {
    case 'get_history':
        // Get complete migration history
        try {
            $history = load_migration_history($history_file);

            // Add system info
            $history['system'] = [
                'php_version' => phpversion(),
                'server_time' => date('c'),
                'data_directory_writable' => is_writable(__DIR__ . '/../data'),
                'backup_directory_exists' => is_dir(__DIR__ . '/backups/migrations')
            ];

            echo json_encode([
                'success' => true,
                'data' => $history
            ]);

            log_audit_event('migration_history_viewed', $user->sub);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_current':
        // Get current version only
        try {
            $history = load_migration_history($history_file);

            echo json_encode([
                'success' => true,
                'version' => $history['currentVersion'],
                'last_migration' => end($history['migrations']) ?: null,
                'last_backup' => $history['lastBackup']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'create_backup':
        // Create manual backup
        try {
            $backup_result = create_backup();

            // Update migration history with backup info
            $history = load_migration_history($history_file);
            $history['lastBackup'] = [
                'timestamp' => date('c'),
                'path' => $backup_result['path'],
                'type' => 'manual',
                'files' => $backup_result['files_backed_up'],
                'performedBy' => $user->sub
            ];
            save_migration_history($history_file, $history);

            echo json_encode([
                'success' => true,
                'message' => 'Backup created successfully',
                'backup' => $backup_result
            ]);

            log_audit_event('migration_backup_created', $user->sub, [
                'backup_path' => $backup_result['path'],
                'files_count' => $backup_result['files_backed_up']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add_migration':
        // Add a completed migration to history
        try {
            $version = $_POST['version'] ?? '';
            $description = $_POST['description'] ?? '';
            $type = $_POST['type'] ?? 'minor';
            $changes = json_decode($_POST['changes'] ?? '[]', true);

            if (empty($version) || empty($description)) {
                throw new Exception('Version and description are required');
            }

            $history = load_migration_history($history_file);

            // Check if backup policy requires a backup
            if ($history['backupPolicy']['requireBackupBeforeMigration'] && !$history['lastBackup']) {
                throw new Exception('Backup required before migration. Please create a backup first.');
            }

            $migration = [
                'version' => $version,
                'timestamp' => date('c'),
                'type' => $type,
                'description' => $description,
                'changes' => $changes,
                'backupTaken' => !empty($history['lastBackup']),
                'backupPath' => $history['lastBackup']['path'] ?? null,
                'performedBy' => $user->sub,
                'status' => 'completed'
            ];

            $history['migrations'][] = $migration;
            $history['currentVersion'] = $version;

            save_migration_history($history_file, $history);

            echo json_encode([
                'success' => true,
                'message' => 'Migration added to history',
                'migration' => $migration
            ]);

            log_audit_event('migration_added', $user->sub, [
                'version' => $version,
                'type' => $type
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
