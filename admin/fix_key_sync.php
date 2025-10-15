<?php
/**
 * Fix Key Synchronization
 *
 * Syncs SECRET_KEY in .env with current key from secret_keys.json
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";
echo "Fixing Key Synchronization...\n\n";

// Load secret_keys.json
$keys_file = __DIR__ . '/secret_keys.json';
if (!file_exists($keys_file)) {
    echo "❌ ERROR: secret_keys.json not found!\n";
    exit;
}

$keys_data = json_decode(file_get_contents($keys_file), true);
$current_key = $keys_data['current']['key'] ?? null;
$current_key_id = $keys_data['current']['key_id'] ?? 'unknown';

if (!$current_key) {
    echo "❌ ERROR: No current key found in secret_keys.json\n";
    exit;
}

echo "Current key from secret_keys.json:\n";
echo "  Key ID: $current_key_id\n";
echo "  Key: " . substr($current_key, 0, 20) . "...\n\n";

// Load .env file
$env_file = __DIR__ . '/.env';
if (!file_exists($env_file)) {
    echo "❌ ERROR: .env file not found!\n";
    exit;
}

$env_content = file_get_contents($env_file);

// Create backup
$backup_file = $env_file . '.backup.' . date('Y_m_d_H_i_s');
if (copy($env_file, $backup_file)) {
    echo "✅ Created backup: " . basename($backup_file) . "\n";
} else {
    echo "❌ ERROR: Failed to create backup!\n";
    exit;
}

// Replace SECRET_KEY line
$pattern = '/^SECRET_KEY=.*$/m';
$replacement = 'SECRET_KEY=' . $current_key;

$updated_content = preg_replace($pattern, $replacement, $env_content);

if ($updated_content === null) {
    echo "❌ ERROR: Failed to update SECRET_KEY in .env\n";
    exit;
}

// Check if SECRET_KEY line was found and replaced
if ($updated_content === $env_content) {
    echo "⚠️  WARNING: No SECRET_KEY line found in .env\n";
    echo "   Adding SECRET_KEY to end of file...\n";
    $updated_content = rtrim($env_content) . "\n\nSECRET_KEY=" . $current_key . "\n";
}

// Write updated .env
if (file_put_contents($env_file, $updated_content) === false) {
    echo "❌ ERROR: Failed to write updated .env file\n";
    echo "   Restoring from backup...\n";
    copy($backup_file, $env_file);
    exit;
}

echo "✅ Updated .env file with current key from secret_keys.json\n\n";

// Verify sync
$env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$secret_key_from_env = null;

foreach ($env_lines as $line) {
    if (strpos($line, 'SECRET_KEY=') === 0) {
        $secret_key_from_env = substr($line, strlen('SECRET_KEY='));
        break;
    }
}

if ($secret_key_from_env === $current_key) {
    echo "✅ VERIFICATION: Keys are now IN SYNC!\n";
    echo "\nYou can now request a new magic link and it should work.\n";
    echo "Old magic links from before this fix will NOT work.\n";
} else {
    echo "❌ VERIFICATION FAILED: Keys still don't match!\n";
    echo "   .env key: " . substr($secret_key_from_env, 0, 20) . "...\n";
    echo "   Expected: " . substr($current_key, 0, 20) . "...\n";
}

echo "</pre>\n";
?>
