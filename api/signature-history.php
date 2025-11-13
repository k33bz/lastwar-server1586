<?php
/**
 * Signature History API
 *
 * Returns the signature history for server rules
 * Serves data from data/signature-history.json with PII stripped
 * Multi-server support added in v3.8.0
 *
 * @version 1.1.0
 * @created 2025-11-12
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to signature history data
$data_file = __DIR__ . '/../data/signature-history.json';

// Read signature history
$signatures = read_json_safe($data_file);

if ($signatures === null) {
    api_error('Failed to load signature history', 500);
}

// Filter by server (multi-server support v3.8.0+)
$signatures = filter_by_server($signatures);

// Return with caching
api_success_with_etag($signatures, 60);
