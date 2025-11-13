<?php
/**
 * Multi-Server Alliances API Example
 *
 * This shows how to modify the existing alliances.php API
 * to support multiple servers with a simple query parameter.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get server parameter (defaults to 1586 for backwards compatibility)
$server = $_GET['server'] ?? '1586';

// Validate server ID (alphanumeric only)
if (!preg_match('/^[a-zA-Z0-9]+$/', $server)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid server ID']);
    exit;
}

// Read alliances data
$alliances_file = __DIR__ . '/../data/alliances.json';
$alliances = json_decode(file_get_contents($alliances_file), true);

// Filter by server
$filtered_alliances = array_filter($alliances, function($alliance) use ($server) {
    // If no server field, assume it's old data (default to 1586)
    $allianceServer = $alliance['server'] ?? '1586';
    return $allianceServer === $server;
});

// Re-index array after filtering
$filtered_alliances = array_values($filtered_alliances);

// Return filtered data
echo json_encode([
    'success' => true,
    'server' => $server,
    'count' => count($filtered_alliances),
    'alliances' => $filtered_alliances,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

/**
 * Example Usage:
 *
 * Server 1586 public site:
 *   fetch('/api/alliances.php?server=1586')
 *
 * Server 9999 public site:
 *   fetch('/api/alliances.php?server=9999')
 *
 * Admin panel (show all):
 *   fetch('/api/alliances.php') // No filter, returns all servers
 *
 * Or explicitly:
 *   fetch('/api/alliances.php?server=*') // Returns all servers
 */
