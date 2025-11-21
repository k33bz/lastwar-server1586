<?php
/**
 * Discord Vote Submission API
 * Version: 1.0.0
 *
 * Unified API for vote submissions from both Discord bot and email voting
 * Handles vote submission, eligibility verification, and magic link generation
 *
 * Endpoints:
 * - POST ?action=submit_vote - Submit a vote (Discord bot or email magic link)
 * - POST ?action=generate_vote_token - Generate magic link token for email voting (Admin/President only)
 * - GET ?action=verify_vote_eligibility - Check if voter is eligible (uses magic link token)
 * - GET ?action=get_vote_status - Get current vote status and submissions
 *
 * Authentication:
 * - Discord bot: X-Bot-Signature header
 * - Email voting: Magic link token in request
 * - Admin operations: JWT session
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
if (!defined('ADMIN_BASE_PATH')) {
    define('ADMIN_BASE_PATH', __DIR__);
}

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'json_helpers.php';
require_once 'includes/csrf.php';

// Only handle actions if this file is accessed directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'discord_vote_submit_api.php') {
    // Set JSON response headers
    header('Content-Type: application/json');

    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'submit_vote':
                handleSubmitVote();
                break;

            case 'generate_vote_token':
                handleGenerateVoteToken();
                break;

            case 'verify_vote_eligibility':
                handleVerifyVoteEligibility();
                break;

            case 'get_vote_status':
                handleGetVoteStatus();
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
} // End of: if file accessed directly

/**
 * Handle vote submission (Discord or email)
 */
function handleSubmitVote() {
    $input = json_decode(file_get_contents('php://input'), true);

    $voteId = $input['vote_id'] ?? '';
    $choice = strtolower($input['vote_choice'] ?? '');
    $source = $input['submission_method'] ?? 'unknown';
    $token = $input['token'] ?? null;

    // Validate choice
    $validChoices = ['yes', 'no', 'abstain'];
    if (!in_array($choice, $validChoices)) {
        throw new Exception('Invalid vote choice. Must be yes, no, or abstain.');
    }

    // Authenticate based on source
    if ($source === 'discord') {
        // Discord bot authentication
        $botSignature = $_SERVER['HTTP_X_BOT_SIGNATURE'] ?? '';
        if (!verifyBotSignature($botSignature, file_get_contents('php://input'))) {
            throw new Exception('Invalid bot signature');
        }

        $submitterInfo = [
            'discord_id' => $input['discord_id'] ?? null,
            'username' => $input['username'] ?? 'Unknown',
            'user_uid' => $input['user_uid'] ?? null,
            'user_email' => $input['user_email'] ?? null
        ];
        $allianceTag = $input['alliance_tag'] ?? null;

    } else if ($source === 'email') {
        // Email magic link authentication
        if (!$token) {
            throw new Exception('Vote token required for email submissions');
        }

        $tokenData = verifyVoteToken($token);
        if (!$tokenData) {
            throw new Exception('Invalid or expired vote token');
        }

        $submitterInfo = [
            'discord_id' => $tokenData['discord_id'] ?? null,
            'username' => $tokenData['username'] ?? 'Unknown',
            'user_uid' => $tokenData['user_uid'],
            'user_email' => $tokenData['user_email']
        ];
        $allianceTag = $tokenData['alliance_tag'];

        // Verify vote ID matches token
        if ($voteId !== $tokenData['vote_id']) {
            throw new Exception('Vote ID mismatch');
        }

        // Mark token as used
        markTokenAsUsed($token);

    } else {
        throw new Exception('Invalid submission method');
    }

    // Load vote
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    $voteIndex = null;
    foreach ($data['votes'] as $idx => $vote) {
        if ($vote['vote_id'] === $voteId) {
            $voteIndex = $idx;
            break;
        }
    }

    if ($voteIndex === null) {
        throw new Exception('Vote not found');
    }

    $vote = &$data['votes'][$voteIndex];

    // Verify vote is active
    if ($vote['status'] !== 'active') {
        throw new Exception('Vote is not active (status: ' . $vote['status'] . ')');
    }

    // Verify deadline hasn't passed
    $now = new DateTime();
    $endTime = new DateTime($vote['voting_period']['end_time']);
    if ($now > $endTime) {
        throw new Exception('Voting period has ended');
    }

    // Verify voter eligibility
    $voterInfo = null;
    foreach ($vote['council_snapshot']['voter_details'] as $voter) {
        if ($voter['alliance_tag'] === $allianceTag) {
            $voterInfo = $voter;
            break;
        }
    }

    if (!$voterInfo) {
        throw new Exception('You are not eligible to vote on this matter (alliance: ' . $allianceTag . ')');
    }

    // Check if already voted
    foreach ($vote['submissions'] as $submission) {
        if ($submission['alliance_tag'] === $allianceTag) {
            throw new Exception('This alliance has already submitted a vote');
        }
    }

    // Record submission
    $now = gmdate('Y-m-d\TH:i:s.000\Z');
    $submission = [
        'alliance_tag' => $allianceTag,
        'vote_choice' => $choice,
        'submitted_at' => $now,
        'submission_method' => $source,
        'submitted_by' => $submitterInfo,
        'vote_sequence' => count($vote['submissions']) + 1,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    $vote['submissions'][] = $submission;

    // Update voter status
    foreach ($vote['council_snapshot']['voter_details'] as &$voter) {
        if ($voter['alliance_tag'] === $allianceTag) {
            $voter['vote_submitted'] = true;
            $voter['submission_time'] = $now;
            break;
        }
    }

    // Add to integrity hash chain
    if (!isset($vote['integrity']['hash_chain'])) {
        $vote['integrity']['hash_chain'] = [];
    }

    $vote['integrity']['hash_chain'][] = [
        'event' => 'vote_submitted_' . $allianceTag,
        'timestamp' => $now,
        'data' => [
            'alliance' => $allianceTag,
            'choice' => $choice,
            'method' => $source,
            'sequence' => $submission['vote_sequence']
        ]
    ];

    // Update vote hash
    $vote['integrity']['vote_hash'] = hash('sha256', json_encode($vote));

    // Save
    json_write($votesFile, $data);

    // Log audit event
    log_audit_event('discord_vote_submitted', $submitterInfo['user_email'] ?? 'system', [
        'vote_id' => $voteId,
        'alliance' => $allianceTag,
        'choice' => $choice,
        'method' => $source
    ]);

    // Check if all votes submitted (for early close)
    $allVotesSubmitted = true;
    foreach ($vote['council_snapshot']['voter_details'] as $voter) {
        if (!$voter['vote_submitted']) {
            $allVotesSubmitted = false;
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'submission' => $submission,
        'vote_status' => [
            'total_eligible' => count($vote['council_snapshot']['voter_details']),
            'total_submitted' => count($vote['submissions']),
            'all_votes_submitted' => $allVotesSubmitted
        ],
        'message' => 'Vote recorded successfully'
    ]);
}

/**
 * Generate magic link token for email voting
 * Admin/President only
 */
function handleGenerateVoteToken() {
    // Require JWT authentication
    $user = require_jwt_session_api();

    if (!has_role($user, ['admin', 'president'])) {
        throw new Exception('Access denied. Only admin and president can generate vote tokens.');
    }

    // CSRF protection
    csrf_validate();

    $input = json_decode(file_get_contents('php://input'), true);

    $voteId = $input['vote_id'] ?? '';
    $allianceTag = $input['alliance_tag'] ?? '';

    if (empty($voteId) || empty($allianceTag)) {
        throw new Exception('Vote ID and alliance tag are required');
    }

    // Load vote
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    $vote = null;
    foreach ($data['votes'] as $v) {
        if ($v['vote_id'] === $voteId) {
            $vote = $v;
            break;
        }
    }

    if (!$vote) {
        throw new Exception('Vote not found');
    }

    // Verify alliance is eligible
    $voterInfo = null;
    foreach ($vote['council_snapshot']['voter_details'] as $voter) {
        if ($voter['alliance_tag'] === $allianceTag) {
            $voterInfo = $voter;
            break;
        }
    }

    if (!$voterInfo) {
        throw new Exception('Alliance not eligible for this vote');
    }

    // Get R5 user info from alliances
    $alliancesFile = __DIR__ . '/../data/alliances.json';
    $alliances = json_read($alliancesFile);

    $alliance = null;
    foreach ($alliances as $a) {
        if ($a['tag'] === $allianceTag) {
            $alliance = $a;
            break;
        }
    }

    if (!$alliance) {
        throw new Exception('Alliance not found');
    }

    // Get user info (try to find by email)
    $userInfo = null;
    if (isset($alliance['r5']['email']) && !empty($alliance['r5']['email'])) {
        $userInfo = get_user_by_email($alliance['r5']['email']);
    }

    // Generate token
    $token = generateVoteToken($voteId, $allianceTag, $userInfo);

    // Build magic link
    $baseUrl = rtrim($_ENV['SITE_URL'] ?? 'https://www.lastwar1586.online', '/');
    $magicLink = $baseUrl . '/admin/vote_submit.php?token=' . $token;

    echo json_encode([
        'success' => true,
        'token' => $token,
        'magic_link' => $magicLink,
        'voter_info' => [
            'alliance_tag' => $allianceTag,
            'r5_name' => $alliance['r5']['name'] ?? 'Unknown',
            'email' => $userInfo['email'] ?? null
        ]
    ]);
}

/**
 * Verify vote eligibility (for email voting page)
 */
function handleVerifyVoteEligibility() {
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        throw new Exception('Token is required');
    }

    $tokenData = verifyVoteToken($token);
    if (!$tokenData) {
        throw new Exception('Invalid or expired vote token');
    }

    // Load vote
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    $vote = null;
    foreach ($data['votes'] as $v) {
        if ($v['vote_id'] === $tokenData['vote_id']) {
            $vote = $v;
            break;
        }
    }

    if (!$vote) {
        throw new Exception('Vote not found');
    }

    // Check if already voted
    $alreadyVoted = false;
    foreach ($vote['submissions'] as $submission) {
        if ($submission['alliance_tag'] === $tokenData['alliance_tag']) {
            $alreadyVoted = true;
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'vote' => [
            'vote_id' => $vote['vote_id'],
            'title' => $vote['vote_details']['title'],
            'description' => $vote['vote_details']['description'],
            'category' => $vote['vote_details']['category'],
            'end_time' => $vote['voting_period']['end_time'],
            'status' => $vote['status']
        ],
        'voter_info' => [
            'alliance_tag' => $tokenData['alliance_tag'],
            'username' => $tokenData['username']
        ],
        'already_voted' => $alreadyVoted,
        'is_expired' => $vote['status'] !== 'active' || new DateTime() > new DateTime($vote['voting_period']['end_time'])
    ]);
}

/**
 * Get vote status (for both Discord bot and email)
 */
function handleGetVoteStatus() {
    $voteId = $_GET['vote_id'] ?? '';

    if (empty($voteId)) {
        throw new Exception('Vote ID is required');
    }

    // Load vote
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    $vote = null;
    foreach ($data['votes'] as $v) {
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
        'vote_id' => $vote['vote_id'],
        'status' => $vote['status'],
        'total_eligible' => count($vote['council_snapshot']['voter_details']),
        'total_submitted' => count($vote['submissions']),
        'submissions' => $vote['submissions'],
        'end_time' => $vote['voting_period']['end_time']
    ]);
}

/**
 * Helper: Verify bot signature
 */
function verifyBotSignature($signature, $payload) {
    $secret = $_ENV['DISCORD_BOT_WEBHOOK_SECRET'] ?? 'your-webhook-secret-here';
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Helper: Generate vote token
 */
function generateVoteToken($voteId, $allianceTag, $userInfo) {
    $tokensFile = __DIR__ . '/vote_tokens.json';

    if (!file_exists($tokensFile)) {
        file_put_contents($tokensFile, json_encode(['tokens' => []], JSON_PRETTY_PRINT));
    }

    $data = json_read($tokensFile);

    $token = bin2hex(random_bytes(32));
    $expiresAt = gmdate('Y-m-d\TH:i:s.000\Z', strtotime('+48 hours'));

    $tokenData = [
        'token' => $token,
        'vote_id' => $voteId,
        'alliance_tag' => $allianceTag,
        'user_uid' => $userInfo['uid'] ?? null,
        'user_email' => $userInfo['email'] ?? null,
        'username' => $userInfo['in_game_name'] ?? $allianceTag,
        'discord_id' => $userInfo['discord_id'] ?? null,
        'created_at' => gmdate('Y-m-d\TH:i:s.000\Z'),
        'expires_at' => $expiresAt,
        'used' => false,
        'used_at' => null
    ];

    $data['tokens'][] = $tokenData;

    json_write($tokensFile, $data);

    return $token;
}

/**
 * Helper: Verify vote token
 */
function verifyVoteToken($token) {
    $tokensFile = __DIR__ . '/vote_tokens.json';

    if (!file_exists($tokensFile)) {
        return null;
    }

    $data = json_read($tokensFile);

    foreach ($data['tokens'] as $tokenData) {
        if ($tokenData['token'] === $token) {
            // Check if used
            if ($tokenData['used']) {
                return null;
            }

            // Check if expired
            $expiresAt = new DateTime($tokenData['expires_at']);
            if (new DateTime() > $expiresAt) {
                return null;
            }

            return $tokenData;
        }
    }

    return null;
}

/**
 * Helper: Mark token as used
 */
function markTokenAsUsed($token) {
    $tokensFile = __DIR__ . '/vote_tokens.json';
    $data = json_read($tokensFile);

    foreach ($data['tokens'] as &$tokenData) {
        if ($tokenData['token'] === $token) {
            $tokenData['used'] = true;
            $tokenData['used_at'] = gmdate('Y-m-d\TH:i:s.000\Z');
            break;
        }
    }

    json_write($tokensFile, $data);
}
