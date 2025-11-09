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
        $files = glob($backup_dir . '/alliances_*.json');
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
        <h1 class="dashboard-title">
            <span class="title-icon">🏛️</span>
            <?php echo $_ENV['APP_NAME'] ?? 'Last War 1586 Admin'; ?>
        </h1>
        <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars(get_user_display_name_from_token($user)); ?></p>
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

<div class="dashboard-content">
    <div class="quick-actions">
        <h2 class="section-title">
            <span class="section-icon">⚡</span>
            Quick Actions
        </h2>
        <div class="quick-actions-grid">
            <?php if ($user->aud !== 'r4'): ?>
            <a href="alliance_edit.php" class="quick-action-card">
                <div class="action-icon">✏️</div>
                <div class="action-title">Alliance Editor</div>
                <div class="action-desc"><?php echo $user->aud === 'r5' ? 'Update alliance info' : 'View all alliances'; ?></div>
            </a>
            <?php endif; ?>

            <?php if ($user->aud === 'admin' || is_power_editor($user)): ?>
            <a href="alliances_power.php" class="quick-action-card power">
                <div class="action-icon">⚡</div>
                <div class="action-title">Power Editor</div>
                <div class="action-desc">Edit alliance power</div>
            </a>
            <?php endif; ?>

            <?php if ($user->aud === 'admin'): ?>
            <a href="user_management.php" class="quick-action-card">
                <div class="action-icon">👥</div>
                <div class="action-title">User Management</div>
                <div class="action-desc">Manage users & roles</div>
            </a>

            <a href="security_audit.php" class="quick-action-card">
                <div class="action-icon">📊</div>
                <div class="action-title">Audit Logs</div>
                <div class="action-desc">View system logs</div>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-sections">
        <!-- Alliance Management - Available to admin, R5, and power editors -->
        <?php if ($user->aud !== 'r4'): ?>
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
        <?php endif; ?>

        <!-- Discord Management - Available to admin, R5, R4, and President -->
        <?php if (defined('DISCORD_ENABLED') && DISCORD_ENABLED && has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
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
            </div>
        </div>
        <?php endif; ?>

        <!-- Season 2 Management - Available to admin, R5, R4, and President -->
        <?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
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
        <?php endif; ?>

        <?php if ($user->aud === 'admin'): ?>
        <!-- Admin-only sections -->
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">👑</span>
                Administrative Control
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
                
                <div class="section-card security">
                    <div class="card-header">
                        <h3>🛡️ Security & Monitoring</h3>
                        <span class="card-badge security">Security</span>
                    </div>
                    <p>Monitor system security, manage authentication, and audit logs</p>
                    <div class="action-buttons">
                        <a href="security_monitor.php" class="btn btn-primary">
                            <span class="btn-icon">📡</span>
                            Monitor
                        </a>
                        <a href="security_keys.php" class="btn btn-secondary">
                            <span class="btn-icon">🔑</span>
                            JWT Keys
                        </a>
                        <a href="security_mfa.php" class="btn btn-secondary">
                            <span class="btn-icon">🔐</span>
                            MFA
                        </a>
                        <a href="security_audit.php" class="btn btn-secondary">
                            <span class="btn-icon">📋</span>
                            Audit Logs
                        </a>
                        <a href="security_backups.php" class="btn btn-secondary">
                            <span class="btn-icon">💾</span>
                            Backups
                        </a>
                    </div>
                </div>

                <div class="section-card system">
                    <div class="card-header">
                        <h3>📊 System Administration</h3>
                        <span class="card-badge system">System</span>
                    </div>
                    <p>System maintenance and testing utilities</p>
                    <div class="action-buttons">
                        <a href="test_dependencies.php" class="btn btn-primary">
                            <span class="btn-icon">🔧</span>
                            System Check
                        </a>
                        <a href="generate_test_token.php" class="btn btn-secondary">
                            <span class="btn-icon">🔑</span>
                            Generate Test Token
                        </a>
                    </div>
                </div>

                <div class="section-card development">
                    <div class="card-header">
                        <h3>🔧 Development Tools</h3>
                        <span class="card-badge dev">Dev</span>
                    </div>
                    <p>Testing and development utilities</p>
                    <div class="action-buttons">
                        <a href="test_alliances_api.php" class="btn btn-primary">
                            <span class="btn-icon">🧪</span>
                            Test API
                        </a>
                        <a href="test_audit_init.php" class="btn btn-secondary">
                            <span class="btn-icon">🔍</span>
                            Test Audit
                        </a>
                        <a href="fix_audit_log.php" class="btn btn-secondary">
                            <span class="btn-icon">🔨</span>
                            Fix Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">📧</span>
                Communication & Access
            </h2>
            
            <div class="section-card communication">
                <div class="card-header">
                    <h3>Magic Link Management</h3>
                    <span class="card-badge communication">Email</span>
                </div>
                <p>Email management and magic link generation</p>
                <div class="action-buttons">
                    <a href="send_magic_link.php" class="btn btn-primary">
                        <span class="btn-icon">📧</span>
                        Send Magic Link
                    </a>
                    <a href="generate_magic_link.php" class="btn btn-secondary">
                        <span class="btn-icon">🔗</span>
                        Generate Links
                    </a>
                    <a href="callback.php" class="btn btn-secondary">
                        <span class="btn-icon">🔄</span>
                        OAuth Callback
                    </a>
                </div>
            </div>
        </div>
        <?php elseif ($user->aud === 'r5'): ?>
        <!-- R5-specific sections -->
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">⭐</span>
                R5 Leadership
            </h2>
            
            <div class="section-cards-grid">
                <div class="section-card r5">
                    <div class="card-header">
                        <h3>📝 Alliance Administration</h3>
                        <span class="card-badge r5">R5</span>
                    </div>
                    <p>Manage your alliance settings and sign server rules</p>
                    <div class="action-buttons">
                        <a href="alliance_edit.php" class="btn btn-primary">
                            <span class="btn-icon">✏️</span>
                            Edit Alliance
                        </a>
                        <a href="alliance_edit.php" class="btn btn-secondary">
                            <span class="btn-icon">✏️</span>
                            Edit All
                        </a>
                        <?php if (is_power_editor($user)): ?>
                            <a href="alliances_power.php" class="btn btn-power">
                                <span class="btn-icon">⚡</span>
                                Power Editor
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (is_power_editor($user)): ?>
                <div class="section-card power">
                    <div class="card-header">
                        <h3>⚡ Power Management</h3>
                        <span class="card-badge power">Power Editor</span>
                    </div>
                    <p>Edit alliance power values and manage alliance data</p>
                    <div class="action-buttons">
                        <a href="alliances_power.php" class="btn btn-power">
                            <span class="btn-icon">⚡</span>
                            Power Editor
                        </a>
                        <a href="alliance_edit.php" class="btn btn-secondary">
                            <span class="btn-icon">✏️</span>
                            Editor
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="section-card members">
                    <div class="card-header">
                        <h3>👥 Member Management</h3>
                        <span class="card-badge members">Members</span>
                    </div>
                    <p>Manage alliance members and recruitment</p>
                    <div class="action-buttons">
                        <a href="alliance_members.php" class="btn btn-primary">
                            <span class="btn-icon">👥</span>
                            Members
                        </a>
                        <a href="recruitment.php" class="btn btn-secondary">
                            <span class="btn-icon">📢</span>
                            Recruitment
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($user->aud === 'r4'): ?>
        <!-- R4-specific sections -->
        <div class="section-group">
            <h2 class="section-title">
                <span class="section-icon">🛡️</span>
                R4 Operations
            </h2>

            <div class="section-cards-grid">
                <div class="section-card r4">
                    <div class="card-header">
                        <h3>📝 Alliance Editor</h3>
                        <span class="card-badge r4">R4</span>
                    </div>
                    <p>View and edit your alliance information</p>
                    <div class="action-buttons">
                        <a href="alliance_edit.php" class="btn btn-primary">
                            <span class="btn-icon">✏️</span>
                            Edit Alliance
                        </a>
                    </div>
                </div>

                <div class="section-card statistics">
                    <div class="card-header">
                        <h3>📊 Alliance Statistics</h3>
                        <span class="card-badge statistics">Stats</span>
                    </div>
                    <p>View alliance power and member statistics</p>
                    <div class="action-buttons">
                        <a href="alliance_stats.php" class="btn btn-primary">
                            <span class="btn-icon">📊</span>
                            Statistics
                        </a>
                        <a href="alliance_power_history.php" class="btn btn-secondary">
                            <span class="btn-icon">📈</span>
                            Power History
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
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
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
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

/* Quick Actions */
.quick-actions {
    margin-bottom: 3rem;
}

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

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.quick-action-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    text-align: center;
}

.quick-action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #667eea;
}

.quick-action-card.power {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
}

.action-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.action-title {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.action-desc {
    font-size: 0.85rem;
    opacity: 0.8;
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
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.section-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.section-card.primary { border-left: 4px solid #667eea; }
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
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-icon {
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-power {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
}

.btn-power:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
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
    
    .quick-actions-grid {
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