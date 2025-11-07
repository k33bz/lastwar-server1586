<?php
/**
 * Discord Scheduled Messages Processor
 * Version: 1.1.0 (Phase 2 - Scheduled messaging)
 *
 * Processes and sends scheduled Discord messages
 * Run this via cron every minute: * * * * * php /path/to/admin/discord_scheduled_processor.php
 *
 * Changelog:
 *   1.1.0 (2025-11-07) - Added auto-delete message tracking support
 *                       - Pass tracking info to send_discord_message for scheduled/recurring messages
 *   1.0.0 (2025-11-04) - Initial implementation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/discord_webhook.php';
require_once __DIR__ . '/discord_message_tracker.php';
require_once __DIR__ . '/audit_logger.php';

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

// Check if Discord is enabled
if (!defined('DISCORD_ENABLED') || !DISCORD_ENABLED) {
    error_log('Discord scheduled processor: Discord integration is disabled');
    exit(0);
}

// Load scheduled messages
$data = load_scheduled_messages($scheduled_file);
$current_time = time();
$messages_sent = 0;
$messages_failed = 0;

// Process pending messages
foreach ($data['scheduled_messages'] as &$message) {
    // Skip if not pending
    if ($message['status'] !== 'pending') {
        continue;
    }

    // Check if it's time to send
    $scheduled_timestamp = strtotime($message['scheduled_time']);
    if ($scheduled_timestamp > $current_time) {
        continue; // Not yet time
    }

    // Replace template variables in message
    require_once __DIR__ . '/discord_variable_replacer.php';
    $processed_message = replace_message_variables($message['message'], $message['created_by'], $message['channel_id']);
    $processed_title = !empty($message['embed_title']) ? replace_message_variables($message['embed_title'], $message['created_by'], $message['channel_id']) : 'Scheduled Announcement';

    // Prepare Discord message
    if ($message['use_embed']) {
        // Get user info for footer
        require_once __DIR__ . '/json_helpers.php';
        $user_data = get_user_by_email($message['created_by']);
        $user_name = $user_data['ign'] ?? $user_data['in_game_name'] ?? 'Unknown';
        $user_alliance = $user_data['alliance'] ?? '';

        // Convert hex color to decimal
        $color_hex = ltrim($message['embed_color'] ?? '#5865F2', '#');
        $color_decimal = hexdec($color_hex);

        $discord_message = create_embed_announcement(
            $processed_title,
            $processed_message,
            [
                'color' => $color_decimal,
                'footer' => $user_alliance ? "[{$user_alliance}] {$user_name} • Last War 1586 Bot" : "{$user_name} • Last War 1586 Bot"
            ]
        );
    } else {
        $discord_message = create_simple_announcement($processed_message);
    }

    // Send message
    try {
        // Prepare tracking info for auto-delete
        $tracking_info = [];
        $delete_after_hours = $message['delete_after_hours'] ?? null;
        if ($delete_after_hours !== null && $delete_after_hours > 0) {
            $tracking_info = [
                'internal_id' => $message['id'],
                'delete_after_hours' => $delete_after_hours,
                'message_type' => 'scheduled',
                'user_email' => $message['created_by']
            ];
        }

        $result = send_discord_message($message['channel_id'], $discord_message, 0, $tracking_info);

        if ($result['success']) {
            $message['status'] = 'sent';
            $message['sent_at'] = date('Y-m-d H:i:s');
            $message['discord_message_id'] = $result['message_id'] ?? null;
            $messages_sent++;

            // Log successful send
            log_audit_event('discord_scheduled_sent', 'system', [
                'message_id' => $message['id'],
                'channel_id' => $message['channel_id'],
                'created_by' => $message['created_by'],
                'scheduled_time' => $message['scheduled_time']
            ]);

            error_log("Scheduled message {$message['id']} sent successfully");
        } else {
            throw new Exception($result['error'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $message['status'] = 'failed';
        $message['error'] = $e->getMessage();
        $message['failed_at'] = date('Y-m-d H:i:s');
        $messages_failed++;

        // Log failure
        log_audit_event('discord_scheduled_failed', 'system', [
            'message_id' => $message['id'],
            'channel_id' => $message['channel_id'],
            'created_by' => $message['created_by'],
            'error' => $e->getMessage()
        ]);

        error_log("Scheduled message {$message['id']} failed: " . $e->getMessage());
    }

    // Rate limiting: wait 1 second between messages
    sleep(1);
}

// Save updated data
save_scheduled_messages($scheduled_file, $data);

// Process recurring messages
$recurring_file = __DIR__ . '/discord_recurring.json';
$recurring_data = load_scheduled_messages($recurring_file); // Reuse helper function
$recurring_sent = 0;
$recurring_failed = 0;

foreach ($recurring_data['recurring_messages'] as &$message) {
    // Skip if disabled
    if (!$message['enabled']) {
        continue;
    }

    // Check if it's time to send
    $next_send_timestamp = strtotime($message['next_send_time']);
    if ($next_send_timestamp > $current_time) {
        continue; // Not yet time
    }

    // Replace template variables in message
    $processed_message = replace_message_variables($message['message'], $message['created_by'], $message['channel_id']);
    $processed_title = !empty($message['embed_title']) ? replace_message_variables($message['embed_title'], $message['created_by'], $message['channel_id']) : 'Recurring Announcement';

    // Prepare Discord message
    if ($message['use_embed']) {
        // Get user info for footer
        require_once __DIR__ . '/json_helpers.php';
        $user_data = get_user_by_email($message['created_by']);
        $user_name = $user_data['ign'] ?? $user_data['in_game_name'] ?? 'Unknown';
        $user_alliance = $user_data['alliance'] ?? '';

        // Convert hex color to decimal
        $color_hex = ltrim($message['embed_color'] ?? '#5865F2', '#');
        $color_decimal = hexdec($color_hex);

        $discord_message = create_embed_announcement(
            $processed_title,
            $processed_message,
            [
                'color' => $color_decimal,
                'footer' => $user_alliance ? "[{$user_alliance}] {$user_name} • Last War 1586 Bot" : "{$user_name} • Last War 1586 Bot"
            ]
        );
    } else {
        $discord_message = create_simple_announcement($processed_message);
    }

    // Send message
    try {
        // Prepare tracking info for auto-delete
        $tracking_info = [];
        $delete_after_hours = $message['delete_after_hours'] ?? null;
        if ($delete_after_hours !== null && $delete_after_hours > 0) {
            // Generate unique ID for each occurrence of recurring message
            $occurrence_id = $message['id'] . '_' . time();
            $tracking_info = [
                'internal_id' => $occurrence_id,
                'delete_after_hours' => $delete_after_hours,
                'message_type' => 'recurring',
                'user_email' => $message['created_by']
            ];
        }

        $result = send_discord_message($message['channel_id'], $discord_message, 0, $tracking_info);

        if ($result['success']) {
            $message['last_sent_at'] = date('Y-m-d H:i:s');
            $message['send_count'] = ($message['send_count'] ?? 0) + 1;

            // Calculate next send time
            $next_send = calculate_next_send_time(
                $message['frequency'],
                $message['time_of_day'],
                $message['day_of_week'] ?? null,
                $message['day_of_month'] ?? null
            );
            $message['next_send_time'] = $next_send;

            $recurring_sent++;

            // Log successful send
            log_audit_event('discord_recurring_sent', 'system', [
                'message_id' => $message['id'],
                'channel_id' => $message['channel_id'],
                'created_by' => $message['created_by'],
                'frequency' => $message['frequency'],
                'send_count' => $message['send_count'],
                'next_send_time' => $next_send
            ]);

            error_log("Recurring message {$message['id']} sent successfully (count: {$message['send_count']})");
        } else {
            throw new Exception($result['error'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $recurring_failed++;

        // Log failure but don't disable the message
        log_audit_event('discord_recurring_failed', 'system', [
            'message_id' => $message['id'],
            'channel_id' => $message['channel_id'],
            'created_by' => $message['created_by'],
            'error' => $e->getMessage()
        ]);

        error_log("Recurring message {$message['id']} failed: " . $e->getMessage());
    }

    // Rate limiting: wait 1 second between messages
    sleep(1);
}

// Helper function for calculating next send time
function calculate_next_send_time($frequency, $time_of_day, $day_of_week = null, $day_of_month = null) {
    $now = time();
    list($hour, $minute) = explode(':', $time_of_day);

    switch ($frequency) {
        case 'daily':
            $next = strtotime("tomorrow {$time_of_day}");
            return date('Y-m-d H:i:s', $next);

        case 'weekly':
            $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                     'thursday' => 4, 'friday' => 5, 'saturday' => 6];
            $target_day = $days[strtolower($day_of_week)] ?? 0;
            $next = strtotime("next " . ucfirst($day_of_week) . " {$time_of_day}");
            return date('Y-m-d H:i:s', $next);

        case 'monthly':
            $target_day = (int)$day_of_month;
            $next_month = date('Y-m', strtotime('+1 month'));
            $next = strtotime("{$next_month}-{$target_day} {$time_of_day}");
            return date('Y-m-d H:i:s', $next);

        default:
            return null;
    }
}

// Save updated recurring data
save_scheduled_messages($recurring_file, $recurring_data);

// Summary
$total_sent = $messages_sent + $recurring_sent;
$total_failed = $messages_failed + $recurring_failed;

if ($total_sent > 0 || $total_failed > 0) {
    error_log("Discord processor completed: Scheduled({$messages_sent} sent, {$messages_failed} failed), Recurring({$recurring_sent} sent, {$recurring_failed} failed)");
}

exit(0);
?>
