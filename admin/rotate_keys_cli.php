#!/usr/bin/env php
<?php
/**
 * CLI Key Rotation Script
 *
 * Rotates JWT keys on production server after deployment
 * Should only be run on production environment
 *
 * @version 1.0.0
 * @date 2025-10-17
 */

// Ensure this is run from CLI only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

define('ADMIN_INIT', true);

// Change to script directory
chdir(__DIR__);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

// Check environment - only run on production
if (APP_ENV !== 'production') {
    echo "❌ Key rotation is only for production environment\n";
    echo "   Current environment: " . APP_ENV . "\n";
    exit(1);
}

// Check if key rotation is enabled
if (!file_exists(__DIR__ . '/secret_key_rotation.php')) {
    echo "ℹ️  Key rotation is not enabled (secret_key_rotation.php not found)\n";
    echo "   Using single static key from .env\n";
    exit(0);
}

require_once __DIR__ . '/secret_key_rotation.php';

echo "🔄 Starting JWT Key Rotation on Production...\n\n";

// Get current rotation status
$keys_file = __DIR__ . '/secret_keys.json';
if (!file_exists($keys_file)) {
    echo "❌ secret_keys.json not found\n";
    exit(1);
}

$keys_data = json_decode(file_get_contents($keys_file), true);
$current_key_id = $keys_data['current_key_id'] ?? null;

if ($current_key_id) {
    $current_key = null;
    foreach ($keys_data['keys'] as $key) {
        if ($key['id'] === $current_key_id) {
            $current_key = $key;
            break;
        }
    }

    if ($current_key) {
        $created = strtotime($current_key['created_at']);
        $age_hours = (time() - $created) / 3600;

        echo "Current Key Status:\n";
        echo "  Key ID: {$current_key_id}\n";
        echo "  Created: {$current_key['created_at']}\n";
        echo "  Age: " . round($age_hours, 1) . " hours\n\n";
    }
}

// Rotate the key
echo "🔑 Generating new key...\n";
$new_key = rotate_secret_key();

if (!$new_key) {
    echo "❌ Failed to rotate key\n";
    exit(1);
}

echo "✅ New key generated: {$new_key['id']}\n\n";

// Update .env file with new key
echo "📝 Updating .env file...\n";

$env_file = __DIR__ . '/.env';
if (!file_exists($env_file)) {
    echo "❌ .env file not found\n";
    exit(1);
}

// Backup current .env
$backup_file = $env_file . '.backup.' . date('Y_m_d_H_i_s');
if (!copy($env_file, $backup_file)) {
    echo "❌ Failed to create backup\n";
    exit(1);
}

echo "✅ Created backup: {$backup_file}\n";

// Read current .env
$env_contents = file_get_contents($env_file);

// Update SECRET_KEY line
$env_contents = preg_replace(
    '/^SECRET_KEY=.*$/m',
    'SECRET_KEY=' . $new_key['key'],
    $env_contents
);

// Write updated .env
if (file_put_contents($env_file, $env_contents) === false) {
    echo "❌ Failed to update .env file\n";
    echo "   Restoring backup...\n";
    copy($backup_file, $env_file);
    exit(1);
}

echo "✅ Updated .env with new key\n\n";

// Display grace period info
echo "Grace Period Information:\n";
echo "  Duration: " . (GRACE_PERIOD_MINUTES ?? 5) . " minutes\n";
echo "  Old tokens will work until: " . date('Y-m-d H:i:s', time() + (GRACE_PERIOD_MINUTES ?? 5) * 60) . "\n";
echo "  New tokens use new key immediately\n\n";

echo "✅ Key rotation completed successfully!\n";
echo "\nNext steps:\n";
echo "  - Active sessions will continue working during grace period\n";
echo "  - Users will need to log in again after grace period expires\n";
echo "  - New magic links will use the new key\n";

exit(0);
