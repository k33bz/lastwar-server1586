<?php
/**
 * Discord Channel Management API
 * Version: 1.0.1
 *
 * Centralized management for Discord channels across alliances and global channels
 *
 * Changelog:
 *   1.0.1 (2025-11-12) - Fixed authentication to use require_jwt_session_api() for proper JSON error responses
 *   1.0.0 (2025-11-09) - Initial implementation
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'json_helpers.php';
require_once 'includes/csrf.php';

header('Content-Type: application/json');

$user = require_jwt_session_api();

// CSRF Protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
}

// Check if user has at least R4 access or admin
if (!has_role($user, ['admin', 'r5', 'r4'])) {
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

/**
 * Get all Discord channels from all sources
 */
function get_all_discord_channels($user) {
    $channels = [];
    $user_data = get_user_by_email($user->sub);
    $user_alliances = $user_data['alliances'] ?? [];
    $is_admin = $user->aud === 'admin' || in_array('*', $user_alliances);

    // 1. Load alliance-specific channels from alliances.json
    if (file_exists(ALLIANCES_FILE)) {
        $alliances_data = json_decode(file_get_contents(ALLIANCES_FILE), true);

        foreach ($alliances_data as $alliance) {
            $alliance_tag = $alliance['tag'] ?? $alliance['alliance'] ?? '';
            $discord_channels = $alliance['discord']['channels'] ?? [];

            // Only show channels user has access to
            if ($is_admin || in_array($alliance_tag, $user_alliances)) {
                foreach ($discord_channels as $channel) {
                    $channels[] = [
                        'id' => $channel['id'] ?? '',
                        'name' => $channel['name'] ?? '',
                        'type' => $channel['type'] ?? 'general',
                        'enabled' => $channel['enabled'] ?? false,
                        'webhook_url' => $channel['webhook_url'] ?? '',
                        'alliance' => $alliance_tag,
                        'alliance_name' => $alliance['name'] ?? $alliance_tag,
                        'server_name' => $alliance['discord']['serverName'] ?? 'Discord',
                        'source' => 'alliance',
                        'can_edit' => $is_admin || in_array($alliance_tag, $user_alliances)
                    ];
                }
            }
        }
    }

    // 2. Load global/cross-alliance channels from discord-channels.json
    if (file_exists(DISCORD_CHANNELS_FILE)) {
        $data = json_decode(file_get_contents(DISCORD_CHANNELS_FILE), true);
        $global_channels = $data['global_channels'] ?? [];

        foreach ($global_channels as $channel) {
            $channel_alliance = $channel['alliance'] ?? '*';

            if ($is_admin || $channel_alliance === '*' || in_array($channel_alliance, $user_alliances)) {
                $channels[] = [
                    'id' => $channel['id'] ?? '',
                    'name' => $channel['name'] ?? '',
                    'type' => $channel['type'] ?? 'general',
                    'enabled' => $channel['enabled'] ?? false,
                    'webhook_url' => $channel['webhook_url'] ?? '',
                    'alliance' => $channel_alliance,
                    'alliance_name' => $channel_alliance === '*' ? 'All Alliances' : $channel_alliance,
                    'server_name' => $channel['server_name'] ?? 'Discord',
                    'source' => 'global',
                    'can_edit' => $is_admin
                ];
            }
        }
    }

    return $channels;
}

/**
 * Test a Discord webhook
 */
function test_discord_webhook($webhook_url) {
    $test_message = [
        'embeds' => [[
            'title' => '✅ Webhook Test',
            'description' => 'This is a test message from Last War 1586 Admin Panel',
            'color' => hexdec('27ae60'),
            'timestamp' => date('c'),
            'footer' => [
                'text' => 'Last War 1586 Bot • Test Message'
            ]
        ]]
    ];

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'error' => $curl_error];
    }

    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'message' => 'Webhook test successful!'];
    } else {
        return ['success' => false, 'error' => "HTTP $http_code: $response"];
    }
}

switch ($action) {
    case 'list':
        $channels = get_all_discord_channels($user);

        echo json_encode([
            'success' => true,
            'channels' => $channels
        ]);

        log_audit_event('discord_channels_list', $user->sub, [
            'channel_count' => count($channels)
        ]);
        break;

    case 'test_webhook':
        $webhook_url = $_POST['webhook_url'] ?? '';

        if (empty($webhook_url)) {
            echo json_encode(['success' => false, 'error' => 'Missing webhook URL']);
            exit();
        }

        $result = test_discord_webhook($webhook_url);

        log_audit_event('discord_webhook_test', $user->sub, [
            'success' => $result['success'],
            'webhook_url' => substr($webhook_url, 0, 50) . '...' // Truncate for security
        ]);

        echo json_encode($result);
        break;

    case 'update_channel':
        // Only admins can update channels for now
        if ($user->aud !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only admins can update channels']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $channel_id = $input['id'] ?? '';
        $source = $input['source'] ?? '';

        if (empty($channel_id) || empty($source)) {
            echo json_encode(['success' => false, 'error' => 'Missing channel ID or source']);
            exit();
        }

        if ($source === 'alliance') {
            // Update alliance channel
            $alliance_tag = $input['alliance'] ?? '';
            if (empty($alliance_tag)) {
                echo json_encode(['success' => false, 'error' => 'Missing alliance tag']);
                exit();
            }

            $alliances_data = json_decode(file_get_contents(ALLIANCES_FILE), true);
            $updated = false;

            foreach ($alliances_data as &$alliance) {
                if (($alliance['tag'] ?? $alliance['alliance'] ?? '') === $alliance_tag) {
                    $channels = &$alliance['discord']['channels'];
                    foreach ($channels as &$channel) {
                        if ($channel['id'] === $channel_id) {
                            $channel['enabled'] = $input['enabled'] ?? $channel['enabled'];
                            $channel['name'] = $input['name'] ?? $channel['name'];
                            $channel['type'] = $input['type'] ?? $channel['type'];
                            $channel['webhook_url'] = $input['webhook_url'] ?? $channel['webhook_url'];
                            $updated = true;
                            break 2;
                        }
                    }
                }
            }

            if ($updated && save_json_file(ALLIANCES_FILE, $alliances_data)) {
                echo json_encode(['success' => true]);
                log_audit_event('discord_channel_update', $user->sub, [
                    'channel_id' => $channel_id,
                    'alliance' => $alliance_tag,
                    'source' => 'alliance'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update channel']);
            }
        } elseif ($source === 'global') {
            // Update global channel
            $data = json_decode(file_get_contents(DISCORD_CHANNELS_FILE), true);
            $updated = false;

            foreach ($data['global_channels'] as &$channel) {
                if ($channel['id'] === $channel_id) {
                    $channel['enabled'] = $input['enabled'] ?? $channel['enabled'];
                    $channel['name'] = $input['name'] ?? $channel['name'];
                    $channel['type'] = $input['type'] ?? $channel['type'];
                    $channel['webhook_url'] = $input['webhook_url'] ?? $channel['webhook_url'];
                    $channel['alliance'] = $input['alliance'] ?? $channel['alliance'];
                    $updated = true;
                    break;
                }
            }

            if ($updated && file_put_contents(DISCORD_CHANNELS_FILE, json_encode($data, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
                log_audit_event('discord_channel_update', $user->sub, [
                    'channel_id' => $channel_id,
                    'source' => 'global'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update channel']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid source']);
        }
        break;

    case 'toggle':
        // Toggle channel enabled/disabled
        if ($user->aud !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only admins can toggle channels']);
            exit();
        }

        $channel_id = $_POST['channel_id'] ?? '';
        $enabled = filter_var($_POST['enabled'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $source = $_POST['source'] ?? '';

        if (empty($channel_id) || empty($source)) {
            echo json_encode(['success' => false, 'error' => 'Missing channel ID or source']);
            exit();
        }

        if ($source === 'alliance') {
            $alliance_tag = $_POST['alliance'] ?? '';
            $alliances_data = json_decode(file_get_contents(ALLIANCES_FILE), true);
            $updated = false;

            foreach ($alliances_data as &$alliance) {
                if (($alliance['tag'] ?? $alliance['alliance'] ?? '') === $alliance_tag) {
                    foreach ($alliance['discord']['channels'] as &$channel) {
                        if ($channel['id'] === $channel_id) {
                            $channel['enabled'] = $enabled;
                            $updated = true;
                            break 2;
                        }
                    }
                }
            }

            if ($updated && save_json_file(ALLIANCES_FILE, $alliances_data)) {
                echo json_encode(['success' => true]);
                log_audit_event('discord_channel_toggle', $user->sub, [
                    'channel_id' => $channel_id,
                    'enabled' => $enabled,
                    'source' => 'alliance'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to toggle channel']);
            }
        } elseif ($source === 'global') {
            $data = json_decode(file_get_contents(DISCORD_CHANNELS_FILE), true);
            $updated = false;

            foreach ($data['global_channels'] as &$channel) {
                if ($channel['id'] === $channel_id) {
                    $channel['enabled'] = $enabled;
                    $updated = true;
                    break;
                }
            }

            if ($updated && file_put_contents(DISCORD_CHANNELS_FILE, json_encode($data, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
                log_audit_event('discord_channel_toggle', $user->sub, [
                    'channel_id' => $channel_id,
                    'enabled' => $enabled,
                    'source' => 'global'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to toggle channel']);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
