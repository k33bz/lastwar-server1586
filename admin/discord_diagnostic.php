<?php
/**
 * Discord Integration Diagnostic Tool
 * Checks if Discord is properly configured and accessible
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once 'config.php';
require_once 'jwt.php';

$user = require_jwt_session();

header('Content-Type: text/plain');

echo "=== Discord Integration Diagnostic ===\n\n";

echo "1. Config Constants:\n";
echo "   DISCORD_ENABLED: " . (defined('DISCORD_ENABLED') ? (DISCORD_ENABLED ? 'TRUE ✓' : 'FALSE ✗') : 'NOT DEFINED ✗') . "\n";
echo "   DISCORD_BOT_TOKEN: " . (defined('DISCORD_BOT_TOKEN') ? (empty(DISCORD_BOT_TOKEN) ? 'EMPTY ✗' : 'SET ✓') : 'NOT DEFINED ✗') . "\n";
echo "   DISCORD_CLIENT_ID: " . (defined('DISCORD_CLIENT_ID') ? (empty(DISCORD_CLIENT_ID) ? 'EMPTY ✗' : 'SET ✓') : 'NOT DEFINED ✗') . "\n";

echo "\n2. User Information:\n";
echo "   Email: " . $user->sub . "\n";
echo "   Audience: " . $user->aud . "\n";

$user_data = get_user_by_email($user->sub);
if ($user_data) {
    $roles = $user_data['roles'] ?? [];
    echo "   Roles: " . (empty($roles) ? 'NONE ✗' : implode(', ', $roles) . ' ✓') . "\n";

    // Check if user has required role
    $has_access = has_role($user, ['admin', 'r5', 'r4', 'president']);
    echo "   Has Discord Access: " . ($has_access ? 'YES ✓' : 'NO ✗') . "\n";
} else {
    echo "   User data: NOT FOUND ✗\n";
}

echo "\n3. File System:\n";
echo "   discord_webhook.php: " . (file_exists(__DIR__ . '/discord_webhook.php') ? 'EXISTS ✓' : 'MISSING ✗') . "\n";
echo "   discord_api.php: " . (file_exists(__DIR__ . '/discord_api.php') ? 'EXISTS ✓' : 'MISSING ✗') . "\n";
echo "   discord_announcements.php: " . (file_exists(__DIR__ . '/discord_announcements.php') ? 'EXISTS ✓' : 'MISSING ✗') . "\n";
echo "   discord_config.php: " . (file_exists(__DIR__ . '/discord_config.php') ? 'EXISTS ✓' : 'MISSING ✗') . "\n";

echo "\n4. Version:\n";
$version_file = dirname(__DIR__) . '/data/version.json';
if (file_exists($version_file)) {
    $version_data = json_decode(file_get_contents($version_file), true);
    echo "   Code Version: " . ($version_data['version'] ?? 'unknown') . "\n";
}

$installed_file = __DIR__ . '/.installed_version';
if (file_exists($installed_file)) {
    echo "   Installed Version: " . trim(file_get_contents($installed_file)) . "\n";
}

echo "\n5. Navigation Condition:\n";
$condition_met = defined('DISCORD_ENABLED') && DISCORD_ENABLED && has_role($user, ['admin', 'r5', 'r4', 'president']);
echo "   Will Discord appear in nav? " . ($condition_met ? 'YES ✓' : 'NO ✗') . "\n";

if (!$condition_met) {
    echo "\n   Why not?\n";
    if (!defined('DISCORD_ENABLED')) {
        echo "   - DISCORD_ENABLED constant not defined\n";
    } elseif (!DISCORD_ENABLED) {
        echo "   - DISCORD_ENABLED is false (check .env file)\n";
    }
    if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
        echo "   - User doesn't have required role (need: admin, r5, r4, or president)\n";
    }
}

echo "\n=== End Diagnostic ===\n";
?>
