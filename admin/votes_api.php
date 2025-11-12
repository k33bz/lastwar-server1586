<?php
/**
 * Votes Management API
 * Version: 1.0.1
 *
 * Handles CRUD operations for council votes with screenshot uploads
 * Allows presidents to record votes and make them public
 *
 * Access: Admin and President roles only
 *
 * Changelog:
 * v1.0.1 (2025-11-12) - Fixed authentication to use require_jwt_session_api()
 *   - Changed from require_jwt_session() to require_jwt_session_api()
 *   - Ensures JSON error responses instead of HTML redirects
 * v1.0.0 (2025-11-08) - Initial version
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    require_once 'jwt.php';
    require_once 'audit_logger.php';
    require_once 'json_helpers.php';
    require_once 'includes/csrf.php';
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server configuration error',
        'details' => $e->getMessage()
    ]);
    exit();
}

header('Content-Type: application/json');

try {
    $user = require_jwt_session_api();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication error',
        'details' => $e->getMessage()
    ]);
    exit();
}

// CSRF Protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$votes_file = __DIR__ . '/../data/votes.json';
$screenshots_dir = __DIR__ . '/../images/votes/';

// Wrap all operations in try-catch for better error reporting
try {

// Helper: Load votes
function load_votes($file) {
    if (!file_exists($file)) {
        return [];
    }
    return json_decode(file_get_contents($file), true) ?? [];
}

// Helper: Save votes
function save_votes($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

// Helper: Get all alliance tags
function get_alliance_tags() {
    $alliances_file = __DIR__ . '/../data/alliances.json';
    if (!file_exists($alliances_file)) {
        return [];
    }
    $alliances = json_decode(file_get_contents($alliances_file), true) ?? [];
    $tags = [];
    foreach ($alliances as $alliance) {
        $tags[] = $alliance['tag'];
    }
    return $tags;
}

// Helper: Generate unique vote ID
function generate_vote_id() {
    return 'vote_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
}

switch ($action) {
    case 'list':
        handle_list();
        break;
    case 'get':
        handle_get();
        break;
    case 'create':
        handle_create();
        break;
    case 'update':
        handle_update();
        break;
    case 'delete':
        handle_delete();
        break;
    case 'upload_screenshot':
        handle_screenshot_upload();
        break;
    case 'delete_screenshot':
        handle_screenshot_delete();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function handle_list() {
    global $user, $votes_file;

    // Public can view votes (for main site display)
    // Presidents and admins can view all details

    $votes = load_votes($votes_file);

    // Sort by vote_date descending (most recent first)
    usort($votes, function($a, $b) {
        return strtotime($b['vote_date']) - strtotime($a['vote_date']);
    });

    echo json_encode([
        'success' => true,
        'votes' => $votes
    ]);
}

function handle_get() {
    global $user, $votes_file;

    $vote_id = $_GET['id'] ?? '';

    if (empty($vote_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote ID required']);
        return;
    }

    $votes = load_votes($votes_file);
    $vote = null;

    foreach ($votes as $v) {
        if ($v['id'] === $vote_id) {
            $vote = $v;
            break;
        }
    }

    if (!$vote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vote not found']);
        return;
    }

    echo json_encode([
        'success' => true,
        'vote' => $vote
    ]);
}

function handle_create() {
    global $user, $votes_file;

    // Only presidents and admins can create votes
    if (!has_role($user, ['admin', 'president'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['resolution']) || empty(trim($input['resolution']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Resolution text is required']);
        return;
    }

    if (!isset($input['vote_date']) || empty($input['vote_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote date is required']);
        return;
    }

    if (!isset($input['votes']) || !is_array($input['votes'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote results are required']);
        return;
    }

    $votes = load_votes($votes_file);

    $new_vote = [
        'id' => generate_vote_id(),
        'resolution' => trim($input['resolution']),
        'vote_date' => $input['vote_date'],
        'created_by' => $user->sub,
        'created_at' => date('c'),
        'updated_at' => date('c'),
        'screenshots' => [],
        'votes' => $input['votes']
    ];

    $votes[] = $new_vote;

    if (!save_votes($votes_file, $votes)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save vote']);
        return;
    }

    log_audit_event('vote_created', $user->sub, [
        'vote_id' => $new_vote['id'],
        'resolution' => substr($new_vote['resolution'], 0, 100)
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Vote created successfully',
        'vote' => $new_vote
    ]);
}

function handle_update() {
    global $user, $votes_file;

    // Only presidents and admins can update votes
    if (!has_role($user, ['admin', 'president'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote ID is required']);
        return;
    }

    $votes = load_votes($votes_file);
    $vote_index = null;

    foreach ($votes as $index => $v) {
        if ($v['id'] === $input['id']) {
            $vote_index = $index;
            break;
        }
    }

    if ($vote_index === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vote not found']);
        return;
    }

    // Update fields
    if (isset($input['resolution'])) {
        $votes[$vote_index]['resolution'] = trim($input['resolution']);
    }
    if (isset($input['vote_date'])) {
        $votes[$vote_index]['vote_date'] = $input['vote_date'];
    }
    if (isset($input['votes'])) {
        $votes[$vote_index]['votes'] = $input['votes'];
    }

    $votes[$vote_index]['updated_at'] = date('c');

    if (!save_votes($votes_file, $votes)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save vote']);
        return;
    }

    log_audit_event('vote_updated', $user->sub, [
        'vote_id' => $input['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Vote updated successfully',
        'vote' => $votes[$vote_index]
    ]);
}

function handle_delete() {
    global $user, $votes_file, $screenshots_dir;

    // Only presidents and admins can delete votes
    if (!has_role($user, ['admin', 'president'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote ID is required']);
        return;
    }

    $votes = load_votes($votes_file);
    $vote_index = null;
    $vote_to_delete = null;

    foreach ($votes as $index => $v) {
        if ($v['id'] === $input['id']) {
            $vote_index = $index;
            $vote_to_delete = $v;
            break;
        }
    }

    if ($vote_index === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vote not found']);
        return;
    }

    // Delete associated screenshots
    if (isset($vote_to_delete['screenshots']) && is_array($vote_to_delete['screenshots'])) {
        foreach ($vote_to_delete['screenshots'] as $screenshot) {
            $filepath = __DIR__ . '/../' . $screenshot;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    // Remove vote from array
    array_splice($votes, $vote_index, 1);

    if (!save_votes($votes_file, $votes)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete vote']);
        return;
    }

    log_audit_event('vote_deleted', $user->sub, [
        'vote_id' => $input['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Vote deleted successfully'
    ]);
}

function handle_screenshot_upload() {
    global $user, $votes_file, $screenshots_dir;

    // Only presidents and admins can upload screenshots
    if (!has_role($user, ['admin', 'president'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $vote_id = $_POST['vote_id'] ?? '';

    if (empty($vote_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote ID is required']);
        return;
    }

    if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
        return;
    }

    $votes = load_votes($votes_file);
    $vote_index = null;

    foreach ($votes as $index => $v) {
        if ($v['id'] === $vote_id) {
            $vote_index = $index;
            break;
        }
    }

    if ($vote_index === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vote not found']);
        return;
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['screenshot']['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only images allowed.']);
        return;
    }

    // Validate file size (max 10MB)
    if ($_FILES['screenshot']['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB.']);
        return;
    }

    // Validate file extension
    $extension = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file extension. Only jpg, jpeg, png, gif, and webp are allowed.']);
        return;
    }

    // Generate unique filename
    $filename = $vote_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filepath = $screenshots_dir . $filename;

    // Ensure directory exists
    if (!is_dir($screenshots_dir)) {
        mkdir($screenshots_dir, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($_FILES['screenshot']['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        return;
    }

    // Add screenshot to vote
    if (!isset($votes[$vote_index]['screenshots'])) {
        $votes[$vote_index]['screenshots'] = [];
    }
    $votes[$vote_index]['screenshots'][] = 'images/votes/' . $filename;
    $votes[$vote_index]['updated_at'] = date('c');

    if (!save_votes($votes_file, $votes)) {
        // Clean up uploaded file
        unlink($filepath);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update vote']);
        return;
    }

    log_audit_event('vote_screenshot_uploaded', $user->sub, [
        'vote_id' => $vote_id,
        'filename' => $filename
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Screenshot uploaded successfully',
        'filename' => 'images/votes/' . $filename,
        'vote' => $votes[$vote_index]
    ]);
}

function handle_screenshot_delete() {
    global $user, $votes_file;

    // Only presidents and admins can delete screenshots
    if (!has_role($user, ['admin', 'president'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['vote_id']) || empty($input['vote_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vote ID is required']);
        return;
    }

    if (!isset($input['filename']) || empty($input['filename'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Filename is required']);
        return;
    }

    $votes = load_votes($votes_file);
    $vote_index = null;

    foreach ($votes as $index => $v) {
        if ($v['id'] === $input['vote_id']) {
            $vote_index = $index;
            break;
        }
    }

    if ($vote_index === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vote not found']);
        return;
    }

    // Find and remove screenshot
    $screenshot_found = false;
    if (isset($votes[$vote_index]['screenshots']) && is_array($votes[$vote_index]['screenshots'])) {
        foreach ($votes[$vote_index]['screenshots'] as $key => $screenshot) {
            if ($screenshot === $input['filename']) {
                // Delete file
                $filepath = __DIR__ . '/../' . $screenshot;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                // Remove from array
                array_splice($votes[$vote_index]['screenshots'], $key, 1);
                $screenshot_found = true;
                break;
            }
        }
    }

    if (!$screenshot_found) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Screenshot not found']);
        return;
    }

    $votes[$vote_index]['updated_at'] = date('c');

    if (!save_votes($votes_file, $votes)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update vote']);
        return;
    }

    log_audit_event('vote_screenshot_deleted', $user->sub, [
        'vote_id' => $input['vote_id'],
        'filename' => $input['filename']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Screenshot deleted successfully',
        'vote' => $votes[$vote_index]
    ]);
}

} catch (Throwable $e) {
    error_log("Votes API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred',
        'details' => $e->getMessage()
    ]);
}
