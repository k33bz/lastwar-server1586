<?php
/**
 * Discord Message Tracking System
 *
 * Tracks sent Discord messages and handles automatic deletion after X hours
 *
 * @version 1.0.0
 * @date 2025-11-07
 * @changelog
 *   1.0.0 (2025-11-07) - Initial implementation
 *                       - Message tracking with timestamps
 *                       - Configurable auto-delete times
 *                       - Cleanup processor support
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}

require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

// Message tracking file path
define('MESSAGE_TRACKING_FILE', __DIR__ . '/discord_message_tracking.json');

/**
 * Track a sent Discord message for potential auto-deletion
 *
 * @param string $internal_id Internal message ID (msg_xxx)
 * @param string $discord_message_id Discord's message ID
 * @param string $channel_id Discord channel ID
 * @param int|null $delete_after_hours Hours until auto-delete (null = never)
 * @param string $message_type Type of message (instant, scheduled, recurring)
 * @param string $user_email User who sent the message
 * @return bool Success status
 */
function track_discord_message($internal_id, $discord_message_id, $channel_id, $delete_after_hours = null, $message_type = 'instant', $user_email = 'system') {
    try {
        // Skip tracking if no auto-delete
        if ($delete_after_hours === null || $delete_after_hours === 0) {
            return true;
        }

        $sent_at = gmdate('Y-m-d H:i:s');
        $delete_at = gmdate('Y-m-d H:i:s', strtotime($sent_at) + ($delete_after_hours * 3600));

        $tracking_entry = [
            'id' => $internal_id,
            'discord_message_id' => $discord_message_id,
            'channel_id' => $channel_id,
            'sent_at' => $sent_at,
            'delete_after_hours' => $delete_after_hours,
            'delete_at' => $delete_at,
            'deleted' => false,
            'message_type' => $message_type,
            'created_by' => $user_email
        ];

        // Add to tracking file
        $result = update_json_file(MESSAGE_TRACKING_FILE, function(&$data) use ($tracking_entry) {
            if (!isset($data['tracked_messages'])) {
                $data['tracked_messages'] = [];
            }

            // Add new tracking entry
            $data['tracked_messages'][] = $tracking_entry;

            // Keep only messages from last 30 days to prevent file bloat
            $cutoff_date = gmdate('Y-m-d H:i:s', time() - (30 * 24 * 3600));
            $data['tracked_messages'] = array_filter($data['tracked_messages'], function($msg) use ($cutoff_date) {
                return $msg['sent_at'] > $cutoff_date || !$msg['deleted'];
            });

            // Re-index array
            $data['tracked_messages'] = array_values($data['tracked_messages']);

            return true;
        });

        if ($result) {
            error_log("Discord Message Tracker: Tracking message {$internal_id} for deletion in {$delete_after_hours}h");
        }

        return $result;

    } catch (Exception $e) {
        error_log("Discord Message Tracker Error [track_discord_message]: " . $e->getMessage());
        return false;
    }
}

/**
 * Get messages that are ready for deletion
 *
 * @return array Array of messages ready to be deleted
 */
function get_messages_for_deletion() {
    try {
        $data = read_json_file(MESSAGE_TRACKING_FILE);
        $messages = $data['tracked_messages'] ?? [];
        $current_time = gmdate('Y-m-d H:i:s');

        // Filter for non-deleted messages past their delete_at time
        $ready_for_deletion = array_filter($messages, function($msg) use ($current_time) {
            return !$msg['deleted'] && $msg['delete_at'] <= $current_time;
        });

        return array_values($ready_for_deletion);

    } catch (Exception $e) {
        error_log("Discord Message Tracker Error [get_messages_for_deletion]: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark message as deleted in tracking
 *
 * @param string $internal_id Internal message ID
 * @param bool $success Whether deletion succeeded
 * @param string|null $error Error message if failed
 * @return bool Success status
 */
function mark_message_deleted($internal_id, $success = true, $error = null) {
    try {
        return update_json_file(MESSAGE_TRACKING_FILE, function(&$data) use ($internal_id, $success, $error) {
            if (!isset($data['tracked_messages'])) {
                return false;
            }

            $found = false;
            foreach ($data['tracked_messages'] as &$msg) {
                if ($msg['id'] === $internal_id) {
                    $msg['deleted'] = $success;
                    $msg['deleted_at'] = gmdate('Y-m-d H:i:s');
                    if ($error) {
                        $msg['deletion_error'] = $error;
                    }
                    $found = true;
                    break;
                }
            }

            return $found;
        });

    } catch (Exception $e) {
        error_log("Discord Message Tracker Error [mark_message_deleted]: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a Discord message
 *
 * @param string $channel_id Discord channel ID
 * @param string $discord_message_id Discord message ID
 * @return array Result with success status
 */
function delete_discord_message($channel_id, $discord_message_id) {
    try {
        if (!defined('DISCORD_BOT_TOKEN') || empty(DISCORD_BOT_TOKEN)) {
            throw new Exception('Discord bot token not configured');
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://discord.com/api/v10/',
            'timeout' => 10.0,
        ]);

        $response = $client->delete("channels/{$channel_id}/messages/{$discord_message_id}", [
            'headers' => [
                'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
                'Content-Type' => 'application/json'
            ]
        ]);

        return [
            'success' => true,
            'status_code' => $response->getStatusCode()
        ];

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $status_code = $e->getResponse()->getStatusCode();
        $error_body = json_decode($e->getResponse()->getBody()->getContents(), true);
        $error_message = $error_body['message'] ?? 'Unknown error';

        // 404 means message already deleted - treat as success
        if ($status_code === 404) {
            return [
                'success' => true,
                'already_deleted' => true,
                'message' => 'Message already deleted or not found'
            ];
        }

        throw new Exception("Discord API error ({$status_code}): {$error_message}");

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get tracking statistics
 *
 * @return array Statistics about tracked messages
 */
function get_tracking_stats() {
    try {
        $data = read_json_file(MESSAGE_TRACKING_FILE);
        $messages = $data['tracked_messages'] ?? [];

        $stats = [
            'total_tracked' => count($messages),
            'pending_deletion' => 0,
            'deleted' => 0,
            'by_type' => [
                'instant' => 0,
                'scheduled' => 0,
                'recurring' => 0
            ],
            'by_delete_time' => [
                '1h' => 0,
                '6h' => 0,
                '12h' => 0,
                '24h' => 0,
                '48h' => 0,
                'other' => 0
            ]
        ];

        $current_time = gmdate('Y-m-d H:i:s');

        foreach ($messages as $msg) {
            if ($msg['deleted']) {
                $stats['deleted']++;
            } elseif ($msg['delete_at'] <= $current_time) {
                $stats['pending_deletion']++;
            }

            // Count by type
            $type = $msg['message_type'] ?? 'instant';
            if (isset($stats['by_type'][$type])) {
                $stats['by_type'][$type]++;
            }

            // Count by delete time
            $hours = $msg['delete_after_hours'] ?? 0;
            if ($hours == 1) $stats['by_delete_time']['1h']++;
            elseif ($hours == 6) $stats['by_delete_time']['6h']++;
            elseif ($hours == 12) $stats['by_delete_time']['12h']++;
            elseif ($hours == 24) $stats['by_delete_time']['24h']++;
            elseif ($hours == 48) $stats['by_delete_time']['48h']++;
            else $stats['by_delete_time']['other']++;
        }

        return $stats;

    } catch (Exception $e) {
        error_log("Discord Message Tracker Error [get_tracking_stats]: " . $e->getMessage());
        return [];
    }
}
?>
