<?php
/**
 * Discord Template Variable Replacer
 * Version: 1.0.0
 *
 * Replaces template variables with actual values in Discord messages
 */

/**
 * Replace all template variables in a message with actual values
 *
 * @param string $message - Message content with variables like {sender_name}
 * @param string $sender_email - Email of user sending the message
 * @param string $channel_id - Discord channel ID (optional, for channel-specific data)
 * @return string - Message with variables replaced
 */
function replace_message_variables($message, $sender_email, $channel_id = null) {
    require_once __DIR__ . '/json_helpers.php';

    // Get sender user data
    $sender_data = get_user_by_email($sender_email);

    // Get alliance data if user has alliance
    $alliance_data = null;
    if ($sender_data && !empty($sender_data['alliance'])) {
        $alliance_data = get_alliance_data($sender_data['alliance']);
    }

    // Get channel-specific data if channel ID provided
    $channel_alliance = null;
    if ($channel_id) {
        $channel_alliance = get_channel_alliance($channel_id);
        // If channel has specific alliance, use that instead
        if ($channel_alliance && $channel_alliance !== $sender_data['alliance']) {
            $alliance_data = get_alliance_data($channel_alliance);
        }
    }

    // Build replacement map
    $replacements = [];

    // Server variables
    $replacements['{server_name}'] = $_ENV['APP_NAME'] ?? 'Server 1586';
    $replacements['{server_reset_time}'] = get_server_reset_time();

    // User variables
    if ($sender_data) {
        $replacements['{sender_name}'] = $sender_data['ign'] ?? $sender_data['email'];
        $replacements['{sender_alliance}'] = $sender_data['alliance'] ?? 'None';
        $replacements['{sender_tag}'] = $alliance_data ? ($alliance_data['tag'] ?? '') : '';
    } else {
        $replacements['{sender_name}'] = $sender_email;
        $replacements['{sender_alliance}'] = 'None';
        $replacements['{sender_tag}'] = '';
    }

    // Alliance variables
    if ($alliance_data) {
        $replacements['{alliance_name}'] = $alliance_data['name'] ?? 'Unknown Alliance';
        $replacements['{alliance_tag}'] = $alliance_data['tag'] ?? '';
        $replacements['{r5_name}'] = $alliance_data['leader'] ?? 'Unknown';
    } else {
        $replacements['{alliance_name}'] = 'No Alliance';
        $replacements['{alliance_tag}'] = '';
        $replacements['{r5_name}'] = 'N/A';
    }

    // DateTime variables
    $replacements['{date}'] = date('Y-m-d');
    $replacements['{time}'] = date('H:i');
    $replacements['{datetime}'] = date('Y-m-d H:i');

    // Custom variables - these stay as placeholders for user to manually replace
    // They're not automatically replaced since they're contextual to each message
    // Users can type actual values when creating messages
    // (We keep them as-is so users see they need to be filled in)

    // Perform replacement
    $processed_message = str_replace(array_keys($replacements), array_values($replacements), $message);

    return $processed_message;
}

/**
 * Get alliance data by tag
 */
function get_alliance_data($alliance_tag) {
    $alliances_file = __DIR__ . '/../data/alliances.json';
    if (!file_exists($alliances_file)) {
        return null;
    }

    $alliances = json_decode(file_get_contents($alliances_file), true);
    if (!$alliances) {
        return null;
    }

    foreach ($alliances as $alliance) {
        if ($alliance['tag'] === $alliance_tag) {
            return $alliance;
        }
    }

    return null;
}

/**
 * Get channel's associated alliance from Discord webhook config
 */
function get_channel_alliance($channel_id) {
    require_once __DIR__ . '/discord_webhook.php';

    if (!defined('DISCORD_WEBHOOKS') || !DISCORD_WEBHOOKS) {
        return null;
    }

    foreach (DISCORD_WEBHOOKS as $channel) {
        if ($channel['channel_id'] === $channel_id) {
            return $channel['alliance'] ?? null;
        }
    }

    return null;
}

/**
 * Get server reset time from config or default
 */
function get_server_reset_time() {
    // Check if there's a config for server reset time
    $config_file = __DIR__ . '/server_config.json';
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
        if ($config && isset($config['server_reset_time'])) {
            return $config['server_reset_time'];
        }
    }

    // Default reset time (UTC)
    return '00:00 UTC';
}

/**
 * Preview variable replacement (for testing/display purposes)
 * Shows what variables will be replaced with, without actually replacing them
 *
 * @param string $sender_email - Email of user
 * @param string $channel_id - Optional channel ID
 * @return array - Map of variables to their replacement values
 */
function preview_variable_replacements($sender_email, $channel_id = null) {
    // Get sender user data
    $sender_data = get_user_by_email($sender_email);

    // Get alliance data
    $alliance_data = null;
    if ($sender_data && !empty($sender_data['alliance'])) {
        $alliance_data = get_alliance_data($sender_data['alliance']);
    }

    // Build preview map
    $preview = [];

    // Server variables
    $preview['{server_name}'] = $_ENV['APP_NAME'] ?? 'Server 1586';
    $preview['{server_reset_time}'] = get_server_reset_time();

    // User variables
    if ($sender_data) {
        $preview['{sender_name}'] = $sender_data['ign'] ?? $sender_data['email'];
        $preview['{sender_alliance}'] = $sender_data['alliance'] ?? 'None';
        $preview['{sender_tag}'] = $alliance_data ? ($alliance_data['tag'] ?? '') : '';
    } else {
        $preview['{sender_name}'] = $sender_email;
        $preview['{sender_alliance}'] = 'None';
        $preview['{sender_tag}'] = '';
    }

    // Alliance variables
    if ($alliance_data) {
        $preview['{alliance_name}'] = $alliance_data['name'] ?? 'Unknown Alliance';
        $preview['{alliance_tag}'] = $alliance_data['tag'] ?? '';
        $preview['{r5_name}'] = $alliance_data['leader'] ?? 'Unknown';
    } else {
        $preview['{alliance_name}'] = 'No Alliance';
        $preview['{alliance_tag}'] = '';
        $preview['{r5_name}'] = 'N/A';
    }

    // DateTime variables
    $preview['{date}'] = date('Y-m-d');
    $preview['{time}'] = date('H:i');
    $preview['{datetime}'] = date('Y-m-d H:i');

    // Custom variables - shown as prompts
    $preview['{event_time}'] = '[Enter event time]';
    $preview['{event_name}'] = '[Enter event name]';
    $preview['{location}'] = '[Enter location]';
    $preview['{notes}'] = '[Enter notes]';

    return $preview;
}
?>
