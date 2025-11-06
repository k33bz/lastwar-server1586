<?php
/**
 * Discord Scheduled Messages API
 * Version: 1.0.0 (Phase 2 - Scheduled messaging)
 *
 * Handles CRUD operations for scheduled Discord messages
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
$scheduled_file = __DIR__ . '/discord_scheduled.json';

// Helper: Load scheduled messages
function load_scheduled_messages($file) {
    if (!file_exists($file)) {
        return ['scheduled_messages' => []];
    }
    return json_decode(file_get_contents($file), true) ?? ['scheduled_messages' => []];
}

// Helper: Save scheduled messages
function save_scheduled_messages($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Helper: Get user accessible channels
function get_user_channels($user) {
    require_once 'discord_webhook.php';
    return get_user_accessible_channels($user);
}

switch ($action) {
    case 'list':
        // Get all scheduled messages for user's accessible channels
        $data = load_scheduled_messages($scheduled_file);
        $accessible_channels = get_user_channels($user);
        $accessible_channel_ids = array_column($accessible_channels, 'id');

        // Filter messages to only those for accessible channels
        $user_messages = array_filter($data['scheduled_messages'], function($msg) use ($accessible_channel_ids) {
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

        log_audit_event('discord_scheduled_list', $user->sub, [
            'message_count' => count($user_messages)
        ]);
        break;

    case 'create':
        // Create new scheduled message
        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['channel_id', 'message', 'scheduled_time'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
                exit();
            }
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

        if (!has_access) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No access to this channel']);
            exit();
        }

        // Validate scheduled time is in the future
        $scheduled_timestamp = strtotime($input['scheduled_time']);
        if ($scheduled_timestamp <= time()) {
            echo json_encode(['success' => false, 'error' => 'Scheduled time must be in the future']);
            exit();
        }

        // Create message object
        $message = [
            'id' => uniqid('sched_', true),
            'channel_id' => $input['channel_id'],
            'message' => $input['message'],
            'use_embed' => $input['use_embed'] ?? true,
            'embed_title' => $input['embed_title'] ?? null,
            'embed_color' => $input['embed_color'] ?? '#5865F2',
            'scheduled_time' => date('Y-m-d H:i:s', $scheduled_timestamp),
            'created_by' => $user->sub,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'sent_at' => null,
            'error' => null
        ];

        $data = load_scheduled_messages($scheduled_file);
        $data['scheduled_messages'][] = $message;

        if (save_scheduled_messages($scheduled_file, $data)) {
            echo json_encode(['success' => true, 'message' => $message]);

            log_audit_event('discord_scheduled_create', $user->sub, [
                'message_id' => $message['id'],
                'channel_id' => $message['channel_id'],
                'scheduled_time' => $message['scheduled_time']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save scheduled message']);
        }
        break;

    case 'delete':
        // Delete scheduled message
        $message_id = $_POST['message_id'] ?? '';

        if (empty($message_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing message_id']);
            exit();
        }

        $data = load_scheduled_messages($scheduled_file);
        $found = false;

        foreach ($data['scheduled_messages'] as $key => $msg) {
            if ($msg['id'] === $message_id) {
                // Verify user has access
                if ($msg['created_by'] !== $user->sub && !has_role($user, ['admin'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Only the creator or admin can delete this message']);
                    exit();
                }

                // Don't allow deleting already-sent messages
                if ($msg['status'] === 'sent') {
                    echo json_encode(['success' => false, 'error' => 'Cannot delete already-sent messages']);
                    exit();
                }

                unset($data['scheduled_messages'][$key]);
                $data['scheduled_messages'] = array_values($data['scheduled_messages']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Message not found']);
            exit();
        }

        if (save_scheduled_messages($scheduled_file, $data)) {
            echo json_encode(['success' => true]);

            log_audit_event('discord_scheduled_delete', $user->sub, [
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
