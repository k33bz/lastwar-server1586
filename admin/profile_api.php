<?php
/**
 * User Profile API
 * Handles user profile updates
 */

require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';
require_once 'includes/input_validator.php';

header('Content-Type: application/json');

try {
    $user = require_jwt_session();

    // CSRF Protection
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrfToken();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $in_game_name = $_POST['in_game_name'] ?? '';
    $new_email = $_POST['email'] ?? '';
    $current_email = $user->sub;

    // Validate in-game name
    if (empty($in_game_name)) {
        throw new Exception('In-game name is required');
    }

    $name_validation = validate_text_field($in_game_name, 1, 50, true);
    if (!$name_validation['valid']) {
        throw new Exception('Invalid in-game name: ' . $name_validation['error']);
    }
    $in_game_name = $name_validation['sanitized'];

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

    // Find and update user
    $user_found = false;
    foreach ($users_data['users'] as &$user_entry) {
        if ($user_entry['email'] === $current_email) {
            $user_found = true;

            // Update in-game name
            $user_entry['in_game_name'] = $in_game_name;

            // Update email if changed
            if ($email_changed) {
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
        'email_changed' => $email_changed,
        'new_email' => $email_changed ? $new_email : null
    ]);

    $response = [
        'success' => true,
        'message' => 'Profile updated successfully',
        'email_changed' => $email_changed
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
