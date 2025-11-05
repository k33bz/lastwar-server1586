<?php
/**
 * Discord Bot Connection Test Script
 * Run this to verify bot token and connection
 *
 * Usage: php test_discord_bot.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/discord_webhook.php';

echo "\n";
echo "===========================================\n";
echo "Discord Bot Connection Test\n";
echo "===========================================\n\n";

// Test 1: Check if Discord is enabled
echo "1. Checking if Discord is enabled...\n";
if (DISCORD_ENABLED) {
    echo "   ✅ Discord is ENABLED\n\n";
} else {
    echo "   ❌ Discord is DISABLED\n";
    echo "   Set DISCORD_ENABLED=true in admin/.env\n";
    exit(1);
}

// Test 2: Check if bot token is configured
echo "2. Checking bot token configuration...\n";
if (empty(DISCORD_BOT_TOKEN)) {
    echo "   ❌ Bot token is NOT configured\n";
    echo "   Set DISCORD_BOT_TOKEN in admin/.env\n";
    exit(1);
}
echo "   ✅ Bot token is configured\n";
echo "   Token: " . substr(DISCORD_BOT_TOKEN, 0, 20) . "..." . substr(DISCORD_BOT_TOKEN, -10) . "\n\n";

// Test 3: Validate bot token
echo "3. Validating bot token with Discord API...\n";
$validation = validate_discord_bot_token();
if ($validation['valid']) {
    echo "   ✅ Bot token is VALID\n";
    echo "   Bot Username: " . $validation['bot_username'] . "\n";
    echo "   Bot ID: " . $validation['bot_id'] . "\n";
    echo "   Bot Discriminator: " . $validation['bot_discriminator'] . "\n\n";
} else {
    echo "   ❌ Bot token is INVALID\n";
    echo "   Error: " . $validation['error'] . "\n";
    echo "   Please check your token in admin/.env\n";
    exit(1);
}

// Test 4: Check for test channel ID
echo "4. Ready to test message sending...\n";
echo "   To send a test message, you need a Discord channel ID.\n\n";

// Ask for channel ID if running interactively
if (php_sapi_name() === 'cli') {
    echo "Enter a Discord channel ID to test (or press Enter to skip): ";
    $channel_id = trim(fgets(STDIN));

    if (!empty($channel_id)) {
        echo "\n5. Testing message sending to channel $channel_id...\n";

        try {
            $test_message = create_simple_announcement('🤖 Discord bot connection test successful! The bot is working correctly.');
            $result = send_discord_message($channel_id, $test_message);

            if ($result['success']) {
                echo "   ✅ Test message sent successfully!\n";
                echo "   Message ID: " . $result['message_id'] . "\n";
                echo "   Channel ID: " . $result['channel_id'] . "\n";
                echo "   Check Discord to see the message!\n\n";
            } else {
                echo "   ❌ Failed to send test message\n";
                echo "   Error: Check the error above\n\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error sending message: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "\n   ⏭️  Skipped message test\n\n";
    }
}

echo "===========================================\n";
echo "Summary:\n";
echo "===========================================\n";
echo "✅ Discord is enabled\n";
echo "✅ Bot token is configured and valid\n";
echo "✅ Bot: " . ($validation['bot_username'] ?? 'Unknown') . "\n";
echo "✅ Ready to send announcements!\n\n";

echo "Next steps:\n";
echo "1. Get Discord channel ID (Right-click channel → Copy ID)\n";
echo "2. Configure channels in Alliance Edit page\n";
echo "3. Go to admin/discord_announcements.php\n";
echo "4. Send your first announcement!\n\n";

echo "Or test via admin UI:\n";
echo "→ admin/discord_config.php (test connection)\n";
echo "→ admin/discord_announcements.php (send message)\n\n";
?>
