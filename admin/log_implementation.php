<?php
/**
 * Log JWT Key Rotation System Implementation
 *
 * One-time script to log the implementation of the key rotation system
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Get current admin user
$user_token = require_jwt_session();

if ($user_token->aud !== 'admin') {
    die('Admin access required');
}

// Log the implementation
$logged = log_key_rotation_implementation($user_token->sub);

if ($logged) {
    echo "✅ JWT Key Rotation System implementation logged successfully\n";
    echo "📊 Check audit logs for complete implementation details\n";
} else {
    echo "❌ Failed to log implementation\n";
}

// Initialize key rotation system
if (file_exists(__DIR__ . '/secret_key_rotation.php')) {
    require_once __DIR__ . '/secret_key_rotation.php';
    
    try {
        // Initialize the secret keys file with current key
        initialize_secret_keys_file();
        echo "🔐 Secret keys file initialized\n";
        
        // Get initial status
        $status = get_key_rotation_status();
        echo "📈 Current key age: {$status['current_key_age_days']} days\n";
        echo "🔄 Auto rotation: " . (AUTO_KEY_ROTATION_ENABLED ? 'Enabled' : 'Disabled') . "\n";
        
    } catch (Exception $e) {
        echo "⚠️ Warning: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 JWT Key Rotation System is now active!\n";
echo "🔗 Access " . ($_ENV['APP_NAME'] ?? 'admin panel') . ": key_rotation_admin_panel.php\n";
echo "📋 Setup cron jobs as documented in SECRET_KEY_ROTATION_SETUP.md\n";
?>