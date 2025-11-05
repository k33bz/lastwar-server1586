<?php
/**
 * Simple Guzzle test - no base_uri
 */

require_once __DIR__ . '/config.php';

use GuzzleHttp\Client;

$channel_id = '702860749344604201';

echo "\n";
echo "Testing Guzzle without base_uri...\n\n";

// Create client WITHOUT base_uri
$client = new Client([
    'timeout' => 30,
    'verify' => false,
    'http_errors' => false
]);

$full_url = "https://discord.com/api/v10/channels/{$channel_id}/messages";

echo "Full URL: $full_url\n";
echo "Token: " . substr(DISCORD_BOT_TOKEN, 0, 20) . "...\n\n";

try {
    $response = $client->post($full_url, [
        'headers' => [
            'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
            'Content-Type' => 'application/json'
        ],
        'json' => [
            'content' => '🧪 Guzzle test without base_uri - ' . date('H:i:s')
        ]
    ]);

    $status = $response->getStatusCode();
    $body_raw = $response->getBody()->getContents();

    echo "Status Code: $status\n";
    echo "Response length: " . strlen($body_raw) . " bytes\n";
    echo "First 200 chars:\n";
    echo substr($body_raw, 0, 200) . "\n\n";

    if (strpos($body_raw, '<!DOCTYPE html>') === 0) {
        echo "❌ Got HTML response\n";
    } else {
        $body = json_decode($body_raw, true);
        if ($body && isset($body['id'])) {
            echo "✅ SUCCESS! Got JSON response!\n";
            echo "Message ID: " . $body['id'] . "\n";
            echo "Channel ID: " . $body['channel_id'] . "\n";
            echo "Content: " . $body['content'] . "\n";
        } else {
            echo "⚠️ JSON decode failed\n";
        }
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
