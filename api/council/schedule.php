<?php
/**
 * Public API: Council Rotation Schedule
 *
 * Returns the complete council rotation schedule
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/council/schedule.php
 * Cache: 300 seconds (5 minutes)
 *
 * Query Parameters:
 * - weeks: Number of future weeks to return (default: 5, max: 52)
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/../api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Get query parameters
$weeks_ahead = min((int)($_GET['weeks'] ?? 5), 52);
$server_id = get_server_id();

// Path to rotation schedule
$data_file = __DIR__ . '/../../data/rotation-schedule.json';

// Read schedule data
$schedule_data = read_json_safe($data_file);

if ($schedule_data === null) {
    api_error('Failed to load rotation schedule', 500);
}

// Unwrap schedule for the requested server (v3.8.0+)
$schedule_data = unwrap_rotation_schedule($schedule_data, $server_id);

if ($schedule_data === null) {
    api_error('Schedule not found for server ' . $server_id, 404);
}

// Calculate current week number
$epoch = strtotime('2025-05-18 22:00:00 EDT'); // Week 1 start
$now = time();
$weeks_elapsed = floor(($now - $epoch) / (7 * 24 * 60 * 60));
$current_week = $weeks_elapsed + 1;

// Filter schedule to show current week + N weeks ahead
$filtered_schedule = array_filter($schedule_data['schedule'] ?? [], function($week) use ($current_week, $weeks_ahead) {
    return $week['weekNumber'] >= $current_week &&
           $week['weekNumber'] <= ($current_week + $weeks_ahead);
});

// Re-index array
$filtered_schedule = array_values($filtered_schedule);

// Build response
$response = [
    'currentWeek' => $current_week,
    'weeksShown' => count($filtered_schedule),
    'schedule' => $filtered_schedule,
    'epoch' => [
        'weekOne' => '2025-05-18',
        'time' => '22:00 EDT (02:00 UTC)',
        'rotationDay' => 'Sunday'
    ]
];

// Return with caching
api_success_with_etag($response, 300);
