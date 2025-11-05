<?php
/**
 * Audit Log API
 *
 * JSON API for fetching audit logs (used by real-time viewer)
 *
 * Documentation:
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 * - Security Changelog: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/SECURITY_CHANGELOG.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-15
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation with export support
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Require admin session
$user = require_admin_session();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Get filters
        $filter_user = $_GET['user'] ?? '';
        $filter_action = $_GET['action_type'] ?? $_GET['action'] ?? '';  // Avoid conflict with 'action' param
        $limit = (int)($_GET['limit'] ?? 100);

        // Fetch logs
        $logs = get_audit_logs([
            'user' => $filter_user,
            'action' => $filter_action !== 'list' ? $filter_action : ''
        ], $limit, 0);

        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
        break;

    case 'export':
        // Export logs as CSV
        $logs = get_audit_logs([], 1000, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');

        // Write header
        fputcsv($output, ['Timestamp', 'Action', 'User', 'IP Address', 'User Agent', 'Details']);

        // Write data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['timestamp'],
                $log['action'],
                $log['user'],
                $log['ip'],
                $log['user_agent'] ?? '',
                json_encode($log['details'])
            ]);
        }

        fclose($output);
        exit;

    case 'raw':
        // Return raw JSON log file content
        $audit_file = __DIR__ . '/audit_log.json';

        if (file_exists($audit_file)) {
            header('Content-Type: application/json');
            echo file_get_contents($audit_file);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'logs' => [],
                'message' => 'No audit log file found'
            ]);
        }
        exit;

    case 'get_user_info':
        // Get user info for tooltip display
        $email = $_GET['email'] ?? '';

        if (empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Email required']);
            break;
        }

        require_once __DIR__ . '/json_helpers.php';
        $user_data = get_user_by_email($email);

        if ($user_data) {
            echo json_encode([
                'success' => true,
                'user_info' => [
                    'email' => $email,
                    'roles' => $user_data['roles'] ?? [],
                    'alliances' => $user_data['alliances'] ?? [],
                    'in_game_name' => $user_data['in_game_name'] ?? null
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
