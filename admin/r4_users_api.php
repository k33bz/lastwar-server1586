<?php
/**
 * R4 Users API (Admin-side)
 * Returns eligible R4 users for alliance management
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once 'jwt.php';

// Require authentication
$user = require_jwt_session();

header('Content-Type: application/json');

$tag = $_GET['tag'] ?? '';
if (empty($tag)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing alliance tag']);
    exit;
}

// Create user token for permission check
$user_token = (object)[
    'sub' => $user->sub,
    'aud' => $user->aud,
    'alliances' => $user->alliances ?? []
];

// Check permission
if (!has_alliance_access($user_token, $tag)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Load users.json
$users_file = __DIR__ . '/users.json';
if (!file_exists($users_file)) {
    echo json_encode([
        'success' => true,
        'eligible_users' => []
    ]);
    exit;
}

$users_data = json_decode(file_get_contents($users_file), true);
$eligible_users = [];

if (isset($users_data['users'])) {
    foreach ($users_data['users'] as $user_account) {
        // Check if user has r4 role
        $roles = $user_account['roles'] ?? [];
        if (!in_array('r4', $roles)) {
            continue;
        }

        // Check if user has access to this alliance
        $user_alliances = $user_account['servers']['1586']['alliances'] ?? [];
        if (!in_array($tag, $user_alliances) && !in_array('*', $user_alliances)) {
            continue;
        }

        $eligible_users[] = [
            'uid' => $user_account['uid'] ?? null,
            'email' => $user_account['email'],
            'in_game_name' => $user_account['in_game_name'] ?? null,
            'discord_id' => $user_account['discord_id'] ?? null,
            'roles' => $roles
        ];
    }
}

echo json_encode([
    'success' => true,
    'alliance_tag' => $tag,
    'eligible_users' => $eligible_users
]);
