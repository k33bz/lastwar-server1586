<?php
/**
 * Discord Announcement API
 * Handles AJAX requests for Discord message sending and configuration
 *
 * Documentation:
 * - Feature Request: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/FEATURE_REQUEST_DISCORD_BOT.md
 * - Bot Setup: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/discord-announcements/BOT-SETUP.md
 *
 * GitHub Issue: https://github.com/k33bz/lastwar-server1586/issues/59
 *
 * @version 1.0.1
 * @date 2025-11-06
 * @changelog
 *   1.0.1 (2025-11-06) - Added error handling for require statements and fatal errors
 *   1.0.0 (2025-11-04) - Initial implementation
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

// Wrap require statements in try-catch
try {
    require_once 'jwt.php';
    require_once 'json_helpers.php';
    require_once 'audit_logger.php';
    require_once 'discord_webhook.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error',
        'details' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit();
}

try {
    $user = require_jwt_session();

    // Check if Discord is enabled
    if (!DISCORD_ENABLED) {
        throw new Exception('Discord integration is disabled');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // CSRF Protection for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrfToken();
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'send_instant':
            // Require at least R4 role or president
            if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
                throw new Exception('Access denied. R4 or higher, or president role required.');
            }

            $channel_ids = json_decode($_POST['channel_ids'] ?? '[]', true);
            $message_content = $_POST['message'] ?? '';
            $use_embed = ($_POST['use_embed'] ?? 'false') === 'true';
            $embed_title = $_POST['embed_title'] ?? '';
            $embed_color = $_POST['embed_color'] ?? '3447003';

            if (empty($channel_ids)) {
                throw new Exception('At least one channel must be selected');
            }

            if (empty($message_content)) {
                throw new Exception('Message content is required');
            }

            // Check rate limiting (admins are exempt)
            $user_email = $user->sub;
            if ($user->aud !== 'admin' && !check_discord_rate_limit($user_email, 'instant')) {
                $user_limit = get_user_discord_rate_limit($user_email);
                log_audit_event('discord_rate_limit_exceeded', $user_email, [
                    'type' => 'instant',
                    'limit' => $user_limit
                ]);
                throw new Exception('Rate limit exceeded. Maximum ' . $user_limit . ' instant messages per hour. You can request an increase from your profile.');
            }

            // Validate channels and permissions
            $validated_channels = validate_user_channel_access($user, $channel_ids);

            if (empty($validated_channels)) {
                log_audit_event('discord_channel_access_denied', $user_email, [
                    'requested_channels' => $channel_ids,
                    'reason' => 'No accessible channels in selection'
                ]);
                throw new Exception('No accessible channels in selection');
            }

            // Get user display info for attribution
            $alliance_tags = get_user_alliance_tags($user_email);
            $display_name = get_user_display_name($user_email);
            $user_roles = get_user_roles($user);
            $primary_role = strtoupper($user_roles[0] ?? 'user');

            // Replace template variables in message content and title
            require_once __DIR__ . '/discord_variable_replacer.php';
            $message_content = replace_message_variables($message_content, $user_email);
            if ($embed_title) {
                $embed_title = replace_message_variables($embed_title, $user_email);
            }

            // Create message with user attribution (with alliance tags if available)
            $attribution = $alliance_tags ? "{$alliance_tags} {$display_name}" : $display_name;
            $footer_options = [
                'footer' => "{$attribution} ({$primary_role}) • Last War 1586 Bot"
            ];

            if ($use_embed) {
                $message = create_embed_announcement(
                    $embed_title ?: 'Announcement',
                    $message_content,
                    array_merge(['color' => (int)$embed_color], $footer_options)
                );
            } else {
                $message = create_simple_announcement($message_content, $footer_options);
            }

            // Send to channels
            $results = send_discord_message_multi($validated_channels, $message);

            // Log successful sends and collect errors
            $success_count = 0;
            $failed_channels = [];
            $error_messages = [];

            foreach ($results as $channel_id => $result) {
                // Get channel context for logging
                $channel_info = get_channel_info_for_logging($user, $channel_id);

                if ($result['success']) {
                    $success_count++;
                    $log_data = [
                        'channel_id' => $channel_id,
                        'type' => 'instant',
                        'message_preview' => substr($message_content, 0, 100)
                    ];

                    // Add channel context if available
                    if ($channel_info) {
                        $log_data = array_merge($log_data, $channel_info);
                    }

                    log_audit_event('discord_message_sent', $user_email, $log_data);
                } else {
                    $failed_channels[] = $channel_id;
                    $error_messages[$channel_id] = $result['error'] ?? 'Unknown error';
                    // Log the error to both error log and audit log
                    error_log("Discord send failed for channel {$channel_id}: " . ($result['error'] ?? 'Unknown error'));

                    $log_data = [
                        'channel_id' => $channel_id,
                        'type' => 'instant',
                        'error' => $result['error'] ?? 'Unknown error',
                        'message_preview' => substr($message_content, 0, 100)
                    ];

                    // Add channel context if available
                    if ($channel_info) {
                        $log_data = array_merge($log_data, $channel_info);
                    }

                    log_audit_event('discord_message_failed', $user_email, $log_data);
                }
            }

            // Save to history
            save_discord_history($user_email, 'instant', $message, $validated_channels, $results);

            // Return error if all channels failed
            if ($success_count === 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to send to any channels',
                    'results' => $results,
                    'failed_channels' => $failed_channels,
                    'error_messages' => $error_messages
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => "Sent to {$success_count}/" . count($validated_channels) . " channels",
                'results' => $results,
                'failed_channels' => $failed_channels,
                'error_messages' => $error_messages
            ]);
            break;

        case 'test_connection':
            // Admin only
            if ($user->aud !== 'admin') {
                throw new Exception('Access denied. Admin privileges required.');
            }

            $channel_id = $_POST['channel_id'] ?? '';

            if (empty($channel_id)) {
                throw new Exception('Channel ID is required');
            }

            $result = test_discord_connection($channel_id);

            // Log test attempt
            log_audit_event('discord_test_connection', $user->sub, [
                'channel_id' => $channel_id,
                'success' => $result['success'] ?? false,
                'error' => $result['error'] ?? null
            ]);

            echo json_encode($result);
            break;

        case 'validate_token':
            // Admin only
            if ($user->aud !== 'admin') {
                throw new Exception('Access denied. Admin privileges required.');
            }

            $result = validate_discord_bot_token();

            echo json_encode($result);
            break;

        case 'get_channels':
            // Get accessible channels for current user
            $channels = get_user_accessible_channels($user);

            // Log channel list access
            log_audit_event('discord_channels_accessed', $user->sub, [
                'channel_count' => count($channels)
            ]);

            echo json_encode([
                'success' => true,
                'channels' => $channels
            ]);
            break;

        case 'get_history':
            // Get message history
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            $history = get_discord_history($user, $limit, $offset);

            echo json_encode([
                'success' => true,
                'history' => $history['messages'],
                'total' => $history['total']
            ]);
            break;

        case 'get_channel_info':
            // Admin only
            if ($user->aud !== 'admin') {
                throw new Exception('Access denied. Admin privileges required.');
            }

            $channel_id = $_GET['channel_id'] ?? '';

            if (empty($channel_id)) {
                throw new Exception('Channel ID is required');
            }

            $info = get_discord_channel_info($channel_id);

            // Log channel info lookup
            log_audit_event('discord_channel_info_accessed', $user->sub, [
                'channel_id' => $channel_id,
                'channel_name' => $info['name'] ?? 'unknown'
            ]);

            echo json_encode([
                'success' => true,
                'channel' => $info
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    // Log the error
    error_log('Discord API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    log_audit_event('discord_api_error', $user->sub ?? 'unknown', [
        'action' => $action ?? 'unknown',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
} catch (Throwable $e) {
    // Catch any other errors (PHP 7+ fatal errors)
    error_log('Discord API Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal server error',
        'details' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Get user-specific Discord rate limit
 *
 * @param string $email User email
 * @return int Rate limit for instant messages per hour
 */
function get_user_discord_rate_limit($email) {
    require_once __DIR__ . '/json_helpers.php';

    $users_data = read_json_file(__DIR__ . '/users.json');

    foreach ($users_data['users'] as $user) {
        if ($user['email'] === $email) {
            // Return user-specific limit if set, otherwise default
            return $user['discord_rate_limit'] ?? DISCORD_MAX_INSTANT_PER_HOUR;
        }
    }

    // Default limit if user not found
    return DISCORD_MAX_INSTANT_PER_HOUR;
}

/**
 * Check Discord rate limits for user
 *
 * @param string $email User email
 * @param string $type Message type (instant, scheduled, recurring)
 * @return bool True if within limits
 */
function check_discord_rate_limit($email, $type) {
    $rate_limit_file = __DIR__ . '/discord_rate_limits.json';

    $limits = [];
    if (file_exists($rate_limit_file)) {
        $limits = json_decode(file_get_contents($rate_limit_file), true) ?: [];
    }

    $now = time();
    $one_hour_ago = $now - 3600;

    // Initialize user limits if not exists
    if (!isset($limits[$email])) {
        $limits[$email] = [
            'instant' => [],
            'scheduled' => 0,
            'recurring' => 0
        ];
    }

    switch ($type) {
        case 'instant':
            // Remove timestamps older than 1 hour
            $limits[$email]['instant'] = array_filter(
                $limits[$email]['instant'],
                function($timestamp) use ($one_hour_ago) {
                    return $timestamp > $one_hour_ago;
                }
            );

            // Get user-specific rate limit
            $user_limit = get_user_discord_rate_limit($email);

            // Check if limit exceeded
            if (count($limits[$email]['instant']) >= $user_limit) {
                return false;
            }

            // Add current timestamp
            $limits[$email]['instant'][] = $now;
            break;

        case 'scheduled':
            if ($limits[$email]['scheduled'] >= DISCORD_MAX_SCHEDULED_PENDING) {
                return false;
            }
            $limits[$email]['scheduled']++;
            break;

        case 'recurring':
            if ($limits[$email]['recurring'] >= DISCORD_MAX_RECURRING_ACTIVE) {
                return false;
            }
            $limits[$email]['recurring']++;
            break;
    }

    // Save updated limits
    file_put_contents($rate_limit_file, json_encode($limits, JSON_PRETTY_PRINT));

    return true;
}

/**
 * Validate user has access to specified channels
 *
 * @param object $user JWT user object
 * @param array $channel_ids Channel IDs to validate
 * @return array Validated channel IDs
 */
function validate_user_channel_access($user, $channel_ids) {
    $accessible_channels = get_user_accessible_channels($user);
    $accessible_ids = array_column($accessible_channels, 'id');

    return array_intersect($channel_ids, $accessible_ids);
}

/**
 * Get channel info by ID from user's accessible channels
 *
 * @param object $user JWT user object
 * @param string $channel_id Discord channel ID
 * @return array|null Channel info or null if not found
 */
function get_channel_info_for_logging($user, $channel_id) {
    $accessible_channels = get_user_accessible_channels($user);

    foreach ($accessible_channels as $channel) {
        if ($channel['id'] === $channel_id) {
            return [
                'channel_name' => $channel['name'] ?? 'Unknown Channel',
                'server_name' => $channel['server_name'] ?? 'Unknown Server',
                'alliance' => $channel['alliance'] ?? null,
                'alliance_name' => $channel['alliance_name'] ?? null
            ];
        }
    }

    return null;
}

/**
 * Get channels accessible by user based on role and alliances
 *
 * @param object $user JWT user object
 * @return array Array of accessible channels
 */
function get_user_accessible_channels($user) {
    $accessible_channels = [];

    // Get user's alliances
    $user_data = get_user_by_email($user->sub);
    if (!$user_data) {
        return [];
    }

    $user_alliances = $user_data['alliances'] ?? [];
    $is_admin = $user->aud === 'admin' || in_array('*', $user_alliances);
    $is_president = has_role($user, 'president');

    // 1. Load alliance-specific channels from alliances.json
    if (file_exists(ALLIANCES_FILE)) {
        $alliances_data = json_decode(file_get_contents(ALLIANCES_FILE), true);

        foreach ($alliances_data as $alliance) {
            $alliance_tag = $alliance['tag'] ?? $alliance['alliance'] ?? '';

            // Admins see ALL channels from ALL alliances
            // Regular users only see their alliance channels
            if ($is_admin) {
                $discord_channels = $alliance['discord']['channels'] ?? [];

                foreach ($discord_channels as $channel) {
                    if ($channel['enabled'] ?? false) {
                        // Add alliance context to channel
                        $channel['alliance'] = $alliance_tag;
                        $channel['alliance_name'] = $alliance['name'] ?? $alliance_tag;
                        $channel['server_name'] = $alliance['discord']['serverName'] ?? 'Discord';
                        $channel['source'] = 'alliance';
                        // Prefix channel name with alliance tag
                        $channel['display_name'] = '[' . $alliance_tag . '] ' . $channel['name'];
                        $accessible_channels[] = $channel;
                    }
                }
            } elseif (in_array($alliance_tag, $user_alliances)) {
                // Regular users see only their alliance channels
                $discord_channels = $alliance['discord']['channels'] ?? [];

                foreach ($discord_channels as $channel) {
                    if ($channel['enabled'] ?? false) {
                        // Add alliance context to channel
                        $channel['alliance'] = $alliance_tag;
                        $channel['alliance_name'] = $alliance['name'] ?? $alliance_tag;
                        $channel['server_name'] = $alliance['discord']['serverName'] ?? 'Discord';
                        $channel['source'] = 'alliance';
                        // Prefix channel name with alliance tag
                        $channel['display_name'] = '[' . $alliance_tag . '] ' . $channel['name'];
                        $accessible_channels[] = $channel;
                    }
                }
            } elseif ($is_president) {
                // Presidents can access "general" type channels from all alliances
                $discord_channels = $alliance['discord']['channels'] ?? [];

                foreach ($discord_channels as $channel) {
                    if (($channel['enabled'] ?? false) && ($channel['type'] ?? '') === 'general') {
                        // Add alliance context to channel
                        $channel['alliance'] = $alliance_tag;
                        $channel['alliance_name'] = $alliance['name'] ?? $alliance_tag;
                        $channel['server_name'] = $alliance['discord']['serverName'] ?? 'Discord';
                        $channel['source'] = 'alliance';
                        // Prefix channel name with alliance tag
                        $channel['display_name'] = '[' . $alliance_tag . '] ' . $channel['name'];
                        $accessible_channels[] = $channel;
                    }
                }
            }
        }
    }

    // 2. Load global/cross-alliance channels from discord-channels.json
    if (file_exists(DISCORD_CHANNELS_FILE)) {
        $data = json_decode(file_get_contents(DISCORD_CHANNELS_FILE), true);
        $global_channels = $data['global_channels'] ?? [];

        foreach ($global_channels as $channel) {
            if ($channel['enabled'] ?? false) {
                // Global channels accessible by all, or check permissions
                $channel_alliance = $channel['alliance'] ?? '*';

                if ($is_admin || $channel_alliance === '*' || in_array($channel_alliance, $user_alliances)) {
                    $channel['source'] = 'global';
                    $accessible_channels[] = $channel;
                }
            }
        }
    }

    return $accessible_channels;
}

/**
 * Save message to Discord history
 *
 * @param string $user_email User who sent message
 * @param string $type Message type
 * @param array $message Message data
 * @param array $targets Target channel IDs
 * @param array $results Send results
 */
function save_discord_history($user_email, $type, $message, $targets, $results) {
    $history_file = DISCORD_HISTORY_FILE;

    $history = ['messages' => []];
    if (file_exists($history_file)) {
        $history = json_decode(file_get_contents($history_file), true) ?: ['messages' => []];
    }

    // Generate unique ID
    $message_id = 'msg_' . bin2hex(random_bytes(8));

    // Extract message preview
    $preview = '';
    if (isset($message['content'])) {
        $preview = substr($message['content'], 0, 200);
    } elseif (isset($message['embeds'][0]['description'])) {
        $preview = substr($message['embeds'][0]['description'], 0, 200);
    }

    // Count successes
    $success_count = count(array_filter($results, function($r) { return $r['success']; }));

    // Add to history
    $history['messages'][] = [
        'id' => $message_id,
        'type' => $type,
        'message' => $message,
        'preview' => $preview,
        'targets' => $targets,
        'sent_by' => $user_email,
        'sent_at' => date('c'),
        'status' => $success_count === count($targets) ? 'success' : 'partial',
        'success_count' => $success_count,
        'total_count' => count($targets),
        'discord_message_ids' => array_column($results, 'message_id', 'channel_id')
    ];

    // Keep only last 1000 messages
    if (count($history['messages']) > 1000) {
        $history['messages'] = array_slice($history['messages'], -1000);
    }

    file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT));
}

/**
 * Get Discord message history
 *
 * @param object $user JWT user object
 * @param int $limit Number of messages to retrieve
 * @param int $offset Offset for pagination
 * @return array History data
 */
function get_discord_history($user, $limit = 50, $offset = 0) {
    if (!file_exists(DISCORD_HISTORY_FILE)) {
        return ['messages' => [], 'total' => 0];
    }

    $history = json_decode(file_get_contents(DISCORD_HISTORY_FILE), true) ?: ['messages' => []];
    $all_messages = $history['messages'];

    // Filter by user (non-admin only sees their own)
    if ($user->aud !== 'admin') {
        $all_messages = array_filter($all_messages, function($msg) use ($user) {
            return $msg['sent_by'] === $user->sub;
        });
    }

    // Sort by date (newest first)
    usort($all_messages, function($a, $b) {
        return strtotime($b['sent_at']) - strtotime($a['sent_at']);
    });

    $total = count($all_messages);
    $messages = array_slice($all_messages, $offset, $limit);

    return [
        'messages' => $messages,
        'total' => $total
    ];
}
?>
