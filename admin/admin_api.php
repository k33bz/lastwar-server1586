<?php
/**
 * Admin API - User management (admin and R5)
 *
 * Documentation:
 * - User Personas (Roles): https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/USER-PERSONAS.md
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 * - Alliance Management Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ALLIANCE_MANAGEMENT_GUIDE.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.6.0
 * @date 2025-10-15
 * @changelog
 *   1.6.0 (2025-10-15) - Added powereditor role support
 *                       - Checkbox for powereditor flag in add/edit forms (admin-only)
 *                       - R5 users cannot grant powereditor access
 *                       - Admin role users cannot have powereditor flag (automatic access)
 *                       - Power editor checkbox hidden when admin role selected
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

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Set page title for header
$page_title = "User Management";

// Use JWT user token
$user_token = $user;

$is_admin = ($user->aud === 'admin');
$is_r5 = ($user->aud === 'r5');

// Include proper helper functions
require_once 'json_helpers.php';
require_once 'audit_logger.php';

define('ALLIANCES_FILE', __DIR__ . '/../data/alliances.json');

// Load available alliances
$alliances_data = read_json_file(ALLIANCES_FILE);
// Handle both array format and object format
$available_alliances = is_array($alliances_data) && isset($alliances_data[0]) ? $alliances_data : ($alliances_data['alliances'] ?? []);

// Handle add user
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Protection
        requireCsrfToken();

        $email = strtolower(trim($_POST['email']));

        // Get selected alliances from checkboxes
        $alliances = [];
        if (isset($_POST['alliance_all'])) {
            $alliances = ['*'];
        } else {
            $alliances = $_POST['alliances'] ?? [];
        }

        $role = $_POST['role'] ?? 'r4';
        $powereditor = isset($_POST['powereditor']) && $_POST['powereditor'] === '1';

        // R5 validation: cannot create admin users or grant all alliances
        if ($is_r5) {
            if ($role === 'admin') {
                $error = 'R5 users cannot create admin accounts';
            } elseif (in_array('*', $alliances)) {
                $error = 'R5 users cannot grant access to all alliances';
            } elseif (!in_array($role, ['r5', 'r4'])) {
                $error = 'R5 users can only create R5 or R4 users';
            } elseif ($powereditor) {
                $error = 'R5 users cannot grant power editor access';
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

        // Admins cannot have powereditor flag (they automatically have access)
        if ($role === 'admin') {
            $powereditor = false;
        }

        if (!isset($error)) {
            try {
                $success = add_user_data($email, $alliances, $role, $powereditor);
                if ($success) {
                    log_audit_event('user_added', $user->sub, [
                        'target_email' => $email,
                        'role' => $role,
                        'powereditor' => $powereditor,
                        'alliances' => $alliances
                    ]);
                    header('Location: user_management.php?success=user_added');
                    exit;
                } else {
                    $error = 'Failed to add user';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
    // Include shared header
    include 'includes/header.php';
    ?>

    <div class="page-header">
        <h1 class="page-title">👥 Add New User</h1>
        <p class="page-description">Create a new user account with role and alliance assignments</p>
    </div>

    <div class="container">
        <style>
            .container {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 2rem;
                max-width: 800px;
            }
            .form-group { 
                margin-bottom: 1.5rem; 
            }
            label { 
                display: block; 
                margin-bottom: 0.5rem; 
                font-weight: 600;
                color: #333;
            }
            input[type="email"], select { 
                width: 100%; 
                padding: 0.75rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 0.9rem;
            }
            .checkbox-group { 
                border: 1px solid #ddd; 
                padding: 1rem; 
                border-radius: 4px; 
                max-height: 200px; 
                overflow-y: auto;
                background: #f8f9fa;
            }
            .checkbox-item { 
                margin: 0.5rem 0; 
            }
            .checkbox-item input { 
                margin-right: 0.5rem; 
            }
            .checkbox-all { 
                background: #e9ecef; 
                padding: 0.75rem; 
                margin-bottom: 0.75rem; 
                border-radius: 4px;
                font-weight: 600;
            }
            .actions {
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 1px solid #eee;
            }
        </style>
        <script>
            function toggleAllAlliances(checkbox) {
                const checkboxes = document.querySelectorAll('input[name="alliances[]"]');
                checkboxes.forEach(cb => cb.disabled = checkbox.checked);
            }
        </script>
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
                <select name="role" id="role-select" onchange="updatePowerEditorVisibility()">
                    <option value="r4">R4 (Can edit all alliance data)</option>
                    <option value="r5">R5 (Can edit + sign rules)</option>
                    <?php if ($is_admin): ?>
                        <option value="admin">Admin (Full access)</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php if ($is_admin): ?>
                <div class="form-group" id="powereditor-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="powereditor" value="1">
                        <strong>Power Editor</strong> - Can edit all alliance power values (but cannot delete alliances)
                    </label>
                </div>
            <?php endif; ?>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Add User</button>
                <a href="user_management.php" class="btn btn-secondary">← Back to Users</a>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <script>
            function updatePowerEditorVisibility() {
                const roleSelect = document.getElementById('role-select');
                const powerEditorGroup = document.getElementById('powereditor-group');
                if (powerEditorGroup) {
                    // Hide power editor checkbox if admin is selected
                    powerEditorGroup.style.display = (roleSelect.value === 'admin') ? 'none' : 'block';
                }
            }
            // Initial check on page load
            updatePowerEditorVisibility();
        </script>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php
    exit;
}

// Handle edit user
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['email'])) {
    $email = $_GET['email'];
    $user_data = get_user_by_email($email);

    if (!$user_data) {
        die('User not found');
    }

    // R5 validation: cannot edit admin users
    if ($is_r5 && $user_data['role'] === 'admin') {
        http_response_code(403);
        die('R5 users cannot edit admin accounts');
    }

    // R5 validation: can only edit users from their alliances
    if ($is_r5) {
        $r5_alliances = $user_token->alliances;
        $target_alliances = $user_data['alliances'];

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
        // CSRF Protection
        requireCsrfToken();

        if (isset($_POST['delete'])) {
            // R5 validation: cannot delete admin users
            if ($is_r5 && $user_data['role'] === 'admin') {
                http_response_code(403);
                die('R5 users cannot delete admin accounts');
            }

            // Revoke all JWT tokens for the user before deletion
            $active_sessions = get_active_sessions($email);
            foreach ($active_sessions as $session) {
                if (isset($session['jti']) && isset($session['exp'])) {
                    blacklist_token($session['jti'], $session['exp']);
                }
            }

            try {
                $success = delete_user_data($email);
                if ($success) {
                    log_audit_event('user_deleted', $user->sub, [
                        'target_email' => $email
                    ]);
                    header('Location: user_management.php?success=user_deleted');
                    exit;
                } else {
                    $error = 'Failed to delete user';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            // Get selected alliances from checkboxes
            $alliances = [];
            if (isset($_POST['alliance_all'])) {
                $alliances = ['*'];
            } else {
                $alliances = $_POST['alliances'] ?? [];
            }

            $role = $_POST['role'];
            $powereditor = isset($_POST['powereditor']) && $_POST['powereditor'] === '1';

            // R5 validation: cannot change user to admin or grant all alliances
            if ($is_r5) {
                if ($role === 'admin') {
                    $error = 'R5 users cannot promote users to admin';
                } elseif (in_array('*', $alliances)) {
                    $error = 'R5 users cannot grant access to all alliances';
                } elseif (!in_array($role, ['r5', 'r4'])) {
                    $error = 'R5 users can only manage R5 or R4 users';
                } elseif ($powereditor) {
                    $error = 'R5 users cannot grant power editor access';
                } else {
                    // Check if trying to demote an R5 user to R4
                    if ($user_data['role'] === 'r5' && $role === 'r4') {
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

            // Admins cannot have powereditor flag (they automatically have access)
            if ($role === 'admin') {
                $powereditor = false;
            }

            if (!isset($error)) {
                try {
                    $success = update_user_data($email, $alliances, $role, $powereditor);
                    if ($success) {
                        log_audit_event('user_updated', $user->sub, [
                            'target_email' => $email,
                            'role' => $role,
                            'powereditor' => $powereditor,
                            'alliances' => $alliances
                        ]);
                        header('Location: user_management.php?success=user_updated');
                        exit;
                    } else {
                        $error = 'Failed to update user';
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }

    $user_has_all = in_array('*', $user_data['alliances']);
    
    // Include shared header
    include 'includes/header.php';
    ?>

    <div class="page-header">
        <h1 class="page-title">✏️ Edit User: <?= htmlspecialchars($email) ?></h1>
        <p class="page-description">Modify user role and alliance assignments</p>
    </div>

    <div class="container">
        <script>
            function toggleAllAlliances(checkbox) {
                const checkboxes = document.querySelectorAll('input[name="alliances[]"]');
                checkboxes.forEach(cb => cb.disabled = checkbox.checked);
            }
        </script>

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
                        $is_checked = in_array($alliance['tag'], $user_data['alliances']);
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
                <select name="role" id="role-select" onchange="updatePowerEditorVisibility()">
                    <option value="r4" <?= $user_data['role'] === 'r4' ? 'selected' : '' ?>>R4 (Can edit all alliance data)</option>
                    <option value="r5" <?= $user_data['role'] === 'r5' ? 'selected' : '' ?>>R5 (Can edit + sign rules)</option>
                    <?php if ($is_admin): ?>
                        <option value="admin" <?= $user_data['role'] === 'admin' ? 'selected' : '' ?>>Admin (Full access)</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php if ($is_admin): ?>
                <div class="form-group" id="powereditor-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="powereditor" value="1" <?= (isset($user_data['powereditor']) && $user_data['powereditor']) ? 'checked' : '' ?>>
                        <strong>Power Editor</strong> - Can edit all alliance power values (but cannot delete alliances)
                    </label>
                </div>
            <?php endif; ?>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Update User</button>
                <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete User</button>
                <a href="user_management.php" class="btn btn-secondary">← Back to Users</a>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <script>
            function updatePowerEditorVisibility() {
                const roleSelect = document.getElementById('role-select');
                const powerEditorGroup = document.getElementById('powereditor-group');
                if (powerEditorGroup) {
                    // Hide power editor checkbox if admin is selected
                    powerEditorGroup.style.display = (roleSelect.value === 'admin') ? 'none' : 'block';
                }
            }
            // Initial check on page load
            updatePowerEditorVisibility();
        </script>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php
    exit;
}

http_response_code(404);
echo 'Invalid action';
?>