<?php
/**
 * Detailed Discord API test with full response logging
 */

require_once __DIR__ . '/config.php';

use GuzzleHttp\Client;

$channel_id = '702860749344604201';

echo "\n";
echo "===========================================\n";
echo "Detailed Discord API Test\n";
echo "===========================================\n\n";

echo "Channel ID: $channel_id\n";
echo "Bot Token: " . substr(DISCORD_BOT_TOKEN, 0, 20) . "...\n";
echo "Environment: " . APP_ENV . "\n\n";

// Create client
$client_options = [
    'base_uri' => 'https://discord.com/api/v10',
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
        'Content-Type' => 'application/json',
        'User-Agent' => 'Server1586Bot (https://www.lastwar1586.online, 1.0.0)'
    ],
    'http_errors' => false // Don't throw exceptions
];

if (APP_ENV === 'development') {
    $client_options['verify'] = false;
}

$client = new Client($client_options);

// Test 1: Verify bot token
echo "1. Verifying bot token...\n";
try {
    $response = $client->get('/users/@me');
    $status = $response->getStatusCode();
    $body = json_decode($response->getBody()->getContents(), true);

    echo "   Status: $status\n";
    if ($status === 200) {
        echo "   ✅ Bot: " . ($body['username'] ?? 'Unknown') . "\n";
        echo "   Bot ID: " . ($body['id'] ?? 'Unknown') . "\n\n";
    } else {
        echo "   ❌ Bot token invalid\n";
        print_r($body);
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Get channel info
echo "2. Getting channel information...\n";
try {
    $response = $client->get("/channels/{$channel_id}");
    $status = $response->getStatusCode();
    $body = json_decode($response->getBody()->getContents(), true);

    echo "   Status: $status\n";
    if ($status === 200) {
        echo "   ✅ Channel found!\n";
        echo "   Channel Name: #" . ($body['name'] ?? 'Unknown') . "\n";
        echo "   Channel Type: " . ($body['type'] ?? 'Unknown') . "\n";
        echo "   Guild ID: " . ($body['guild_id'] ?? 'Unknown') . "\n\n";
    } elseif ($status === 404) {
        echo "   ❌ Channel not found!\n";
        echo "   This means either:\n";
        echo "   - The channel ID is incorrect\n";
        echo "   - The bot is not in the server\n";
        echo "   - The channel was deleted\n\n";
        exit(1);
    } elseif ($status === 403) {
        echo "   ❌ Bot lacks permission to view this channel\n";
        echo "   Response:\n";
        print_r($body);
        echo "\n";
        exit(1);
    } else {
        echo "   ❌ Unexpected status: $status\n";
        echo "   Response:\n";
        print_r($body);
        echo "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Send test message
echo "3. Sending test message...\n";
try {
    $message = [
        'content' => '🤖 **Discord Bot Test**\n\nPhase 1 implementation complete!\nIf you see this, the bot is working correctly. ✅'
    ];

    echo "   Message content: " . substr($message['content'], 0, 50) . "...\n";

    $response = $client->post("/channels/{$channel_id}/messages", [
        'json' => $message
    ]);

    $status = $response->getStatusCode();
    $body_contents = $response->getBody()->getContents();

    echo "   Status: $status\n";
    echo "   Raw response length: " . strlen($body_contents) . " bytes\n";
    echo "   Raw response: " . substr($body_contents, 0, 200) . "...\n";

    $body = json_decode($body_contents, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "   ⚠️ JSON decode error: " . json_last_error_msg() . "\n";
    }

    echo "   Parsed body count: " . (is_array($body) ? count($body) : 'not array') . "\n";

    if ($status === 200 || $status === 201) {
        echo "   ✅ Message sent successfully!\n\n";
        echo "   Message ID: " . ($body['id'] ?? 'Unknown') . "\n";
        echo "   Channel ID: " . ($body['channel_id'] ?? 'Unknown') . "\n";
        echo "   Timestamp: " . ($body['timestamp'] ?? 'Unknown') . "\n";
        echo "   Author: " . ($body['author']['username'] ?? 'Unknown') . "\n\n";
        echo "   Full response:\n";
        echo json_encode($body, JSON_PRETTY_PRINT) . "\n\n";
        echo "🎉 Check Discord now!\n\n";
    } elseif ($status === 403) {
        echo "   ❌ Bot lacks permission to send messages\n";
        echo "   Error: " . ($body['message'] ?? 'Unknown') . "\n";
        echo "   Code: " . ($body['code'] ?? 'Unknown') . "\n\n";
        echo "   Solutions:\n";
        echo "   1. Check bot has 'Send Messages' permission in the channel\n";
        echo "   2. Check bot role is above @everyone\n";
        echo "   3. Check channel-specific permissions\n\n";
    } elseif ($status === 404) {
        echo "   ❌ Channel not found when sending\n";
        echo "   This shouldn't happen since we verified the channel exists!\n\n";
    } else {
        echo "   ❌ Unexpected status: $status\n";
        echo "   Response:\n";
        print_r($body);
        echo "\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "===========================================\n";
?>
