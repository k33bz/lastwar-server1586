<?php
/**
 * Alliance Power Editor API
 *
 * CRUD operations for alliance power management
 * Admins only - edit tag, name, and power for all alliances
 *
 * Documentation:
 * - Alliance Management Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ALLIANCE_MANAGEMENT_GUIDE.md
 * - Alliance Data Schema: https://github.com/k33bz/lastwar-server1586/blob/mainline/data/ALLIANCE_SCHEMA.md
 * - User Personas (Roles): https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/USER-PERSONAS.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * Actions:
 * - list: Get all alliances
 * - update: Update all alliances (bulk save)
 * - add: Add new alliance
 * - delete: Remove alliance
 *
 * @version 2.2.0
 * @date 2025-10-15
 * @changelog
 *   2.2.0 (2025-10-15) - Added CSV power history with datetime stamps
 *                       - Auto-appends to CSV on power edits
 *                       - Updates CSV header when alliances added/deleted
 *   2.1.0 (2025-10-15) - Added audit logging and automatic backups
 *                       - Backup created before every update/add/delete
 *                       - Logs track who changed what and when
 *   2.0.0 (2025-10-15) - Added powereditor role support
 *                       - Power editors can list, update, and add alliances
 *                       - Only admins can delete alliances
 *   1.0.2 (2025-10-15) - Fixed JWT token object/array access bug ($user->aud)
 *   1.0.1 (2025-10-15) - Added error reporting and try-catch blocks for debugging
 *   1.0.0 (2025-10-14) - Initial implementation
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config.php';
    require_once 'jwt.php';
    require_once 'json_helpers.php';
    require_once 'audit_logger.php';
    require_once 'csv_helpers.php';
    require_once 'includes/alliance_helper.php';
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load dependencies: ' . $e->getMessage()]);
    exit;
}

// Require admin or power editor authentication
$user = require_jwt_session();

if (!is_power_editor($user)) {
    http_response_code(403);
    echo json_encode(['error' => 'Power editor access required']);
    exit;
}

// Only admins can delete
$can_delete = can_delete_alliances($user);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Path to alliances.json (parent directory)
$alliances_file = __DIR__ . '/../data/alliances.json';

try {
    switch ($action) {
        case 'list':
            // Return all alliances with tag, name, power
            $alliances = json_read($alliances_file);

        // Extract only needed fields and add index for editing
        $simplified = array_map(function($alliance, $index) {
            return [
                'index' => $index,
                'tag' => $alliance['tag'] ?? '',
                'name' => $alliance['name'] ?? '',
                'power' => $alliance['power'] ?? 0
            ];
        }, $alliances, array_keys($alliances));

        echo json_encode(['alliances' => $simplified]);
        break;

    case 'update':
        // Bulk update all alliances
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['alliances']) || !is_array($input['alliances'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input: alliances array required']);
            exit;
        }

        // Load current alliances
        $alliances = json_read($alliances_file);

        // Create backup before making changes
        backup_alliances($alliances, $user->sub, 'power_edit');

        // Track changes for audit log
        $changes = [];

        // Update each alliance's tag, name, and power
        foreach ($input['alliances'] as $update) {
            $index = $update['index'] ?? null;

            if ($index === null || !isset($alliances[$index])) {
                continue; // Skip invalid indices
            }

            $alliance_tag = $alliances[$index]['tag'];
            $alliance_changes = [];

            // Track and update tag
            if (isset($update['tag']) && $update['tag'] !== $alliances[$index]['tag']) {
                $alliance_changes['tag'] = [
                    'old' => $alliances[$index]['tag'],
                    'new' => trim($update['tag'])
                ];
                $alliances[$index]['tag'] = trim($update['tag']);
            }

            // Track and update name
            if (isset($update['name']) && $update['name'] !== $alliances[$index]['name']) {
                $alliance_changes['name'] = [
                    'old' => $alliances[$index]['name'],
                    'new' => trim($update['name'])
                ];
                $alliances[$index]['name'] = trim($update['name']);
            }

            // Track and update power
            if (isset($update['power']) && (int)$update['power'] !== $alliances[$index]['power']) {
                $alliance_changes['power'] = [
                    'old' => $alliances[$index]['power'],
                    'new' => (int)$update['power']
                ];
                $alliances[$index]['power'] = (int)$update['power'];
            }

            // Add to changes log if anything changed
            if (!empty($alliance_changes)) {
                $changes[$alliance_tag] = $alliance_changes;
            }
        }

        // Save updated alliances
        json_write($alliances_file, $alliances);

        // Update CSV using helper (more comprehensive than append_power_snapshot)
        AllianceHelper::updateAllianceCSV($alliances);

        // Log audit event with changes
        log_audit_event('edit_alliance_power', $user->sub, [
            'alliances_modified' => count($changes),
            'changes' => $changes
        ]);

        echo json_encode(['success' => true, 'message' => 'Alliances updated successfully']);
        break;

    case 'add':
        // Add new alliance
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['tag']) || !isset($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag and name required']);
            exit;
        }

        $alliances = json_read($alliances_file);

        // Check if tag already exists
        foreach ($alliances as $alliance) {
            if ($alliance['tag'] === $input['tag']) {
                http_response_code(400);
                echo json_encode(['error' => 'Alliance tag already exists']);
                exit;
            }
        }

        // Create backup before adding
        backup_alliances($alliances, $user->sub, 'add_alliance');

        // Create new alliance with minimal structure
        $newAlliance = [
            'tag' => trim($input['tag']),
            'name' => trim($input['name']),
            'r5' => $input['r5'] ?? 'R5 of ' . trim($input['tag']),
            'signed' => false,
            'power' => (int)($input['power'] ?? 0),
            'r5History' => [
                [
                    'r5Name' => $input['r5'] ?? 'R5 of ' . trim($input['tag']),
                    'gameId' => null,
                    'discordId' => null,
                    'startDate' => date('Y-m-d\TH:i:s\Z'),
                    'endDate' => null,
                    'current' => true,
                    'signatures' => []
                ]
            ]
        ];

        // Add to alliances array
        $alliances[] = $newAlliance;

        json_write($alliances_file, $alliances);

        // Update CSV header with new alliance
        update_csv_header($alliances);

        // Append power snapshot with new alliance
        append_power_snapshot($alliances);

        // Log audit event
        log_audit_event('add_alliance', $user->sub, [
            'alliance_tag' => $newAlliance['tag'],
            'alliance_name' => $newAlliance['name'],
            'initial_power' => $newAlliance['power']
        ]);

        echo json_encode(['success' => true, 'message' => 'Alliance added successfully']);
        break;

    case 'delete':
        // Delete alliance by index (admin only)
        if (!$can_delete) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required to delete alliances']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['index'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Alliance index required']);
            exit;
        }

        $alliances = json_read($alliances_file);
        $index = (int)$input['index'];

        if (!isset($alliances[$index])) {
            http_response_code(404);
            echo json_encode(['error' => 'Alliance not found']);
            exit;
        }

        // Create backup before deleting
        backup_alliances($alliances, $user->sub, 'delete_alliance');

        $deletedTag = $alliances[$index]['tag'];
        $deletedName = $alliances[$index]['name'];
        $deletedPower = $alliances[$index]['power'];

        // Remove alliance from array
        array_splice($alliances, $index, 1);

        json_write($alliances_file, $alliances);

        // Update CSV header (remove deleted alliance column)
        update_csv_header($alliances);

        // Append power snapshot with deleted alliance removed
        append_power_snapshot($alliances);

        // Log audit event
        log_audit_event('delete_alliance', $user->sub, [
            'alliance_tag' => $deletedTag,
            'alliance_name' => $deletedName,
            'alliance_power' => $deletedPower,
            'index' => $index
        ]);

        echo json_encode(['success' => true, 'message' => "Alliance '{$deletedTag}' deleted successfully"]);
        break;

    case 'update_single_power':
        // Update single alliance power using helper
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['tag']) || !isset($input['power'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag and power required']);
            exit;
        }
        
        $tag = trim($input['tag']);
        $new_power = (int)$input['power'];
        
        if ($new_power < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Power cannot be negative']);
            exit;
        }
        
        // Use helper to update power (handles CSV, history, etc.)
        $result = AllianceHelper::updateAlliancePower($tag, $new_power, $user);
        
        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
            exit;
        }
        
        // Log audit event
        log_audit_event('update_single_alliance_power', $user->sub, [
            'alliance_tag' => $tag,
            'old_power' => $result['old_power'],
            'new_power' => $result['new_power'],
            'power_change' => $result['power_change'],
            'new_rank' => $result['new_rank']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Alliance power updated successfully',
            'data' => [
                'alliance_tag' => $tag,
                'old_power' => $result['old_power'],
                'new_power' => $result['new_power'],
                'power_change' => $result['power_change'],
                'new_rank' => $result['new_rank']
            ]
        ]);
        break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
