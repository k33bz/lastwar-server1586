<?php
/**
 * Public API: Server Rules
 *
 * Returns the current server rules and NAP15 agreements
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/rules.php
 * Cache: 300 seconds (5 minutes)
 *
 * Documentation:
 * - Rules Management: https://github.com/k33bz/lastwar-server1586/blob/mainline/CLAUDE.md
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to rules data
$data_file = __DIR__ . '/../data/rules.json';

// Read rules data
$rules = read_json_safe($data_file);

if ($rules === null) {
    api_error('Failed to load rules data', 500);
}

// Rules change infrequently, cache for 5 minutes
api_success_with_etag($rules, 300);
