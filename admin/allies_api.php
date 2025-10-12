<?php
/**
 * Alliance API - Edit alliance data
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

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
    $alliance = null;
    $index = -1;

    foreach ($alliances_data['alliances'] as $i => $a) {
        if (strtolower($a['tag'] ?? '') === strtolower($tag)) {
            $alliance = $a;
            $index = $i;
            break;
        }
    }

    if (!$alliance) {
        die('Alliance not found.');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update alliance data
        $alliances_data['alliances'][$index]['r5'] = $_POST['r5'] ?? $alliance['r5'];
        $alliances_data['alliances'][$index]['name'] = $_POST['name'] ?? $alliance['name'];
        $alliances_data['alliances'][$index]['signed'] = isset($_POST['signed']);

        write_json_file(ALLIANCES_FILE, $alliances_data);

        header('Location: dashboard.php');
        exit;
    }

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
        </style>
    </head>
    <body>
        <h1>Edit Alliance: <?= htmlspecialchars($alliance['tag']) ?></h1>
        <form method="POST">
            <div class="form-group">
                <label>Alliance Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($alliance['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>R5 Name:</label>
                <input type="text" name="r5" value="<?= htmlspecialchars($alliance['r5'] ?? '') ?>" required>
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