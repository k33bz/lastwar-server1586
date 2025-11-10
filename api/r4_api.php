<?php
/**
 * R4 Management API
 * Handles R4 operations for alliances
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../admin/jwt.php';
require_once __DIR__ . '/../admin/includes/alliance_helper.php';

// Require authentication
$user = require_jwt_session();
$user_token = (object)[
    'sub' => $user->sub,
    'aud' => $user->aud,
    'alliances' => $user->alliances ?? []
];

// GET: List R4s for an alliance
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tag = $_GET['tag'] ?? '';

    if (empty($tag)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing alliance tag']);
        exit;
    }

    // Check permission
    if (!has_alliance_access($user_token, $tag)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $result = AllianceHelper::getAllianceByTag($tag);
    if (!$result) {
        http_response_code(404);
        echo json_encode(['error' => 'Alliance not found']);
        exit;
    }

    $alliance = $result['alliance'];
    $r4s = $alliance['r4s'] ?? [];

    echo json_encode([
        'success' => true,
        'alliance_tag' => $tag,
        'r4s' => $r4s
    ]);
    exit;
}

// POST: Add or update R4
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $tag = $input['alliance_tag'] ?? '';

    if (empty($tag)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing alliance tag']);
        exit;
    }

    // Check permission
    if (!has_alliance_access($user_token, $tag)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    $alliances = AllianceHelper::loadAlliances();
    $result = AllianceHelper::getAllianceByTag($tag);

    if (!$result) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Alliance not found']);
        exit;
    }

    $index = $result['index'];
    $alliance = $result['alliance'];

    // Initialize r4s array if doesn't exist
    if (!isset($alliance['r4s'])) {
        $alliance['r4s'] = [];
    }

    // Add R4
    if ($action === 'add') {
        $name = $input['name'] ?? '';
        $gameId = $input['gameId'] ?? null;
        $discordId = $input['discordId'] ?? null;
        $canVote = $input['canVote'] ?? false;
        $role = $input['role'] ?? null;

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'R4 name is required']);
            exit;
        }

        $new_r4 = [
            'name' => $name,
            'gameId' => $gameId,
            'discordId' => $discordId,
            'canVote' => (bool)$canVote,
            'role' => $role,
            'addedDate' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        $alliance['r4s'][] = $new_r4;
        $alliances[$index] = $alliance;

        AllianceHelper::saveAlliances($alliances);

        echo json_encode([
            'success' => true,
            'message' => 'R4 added successfully',
            'r4' => $new_r4
        ]);
        exit;
    }

    // Update R4
    if ($action === 'update') {
        $r4_index = $input['r4_index'] ?? null;

        if ($r4_index === null || !isset($alliance['r4s'][$r4_index])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'R4 not found']);
            exit;
        }

        // Update fields if provided
        if (isset($input['name'])) {
            $alliance['r4s'][$r4_index]['name'] = $input['name'];
        }
        if (isset($input['gameId'])) {
            $alliance['r4s'][$r4_index]['gameId'] = $input['gameId'];
        }
        if (isset($input['discordId'])) {
            $alliance['r4s'][$r4_index]['discordId'] = $input['discordId'];
        }
        if (isset($input['canVote'])) {
            $alliance['r4s'][$r4_index]['canVote'] = (bool)$input['canVote'];
        }
        if (isset($input['role'])) {
            $alliance['r4s'][$r4_index]['role'] = $input['role'];
        }

        $alliances[$index] = $alliance;
        AllianceHelper::saveAlliances($alliances);

        echo json_encode([
            'success' => true,
            'message' => 'R4 updated successfully',
            'r4' => $alliance['r4s'][$r4_index]
        ]);
        exit;
    }

    // Delete R4
    if ($action === 'delete') {
        $r4_index = $input['r4_index'] ?? null;

        if ($r4_index === null || !isset($alliance['r4s'][$r4_index])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'R4 not found']);
            exit;
        }

        $deleted_r4 = $alliance['r4s'][$r4_index];
        array_splice($alliance['r4s'], $r4_index, 1);

        $alliances[$index] = $alliance;
        AllianceHelper::saveAlliances($alliances);

        echo json_encode([
            'success' => true,
            'message' => 'R4 removed successfully',
            'deleted_r4' => $deleted_r4
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
