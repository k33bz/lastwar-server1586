<?php
/**
 * Discord Bot Debug Test - Detailed Diagnostics
 */

require_once __DIR__ . '/config.php';

use GuzzleHttp\Client;

echo "\n";
echo "===========================================\n";
echo "Discord Bot DEBUG Test\n";
echo "===========================================\n\n";

// Show configuration
echo "Configuration:\n";
echo "- Bot Token: " . substr(DISCORD_BOT_TOKEN, 0, 30) . "..." . substr(DISCORD_BOT_TOKEN, -10) . "\n";
echo "- Client ID: " . DISCORD_CLIENT_ID . "\n";
echo "- Enabled: " . (DISCORD_ENABLED ? 'true' : 'false') . "\n\n";

// Test raw API call
echo "Testing raw Discord API call...\n";

try {
    $client = new Client([
        'base_uri' => 'https://discord.com/api/v10',
        'timeout' => 30,
        'http_errors' => false // Don't throw exceptions on error status
    ]);

    $response = $client->get('/users/@me', [
        'headers' => [
            'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
            'Content-Type' => 'application/json'
        ]
    ]);

    $status_code = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    echo "\nAPI Response:\n";
    echo "- Status Code: $status_code\n";
    echo "- Response Body:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    if ($status_code === 200) {
        echo "✅ SUCCESS! Bot is authenticated!\n";
        echo "- Bot Username: " . ($data['username'] ?? 'Unknown') . "\n";
        echo "- Bot ID: " . ($data['id'] ?? 'Unknown') . "\n";
        echo "- Bot Discriminator: " . ($data['discriminator'] ?? '0000') . "\n\n";
    } else {
        echo "❌ FAILED with status code $status_code\n";
        echo "Error details above.\n\n";

        if ($status_code === 401) {
            echo "Error 401 means: Unauthorized - Invalid bot token\n";
            echo "Solutions:\n";
            echo "1. Token was regenerated - get new token from Discord Portal\n";
            echo "2. Token has spaces - check admin/.env line 25\n";
            echo "3. Token format is wrong - should be: MTQzNTMz...rest_of_token\n\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Exception occurred:\n";
    echo $e->getMessage() . "\n\n";
    echo "This could be:\n";
    echo "- Network/connectivity issue\n";
    echo "- Firewall blocking Discord API\n";
    echo "- DNS resolution problem\n\n";
}

echo "===========================================\n";
?>
