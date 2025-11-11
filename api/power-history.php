<?php
/**
 * Public API: Power History
 *
 * Returns historical power data for alliances in CSV format
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/power-history.php
 * Cache: 300 seconds (5 minutes)
 *
 * Note: CSV format contains only alliance tags (public) and power numbers (public data)
 * No PII is contained in this dataset
 *
 * @version 1.0.0
 * @date 2025-11-11
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to power history CSV
$csv_file = __DIR__ . '/../data/power-history.csv';

if (!file_exists($csv_file)) {
    api_error('Power history data not found', 404);
}

$csv_content = file_get_contents($csv_file);

if ($csv_content === false) {
    api_error('Failed to load power history data', 500);
}

// Set CSV-specific headers
header('Content-Type: text/csv; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: public, max-age=300'); // 5 minutes
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Generate ETag for caching
$etag = '"' . md5($csv_content) . '"';

// Check if client has cached version
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
    header('HTTP/1.1 304 Not Modified');
    header('ETag: ' . $etag);
    exit;
}

// Set ETag and return CSV
header('ETag: ' . $etag);
echo $csv_content;
