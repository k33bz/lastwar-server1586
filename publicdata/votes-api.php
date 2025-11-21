<?php
/**
 * Public Votes API
 * Version: 1.0.0
 *
 * Public-facing API for council vote results
 * Accessible without authentication
 * Returns sanitized vote data (no emails, sensitive info)
 *
 * Endpoints:
 * - GET ?action=get_recent - Get recent votes (default: last 10)
 * - GET ?action=get_vote&vote_id=xxx - Get specific vote details
 * - GET ?action=get_active - Get currently active votes
 * - GET ?action=get_completed - Get completed votes
 *
 * CORS: Allows cross-origin requests from public website
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit();
}

$action = $_GET['action'] ?? 'get_recent';

try {
    switch ($action) {
        case 'get_recent':
            handleGetRecent();
            break;

        case 'get_vote':
            handleGetVote();
            break;

        case 'get_active':
            handleGetActive();
            break;

        case 'get_completed':
            handleGetCompleted();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get recent votes
 */
function handleGetRecent() {
    $limit = intval($_GET['limit'] ?? 10);
    $limit = min($limit, 50); // Max 50 votes

    $votes = loadVotes();
    $publicVotes = array_map('sanitizeVote', array_slice($votes, 0, $limit));

    echo json_encode([
        'success' => true,
        'votes' => $publicVotes,
        'count' => count($publicVotes)
    ]);
}

/**
 * Get specific vote by ID
 */
function handleGetVote() {
    $voteId = $_GET['vote_id'] ?? '';

    if (empty($voteId)) {
        throw new Exception('Vote ID is required');
    }

    $votes = loadVotes();

    foreach ($votes as $vote) {
        if ($vote['vote_id'] === $voteId) {
            echo json_encode([
                'success' => true,
                'vote' => sanitizeVote($vote)
            ]);
            return;
        }
    }

    throw new Exception('Vote not found');
}

/**
 * Get active votes
 */
function handleGetActive() {
    $votes = loadVotes();
    $activeVotes = array_filter($votes, fn($v) => $v['status'] === 'active');
    $publicVotes = array_map('sanitizeVote', array_values($activeVotes));

    echo json_encode([
        'success' => true,
        'votes' => $publicVotes,
        'count' => count($publicVotes)
    ]);
}

/**
 * Get completed votes
 */
function handleGetCompleted() {
    $limit = intval($_GET['limit'] ?? 20);
    $limit = min($limit, 100); // Max 100 votes

    $votes = loadVotes();
    $completedVotes = array_filter($votes, fn($v) => $v['status'] === 'completed');
    $publicVotes = array_map('sanitizeVote', array_slice(array_values($completedVotes), 0, $limit));

    echo json_encode([
        'success' => true,
        'votes' => $publicVotes,
        'count' => count($publicVotes)
    ]);
}

/**
 * Helper: Load votes from JSON file
 */
function loadVotes() {
    $votesFile = __DIR__ . '/../data/discord-votes.json';

    if (!file_exists($votesFile)) {
        return [];
    }

    $data = json_decode(file_get_contents($votesFile), true);
    return $data['votes'] ?? [];
}

/**
 * Helper: Sanitize vote data for public consumption
 * Removes sensitive information like emails, discord IDs, etc.
 */
function sanitizeVote($vote) {
    // Basic vote info
    $public = [
        'vote_id' => $vote['vote_id'],
        'status' => $vote['status'],
        'created_at' => $vote['created_at'],
        'vote_details' => [
            'title' => $vote['vote_details']['title'],
            'description' => $vote['vote_details']['description'],
            'category' => $vote['vote_details']['category'],
            'options' => $vote['vote_details']['options'] ?? ['yes', 'no', 'abstain']
        ],
        'voting_period' => [
            'start_time' => $vote['voting_period']['start_time'],
            'end_time' => $vote['voting_period']['end_time'],
            'duration_hours' => $vote['voting_period']['duration_hours']
        ],
        'council_info' => [
            'week_number' => $vote['council_snapshot']['week_number'] ?? null,
            'permanent_members' => $vote['council_snapshot']['permanent_members'] ?? [],
            'rotating_members' => $vote['council_snapshot']['rotating_members'] ?? [],
            'total_eligible_voters' => count($vote['council_snapshot']['voter_details'] ?? [])
        ],
        'submission_stats' => [
            'total_submitted' => count($vote['submissions'] ?? []),
            'total_eligible' => count($vote['council_snapshot']['voter_details'] ?? []),
            'submission_rate' => calculateSubmissionRate($vote)
        ]
    ];

    // Add submissions (sanitized - no personal info)
    if (isset($vote['submissions']) && is_array($vote['submissions'])) {
        $public['submissions'] = array_map(function($sub) {
            return [
                'alliance_tag' => $sub['alliance_tag'],
                'vote_choice' => $sub['vote_choice'],
                'submitted_at' => $sub['submitted_at'],
                'submission_method' => $sub['submission_method'] ?? 'unknown',
                'vote_sequence' => $sub['vote_sequence'] ?? 0
            ];
        }, $vote['submissions']);
    } else {
        $public['submissions'] = [];
    }

    // Add results if completed
    if ($vote['status'] === 'completed' && isset($vote['results'])) {
        $public['results'] = [
            'outcome' => $vote['results']['outcome'],
            'total_eligible' => $vote['results']['total_eligible'],
            'total_submitted' => $vote['results']['total_submitted'],
            'yes_count' => $vote['results']['yes_count'],
            'no_count' => $vote['results']['no_count'],
            'abstain_count' => $vote['results']['abstain_count'],
            'absent_count' => $vote['results']['absent_count'],
            'finalized_at' => $vote['results']['finalized_at'],
            'finalization_reason' => $vote['results']['finalization_reason']
        ];

        // Add breakdown by alliance (which alliances voted how)
        $public['results']['votes_by_alliance'] = [];
        if (isset($vote['submissions'])) {
            foreach ($vote['submissions'] as $sub) {
                $public['results']['votes_by_alliance'][] = [
                    'alliance' => $sub['alliance_tag'],
                    'choice' => $sub['vote_choice']
                ];
            }
        }

        // Add absent alliances
        $submitted = array_column($vote['submissions'] ?? [], 'alliance_tag');
        $all = array_column($vote['council_snapshot']['voter_details'] ?? [], 'alliance_tag');
        $absent = array_diff($all, $submitted);
        $public['results']['absent_alliances'] = array_values($absent);
    }

    return $public;
}

/**
 * Helper: Calculate submission rate
 */
function calculateSubmissionRate($vote) {
    $total = count($vote['council_snapshot']['voter_details'] ?? []);
    $submitted = count($vote['submissions'] ?? []);

    if ($total === 0) {
        return 0;
    }

    return round(($submitted / $total) * 100, 2);
}
