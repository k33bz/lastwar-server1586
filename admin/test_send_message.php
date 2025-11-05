<?php
/**
 * Quick test - Send message to specific channel
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/discord_webhook.php';

$channel_id = '702860749344604201';

echo "\n";
echo "===========================================\n";
echo "Sending Test Message to Discord\n";
echo "===========================================\n\n";

echo "Channel ID: $channel_id\n";
echo "Bot Token: " . substr(DISCORD_BOT_TOKEN, 0, 20) . "...\n\n";

try {
    echo "Creating test message...\n";
    $message = create_simple_announcement('🤖 Discord Bot Test - Phase 1 Implementation Complete! ✅');

    echo "Sending message to Discord...\n";
    $result = send_discord_message($channel_id, $message);

    echo "\n";
    if ($result['success']) {
        echo "✅ SUCCESS! Message sent to Discord!\n\n";
        echo "Details:\n";
        echo "- Message ID: " . $result['message_id'] . "\n";
        echo "- Channel ID: " . $result['channel_id'] . "\n";
        echo "- Timestamp: " . ($result['timestamp'] ?? 'N/A') . "\n\n";
        echo "🎉 Check your Discord channel to see the message!\n\n";
    } else {
        echo "❌ Failed to send message\n";
        print_r($result);
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";

    if (strpos($e->getMessage(), 'Bot lacks permission') !== false) {
        echo "Solution: Check bot has 'Send Messages' permission in the channel\n";
    } elseif (strpos($e->getMessage(), 'Channel not found') !== false) {
        echo "Solution: Verify channel ID is correct and bot is in the server\n";
    } elseif (strpos($e->getMessage(), 'bot token not configured') !== false) {
        echo "Solution: Set DISCORD_BOT_TOKEN in admin/.env\n";
    }
    echo "\n";
}

echo "===========================================\n";
?>
