<?php
/**
 * Discord Scheduled Messages Processor
 * Version: 1.0.0 (Phase 2 - Scheduled messaging)
 *
 * Processes and sends scheduled Discord messages
 * Run this via cron every minute: * * * * * php /path/to/admin/discord_scheduled_processor.php
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/discord_webhook.php';
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

    // Prepare Discord message
    if ($message['use_embed']) {
        $discord_message = create_rich_announcement(
            $message['message'],
            $message['embed_title'] ?? 'Scheduled Announcement',
            $message['embed_color'] ?? '#5865F2',
            $message['created_by']
        );
    } else {
        $discord_message = create_simple_announcement($message['message']);
    }

    // Send message
    try {
        $result = send_discord_message($message['channel_id'], $discord_message);

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

// Summary
if ($messages_sent > 0 || $messages_failed > 0) {
    error_log("Discord scheduled processor completed: {$messages_sent} sent, {$messages_failed} failed");
}

exit(0);
?>
