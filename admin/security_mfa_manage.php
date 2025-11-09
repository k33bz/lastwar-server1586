<?php
/**
 * Multi-Factor Authentication Management
 * Version: 1.0.0
 *
 * Manage MFA settings and view MFA status across all users
 */

require_once 'jwt.php';
require_once 'security_mfa.php';
require_once 'json_helpers.php';

$user = require_admin_session();

$page_title = "MFA Management";

// Get all users with MFA status
$users_data = read_json_file(USERS_FILE);
$users = $users_data['users'] ?? [];

$mfa_stats = [
    'total_users' => count($users),
    'enabled' => 0,
    'disabled' => 0,
    'never_setup' => 0
];

foreach ($users as $u) {
    if (isset($u['mfa']) && $u['mfa']['enabled']) {
        $mfa_stats['enabled']++;
    } elseif (isset($u['mfa']) && !$u['mfa']['enabled']) {
        $mfa_stats['disabled']++;
    } else {
        $mfa_stats['never_setup']++;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="header-content">
        <h1 class="page-title">
            <span class="title-icon">🔐</span>
            Multi-Factor Authentication
        </h1>
        <p class="page-subtitle">Secure your admin panel with two-factor authentication</p>
    </div>
</div>

<!-- MFA Information Section -->
<div class="info-section">
    <div class="info-card primary">
        <div class="info-icon">ℹ️</div>
        <div class="info-content">
            <h3>What is MFA?</h3>
            <p>Multi-Factor Authentication (MFA) adds an extra layer of security to your account by requiring a second verification method in addition to your password. Even if someone knows your password, they cannot access your account without the second factor.</p>
        </div>
    </div>

    <div class="info-card">
        <div class="info-icon">📱</div>
        <div class="info-content">
            <h3>How it Works</h3>
            <ol>
                <li><strong>Setup</strong>: Scan a QR code with an authenticator app (Google Authenticator, Authy, 1Password, etc.)</li>
                <li><strong>Login</strong>: Enter your password, then enter the 6-digit code from your authenticator app</li>
                <li><strong>Backup Codes</strong>: Save 10 backup codes in case you lose access to your authenticator</li>
            </ol>
        </div>
    </div>

    <div class="info-card">
        <div class="info-icon">🔒</div>
        <div class="info-content">
            <h3>Recommended Apps</h3>
            <ul>
                <li><strong>Google Authenticator</strong> - Simple and reliable (iOS/Android)</li>
                <li><strong>Authy</strong> - Cloud backup and multi-device support</li>
                <li><strong>1Password</strong> - Integrated with password manager</li>
                <li><strong>Microsoft Authenticator</strong> - Push notifications</li>
            </ul>
        </div>
    </div>

    <div class="info-card warning">
        <div class="info-icon">⚠️</div>
        <div class="info-content">
            <h3>Important Security Notes</h3>
            <ul>
                <li><strong>Save Backup Codes</strong>: Store them securely offline (not on your phone)</li>
                <li><strong>Time Sync</strong>: Ensure your device time is accurate for TOTP codes</li>
                <li><strong>Recovery</strong>: If you lose your authenticator, use a backup code or contact an admin</li>
                <li><strong>Never Share</strong>: Never share your codes or QR code with anyone</li>
            </ul>
        </div>
    </div>
</div>

<!-- MFA Statistics -->
<div class="stats-section">
    <h2 class="section-title">
        <span class="section-icon">📊</span>
        MFA Adoption Statistics
    </h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $mfa_stats['total_users']; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card success">
            <div class="stat-number"><?php echo $mfa_stats['enabled']; ?></div>
            <div class="stat-label">MFA Enabled</div>
            <div class="stat-sublabel"><?php echo $mfa_stats['total_users'] > 0 ? round(($mfa_stats['enabled'] / $mfa_stats['total_users']) * 100) : 0; ?>% adoption</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?php echo $mfa_stats['disabled']; ?></div>
            <div class="stat-label">Previously Disabled</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-number"><?php echo $mfa_stats['never_setup']; ?></div>
            <div class="stat-label">Never Setup</div>
        </div>
    </div>
</div>

<!-- User MFA Status Table -->
<div class="table-section">
    <h2 class="section-title">
        <span class="section-icon">👥</span>
        User MFA Status
    </h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>MFA Status</th>
                <th>Enabled Date</th>
                <th>Last Used</th>
                <th>Backup Codes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['ign'] ?? $u['in_game_name'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><span class="role-badge role-<?php echo strtolower($u['role'] ?? 'user'); ?>"><?php echo strtoupper($u['role'] ?? 'USER'); ?></span></td>
                <td>
                    <?php if (isset($u['mfa']) && $u['mfa']['enabled']): ?>
                        <span class="status-badge success">✓ Enabled</span>
                    <?php elseif (isset($u['mfa']) && !$u['mfa']['enabled']): ?>
                        <span class="status-badge warning">Disabled</span>
                    <?php else: ?>
                        <span class="status-badge danger">Never Setup</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    if (isset($u['mfa']['enabled_at'])) {
                        echo date('M j, Y', $u['mfa']['enabled_at']);
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (isset($u['mfa']['last_used'])) {
                        $diff = time() - $u['mfa']['last_used'];
                        if ($diff < 3600) {
                            echo floor($diff / 60) . ' min ago';
                        } elseif ($diff < 86400) {
                            echo floor($diff / 3600) . ' hours ago';
                        } else {
                            echo floor($diff / 86400) . ' days ago';
                        }
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (isset($u['mfa']['backup_codes'])) {
                        $count = count($u['mfa']['backup_codes']);
                        $badge_class = $count === 0 ? 'danger' : ($count <= 3 ? 'warning' : 'success');
                        echo "<span class=\"status-badge {$badge_class}\">{$count} remaining</span>";
                    } else {
                        echo '—';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Security Best Practices -->
<div class="best-practices-section">
    <h2 class="section-title">
        <span class="section-icon">✅</span>
        Security Best Practices
    </h2>
    <div class="practices-grid">
        <div class="practice-card">
            <div class="practice-icon">🔐</div>
            <h3>Enforce MFA for Admins</h3>
            <p>Require all admin and R5 users to enable MFA to protect sensitive operations.</p>
        </div>
        <div class="practice-card">
            <div class="practice-icon">📝</div>
            <h3>Document Recovery Process</h3>
            <p>Ensure users know how to recover their account if they lose their authenticator device.</p>
        </div>
        <div class="practice-card">
            <div class="practice-icon">⏰</div>
            <h3>Regular Audits</h3>
            <p>Periodically review MFA usage and remind users to enable it.</p>
        </div>
        <div class="practice-card">
            <div class="practice-icon">🔄</div>
            <h3>Rotate Backup Codes</h3>
            <p>Regenerate backup codes periodically and when users change devices.</p>
        </div>
    </div>
</div>

<style>
/* Info Section */
.info-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
}

.info-card.primary {
    border-left: 4px solid #667eea;
}

.info-card.warning {
    border-left: 4px solid #f39c12;
    background: rgba(243, 156, 18, 0.05);
}

.info-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.info-content h3 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.info-content p,
.info-content ol,
.info-content ul {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--text-secondary);
}

.info-content ol,
.info-content ul {
    margin-left: 1.5rem;
    margin-top: 0.5rem;
}

.info-content li {
    margin-bottom: 0.5rem;
}

.info-content strong {
    color: var(--text-primary);
}

/* Stats Section */
.stats-section {
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.stat-card.success {
    border-left: 4px solid #27ae60;
}

.stat-card.warning {
    border-left: 4px solid #f39c12;
}

.stat-card.danger {
    border-left: 4px solid #e74c3c;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stat-sublabel {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Table Section */
.table-section {
    margin-bottom: 2rem;
}

.data-table {
    width: 100%;
    background: var(--bg-secondary);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
}

.data-table thead {
    background: var(--bg-tertiary);
}

.data-table th,
.data-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.data-table th {
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table tbody tr:hover {
    background: var(--bg-tertiary);
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge.success {
    background: #d4edda;
    color: #155724;
}

.status-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.status-badge.danger {
    background: #f8d7da;
    color: #721c24;
}

/* Best Practices Section */
.best-practices-section {
    margin-bottom: 2rem;
}

.practices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.practice-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.practice-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.practice-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.practice-card p {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.5;
}

body.dark-theme .status-badge.success {
    background: #1e4620;
    color: #a5d6a7;
}

body.dark-theme .status-badge.warning {
    background: #4a3a1a;
    color: #ffd54f;
}

body.dark-theme .status-badge.danger {
    background: #4a1e1e;
    color: #ef9a9a;
}
</style>

<?php include 'includes/footer.php'; ?>
