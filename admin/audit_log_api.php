<?php
/**
 * Audit Log API
 *
 * JSON API for fetching audit logs (used by real-time viewer)
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

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
