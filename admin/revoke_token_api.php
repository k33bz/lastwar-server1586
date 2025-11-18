<?php
/**
 * Token Revocation API
 *
 * Provides endpoints for revoking JWT tokens by user email
 *
 * Documentation:
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 * - Security Changelog: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/SECURITY_CHANGELOG.md
 * - Secret Key Rotation: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/SECRET_KEY_ROTATION_SETUP.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-13
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Require admin session
$admin_token = require_admin_session();

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF Protection
requireCsrfToken();

// Get action from POST data
$action = $_POST['action'] ?? '';

/**
 * Revoke all active tokens for a specific user email
 */
if ($action === 'revoke_user_tokens') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email required']);
        exit;
    }

    try {
        // Read blacklist file
        $blacklist = read_json_file(BLACKLIST_FILE);

        // Scan all session files (if using file-based sessions)
        // For JWT, we need to blacklist all tokens for this user
        // Since JWT tokens are stateless, we'll need to track active sessions

        // For now, we'll implement a simple approach:
        // 1. Add a marker to indicate user's tokens should be revoked
        // 2. Check this marker during token validation

        // Better approach: Store active session JTIs in users.json
        $users_data = read_json_file(USERS_FILE);
        $user_found = false;

        foreach ($users_data['users'] as &$user) {
            if (strtolower($user['email']) === strtolower(trim($email))) {
                $user_found = true;

                // If user has active_sessions, blacklist them all
                if (isset($user['active_sessions']) && is_array($user['active_sessions'])) {
                    foreach ($user['active_sessions'] as $session) {
                        if (isset($session['jti']) && isset($session['exp'])) {
                            blacklist_token($session['jti'], $session['exp']);
                        }
                    }

                    // Clear active sessions
                    $user['active_sessions'] = [];
                }

                break;
            }
        }

        if (!$user_found) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // Save updated users data
        write_json_file(USERS_FILE, $users_data);

        // Log audit event
        log_audit_event('tokens_revoked', $admin_token->sub, [
            'target_user' => $email,
            'revoked_by' => $admin_token->sub
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'All tokens revoked for user'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to revoke tokens: ' . $e->getMessage()]);
    }

    exit;
}

/**
 * Get active session info for a specific user
 */
if ($action === 'get_active_sessions') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email required']);
        exit;
    }

    try {
        $users_data = read_json_file(USERS_FILE);

        foreach ($users_data['users'] as $user) {
            if (strtolower($user['email']) === strtolower(trim($email))) {
                $active_sessions = $user['active_sessions'] ?? [];

                // Filter out expired sessions
                $now = time();
                $active_sessions = array_filter($active_sessions, function($session) use ($now) {
                    return isset($session['exp']) && $session['exp'] > $now;
                });

                echo json_encode([
                    'success' => true,
                    'active_sessions' => array_values($active_sessions),
                    'count' => count($active_sessions)
                ]);
                exit;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'User not found']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get sessions: ' . $e->getMessage()]);
    }

    exit;
}

// Unknown action
http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>
