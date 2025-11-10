<?php
/**
 * Alliance R4 Profile API
 * Allows R4s to update their Discord ID in alliances.json
 */

require_once __DIR__ . '/../admin/json_helpers.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    $alliance_tag = $input['alliance_tag'] ?? '';
    $name = $input['name'] ?? '';
    $discord_id = $input['discord_id'] ?? '';

    // Validate inputs
    if (empty($alliance_tag)) {
        throw new Exception('Alliance tag is required');
    }

    if (empty($name)) {
        throw new Exception('R4 name is required');
    }

    if (empty($discord_id)) {
        throw new Exception('Discord ID is required');
    }

    // Validate Discord ID format
    if (!preg_match('/^[0-9]{17,19}$/', $discord_id)) {
        throw new Exception('Invalid Discord ID format. Must be 17-19 digits.');
    }

    // Load alliances
    $alliances_file = __DIR__ . '/../data/alliances.json';
    $alliances = json_decode(file_get_contents($alliances_file), true);

    if (!is_array($alliances)) {
        throw new Exception('Invalid alliances data');
    }

    // Find the alliance
    $alliance_index = -1;
    foreach ($alliances as $index => $alliance) {
        if ($alliance['tag'] === $alliance_tag) {
            $alliance_index = $index;
            break;
        }
    }

    if ($alliance_index === -1) {
        throw new Exception('Alliance not found');
    }

    // Check if r4s array exists
    if (!isset($alliances[$alliance_index]['r4s']) || !is_array($alliances[$alliance_index]['r4s'])) {
        throw new Exception('No R4s configured for this alliance');
    }

    // Find the R4 by name
    $r4_index = -1;
    foreach ($alliances[$alliance_index]['r4s'] as $index => $r4) {
        if (strcasecmp($r4['name'], $name) === 0) {
            $r4_index = $index;
            break;
        }
    }

    if ($r4_index === -1) {
        throw new Exception("R4 named '{$name}' not found in alliance {$alliance_tag}");
    }

    // Update Discord ID
    $alliances[$alliance_index]['r4s'][$r4_index]['discordId'] = $discord_id;

    // Save alliances file
    if (!file_put_contents($alliances_file, json_encode($alliances, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save alliances data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Discord ID updated successfully',
        'alliance' => $alliance_tag,
        'role' => 'R4',
        'name' => $name
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
