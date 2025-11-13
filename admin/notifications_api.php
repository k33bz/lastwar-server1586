<?php
/**
 * Notifications API
 *
 * Generic notification system for admin panel
 * Handles creating, reading, and managing notifications with badge counts
 *
 * @version 1.0.0
 * @created 2025-11-12
 *
 * Changelog:
 * - 1.0.0: Initial implementation with 5 endpoints
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors to user
ini_set('log_errors', '1');

header('Content-Type: application/json');

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Require valid JWT session
$user = require_jwt_session_api();

// Get action parameter
$action = $_GET['action'] ?? '';

// Notifications file path
$notifications_file = __DIR__ . '/../data/notifications.json';

// Helper function to read notifications
function read_notifications($file) {
    if (!file_exists($file)) {
        return ['version' => '1.0.0', 'last_updated' => '', 'notifications' => []];
    }

    $content = file_get_contents($file);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['version' => '1.0.0', 'last_updated' => '', 'notifications' => []];
    }

    return $data;
}

// Helper function to write notifications
function write_notifications($file, $data) {
    $data['last_updated'] = date('Y-m-d H:i:s');
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Helper function to check if notification is visible to user
function is_notification_visible($notification, $user_email, $user_role) {
    // Check if expired
    if (isset($notification['expires_at']) && !empty($notification['expires_at'])) {
        $expires = strtotime($notification['expires_at']);
        if ($expires !== false && $expires < time()) {
            return false;
        }
    }

    // Check recipient targeting
    $recipient_type = $notification['recipient_type'] ?? 'email';

    switch ($recipient_type) {
        case 'broadcast':
            return true;

        case 'email':
            $recipients = $notification['recipients'] ?? [];
            return in_array($user_email, $recipients);

        case 'role':
            $recipients = $notification['recipients'] ?? [];
            return in_array($user_role, $recipients);

        case 'alliance':
            // For now, we don't have alliance info in JWT
            // This would need to be extended when alliance tracking is added
            return false;

        default:
            return false;
    }
}

// Helper function to get user's unread notifications
function get_user_notifications($data, $user_email, $user_role, $unread_only = false) {
    $user_notifications = [];

    foreach ($data['notifications'] as $notification) {
        if (!is_notification_visible($notification, $user_email, $user_role)) {
            continue;
        }

        // Check read status for this user
        $read_by = $notification['read_by'] ?? [];
        $is_read = in_array($user_email, $read_by);

        if ($unread_only && $is_read) {
            continue;
        }

        $user_notifications[] = array_merge($notification, ['is_read' => $is_read]);
    }

    // Sort by priority (high > medium > low) then by created date (newest first)
    usort($user_notifications, function($a, $b) {
        $priority_order = ['high' => 3, 'medium' => 2, 'low' => 1];
        $a_priority = $priority_order[$a['priority'] ?? 'medium'];
        $b_priority = $priority_order[$b['priority'] ?? 'medium'];

        if ($a_priority !== $b_priority) {
            return $b_priority - $a_priority;
        }

        $a_time = strtotime($a['created_at'] ?? '');
        $b_time = strtotime($b['created_at'] ?? '');
        return $b_time - $a_time;
    });

    return $user_notifications;
}

try {
    switch ($action) {
        // GET: Get unread notification count
        case 'get_unread_count':
            $data = read_notifications($notifications_file);
            $notifications = get_user_notifications($data, $user->sub, $user->aud, true);

            echo json_encode([
                'success' => true,
                'count' => count($notifications),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // GET: Get notifications (paginated)
        case 'get_notifications':
            $page = max(1, intval($_GET['page'] ?? 1));
            $per_page = max(1, min(50, intval($_GET['per_page'] ?? 10)));
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

            $data = read_notifications($notifications_file);
            $all_notifications = get_user_notifications($data, $user->sub, $user->aud, $unread_only);

            // Paginate
            $total = count($all_notifications);
            $offset = ($page - 1) * $per_page;
            $notifications = array_slice($all_notifications, $offset, $per_page);

            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'total_pages' => ceil($total / $per_page)
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // POST: Mark notification as read
        case 'mark_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $notification_id = $input['notification_id'] ?? '';

            if (empty($notification_id)) {
                throw new Exception('Notification ID is required', 400);
            }

            $data = read_notifications($notifications_file);
            $found = false;

            foreach ($data['notifications'] as &$notification) {
                if ($notification['id'] === $notification_id) {
                    $found = true;

                    // Add user to read_by array if not already there
                    if (!isset($notification['read_by'])) {
                        $notification['read_by'] = [];
                    }

                    if (!in_array($user->sub, $notification['read_by'])) {
                        $notification['read_by'][] = $user->sub;
                    }

                    break;
                }
            }

            if (!$found) {
                throw new Exception('Notification not found', 404);
            }

            write_notifications($notifications_file, $data);

            log_audit('notification_marked_read', [
                'notification_id' => $notification_id,
                'user' => $user->sub
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // POST: Mark all notifications as read
        case 'mark_all_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            $data = read_notifications($notifications_file);
            $marked_count = 0;

            foreach ($data['notifications'] as &$notification) {
                if (!is_notification_visible($notification, $user->sub, $user->aud)) {
                    continue;
                }

                // Add user to read_by array if not already there
                if (!isset($notification['read_by'])) {
                    $notification['read_by'] = [];
                }

                if (!in_array($user->sub, $notification['read_by'])) {
                    $notification['read_by'][] = $user->sub;
                    $marked_count++;
                }
            }

            write_notifications($notifications_file, $data);

            log_audit('all_notifications_marked_read', [
                'count' => $marked_count,
                'user' => $user->sub
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read',
                'count' => $marked_count,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        // POST: Create notification (internal use - admin/president only)
        case 'create_notification':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }

            // Only admin/president can create notifications
            if ($user->aud !== 'admin' && $user->aud !== 'president') {
                throw new Exception('Access denied. Admin or President privileges required.', 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            $required_fields = ['type', 'title', 'message', 'recipient_type'];
            foreach ($required_fields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    throw new Exception("Field '{$field}' is required", 400);
                }
            }

            // Validate recipient_type
            $valid_recipient_types = ['broadcast', 'email', 'role', 'alliance'];
            if (!in_array($input['recipient_type'], $valid_recipient_types)) {
                throw new Exception('Invalid recipient_type', 400);
            }

            // If not broadcast, require recipients array
            if ($input['recipient_type'] !== 'broadcast') {
                if (!isset($input['recipients']) || !is_array($input['recipients']) || empty($input['recipients'])) {
                    throw new Exception('Recipients array is required for non-broadcast notifications', 400);
                }
            }

            // Generate unique ID
            $notification_id = 'notif_' . bin2hex(random_bytes(8)) . '_' . time();

            // Create notification object
            $notification = [
                'id' => $notification_id,
                'type' => $input['type'],
                'priority' => $input['priority'] ?? 'medium',
                'title' => $input['title'],
                'message' => $input['message'],
                'recipient_type' => $input['recipient_type'],
                'recipients' => $input['recipients'] ?? [],
                'action_url' => $input['action_url'] ?? '',
                'action_text' => $input['action_text'] ?? 'View',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $user->sub,
                'expires_at' => $input['expires_at'] ?? '',
                'read_by' => []
            ];

            // Read existing notifications
            $data = read_notifications($notifications_file);

            // Add new notification
            $data['notifications'][] = $notification;

            // Write back
            write_notifications($notifications_file, $data);

            log_audit('notification_created', [
                'notification_id' => $notification_id,
                'type' => $notification['type'],
                'recipient_type' => $notification['recipient_type'],
                'created_by' => $user->sub
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification created successfully',
                'notification_id' => $notification_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        default:
            throw new Exception('Invalid action', 400);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
