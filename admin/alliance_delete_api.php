<?php
/**
 * Alliance Delete API
 *
 * API endpoint for deleting alliances from the system (admin-only)
 *
 * Documentation:
 * - Alliance Management Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ALLIANCE_MANAGEMENT_GUIDE.md
 * - Alliance Data Schema: https://github.com/k33bz/lastwar-server1586/blob/mainline/data/ALLIANCE_SCHEMA.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * Actions:
 * - delete_alliance: Delete alliance by tag from alliances.json
 *
 * @version 1.0.0
 * @date 2025-10-15
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation
 *                       - Admin-only access control
 *                       - Delete alliance by tag
 *                       - Confirmation via POST action
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config.php';
    require_once 'jwt.php';
    require_once 'json_helpers.php';
    require_once 'audit_logger.php';
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load dependencies: ' . $e->getMessage()]);
    exit;
}

// Require admin authentication (only admins can delete)
$user = require_admin_session();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Path to alliances.json (parent directory)
$alliances_file = __DIR__ . '/../data/alliances.json';

try {
    if ($action === 'delete_alliance') {
        $tag = $_POST['tag'] ?? '';

        if (empty($tag)) {
            http_response_code(400);
            echo json_encode(['error' => 'Alliance tag required']);
            exit;
        }

        // Load current alliances
        $alliances = json_read($alliances_file);

        // Find alliance index by tag
        $index_to_delete = null;
        foreach ($alliances as $index => $alliance) {
            if (strcasecmp($alliance['tag'] ?? '', $tag) === 0) {
                $index_to_delete = $index;
                break;
            }
        }

        if ($index_to_delete === null) {
            http_response_code(404);
            echo json_encode(['error' => "Alliance with tag '{$tag}' not found"]);
            exit;
        }

        $deleted_name = $alliances[$index_to_delete]['name'] ?? 'Unknown';

        // Remove alliance from array
        array_splice($alliances, $index_to_delete, 1);

        // Recalculate ranks based on array order
        foreach ($alliances as $index => $alliance) {
            $alliances[$index]['rank'] = $index + 1;
        }

        // Save updated alliances
        json_write($alliances_file, $alliances);

        // Log audit event
        log_audit_event('alliance_deleted', $user->sub, [
            'alliance_tag' => $tag,
            'alliance_name' => $deleted_name,
            'previous_rank' => $index_to_delete + 1
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Alliance '{$tag}' ({$deleted_name}) deleted successfully"
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
