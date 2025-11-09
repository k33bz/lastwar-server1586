<?php
/**
 * Discord Recurring Messages API
 * Version: 1.1.0 (Phase 3 - Recurring messaging)
 *
 * Handles CRUD operations for recurring Discord messages
 *
 * Changelog:
 *   1.1.0 (2025-11-07) - Added auto-delete message support
 *                       - Store delete_after_hours in recurring message objects
 *   1.0.0 (2025-11-04) - Initial implementation
 */

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'json_helpers.php';

header('Content-Type: application/json');

$user = require_jwt_session();

// Check if user has at least R4 access or president role
if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Check if Discord is enabled
if (!defined('DISCORD_ENABLED') || !DISCORD_ENABLED) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Discord integration is disabled']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$recurring_file = __DIR__ . '/discord_recurring.json';

// Helper: Load recurring messages
function load_recurring_messages($file) {
    if (!file_exists($file)) {
        return ['recurring_messages' => []];
    }
    return json_decode(file_get_contents($file), true) ?? ['recurring_messages' => []];
}

// Helper: Save recurring messages
function save_recurring_messages($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Helper: Get user accessible channels
function get_user_channels($user) {
    require_once 'discord_webhook.php';
    return get_user_accessible_channels($user);
}

// Helper: Calculate next send time
function calculate_next_send_time($frequency, $time_of_day, $day_of_week = null, $day_of_month = null) {
    $now = time();
    list($hour, $minute) = explode(':', $time_of_day);

    switch ($frequency) {
        case 'daily':
            $next = strtotime("today {$time_of_day}");
            if ($next <= $now) {
                $next = strtotime("tomorrow {$time_of_day}");
            }
            return date('Y-m-d H:i:s', $next);

        case 'weekly':
            $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                     'thursday' => 4, 'friday' => 5, 'saturday' => 6];
            $target_day = $days[strtolower($day_of_week)] ?? 0;
            $current_day = (int)date('w');

            $days_until = ($target_day - $current_day + 7) % 7;
            if ($days_until === 0) {
                // Same day - check if time has passed
                $today_time = strtotime("today {$time_of_day}");
                if ($today_time > $now) {
                    return date('Y-m-d H:i:s', $today_time);
                }
                $days_until = 7; // Next week
            }

            $next = strtotime("+{$days_until} days {$time_of_day}");
            return date('Y-m-d H:i:s', $next);

        case 'monthly':
            $target_day = (int)$day_of_month;
            $current_month = date('Y-m');
            $next = strtotime("{$current_month}-{$target_day} {$time_of_day}");

            if ($next <= $now) {
                // Next month
                $next_month = date('Y-m', strtotime('+1 month'));
                $next = strtotime("{$next_month}-{$target_day} {$time_of_day}");
            }

            return date('Y-m-d H:i:s', $next);

        default:
            return null;
    }
}

switch ($action) {
    case 'list':
        // Get all recurring messages for user's accessible channels
        $data = load_recurring_messages($recurring_file);
        $accessible_channels = get_user_channels($user);
        $accessible_channel_ids = array_column($accessible_channels, 'id');

        // Filter messages to only those for accessible channels
        $user_messages = array_filter($data['recurring_messages'], function($msg) use ($accessible_channel_ids) {
            return in_array($msg['channel_id'], $accessible_channel_ids);
        });

        // Add channel names
        foreach ($user_messages as &$msg) {
            foreach ($accessible_channels as $channel) {
                if ($channel['id'] === $msg['channel_id']) {
                    $msg['channel_name'] = $channel['name'];
                    $msg['alliance'] = $channel['alliance'] ?? null;
                    break;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'messages' => array_values($user_messages)
        ]);

        log_audit_event('discord_recurring_list', $user->sub, [
            'message_count' => count($user_messages)
        ]);
        break;

    case 'create':
        // Create new recurring message
        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['channel_id', 'message', 'frequency', 'time_of_day'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
                exit();
            }
        }

        // Validate frequency
        $valid_frequencies = ['daily', 'weekly', 'monthly'];
        if (!in_array($input['frequency'], $valid_frequencies)) {
            echo json_encode(['success' => false, 'error' => 'Invalid frequency']);
            exit();
        }

        // Validate day_of_week for weekly
        if ($input['frequency'] === 'weekly' && empty($input['day_of_week'])) {
            echo json_encode(['success' => false, 'error' => 'day_of_week required for weekly frequency']);
            exit();
        }

        // Validate day_of_month for monthly
        if ($input['frequency'] === 'monthly' && empty($input['day_of_month'])) {
            echo json_encode(['success' => false, 'error' => 'day_of_month required for monthly frequency']);
            exit();
        }

        // Validate user has access to this channel
        $accessible_channels = get_user_channels($user);
        $has_access = false;
        foreach ($accessible_channels as $channel) {
            if ($channel['id'] === $input['channel_id']) {
                $has_access = true;
                break;
            }
        }

        if (!$has_access) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No access to this channel']);
            exit();
        }

        // Calculate next send time
        $next_send = calculate_next_send_time(
            $input['frequency'],
            $input['time_of_day'],
            $input['day_of_week'] ?? null,
            $input['day_of_month'] ?? null
        );

        // Create message object
        $message = [
            'id' => uniqid('recur_', true),
            'channel_id' => $input['channel_id'],
            'message' => $input['message'],
            'use_embed' => $input['use_embed'] ?? true,
            'embed_title' => $input['embed_title'] ?? null,
            'embed_color' => $input['embed_color'] ?? '#5865F2',
            'frequency' => $input['frequency'],
            'time_of_day' => $input['time_of_day'],
            'day_of_week' => $input['day_of_week'] ?? null,
            'day_of_month' => $input['day_of_month'] ?? null,
            'next_send_time' => $next_send,
            'last_sent_at' => null,
            'send_count' => 0,
            'created_by' => $user->sub,
            'created_at' => date('Y-m-d H:i:s'),
            'enabled' => true,
            'delete_after_hours' => isset($input['delete_after_hours']) && $input['delete_after_hours'] !== '' ? (int)$input['delete_after_hours'] : null
        ];

        $data = load_recurring_messages($recurring_file);
        $data['recurring_messages'][] = $message;

        if (save_recurring_messages($recurring_file, $data)) {
            echo json_encode(['success' => true, 'message' => $message]);

            log_audit_event('discord_recurring_create', $user->sub, [
                'message_id' => $message['id'],
                'channel_id' => $message['channel_id'],
                'frequency' => $message['frequency'],
                'next_send_time' => $message['next_send_time']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save recurring message']);
        }
        break;

    case 'toggle':
        // Enable/disable recurring message
        $message_id = $_POST['message_id'] ?? '';
        $enabled = filter_var($_POST['enabled'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

        if (empty($message_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing message_id']);
            exit();
        }

        $data = load_recurring_messages($recurring_file);
        $found = false;

        foreach ($data['recurring_messages'] as &$msg) {
            if ($msg['id'] === $message_id) {
                // Verify user has access
                if ($msg['created_by'] !== $user->sub && !has_role($user, ['admin'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Only the creator or admin can modify this message']);
                    exit();
                }

                $msg['enabled'] = $enabled;
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
            exit();
        }

        if (save_recurring_messages($recurring_file, $data)) {
            echo json_encode(['success' => true]);

            log_audit_event('discord_recurring_toggle', $user->sub, [
                'message_id' => $message_id,
                'enabled' => $enabled
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update message']);
        }
        break;

    case 'delete':
        // Delete recurring message
        $message_id = $_POST['message_id'] ?? '';

        if (empty($message_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing message_id']);
            exit();
        }

        $data = load_recurring_messages($recurring_file);
        $found = false;

        foreach ($data['recurring_messages'] as $key => $msg) {
            if ($msg['id'] === $message_id) {
                // Verify user has access
                if ($msg['created_by'] !== $user->sub && !has_role($user, ['admin'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Only the creator or admin can delete this message']);
                    exit();
                }

                unset($data['recurring_messages'][$key]);
                $data['recurring_messages'] = array_values($data['recurring_messages']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
            exit();
        }

        if (save_recurring_messages($recurring_file, $data)) {
            echo json_encode(['success' => true]);

            log_audit_event('discord_recurring_delete', $user->sub, [
                'message_id' => $message_id
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
