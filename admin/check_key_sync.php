<?php
/**
 * Key Synchronization Checker
 *
 * Checks if SECRET_KEY from .env matches current key in secret_keys.json
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";
echo "Checking Key Synchronization...\n\n";

// Load .env manually
$env_file = __DIR__ . '/.env';
if (!file_exists($env_file)) {
    echo "ERROR: .env file not found!\n";
    exit;
}

$env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$secret_key_from_env = null;

foreach ($env_lines as $line) {
    if (strpos($line, 'SECRET_KEY=') === 0) {
        $secret_key_from_env = substr($line, strlen('SECRET_KEY='));
        break;
    }
}

echo "1. SECRET_KEY from .env:\n";
echo "   " . ($secret_key_from_env ? substr($secret_key_from_env, 0, 20) . "..." : "NOT FOUND") . "\n\n";

// Load secret_keys.json
$keys_file = __DIR__ . '/secret_keys.json';
if (!file_exists($keys_file)) {
    echo "2. secret_keys.json: NOT FOUND (needs initialization)\n";
    exit;
}

$keys_data = json_decode(file_get_contents($keys_file), true);
echo "2. Current key from secret_keys.json:\n";
echo "   Key ID: " . ($keys_data['current']['key_id'] ?? 'unknown') . "\n";
echo "   Key: " . substr($keys_data['current']['key'] ?? '', 0, 20) . "...\n";
echo "   Created: " . date('Y-m-d H:i:s', $keys_data['current']['created_at'] ?? 0) . "\n\n";

if ($keys_data['previous'] !== null) {
    echo "3. Previous key (grace period):\n";
    echo "   Key ID: " . ($keys_data['previous']['key_id'] ?? 'unknown') . "\n";
    echo "   Key: " . substr($keys_data['previous']['key'] ?? '', 0, 20) . "...\n";
    echo "   Created: " . date('Y-m-d H:i:s', $keys_data['previous']['created_at'] ?? 0) . "\n";

    $grace_period = 300; // 5 minutes
    $grace_ends = $keys_data['current']['created_at'] + $grace_period;
    $time_left = $grace_ends - time();
    echo "   Grace period ends: " . date('Y-m-d H:i:s', $grace_ends) . " (" . ($time_left > 0 ? $time_left . " seconds left" : "EXPIRED") . ")\n\n";
} else {
    echo "3. Previous key: NONE\n\n";
}

// Check if they match
if ($secret_key_from_env === $keys_data['current']['key']) {
    echo "✅ STATUS: Keys are IN SYNC\n";
} else {
    echo "❌ STATUS: Keys are OUT OF SYNC!\n";
    echo "   This means .env and secret_keys.json have different current keys.\n";
    echo "   Magic links will fail until they are synchronized.\n";
}

echo "</pre>\n";
?>
