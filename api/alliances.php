<?php
/**
 * Public API: Alliance Rankings
 *
 * Returns the current top 15 alliance rankings with power, R5, and signature status
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/alliances.php
 * Cache: 60 seconds
 *
 * Documentation:
 * - Data Schema: https://github.com/k33bz/lastwar-server1586/blob/mainline/data/ALLIANCE_SCHEMA.md
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to alliances data
$data_file = __DIR__ . '/../data/alliances.json';

// Read alliances data
$alliances = read_json_safe($data_file);

if ($alliances === null) {
    api_error('Failed to load alliance data', 500);
}

// Calculate ranks dynamically from power
// Sort by power descending
usort($alliances, function($a, $b) {
    return ($b['power'] ?? 0) - ($a['power'] ?? 0);
});

// Add calculated rank to each alliance
foreach ($alliances as $index => &$alliance) {
    $alliance['rank'] = $index + 1;
}
unset($alliance); // Break reference

// SECURITY: Strip PII before sending to public
$sanitized_alliances = strip_alliance_pii($alliances);

// Return with ETag support for efficient caching
api_success_with_etag($sanitized_alliances, 60);
