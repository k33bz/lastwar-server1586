<?php
/**
 * User Profile API
 * Version: 1.1.0
 * Handles user profile updates
 *
 * Changelog:
 * - 1.1.0 (2025-01-18): Added UID-based identity support (v4.0.0+)
 *                      - User lookup by UID or email
 *                      - Email history tracking when email changes
 *                      - Fixed JWT token update to preserve UID in sub claim
 *                      - Maintains backward compatibility with legacy email-based tokens
 * - 1.0.1: Fixed authentication to use require_jwt_session_api() for proper JSON error responses
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);

require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';
require_once 'includes/input_validator.php';

header('Content-Type: application/json');

try {
    $user = require_jwt_session_api();

    // CSRF Protection
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrfToken();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $in_game_name = $_POST['in_game_name'] ?? '';
    $discord_id = $_POST['discord_id'] ?? '';
    $new_email = $_POST['email'] ?? '';
    $preferred_language = $_POST['preferred_language'] ?? 'en';

    // v4.0.0+: Get current email from email claim, or sub for legacy tokens
    $current_email = $user->email ?? $user->sub;
    $user_uid = $user->sub; // UID (v4.0.0+) or email (legacy)

    // Validate in-game name
    if (empty($in_game_name)) {
        throw new Exception('In-game name is required');
    }

    $name_validation = validate_text_field($in_game_name, 1, 50, true);
    if (!$name_validation['valid']) {
        throw new Exception('Invalid in-game name: ' . $name_validation['error']);
    }
    $in_game_name = $name_validation['sanitized'];

    // Validate Discord ID (optional but must be valid if provided)
    if (!empty($discord_id)) {
        // Discord IDs are 17-19 digit numbers
        if (!preg_match('/^[0-9]{17,19}$/', $discord_id)) {
            throw new Exception('Invalid Discord ID format. Must be 17-19 digits.');
        }
    }

    // Validate preferred language
    $supported_languages = ['en', 'es', 'pt', 'de', 'ko'];
    if (!in_array($preferred_language, $supported_languages)) {
        throw new Exception('Invalid language code');
    }

    // Validate email
    if (empty($new_email)) {
        throw new Exception('Email is required');
    }

    $new_email = filter_var($new_email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address format');
    }

    // Check if email changed
    $email_changed = ($new_email !== $current_email);

    // If email changed, check if new email already exists
    if ($email_changed) {
        $existing_user = get_user_by_email($new_email);
        if ($existing_user) {
            throw new Exception('Email address already in use by another account');
        }
    }

    // Load users
    $users_file = __DIR__ . '/users.json';
    $users_data = json_decode(file_get_contents($users_file), true);

    if (!isset($users_data['users']) || !is_array($users_data['users'])) {
        throw new Exception('Invalid users data structure');
    }

    // Find and update user (by UID for v4.0.0+ or email for legacy)
    $user_found = false;
    foreach ($users_data['users'] as &$user_entry) {
        // Check by UID (v4.0.0+) or email (legacy)
        $is_match = false;
        if (isset($user_entry['uid']) && $user_entry['uid'] === $user_uid) {
            $is_match = true;
        } elseif ($user_entry['email'] === $current_email) {
            $is_match = true;
        }

        if ($is_match) {
            $user_found = true;

            // Update in-game name
            $user_entry['in_game_name'] = $in_game_name;

            // Update Discord ID
            if (!empty($discord_id)) {
                $user_entry['discord_id'] = $discord_id;
            } elseif (isset($user_entry['discord_id'])) {
                // Remove Discord ID if cleared
                unset($user_entry['discord_id']);
            }

            // Update preferred language
            $user_entry['preferred_language'] = $preferred_language;

            // Update email if changed (with history tracking)
            if ($email_changed) {
                // Initialize email_history if not exists
                if (!isset($user_entry['email_history'])) {
                    $user_entry['email_history'] = [];
                }

                // Add old email to history
                $user_entry['email_history'][] = [
                    'email' => $current_email,
                    'changed_at' => gmdate('Y-m-d\TH:i:s\Z')
                ];

                // Update to new email
                $user_entry['email'] = $new_email;
            }

            break;
        }
    }
    unset($user_entry);

    if (!$user_found) {
        throw new Exception('User not found');
    }

    // Save users file
    if (!file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save user data');
    }

    // Log the update
    log_audit_event('user_profile_updated', $current_email, [
        'in_game_name' => $in_game_name,
        'discord_id' => !empty($discord_id) ? 'Updated' : 'Cleared',
        'preferred_language' => $preferred_language,
        'email_changed' => $email_changed,
        'new_email' => $email_changed ? $new_email : null
    ]);

    // Regenerate JWT token with new language and email
    // v4.0.0+: Update email claim (not sub, which is the UID)
    if ($email_changed) {
        // For v4.0.0+ tokens, update the email claim
        // For legacy tokens, sub is the email identifier (will be updated)
        if (isset($user->email)) {
            $user->email = $new_email;
        } else {
            // Legacy token: sub is email
            $user->sub = $new_email;
        }
    }

    // Create new session token with updated language
    $new_token = create_session_token($user, $preferred_language);

    // Set new session cookie
    setcookie('session', $new_token, [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/admin',
        'httponly' => true,
        'secure' => false,
        'samesite' => 'Lax'
    ]);

    $response = [
        'success' => true,
        'message' => 'Profile updated successfully',
        'email_changed' => $email_changed,
        'language_updated' => true,
        'reload_required' => true
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
