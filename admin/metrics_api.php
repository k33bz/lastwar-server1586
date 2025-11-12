<?php
/**
 * System Metrics API
 * Version: 1.0.1
 *
 * Provides CloudWatch-style metrics for monitoring system activity
 *
 * Changelog:
 * - 1.0.1: Fixed authentication to use require_jwt_session_api() for proper JSON error responses
 *
 * Metrics tracked:
 * - Discord messages sent (by type: announcements, scheduled, recurring)
 * - Login attempts (successful vs failed)
 * - API requests by endpoint
 * - User activity by role
 * - Backup operations
 * - Data modifications
 */

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'includes/csrf.php';

header('Content-Type: application/json');

$user = require_jwt_session_api();

// Only admins can view metrics
if (!has_role($user, ['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$action = $_GET['action'] ?? '';
$timeRange = $_GET['timeRange'] ?? '24h'; // 24h, 7d, 30d, 90d

/**
 * Get time range timestamps
 */
function get_time_range($range) {
    $now = time();
    switch ($range) {
        case '1h':
            return [$now - 3600, $now, 60]; // 1 minute intervals
        case '24h':
            return [$now - 86400, $now, 3600]; // 1 hour intervals
        case '7d':
            return [$now - (7 * 86400), $now, 86400]; // 1 day intervals
        case '30d':
            return [$now - (30 * 86400), $now, 86400]; // 1 day intervals
        case '90d':
            return [$now - (90 * 86400), $now, 86400 * 3]; // 3 day intervals
        default:
            return [$now - 86400, $now, 3600];
    }
}

/**
 * Load audit logs and filter by time range
 */
function load_audit_logs($start_time, $end_time) {
    $log_file = __DIR__ . '/audit_logs.json';

    if (!file_exists($log_file)) {
        return [];
    }

    $logs = json_decode(file_get_contents($log_file), true);
    if (!$logs || !isset($logs['logs'])) {
        return [];
    }

    // Filter by time range
    return array_filter($logs['logs'], function($log) use ($start_time, $end_time) {
        $log_time = strtotime($log['timestamp']);
        return $log_time >= $start_time && $log_time <= $end_time;
    });
}

/**
 * Generate time buckets for charting
 */
function generate_time_buckets($start_time, $end_time, $interval) {
    $buckets = [];
    $current = $start_time;

    while ($current <= $end_time) {
        $buckets[$current] = 0;
        $current += $interval;
    }

    return $buckets;
}

/**
 * Aggregate logs into time buckets
 */
function aggregate_by_time($logs, $start_time, $end_time, $interval, $filter_fn = null) {
    $buckets = generate_time_buckets($start_time, $end_time, $interval);

    foreach ($logs as $log) {
        if ($filter_fn && !$filter_fn($log)) {
            continue;
        }

        $log_time = strtotime($log['timestamp']);
        $bucket_time = floor($log_time / $interval) * $interval;

        if (isset($buckets[$bucket_time])) {
            $buckets[$bucket_time]++;
        }
    }

    return $buckets;
}

try {
    list($start_time, $end_time, $interval) = get_time_range($timeRange);
    $logs = load_audit_logs($start_time, $end_time);

    switch ($action) {
        case 'discord_messages':
            // Discord messages by type
            $announcements = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return strpos($log['action'], 'discord_announcement') !== false;
            });

            $scheduled = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return $log['action'] === 'discord_scheduled_sent';
            });

            $recurring = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return $log['action'] === 'discord_recurring_sent';
            });

            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => array_keys($announcements),
                    'datasets' => [
                        [
                            'label' => 'Announcements',
                            'data' => array_values($announcements),
                            'color' => '#3498db'
                        ],
                        [
                            'label' => 'Scheduled',
                            'data' => array_values($scheduled),
                            'color' => '#9b59b6'
                        ],
                        [
                            'label' => 'Recurring',
                            'data' => array_values($recurring),
                            'color' => '#e67e22'
                        ]
                    ]
                ]
            ]);
            break;

        case 'login_attempts':
            // Login attempts (successful vs failed)
            $successful = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return $log['action'] === 'login_success';
            });

            $failed = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return $log['action'] === 'login_failed';
            });

            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => array_keys($successful),
                    'datasets' => [
                        [
                            'label' => 'Successful',
                            'data' => array_values($successful),
                            'color' => '#27ae60'
                        ],
                        [
                            'label' => 'Failed',
                            'data' => array_values($failed),
                            'color' => '#e74c3c'
                        ]
                    ]
                ]
            ]);
            break;

        case 'data_operations':
            // Data modification operations
            $creates = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return strpos($log['action'], 'create') !== false || strpos($log['action'], 'add') !== false;
            });

            $updates = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false;
            });

            $deletes = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return strpos($log['action'], 'delete') !== false || strpos($log['action'], 'remove') !== false;
            });

            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => array_keys($creates),
                    'datasets' => [
                        [
                            'label' => 'Creates',
                            'data' => array_values($creates),
                            'color' => '#27ae60'
                        ],
                        [
                            'label' => 'Updates',
                            'data' => array_values($updates),
                            'color' => '#f39c12'
                        ],
                        [
                            'label' => 'Deletes',
                            'data' => array_values($deletes),
                            'color' => '#e74c3c'
                        ]
                    ]
                ]
            ]);
            break;

        case 'user_activity':
            // Activity by user role
            $admins = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return isset($log['metadata']['role']) && $log['metadata']['role'] === 'admin';
            });

            $r5 = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return isset($log['metadata']['role']) && $log['metadata']['role'] === 'r5';
            });

            $r4 = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return isset($log['metadata']['role']) && $log['metadata']['role'] === 'r4';
            });

            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => array_keys($admins),
                    'datasets' => [
                        [
                            'label' => 'Admins',
                            'data' => array_values($admins),
                            'color' => '#e74c3c'
                        ],
                        [
                            'label' => 'R5',
                            'data' => array_values($r5),
                            'color' => '#f39c12'
                        ],
                        [
                            'label' => 'R4',
                            'data' => array_values($r4),
                            'color' => '#3498db'
                        ]
                    ]
                ]
            ]);
            break;

        case 'backups':
            // Backup operations
            $manual = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return $log['action'] === 'manual_backup';
            });

            $auto = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return $log['action'] === 'auto_backup';
            });

            $restores = aggregate_by_time($logs, $start_time, $end_time, $interval, function($log) {
                return strpos($log['action'], 'restore') !== false;
            });

            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => array_keys($manual),
                    'datasets' => [
                        [
                            'label' => 'Manual Backups',
                            'data' => array_values($manual),
                            'color' => '#3498db'
                        ],
                        [
                            'label' => 'Auto Backups',
                            'data' => array_values($auto),
                            'color' => '#27ae60'
                        ],
                        [
                            'label' => 'Restores',
                            'data' => array_values($restores),
                            'color' => '#e74c3c'
                        ]
                    ]
                ]
            ]);
            break;

        case 'summary':
            // Summary statistics for current time range
            $total_events = count($logs);
            $unique_users = count(array_unique(array_column($logs, 'user')));

            // Count by action type
            $action_counts = [];
            foreach ($logs as $log) {
                $action = $log['action'];
                if (!isset($action_counts[$action])) {
                    $action_counts[$action] = 0;
                }
                $action_counts[$action]++;
            }

            // Top 10 actions
            arsort($action_counts);
            $top_actions = array_slice($action_counts, 0, 10, true);

            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_events' => $total_events,
                    'unique_users' => $unique_users,
                    'time_range' => $timeRange,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'top_actions' => $top_actions
                ]
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
