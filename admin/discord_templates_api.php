<?php
/**
 * Discord Message Templates API
 * Version: 1.0.1
 *
 * Handles CRUD operations for Discord message templates with variable support
 * Templates can be global (all alliances) or alliance-specific
 * Similar to tags system with submission/approval workflow
 *
 * Changelog:
 *   1.0.1 (2025-11-06) - Added error handling and logging for 500 errors
 *   1.0.0 (2025-11-05) - Initial implementation
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Catch fatal errors
try {
    require_once 'jwt.php';
    require_once 'audit_logger.php';
    require_once 'json_helpers.php';
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

// Check if user has at least R4 access or president role
if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Check if Discord is enabled
if (!defined('DISCORD_ENABLED') || !DISCORD_ENABLED) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Discord integration is disabled']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$templates_file = __DIR__ . '/discord_templates.json';

// Wrap all operations in try-catch for better error reporting
try {

// Helper: Load templates
function load_templates($file) {
    if (!file_exists($file)) {
        return ['templates' => [], 'pending_submissions' => []];
    }
    return json_decode(file_get_contents($file), true) ?? ['templates' => [], 'pending_submissions' => []];
}

// Helper: Save templates
function save_templates($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Helper: Get user's alliance
function get_user_alliance($user) {
    $users_data = read_json_file(__DIR__ . '/users.json');

    foreach ($users_data['users'] as $u) {
        if ($u['email'] === $user->sub) {
            return $u['alliance'] ?? null;
        }
    }
    return null;
}

// Helper: Get available variables
function get_available_variables() {
    return [
        'server' => [
            'label' => 'Server Information',
            'variables' => [
                ['key' => '{server_name}', 'description' => 'Server name (e.g., "Server 1586")'],
                ['key' => '{server_reset_time}', 'description' => 'Server reset time'],
            ]
        ],
        'user' => [
            'label' => 'User Information',
            'variables' => [
                ['key' => '{sender_name}', 'description' => 'Name of person sending message'],
                ['key' => '{sender_alliance}', 'description' => 'Alliance of sender'],
                ['key' => '{sender_tag}', 'description' => 'Alliance tag of sender'],
            ]
        ],
        'alliance' => [
            'label' => 'Alliance Information',
            'variables' => [
                ['key' => '{alliance_name}', 'description' => 'Full alliance name'],
                ['key' => '{alliance_tag}', 'description' => 'Alliance tag'],
                ['key' => '{r5_name}', 'description' => 'R5 leader name'],
            ]
        ],
        'datetime' => [
            'label' => 'Date & Time',
            'variables' => [
                ['key' => '{date}', 'description' => 'Current date (YYYY-MM-DD)'],
                ['key' => '{time}', 'description' => 'Current time (HH:MM)'],
                ['key' => '{datetime}', 'description' => 'Current date and time'],
            ]
        ],
        'custom' => [
            'label' => 'Custom Fields',
            'variables' => [
                ['key' => '{event_time}', 'description' => 'Custom event time'],
                ['key' => '{event_name}', 'description' => 'Custom event name'],
                ['key' => '{location}', 'description' => 'Custom location/coordinates'],
                ['key' => '{notes}', 'description' => 'Additional notes or details'],
            ]
        ]
    ];
}

switch ($action) {
    case 'get_variables':
        // Return available variables list
        echo json_encode([
            'success' => true,
            'variables' => get_available_variables()
        ]);

        log_audit_event('discord_templates_variables_viewed', $user->sub);
        break;

    case 'list':
        // Get templates accessible to user (global + user's alliance)
        $data = load_templates($templates_file);
        $user_alliance = get_user_alliance($user);

        $accessible_templates = array_filter($data['templates'], function($template) use ($user_alliance, $user) {
            // Global templates are accessible to all
            if ($template['scope'] === 'global') {
                return true;
            }
            // Alliance templates only accessible to same alliance
            if ($template['scope'] === 'alliance' && $template['alliance'] === $user_alliance) {
                return true;
            }
            return false;
        });

        echo json_encode([
            'success' => true,
            'templates' => array_values($accessible_templates),
            'user_alliance' => $user_alliance
        ]);

        log_audit_event('discord_templates_list', $user->sub, [
            'template_count' => count($accessible_templates)
        ]);
        break;

    case 'create':
        // Create new template
        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['name', 'content'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
                exit();
            }
        }

        $scope = $input['scope'] ?? 'alliance';
        $submit_for_global = $input['submit_for_global'] ?? false;
        $user_alliance = get_user_alliance($user);

        // If scope is global but user is not admin, treat as submission
        if ($scope === 'global' && $user->aud !== 'admin') {
            $scope = 'alliance'; // Store as alliance template
            $submit_for_global = true; // Mark for submission
        }

        // Alliance-specific templates require alliance
        if ($scope === 'alliance' && !$user_alliance) {
            echo json_encode(['success' => false, 'error' => 'Alliance required for alliance-specific templates']);
            exit();
        }

        // Extract variables used in template
        $variables_used = [];
        preg_match_all('/\{([^}]+)\}/', $input['content'], $matches);
        if (!empty($matches[1])) {
            $variables_used = array_unique($matches[1]);
        }

        $template = [
            'id' => uniqid('tpl_', true),
            'name' => $input['name'],
            'content' => $input['content'],
            'variables_used' => array_map(function($v) { return '{' . $v . '}'; }, $variables_used),
            'scope' => $scope,
            'alliance' => $scope === 'alliance' ? $user_alliance : null,
            'created_by' => $user->sub,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $data = load_templates($templates_file);
        $data['templates'][] = $template;

        // If submitting for global approval
        if ($submit_for_global && $user->aud !== 'admin') {
            $submission = [
                'id' => uniqid('sub_', true),
                'template_id' => $template['id'],
                'template_name' => $template['name'],
                'template_content' => $template['content'],
                'submitted_by' => $user->sub,
                'submitted_at' => date('Y-m-d H:i:s'),
                'status' => 'pending'
            ];
            $data['pending_submissions'][] = $submission;
        }

        if (save_templates($templates_file, $data)) {
            $response = ['success' => true, 'template' => $template];

            if ($submit_for_global && $user->aud !== 'admin') {
                $response['message'] = 'Template created and submitted for global approval';
            }

            echo json_encode($response);

            log_audit_event('discord_template_create', $user->sub, [
                'template_id' => $template['id'],
                'template_name' => $template['name'],
                'scope' => $scope,
                'submitted_for_global' => $submit_for_global
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save template']);
        }
        break;

    case 'delete':
        // Delete template
        $template_id = $_POST['template_id'] ?? '';

        if (empty($template_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing template_id']);
            exit();
        }

        $data = load_templates($templates_file);
        $found = false;

        foreach ($data['templates'] as $key => $template) {
            if ($template['id'] === $template_id) {
                // Check permissions: creator or admin
                if ($template['created_by'] !== $user->sub && $user->aud !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Only the creator or admin can delete this template']);
                    exit();
                }

                // Cannot delete global templates unless admin
                if ($template['scope'] === 'global' && $user->aud !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Only admins can delete global templates']);
                    exit();
                }

                unset($data['templates'][$key]);
                $data['templates'] = array_values($data['templates']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Template not found']);
            exit();
        }

        if (save_templates($templates_file, $data)) {
            echo json_encode(['success' => true]);

            log_audit_event('discord_template_delete', $user->sub, [
                'template_id' => $template_id
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete template']);
        }
        break;

    case 'list_submissions':
        // List pending global submissions (admin only)
        if ($user->aud !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
            exit();
        }

        $data = load_templates($templates_file);
        $pending = array_filter($data['pending_submissions'], function($sub) {
            return $sub['status'] === 'pending';
        });

        echo json_encode([
            'success' => true,
            'submissions' => array_values($pending)
        ]);

        log_audit_event('discord_template_submissions_viewed', $user->sub);
        break;

    case 'approve_submission':
        // Approve global template submission (admin only)
        if ($user->aud !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
            exit();
        }

        $submission_id = $_POST['submission_id'] ?? '';

        if (empty($submission_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing submission_id']);
            exit();
        }

        $data = load_templates($templates_file);
        $found_submission = false;
        $template_id = null;

        // Find and update submission
        foreach ($data['pending_submissions'] as &$sub) {
            if ($sub['id'] === $submission_id && $sub['status'] === 'pending') {
                $sub['status'] = 'approved';
                $sub['reviewed_by'] = $user->sub;
                $sub['reviewed_at'] = date('Y-m-d H:i:s');
                $template_id = $sub['template_id'];
                $found_submission = true;
                break;
            }
        }

        if (!$found_submission) {
            echo json_encode(['success' => false, 'error' => 'Submission not found or already processed']);
            exit();
        }

        // Update template to global scope
        foreach ($data['templates'] as &$template) {
            if ($template['id'] === $template_id) {
                $template['scope'] = 'global';
                $template['alliance'] = null;
                $template['approved_by'] = $user->sub;
                $template['approved_at'] = date('Y-m-d H:i:s');
                break;
            }
        }

        if (save_templates($templates_file, $data)) {
            echo json_encode(['success' => true]);

            log_audit_event('discord_template_approved', $user->sub, [
                'submission_id' => $submission_id,
                'template_id' => $template_id
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to approve submission']);
        }
        break;

    case 'reject_submission':
        // Reject global template submission (admin only)
        if ($user->aud !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
            exit();
        }

        $submission_id = $_POST['submission_id'] ?? '';
        $reason = $_POST['reason'] ?? 'No reason provided';

        if (empty($submission_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing submission_id']);
            exit();
        }

        $data = load_templates($templates_file);
        $found = false;

        foreach ($data['pending_submissions'] as &$sub) {
            if ($sub['id'] === $submission_id && $sub['status'] === 'pending') {
                $sub['status'] = 'rejected';
                $sub['reviewed_by'] = $user->sub;
                $sub['reviewed_at'] = date('Y-m-d H:i:s');
                $sub['rejection_reason'] = $reason;
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Submission not found or already processed']);
            exit();
        }

        if (save_templates($templates_file, $data)) {
            echo json_encode(['success' => true]);

            log_audit_event('discord_template_rejected', $user->sub, [
                'submission_id' => $submission_id,
                'reason' => $reason
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to reject submission']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

} catch (Throwable $e) {
    // Log the error
    error_log('Discord Templates API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

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
