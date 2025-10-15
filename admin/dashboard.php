<?php
/**
 * Admin Dashboard - Main interface for alliance and admin users
 *
 * @version 1.7.0
 * @date 2025-10-15
 * @changelog
 *   1.7.0 (2025-10-15) - Added JWT key rotation status monitoring
 *                       - Added key rotation admin panel link
 *                       - Integrated security status display for admins
 *   1.6.0 (2025-10-15) - Added powereditor role support
 *                       - Header badge shows "R5/POWEREDITOR" or "R4/POWEREDITOR"
 *                       - User management table shows powereditor flag in role column
 *                       - Power editors can access Alliance Power Editor from Quick Links
 *                       - Added Delete Alliance button for admins (calls alliance_delete_api.php)
 *   1.5.0 (2025-10-13) - Added PII protection for email addresses
 *                       - Emails masked by default (show first 2 chars + domain)
 *                       - Click-to-reveal functionality with eye icon in table
 *                       - Header email also masked with click-to-reveal
 *   1.4.0 (2025-10-13) - Added alliance-based filtering for R5 user management
 *                       - R5 can only view/manage users from their assigned alliances
 *   1.3.0 (2025-10-13) - Added R5 user management capability
 *                       - R5 can view and manage R5/R4 users (not admins)
 *                       - Magic link generation and token revocation restricted to admins
 *   1.2.0 (2025-10-13) - Added JWT token revocation functionality
 *                       - Added active session tracking and display
 *                       - Added "Revoke Sessions" button for admins
 *   1.1.0 (2025-10-13) - Initial implementation
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

// Load key rotation support if available
if (file_exists(__DIR__ . '/secret_key_rotation.php')) {
    require_once __DIR__ . '/secret_key_rotation.php';
}

// Require valid session
$user_token = require_jwt_session();

// Load data
$users_data = read_json_file(USERS_FILE);
$alliances_data_raw = file_exists(ALLIANCES_FILE) ? read_json_file(ALLIANCES_FILE) : [];
// Handle both array format and object format
$alliances_data = is_array($alliances_data_raw) && isset($alliances_data_raw[0]) ? $alliances_data_raw : ($alliances_data_raw['alliances'] ?? []);

$is_admin = ($user_token->aud === 'admin');
$user_email = $user_token->sub;
$user_alliances = $user_token->alliances;

// Get key rotation status if available
$key_rotation_status = null;
if (function_exists('get_key_rotation_status')) {
    try {
        $key_rotation_status = get_key_rotation_status();
    } catch (Exception $e) {
        error_log("Failed to get key rotation status: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Last War 1586 Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; font-size: 24px; }
        .user-info { color: #666; font-size: 14px; }
        .badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger .timer {
            display: block;
            font-size: 11px;
            opacity: 0.9;
            margin-top: 3px;
        }
        .btn-danger.expiring-soon {
            background: #d35400;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .alliance-card {
            border: 2px solid #eee;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .alliance-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .actions { display: flex; gap: 10px; }
        .signature-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
            font-size: 13px;
        }
        .signature-table th {
            background: #667eea;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        .signature-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .signature-table tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 11px;
            color: white;
        }
        .btn-revoke {
            background: #e74c3c;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-revoke:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .session-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 8px;
        }
        .session-indicator.active {
            background: #28a745;
            box-shadow: 0 0 4px #28a745;
        }
        .session-indicator.inactive {
            background: #bdc3c7;
        }
        .email-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .email-hidden {
            font-family: monospace;
            letter-spacing: 2px;
        }
        .email-visible {
            font-family: inherit;
        }
        .eye-icon {
            cursor: pointer;
            padding: 4px;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        .eye-icon:hover {
            background-color: #f0f0f0;
        }
        .eye-icon svg {
            width: 18px;
            height: 18px;
            fill: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Last War 1586 Admin Dashboard</h1>
            <div class="user-info">
                Logged in as:
                <strong class="email-display email-hidden" data-email="<?= htmlspecialchars($user_email) ?>" style="cursor: pointer;" onclick="toggleHeaderEmail(this)" title="Click to show/hide email">
                    <?php
                    // Mask current user's email
                    $parts = explode('@', $user_email);
                    $masked = substr($parts[0], 0, 1) . str_repeat('*', max(6, strlen($parts[0]) - 2)) . substr($parts[0], -1) . '@' . $parts[1];
                    echo htmlspecialchars($masked);
                    ?>
                </strong>
                <?php
                $role = $user_token->aud;
                $is_powereditor = isset($user_token->powereditor) && $user_token->powereditor;

                $badge_colors = [
                    'admin' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'r5' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                    'r4' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                    'alliance' => '#6c757d'
                ];
                $badge_color = $badge_colors[$role] ?? '#6c757d';

                // Build role label with powereditor if applicable
                $role_label = strtoupper($role);
                if ($role !== 'admin' && $is_powereditor) {
                    $role_label .= '/POWEREDITOR';
                }
                ?>
                <span class="badge" style="background: <?= $badge_color ?>;"><?= $role_label ?></span>
            </div>
        </div>
        <form method="POST" action="logout.php" style="margin: 0;">
            <button type="submit" class="btn btn-danger" id="logoutBtn">
                Logout
                <span class="timer" id="sessionTimer">Loading...</span>
            </button>
        </form>
    </div>

    <!-- Key Rotation Status (Admin Only) -->
    <?php if ($is_admin && $key_rotation_status): ?>
    <div class="section" style="background: <?= $key_rotation_status['current_key_age_days'] > 25 ? '#fff3cd' : '#d4edda' ?>; border-left: 4px solid <?= $key_rotation_status['current_key_age_days'] > 25 ? '#ffc107' : '#28a745' ?>;">
        <h3 style="margin: 0 0 10px 0; color: #333;">🔐 JWT Key Security Status</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px;">
            <div>
                <strong>Current Key Age:</strong><br>
                <span style="color: <?= $key_rotation_status['current_key_age_days'] > 25 ? '#856404' : '#155724' ?>;">
                    <?= $key_rotation_status['current_key_age_days'] ?> days
                </span>
            </div>
            <div>
                <strong>Last Rotation:</strong><br>
                <?= $key_rotation_status['last_rotation'] ?>
            </div>
            <div>
                <strong>Grace Period:</strong><br>
                <?= $key_rotation_status['grace_period_active'] ? '🟡 Active' : '🟢 Inactive' ?>
            </div>
            <div>
                <strong>Auto Rotation:</strong><br>
                <?= AUTO_KEY_ROTATION_ENABLED ? '🟢 Enabled' : '🔴 Disabled' ?>
            </div>
        </div>
        <?php if ($key_rotation_status['current_key_age_days'] > 25): ?>
        <div style="margin-top: 10px; padding: 8px; background: rgba(255,193,7,0.1); border-radius: 4px; font-size: 13px;">
            ⚠️ <strong>Key rotation recommended.</strong> Current key is over 25 days old.
            <a href="key_rotation_admin_panel.php" style="color: #856404; text-decoration: underline;">Manage rotation →</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <script>
        // Get token expiration from PHP
        const tokenExpiration = <?= $user_token->exp ?>;

        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = tokenExpiration - now;

            const btn = document.getElementById('logoutBtn');
            const timer = document.getElementById('sessionTimer');

            if (timeLeft <= 0) {
                timer.textContent = 'Session expired';
                btn.classList.add('expiring-soon');
                setTimeout(() => {
                    window.location.href = 'login.php?error=expired';
                }, 2000);
                return;
            }

            // Calculate hours, minutes, seconds
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;

            // Format timer display
            if (hours > 0) {
                timer.textContent = `Session: ${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                timer.textContent = `Session: ${minutes}m ${seconds}s`;
            } else {
                timer.textContent = `Session: ${seconds}s`;
            }

            // Add warning class if less than 5 minutes
            if (timeLeft < 300) {
                btn.classList.add('expiring-soon');
            } else {
                btn.classList.remove('expiring-soon');
            }
        }

        // Update timer immediately and then every second
        updateTimer();
        setInterval(updateTimer, 1000);
    </script>

    <?php if ($is_admin || $user_token->aud === 'r5'): ?>
        <div class="section">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Alliances</th>
                        <th>Session Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_data['users'] as $user):
                        // R5 users can only see users from their alliances
                        if (!$is_admin && $user_token->aud === 'r5') {
                            // Cannot see admin users
                            if ($user['role'] === 'admin') {
                                continue;
                            }

                            // Check if user belongs to any of R5's alliances
                            $r5_alliances = $user_token->alliances;
                            $user_alliances = $user['alliances'];

                            // Skip if user has '*' (shouldn't happen for non-admins, but check)
                            if (in_array('*', $user_alliances)) {
                                continue;
                            }

                            // Check for alliance overlap
                            $has_overlap = false;
                            foreach ($user_alliances as $alliance) {
                                if (in_array($alliance, $r5_alliances) || in_array('*', $r5_alliances)) {
                                    $has_overlap = true;
                                    break;
                                }
                            }

                            // Skip if no alliance overlap
                            if (!$has_overlap) {
                                continue;
                            }
                        }

                        $active_sessions = get_active_sessions($user['email']);
                        $has_active = count($active_sessions) > 0;
                    ?>
                        <tr data-user-email="<?= htmlspecialchars($user['email']) ?>">
                            <td>
                                <div class="email-container">
                                    <span class="email-display email-hidden" data-email="<?= htmlspecialchars($user['email']) ?>">
                                        <?php
                                        // Mask email: show first 2 chars + domain
                                        $parts = explode('@', $user['email']);
                                        $masked = substr($parts[0], 0, 1) . str_repeat('*', max(6, strlen($parts[0]) - 2)) . substr($parts[0], -1) . '@' . $parts[1];
                                        echo htmlspecialchars($masked);
                                        ?>
                                    </span>
                                    <span class="eye-icon" onclick="toggleEmail(this)" title="Show/Hide Email">
                                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path class="eye-open" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $display_role = htmlspecialchars($user['role']);
                                if ($user['role'] !== 'admin' && isset($user['powereditor']) && $user['powereditor']) {
                                    $display_role .= '/powereditor';
                                }
                                echo $display_role;
                                ?>
                            </td>
                            <td><?= htmlspecialchars(implode(', ', $user['alliances'])) ?></td>
                            <td>
                                <span class="session-count" data-count="<?= count($active_sessions) ?>">
                                    <?= count($active_sessions) ?> active
                                </span>
                                <span class="session-indicator <?= $has_active ? 'active' : 'inactive' ?>"></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="admin_api.php?action=edit&email=<?= urlencode($user['email']) ?>" class="btn btn-primary btn-small">Edit</a>
                                    <?php if ($is_admin): ?>
                                        <a href="generate_magic_link.php?email=<?= urlencode($user['email']) ?>" class="btn btn-success btn-small">🔗 Magic Link</a>
                                        <button
                                            class="btn-revoke btn-small"
                                            onclick="revokeUserTokens('<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>')"
                                            <?= $has_active ? '' : 'disabled' ?>
                                        >
                                            Revoke Sessions
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <a href="admin_api.php?action=add" class="btn btn-primary">Add New User</a>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Quick Links</h2>
        <div class="actions">
            <a href="../index.html" class="btn btn-primary" target="_blank">View Public Site</a>
            <?php if ($is_admin || $is_powereditor): ?>
                <a href="alliances_power.php" class="btn btn-success">⚡ Alliance Power Editor</a>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <a href="audit_log_viewer.php" class="btn btn-primary">📊 Audit Log Viewer</a>
                <a href="backup_restore.php" class="btn btn-primary">💾 Backup & Restore</a>
                <a href="key_rotation_admin_panel.php" class="btn btn-warning">🔐 Key Rotation</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>Your Alliances</h2>
        <?php
        // Helper function to extract R5 name from either string or object format
        function get_r5_name($r5_data) {
            if (is_string($r5_data)) {
                return $r5_data;
            } elseif (is_array($r5_data) && isset($r5_data['name'])) {
                return $r5_data['name'];
            }
            return 'N/A';
        }

        // Helper function to get all signatures from r5History
        function get_all_signatures($alliance) {
            $signatures = [];
            if (isset($alliance['r5History']) && is_array($alliance['r5History'])) {
                foreach ($alliance['r5History'] as $history) {
                    if (isset($history['signatures']) && is_array($history['signatures'])) {
                        foreach ($history['signatures'] as $sig) {
                            $signatures[] = $sig;
                        }
                    }
                }
            }
            return $signatures;
        }

        // Helper function to calculate signature status
        function get_signature_status($version, $signedAt = null) {
            // Known version release dates
            $version_dates = [
                '1.0' => '2025-05-19T00:00:00Z',
                '1.1' => '2025-10-05T00:00:00Z',
                '1.2' => '2025-10-05T00:00:00Z'
            ];

            // If version not found, can't calculate status
            if (!isset($version_dates[$version])) {
                return ['status' => 'Unknown', 'color' => '#999'];
            }

            $release_date = strtotime($version_dates[$version]);
            $deadline = $release_date + (30 * 24 * 60 * 60); // 30 days after release
            $now = time();

            // If signed
            if ($signedAt) {
                $signed_timestamp = strtotime($signedAt);
                return ['status' => 'Signed', 'color' => '#28a745'];
            }

            // If past deadline
            if ($now > $deadline) {
                $days_overdue = floor(($now - $deadline) / (24 * 60 * 60));
                return ['status' => $days_overdue . ' days overdue', 'color' => '#e74c3c'];
            }

            // If pending
            $days_remaining = ceil(($deadline - $now) / (24 * 60 * 60));
            return ['status' => 'Pending (' . $days_remaining . ' days remaining)', 'color' => '#f39c12'];
        }

        $accessible_alliances = [];
        if (in_array('*', $user_alliances)) {
            $accessible_alliances = $alliances_data;
        } else {
            foreach ($alliances_data as $alliance) {
                if (in_array(strtolower($alliance['tag'] ?? ''), array_map('strtolower', $user_alliances))) {
                    $accessible_alliances[] = $alliance;
                }
            }
        }

        if (empty($accessible_alliances)): ?>
            <p>No alliances assigned to your account.</p>
        <?php else: ?>
            <?php foreach ($accessible_alliances as $alliance): ?>
                <div class="alliance-card">
                    <h3><?= htmlspecialchars($alliance['tag'] ?? 'N/A') ?> - <?= htmlspecialchars($alliance['name'] ?? 'Unknown') ?></h3>
                    <p><strong>R5:</strong> <?= htmlspecialchars(get_r5_name($alliance['r5'] ?? null)) ?></p>
                    <p><strong>Rank:</strong> <?= htmlspecialchars($alliance['rank'] ?? 'N/A') ?></p>

                    <?php
                    // Get all signatures for this alliance
                    $signatures = get_all_signatures($alliance);

                    // Known versions (from amendments)
                    $all_versions = ['1.0', '1.1', '1.2'];

                    // Build map of versions to signatures
                    $version_signatures = [];
                    foreach ($signatures as $sig) {
                        $version = $sig['version'] ?? null;
                        if ($version) {
                            // Keep track of the latest signature for each version
                            if (!isset($version_signatures[$version]) ||
                                strtotime($sig['signedAt']) > strtotime($version_signatures[$version]['signedAt'])) {
                                $version_signatures[$version] = $sig;
                            }
                        }
                    }
                    ?>

                    <table class="signature-table">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Signer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_versions as $version): ?>
                                <?php
                                $sig = $version_signatures[$version] ?? null;
                                $signed_at = $sig ? $sig['signedAt'] : null;
                                $signed_by = $sig ? ($sig['signedBy'] ?? 'Unknown') : '-';
                                $status_info = get_signature_status($version, $signed_at);
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($version) ?></strong></td>
                                    <td>
                                        <span class="status-badge" style="background-color: <?= $status_info['color'] ?>;">
                                            <?= htmlspecialchars($status_info['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($signed_by) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="actions">
                        <?php if (is_r4_or_higher($user_token) || can_sign_rules($user_token, $alliance['tag'] ?? '')): ?>
                            <a href="alliance_edit.php?tag=<?= urlencode($alliance['tag'] ?? '') ?>" class="btn btn-primary">
                                <?= is_r4_or_higher($user_token) ? 'Edit Alliance' : 'View/Sign Alliance' ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <button
                                class="btn btn-danger btn-small"
                                onclick="deleteAlliance('<?= htmlspecialchars($alliance['tag'] ?? '', ENT_QUOTES) ?>')"
                                title="Delete this alliance from the system"
                            >
                                Delete Alliance
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        /**
         * Toggle email visibility in header (PII protection)
         */
        function toggleHeaderEmail(emailSpan) {
            const fullEmail = emailSpan.getAttribute('data-email');
            const isHidden = emailSpan.classList.contains('email-hidden');

            if (isHidden) {
                // Show full email
                emailSpan.textContent = fullEmail;
                emailSpan.classList.remove('email-hidden');
                emailSpan.classList.add('email-visible');
                emailSpan.title = 'Click to hide email';
            } else {
                // Hide email (mask it)
                const parts = fullEmail.split('@');
                const masked = parts[0].substring(0, 1) + '*'.repeat(Math.max(6, parts[0].length - 2)) + parts[0].substring(parts[0].length - 1) + '@' + parts[1];
                emailSpan.textContent = masked;
                emailSpan.classList.remove('email-visible');
                emailSpan.classList.add('email-hidden');
                emailSpan.title = 'Click to show email';
            }
        }

        /**
         * Toggle email visibility in table (PII protection)
         */
        function toggleEmail(eyeIcon) {
            const emailSpan = eyeIcon.previousElementSibling;
            const fullEmail = emailSpan.getAttribute('data-email');
            const isHidden = emailSpan.classList.contains('email-hidden');

            if (isHidden) {
                // Show full email
                emailSpan.textContent = fullEmail;
                emailSpan.classList.remove('email-hidden');
                emailSpan.classList.add('email-visible');
                eyeIcon.title = 'Hide Email';
            } else {
                // Hide email (mask it)
                const parts = fullEmail.split('@');
                const masked = parts[0].substring(0, 1) + '*'.repeat(Math.max(6, parts[0].length - 2)) + parts[0].substring(parts[0].length - 1) + '@' + parts[1];
                emailSpan.textContent = masked;
                emailSpan.classList.remove('email-visible');
                emailSpan.classList.add('email-hidden');
                eyeIcon.title = 'Show Email';
            }
        }

        /**
         * Revoke all active tokens for a user
         */
        function revokeUserTokens(email) {
            if (!confirm(`Are you sure you want to revoke all active sessions for ${email}?\n\nThis will immediately log out the user from all devices.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'revoke_user_tokens');
            formData.append('email', email);

            fetch('revoke_token_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully revoked all sessions for ${email}`);

                    // Update UI
                    const row = document.querySelector(`tr[data-user-email="${email}"]`);
                    if (row) {
                        const sessionCount = row.querySelector('.session-count');
                        const sessionIndicator = row.querySelector('.session-indicator');
                        const revokeBtn = row.querySelector('.btn-revoke');

                        if (sessionCount) sessionCount.textContent = '0 active';
                        if (sessionIndicator) {
                            sessionIndicator.classList.remove('active');
                            sessionIndicator.classList.add('inactive');
                        }
                        if (revokeBtn) revokeBtn.disabled = true;
                    }
                } else {
                    alert(`Error: ${data.error || 'Unknown error occurred'}`);
                }
            })
            .catch(error => {
                console.error('Error revoking tokens:', error);
                alert('Failed to revoke sessions. Please try again.');
            });
        }

        /**
         * Delete an alliance from the system
         */
        function deleteAlliance(allianceTag) {
            if (!confirm(`Are you sure you want to delete alliance "${allianceTag}"?\n\nThis action CANNOT be undone and will:\n- Remove the alliance from all data files\n- Delete all associated history and signatures\n- Impact the public site rankings\n\nType the alliance tag to confirm: ${allianceTag}`)) {
                return;
            }

            const confirmTag = prompt(`Please type the alliance tag "${allianceTag}" to confirm deletion:`);
            if (confirmTag !== allianceTag) {
                alert('Alliance tag does not match. Deletion cancelled.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_alliance');
            formData.append('tag', allianceTag);

            fetch('alliance_delete_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully deleted alliance "${allianceTag}"`);
                    location.reload();
                } else {
                    alert(`Error: ${data.error || 'Unknown error occurred'}`);
                }
            })
            .catch(error => {
                console.error('Error deleting alliance:', error);
                alert('Failed to delete alliance. Please try again.');
            });
        }

        /**
         * Auto-refresh session counts every 30 seconds
         */
        setInterval(function() {
            // Refresh page to update session counts
            // Could be replaced with AJAX polling if needed
            location.reload();
        }, 30000);
    </script>
</body>
</html>
