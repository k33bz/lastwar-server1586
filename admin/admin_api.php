<?php
/**
 * Admin API - User management (admin and R5)
 *
 * @version 1.5.0
 * @date 2025-10-13
 * @changelog
 *   1.5.0 (2025-10-13) - Revoke all JWT tokens when user is deleted
 *                       - Blacklist all active_sessions before calling delete_user()
 *                       - Prevents deleted users from accessing system with existing tokens
 *   1.4.0 (2025-10-13) - Implement R5 demotion rules
 *                       - R5 can promote users to R5 (already allowed)
 *                       - R5 cannot demote another R5 to R4 (new validation)
 *                       - R5 can only demote themselves to R4 (new validation)
 *   1.3.0 (2025-10-13) - Removed legacy "alliance" role
 *                       - Only Admin, R5, and R4 roles supported
 *                       - Default role changed from "alliance" to "r4"
 *   1.2.0 (2025-10-13) - Added alliance-based access control for R5
 *                       - R5 can only manage users from their assigned alliances
 *                       - R5 can only assign alliances they have access to
 *                       - UI filters alliance checkboxes based on R5's access
 *   1.1.0 (2025-10-13) - Added R5 user management capability
 *                       - R5 can create/edit R5 and R4 users (not admin)
 *                       - Added role validation based on current user's role
 *   1.0.0 (2025-10-12) - Initial implementation (admin only)
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

// Require either admin or R5 session
$user_token = require_jwt_session();
$is_admin = ($user_token->aud === 'admin');
$is_r5 = ($user_token->aud === 'r5');

// Only admin and R5 can access user management
if (!$is_admin && !$is_r5) {
    http_response_code(403);
    die('Access denied. Admin or R5 privileges required.');
}

// Load available alliances
$alliances_data = read_json_file(ALLIANCES_FILE);
// Handle both array format and object format
$available_alliances = is_array($alliances_data) && isset($alliances_data[0]) ? $alliances_data : ($alliances_data['alliances'] ?? []);

// Handle add user
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email']));

        // Get selected alliances from checkboxes
        $alliances = [];
        if (isset($_POST['alliance_all'])) {
            $alliances = ['*'];
        } else {
            $alliances = $_POST['alliances'] ?? [];
        }

        $role = $_POST['role'] ?? 'r4';

        // R5 validation: cannot create admin users or grant all alliances
        if ($is_r5) {
            if ($role === 'admin') {
                $error = 'R5 users cannot create admin accounts';
            } elseif (in_array('*', $alliances)) {
                $error = 'R5 users cannot grant access to all alliances';
            } elseif (!in_array($role, ['r5', 'r4'])) {
                $error = 'R5 users can only create R5 or R4 users';
            } else {
                // R5 can only assign alliances they have access to
                $r5_alliances = $user_token->alliances;
                foreach ($alliances as $alliance) {
                    if (!in_array($alliance, $r5_alliances) && !in_array('*', $r5_alliances)) {
                        $error = 'R5 users can only assign alliances they have access to';
                        break;
                    }
                }
            }
        }

        if (!isset($error)) {
            if (add_user($email, $alliances, $role)) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Failed to add user';
            }
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
            input[type="email"], select { width: 100%; padding: 8px; }
            button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; }
            .btn-secondary { padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px; }
            .btn-secondary:hover { background: #5a6268; }
            .checkbox-group { border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto; }
            .checkbox-item { margin: 8px 0; }
            .checkbox-item input { margin-right: 8px; }
            .checkbox-all { background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 3px; }
        </style>
        <script>
            function toggleAllAlliances(checkbox) {
                const checkboxes = document.querySelectorAll('input[name="alliances[]"]');
                checkboxes.forEach(cb => cb.disabled = checkbox.checked);
            }
        </script>
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
                <label>Alliances:</label>
                <div class="checkbox-group">
                    <?php if ($is_admin): ?>
                        <div class="checkbox-all">
                            <label class="checkbox-item">
                                <input type="checkbox" name="alliance_all" onchange="toggleAllAlliances(this)">
                                <strong>All Alliances (*)</strong>
                            </label>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($available_alliances as $alliance):
                        // R5 users can only see their assigned alliances
                        if ($is_r5 && !in_array($alliance['tag'], $user_token->alliances) && !in_array('*', $user_token->alliances)) {
                            continue;
                        }
                    ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="alliances[]" value="<?= htmlspecialchars($alliance['tag']) ?>">
                            <?= htmlspecialchars($alliance['tag']) ?> - <?= htmlspecialchars($alliance['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <select name="role">
                    <option value="r4">R4 (Can edit all alliance data)</option>
                    <option value="r5">R5 (Can edit + sign rules)</option>
                    <?php if ($is_admin): ?>
                        <option value="admin">Admin (Full access)</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit">Add User</button>
            <a href="dashboard.php" class="btn-secondary">Cancel</a>
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

    // R5 validation: cannot edit admin users
    if ($is_r5 && $user['role'] === 'admin') {
        http_response_code(403);
        die('R5 users cannot edit admin accounts');
    }

    // R5 validation: can only edit users from their alliances
    if ($is_r5) {
        $r5_alliances = $user_token->alliances;
        $target_alliances = $user['alliances'];

        // Check if R5 has access to at least one of target user's alliances
        $has_overlap = false;
        foreach ($target_alliances as $alliance) {
            if (in_array($alliance, $r5_alliances) || in_array('*', $r5_alliances)) {
                $has_overlap = true;
                break;
            }
        }

        if (!$has_overlap) {
            http_response_code(403);
            die('R5 users can only edit users from their assigned alliances');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete'])) {
            // R5 validation: cannot delete admin users
            if ($is_r5 && $user['role'] === 'admin') {
                http_response_code(403);
                die('R5 users cannot delete admin accounts');
            }

            // Revoke all JWT tokens for the user before deletion
            if (isset($user['active_sessions']) && is_array($user['active_sessions'])) {
                foreach ($user['active_sessions'] as $session) {
                    if (isset($session['jti']) && isset($session['exp'])) {
                        blacklist_token($session['jti'], $session['exp']);
                    }
                }
            }

            delete_user($email);
            header('Location: dashboard.php');
            exit;
        } else {
            // Get selected alliances from checkboxes
            $alliances = [];
            if (isset($_POST['alliance_all'])) {
                $alliances = ['*'];
            } else {
                $alliances = $_POST['alliances'] ?? [];
            }

            $role = $_POST['role'];

            // R5 validation: cannot change user to admin or grant all alliances
            if ($is_r5) {
                if ($role === 'admin') {
                    $error = 'R5 users cannot promote users to admin';
                } elseif (in_array('*', $alliances)) {
                    $error = 'R5 users cannot grant access to all alliances';
                } elseif (!in_array($role, ['r5', 'r4'])) {
                    $error = 'R5 users can only manage R5 or R4 users';
                } else {
                    // Check if trying to demote an R5 user to R4
                    if ($user['role'] === 'r5' && $role === 'r4') {
                        // Get logged-in user's email
                        $current_user_email = strtolower($user_token->sub);

                        // Only allow if demoting self
                        if (strtolower($email) !== $current_user_email) {
                            $error = 'R5 users can only demote themselves, not other R5 users';
                        }
                    }

                    // R5 can only assign alliances they have access to
                    if (!isset($error)) {
                        $r5_alliances = $user_token->alliances;
                        foreach ($alliances as $alliance) {
                            if (!in_array($alliance, $r5_alliances) && !in_array('*', $r5_alliances)) {
                                $error = 'R5 users can only assign alliances they have access to';
                                break;
                            }
                        }
                    }
                }
            }

            if (!isset($error)) {
                update_user($email, $alliances, $role);
                header('Location: dashboard.php');
                exit;
            }
        }
    }

    $user_has_all = in_array('*', $user['alliances']);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit User</title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            select { width: 100%; padding: 8px; }
            button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
            .btn-primary { background: #667eea; color: white; }
            .btn-danger { background: #e74c3c; color: white; }
            .btn-secondary { padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px; }
            .btn-secondary:hover { background: #5a6268; }
            .checkbox-group { border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto; }
            .checkbox-item { margin: 8px 0; }
            .checkbox-item input { margin-right: 8px; }
            .checkbox-all { background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 3px; }
        </style>
        <script>
            function toggleAllAlliances(checkbox) {
                const checkboxes = document.querySelectorAll('input[name="alliances[]"]');
                checkboxes.forEach(cb => cb.disabled = checkbox.checked);
            }
        </script>
    </head>
    <body>
        <h1>Edit User: <?= htmlspecialchars($email) ?></h1>
        <?php if (isset($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Alliances:</label>
                <div class="checkbox-group">
                    <?php if ($is_admin): ?>
                        <div class="checkbox-all">
                            <label class="checkbox-item">
                                <input type="checkbox" name="alliance_all" onchange="toggleAllAlliances(this)" <?= $user_has_all ? 'checked' : '' ?>>
                                <strong>All Alliances (*)</strong>
                            </label>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($available_alliances as $alliance):
                        // R5 users can only see their assigned alliances
                        if ($is_r5 && !in_array($alliance['tag'], $user_token->alliances) && !in_array('*', $user_token->alliances)) {
                            continue;
                        }
                        $is_checked = in_array($alliance['tag'], $user['alliances']);
                    ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="alliances[]" value="<?= htmlspecialchars($alliance['tag']) ?>" <?= $is_checked ? 'checked' : '' ?> <?= $user_has_all ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($alliance['tag']) ?> - <?= htmlspecialchars($alliance['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <select name="role">
                    <option value="r4" <?= $user['role'] === 'r4' ? 'selected' : '' ?>>R4 (Can edit all alliance data)</option>
                    <option value="r5" <?= $user['role'] === 'r5' ? 'selected' : '' ?>>R5 (Can edit + sign rules)</option>
                    <?php if ($is_admin): ?>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin (Full access)</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Update User</button>
            <button type="submit" name="delete" class="btn-danger" onclick="return confirm('Are you sure?')">Delete User</button>
            <a href="dashboard.php" class="btn-secondary">Cancel</a>
        </form>
    </body>
    </html>
    <?php
    exit;
}

http_response_code(404);
echo 'Invalid action';
?>