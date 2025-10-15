<?php
/**
 * Enhanced JWT Functions with Secret Key Rotation Support
 *
 * Drop-in replacement for jwt.php functions that supports key rotation
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/secret_key_rotation.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Enhanced JWT decode that handles key rotation
 *
 * @param string $token JWT token string
 * @return object Decoded token payload
 * @throws Exception if token is invalid with all available keys
 */
function enhanced_decode_jwt($token) {
    try {
        // Try decoding with rotation-aware function
        $decoded = decode_jwt_with_rotation($token);

        // Check if token is blacklisted (still needed for manual revocations)
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
 * Enhanced session validation with key rotation support
 *
 * @return object Decoded JWT token
 */
function require_enhanced_jwt_session_with_key_rotation() {
    if (!isset($_COOKIE['jwt'])) {
        header('Location: login.php?error=no_session');
        exit;
    }

    try {
        $token = enhanced_decode_jwt($_COOKIE['jwt']);

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
        } elseif (strpos($e->getMessage(), 'key rotated') !== false) {
            $error = 'key_rotated';
        }

        header('Location: login.php?error=' . $error);
        exit;
    }
}

/**
 * Enhanced admin session validation with key rotation
 *
 * @return object Decoded JWT token
 */
function require_enhanced_admin_session_with_key_rotation() {
    $token = require_enhanced_jwt_session_with_key_rotation();

    if ($token->aud !== 'admin') {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }

    return $token;
}

/**
 * Enhanced magic link validation with key rotation support
 *
 * @param string $magic_token_string Magic link token
 * @return object Decoded magic token
 */
function validate_magic_link_with_key_rotation($magic_token_string) {
    try {
        // Decode with key rotation support
        $magic_token = enhanced_decode_jwt($magic_token_string);

        // Verify it's actually a magic link token
        if (!isset($magic_token->magic) || $magic_token->magic !== true) {
            throw new Exception('Not a valid magic link token');
        }

        return $magic_token;
    } catch (Exception $e) {
        throw new Exception('Magic link validation failed: ' . $e->getMessage());
    }
}
?>