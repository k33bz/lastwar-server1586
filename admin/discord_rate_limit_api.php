<?php
/**
 * Discord Rate Limit Management API
 * Version: 1.0.0
 *
 * Handles user rate limit requests and admin approvals
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json');

try {
    require_once 'jwt.php';
    require_once 'audit_logger.php';
    require_once 'json_helpers.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error',
        'details' => $e->getMessage()
    ]);
    exit();
}

try {
    $user = require_jwt_session();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication error',
        'details' => $e->getMessage()
    ]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$requests_file = __DIR__ . '/discord_rate_limit_requests.json';

// Helper: Load rate limit requests
function load_requests($file) {
    if (!file_exists($file)) {
        return ['requests' => []];
    }
    return json_decode(file_get_contents($file), true) ?? ['requests' => []];
}

// Helper: Save rate limit requests
function save_requests($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Helper: Get user's current rate limit
function get_current_rate_limit($email) {
    $users_data = read_json_file(__DIR__ . '/users.json');

    foreach ($users_data['users'] as $user) {
        if ($user['email'] === $email) {
            return $user['discord_rate_limit'] ?? DISCORD_MAX_INSTANT_PER_HOUR;
        }
    }

    return DISCORD_MAX_INSTANT_PER_HOUR;
}

try {
    switch ($action) {
        case 'get_my_limit':
            // Get current user's rate limit
            $current_limit = get_current_rate_limit($user->sub);

            // Check if user has pending request
            $data = load_requests($requests_file);
            $pending_request = null;

            foreach ($data['requests'] as $req) {
                if ($req['user_email'] === $user->sub && $req['status'] === 'pending') {
                    $pending_request = $req;
                    break;
                }
            }

            echo json_encode([
                'success' => true,
                'current_limit' => $current_limit,
                'is_admin' => $user->aud === 'admin',
                'pending_request' => $pending_request
            ]);

            break;

        case 'request_increase':
            // User requests rate limit increase
            $input = json_decode(file_get_contents('php://input'), true);

            $requested_limit = (int)($input['requested_limit'] ?? 0);
            $reason = trim($input['reason'] ?? '');

            if ($requested_limit <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid requested limit']);
                exit();
            }

            if (empty($reason)) {
                echo json_encode(['success' => false, 'error' => 'Please provide a reason for the increase']);
                exit();
            }

            $current_limit = get_current_rate_limit($user->sub);

            if ($requested_limit <= $current_limit) {
                echo json_encode(['success' => false, 'error' => 'Requested limit must be higher than current limit']);
                exit();
            }

            // Check for existing pending request
            $data = load_requests($requests_file);
            foreach ($data['requests'] as $req) {
                if ($req['user_email'] === $user->sub && $req['status'] === 'pending') {
                    echo json_encode(['success' => false, 'error' => 'You already have a pending rate limit request']);
                    exit();
                }
            }

            // Get user info
            $users_data = read_json_file(__DIR__ . '/users.json');
            $user_ign = '';
            foreach ($users_data['users'] as $u) {
                if ($u['email'] === $user->sub) {
                    $user_ign = $u['ign'] ?? $user->sub;
                    break;
                }
            }

            // Create request
            $request = [
                'id' => uniqid('rlr_', true),
                'user_email' => $user->sub,
                'user_ign' => $user_ign,
                'current_limit' => $current_limit,
                'requested_limit' => $requested_limit,
                'reason' => $reason,
                'status' => 'pending',
                'requested_at' => date('Y-m-d H:i:s')
            ];

            $data['requests'][] = $request;

            if (save_requests($requests_file, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rate limit increase request submitted. An admin will review it soon.',
                    'request' => $request
                ]);

                log_audit_event('discord_rate_limit_requested', $user->sub, [
                    'current_limit' => $current_limit,
                    'requested_limit' => $requested_limit,
                    'reason' => $reason
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save request']);
            }

            break;

        case 'list_requests':
            // Admin only - list all rate limit requests
            if ($user->aud !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit();
            }

            $data = load_requests($requests_file);

            // Filter by status if requested
            $status_filter = $_GET['status'] ?? 'all';
            $requests = $data['requests'];

            if ($status_filter !== 'all') {
                $requests = array_filter($requests, function($req) use ($status_filter) {
                    return $req['status'] === $status_filter;
                });
            }

            // Sort by date (newest first)
            usort($requests, function($a, $b) {
                return strtotime($b['requested_at']) - strtotime($a['requested_at']);
            });

            echo json_encode([
                'success' => true,
                'requests' => array_values($requests)
            ]);

            log_audit_event('discord_rate_limit_requests_viewed', $user->sub);

            break;

        case 'approve_request':
            // Admin only - approve rate limit request
            if ($user->aud !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit();
            }

            requireCsrfToken();

            $request_id = $_POST['request_id'] ?? '';

            if (empty($request_id)) {
                echo json_encode(['success' => false, 'error' => 'Missing request_id']);
                exit();
            }

            $data = load_requests($requests_file);
            $found = false;
            $approved_request = null;

            // Find and update request
            foreach ($data['requests'] as &$req) {
                if ($req['id'] === $request_id && $req['status'] === 'pending') {
                    $req['status'] = 'approved';
                    $req['reviewed_by'] = $user->sub;
                    $req['reviewed_at'] = date('Y-m-d H:i:s');
                    $approved_request = $req;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                echo json_encode(['success' => false, 'error' => 'Request not found or already processed']);
                exit();
            }

            // Update user's rate limit in users.json (only if different from default)
            $users_data = read_json_file(__DIR__ . '/users.json');
            $user_updated = false;

            foreach ($users_data['users'] as &$u) {
                if ($u['email'] === $approved_request['user_email']) {
                    $requested_limit = $approved_request['requested_limit'];

                    // Only store if different from system default
                    if ($requested_limit != DISCORD_MAX_INSTANT_PER_HOUR) {
                        $u['discord_rate_limit'] = $requested_limit;
                    } else {
                        // Remove field if it equals default (cleanup)
                        unset($u['discord_rate_limit']);
                    }

                    $user_updated = true;
                    break;
                }
            }

            if ($user_updated) {
                write_json_file(__DIR__ . '/users.json', $users_data);
            }

            // Save updated requests
            if (save_requests($requests_file, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rate limit request approved'
                ]);

                log_audit_event('discord_rate_limit_approved', $user->sub, [
                    'request_id' => $request_id,
                    'user_email' => $approved_request['user_email'],
                    'new_limit' => $approved_request['requested_limit']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save changes']);
            }

            break;

        case 'reject_request':
            // Admin only - reject rate limit request
            if ($user->aud !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit();
            }

            requireCsrfToken();

            $request_id = $_POST['request_id'] ?? '';
            $rejection_reason = $_POST['reason'] ?? 'No reason provided';

            if (empty($request_id)) {
                echo json_encode(['success' => false, 'error' => 'Missing request_id']);
                exit();
            }

            $data = load_requests($requests_file);
            $found = false;

            foreach ($data['requests'] as &$req) {
                if ($req['id'] === $request_id && $req['status'] === 'pending') {
                    $req['status'] = 'rejected';
                    $req['reviewed_by'] = $user->sub;
                    $req['reviewed_at'] = date('Y-m-d H:i:s');
                    $req['rejection_reason'] = $rejection_reason;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                echo json_encode(['success' => false, 'error' => 'Request not found or already processed']);
                exit();
            }

            if (save_requests($requests_file, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rate limit request rejected'
                ]);

                log_audit_event('discord_rate_limit_rejected', $user->sub, [
                    'request_id' => $request_id,
                    'reason' => $rejection_reason
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save changes']);
            }

            break;

        case 'set_user_limit':
            // Admin only - directly set user's rate limit
            if ($user->aud !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Admin access required']);
                exit();
            }

            requireCsrfToken();

            $input = json_decode(file_get_contents('php://input'), true);

            $target_email = $input['user_email'] ?? '';
            $new_limit = (int)($input['limit'] ?? 0);

            if (empty($target_email) || $new_limit <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
                exit();
            }

            $users_data = read_json_file(__DIR__ . '/users.json');
            $found = false;

            foreach ($users_data['users'] as &$u) {
                if ($u['email'] === $target_email) {
                    $old_limit = $u['discord_rate_limit'] ?? DISCORD_MAX_INSTANT_PER_HOUR;

                    // Only store if different from system default
                    if ($new_limit != DISCORD_MAX_INSTANT_PER_HOUR) {
                        $u['discord_rate_limit'] = $new_limit;
                    } else {
                        // Remove field if it equals default (cleanup)
                        unset($u['discord_rate_limit']);
                    }

                    $found = true;

                    log_audit_event('discord_rate_limit_admin_set', $user->sub, [
                        'target_user' => $target_email,
                        'old_limit' => $old_limit,
                        'new_limit' => $new_limit
                    ]);
                    break;
                }
            }

            if (!$found) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit();
            }

            if (write_json_file(__DIR__ . '/users.json', $users_data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User rate limit updated'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save changes']);
            }

            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    error_log('Discord Rate Limit API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred',
        'details' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>
