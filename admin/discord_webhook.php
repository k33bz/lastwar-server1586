<?php
/**
 * Discord Webhook and Bot API Functions
 *
 * Handles Discord message sending for announcements via webhooks and bot API
 *
 * Documentation:
 * - Feature Request: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/FEATURE_REQUEST_DISCORD_BOT.md
 * - Bot Setup: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/discord-announcements/BOT-SETUP.md
 * - Environment Configuration: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/admin/ENV-CONFIG.md
 *
 * GitHub Issue: https://github.com/k33bz/lastwar-server1586/issues/59
 *
 * @version 1.0.0
 * @date 2025-11-04
 * @changelog
 *   1.0.0 (2025-11-04) - Initial implementation
 *                      - Discord bot API message sending
 *                      - Webhook fallback support
 *                      - Embed message formatting
 *                      - Rate limiting compliance
 *                      - Error handling and retry logic
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;

/**
 * Discord API Configuration
 */
define('DISCORD_API_BASE', 'https://discord.com/api/v10');
define('DISCORD_BOT_INVITE_LINK', 'https://discord.com/oauth2/authorize?client_id=1435336079409545256&permissions=395137378368&integration_type=0&scope=bot');
define('DISCORD_RATE_LIMIT_DELAY', 1); // seconds between requests
define('DISCORD_MAX_RETRIES', 3);
define('DISCORD_RETRY_DELAY', 5); // seconds

/**
 * Send message to Discord channel via Bot API
 *
 * @param string $channel_id Discord channel ID
 * @param array $message Message data (content, embeds, etc.)
 * @param int $retry_count Current retry attempt (internal use)
 * @return array Response with success status and Discord message ID
 * @throws Exception if message cannot be sent
 */
function send_discord_message($channel_id, $message, $retry_count = 0) {
    if (!defined('DISCORD_BOT_TOKEN') || empty(DISCORD_BOT_TOKEN)) {
        throw new Exception('Discord bot token not configured. Please set DISCORD_BOT_TOKEN in admin/.env');
    }

    if (!defined('DISCORD_ENABLED') || !DISCORD_ENABLED) {
        throw new Exception('Discord integration is disabled. Set DISCORD_ENABLED=true in admin/.env');
    }

    // Validate channel ID format
    if (!preg_match('/^\d{15,20}$/', $channel_id)) {
        throw new Exception('Invalid Discord channel ID format');
    }

    // Validate message
    if (empty($message['content']) && empty($message['embeds'])) {
        throw new Exception('Message must contain either content or embeds');
    }

    // Validate message length
    if (isset($message['content']) && strlen($message['content']) > 2000) {
        throw new Exception('Message content exceeds Discord limit of 2000 characters');
    }

    $client_options = [
        'timeout' => 30,
        'verify' => false, // Disable SSL verification in development (Windows SSL cert issue)
        'http_errors' => true // Throw exceptions on HTTP errors
    ];

    $client = new Client($client_options);

    try {
        // Debug: Log the message being sent
        error_log("Discord message to channel {$channel_id}: " . json_encode($message));

        $response = $client->post(DISCORD_API_BASE . "/channels/{$channel_id}/messages", [
            'headers' => [
                'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Server1586Bot (https://www.lastwar1586.online, 1.0.0)'
            ],
            'json' => $message
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        return [
            'success' => true,
            'message_id' => $body['id'] ?? null,
            'channel_id' => $channel_id,
            'timestamp' => $body['timestamp'] ?? null
        ];

    } catch (ClientException $e) {
        $status_code = $e->getResponse()->getStatusCode();
        $error_body = json_decode($e->getResponse()->getBody()->getContents(), true);
        $error_message = $error_body['message'] ?? 'Unknown error';

        // Debug: Log full error response
        error_log("Discord API error ({$status_code}): " . json_encode($error_body));

        // Handle rate limiting (429)
        if ($status_code === 429) {
            $retry_after = $error_body['retry_after'] ?? DISCORD_RETRY_DELAY;

            if ($retry_count < DISCORD_MAX_RETRIES) {
                error_log("Discord rate limit hit, retrying after {$retry_after} seconds (attempt " . ($retry_count + 1) . ")");
                sleep($retry_after);
                return send_discord_message($channel_id, $message, $retry_count + 1);
            } else {
                throw new Exception("Discord rate limit exceeded after {$retry_count} retries");
            }
        }

        // Handle permissions error (403)
        if ($status_code === 403) {
            throw new Exception("Bot lacks permission to send messages in this channel. Please invite the bot or check permissions: " . DISCORD_BOT_INVITE_LINK);
        }

        // Handle not found (404)
        if ($status_code === 404) {
            throw new Exception("Channel not found or bot is not in the server. Please invite the bot: " . DISCORD_BOT_INVITE_LINK);
        }

        // Handle bad request (400)
        if ($status_code === 400) {
            throw new Exception("Invalid message format. Error: {$error_message}");
        }

        // Generic error
        error_log("Discord API error ({$status_code}): {$error_message}");
        throw new Exception("Discord API error: {$error_message}");

    } catch (GuzzleException $e) {
        error_log("Discord connection error: " . $e->getMessage());

        // Retry on connection errors
        if ($retry_count < DISCORD_MAX_RETRIES) {
            error_log("Retrying Discord message send (attempt " . ($retry_count + 1) . ")");
            sleep(DISCORD_RETRY_DELAY);
            return send_discord_message($channel_id, $message, $retry_count + 1);
        }

        throw new Exception("Could not connect to Discord API: " . $e->getMessage());
    }
}

/**
 * Send message to multiple Discord channels
 *
 * @param array $channel_ids Array of Discord channel IDs
 * @param array $message Message data
 * @return array Array of results for each channel
 */
function send_discord_message_multi($channel_ids, $message) {
    $results = [];

    foreach ($channel_ids as $channel_id) {
        try {
            $result = send_discord_message($channel_id, $message);
            $results[$channel_id] = $result;

            // Rate limiting: wait between messages
            if (count($channel_ids) > 1) {
                sleep(DISCORD_RATE_LIMIT_DELAY);
            }

        } catch (Exception $e) {
            $results[$channel_id] = [
                'success' => false,
                'error' => $e->getMessage(),
                'channel_id' => $channel_id
            ];
        }
    }

    return $results;
}

/**
 * Send message via Discord Webhook (fallback method)
 *
 * @param string $webhook_url Discord webhook URL
 * @param array $message Message data
 * @return array Response with success status
 * @throws Exception if message cannot be sent
 */
function send_discord_webhook($webhook_url, $message) {
    // Validate webhook URL
    if (!preg_match('/^https:\/\/discord\.com\/api\/webhooks\/\d+\/[\w-]+$/', $webhook_url)) {
        throw new Exception('Invalid Discord webhook URL format');
    }

    $client_options = [
        'timeout' => 30,
        'verify' => false, // Disable SSL verification in development
        'http_errors' => true
    ];

    $client = new Client($client_options);

    try {
        $response = $client->post($webhook_url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $message
        ]);

        return [
            'success' => true,
            'status_code' => $response->getStatusCode()
        ];

    } catch (ClientException $e) {
        $status_code = $e->getResponse()->getStatusCode();
        $error_body = json_decode($e->getResponse()->getBody()->getContents(), true);
        $error_message = $error_body['message'] ?? 'Unknown error';

        if ($status_code === 429) {
            throw new Exception('Webhook rate limit exceeded. Please try again later.');
        }

        throw new Exception("Webhook error ({$status_code}): {$error_message}");

    } catch (GuzzleException $e) {
        throw new Exception("Could not connect to Discord webhook: " . $e->getMessage());
    }
}

/**
 * Create Discord embed message structure
 *
 * @param string $title Embed title
 * @param string $description Embed description
 * @param array $options Optional parameters (color, fields, footer, thumbnail, image, timestamp, url)
 * @return array Embed structure
 */
function create_discord_embed($title, $description, $options = []) {
    $embed = [
        'title' => $title,
        'description' => $description,
        'color' => $options['color'] ?? 3447003, // Default: blue (#3498db)
        'timestamp' => $options['timestamp'] ?? date('c')
    ];

    // Add fields (name/value pairs)
    if (isset($options['fields']) && is_array($options['fields'])) {
        $embed['fields'] = $options['fields'];
    }

    // Add footer
    if (isset($options['footer'])) {
        $embed['footer'] = ['text' => $options['footer']];
        if (isset($options['footer_icon'])) {
            $embed['footer']['icon_url'] = $options['footer_icon'];
        }
    } else {
        // Default footer
        $embed['footer'] = [
            'text' => 'Last War 1586 Bot'
        ];
    }

    // Add thumbnail
    if (isset($options['thumbnail'])) {
        $embed['thumbnail'] = ['url' => $options['thumbnail']];
    }

    // Add image
    if (isset($options['image'])) {
        $embed['image'] = ['url' => $options['image']];
    }

    // Add URL
    if (isset($options['url'])) {
        $embed['url'] = $options['url'];
    }

    // Add author
    if (isset($options['author'])) {
        $embed['author'] = ['name' => $options['author']];
        if (isset($options['author_icon'])) {
            $embed['author']['icon_url'] = $options['author_icon'];
        }
        if (isset($options['author_url'])) {
            $embed['author']['url'] = $options['author_url'];
        }
    }

    return $embed;
}

/**
 * Create simple text announcement
 *
 * @param string $content Message text
 * @param array $options Optional parameters (mentions)
 * @return array Message structure
 */
function create_simple_announcement($content, $options = []) {
    // Add footer attribution to message content for simple (non-embed) messages
    if (isset($options['footer'])) {
        $content .= "\n\n*— " . $options['footer'] . "*";
    }

    $message = ['content' => $content];

    // Add allowed mentions
    if (isset($options['mention_everyone']) && $options['mention_everyone']) {
        $message['allowed_mentions'] = ['parse' => ['everyone']];
    } elseif (isset($options['mention_roles']) && is_array($options['mention_roles'])) {
        $message['allowed_mentions'] = [
            'parse' => [],
            'roles' => $options['mention_roles']
        ];
    } else {
        // Default: no mentions
        $message['allowed_mentions'] = ['parse' => []];
    }

    return $message;
}

/**
 * Create announcement with embed
 *
 * @param string $title Announcement title
 * @param string $description Announcement description
 * @param array $options Embed options and message options
 * @return array Message structure with embed
 */
function create_embed_announcement($title, $description, $options = []) {
    $embed = create_discord_embed($title, $description, $options);

    $message = ['embeds' => [$embed]];

    // Add content (text before embed)
    if (isset($options['content'])) {
        $message['content'] = $options['content'];
    }

    // Add allowed mentions
    if (isset($options['mention_everyone']) && $options['mention_everyone']) {
        $message['allowed_mentions'] = ['parse' => ['everyone']];
    } else {
        $message['allowed_mentions'] = ['parse' => []];
    }

    return $message;
}

/**
 * Test Discord bot connection
 *
 * @param string $channel_id Channel ID to test with
 * @return array Test result
 */
function test_discord_connection($channel_id) {
    try {
        $test_message = create_simple_announcement('🤖 Discord bot connection test successful!');
        $result = send_discord_message($channel_id, $test_message);

        return [
            'success' => true,
            'message' => 'Connection successful!',
            'message_id' => $result['message_id'] ?? null
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get Discord channel information
 *
 * @param string $channel_id Channel ID
 * @return array Channel data
 * @throws Exception if channel not found
 */
function get_discord_channel_info($channel_id) {
    if (!defined('DISCORD_BOT_TOKEN') || empty(DISCORD_BOT_TOKEN)) {
        throw new Exception('Discord bot token not configured');
    }

    $client_options = [
        'timeout' => 30,
        'verify' => false, // Disable SSL verification in development
        'http_errors' => true
    ];

    $client = new Client($client_options);

    try {
        $response = $client->get(DISCORD_API_BASE . "/channels/{$channel_id}", [
            'headers' => [
                'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
                'Content-Type' => 'application/json'
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true);

    } catch (ClientException $e) {
        $status_code = $e->getResponse()->getStatusCode();

        if ($status_code === 404) {
            throw new Exception("Channel not found or bot lacks access");
        }

        throw new Exception("Could not retrieve channel info");
    }
}

/**
 * Validate Discord bot token
 *
 * @return array Validation result with bot user info
 */
function validate_discord_bot_token() {
    if (!defined('DISCORD_BOT_TOKEN') || empty(DISCORD_BOT_TOKEN)) {
        return [
            'valid' => false,
            'error' => 'Bot token not configured'
        ];
    }

    $client_options = [
        'timeout' => 30,
        'verify' => false, // Disable SSL verification in development
        'http_errors' => true
    ];

    $client = new Client($client_options);

    try {
        $response = $client->get(DISCORD_API_BASE . '/users/@me', [
            'headers' => [
                'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
                'Content-Type' => 'application/json'
            ]
        ]);
        $bot_user = json_decode($response->getBody()->getContents(), true);

        return [
            'valid' => true,
            'bot_username' => $bot_user['username'] ?? 'Unknown',
            'bot_id' => $bot_user['id'] ?? null,
            'bot_discriminator' => $bot_user['discriminator'] ?? '0000'
        ];

    } catch (Exception $e) {
        return [
            'valid' => false,
            'error' => 'Invalid bot token or connection error'
        ];
    }
}

/**
 * Format variable substitution in message content
 *
 * @param string $template Template string with {variables}
 * @param array $variables Key-value pairs for substitution
 * @return string Formatted message
 */
function format_discord_message($template, $variables = []) {
    $message = $template;

    foreach ($variables as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }

    return $message;
}

/**
 * Calculate time until event (for countdown messages)
 *
 * @param string $event_time Event timestamp (ISO 8601 or Unix timestamp)
 * @return string Human-readable countdown
 */
function discord_countdown($event_time) {
    $now = time();
    $event = is_numeric($event_time) ? $event_time : strtotime($event_time);

    $diff = $event - $now;

    if ($diff <= 0) {
        return 'NOW';
    }

    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);

    if ($hours > 24) {
        $days = floor($hours / 24);
        $hours = $hours % 24;
        return "{$days} day" . ($days > 1 ? 's' : '') . ", {$hours} hour" . ($hours > 1 ? 's' : '');
    } elseif ($hours > 0) {
        return "{$hours} hour" . ($hours > 1 ? 's' : '') . ", {$minutes} minute" . ($minutes > 1 ? 's' : '');
    } else {
        return "{$minutes} minute" . ($minutes > 1 ? 's' : '');
    }
}

?>
