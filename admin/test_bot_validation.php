<?php
/**
 * Test bot token validation function
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/discord_webhook.php';

echo "\n";
echo "===========================================\n";
echo "Testing Bot Token Validation\n";
echo "===========================================\n\n";

$result = validate_discord_bot_token();

if ($result['valid']) {
    echo "✅ Bot token is valid!\n\n";
    echo "Bot Details:\n";
    echo "- Username: " . $result['bot_username'] . "\n";
    echo "- ID: " . $result['bot_id'] . "\n";
    echo "- Discriminator: " . $result['bot_discriminator'] . "\n\n";
} else {
    echo "❌ Bot token is invalid\n";
    echo "Error: " . $result['error'] . "\n\n";
}

echo "===========================================\n";
?>
