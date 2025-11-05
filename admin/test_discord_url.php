<?php
/**
 * Test exact Discord API URL
 */

require_once __DIR__ . '/config.php';

use GuzzleHttp\Client;

$server_id = '361489598603591690';
$channel_id = '702860749344604201';

echo "\n";
echo "Server ID: $server_id\n";
echo "Channel ID: $channel_id\n";
echo "Bot Token: " . substr(DISCORD_BOT_TOKEN, 0, 20) . "...\n\n";

// Correct Discord API endpoint
$api_base = 'https://discord.com/api/v10';
$endpoint = "/channels/{$channel_id}/messages";
$full_url = $api_base . $endpoint;

echo "Full URL: $full_url\n\n";

$client_options = [
    'base_uri' => $api_base,
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bot ' . DISCORD_BOT_TOKEN,
        'Content-Type' => 'application/json',
    ],
    'http_errors' => false,
    'verify' => false // Dev only
];

$client = new Client($client_options);

echo "Sending POST request...\n";

$message = [
    'content' => '🧪 Test message from Server 1586 Bot - ' . date('H:i:s')
];

try {
    $response = $client->post($endpoint, [
        'json' => $message,
        'debug' => false
    ]);

    $status = $response->getStatusCode();
    $body_raw = $response->getBody()->getContents();

    echo "Status Code: $status\n";
    echo "Response length: " . strlen($body_raw) . " bytes\n";
    echo "First 500 chars of response:\n";
    echo substr($body_raw, 0, 500) . "\n\n";

    if (strpos($body_raw, '<!DOCTYPE html>') === 0) {
        echo "❌ ERROR: Got HTML instead of JSON!\n";
        echo "This means the API endpoint is wrong or there's a redirect.\n\n";

        echo "Expected endpoint: POST $full_url\n";
        echo "With header: Authorization: Bot [token]\n\n";
    } else {
        $body = json_decode($body_raw, true);
        if ($body) {
            echo "✅ Got JSON response!\n";
            print_r($body);
        }
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
