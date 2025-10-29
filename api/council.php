<?php
/**
 * Public API: Current Council Members
 *
 * Returns the current week's voting council members (permanent + rotating)
 * READ-ONLY endpoint - no authentication required
 *
 * Endpoint: GET /api/council.php
 * Cache: 60 seconds
 *
 * Documentation:
 * - Council System: https://github.com/k33bz/lastwar-server1586/blob/mainline/CLAUDE.md
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Path to data files
$alliances_file = __DIR__ . '/../data/alliances.json';
$schedule_file = __DIR__ . '/../data/rotation-schedule.json';

// Read alliances data
$alliances = read_json_safe($alliances_file);
if ($alliances === null) {
    api_error('Failed to load alliance data', 500);
}

// Read rotation schedule
$schedule_data = read_json_safe($schedule_file);
if ($schedule_data === null) {
    api_error('Failed to load rotation schedule', 500);
}

// Sort alliances by power
usort($alliances, function($a, $b) {
    return ($b['power'] ?? 0) - ($a['power'] ?? 0);
});

// Get top 5 permanent members
$permanent_members = array_slice($alliances, 0, 5);

// Calculate current week number
$epoch = strtotime('2025-05-18 22:00:00 EDT'); // Week 1 start
$now = time();
$weeks_elapsed = floor(($now - $epoch) / (7 * 24 * 60 * 60));
$current_week = $weeks_elapsed + 1;

// Find current week's rotation in schedule
$current_rotation = null;
foreach ($schedule_data['schedule'] ?? [] as $week) {
    if ($week['weekNumber'] == $current_week) {
        $current_rotation = $week;
        break;
    }
}

// Get rotating members by tag
$rotating_members = [];
if ($current_rotation && isset($current_rotation['rotatingMembers'])) {
    foreach ($current_rotation['rotatingMembers'] as $tag) {
        foreach ($alliances as $alliance) {
            if ($alliance['tag'] === $tag) {
                $rotating_members[] = $alliance;
                break;
            }
        }
    }
}

// Build response
$response = [
    'weekNumber' => $current_week,
    'rotationDate' => $current_rotation['startDate'] ?? null,
    'permanentMembers' => $permanent_members,
    'rotatingMembers' => $rotating_members,
    'totalSeats' => 7
];

// Return with caching
api_success_with_etag($response, 60);
