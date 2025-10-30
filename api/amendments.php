<?php
/**
 * Public API: Rule Amendments
 *
 * Returns the history of rule changes and amendments
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/amendments.php
 * Cache: 300 seconds (5 minutes)
 *
 * Documentation:
 * - Amendment System: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/CLAUDE.md
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to amendments data
$data_file = __DIR__ . '/../data/amendments.json';

// Read amendments data
$amendments = read_json_safe($data_file);

if ($amendments === null) {
    api_error('Failed to load amendments data', 500);
}

// Amendments change infrequently, cache for 5 minutes
api_success_with_etag($amendments, 300);
