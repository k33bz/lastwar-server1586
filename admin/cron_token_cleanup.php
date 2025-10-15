<?php
/**
 * Cron Job for JWT Token Cleanup
 *
 * Run this script periodically to clean up expired tokens
 * Recommended: Every hour via cron job
 *
 * Usage:
 * 0 * * * * /usr/bin/php /path/to/admin/cron_token_cleanup.php
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
require_once __DIR__ . '/token_rotation.php';
require_once __DIR__ . '/audit_logger.php';

echo "JWT Token Cleanup - " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n";

try {
    // Clean up expired tokens from blacklist
    $cleaned_tokens = cleanup_expired_tokens();
    echo "Cleaned up $cleaned_tokens expired tokens from blacklist\n";
    
    // Clean up expired active sessions
    $cleaned_sessions = cleanup_expired_sessions();
    echo "Cleaned up $cleaned_sessions expired active sessions\n";
    
    // Log cleanup activity
    log_audit_event('token_cleanup', 'system', [
        'cleaned_tokens' => $cleaned_tokens,
        'cleaned_sessions' => $cleaned_sessions
    ]);
    
    echo "Cleanup completed successfully\n";
    
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    error_log("Token cleanup failed: " . $e->getMessage());
    exit(1);
}

/**
 * Clean up expired active sessions from users.json
 *
 * @return int Number of sessions cleaned up
 */
function cleanup_expired_sessions() {
    $cleaned = 0;
    
    try {
        update_json_file(USERS_FILE, function(&$data) use (&$cleaned) {
            $now = time();
            
            foreach ($data['users'] as &$user) {
                if (isset($user['active_sessions'])) {
                    $original_count = count($user['active_sessions']);
                    
                    // Filter out expired sessions
                    $user['active_sessions'] = array_values(array_filter(
                        $user['active_sessions'],
                        function($session) use ($now) {
                            return isset($session['exp']) && $session['exp'] > $now;
                        }
                    ));
                    
                    $cleaned += $original_count - count($user['active_sessions']);
                }
            }
            
            return true;
        });
    } catch (Exception $e) {
        error_log("Failed to cleanup expired sessions: " . $e->getMessage());
    }
    
    return $cleaned;
}
?>