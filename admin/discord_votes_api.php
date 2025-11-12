<?php
/**
 * Discord Votes API
 * Version: 1.0.1
 *
 * Unified API for managing Discord bot votes and vote requests
 * Used by both admin site and Discord bot for consistency
 *
 * Changelog:
 * - 1.0.1: Fixed authentication to use require_jwt_session_api() for proper JSON error responses
 *
 * Endpoints:
 * - POST ?action=create_request - Submit vote request (R5, R4, APE, President, Admin)
 * - POST ?action=create_vote - Create vote directly (President, Admin only)
 * - POST ?action=approve_request - Approve pending request (President, Admin only)
 * - POST ?action=reject_request - Reject pending request (President, Admin only)
 * - GET ?action=get_requests - List all vote requests
 * - GET ?action=get_pending_requests - List pending requests only
 * - GET ?action=get_votes - List all Discord votes
 * - GET ?action=get_vote - Get specific vote by ID
 * - GET ?action=get_active_votes - List active votes only
 *
 * Access Control:
 * - R5/R4/APE: Can submit vote requests
 * - President/Admin: Can create votes, approve/reject requests, view all
 */

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'json_helpers.php';

// Require authentication
$user = require_jwt_session_api();

// CSRF protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
}

// Set JSON response headers
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_request':
            handleCreateRequest($user);
            break;

        case 'create_vote':
            handleCreateVote($user);
            break;

        case 'approve_request':
            handleApproveRequest($user);
            break;

        case 'reject_request':
            handleRejectRequest($user);
            break;

        case 'get_requests':
            handleGetRequests($user);
            break;

        case 'get_pending_requests':
            handleGetPendingRequests($user);
            break;

        case 'get_votes':
            handleGetVotes($user);
            break;

        case 'get_vote':
            handleGetVote($user);
            break;

        case 'get_active_votes':
            handleGetActiveVotes($user);
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
    exit();
}

/**
 * Handle vote request creation
 * Accessible by R5, R4, APE, President, Admin
 */
function handleCreateRequest($user) {
    // Check permissions - R5, R4, APE, President, or Admin
    if (!has_role($user, ['admin', 'president', 'r5', 'r4', 'ape'])) {
        throw new Exception('Access denied. Only alliance members can submit vote requests.');
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);

    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $category = $input['category'] ?? 'other';

    // Validate
    if (empty($title) || strlen($title) > 100) {
        throw new Exception('Title is required and must be 100 characters or less');
    }

    if (empty($description)) {
        throw new Exception('Description is required');
    }

    $validCategories = ['rule_change', 'alliance_action', 'server_event', 'other'];
    if (!in_array($category, $validCategories)) {
        throw new Exception('Invalid category');
    }

    // Generate request ID
    $requestId = 'votereq_' . date('Ymd') . '_' . substr(md5(uniqid('', true)), 0, 6);

    // Create request object
    $request = [
        'request_id' => $requestId,
        'status' => 'pending',
        'created_at' => gmdate('Y-m-d\TH:i:s.000\Z'),
        'requested_by' => [
            'user_email' => $user->sub,
            'username' => $user->name ?? $user->sub,
            'roles' => $user->aud,
            'alliance' => $user->alliance ?? null,
            'discord_id' => $user->discord_id ?? null
        ],
        'vote_details' => [
            'title' => $title,
            'description' => $description,
            'category' => $category
        ],
        'president_response' => null,
        'responded_at' => null,
        'created_vote_id' => null
    ];

    // Load requests file
    $requestsFile = __DIR__ . '/../data/discord-vote-requests.json';
    $data = json_read($requestsFile);

    if (!isset($data['requests'])) {
        $data = ['requests' => []];
    }

    // Add new request to beginning of array
    array_unshift($data['requests'], $request);

    // Save
    json_write($requestsFile, $data);

    // Log audit event
    log_audit_event('discord_vote_request_created', $user->sub, [
        'request_id' => $requestId,
        'title' => $title,
        'category' => $category
    ]);

    echo json_encode([
        'success' => true,
        'request' => $request
    ]);
}

/**
 * Handle direct vote creation
 * President and Admin only
 */
function handleCreateVote($user) {
    // Check permissions - President or Admin only
    if (!has_role($user, ['admin', 'president'])) {
        throw new Exception('Access denied. Only president and admins can create votes directly.');
    }

    // Get vote data
    $input = json_decode(file_get_contents('php://input'), true);

    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $category = $input['category'] ?? 'other';

    // Validate
    if (empty($title) || strlen($title) > 100) {
        throw new Exception('Title is required and must be 100 characters or less');
    }

    if (empty($description)) {
        throw new Exception('Description is required');
    }

    $validCategories = ['rule_change', 'alliance_action', 'server_event', 'other'];
    if (!in_array($category, $validCategories)) {
        throw new Exception('Invalid category');
    }

    // Generate vote ID
    $voteId = 'vote_' . date('Ymd') . '_' . substr(md5(uniqid('', true)), 0, 6);

    // Get current council members
    $councilSnapshot = getCurrentCouncilMembers();

    // Create vote object (matching bot structure)
    $now = gmdate('Y-m-d\TH:i:s.000\Z');
    $durationHours = 24; // Default 24 hours
    $endTime = gmdate('Y-m-d\TH:i:s.000\Z', strtotime("+{$durationHours} hours"));

    $vote = [
        'vote_id' => $voteId,
        'status' => 'active',
        'created_at' => $now,
        'created_by' => [
            'user_email' => $user->sub,
            'username' => $user->name ?? $user->sub,
            'role' => has_role($user, 'president') ? 'president' : 'admin',
            'source' => 'web'
        ],
        'vote_details' => [
            'title' => $title,
            'description' => $description,
            'options' => ['yes', 'no', 'abstain'],
            'category' => $category
        ],
        'voting_period' => [
            'start_time' => $now,
            'end_time' => $endTime,
            'duration_hours' => $durationHours,
            'early_close_enabled' => false
        ],
        'council_snapshot' => $councilSnapshot,
        'submissions' => [],
        'results' => null,
        'discord_metadata' => [
            'vote_channel_id' => null,
            'vote_message_id' => null,
            'result_message_id' => null,
            'notification_dm_ids' => []
        ],
        'integrity' => [
            'vote_hash' => null,
            'hash_chain' => [
                [
                    'event' => 'vote_created',
                    'timestamp' => $now,
                    'data' => [
                        'vote_id' => $voteId,
                        'created_by' => $user->name ?? $user->sub,
                        'source' => 'web'
                    ]
                ]
            ]
        ]
    ];

    // Create vote hash (simplified version)
    $vote['integrity']['vote_hash'] = hash('sha256', json_encode($vote));

    // Load votes file
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    if (!isset($data['votes'])) {
        $data = ['votes' => []];
    }

    // Add new vote
    array_unshift($data['votes'], $vote);

    // Save
    json_write($votesFile, $data);

    // Log audit event
    log_audit_event('discord_vote_created', $user->sub, [
        'vote_id' => $voteId,
        'title' => $title,
        'category' => $category,
        'source' => 'web'
    ]);

    echo json_encode([
        'success' => true,
        'vote' => $vote,
        'message' => 'Vote created successfully. Discord bot will process and publish it.'
    ]);
}

/**
 * Handle vote request approval
 * President and Admin only
 */
function handleApproveRequest($user) {
    // Check permissions
    if (!has_role($user, ['admin', 'president'])) {
        throw new Exception('Access denied. Only president and admins can approve requests.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = $input['request_id'] ?? '';

    if (empty($requestId)) {
        throw new Exception('Request ID is required');
    }

    // Load requests
    $requestsFile = __DIR__ . '/../data/discord-vote-requests.json';
    $data = json_read($requestsFile);

    // Find request
    $requestIndex = null;
    foreach ($data['requests'] as $idx => $req) {
        if ($req['request_id'] === $requestId) {
            $requestIndex = $idx;
            break;
        }
    }

    if ($requestIndex === null) {
        throw new Exception('Request not found');
    }

    if ($data['requests'][$requestIndex]['status'] !== 'pending') {
        throw new Exception('Request has already been processed');
    }

    // Update request status
    $data['requests'][$requestIndex]['status'] = 'approved';
    $data['requests'][$requestIndex]['president_response'] = [
        'approved' => true,
        'approver_email' => $user->sub,
        'approver_name' => $user->name ?? $user->sub,
        'timestamp' => gmdate('Y-m-d\TH:i:s.000\Z')
    ];
    $data['requests'][$requestIndex]['responded_at'] = gmdate('Y-m-d\TH:i:s.000\Z');

    json_write($requestsFile, $data);

    // Now create the vote automatically
    $request = $data['requests'][$requestIndex];
    $voteId = createVoteFromRequest($request, $user);

    // Link vote to request
    $data = json_read($requestsFile); // Reload
    foreach ($data['requests'] as $idx => $req) {
        if ($req['request_id'] === $requestId) {
            $data['requests'][$idx]['created_vote_id'] = $voteId;
            break;
        }
    }
    json_write($requestsFile, $data);

    // Log audit event
    log_audit_event('discord_vote_request_approved', $user->sub, [
        'request_id' => $requestId,
        'created_vote_id' => $voteId
    ]);

    echo json_encode([
        'success' => true,
        'request' => $data['requests'][$requestIndex],
        'vote_id' => $voteId,
        'message' => 'Request approved and vote created successfully'
    ]);
}

/**
 * Handle vote request rejection
 * President and Admin only
 */
function handleRejectRequest($user) {
    // Check permissions
    if (!has_role($user, ['admin', 'president'])) {
        throw new Exception('Access denied. Only president and admins can reject requests.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = $input['request_id'] ?? '';
    $reason = $input['reason'] ?? 'No reason provided';

    if (empty($requestId)) {
        throw new Exception('Request ID is required');
    }

    // Load requests
    $requestsFile = __DIR__ . '/../data/discord-vote-requests.json';
    $data = json_read($requestsFile);

    // Find request
    $requestIndex = null;
    foreach ($data['requests'] as $idx => $req) {
        if ($req['request_id'] === $requestId) {
            $requestIndex = $idx;
            break;
        }
    }

    if ($requestIndex === null) {
        throw new Exception('Request not found');
    }

    if ($data['requests'][$requestIndex]['status'] !== 'pending') {
        throw new Exception('Request has already been processed');
    }

    // Update request status
    $data['requests'][$requestIndex]['status'] = 'rejected';
    $data['requests'][$requestIndex]['president_response'] = [
        'approved' => false,
        'rejector_email' => $user->sub,
        'rejector_name' => $user->name ?? $user->sub,
        'reason' => $reason,
        'timestamp' => gmdate('Y-m-d\TH:i:s.000\Z')
    ];
    $data['requests'][$requestIndex]['responded_at'] = gmdate('Y-m-d\TH:i:s.000\Z');

    json_write($requestsFile, $data);

    // Log audit event
    log_audit_event('discord_vote_request_rejected', $user->sub, [
        'request_id' => $requestId,
        'reason' => $reason
    ]);

    echo json_encode([
        'success' => true,
        'request' => $data['requests'][$requestIndex],
        'message' => 'Request rejected successfully'
    ]);
}

/**
 * Get all vote requests
 */
function handleGetRequests($user) {
    // Check permissions
    if (!has_role($user, ['admin', 'president', 'r5', 'r4', 'ape'])) {
        throw new Exception('Access denied');
    }

    $requestsFile = __DIR__ . '/../data/discord-vote-requests.json';
    $data = json_read($requestsFile);

    // R5/R4/APE can only see their own requests
    // President/Admin can see all
    $requests = $data['requests'] ?? [];

    if (!has_role($user, ['admin', 'president'])) {
        $requests = array_filter($requests, function($req) use ($user) {
            return $req['requested_by']['user_email'] === $user->sub;
        });
        $requests = array_values($requests); // Re-index
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
}

/**
 * Get pending vote requests only
 */
function handleGetPendingRequests($user) {
    // Check permissions - President/Admin only
    if (!has_role($user, ['admin', 'president'])) {
        throw new Exception('Access denied. Only president and admins can view pending requests.');
    }

    $requestsFile = __DIR__ . '/../data/discord-vote-requests.json';
    $data = json_read($requestsFile);

    $pendingRequests = array_filter($data['requests'] ?? [], function($req) {
        return $req['status'] === 'pending';
    });

    echo json_encode([
        'success' => true,
        'requests' => array_values($pendingRequests)
    ]);
}

/**
 * Get all Discord votes
 */
function handleGetVotes($user) {
    // Check permissions
    if (!has_role($user, ['admin', 'president', 'r5', 'r4', 'ape'])) {
        throw new Exception('Access denied');
    }

    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    echo json_encode([
        'success' => true,
        'votes' => $data['votes'] ?? []
    ]);
}

/**
 * Get specific vote by ID
 */
function handleGetVote($user) {
    // Check permissions
    if (!has_role($user, ['admin', 'president', 'r5', 'r4', 'ape'])) {
        throw new Exception('Access denied');
    }

    $voteId = $_GET['vote_id'] ?? '';

    if (empty($voteId)) {
        throw new Exception('Vote ID is required');
    }

    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    $vote = null;
    foreach ($data['votes'] ?? [] as $v) {
        if ($v['vote_id'] === $voteId) {
            $vote = $v;
            break;
        }
    }

    if (!$vote) {
        throw new Exception('Vote not found');
    }

    echo json_encode([
        'success' => true,
        'vote' => $vote
    ]);
}

/**
 * Get active votes only
 */
function handleGetActiveVotes($user) {
    // Check permissions
    if (!has_role($user, ['admin', 'president', 'r5', 'r4', 'ape'])) {
        throw new Exception('Access denied');
    }

    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    $activeVotes = array_filter($data['votes'] ?? [], function($v) {
        return $v['status'] === 'active';
    });

    echo json_encode([
        'success' => true,
        'votes' => array_values($activeVotes)
    ]);
}

/**
 * Helper: Get current council members
 */
function getCurrentCouncilMembers() {
    $rotationFile = __DIR__ . '/../data/rotation-schedule.json';
    $alliancesFile = __DIR__ . '/../data/alliances.json';

    $rotation = json_read($rotationFile);
    $alliances = json_read($alliancesFile);

    $currentWeek = $rotation['currentWeekNumber'];
    $schedule = null;

    foreach ($rotation['schedule'] as $s) {
        if ($s['weekNumber'] === $currentWeek) {
            $schedule = $s;
            break;
        }
    }

    if (!$schedule) {
        throw new Exception('No schedule found for current week');
    }

    // Top 5 are permanent
    $permanentMembers = $rotation['metadata']['top5Permanent'];
    $rotatingMembers = $schedule['rotatingMembers'];

    $councilTags = array_merge($permanentMembers, $rotatingMembers);

    // Build voter details
    $voterDetails = [];
    foreach ($councilTags as $tag) {
        $alliance = null;
        foreach ($alliances as $a) {
            if ($a['tag'] === $tag) {
                $alliance = $a;
                break;
            }
        }

        if (!$alliance) {
            $voterDetails[] = [
                'alliance_tag' => $tag,
                'r5_name' => 'Unknown',
                'discord_id' => null,
                'delegated_voters' => [],
                'vote_submitted' => false,
                'submission_time' => null
            ];
            continue;
        }

        // Get delegated voters (R4s with canVote = true)
        $delegatedVoters = [];
        if (isset($alliance['r4s']) && is_array($alliance['r4s'])) {
            foreach ($alliance['r4s'] as $r4) {
                if (isset($r4['canVote']) && $r4['canVote'] && isset($r4['discordId'])) {
                    $delegatedVoters[] = [
                        'name' => $r4['name'] ?? 'Unknown',
                        'discord_id' => $r4['discordId'],
                        'role' => $r4['role'] ?? 'R4'
                    ];
                }
            }
        }

        $voterDetails[] = [
            'alliance_tag' => $tag,
            'r5_name' => $alliance['r5']['name'] ?? 'Unknown',
            'discord_id' => $alliance['r5']['discordId'] ?? null,
            'delegated_voters' => $delegatedVoters,
            'vote_submitted' => false,
            'submission_time' => null
        ];
    }

    return [
        'week_number' => $currentWeek,
        'permanent_members' => $permanentMembers,
        'rotating_members' => $rotatingMembers,
        'voter_details' => $voterDetails
    ];
}

/**
 * Helper: Create vote from approved request
 */
function createVoteFromRequest($request, $user) {
    $voteId = 'vote_' . date('Ymd') . '_' . substr(md5(uniqid('', true)), 0, 6);

    $councilSnapshot = getCurrentCouncilMembers();

    $now = gmdate('Y-m-d\TH:i:s.000\Z');
    $durationHours = 24;
    $endTime = gmdate('Y-m-d\TH:i:s.000\Z', strtotime("+{$durationHours} hours"));

    $vote = [
        'vote_id' => $voteId,
        'status' => 'active',
        'created_at' => $now,
        'created_by' => [
            'user_email' => $user->sub,
            'username' => $user->name ?? $user->sub,
            'role' => has_role($user, 'president') ? 'president' : 'admin',
            'source' => 'web_approval',
            'request_id' => $request['request_id']
        ],
        'vote_details' => [
            'title' => $request['vote_details']['title'],
            'description' => $request['vote_details']['description'],
            'options' => ['yes', 'no', 'abstain'],
            'category' => $request['vote_details']['category']
        ],
        'voting_period' => [
            'start_time' => $now,
            'end_time' => $endTime,
            'duration_hours' => $durationHours,
            'early_close_enabled' => false
        ],
        'council_snapshot' => $councilSnapshot,
        'submissions' => [],
        'results' => null,
        'discord_metadata' => [
            'vote_channel_id' => null,
            'vote_message_id' => null,
            'result_message_id' => null,
            'notification_dm_ids' => []
        ],
        'integrity' => [
            'vote_hash' => null,
            'hash_chain' => [
                [
                    'event' => 'vote_created_from_request',
                    'timestamp' => $now,
                    'data' => [
                        'vote_id' => $voteId,
                        'request_id' => $request['request_id'],
                        'approved_by' => $user->name ?? $user->sub,
                        'source' => 'web'
                    ]
                ]
            ]
        ]
    ];

    $vote['integrity']['vote_hash'] = hash('sha256', json_encode($vote));

    // Save vote
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    if (!isset($data['votes'])) {
        $data = ['votes' => []];
    }

    array_unshift($data['votes'], $vote);
    json_write($votesFile, $data);

    log_audit_event('discord_vote_created_from_request', $user->sub, [
        'vote_id' => $voteId,
        'request_id' => $request['request_id'],
        'title' => $vote['vote_details']['title']
    ]);

    return $voteId;
}
