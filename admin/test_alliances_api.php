<?php
// Test the alliance API logic
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/jwt.php';
    require_once __DIR__ . '/json_helpers.php';

    // Require authentication for security
    $user = require_jwt_session();

    $alliances_file = __DIR__ . '/../data/alliances.json';

    // Test reading alliances
    $alliances = json_read($alliances_file);

    // Extract only needed fields and add index for editing
    $simplified = array_map(function($alliance, $index) {
        return [
            'index' => $index,
            'tag' => $alliance['tag'] ?? '',
            'name' => $alliance['name'] ?? '',
            'power' => $alliance['power'] ?? 0
        ];
    }, $alliances, array_keys($alliances));

    echo json_encode([
        'status' => 'success',
        'count' => count($simplified),
        'first_3' => array_slice($simplified, 0, 3)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
