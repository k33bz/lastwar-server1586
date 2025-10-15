<?php
/**
 * JWT Secret Key Rotation System
 *
 * Provides secure rotation of JWT secret keys with graceful transition
 * and emergency invalidation of all tokens
 *
 * @version 1.0.1
 * @date 2025-10-15
 * @changelog
 *   1.0.1 (2025-10-15) - Fixed JWT class loading and constant conflicts
 *                       - Added autoloader initialization
 *                       - Resolved circular dependency issues
 *   1.0.0 (2025-10-15) - Initial implementation
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}

// Ensure autoloader is loaded first
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Secret key rotation configuration
define('SECRET_KEYS_FILE', __DIR__ . '/secret_keys.json');

// Use grace period from config.php if available, otherwise default to 300 seconds
if (!defined('KEY_ROTATION_GRACE_PERIOD')) {
    define('KEY_ROTATION_GRACE_PERIOD', 300); // 5 minutes grace period for old key
}

/**
 * Generate a new cryptographically secure secret key
 *
 * @param int $length Key length in bytes (default: 64)
 * @return string Base64 encoded secret key
 */
function generate_new_secret_key($length = 64) {
    return base64_encode(random_bytes($length));
}

/**
 * Initialize secret keys file if it doesn't exist
 */
function initialize_secret_keys_file() {
    if (!file_exists(SECRET_KEYS_FILE)) {
        $initial_keys = [
            'current' => [
                'key' => SECRET_KEY,
                'created_at' => time(),
                'key_id' => 'initial'
            ],
            'previous' => null,
            'rotation_history' => []
        ];
        
        write_json_file(SECRET_KEYS_FILE, $initial_keys);
        
        // Secure file permissions
        if (function_exists('chmod')) {
            @chmod(SECRET_KEYS_FILE, 0600);
        }
    }
}

/**
 * Get current and previous secret keys
 *
 * @return array Keys data
 */
function get_secret_keys() {
    initialize_secret_keys_file();
    return read_json_file(SECRET_KEYS_FILE);
}

/**
 * Rotate the JWT secret key
 *
 * @param string $admin_email Admin performing the rotation
 * @param string $reason Reason for rotation
 * @return array New key information
 */
function rotate_secret_key($admin_email, $reason = 'scheduled_rotation') {
    try {
        $keys_data = get_secret_keys();
        $new_key = generate_new_secret_key();
        $new_key_id = 'key_' . date('Y_m_d_H_i_s');
        $rotation_time = time();
        
        // Update keys structure
        $updated_keys = [
            'current' => [
                'key' => $new_key,
                'created_at' => $rotation_time,
                'key_id' => $new_key_id
            ],
            'previous' => $keys_data['current'], // Keep old key for grace period
            'rotation_history' => array_merge(
                [$keys_data['current']], // Add current to history
                array_slice($keys_data['rotation_history'] ?? [], 0, 9) // Keep last 10 keys
            )
        ];
        
        // Save updated keys
        write_json_file(SECRET_KEYS_FILE, $updated_keys);
        
        // Update .env file with new key
        update_env_secret_key($new_key);
        
        // Clear all active sessions (force re-authentication)
        clear_all_active_sessions();
        
        // Clear token blacklist (all old tokens are now invalid anyway)
        clear_token_blacklist();
        
        // Log rotation event
        log_audit_event('secret_key_rotation', $admin_email, [
            'reason' => $reason,
            'new_key_id' => $new_key_id,
            'old_key_id' => $keys_data['current']['key_id'] ?? 'unknown',
            'rotation_time' => date('Y-m-d H:i:s', $rotation_time)
        ]);
        
        return [
            'success' => true,
            'new_key_id' => $new_key_id,
            'rotation_time' => $rotation_time,
            'grace_period_ends' => $rotation_time + KEY_ROTATION_GRACE_PERIOD
        ];
        
    } catch (Exception $e) {
        error_log("Secret key rotation failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Update .env file with new secret key
 *
 * @param string $new_key New secret key
 * @return bool Success status
 */
function update_env_secret_key($new_key) {
    $env_file = __DIR__ . '/.env';
    
    if (!file_exists($env_file)) {
        throw new Exception('.env file not found');
    }
    
    $env_content = file_get_contents($env_file);
    
    // Replace SECRET_KEY line
    $pattern = '/^SECRET_KEY=.*$/m';
    $replacement = 'SECRET_KEY=' . $new_key;
    
    $updated_content = preg_replace($pattern, $replacement, $env_content);
    
    if ($updated_content === null) {
        throw new Exception('Failed to update SECRET_KEY in .env file');
    }
    
    // Create backup of old .env
    $backup_file = $env_file . '.backup.' . date('Y_m_d_H_i_s');
    copy($env_file, $backup_file);
    
    // Write updated .env
    if (file_put_contents($env_file, $updated_content) === false) {
        throw new Exception('Failed to write updated .env file');
    }
    
    return true;
}

/**
 * Clear all active sessions from all users
 *
 * @return int Number of sessions cleared
 */
function clear_all_active_sessions() {
    $cleared = 0;
    
    try {
        update_json_file(USERS_FILE, function(&$data) use (&$cleared) {
            foreach ($data['users'] as &$user) {
                if (isset($user['active_sessions'])) {
                    $cleared += count($user['active_sessions']);
                    $user['active_sessions'] = [];
                }
            }
            return true;
        });
    } catch (Exception $e) {
        error_log("Failed to clear active sessions: " . $e->getMessage());
    }
    
    return $cleared;
}

/**
 * Clear token blacklist (all tokens invalid after key rotation)
 *
 * @return bool Success status
 */
function clear_token_blacklist() {
    try {
        $empty_blacklist = ['jti' => [], 'expires' => []];
        write_json_file(BLACKLIST_FILE, $empty_blacklist);
        return true;
    } catch (Exception $e) {
        error_log("Failed to clear token blacklist: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced JWT decode that tries current and previous keys
 *
 * @param string $token JWT token
 * @return object Decoded token
 * @throws Exception if token cannot be decoded with any key
 */
function decode_jwt_with_rotation($token) {
    $keys_data = get_secret_keys();
    
    // Try current key first
    try {
        return JWT::decode($token, new Key($keys_data['current']['key'], 'HS256'));
    } catch (Exception $e) {
        // Current key failed, try previous key if within grace period
        if ($keys_data['previous'] !== null) {
            $grace_period_end = $keys_data['current']['created_at'] + KEY_ROTATION_GRACE_PERIOD;
            
            if (time() <= $grace_period_end) {
                try {
                    return JWT::decode($token, new Key($keys_data['previous']['key'], 'HS256'));
                } catch (Exception $e2) {
                    // Both keys failed
                    throw new Exception('Token invalid with both current and previous keys');
                }
            }
        }
        
        // No previous key or grace period expired
        throw $e;
    }
}

/**
 * Emergency key rotation (immediate invalidation)
 *
 * @param string $admin_email Admin performing emergency rotation
 * @param string $reason Security incident reason
 * @return array Rotation result
 */
function emergency_key_rotation($admin_email, $reason) {
    // Perform rotation without grace period
    $result = rotate_secret_key($admin_email, "EMERGENCY: $reason");
    
    if ($result['success']) {
        // Send emergency notification email to all admins
        send_emergency_rotation_notification($reason);
        
        // Log as high-priority security event
        log_audit_event('emergency_key_rotation', $admin_email, [
            'reason' => $reason,
            'all_sessions_invalidated' => true,
            'immediate_effect' => true
        ]);
    }
    
    return $result;
}

/**
 * Send emergency rotation notification to all admins
 *
 * @param string $reason Rotation reason
 */
function send_emergency_rotation_notification($reason) {
    try {
        require_once __DIR__ . '/mailer.php';
        
        // Get all admin users
        $users_data = read_json_file(USERS_FILE);
        $admin_emails = [];
        
        foreach ($users_data['users'] as $user) {
            if ($user['role'] === 'admin') {
                $admin_emails[] = $user['email'];
            }
        }
        
        $subject = 'SECURITY ALERT: Emergency JWT Key Rotation - Last War 1586';
        $message = "
        SECURITY ALERT: Emergency JWT key rotation has been performed.
        
        Reason: $reason
        Time: " . date('Y-m-d H:i:s T') . "
        
        All user sessions have been invalidated.
        All users must log in again.
        
        If this was not expected, please investigate immediately.
        ";
        
        foreach ($admin_emails as $email) {
            send_email($email, $subject, $message);
        }
        
    } catch (Exception $e) {
        error_log("Failed to send emergency rotation notifications: " . $e->getMessage());
    }
}

/**
 * Schedule automatic key rotation
 *
 * @param int $interval_days Rotation interval in days
 * @return bool Success status
 */
function schedule_key_rotation($interval_days = 30) {
    $keys_data = get_secret_keys();
    $current_key_age = time() - $keys_data['current']['created_at'];
    $rotation_interval = $interval_days * 24 * 3600;
    
    if ($current_key_age >= $rotation_interval) {
        return rotate_secret_key('system', 'scheduled_rotation');
    }
    
    return ['success' => false, 'reason' => 'Not due for rotation yet'];
}

/**
 * Get key rotation status and metrics
 *
 * @return array Status information
 */
function get_key_rotation_status() {
    $keys_data = get_secret_keys();
    $current_age = time() - $keys_data['current']['created_at'];
    
    return [
        'current_key_id' => $keys_data['current']['key_id'],
        'current_key_age_days' => round($current_age / 86400, 1),
        'previous_key_exists' => $keys_data['previous'] !== null,
        'grace_period_active' => $keys_data['previous'] !== null && 
                                (time() <= $keys_data['current']['created_at'] + KEY_ROTATION_GRACE_PERIOD),
        'rotation_history_count' => count($keys_data['rotation_history'] ?? []),
        'last_rotation' => date('Y-m-d H:i:s', $keys_data['current']['created_at'])
    ];
}

/**
 * Validate that current environment key matches stored key
 *
 * @return bool True if keys match
 */
function validate_env_key_sync() {
    $keys_data = get_secret_keys();
    return SECRET_KEY === $keys_data['current']['key'];
}
?>