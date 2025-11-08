<?php
/**
 * Council Rotation Schedule API
 * Version: 1.0.0
 *
 * Regenerates the council rotation schedule for future weeks while preserving past history.
 * Based on scripts/update-rotation-schedule.py algorithm.
 *
 * Algorithm:
 * 1. Load current schedule and alliance data
 * 2. Determine current week number and next rotation date
 * 3. Count recent rotations (last 10 weeks) for fairness
 * 4. Generate next 52 weeks using weighted fair selection
 * 5. Prevent back-to-back rotations (minimum 2 weeks gap)
 * 6. Preserve all past weeks unchanged
 * 7. Update rotation-schedule.json file
 *
 * Access: Admin and President roles only
 */

require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';
require_once 'includes/csrf.php';

header('Content-Type: application/json');

$user = require_jwt_session();

// Require admin or president role
if (!has_role($user, ['admin', 'president'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin or President role required.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Configuration (matching Python script)
define('WEEK_1_EPOCH', '2025-05-19T02:00:00+00:00');  // Monday 02:00 UTC = Sunday 10PM EDT
define('ROTATION_DAY', 1);  // Monday (PHP: 1=Monday, 7=Sunday)
define('ROTATION_HOUR', 2);  // 2 AM UTC
define('WEEKS_TO_GENERATE', 52);
define('LOOKBACK_WEEKS', 10);
define('MIN_WEEKS_BETWEEN_ROTATIONS', 2);  // Minimum weeks before same alliance can rotate again

$alliances_file = __DIR__ . '/../data/alliances.json';
$schedule_file = __DIR__ . '/../data/rotation-schedule.json';

/**
 * Get current week number since Week 1 epoch
 */
function get_current_week_number() {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $epoch = new DateTime(WEEK_1_EPOCH);

    $interval = $now->diff($epoch);
    $total_seconds = ($interval->days * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    $week_number = floor($total_seconds / (7 * 24 * 60 * 60)) + 1;

    return max(1, (int)$week_number);
}

/**
 * Get next rotation date (Monday 02:00 UTC)
 */
function get_next_rotation_date() {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $day_of_week = (int)$now->format('N');  // 1=Monday, 7=Sunday

    $days_until_monday = (ROTATION_DAY - $day_of_week + 7) % 7;

    // If today is Monday but past rotation time, go to next Monday
    if ($days_until_monday == 0 && (int)$now->format('H') >= ROTATION_HOUR) {
        $days_until_monday = 7;
    }

    $next_monday = clone $now;
    $next_monday->modify("+{$days_until_monday} days");
    $next_monday->setTime(ROTATION_HOUR, 0, 0);

    return $next_monday;
}

/**
 * Load alliances sorted by power with ranks assigned
 */
function load_alliances($file) {
    $alliances = json_read($file);

    // Sort by power (descending)
    usort($alliances, function($a, $b) {
        return ($b['power'] ?? 0) - ($a['power'] ?? 0);
    });

    // Assign ranks
    foreach ($alliances as $i => &$alliance) {
        $alliance['rank'] = $i + 1;
    }

    return $alliances;
}

/**
 * Load existing rotation schedule
 */
function load_schedule($file) {
    if (!file_exists($file)) {
        return [
            'generatedAt' => null,
            'epoch' => WEEK_1_EPOCH,
            'currentWeekNumber' => 0,
            'schedule' => []
        ];
    }

    return json_read($file);
}

/**
 * Get rotating pool (ranks 6-15)
 */
function get_rotating_pool($alliances) {
    return array_filter($alliances, function($a) {
        return $a['rank'] >= 6 && $a['rank'] <= 15;
    });
}

/**
 * Count recent rotations in last N weeks
 */
function count_recent_rotations($schedule, $current_week, $lookback_weeks) {
    $rotation_counts = [];
    $start_week = max(1, $current_week - $lookback_weeks);

    foreach ($schedule as $week_data) {
        $week_num = $week_data['weekNumber'];
        if ($week_num >= $start_week && $week_num < $current_week) {
            foreach ($week_data['rotatingMembers'] as $tag) {
                if (!isset($rotation_counts[$tag])) {
                    $rotation_counts[$tag] = 0;
                }
                $rotation_counts[$tag]++;
            }
        }
    }

    return $rotation_counts;
}

/**
 * Select fair pair of alliances to rotate
 */
function select_fair_pair($available_alliances, $rotation_counts, $used_this_cycle, $recent_rotation_history = [], $min_weeks_gap = 2) {
    $max_count = $rotation_counts ? max($rotation_counts) : 0;

    $weighted_alliances = [];
    foreach ($available_alliances as $alliance) {
        $tag = $alliance['tag'];
        $count = $rotation_counts[$tag] ?? 0;
        $cycle_bonus = in_array($tag, $used_this_cycle) ? 0 : 2;

        // Check recent rotation history for penalty
        $recent_penalty = 0;
        $weeks_to_check = min(count($recent_rotation_history), $min_weeks_gap - 1);
        for ($i = 0; $i < $weeks_to_check; $i++) {
            if (in_array($tag, $recent_rotation_history[$i])) {
                // Stronger penalty for more recent rotations
                $recent_penalty -= (10 - $i * 2);
            }
        }

        $weight = ($max_count - $count + 1) + $cycle_bonus + $recent_penalty;
        $weighted_alliances[] = [
            'tag' => $tag,
            'weight' => $weight,
            'rank' => $alliance['rank']
        ];
    }

    // Sort by weight (descending), then rank (ascending)
    usort($weighted_alliances, function($a, $b) {
        if ($b['weight'] != $a['weight']) {
            return $b['weight'] - $a['weight'];
        }
        return $a['rank'] - $b['rank'];
    });

    // Select first and second
    $first_tag = $weighted_alliances[0]['tag'];
    $second_tag = null;

    foreach (array_slice($weighted_alliances, 1) as $item) {
        if ($item['tag'] != $first_tag) {
            $second_tag = $item['tag'];
            break;
        }
    }

    // Fallback
    if ($second_tag === null) {
        $second_tag = $weighted_alliances[1]['tag'] ?? $first_tag;
    }

    return [$first_tag, $second_tag];
}

/**
 * Generate future schedule
 */
function generate_future_schedule($current_week, $rotating_pool, $recent_counts, $weeks_to_generate, $existing_schedule = [], $min_weeks_gap = 2) {
    $schedule = [];
    $rotation_counts = $recent_counts;

    // Track cycle usage
    $cycle_length = (int)(count($rotating_pool) / 2);
    $used_this_cycle = [];

    // Track recent rotation history
    $recent_rotation_history = [];

    // Build initial history from existing schedule
    $weeks_to_load = $min_weeks_gap - 1;
    for ($i = $weeks_to_load; $i > 0; $i--) {
        $week_num = $current_week - $i;
        foreach ($existing_schedule as $week_data) {
            if ($week_data['weekNumber'] == $week_num) {
                $recent_rotation_history[] = $week_data['rotatingMembers'];
                break;
            }
        }
    }

    // Calculate start date
    $epoch = new DateTime(WEEK_1_EPOCH);
    $week_offset = $current_week - 1;
    $first_week_start = clone $epoch;
    $first_week_start->modify("+{$week_offset} weeks");

    for ($i = 0; $i < $weeks_to_generate; $i++) {
        $week_number = $current_week + $i;
        $week_start = clone $first_week_start;
        $week_start->modify("+{$i} weeks");

        // Reset cycle tracking
        if ($i > 0 && $i % $cycle_length == 0) {
            $used_this_cycle = [];
        }

        // Select fair pair
        list($first_tag, $second_tag) = select_fair_pair(
            $rotating_pool,
            $rotation_counts,
            $used_this_cycle,
            $recent_rotation_history,
            $min_weeks_gap
        );

        // Update tracking
        $rotation_counts[$first_tag] = ($rotation_counts[$first_tag] ?? 0) + 1;
        $rotation_counts[$second_tag] = ($rotation_counts[$second_tag] ?? 0) + 1;
        $used_this_cycle[] = $first_tag;
        $used_this_cycle[] = $second_tag;

        // Update recent history (sliding window)
        array_unshift($recent_rotation_history, [$first_tag, $second_tag]);
        if (count($recent_rotation_history) > $min_weeks_gap - 1) {
            array_pop($recent_rotation_history);
        }

        // Create week entry
        $schedule[] = [
            'weekNumber' => $week_number,
            'startDate' => $week_start->format('Y-m-d\TH:i:s\Z'),
            'rotatingMembers' => [$first_tag, $second_tag]
        ];
    }

    return $schedule;
}

// Handle actions
switch ($action) {
    case 'get_status':
        // Get current status info
        try {
            $alliances = load_alliances($alliances_file);
            $existing_schedule = load_schedule($schedule_file);
            $current_week = get_current_week_number();
            $next_rotation = get_next_rotation_date();
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $next_week = ($now < $next_rotation) ? $current_week : $current_week + 1;

            $rotating_pool = get_rotating_pool($alliances);
            $pool_tags = array_map(function($a) { return $a['tag']; }, $rotating_pool);

            echo json_encode([
                'success' => true,
                'current_week' => $current_week,
                'next_rotation_week' => $next_week,
                'next_rotation_date' => $next_rotation->format('Y-m-d H:i:s \U\T\C'),
                'total_alliances' => count($alliances),
                'rotating_pool_count' => count($rotating_pool),
                'rotating_pool_tags' => $pool_tags,
                'schedule_weeks_count' => count($existing_schedule['schedule']),
                'last_generated' => $existing_schedule['metadata']['lastGeneratedDate'] ?? null
            ]);

            log_audit_event('council_rotation_status_viewed', $user->sub);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'regenerate':
        // Regenerate schedule - requires CSRF token
        requireCsrfToken();

        try {
            $alliances = load_alliances($alliances_file);
            $existing_schedule = load_schedule($schedule_file);
            $current_week = get_current_week_number();
            $next_rotation = get_next_rotation_date();
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $next_week = ($now < $next_rotation) ? $current_week : $current_week + 1;

            // Get rotating pool
            $rotating_pool = get_rotating_pool($alliances);
            $pool_tags = array_map(function($a) { return $a['tag']; }, $rotating_pool);

            // Count recent rotations
            $recent_counts = count_recent_rotations($existing_schedule['schedule'], $current_week, LOOKBACK_WEEKS);

            // Generate new schedule
            $new_schedule = generate_future_schedule(
                $next_week,
                $rotating_pool,
                $recent_counts,
                WEEKS_TO_GENERATE,
                $existing_schedule['schedule'],
                MIN_WEEKS_BETWEEN_ROTATIONS
            );

            // Preserve past weeks
            $past_weeks = array_filter($existing_schedule['schedule'], function($w) use ($next_week) {
                return $w['weekNumber'] < $next_week;
            });
            $past_weeks = array_values($past_weeks);

            // Combine past + new
            $full_schedule = array_merge($past_weeks, $new_schedule);

            // Track top 3 and top 15 for change detection
            $top3_tags = array_map(function($a) { return $a['tag']; }, array_slice($alliances, 0, 3));
            $top15_tags = array_map(function($a) { return $a['tag']; }, array_slice($alliances, 0, 15));

            // Create output
            $output = [
                'generatedAt' => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
                'epoch' => WEEK_1_EPOCH,
                'currentWeekNumber' => $current_week,
                'metadata' => [
                    'top3Snapshot' => $top3_tags,
                    'top15Snapshot' => $top15_tags,
                    'lastGeneratedDate' => (new DateTime())->format('Y-m-d'),
                    'regeneratedBy' => $user->sub,
                    'regeneratedByRole' => $user->aud
                ],
                'schedule' => $full_schedule
            ];

            // Write to file
            json_write($schedule_file, $output);

            // Calculate fairness stats
            $future_counts = [];
            foreach ($new_schedule as $week_data) {
                foreach ($week_data['rotatingMembers'] as $tag) {
                    $future_counts[$tag] = ($future_counts[$tag] ?? 0) + 1;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Council rotation schedule regenerated successfully',
                'stats' => [
                    'past_weeks_preserved' => count($past_weeks),
                    'new_weeks_generated' => count($new_schedule),
                    'total_weeks' => count($full_schedule),
                    'next_rotation_week' => $next_week,
                    'future_rotation_counts' => $future_counts
                ]
            ]);

            log_audit_event('council_rotation_regenerated', $user->sub, [
                'next_week' => $next_week,
                'weeks_generated' => count($new_schedule),
                'weeks_preserved' => count($past_weeks)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
