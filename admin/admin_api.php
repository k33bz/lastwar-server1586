<?php
/**
 * Admin API - User management (admin only)
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

$user_token = require_admin_session();

// Handle add user
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email']));
        $alliances = array_map('trim', explode(',', $_POST['alliances']));
        $role = $_POST['role'] ?? 'alliance';

        if (add_user($email, $alliances, $role)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Failed to add user';
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Add User</title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input, select { width: 100%; padding: 8px; }
            button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Add New User</h1>
        <?php if (isset($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Alliances (comma-separated tags):</label>
                <input type="text" name="alliances" placeholder="uvvu,orce" required>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <select name="role">
                    <option value="alliance">Alliance User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit">Add User</button>
            <a href="dashboard.php">Cancel</a>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Handle edit user
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['email'])) {
    $email = $_GET['email'];
    $user = get_user_by_email($email);

    if (!$user) {
        die('User not found');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete'])) {
            delete_user($email);
            header('Location: dashboard.php');
            exit;
        } else {
            $alliances = array_map('trim', explode(',', $_POST['alliances']));
            $role = $_POST['role'];
            update_user($email, $alliances, $role);
            header('Location: dashboard.php');
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit User</title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input, select { width: 100%; padding: 8px; }
            button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
            .btn-primary { background: #667eea; color: white; }
            .btn-danger { background: #e74c3c; color: white; }
        </style>
    </head>
    <body>
        <h1>Edit User: <?= htmlspecialchars($email) ?></h1>
        <form method="POST">
            <div class="form-group">
                <label>Alliances (comma-separated):</label>
                <input type="text" name="alliances" value="<?= htmlspecialchars(implode(', ', $user['alliances'])) ?>" required>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <select name="role">
                    <option value="alliance" <?= $user['role'] === 'alliance' ? 'selected' : '' ?>>Alliance User</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Update User</button>
            <button type="submit" name="delete" class="btn-danger" onclick="return confirm('Are you sure?')">Delete User</button>
            <a href="dashboard.php">Cancel</a>
        </form>
    </body>
    </html>
    <?php
    exit;
}

http_response_code(404);
echo 'Invalid action';
?>