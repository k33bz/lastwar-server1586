<?php
/**
 * Public API: Version Information
 *
 * Returns current version, release date, and component versions
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/version.php
 * Cache: 300 seconds (5 minutes)
 *
 * Documentation:
 * - Versioning System: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/VERSIONING.md
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to version data
$data_file = __DIR__ . '/../version.json';

// Read version data
$version = read_json_safe($data_file);

if ($version === null) {
    api_error('Failed to load version data', 500);
}

// Version changes infrequently, cache for 5 minutes
api_success_with_etag($version, 300);
