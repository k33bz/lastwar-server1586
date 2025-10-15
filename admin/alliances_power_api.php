<?php
/**
 * Alliance Power Editor API
 *
 * CRUD operations for alliance power management
 * Admins only - edit tag, name, and power for all alliances
 *
 * Actions:
 * - list: Get all alliances
 * - update: Update all alliances (bulk save)
 * - add: Add new alliance
 * - delete: Remove alliance
 *
 * @version 1.0.1
 * @date 2025-10-15
 * @changelog
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
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load dependencies: ' . $e->getMessage()]);
    exit;
}

// Require admin authentication
$user = require_jwt_session();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

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

        // Update each alliance's tag, name, and power
        foreach ($input['alliances'] as $update) {
            $index = $update['index'] ?? null;

            if ($index === null || !isset($alliances[$index])) {
                continue; // Skip invalid indices
            }

            // Update only tag, name, and power
            if (isset($update['tag'])) {
                $alliances[$index]['tag'] = trim($update['tag']);
            }
            if (isset($update['name'])) {
                $alliances[$index]['name'] = trim($update['name']);
            }
            if (isset($update['power'])) {
                $alliances[$index]['power'] = (int)$update['power'];
            }
        }

        // Save updated alliances
        json_write($alliances_file, $alliances);

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

        echo json_encode(['success' => true, 'message' => 'Alliance added successfully']);
        break;

    case 'delete':
        // Delete alliance by index
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

        $deletedTag = $alliances[$index]['tag'];

        // Remove alliance from array
        array_splice($alliances, $index, 1);

        json_write($alliances_file, $alliances);

        echo json_encode(['success' => true, 'message' => "Alliance '{$deletedTag}' deleted successfully"]);
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
