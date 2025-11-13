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
$signature_data = read_json_safe($data_file);

if ($signature_data === null) {
    api_error('Failed to load signature history', 500);
}

// Filter alliances by server (multi-server support v3.8.0+)
// Note: signature-history.json has structure: { currentRulesVersion, lastUpdated, alliances: [...] }
// We need to filter only the alliances array, not the whole object
if (isset($signature_data['alliances']) && is_array($signature_data['alliances'])) {
    $signature_data['alliances'] = filter_by_server($signature_data['alliances']);
}

// Return with caching
api_success_with_etag($signature_data, 60);
