<?php
/**
 * Season Status API
 * Returns current season information with UTC timestamps
 *
 * @version 1.0.0
 * @date 2025-11-21
 */

require_once __DIR__ . '/api_helpers.php';

// Handle CORS preflight
handle_preflight();

// Only allow GET requests
validate_method('GET');

// Get requested season (default to current/latest)
$season_number = $_GET['season'] ?? '1';

// Map season numbers to data files
$season_files = [
    '1' => '../data/season1_config.json',
    '2' => '../data/season2_config.json'
];

if (!isset($season_files[$season_number])) {
    api_error('Season not found', 404);
}

$data_file = __DIR__ . '/' . $season_files[$season_number];

// Read season configuration
$season_data = read_json_safe($data_file);

if ($season_data === null) {
    api_error('Failed to load season data', 500);
}

// Calculate current status
$start_datetime = new DateTime($season_data['season_start_date'] . ' ' . $season_data['season_start_time'], new DateTimeZone('UTC'));
$end_datetime = new DateTime($season_data['season_end_date'] . ' ' . $season_data['season_end_time'], new DateTimeZone('UTC'));
$now = new DateTime('now', new DateTimeZone('UTC'));

$total_seconds = $end_datetime->getTimestamp() - $start_datetime->getTimestamp();
$elapsed_seconds = $now->getTimestamp() - $start_datetime->getTimestamp();
$remaining_seconds = $end_datetime->getTimestamp() - $now->getTimestamp();

$progress_percent = min(100, max(0, ($elapsed_seconds / $total_seconds) * 100));
$current_day = max(1, floor($elapsed_seconds / 86400) + 1);

$is_active = $now >= $start_datetime && $now <= $end_datetime;
$is_ended = $now > $end_datetime;
$is_upcoming = $now < $start_datetime;

// Build response
$response = [
    'season_number' => (int)$season_data['season_number'],
    'season_name' => $season_data['season_name'],
    'status' => $is_ended ? 'ended' : ($is_active ? 'active' : 'upcoming'),
    'start_utc' => $start_datetime->format('Y-m-d\TH:i:s\Z'),
    'end_utc' => $end_datetime->format('Y-m-d\TH:i:s\Z'),
    'start_timestamp' => $start_datetime->getTimestamp(),
    'end_timestamp' => $end_datetime->getTimestamp(),
    'current_timestamp' => $now->getTimestamp(),
    'total_days' => (int)$season_data['total_days'],
    'current_day' => $current_day,
    'progress_percent' => round($progress_percent, 2),
    'remaining_seconds' => max(0, $remaining_seconds),
    'elapsed_seconds' => max(0, $elapsed_seconds)
];

// Return with caching (60 second cache for active seasons, longer for ended)
$cache_seconds = $is_ended ? 3600 : 60;
api_success_with_etag($response, $cache_seconds);
