<?php
/**
 * Discord Message Cleanup Processor
 *
 * Cron job that runs every minute to delete old Discord messages
 * based on their auto-delete settings
 *
 * Cron setup:
 * * * * * * /usr/bin/php /path/to/admin/discord_cleanup_processor.php >> /path/to/logs/discord_cleanup.log 2>&1
 *
 * @version 1.0.0
 * @date 2025-11-07
 */

// Change to script directory
chdir(__DIR__);

// Load dependencies
require_once 'config.php';
require_once 'discord_message_tracker.php';
require_once 'audit_logger.php';

// Check if Discord is enabled
if (!defined('DISCORD_ENABLED') || !DISCORD_ENABLED) {
    error_log('Discord Message Cleanup: Discord is disabled, skipping');
    exit(0);
}

// Get messages ready for deletion
$messages_to_delete = get_messages_for_deletion();

if (empty($messages_to_delete)) {
    // No messages to delete, exit silently
    exit(0);
}

$deleted_count = 0;
$failed_count = 0;
$errors = [];

foreach ($messages_to_delete as $message) {
    try {
        // Delete message from Discord
        $result = delete_discord_message($message['channel_id'], $message['discord_message_id']);

        if ($result['success']) {
            // Mark as deleted in tracking
            mark_message_deleted($message['id'], true);
            $deleted_count++;

            // Log deletion to audit log
            log_audit_event('discord_message_auto_deleted', 'system', [
                'message_id' => $message['id'],
                'discord_message_id' => $message['discord_message_id'],
                'channel_id' => $message['channel_id'],
                'message_type' => $message['message_type'],
                'sent_at' => $message['sent_at'],
                'delete_after_hours' => $message['delete_after_hours'],
                'created_by' => $message['created_by'],
                'already_deleted' => $result['already_deleted'] ?? false
            ]);

            error_log("Discord Cleanup: Deleted message {$message['id']} (Discord ID: {$message['discord_message_id']})");
        } else {
            // Mark as failed
            mark_message_deleted($message['id'], false, $result['error'] ?? 'Unknown error');
            $failed_count++;
            $errors[] = [
                'message_id' => $message['id'],
                'error' => $result['error'] ?? 'Unknown error'
            ];

            error_log("Discord Cleanup: Failed to delete message {$message['id']}: " . ($result['error'] ?? 'Unknown error'));
        }

        // Rate limiting: wait 100ms between deletions
        usleep(100000);

    } catch (Exception $e) {
        $failed_count++;
        $errors[] = [
            'message_id' => $message['id'],
            'error' => $e->getMessage()
        ];

        mark_message_deleted($message['id'], false, $e->getMessage());

        error_log("Discord Cleanup Exception: " . $e->getMessage());
    }
}

// Log summary
if ($deleted_count > 0 || $failed_count > 0) {
    error_log("Discord Cleanup Summary: Deleted {$deleted_count}, Failed {$failed_count}");

    // Log summary to audit log
    log_audit_event('discord_cleanup_run', 'system', [
        'messages_processed' => count($messages_to_delete),
        'deleted' => $deleted_count,
        'failed' => $failed_count,
        'errors' => $failed_count > 0 ? $errors : []
    ]);
}

exit(0);
?>
