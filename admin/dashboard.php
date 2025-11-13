<?php
/**
 * Admin Dashboard
 * Version: 1.1.0
 * Main admin panel overview
 *
 * Documentation:
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 * - Admin Panel Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/README.md
 * - User Personas (Roles): https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/USER-PERSONAS.md
 * - Full Changelog: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/CHANGELOG.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * Changelog:
 * v1.1.0 (2025-10-17) - Made statistics dynamic and functional
 *   - Added active users tracking (logged in within 30 days)
 *   - Added user trend calculation (new users in last 7 days)
 *   - Added alliance trend tracking (requires alliance-count-history.json)
 *   - Enhanced security status assessment (good/warning/critical)
 *   - Enhanced backup status tracking (recent/ok/old/none)
 *   - Added sublabels showing additional context (total users, backup timestamp, status)
 *   - Added status-based color coding for security and backup cards
 * v1.0.0 (2025-10-15) - Initial dashboard with static stats
 */

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Set page title for header
$page_title = "Dashboard";

// Include shared header
include 'includes/header.php';

// Get system statistics
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'users_trend' => 0,
    'total_alliances' => 0,
    'alliances_trend' => 0,
    'security_events' => 0,
    'security_status' => 'good',
    'last_backup' => 'Never',
    'backup_status' => 'none'
];

try {
    // Count users from users.json
    $users_file = __DIR__ . '/users.json';
    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true);
        $total = count($users_data['users'] ?? []);
        $stats['total_users'] = $total;

        // Count active users (logged in within last 30 days)
        $active_count = 0;
        $thirty_days_ago = time() - (30 * 86400);
        foreach ($users_data['users'] ?? [] as $user_item) {
            if (isset($user_item['lastLogin'])) {
                $last_login = strtotime($user_item['lastLogin']);
                if ($last_login > $thirty_days_ago) {
                    $active_count++;
                }
            }
        }
        $stats['active_users'] = $active_count;

        // Calculate trend (new users in last 7 days)
        $week_ago = time() - (7 * 86400);
        $new_users = 0;
        foreach ($users_data['users'] ?? [] as $user_item) {
            if (isset($user_item['createdAt'])) {
                $created = strtotime($user_item['createdAt']);
                if ($created > $week_ago) {
                    $new_users++;
                }
            }
        }
        $stats['users_trend'] = $new_users;
    }

    // Count alliances from alliances.json
    $alliances_file = __DIR__ . '/../data/alliances.json';
    if (file_exists($alliances_file)) {
        $alliances_data = json_decode(file_get_contents($alliances_file), true);
        $current_count = count($alliances_data ?? []);
        $stats['total_alliances'] = $current_count;

        // Try to get previous alliance count for trend
        $history_file = __DIR__ . '/../data/alliance-count-history.json';
        if (file_exists($history_file)) {
            $history = json_decode(file_get_contents($history_file), true);
            $last_week = $history['last_week'] ?? $current_count;
            $stats['alliances_trend'] = $current_count - $last_week;
        }
    }

    // Count recent security events and assess status
    $audit_file = __DIR__ . '/audit_log.json';
    if (file_exists($audit_file)) {
        $audit_data = json_decode(file_get_contents($audit_file), true);
        $recent_events = 0;
        $critical_events = 0;
        $yesterday = time() - 86400;

        $critical_actions = ['failed_login', 'unauthorized_access', 'token_blacklisted', 'security_alert'];

        foreach ($audit_data as $event) {
            if (isset($event['timestamp']) && strtotime($event['timestamp']) > $yesterday) {
                $recent_events++;
                if (in_array($event['action'] ?? '', $critical_actions)) {
                    $critical_events++;
                }
            }
        }

        $stats['security_events'] = $recent_events;

        // Determine security status
        if ($critical_events > 3) {
            $stats['security_status'] = 'critical';
        } elseif ($critical_events > 0 || $recent_events > 10) {
            $stats['security_status'] = 'warning';
        } else {
            $stats['security_status'] = 'good';
        }
    }

    // Check for recent backups
    $backup_dir = __DIR__ . '/backups';
    if (is_dir($backup_dir)) {
        $files = glob($backup_dir . '/*.json');
        if (!empty($files)) {
            $latest_backup = max(array_map('filemtime', $files));
            $hours_ago = (time() - $latest_backup) / 3600;

            $stats['last_backup'] = date('M j, Y H:i', $latest_backup);

            // Determine backup status
            if ($hours_ago < 24) {
                $stats['backup_status'] = 'recent';
            } elseif ($hours_ago < 72) {
                $stats['backup_status'] = 'ok';
            } else {
                $stats['backup_status'] = 'old';
            }
        }
    }
} catch (Exception $e) {
    // Use default stats if files unavailable
}
?>

<div class="dashboard-header">
    <div class="header-content">
        <div class="header-left">
            <h1 class="dashboard-title">
                <span class="title-icon">🏛️</span>
                <?php echo $_ENV['APP_NAME'] ?? 'Last War 1586 Admin'; ?>
            </h1>
            <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars(get_user_display_name_from_token($user)); ?></p>
        </div>
        <div class="header-right">
            <div class="user-badge">
                <span class="role-badge role-<?php echo strtolower($user->aud); ?>">
                    <?php echo strtoupper($user->aud); ?>
                    <?php if (is_power_editor($user)): ?>
                        <span class="power-badge">⚡ POWER</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="dashboard-tabs">
    <div class="tabs-container">
        <button class="tab-button active" data-tab="overview">
            <span class="tab-icon">📊</span>
            <span class="tab-label">Overview</span>
        </button>

        <?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
        <button class="tab-button" data-tab="alliances">
            <span class="tab-icon">⚔️</span>
            <span class="tab-label">Alliances</span>
        </button>
        <?php endif; ?>

        <?php if (defined('DISCORD_ENABLED') && DISCORD_ENABLED && has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
        <button class="tab-button" data-tab="discord">
            <span class="tab-icon">💬</span>
            <span class="tab-label">Discord</span>
            <span class="tab-badge" id="discordBadge">0</span>
        </button>
        <?php endif; ?>

        <?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
        <button class="tab-button" data-tab="season2">
            <span class="tab-icon">❄️</span>
            <span class="tab-label">Season 2</span>
            <span class="tab-badge" id="season2Badge">0</span>
        </button>
        <?php endif; ?>

        <?php if (has_role($user, ['admin', 'president'])): ?>
        <button class="tab-button" data-tab="governance">
            <span class="tab-icon">👑</span>
            <span class="tab-label">Governance</span>
        </button>
        <?php endif; ?>

        <?php if ($user->aud === 'admin'): ?>
        <button class="tab-button" data-tab="admin">
            <span class="tab-icon">🔐</span>
            <span class="tab-label">Admin & Security</span>
            <?php if ($stats['security_status'] === 'critical' || $stats['security_status'] === 'warning'): ?>
            <span class="tab-badge warning">!</span>
            <?php endif; ?>
        </button>

        <button class="tab-button" data-tab="system">
            <span class="tab-icon">⚙️</span>
            <span class="tab-label">System & Tools</span>
            <?php if ($stats['backup_status'] === 'old' || $stats['backup_status'] === 'none'): ?>
            <span class="tab-badge warning">!</span>
            <?php endif; ?>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Tab Content Panels -->
<div class="tab-content active" id="overview-tab">
<div class="stats-overview">
    <div class="stats-grid">
        <div class="stat-card users">
            <div class="stat-header">
                <div class="stat-icon">👥</div>
                <div class="stat-trend <?php echo $stats['users_trend'] > 0 ? 'positive' : 'neutral'; ?>">
                    <?php echo $stats['users_trend'] > 0 ? '+' . $stats['users_trend'] : '—'; ?>
                </div>
            </div>
            <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
            <div class="stat-label">Active Users</div>
            <div class="stat-sublabel"><?php echo number_format($stats['total_users']); ?> total</div>
        </div>

        <div class="stat-card alliances">
            <div class="stat-header">
                <div class="stat-icon">⚔️</div>
                <div class="stat-trend <?php
                    echo $stats['alliances_trend'] > 0 ? 'positive' :
                        ($stats['alliances_trend'] < 0 ? 'negative' : 'neutral');
                ?>">
                    <?php
                    if ($stats['alliances_trend'] > 0) echo '+' . $stats['alliances_trend'];
                    elseif ($stats['alliances_trend'] < 0) echo $stats['alliances_trend'];
                    else echo '—';
                    ?>
                </div>
            </div>
            <div class="stat-number"><?php echo number_format($stats['total_alliances']); ?></div>
            <div class="stat-label">Alliances</div>
        </div>

        <div class="stat-card security <?php echo $stats['security_status']; ?>">
            <div class="stat-header">
                <div class="stat-icon">🛡️</div>
                <div class="stat-trend <?php echo $stats['security_status'] === 'good' ? 'positive' :
                    ($stats['security_status'] === 'critical' ? 'negative' : 'neutral'); ?>">
                    <?php
                    if ($stats['security_status'] === 'good') echo '✓';
                    elseif ($stats['security_status'] === 'critical') echo '⚠️';
                    else echo '⚠';
                    ?>
                </div>
            </div>
            <div class="stat-number"><?php echo number_format($stats['security_events']); ?></div>
            <div class="stat-label">Security Events (24h)</div>
            <div class="stat-sublabel">Status: <?php echo ucfirst($stats['security_status']); ?></div>
        </div>

        <div class="stat-card backup <?php echo $stats['backup_status']; ?>">
            <div class="stat-header">
                <div class="stat-icon">💾</div>
                <div class="stat-trend <?php
                    echo $stats['backup_status'] === 'recent' ? 'positive' :
                        ($stats['backup_status'] === 'old' || $stats['backup_status'] === 'none' ? 'negative' : 'neutral');
                ?>">
                    <?php
                    if ($stats['backup_status'] === 'recent') echo '✓';
                    elseif ($stats['backup_status'] === 'old' || $stats['backup_status'] === 'none') echo '⚠️';
                    else echo '—';
                    ?>
                </div>
            </div>
            <div class="stat-number"><?php echo $stats['last_backup'] === 'Never' ? '—' : '✓'; ?></div>
            <div class="stat-label">Last Backup</div>
            <div class="stat-sublabel"><?php echo $stats['last_backup']; ?></div>
        </div>
    </div>
</div>

</div>
<!-- End Overview Tab -->

<!-- Alliances Tab -->
<?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
<div class="tab-content" id="alliances-tab">
    <div class="main-sections">
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">⚔️</span>
                Alliance Management
            </h2>

            <div class="section-card primary">
                <div class="card-header">
                    <h3>Alliance Operations</h3>
                    <span class="card-badge">Core</span>
                </div>
                <p>Manage alliance data, power levels, and member information</p>
                <div class="action-buttons">
                    <a href="alliance_edit.php" class="btn btn-primary">
                        <span class="btn-icon">✏️</span>
                        Alliance Editor
                    </a>
                    <?php if ($user->aud === 'admin' || is_power_editor($user)): ?>
                        <a href="alliances_power.php" class="btn btn-power">
                            <span class="btn-icon">⚡</span>
                            Power Editor
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- End Alliances Tab -->

<!-- Discord Tab -->
<?php if (defined('DISCORD_ENABLED') && DISCORD_ENABLED && has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
<div class="tab-content" id="discord-tab">
    <div class="main-sections">
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">💬</span>
                Discord Management
            </h2>

            <div class="section-cards-grid">
                <div class="section-card discord">
                    <div class="card-header">
                        <h3>📢 Announcements</h3>
                        <span class="card-badge">Discord</span>
                    </div>
                    <p>Send instant announcements to your alliance Discord channels</p>
                    <div class="action-buttons">
                        <a href="discord_announcements.php" class="btn btn-primary">
                            <span class="btn-icon">📢</span>
                            Send Announcement
                        </a>
                    </div>
                </div>

                <div class="section-card discord">
                    <div class="card-header">
                        <h3>📅 Scheduled Messages</h3>
                        <span class="card-badge">Discord</span>
                    </div>
                    <p>Schedule Discord messages for future events and reminders</p>
                    <div class="action-buttons">
                        <a href="discord_scheduled.php" class="btn btn-primary">
                            <span class="btn-icon">📅</span>
                            Manage Schedule
                        </a>
                    </div>
                </div>

                <div class="section-card discord">
                    <div class="card-header">
                        <h3>🔄 Recurring Messages</h3>
                        <span class="card-badge">Discord</span>
                    </div>
                    <p>Set up automatic recurring messages for daily/weekly reminders</p>
                    <div class="action-buttons">
                        <a href="discord_recurring.php" class="btn btn-primary">
                            <span class="btn-icon">🔄</span>
                            Manage Recurring
                        </a>
                    </div>
                </div>

                <div class="section-card discord">
                    <div class="card-header">
                        <h3>📝 Message Templates</h3>
                        <span class="card-badge">Discord</span>
                    </div>
                    <p>Create and manage reusable message templates</p>
                    <div class="action-buttons">
                        <a href="discord_templates.php" class="btn btn-primary">
                            <span class="btn-icon">📝</span>
                            View Templates
                        </a>
                    </div>
                </div>

                <div class="section-card discord">
                    <div class="card-header">
                        <h3>🔗 Channel Management</h3>
                        <span class="card-badge">Settings</span>
                    </div>
                    <p>Configure Discord channels and webhooks for all alliances</p>
                    <div class="action-buttons">
                        <a href="discord_channels.php" class="btn btn-primary">
                            <span class="btn-icon">🔗</span>
                            Manage Channels
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- End Discord Tab -->

<!-- Season 2 Tab -->
<?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
<div class="tab-content" id="season2-tab">
    <div class="main-sections">
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">❄️</span>
                Season 2 Events
            </h2>

            <div class="section-card season">
                <div class="card-header">
                    <h3>📆 Event Calendar</h3>
                    <span class="card-badge season">Season 2</span>
                </div>
                <p>View and manage Season 2 event calendar and schedules</p>
                <div class="action-buttons">
                    <a href="season2_manager.php" class="btn btn-primary">
                        <span class="btn-icon">📆</span>
                        Event Calendar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- End Season 2 Tab -->

<!-- Governance Tab -->
<?php if (has_role($user, ['admin', 'president'])): ?>
<div class="tab-content" id="governance-tab">
    <div class="main-sections">
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">👑</span>
                Governance & Leadership
            </h2>

            <div class="section-cards-grid">
                <div class="section-card president">
                    <div class="card-header">
                        <h3>🗳️ Voting Management</h3>
                        <span class="card-badge president">President</span>
                    </div>
                    <p>Manage server-wide votes and track participation</p>
                    <div class="action-buttons">
                        <a href="votes_management.php" class="btn btn-primary">
                            <span class="btn-icon">🗳️</span>
                            Manage Votes
                        </a>
                    </div>
                </div>

                <div class="section-card president">
                    <div class="card-header">
                        <h3>🔄 Council Rotation</h3>
                        <span class="card-badge president">President</span>
                    </div>
                    <p>Manage council member rotation schedule and assignments</p>
                    <div class="action-buttons">
                        <a href="council_rotation.php" class="btn btn-primary">
                            <span class="btn-icon">🔄</span>
                            View Schedule
                        </a>
                    </div>
                </div>

                <?php if (defined('DISCORD_ENABLED') && DISCORD_ENABLED): ?>
                <div class="section-card president">
                    <div class="card-header">
                        <h3>💬 Discord Governance</h3>
                        <span class="card-badge discord">Discord</span>
                    </div>
                    <p>Council vote proposals and presidential approvals</p>
                    <div class="action-buttons">
                        <a href="discord_vote_proposals.php" class="btn btn-primary">
                            <span class="btn-icon">📝</span>
                            Vote Proposals
                        </a>
                        <?php if (has_role($user, ['admin', 'president'])): ?>
                        <a href="president_vote_approvals.php" class="btn btn-secondary">
                            <span class="btn-icon">✓</span>
                            Approvals
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- End Governance Tab -->

<!-- Admin & Security Tab (Admin Only) -->
<?php if ($user->aud === 'admin'): ?>
<div class="tab-content" id="admin-tab">
    <div class="main-sections">
        <!-- Administration Section -->
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">👥</span>
                Administration
            </h2>

            <div class="section-cards-grid">
                <div class="section-card admin">
                    <div class="card-header">
                        <h3>👥 User Management</h3>
                        <span class="card-badge admin">Admin</span>
                    </div>
                    <p>Manage user accounts, roles, and authentication</p>
                    <div class="action-buttons">
                        <a href="user_management.php" class="btn btn-primary">
                            <span class="btn-icon">👥</span>
                            User Management
                        </a>
                        <a href="admin_api.php?action=add" class="btn btn-secondary">
                            <span class="btn-icon">➕</span>
                            Add User
                        </a>
                    </div>
                </div>

                <div class="section-card communication">
                    <div class="card-header">
                        <h3>📧 Access Management</h3>
                        <span class="card-badge communication">Email</span>
                    </div>
                    <p>Email management and magic link generation for user access</p>
                    <div class="action-buttons">
                        <a href="send_magic_link.php" class="btn btn-primary">
                            <span class="btn-icon">📧</span>
                            Send Magic Link
                        </a>
                        <a href="generate_magic_link.php" class="btn btn-secondary">
                            <span class="btn-icon">🔗</span>
                            Generate Links
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Section -->
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">🛡️</span>
                Security & Monitoring
            </h2>

            <div class="section-cards-grid">
                <div class="section-card security">
                    <div class="card-header">
                        <h3>📡 Security Monitor</h3>
                        <span class="card-badge security">Security</span>
                    </div>
                    <p>Real-time security monitoring and threat detection</p>
                    <div class="action-buttons">
                        <a href="security_monitor.php" class="btn btn-primary">
                            <span class="btn-icon">📡</span>
                            Security Monitor
                        </a>
                    </div>
                </div>

                <div class="section-card security">
                    <div class="card-header">
                        <h3>🔑 Authentication</h3>
                        <span class="card-badge security">Security</span>
                    </div>
                    <p>Manage JWT keys, MFA settings, and test tokens</p>
                    <div class="action-buttons">
                        <a href="security_keys.php" class="btn btn-primary">
                            <span class="btn-icon">🔑</span>
                            JWT Key Rotation
                        </a>
                        <a href="security_mfa_manage.php" class="btn btn-secondary">
                            <span class="btn-icon">🔐</span>
                            MFA Management
                        </a>
                        <a href="generate_test_token.php" class="btn btn-secondary">
                            <span class="btn-icon">🎫</span>
                            Test Tokens
                        </a>
                    </div>
                </div>

                <div class="section-card security">
                    <div class="card-header">
                        <h3>📋 Audit & Backup</h3>
                        <span class="card-badge security">Security</span>
                    </div>
                    <p>Security audit logs and system backups</p>
                    <div class="action-buttons">
                        <a href="security_audit.php" class="btn btn-primary">
                            <span class="btn-icon">📋</span>
                            Audit Logs
                        </a>
                        <a href="security_backups.php" class="btn btn-secondary">
                            <span class="btn-icon">💾</span>
                            Backups
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Admin & Security Tab -->

<!-- System & Tools Tab (Admin Only) -->
<div class="tab-content" id="system-tab">
    <div class="main-sections">
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">📊</span>
                System Monitoring
            </h2>

            <div class="section-cards-grid">
                <div class="section-card metrics">
                    <div class="card-header">
                        <h3>📈 Performance Metrics</h3>
                        <span class="card-badge monitoring">Monitoring</span>
                    </div>
                    <p>CloudWatch-style metrics and performance monitoring dashboard</p>
                    <div class="action-buttons">
                        <a href="metrics_dashboard.php" class="btn btn-primary">
                            <span class="btn-icon">📈</span>
                            View Metrics
                        </a>
                    </div>
                </div>

                <div class="section-card system">
                    <div class="card-header">
                        <h3>🔧 System Health</h3>
                        <span class="card-badge system">System</span>
                    </div>
                    <p>Check system dependencies and configuration status</p>
                    <div class="action-buttons">
                        <a href="test_dependencies.php" class="btn btn-primary">
                            <span class="btn-icon">🔧</span>
                            System Check
                        </a>
                    </div>
                </div>

                <div class="section-card system">
                    <div class="card-header">
                        <h3>🔄 Migration History</h3>
                        <span class="card-badge system">Version</span>
                    </div>
                    <p>Track system version changes, migrations, and backups</p>
                    <div class="action-buttons">
                        <a href="migration_history.php" class="btn btn-primary">
                            <span class="btn-icon">🔄</span>
                            View History
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">🧪</span>
                Development & Testing
            </h2>

            <div class="section-cards-grid">
                <div class="section-card development">
                    <div class="card-header">
                        <h3>🧪 API Testing</h3>
                        <span class="card-badge dev">Dev</span>
                    </div>
                    <p>Test API endpoints and data integrity</p>
                    <div class="action-buttons">
                        <a href="test_alliances_api.php" class="btn btn-primary">
                            <span class="btn-icon">🧪</span>
                            Test Alliances API
                        </a>
                    </div>
                </div>

                <div class="section-card development">
                    <div class="card-header">
                        <h3>🔨 Maintenance Tools</h3>
                        <span class="card-badge dev">Dev</span>
                    </div>
                    <p>Testing utilities and log maintenance</p>
                    <div class="action-buttons">
                        <a href="test_audit_init.php" class="btn btn-primary">
                            <span class="btn-icon">🔍</span>
                            Test Audit
                        </a>
                        <a href="fix_audit_log.php" class="btn btn-secondary">
                            <span class="btn-icon">🔨</span>
                            Fix Logs
                        </a>
                    </div>
                </div>

                <div class="section-card development">
                    <div class="card-header">
                        <h3>🔄 OAuth Integration</h3>
                        <span class="card-badge dev">Dev</span>
                    </div>
                    <p>OAuth callback handler for testing authentication flows</p>
                    <div class="action-buttons">
                        <a href="callback.php" class="btn btn-primary">
                            <span class="btn-icon">🔄</span>
                            OAuth Callback
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- End System & Tools Tab -->

<!-- R5 & R4 users see the same tab structure as admins (just with limited tabs) -->

<script>
// Tab Navigation
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;

            // Remove active class from all buttons and content
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.classList.add('active');
            }

            // Store active tab in localStorage
            localStorage.setItem('dashboardActiveTab', targetTab);
        });
    });

    // Restore last active tab on page load
    const savedTab = localStorage.getItem('dashboardActiveTab');
    if (savedTab) {
        const savedButton = document.querySelector(`[data-tab="${savedTab}"]`);
        if (savedButton) {
            savedButton.click();
        }
    }

    // Load badge counts
    loadBadgeCounts();

    // Add animated number counters to stat cards
    animateStatNumbers();

    // Initialize keyboard shortcuts
    initKeyboardShortcuts(tabButtons);
});

// Load badge counts for Discord and Season 2 tabs
async function loadBadgeCounts() {
    try {
        // Load Discord pending messages count
        const discordBadge = document.getElementById('discordBadge');
        if (discordBadge) {
            const scheduledResp = await fetch('discord_scheduled_api.php?action=list', {
                credentials: 'include'
            });
            const scheduledData = await scheduledResp.json();
            const pendingScheduled = scheduledData.messages?.filter(m => m.status === 'pending').length || 0;

            const recurringResp = await fetch('discord_recurring_api.php?action=list', {
                credentials: 'include'
            });
            const recurringData = await recurringResp.json();
            const activeRecurring = recurringData.messages?.filter(m => m.enabled).length || 0;

            const total = pendingScheduled + activeRecurring;
            if (total > 0) {
                discordBadge.textContent = total;
                discordBadge.style.display = 'block';
            } else {
                discordBadge.style.display = 'none';
            }
        }

        // Load Season 2 upcoming events count
        const season2Badge = document.getElementById('season2Badge');
        if (season2Badge) {
            const season2Resp = await fetch('season2_api.php?action=get_upcoming_events&days=7', {
                credentials: 'include'
            });
            const season2Data = await season2Resp.json();
            const upcomingCount = season2Data.events?.length || 0;

            if (upcomingCount > 0) {
                season2Badge.textContent = upcomingCount;
                season2Badge.style.display = 'block';
            } else {
                season2Badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading badge counts:', error);
    }
}

// Animate stat numbers on page load
function animateStatNumbers() {
    const statNumbers = document.querySelectorAll('.stat-number');

    statNumbers.forEach(elem => {
        const text = elem.textContent.trim();
        if (text === '—' || text === '✓') return; // Skip non-numeric stats

        const final = parseInt(text.replace(/,/g, ''));
        if (isNaN(final)) return;

        let current = 0;
        const increment = Math.ceil(final / 30); // 30 frames
        const duration = 1000; // 1 second
        const stepTime = duration / 30;

        elem.textContent = '0';

        const timer = setInterval(() => {
            current += increment;
            if (current >= final) {
                elem.textContent = final.toLocaleString();
                clearInterval(timer);
            } else {
                elem.textContent = current.toLocaleString();
            }
        }, stepTime);
    });
}

// Keyboard Shortcuts
function initKeyboardShortcuts(tabButtons) {
    document.addEventListener('keydown', function(e) {
        // Number keys 1-6 for tab switching (when not in input fields)
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        const key = e.key;

        // Tab shortcuts: 1-6
        if (key >= '1' && key <= '6') {
            const index = parseInt(key) - 1;
            if (tabButtons[index]) {
                e.preventDefault();
                tabButtons[index].click();
            }
        }

        // Escape to close modals (future enhancement)
        if (key === 'Escape') {
            // Could close any open modals here
        }
    });
}

// Save user preference to server (optional - for cross-device sync)
async function saveUserPreference(key, value) {
    try {
        const csrfToken = getCsrfToken();
        await fetch('user_preferences_api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ key, value })
        });
    } catch (error) {
        console.error('Failed to save preference:', error);
    }
}
</script>

<style>
/* CSS Variables for Theme Support */
:root {
    --bg-primary: #f5f5f5;
    --bg-secondary: #ffffff;
    --bg-tertiary: #f8f9fa;
    --text-primary: #333333;
    --text-secondary: #6c757d;
    --text-tertiary: #495057;
    --border-color: #e9ecef;
    --shadow-sm: 0 2px 15px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.15);
}

/* Dark Theme */
body.dark-theme {
    --bg-primary: #1a1a1a;
    --bg-secondary: #2d2d2d;
    --bg-tertiary: #3a3a3a;
    --text-primary: #e0e0e0;
    --text-secondary: #a0a0a0;
    --text-tertiary: #c0c0c0;
    --border-color: #404040;
    --shadow-sm: 0 2px 15px rgba(0, 0, 0, 0.3);
    --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.4);
    --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.5);
}

/* Apply theme variables to body */
body {
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Tab Navigation */
.dashboard-tabs {
    background: var(--bg-secondary);
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 2rem;
    padding: 1rem;
    position: sticky;
    top: 70px;
    z-index: 100;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.tabs-container {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    scrollbar-width: thin;
}

.tabs-container::-webkit-scrollbar {
    height: 4px;
}

.tabs-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.tabs-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.tab-button {
    background: transparent;
    border: none;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    position: relative;
}

.tab-button:hover {
    background: var(--bg-tertiary);
    color: var(--text-tertiary);
}

.tab-button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.tab-icon {
    font-size: 1.2rem;
}

.tab-label {
    font-weight: 600;
}

.tab-badge {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
    display: none;
}

.tab-badge.warning {
    background: #f39c12;
    color: white;
    display: block;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.tab-button.active .tab-badge {
    background: rgba(255, 255, 255, 0.4);
}

.tab-button.active .tab-badge.warning {
    background: #e67e22;
}

/* Tab Content */
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dashboard Header */
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin: -2rem -2rem 2rem -2rem;
    border-radius: 0 0 20px 20px;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.header-left {
    flex: 1;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dashboard-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.title-icon {
    font-size: 3rem;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.user-badge {
    display: flex;
    gap: 1rem;
}

.role-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.power-badge {
    background: #f39c12;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
}

/* Stats Overview */
.stats-overview {
    margin-bottom: 3rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--bg-secondary);
    backdrop-filter: blur(10px);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.stat-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.stat-card:hover::after {
    opacity: 1;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card.users::before { background: linear-gradient(90deg, #3498db, #2980b9); }
.stat-card.alliances::before { background: linear-gradient(90deg, #e74c3c, #c0392b); }
.stat-card.security::before { background: linear-gradient(90deg, #f39c12, #e67e22); }
.stat-card.backup::before { background: linear-gradient(90deg, #27ae60, #229954); }

/* Status-specific card colors */
.stat-card.security.good::before { background: linear-gradient(90deg, #27ae60, #229954); }
.stat-card.security.warning::before { background: linear-gradient(90deg, #f39c12, #e67e22); }
.stat-card.security.critical::before { background: linear-gradient(90deg, #e74c3c, #c0392b); }

.stat-card.backup.recent::before { background: linear-gradient(90deg, #27ae60, #229954); }
.stat-card.backup.ok::before { background: linear-gradient(90deg, #3498db, #2980b9); }
.stat-card.backup.old::before,
.stat-card.backup.none::before { background: linear-gradient(90deg, #e67e22, #d35400); }

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-icon {
    font-size: 2rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 12px;
}

.stat-trend {
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
}

.stat-trend.positive { background: #d4edda; color: #155724; }
.stat-trend.negative { background: #f8d7da; color: #721c24; }
.stat-trend.neutral { background: #e2e3e5; color: #6c757d; }

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.stat-sublabel {
    color: #95a5a6;
    font-size: 0.75rem;
    font-weight: 400;
    margin-top: 0.25rem;
}

/* Dashboard Content */
.dashboard-content {
    max-width: 1200px;
    margin: 0 auto;
}

/* Section Titles */
.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-icon {
    font-size: 1.8rem;
}

/* Main Sections */
.main-sections {
    display: flex;
    flex-direction: column;
    gap: 3rem;
}

.section-group {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.section-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.section-card {
    background: var(--bg-tertiary);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.section-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s ease;
}

body.dark-theme .section-card::before {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
}

.section-card:hover::before {
    left: 100%;
}

.section-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    background: var(--bg-secondary);
}

.section-card.primary { border-left: 4px solid #667eea; }
.section-card.president { border-left: 4px solid #f1c40f; }
.section-card.admin { border-left: 4px solid #e74c3c; }
.section-card.security { border-left: 4px solid #f39c12; }
.section-card.system { border-left: 4px solid #27ae60; }
.section-card.development { border-left: 4px solid #9b59b6; }
.section-card.communication { border-left: 4px solid #3498db; }
.section-card.r5 { border-left: 4px solid #f1c40f; }
.section-card.r4 { border-left: 4px solid #95a5a6; }
.section-card.power { border-left: 4px solid #e67e22; }
.section-card.members { border-left: 4px solid #2ecc71; }
.section-card.statistics { border-left: 4px solid #8e44ad; }
.section-card.discord { border-left: 4px solid #5865F2; }
.section-card.season { border-left: 4px solid #00B4D8; }

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.card-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.card-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.card-badge.admin { background: #fee; color: #c33; }
.card-badge.president { background: #fff9e6; color: #b8860b; }
.card-badge.security { background: #fff3cd; color: #856404; }
.card-badge.system { background: #d4edda; color: #155724; }
.card-badge.dev { background: #e2e3f1; color: #6f42c1; }
.card-badge.communication { background: #cce5ff; color: #004085; }
.card-badge.r5 { background: #fff3cd; color: #856404; }
.card-badge.r4 { background: #e2e3e5; color: #6c757d; }
.card-badge.power { background: #ffe8cc; color: #cc5500; }
.card-badge.members { background: #d1ecf1; color: #0c5460; }
.card-badge.statistics { background: #f3e5f5; color: #6a1b9a; }
.card-badge.core { background: #667eea; color: white; }
.card-badge.discord { background: #e8eafc; color: #5865F2; }
.card-badge.season { background: #caf0f8; color: #023047; }

.section-card p {
    color: #6c757d;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:active::before {
    width: 300px;
    height: 300px;
}

.btn-icon {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.btn:hover .btn-icon {
    transform: scale(1.1);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    background: linear-gradient(135deg, #7688eb 0%, #8558b3 100%);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
}

.btn-power {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
}

.btn-power:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(243, 156, 18, 0.5);
    background: linear-gradient(135deg, #f5a623 0%, #f77c33 100%);
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-tabs {
        position: static;
        padding: 0.75rem;
    }

    .tab-button {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }

    .tab-label {
        display: none;
    }

    .tab-icon {
        font-size: 1.4rem;
    }

    .header-content {
        padding: 0 1rem;
    }

    .dashboard-title {
        font-size: 2rem;
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .section-cards-grid {
        grid-template-columns: 1fr;
    }

    .section-group {
        padding: 1.5rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .dashboard-header {
        margin: -1rem -1rem 1rem -1rem;
    }
    
    .header-content {
        padding: 0 1rem;
    }
    
    .dashboard-title {
        font-size: 1.75rem;
    }
    
    .section-group {
        padding: 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>