<?php
/**
 * Discord Vote Webhook
 * Version: 1.0.0
 *
 * Receives finalized vote data from Discord bot
 * Updates vote records and sends result email notifications
 *
 * Authentication: Bot signature verification
 */

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);

require_once 'json_helpers.php';
require_once 'audit_logger.php';
require_once 'vote_email_helper.php';

// Set JSON response headers
header('Content-Type: application/json');

// Verify bot signature
$botSignature = $_SERVER['HTTP_X_BOT_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');

if (!verifyBotSignature($botSignature, $payload)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid bot signature'
    ]);
    exit();
}

// Parse vote data
$voteData = json_decode($payload, true);

if (!$voteData || !isset($voteData['vote_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid vote data'
    ]);
    exit();
}

try {
    // Load votes file
    $votesFile = __DIR__ . '/../data/discord-votes.json';
    $data = json_read($votesFile);

    // Find and update vote
    $voteIndex = null;
    foreach ($data['votes'] as $idx => $vote) {
        if ($vote['vote_id'] === $voteData['vote_id']) {
            $voteIndex = $idx;
            break;
        }
    }

    if ($voteIndex === null) {
        throw new Exception('Vote not found');
    }

    // Update vote with finalized data
    $data['votes'][$voteIndex] = $voteData;
    json_write($votesFile, $data);

    // Send result email notifications if vote is completed
    if ($voteData['status'] === 'completed' && isset($voteData['results'])) {
        sendVoteResultEmails($voteData);
    }

    // Log audit event
    log_audit_event('discord_vote_webhook_received', 'system', [
        'vote_id' => $voteData['vote_id'],
        'status' => $voteData['status'],
        'outcome' => $voteData['results']['outcome'] ?? null
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Vote updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
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
 * Helper: Send result email notifications
 */
function sendVoteResultEmails($vote) {
    $alliancesFile = __DIR__ . '/../data/alliances.json';
    $alliances = json_read($alliancesFile);

    $emailsSent = 0;
    $emailsFailed = 0;

    // Get all voters (both who voted and who didn't)
    foreach ($vote['council_snapshot']['voter_details'] as $voter) {
        $allianceTag = $voter['alliance_tag'];

        // Find alliance data
        $alliance = null;
        foreach ($alliances as $a) {
            if ($a['tag'] === $allianceTag) {
                $alliance = $a;
                break;
            }
        }

        if (!$alliance) {
            error_log("Alliance not found for tag: {$allianceTag}");
            continue;
        }

        // Get R5 info
        $r5Email = $alliance['r5']['email'] ?? null;
        $r5Name = $alliance['r5']['name'] ?? $allianceTag;

        if (!$r5Email) {
            error_log("No email for R5 of alliance: {$allianceTag}");
            continue;
        }

        // Get user info for language preference
        $userInfo = get_user_by_email($r5Email);
        $language = $userInfo['preferred_language'] ?? 'en';

        // Find voter's choice (if they voted)
        $voterChoice = null;
        foreach ($vote['submissions'] as $submission) {
            if ($submission['alliance_tag'] === $allianceTag) {
                $voterChoice = $submission['vote_choice'];
                break;
            }
        }

        // Send result email
        try {
            $emailSent = send_vote_result_notification_email(
                $r5Email,
                $vote['vote_id'],
                $vote['vote_details']['title'],
                $allianceTag,
                $r5Name,
                $voterChoice,
                $vote['results'],
                $language
            );

            if ($emailSent) {
                $emailsSent++;
                error_log("Vote result email sent to {$r5Email} ({$allianceTag})");
            } else {
                $emailsFailed++;
                error_log("Failed to send vote result to {$r5Email} ({$allianceTag})");
            }

        } catch (Exception $e) {
            $emailsFailed++;
            error_log("Error sending vote result to {$r5Email}: " . $e->getMessage());
        }

        // Also send to R4s with voting rights
        if (isset($alliance['r4s']) && is_array($alliance['r4s'])) {
            foreach ($alliance['r4s'] as $r4) {
                if (!isset($r4['canVote']) || !$r4['canVote']) {
                    continue;
                }

                $r4Email = $r4['email'] ?? null;
                $r4Name = $r4['name'] ?? 'R4 Officer';

                if (!$r4Email) {
                    continue;
                }

                // Get user info for language preference
                $r4UserInfo = get_user_by_email($r4Email);
                $r4Language = $r4UserInfo['preferred_language'] ?? 'en';

                // R4s share the alliance's vote choice
                try {
                    $r4EmailSent = send_vote_result_notification_email(
                        $r4Email,
                        $vote['vote_id'],
                        $vote['vote_details']['title'],
                        $allianceTag,
                        $r4Name,
                        $voterChoice,
                        $vote['results'],
                        $r4Language
                    );

                    if ($r4EmailSent) {
                        $emailsSent++;
                        error_log("Vote result email sent to R4 {$r4Email} ({$allianceTag})");
                    } else {
                        $emailsFailed++;
                        error_log("Failed to send vote result to R4 {$r4Email} ({$allianceTag})");
                    }

                } catch (Exception $e) {
                    $emailsFailed++;
                    error_log("Error sending vote result to R4 {$r4Email}: " . $e->getMessage());
                }
            }
        }
    }

    // Log summary
    log_audit_event('vote_result_emails_sent', 'system', [
        'vote_id' => $vote['vote_id'],
        'emails_sent' => $emailsSent,
        'emails_failed' => $emailsFailed
    ]);

    return [
        'sent' => $emailsSent,
        'failed' => $emailsFailed
    ];
}
