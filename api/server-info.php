<?php
/**
 * Public API: Server Information
 *
 * Returns server metadata, Discord info, and NAP15 details
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/server-info.php
 * Cache: 3600 seconds (1 hour)
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to server info data
$data_file = __DIR__ . '/../data/server-info.json';

// Read server info data
$server_info = read_json_safe($data_file);

if ($server_info === null) {
    api_error('Failed to load server information', 500);
}

// Server info rarely changes, cache for 1 hour
api_success_with_etag($server_info, 3600);
