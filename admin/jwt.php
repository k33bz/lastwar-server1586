<?php
/**
 * JWT Helper Functions
 *
 * Provides JWT encoding, decoding, and session validation functions
 * for the Last War 1586 Admin authentication system
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation with proper error handling
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
 * Decode and validate JWT token
 *
 * @param string $token JWT token string
 * @return object Decoded token payload
 * @throws Exception if token is invalid, expired, or blacklisted
 */
function decode_jwt($token) {
    try {
        $decoded = JWT::decode($token, new Key(SECRET_KEY, 'HS256'));

        // Check if token is blacklisted
        if (is_token_blacklisted($decoded->jti)) {
            throw new Exception('Token has been revoked');
        }

        return $decoded;
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception('Token has expired');
    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        throw new Exception('Invalid token signature');
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
    // Admin has access to all alliances
    if ($token->aud === 'admin' || in_array('*', $token->alliances)) {
        return true;
    }

    // Check if alliance is in user's allowed list
    return in_array(strtolower($alliance_tag), array_map('strtolower', $token->alliances));
}

/**
 * Create magic link token
 *
 * @param string $email User email
 * @param array $user User data from users.json
 * @return string Magic link JWT token
 */
function create_magic_link_token($email, $user) {
    $payload = [
        'sub' => $email,
        'aud' => $user['role'],
        'alliances' => $user['alliances'],
        'jti' => bin2hex(random_bytes(16)),
        'magic' => true
    ];

    return encode_jwt($payload, MAGIC_LINK_EXPIRY);
}

/**
 * Create session token from magic link token
 *
 * @param object $magic_token Decoded magic link token
 * @return string Session JWT token
 */
function create_session_token($magic_token) {
    $payload = [
        'sub' => $magic_token->sub,
        'aud' => $magic_token->aud,
        'alliances' => $magic_token->alliances,
        'jti' => bin2hex(random_bytes(16))
    ];

    return encode_jwt($payload, SESSION_TOKEN_EXPIRY);
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
        } catch (Exception $e) {
            // Token already invalid, just clear cookie
            error_log("Error revoking token: " . $e->getMessage());
        }
    }

    clear_session_cookie();
    return true;
}
?>
