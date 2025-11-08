<?php
/**
 * Season 2 Event Management API
 *
 * Handles Season 2 configuration, calendar generation, and event announcements
 *
 * @version 1.0.0
 * @date 2025-11-07
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

try {
    require_once 'jwt.php';
    require_once 'json_helpers.php';
    require_once 'audit_logger.php';
    require_once 'includes/csrf.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error: ' . $e->getMessage()
    ]);
    exit();
}

$user = require_jwt_session();

// Admin only for configuration, R4+ for viewing
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CSRF Protection for state-changing operations
if (in_array($action, ['update_config', 'announce_event'])) {
    requireCsrfToken();
}

$config_file = __DIR__ . '/../data/season2_config.json';
$templates_file = __DIR__ . '/../data/season2_event_templates.json';
$calendar_file = __DIR__ . '/../data/season2_calendar.json';

try {
    switch ($action) {
        case 'get_config':
            // Anyone can view config
            $config = json_read($config_file);
            echo json_encode(['success' => true, 'config' => $config]);
            break;

        case 'update_config':
            // Admin only
            if ($user->aud !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['season_start_date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'season_start_date required']);
                exit();
            }

            // Validate date format
            $start_date = $input['season_start_date'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid date format (use YYYY-MM-DD)']);
                exit();
            }

            // Load current config
            $config = json_read($config_file);

            // Update config
            $config['season_start_date'] = $start_date;
            $config['season_start_time'] = $input['season_start_time'] ?? $config['season_start_time'];
            $config['server_timezone'] = $input['server_timezone'] ?? $config['server_timezone'];
            $config['faction_war_time'] = $input['faction_war_time'] ?? $config['faction_war_time'];
            $config['faction_war_duration_hours'] = $input['faction_war_duration_hours'] ?? $config['faction_war_duration_hours'];
            $config['daily_reset_time'] = $input['daily_reset_time'] ?? $config['daily_reset_time'];
            $config['valor_badge_day'] = $input['valor_badge_day'] ?? $config['valor_badge_day'];
            $config['alliance_duel_enabled'] = $input['alliance_duel_enabled'] ?? $config['alliance_duel_enabled'];
            $config['alliance_duel_weeks'] = $input['alliance_duel_weeks'] ?? $config['alliance_duel_weeks'];
            $config['alliance_duel_start_day'] = $input['alliance_duel_start_day'] ?? $config['alliance_duel_start_day'];
            $config['alliance_duel_duration_days'] = $input['alliance_duel_duration_days'] ?? $config['alliance_duel_duration_days'];
            $config['config_updated_at'] = gmdate('Y-m-d H:i:s');
            $config['config_updated_by'] = $user->sub;

            json_write($config_file, $config);

            // Regenerate calendar
            $calendar = generate_season_calendar($config, $templates_file);
            json_write($calendar_file, $calendar);

            log_audit_event('season2_config_updated', $user->sub, [
                'season_start_date' => $start_date,
                'events_generated' => count($calendar['events'])
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Season 2 configured successfully',
                'config' => $config,
                'events_generated' => count($calendar['events'])
            ]);
            break;

        case 'get_calendar':
            // Anyone can view calendar
            $calendar = json_read($calendar_file);

            // Add current week and day calculations
            if ($calendar['season_start_date']) {
                $calendar['current_week'] = calculate_current_week($calendar['season_start_date']);
                $calendar['current_day'] = calculate_current_day($calendar['season_start_date']);
                $calendar['days_elapsed'] = calculate_days_elapsed($calendar['season_start_date']);
            }

            echo json_encode(['success' => true, 'calendar' => $calendar]);
            break;

        case 'get_upcoming_events':
            // Get next N upcoming events
            $limit = (int)($_GET['limit'] ?? 10);
            $calendar = json_read($calendar_file);

            if (empty($calendar['events'])) {
                echo json_encode(['success' => true, 'events' => []]);
                exit();
            }

            $now = time();
            $upcoming = array_filter($calendar['events'], function($event) use ($now) {
                return strtotime($event['datetime']) >= $now;
            });

            // Sort by datetime
            usort($upcoming, function($a, $b) {
                return strtotime($a['datetime']) - strtotime($b['datetime']);
            });

            $upcoming = array_slice($upcoming, 0, $limit);

            echo json_encode(['success' => true, 'events' => $upcoming]);
            break;

        case 'announce_event':
            // R4+ can announce
            if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'R4 or higher access required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $event_id = $input['event_id'] ?? '';
            $channel_ids = $input['channel_ids'] ?? [];

            if (empty($event_id) || empty($channel_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'event_id and channel_ids required']);
                exit();
            }

            // Get event from calendar
            $calendar = json_read($calendar_file);
            $event = null;
            foreach ($calendar['events'] as $e) {
                if ($e['id'] === $event_id) {
                    $event = $e;
                    break;
                }
            }

            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Event not found']);
                exit();
            }

            // Load template
            $templates = json_read($templates_file);
            $template_key = $event['template_key'] ?? null;
            $template = $templates['template_definitions'][$template_key] ?? null;

            if (!$template) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Template not found for event']);
                exit();
            }

            // Send announcement via Discord API
            require_once 'discord_webhook.php';

            $message_content = replace_season2_variables($template['content'], $calendar);
            $embed_title = replace_season2_variables($template['title'], $calendar);

            if ($template['use_embed']) {
                $message = create_embed_announcement(
                    $embed_title,
                    $message_content,
                    [
                        'color' => hexdec($template['embed_color']),
                        'footer' => "{$user->sub} • Season 2 Event System"
                    ]
                );
            } else {
                $message = create_simple_announcement($message_content, [
                    'footer' => "{$user->sub} • Season 2 Event System"
                ]);
            }

            $results = send_discord_message_multi($channel_ids, $message);

            $success_count = count(array_filter($results, function($r) { return $r['success']; }));

            log_audit_event('season2_event_announced', $user->sub, [
                'event_id' => $event_id,
                'event_name' => $event['name'],
                'channels' => count($channel_ids),
                'success_count' => $success_count
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Announcement sent to {$success_count}/" . count($channel_ids) . " channels",
                'results' => $results
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log('Season 2 API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Generate complete season calendar from config and templates
 */
function generate_season_calendar($config, $templates_file) {
    $templates = json_read($templates_file);
    $events = [];

    $season_start = strtotime($config['season_start_date'] . ' ' . $config['season_start_time']);

    foreach ($templates['event_templates'] as $template) {
        if ($template['type'] === 'week_phase' || $template['type'] === 'season_milestone' || $template['type'] === 'cold_wave' || $template['type'] === 'rare_soil') {
            // Calculate absolute date from week + day_offset
            $week = $template['week'] ?? 1;
            $day_offset = $template['day_offset'] ?? 0;
            $total_days = (($week - 1) * 7) + $day_offset;

            $event_timestamp = strtotime("+{$total_days} days", $season_start);
            $event_date = date('Y-m-d', $event_timestamp);
            $event_time = $template['time'] ?? '00:00';

            $events[] = [
                'id' => $template['id'],
                'name' => $template['name'],
                'type' => $template['type'],
                'datetime' => $event_date . ' ' . $event_time,
                'week' => $week,
                'day_offset' => $day_offset,
                'importance' => $template['importance'],
                'template_key' => $template['template_key'],
                'description' => $template['description'],
                'auto_announce' => $template['auto_announce'] ?? false
            ];
        } elseif ($template['type'] === 'weekly_recurring') {
            // Generate recurring events for all 7 weeks
            $day_of_week = $template['day_of_week'];
            $event_time = $template['time'] ?? $config['faction_war_time'] ?? '14:00';

            for ($week = 1; $week <= 7; $week++) {
                // Find the specific day of week in this week
                $week_start = strtotime("+" . (($week - 1) * 7) . " days", $season_start);
                $target_day = strtotime("next {$day_of_week}", $week_start - 86400); // -1 day to include same day

                $event_date = date('Y-m-d', $target_day);

                $events[] = [
                    'id' => $template['id'] . '_week_' . $week,
                    'name' => $template['name'] . ' (Week ' . $week . ')',
                    'type' => $template['type'],
                    'datetime' => $event_date . ' ' . $event_time,
                    'week' => $week,
                    'importance' => $template['importance'],
                    'template_key' => $template['template_key'],
                    'description' => $template['description'],
                    'auto_announce' => $template['auto_announce'] ?? false,
                    'reminder_hours' => $template['reminder_hours'] ?? []
                ];
            }
        } elseif ($template['type'] === 'alliance_duel') {
            // Generate Alliance Duel VS events for configured weeks
            if ($config['alliance_duel_enabled'] ?? false) {
                $duel_weeks = $config['alliance_duel_weeks'] ?? [];
                $start_day = $config['alliance_duel_start_day'] ?? 'monday';

                foreach ($duel_weeks as $duel_week) {
                    // Find the start of this week
                    $week_start = strtotime("+" . (($duel_week - 1) * 7) . " days", $season_start);

                    // Find the configured start day (e.g., Monday) of this week
                    // Using strtotime "next X" from the day before the week to include the week's first day
                    $duel_start = strtotime("next {$start_day}", $week_start - 86400);

                    // Add day_offset from template
                    $day_offset = $template['day_offset'] ?? 0;
                    $event_timestamp = strtotime("+" . $day_offset . " days", $duel_start);
                    $event_date = date('Y-m-d', $event_timestamp);
                    $event_time = $template['time'] ?? '08:00';

                    $events[] = [
                        'id' => $template['id'] . '_week_' . $duel_week,
                        'name' => $template['name'] . ' (Week ' . $duel_week . ')',
                        'type' => $template['type'],
                        'datetime' => $event_date . ' ' . $event_time,
                        'week' => $duel_week,
                        'day_offset' => $day_offset,
                        'importance' => $template['importance'],
                        'template_key' => $template['template_key'],
                        'description' => $template['description'],
                        'auto_announce' => $template['auto_announce'] ?? false
                    ];
                }
            }
        }
    }

    // Sort by datetime
    usort($events, function($a, $b) {
        return strtotime($a['datetime']) - strtotime($b['datetime']);
    });

    $season_end = strtotime("+49 days", $season_start); // 7 weeks = 49 days

    return [
        'calendar_generated_at' => gmdate('Y-m-d H:i:s'),
        'season_start_date' => date('Y-m-d', $season_start),
        'season_end_date' => date('Y-m-d', $season_end),
        'events' => $events
    ];
}

/**
 * Calculate current week (1-7) based on season start date
 */
function calculate_current_week($season_start_date) {
    $start = strtotime($season_start_date);
    $now = time();
    $days_elapsed = floor(($now - $start) / 86400);
    $week = floor($days_elapsed / 7) + 1;
    return min(max($week, 1), 7); // Clamp to 1-7
}

/**
 * Calculate current day within current week (1-7)
 */
function calculate_current_day($season_start_date) {
    $start = strtotime($season_start_date);
    $now = time();
    $days_elapsed = floor(($now - $start) / 86400);
    return ($days_elapsed % 7) + 1;
}

/**
 * Calculate total days elapsed since season start
 */
function calculate_days_elapsed($season_start_date) {
    $start = strtotime($season_start_date);
    $now = time();
    return max(0, floor(($now - $start) / 86400));
}

/**
 * Replace Season 2 variables in template content
 */
function replace_season2_variables($content, $calendar) {
    // Load alliance data for variables
    require_once __DIR__ . '/json_helpers.php';

    $alliances_file = __DIR__ . '/../data/alliances.json';
    $alliances = file_exists($alliances_file) ? json_read($alliances_file) : [];

    $variables = [
        '{current_week}' => 'Week ' . ($calendar['current_week'] ?? 1),
        '{current_day}' => $calendar['current_day'] ?? 1,
        '{days_elapsed}' => $calendar['days_elapsed'] ?? 0,
        '{days_until_next_week}' => 7 - (($calendar['current_day'] ?? 1) - 1),
        '{days_until_season_end}' => max(0, 49 - ($calendar['days_elapsed'] ?? 0)),
        '{season_start_date}' => $calendar['season_start_date'] ?? 'TBD',
        '{season_end_date}' => $calendar['season_end_date'] ?? 'TBD',
        '{alliance_power}' => number_format(array_sum(array_column($alliances, 'power'))),
        '{furnace_level}' => '10', // TODO: Track this dynamically
        '{coal_reserves}' => '2.4M', // TODO: Track this dynamically
        '{rare_soil_count}' => '3', // TODO: Track this dynamically
        '{alliance_rank}' => '#1', // TODO: Track this dynamically
        '{cities_controlled}' => '5', // TODO: Track this dynamically
        '{faction_wars_won}' => '4/6', // TODO: Track this dynamically
        '{avg_profession_level}' => '42', // TODO: Track this dynamically
        '{target_profession_level}' => '50',
        '{power_change_percent}' => '+15', // TODO: Calculate dynamically
        '{reward_tier}' => 'Legendary', // TODO: Determine dynamically
    ];

    return str_replace(array_keys($variables), array_values($variables), $content);
}
?>
