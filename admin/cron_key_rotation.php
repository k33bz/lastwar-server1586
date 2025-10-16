<?php
/**
 * Automated JWT Secret Key Rotation Cron Job
 *
 * Automatically rotates JWT secret keys based on configured schedule
 * Recommended: Run daily to check if rotation is needed
 *
 * Usage:
 * 0 2 * * * /usr/bin/php /path/to/admin/cron_key_rotation.php
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/secret_key_rotation.php';

// Configuration
$ROTATION_INTERVAL_DAYS = (int)($_ENV['KEY_ROTATION_INTERVAL_DAYS'] ?? 30); // Default: 30 days
$AUTO_ROTATION_ENABLED = ($_ENV['AUTO_KEY_ROTATION_ENABLED'] ?? 'true') === 'true';

echo "JWT Secret Key Rotation Check - " . date('Y-m-d H:i:s') . "\n";
echo "================================================\n";

if (!$AUTO_ROTATION_ENABLED) {
    echo "Automatic key rotation is disabled in configuration\n";
    exit(0);
}

try {
    // Check if rotation is needed
    $status = get_key_rotation_status();
    $key_age_days = $status['current_key_age_days'];
    
    echo "Current key age: {$key_age_days} days\n";
    echo "Rotation interval: {$ROTATION_INTERVAL_DAYS} days\n";
    
    if ($key_age_days >= $ROTATION_INTERVAL_DAYS) {
        echo "Key rotation needed. Starting rotation...\n";
        
        $result = rotate_secret_key('system_cron', 'Automatic scheduled rotation');
        
        if ($result['success']) {
            echo "✓ Key rotation completed successfully\n";
            echo "  New Key ID: {$result['new_key_id']}\n";
            echo "  Rotation Time: " . date('Y-m-d H:i:s', $result['rotation_time']) . "\n";
            echo "  Grace Period Ends: " . date('Y-m-d H:i:s', $result['grace_period_ends']) . "\n";
            
            // Send notification to admins
            send_rotation_notification($result);
            
        } else {
            echo "✗ Key rotation failed: {$result['error']}\n";
            error_log("Automated key rotation failed: " . $result['error']);
            exit(1);
        }
    } else {
        $days_until_rotation = $ROTATION_INTERVAL_DAYS - $key_age_days;
        echo "No rotation needed. Next rotation in {$days_until_rotation} days\n";
    }
    
    // Validate environment sync
    if (!validate_env_key_sync()) {
        echo "WARNING: Environment key is out of sync with stored key!\n";
        error_log("JWT key environment sync validation failed");
    }
    
    echo "Key rotation check completed\n";
    
} catch (Exception $e) {
    echo "Error during key rotation check: " . $e->getMessage() . "\n";
    error_log("Key rotation cron job failed: " . $e->getMessage());
    exit(1);
}

/**
 * Send rotation notification to admins
 *
 * @param array $result Rotation result
 */
function send_rotation_notification($result) {
    try {
        require_once __DIR__ . '/mailer.php';
        require_once __DIR__ . '/json_helpers.php';
        
        // Get all admin users
        $users_data = read_json_file(USERS_FILE);
        $admin_emails = [];
        
        foreach ($users_data['users'] as $user) {
            if ($user['role'] === 'admin') {
                $admin_emails[] = $user['email'];
            }
        }
        
        $subject = 'JWT Secret Key Rotated - ' . ($_ENV['APP_NAME'] ?? 'Last War 1586 Admin');
        $message = "
The JWT secret key has been automatically rotated as scheduled.

Details:
- New Key ID: {$result['new_key_id']}
- Rotation Time: " . date('Y-m-d H:i:s T', $result['rotation_time']) . "
- Grace Period Ends: " . date('Y-m-d H:i:s T', $result['grace_period_ends']) . "

All user sessions have been invalidated for security.
Users will need to log in again.

This is an automated security measure.
No action is required unless you notice any issues.

<?php echo $_ENV['APP_NAME'] ?? 'Admin Panel'; ?>: " . APP_URL . "/admin/key_rotation_admin_panel.php
        ";
        
        foreach ($admin_emails as $email) {
            send_email($email, $subject, $message);
        }
        
        echo "Rotation notifications sent to " . count($admin_emails) . " admin(s)\n";
        
    } catch (Exception $e) {
        echo "Failed to send rotation notifications: " . $e->getMessage() . "\n";
        error_log("Failed to send key rotation notifications: " . $e->getMessage());
    }
}
?>