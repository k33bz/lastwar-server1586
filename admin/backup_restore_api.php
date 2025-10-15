<?php
/**
 * Backup Restore API
 *
 * API endpoints for viewing and restoring alliance backups
 *
 * @version 1.0.0
 * @date 2025-10-15
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Require admin session
$user = require_admin_session();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'preview':
            // Preview backup content without restoring
            $filename = $_GET['filename'] ?? '';

            if (empty($filename)) {
                throw new Exception('Filename required');
            }

            $backup_path = BACKUP_DIR . '/' . basename($filename);

            if (!file_exists($backup_path)) {
                throw new Exception('Backup file not found');
            }

            $backup_data = json_decode(file_get_contents($backup_path), true);

            if (!$backup_data || !isset($backup_data['data'])) {
                throw new Exception('Invalid backup format');
            }

            echo json_encode([
                'success' => true,
                'timestamp' => $backup_data['timestamp'] ?? null,
                'user' => $backup_data['user'] ?? 'unknown',
                'reason' => $backup_data['reason'] ?? 'unknown',
                'alliances' => $backup_data['data']
            ]);
            break;

        case 'restore':
            // Restore from backup
            $filename = $_POST['filename'] ?? '';

            if (empty($filename)) {
                throw new Exception('Filename required');
            }

            // Perform restore
            $result = restore_alliance_backup($filename, $user->sub);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Alliance data restored successfully from backup'
                ]);
            } else {
                throw new Exception('Restore operation failed');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
