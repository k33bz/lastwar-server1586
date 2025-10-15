<?php
/**
 * Initialize JWT Key Rotation System
 *
 * Run this once to set up the key rotation system
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';

echo "🔐 Initializing JWT Key Rotation System...\n";

// Check if JWT classes are available
if (!class_exists('Firebase\JWT\JWT')) {
    echo "❌ JWT classes not found. Please run: composer install\n";
    exit(1);
}

// Load key rotation system
require_once __DIR__ . '/secret_key_rotation.php';

try {
    // Initialize the secret keys file
    initialize_secret_keys_file();
    echo "✅ Secret keys file initialized\n";
    
    // Get status
    $status = get_key_rotation_status();
    echo "📊 Current key age: {$status['current_key_age_days']} days\n";
    echo "🔄 Auto rotation: " . (AUTO_KEY_ROTATION_ENABLED ? 'Enabled' : 'Disabled') . "\n";
    echo "⏱️ Grace period: " . KEY_ROTATION_GRACE_PERIOD . " seconds\n";
    
    // Validate environment sync
    if (validate_env_key_sync()) {
        echo "✅ Environment key is synchronized\n";
    } else {
        echo "⚠️ Environment key sync issue detected\n";
    }
    
    echo "\n🎉 JWT Key Rotation System is ready!\n";
    echo "🔗 Admin panel: key_rotation_admin_panel.php\n";
    echo "📋 Don't forget to set up cron jobs:\n";
    echo "   0 2 * * * php " . __DIR__ . "/cron_key_rotation.php\n";
    echo "   0 * * * * php " . __DIR__ . "/cron_token_cleanup.php\n";
    
} catch (Exception $e) {
    echo "❌ Initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>