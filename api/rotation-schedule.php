<?php
/**
 * Council Rotation Schedule API
 *
 * Returns the council rotation schedule for public display
 * Serves data from data/rotation-schedule.json with PII stripped
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

// Get server ID for filtering
$server_id = get_server_id();

// Path to rotation schedule data
$data_file = __DIR__ . '/../data/rotation-schedule.json';

// Read rotation schedule
$schedule_data = read_json_safe($data_file);

if ($schedule_data === null) {
    api_error('Failed to load rotation schedule', 500);
}

// Unwrap schedule for the requested server (v3.8.0+)
$schedule_data = unwrap_rotation_schedule($schedule_data, $server_id);

if ($schedule_data === null) {
    api_error('Schedule not found for server ' . $server_id, 404);
}

// Return with caching
api_success_with_etag($schedule_data, 60);
