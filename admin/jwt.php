<?php
/**
 * JWT Helper Functions
 *
 * Provides JWT encoding, decoding, and session validation functions
 * for the Last War 1586 Admin authentication system
 *
 * Documentation:
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 * - Secret Key Rotation: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/SECRET_KEY_ROTATION_SETUP.md
 * - Security Changelog: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/SECURITY_CHANGELOG.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 2.2.0
 * @date 2025-10-31
 * @changelog
 *   2.2.0 (2025-10-31) - Added multi-role system support (Database schema v3)
 *                       - Added has_role(), get_user_roles(), get_primary_role() helpers
 *                       - Updated create_magic_link_token() to support roles array
 *                       - Maintains backward compatibility with old role + powereditor format
 *                       - JWT tokens now include 'roles' array for new users
 *                       - Migration handled by migrate.php v3.4.0
 *   2.1.0 (2025-10-15) - Added JWT key rotation support
 *                       - Enhanced decode_jwt() with rotation fallback
 *                       - Added conditional key rotation loading
 *                       - Improved error handling for key rotation scenarios
 *   2.0.0 (2025-10-15) - Added powereditor role support
 *                       - Added powereditor flag to JWT tokens
 *                       - Added is_power_editor() helper function
 *                       - Added can_delete_alliances() helper function
 *   1.1.0 (2025-10-13) - Added active session tracking in users.json
 *   1.0.0 (2025-10-12) - Initial complete implementation with proper error handling
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load key rotation support if available and JWT classes are loaded
if (file_exists(__DIR__ . '/secret_key_rotation.php') && class_exists('Firebase\JWT\JWT')) {
    require_once __DIR__ . '/secret_key_rotation.php';
}

/**
 * Encode JWT token with payload
 *
 * @param array $payload JWT claims
 * @param int $expiry Expiry time in seconds from now
 * @return string Encoded JWT token
 */
function encode_jwt($payload, $expiry = null) {
    if ($expiry === null) {
        $expiry = SESSION_TOKEN_EXPIRY;
    }

    $payload['exp'] = time() + $expiry;
    $payload['iat'] = time();

    // Generate unique JWT ID if not provided
    if (!isset($payload['jti'])) {
        $payload['jti'] = bin2hex(random_bytes(16));
    }

    return JWT::encode($payload, SECRET_KEY, 'HS256');
}

/**
 * Decode and validate JWT token with key rotation support
 *
 * @param string $token JWT token string
 * @return object Decoded token payload
 * @throws Exception if token is invalid, expired, or blacklisted
 */
function decode_jwt($token) {
    try {
        // Try with key rotation support if available
        if (function_exists('decode_jwt_with_rotation')) {
            $decoded = decode_jwt_with_rotation($token);
        } else {
            // Fallback to single key validation
            $decoded = JWT::decode($token, new Key(SECRET_KEY, 'HS256'));
        }

        // Check if token is blacklisted
        if (is_token_blacklisted($decoded->jti)) {
            throw new Exception('Token has been revoked');
        }

        return $decoded;
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception('Token has expired');
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        throw new Exception('Invalid token signature or key rotated');
    } catch (Exception $e) {
        throw new Exception('Invalid token: ' . $e->getMessage());
    }
}

/**
 * Require valid JWT session for protected pages
 *
 * Validates JWT from cookie and redirects to login if invalid
 * Returns decoded token payload if valid
 *
 * @return object Decoded JWT token
 */
function require_jwt_session() {
    if (!isset($_COOKIE['jwt'])) {
        header('Location: login.php?error=no_session');
        exit;
    }

    try {
        $token = decode_jwt($_COOKIE['jwt']);

        // Additional validation: check token is not a magic link token
        if (isset($token->magic) && $token->magic === true) {
            throw new Exception('Magic link tokens cannot be used as session tokens');
        }

        return $token;
    } catch (Exception $e) {
        // Clear invalid cookie
        setcookie('jwt', '', time() - 3600, '/admin/', '', true, true);

        // Determine error type for better UX
        $error = 'invalid';
        if (strpos($e->getMessage(), 'expired') !== false) {
            $error = 'expired';
        } elseif (strpos($e->getMessage(), 'revoked') !== false) {
            $error = 'revoked';
        }

        header('Location: login.php?error=' . $error);
        exit;
    }
}

/**
 * Require admin role for protected pages
 *
 * Validates JWT session and ensures user has admin role
 *
 * @return object Decoded JWT token
 */
function require_admin_session() {
    $token = require_jwt_session();

    if ($token->aud !== 'admin') {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }

    return $token;
}

/**
 * Check if user has access to a specific alliance
 *
 * @param object $token Decoded JWT token
 * @param string $alliance_tag Alliance tag to check
 * @return bool True if user has access
 */
function has_alliance_access($token, $alliance_tag) {
    // Admin with * access or users with * in alliances have access to all alliances
    if (($token->aud === 'admin' && in_array('*', $token->alliances)) || in_array('*', $token->alliances)) {
        return true;
    }

    // Check if alliance is in user's allowed list (case-insensitive)
    return in_array(strtolower($alliance_tag), array_map('strtolower', $token->alliances));
}

/**
 * Check if user can sign rules (R5 only)
 *
 * @param object $token Decoded JWT token
 * @param string $alliance_tag Alliance tag to check
 * @return bool True if user can sign rules
 */
function can_sign_rules($token, $alliance_tag) {
    // Only R5 or admin can sign rules
    if ($token->aud === 'admin' || $token->aud === 'r5') {
        return has_alliance_access($token, $alliance_tag);
    }
    return false;
}

/**
 * Check if user is R4 or higher (R4, R5, or admin)
 *
 * @param object $token Decoded JWT token
 * @return bool True if user is R4+
 */
function is_r4_or_higher($token) {
    return in_array($token->aud, ['admin', 'r5', 'r4']);
}

/**
 * Check if user has power editor access
 *
 * @param object $token Decoded JWT token
 * @return bool True if user can access power editor
 */
function is_power_editor($token) {
    // Admins always have access
    if ($token->aud === 'admin') {
        return true;
    }

    // Check powereditor flag for R5/R4 users
    return isset($token->powereditor) && $token->powereditor === true;
}

/**
 * Check if user can delete alliances (admins only)
 *
 * @param object $token Decoded JWT token
 * @return bool True if user can delete alliances
 */
function can_delete_alliances($token) {
    return $token->aud === 'admin';
}

/**
 * Check if user has a specific role (supports multi-role)
 *
 * @param object $token Decoded JWT token
 * @param string|array $role Role(s) to check for ('admin', 'r5', 'r4', 'ape', etc.)
 * @return bool True if user has the role (or any of the roles if array)
 */
function has_role($token, $role) {
    // If checking multiple roles (array), check if user has any of them
    if (is_array($role)) {
        foreach ($role as $r) {
            if (has_role($token, $r)) {
                return true;
            }
        }
        return false;
    }

    // Check new multi-role format first
    if (isset($token->roles) && is_array($token->roles)) {
        return in_array($role, $token->roles);
    }

    // Backward compatibility: check old format
    if ($token->aud === $role) {
        return true;
    }

    // Check old powereditor flag for APE role
    if ($role === 'ape' && isset($token->powereditor) && $token->powereditor) {
        return true;
    }

    return false;
}

/**
 * Get all roles for a user (supports multi-role)
 *
 * @param object $token Decoded JWT token
 * @return array Array of roles
 */
function get_user_roles($token) {
    // Check new multi-role format first
    if (isset($token->roles) && is_array($token->roles)) {
        return $token->roles;
    }

    // Backward compatibility: convert old format
    $roles = [];
    if (isset($token->aud)) {
        $roles[] = $token->aud;
    }
    if (isset($token->powereditor) && $token->powereditor) {
        $roles[] = 'ape';
    }

    return $roles;
}

/**
 * Get primary role for backward compatibility
 *
 * @param array $roles Array of roles
 * @return string Primary role (for aud claim)
 */
function get_primary_role($roles) {
    // Priority order: admin > r5 > r4 > ape > none > disabled
    $priority = ['admin', 'r5', 'r4', 'ape', 'none', 'disabled'];

    foreach ($priority as $role) {
        if (in_array($role, $roles)) {
            return $role;
        }
    }

    return $roles[0] ?? 'none';
}

/**
 * Create magic link token
 *
 * @param string $email User email
 * @param array $user User data from users.json
 * @return string Magic link JWT token
 */
function create_magic_link_token($email, $user) {
    // Support both old format (role + powereditor) and new format (roles array)
    if (isset($user['roles']) && is_array($user['roles'])) {
        // New multi-role format
        $payload = [
            'sub' => $email,
            'aud' => get_primary_role($user['roles']), // Backward compatibility
            'roles' => $user['roles'],
            'alliances' => $user['alliances'],
            'jti' => bin2hex(random_bytes(16)),
            'magic' => true
        ];
    } else {
        // Old format (backward compatibility)
        $payload = [
            'sub' => $email,
            'aud' => $user['role'],
            'alliances' => $user['alliances'],
            'powereditor' => $user['powereditor'] ?? false,
            'jti' => bin2hex(random_bytes(16)),
            'magic' => true
        ];
    }

    return encode_jwt($payload, MAGIC_LINK_EXPIRY);
}

/**
 * Create session token from magic link token
 *
 * @param object $magic_token Decoded magic link token
 * @return string Session JWT token
 */
function create_session_token($magic_token) {
    $jti = bin2hex(random_bytes(16));
    $exp = time() + SESSION_TOKEN_EXPIRY;

    $payload = [
        'sub' => $magic_token->sub,
        'aud' => $magic_token->aud,
        'alliances' => $magic_token->alliances,
        'powereditor' => $magic_token->powereditor ?? false,
        'jti' => $jti
    ];

    $token = encode_jwt($payload, SESSION_TOKEN_EXPIRY);

    // Track active session in users.json
    track_active_session($magic_token->sub, $jti, $exp);

    return $token;
}

/**
 * Set JWT session cookie
 *
 * @param string $token JWT token
 * @param int $expiry Expiry time in seconds from now
 */
function set_session_cookie($token, $expiry = null) {
    if ($expiry === null) {
        $expiry = SESSION_TOKEN_EXPIRY;
    }

    setcookie('jwt', $token, [
        'expires' => time() + $expiry,
        'path' => '/admin/',
        'domain' => '',
        'secure' => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

/**
 * Clear JWT session cookie
 */
function clear_session_cookie() {
    setcookie('jwt', '', [
        'expires' => time() - 3600,
        'path' => '/admin/',
        'domain' => '',
        'secure' => APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

/**
 * Revoke current session token
 *
 * @return bool Success status
 */
function revoke_current_session() {
    if (isset($_COOKIE['jwt'])) {
        try {
            $token = JWT::decode($_COOKIE['jwt'], new Key(SECRET_KEY, 'HS256'));
            blacklist_token($token->jti, $token->exp);

            // Remove from active sessions tracking
            remove_active_session($token->sub, $token->jti);
        } catch (Exception $e) {
            // Token already invalid, just clear cookie
            error_log("Error revoking token: " . $e->getMessage());
        }
    }

    clear_session_cookie();
    return true;
}

/**
 * Track active session in users.json
 *
 * @param string $email User email
 * @param string $jti JWT ID
 * @param int $exp Token expiry timestamp
 * @return bool Success status
 */
function track_active_session($email, $jti, $exp) {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email, $jti, $exp) {
            $email = strtolower(trim($email));

            foreach ($data['users'] as &$user) {
                if (strtolower($user['email']) === $email) {
                    // Initialize active_sessions if not exists
                    if (!isset($user['active_sessions'])) {
                        $user['active_sessions'] = [];
                    }

                    // Clean up expired sessions first
                    $now = time();
                    $user['active_sessions'] = array_values(array_filter(
                        $user['active_sessions'],
                        function($session) use ($now) {
                            return isset($session['exp']) && $session['exp'] > $now;
                        }
                    ));

                    // Add new session
                    $user['active_sessions'][] = [
                        'jti' => $jti,
                        'exp' => $exp,
                        'created_at' => time(),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ];

                    return true;
                }
            }

            return false;
        });
    } catch (Exception $e) {
        error_log("Error tracking session: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove active session from users.json
 *
 * @param string $email User email
 * @param string $jti JWT ID
 * @return bool Success status
 */
function remove_active_session($email, $jti) {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email, $jti) {
            $email = strtolower(trim($email));

            foreach ($data['users'] as &$user) {
                if (strtolower($user['email']) === $email) {
                    if (isset($user['active_sessions'])) {
                        $user['active_sessions'] = array_values(array_filter(
                            $user['active_sessions'],
                            function($session) use ($jti) {
                                return $session['jti'] !== $jti;
                            }
                        ));
                    }
                    return true;
                }
            }

            return false;
        });
    } catch (Exception $e) {
        error_log("Error removing session: " . $e->getMessage());
        return false;
    }
}

/**
 * Get active sessions for a user
 *
 * @param string $email User email
 * @return array Array of active sessions
 */
function get_active_sessions($email) {
    try {
        $users_data = read_json_file(USERS_FILE);
        $email = strtolower(trim($email));

        foreach ($users_data['users'] as $user) {
            if (strtolower($user['email']) === $email) {
                $sessions = $user['active_sessions'] ?? [];

                // Filter out expired sessions
                $now = time();
                return array_values(array_filter($sessions, function($session) use ($now) {
                    return isset($session['exp']) && $session['exp'] > $now;
                }));
            }
        }

        return [];
    } catch (Exception $e) {
        error_log("Error getting active sessions: " . $e->getMessage());
        return [];
    }
}
?>
