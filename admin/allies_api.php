<?php
/**
 * Alliance API - Edit alliance data
 *
 * Documentation:
 * - Alliance Management Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ALLIANCE_MANAGEMENT_GUIDE.md
 * - Alliance Data Schema: https://github.com/k33bz/lastwar-server1586/blob/mainline/data/ALLIANCE_SCHEMA.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

$user_token = require_jwt_session();

// Handle edit action
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['tag'])) {
    $tag = $_GET['tag'];

    // Check permission
    if (!has_alliance_access($user_token, $tag)) {
        http_response_code(403);
        die('Access denied to this alliance.');
    }

    // Load alliances data
    $alliances_data = read_json_file(ALLIANCES_FILE);
    // Handle both array format and object format
    $alliances_array = is_array($alliances_data) && isset($alliances_data[0]) ? $alliances_data : ($alliances_data['alliances'] ?? []);
    $alliance = null;
    $index = -1;

    foreach ($alliances_array as $i => $a) {
        if (strtolower($a['tag'] ?? '') === strtolower($tag)) {
            $alliance = $a;
            $index = $i;
            break;
        }
    }

    if (!$alliance) {
        die('Alliance not found.');
    }

    // Helper function to extract R5 name from either string or object format
    function get_r5_name($r5_data) {
        if (is_string($r5_data)) {
            return $r5_data;
        } elseif (is_array($r5_data) && isset($r5_data['name'])) {
            return $r5_data['name'];
        }
        return '';
    }

    // Helper function to set R5 data maintaining the original format
    function set_r5_name($original_r5, $new_name) {
        if (is_array($original_r5) && isset($original_r5['name'])) {
            // Keep the object format, just update the name
            $original_r5['name'] = $new_name;
            return $original_r5;
        }
        // Return as string
        return $new_name;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Protection
        requireCsrfToken();

        // Update alliance data
        $alliances_array[$index]['r5'] = set_r5_name($alliance['r5'], $_POST['r5'] ?? '');
        $alliances_array[$index]['name'] = $_POST['name'] ?? $alliance['name'];
        $alliances_array[$index]['signed'] = isset($_POST['signed']);

        // Write back in the original format (array)
        write_json_file(ALLIANCES_FILE, $alliances_array);

        // Log audit event
        log_audit_event('alliance_edited', $user_token->sub, [
            'alliance_tag' => $tag,
            'changes' => [
                'name' => $_POST['name'] ?? $alliance['name'],
                'r5' => $_POST['r5'] ?? $r5_name,
                'signed' => isset($_POST['signed'])
            ]
        ]);

        header('Location: dashboard.php');
        exit;
    }

    $r5_name = get_r5_name($alliance['r5'] ?? null);

    // Display edit form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Alliance - <?= htmlspecialchars($alliance['tag']) ?></title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            h1 { color: #333; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"] { width: 100%; padding: 8px; font-size: 14px; }
            input[type="checkbox"] { margin-right: 5px; }
            button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
            .btn-secondary { background: #666; margin-left: 10px; }
            .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .info p { margin: 5px 0; color: #2c3e50; }
        </style>
    </head>
    <body>
        <h1>Edit Alliance: <?= htmlspecialchars($alliance['tag']) ?></h1>

        <div class="info">
            <p><strong>Rank:</strong> <?= htmlspecialchars($alliance['rank'] ?? 'N/A') ?></p>
            <p><strong>Power:</strong> <?= isset($alliance['power']) ? number_format($alliance['power']) : 'N/A' ?></p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Alliance Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($alliance['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>R5 Name:</label>
                <input type="text" name="r5" value="<?= htmlspecialchars($r5_name) ?>" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="signed" <?= ($alliance['signed'] ?? false) ? 'checked' : '' ?>>
                    NAP Agreement Signed
                </label>
            </div>
            <button type="submit">Save Changes</button>
            <a href="dashboard.php" class="btn-secondary" style="display:inline-block;padding:10px 20px;text-decoration:none;color:white;background:#666;border-radius:5px;">Cancel</a>
        </form>
    </body>
    </html>
    <?php
    exit;
}

http_response_code(404);
echo 'Invalid action';
?>