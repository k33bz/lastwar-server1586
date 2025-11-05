<?php
/**
 * Test sending Discord embed message
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/discord_webhook.php';

$channel_id = '702860749344604201';

echo "\n";
echo "===========================================\n";
echo "Testing Discord Embed Message\n";
echo "===========================================\n\n";

try {
    // Create an embed announcement
    $message = create_embed_announcement(
        '🎉 Discord Bot Phase 1 Complete!',
        'The Discord announcement system is now fully operational. R5 and R4 leaders can send instant messages to their Discord channels.',
        [
            'color' => 3447003, // Blue
            'fields' => [
                [
                    'name' => '✅ Implemented Features',
                    'value' => "• Instant message sending\n• Rich embed support\n• Multi-channel posting\n• Role-based access control",
                    'inline' => false
                ],
                [
                    'name' => '📋 Coming Soon',
                    'value' => "• Scheduled announcements\n• Recurring messages\n• Message templates",
                    'inline' => false
                ]
            ],
            'footer' => 'Last War 1586 Bot • Phase 1',
            'timestamp' => date('c')
        ]
    );

    echo "Sending embed message to channel $channel_id...\n\n";

    $result = send_discord_message($channel_id, $message);

    if ($result['success']) {
        echo "✅ SUCCESS! Embed message sent!\n\n";
        echo "Details:\n";
        echo "- Message ID: " . $result['message_id'] . "\n";
        echo "- Channel ID: " . $result['channel_id'] . "\n";
        echo "- Timestamp: " . ($result['timestamp'] ?? 'N/A') . "\n\n";
        echo "🎉 Check Discord to see the embed!\n\n";
    } else {
        echo "❌ Failed to send message\n";
        print_r($result);
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

echo "===========================================\n";
?>
