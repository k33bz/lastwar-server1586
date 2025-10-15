<?php
/**
 * JWT Token Rotation System
 *
 * Provides automatic token rotation for enhanced security
 * Implements sliding window rotation and refresh token patterns
 *
 * @version 1.0.1
 * @date 2025-10-15
 * @changelog
 *   1.0.1 (2025-10-15) - Fixed constant conflicts and JWT imports
 *                       - Added autoloader initialization
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
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Token rotation configuration
if (!defined('TOKEN_ROTATION_THRESHOLD')) {
    define('TOKEN_ROTATION_THRESHOLD', 0.5); // Rotate when 50% of lifetime remaining
}

// Use refresh token expiry from config.php if available
if (!defined('REFRESH_TOKEN_EXPIRY')) {
    define('REFRESH_TOKEN_EXPIRY', 7 * 24 * 3600); // 7 days for refresh tokens
}

/**
 * Check if token needs rotation based on remaining lifetime
 *
 * @param object $token Decoded JWT token
 * @return bool True if token should be rotated
 */
function should_rotate_token($token) {
    $now = time();
    $issued_at = $token->iat ?? $now;
    $expires_at = $token->exp ?? $now;
    
    $total_lifetime = $expires_at - $issued_at;
    $remaining_lifetime = $expires_at - $now;
    
    // Rotate if less than threshold percentage of lifetime remains
    $threshold_time = $total_lifetime * TOKEN_ROTATION_THRESHOLD;
    
    return $remaining_lifetime < $threshold_time;
}

/**
 * Rotate JWT token with new expiry and JTI
 *
 * @param object $old_token Current token
 * @return array New token data [token, jti, expires]
 */
function rotate_jwt_token($old_token) {
    // Create new token with same claims but new JTI and expiry
    $new_jti = bin2hex(random_bytes(16));
    $new_expiry = time() + SESSION_TOKEN_EXPIRY;
    
    $payload = [
        'sub' => $old_token->sub,
        'aud' => $old_token->aud,
        'alliances' => $old_token->alliances,
        'powereditor' => $old_token->powereditor ?? false,
        'jti' => $new_jti,
        'iat' => time(),
        'exp' => $new_expiry
    ];
    
    $new_token = JWT::encode($payload, SECRET_KEY, 'HS256');
    
    // Blacklist old token
    blacklist_token($old_token->jti, $old_token->exp);
    
    // Update active session tracking
    remove_active_session($old_token->sub, $old_token->jti);
    track_active_session($old_token->sub, $new_jti, $new_expiry);
    
    return [
        'token' => $new_token,
        'jti' => $new_jti,
        'expires' => $new_expiry
    ];
}

/**
 * Middleware function to check and rotate token automatically
 * Call this in protected pages before require_jwt_session()
 *
 * @return object|null Decoded token or null if rotation occurred
 */
function check_and_rotate_token() {
    if (!isset($_COOKIE['jwt'])) {
        return null;
    }
    
    try {
        $token = decode_jwt($_COOKIE['jwt']);
        
        // Check if token needs rotation
        if (should_rotate_token($token)) {
            $rotation_result = rotate_jwt_token($token);
            
            // Set new cookie
            set_session_cookie($rotation_result['token']);
            
            // Log rotation event
            error_log("Token rotated for user: " . $token->sub);
            
            // Return new token
            return JWT::decode($rotation_result['token'], new Key(SECRET_KEY, 'HS256'));
        }
        
        return $token;
    } catch (Exception $e) {
        // Token invalid, let normal auth flow handle it
        return null;
    }
}

/**
 * Enhanced session validation with automatic rotation
 *
 * @return object Decoded JWT token (possibly rotated)
 */
function require_jwt_session_with_rotation() {
    // Try to rotate token first
    $token = check_and_rotate_token();
    
    if ($token) {
        return $token;
    }
    
    // Fall back to normal session validation
    return require_jwt_session();
}

/**
 * Create refresh token for long-term authentication
 *
 * @param string $email User email
 * @param array $user User data
 * @return string Refresh token
 */
function create_refresh_token($email, $user) {
    $payload = [
        'sub' => $email,
        'aud' => 'refresh',
        'user_role' => $user['role'],
        'alliances' => $user['alliances'],
        'powereditor' => $user['powereditor'] ?? false,
        'jti' => bin2hex(random_bytes(16)),
        'iat' => time(),
        'exp' => time() + REFRESH_TOKEN_EXPIRY
    ];
    
    return JWT::encode($payload, SECRET_KEY, 'HS256');
}

/**
 * Exchange refresh token for new access token
 *
 * @param string $refresh_token Refresh token
 * @return array|false New token data or false on failure
 */
function refresh_access_token($refresh_token) {
    try {
        $token = JWT::decode($refresh_token, new Key(SECRET_KEY, 'HS256'));
        
        // Verify it's a refresh token
        if ($token->aud !== 'refresh') {
            throw new Exception('Not a refresh token');
        }
        
        // Check if token is blacklisted
        if (is_token_blacklisted($token->jti)) {
            throw new Exception('Refresh token has been revoked');
        }
        
        // Create new access token
        $new_jti = bin2hex(random_bytes(16));
        $new_expiry = time() + SESSION_TOKEN_EXPIRY;
        
        $access_payload = [
            'sub' => $token->sub,
            'aud' => $token->user_role,
            'alliances' => $token->alliances,
            'powereditor' => $token->powereditor ?? false,
            'jti' => $new_jti
        ];
        
        $access_token = encode_jwt($access_payload, SESSION_TOKEN_EXPIRY);
        
        // Track new session
        track_active_session($token->sub, $new_jti, $new_expiry);
        
        return [
            'access_token' => $access_token,
            'jti' => $new_jti,
            'expires' => $new_expiry
        ];
        
    } catch (Exception $e) {
        error_log("Refresh token validation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Automatic cleanup of expired tokens from blacklist
 * Call this periodically (e.g., via cron job)
 *
 * @return int Number of tokens cleaned up
 */
function cleanup_expired_tokens() {
    return cleanup_blacklist();
}

/**
 * Force rotation of all active sessions for a user
 * Useful for security incidents or password changes
 *
 * @param string $email User email
 * @return bool Success status
 */
function force_rotate_user_sessions($email) {
    try {
        $sessions = get_active_sessions($email);
        
        foreach ($sessions as $session) {
            // Blacklist all active tokens
            blacklist_token($session['jti'], $session['exp']);
        }
        
        // Clear all active sessions
        update_json_file(USERS_FILE, function(&$data) use ($email) {
            $email = strtolower(trim($email));
            
            foreach ($data['users'] as &$user) {
                if (strtolower($user['email']) === $email) {
                    $user['active_sessions'] = [];
                    return true;
                }
            }
            return false;
        });
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to force rotate sessions for $email: " . $e->getMessage());
        return false;
    }
}
?>