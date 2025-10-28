<?php
/**
 * Security: JWT Key Management
 *
 * Web interface for managing JWT secret key rotation
 * Admin-only access required
 *
 * @version 3.0.0
 * @date 2025-10-16
 */

session_start();
define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/enhanced_jwt_with_key_rotation.php';
require_once __DIR__ . '/secret_key_rotation.php';

// Set page title for header
$page_title = "JWT Key Rotation";

// Require admin authentication
$user = require_enhanced_admin_session_with_key_rotation();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    requireCsrfToken();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'rotate_key':
            $reason = trim($_POST['reason'] ?? 'Manual rotation via ' . ($_ENV['APP_NAME'] ?? 'admin panel'));
            $result = rotate_secret_key($user->sub, $reason);
            
            if ($result['success']) {
                $message = "Secret key rotated successfully. New key ID: {$result['new_key_id']}. All users must log in again.";
            } else {
                $error = "Key rotation failed: " . $result['error'];
            }
            break;
            
        case 'emergency_rotate':
            $reason = trim($_POST['emergency_reason'] ?? 'Emergency rotation');
            $result = emergency_key_rotation($user->sub, $reason);
            
            if ($result['success']) {
                $message = "EMERGENCY: Secret key rotated immediately. All sessions invalidated. Admin notifications sent.";
            } else {
                $error = "Emergency rotation failed: " . $result['error'];
            }
            break;
    }
}

// Get current status
$status = get_key_rotation_status();
$env_sync = validate_env_key_sync();

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🔄 JWT Key Rotation Management</h1>
    <p class="page-description">Manage JWT secret key rotation for enhanced security</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="container">
<style>
.container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.status-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.status-card h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1rem;
}

.status-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 0.25rem;
}

.warning {
    border-left-color: #f39c12;
    background: #fef9e7;
}

.warning .status-value {
    color: #f39c12;
}

.danger {
    border-left-color: #e74c3c;
    background: #fdf2f2;
}

.danger .status-value {
    color: #e74c3c;
}

.success {
    border-left-color: #27ae60;
    background: #f0f9f4;
}

.success .status-value {
    color: #27ae60;
}

.action-section {
    margin: 2rem 0;
    padding: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

.emergency-section {
    border-color: #e74c3c;
    background: #fdf2f2;
}

.form-group {
    margin: 1rem 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.history-table th, .history-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.history-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.actions-footer {
    margin-top: 2rem;
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}
</style>

        <!-- Status Overview -->
        <h2>Current Status</h2>
        <div class="status-grid">
            <div class="status-card <?= $status['current_key_age_days'] > 30 ? 'warning' : 'success' ?>">
                <h3>Current Key Age</h3>
                <div class="status-value"><?= $status['current_key_age_days'] ?> days</div>
                <small>Key ID: <?= htmlspecialchars($status['current_key_id']) ?></small>
            </div>

            <div class="status-card <?= $env_sync ? 'success' : 'danger' ?>">
                <h3>Environment Sync</h3>
                <div class="status-value"><?= $env_sync ? '✓ Synced' : '✗ Out of Sync' ?></div>
                <small><?= $env_sync ? 'Config matches stored key' : 'Config key mismatch!' ?></small>
            </div>

            <div class="status-card <?= $status['grace_period_active'] ? 'warning' : 'success' ?>">
                <h3>Grace Period</h3>
                <div class="status-value"><?= $status['grace_period_active'] ? 'Active' : 'Inactive' ?></div>
                <small><?= $status['previous_key_exists'] ? 'Previous key available' : 'No previous key' ?></small>
            </div>

            <div class="status-card">
                <h3>Rotation History</h3>
                <div class="status-value"><?= $status['rotation_history_count'] ?></div>
                <small>Previous rotations stored</small>
            </div>
        </div>

        <!-- Manual Rotation -->
        <div class="action-section">
            <h3>🔄 Manual Key Rotation</h3>
            <p>Rotate the JWT secret key. This will invalidate all current sessions and require all users to log in again.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="rotate_key">
                <div class="form-group">
                    <label for="reason">Rotation Reason:</label>
                    <input type="text" id="reason" name="reason" placeholder="e.g., Scheduled monthly rotation" required>
                </div>
                <button type="submit" class="btn btn-primary" onclick="return confirm('This will log out all users. Continue?')">
                    🔄 Rotate Secret Key
                </button>
            </form>
        </div>

        <!-- Emergency Rotation -->
        <div class="action-section emergency-section">
            <h3>🚨 Emergency Key Rotation</h3>
            <p><strong>WARNING:</strong> Emergency rotation immediately invalidates ALL tokens and sends alerts to all admins.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="emergency_rotate">
                <div class="form-group">
                    <label for="emergency_reason">Security Incident Reason:</label>
                    <textarea id="emergency_reason" name="emergency_reason" rows="3" placeholder="Describe the security incident requiring emergency rotation..." required></textarea>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('EMERGENCY ROTATION: This will immediately invalidate all sessions and send security alerts. Are you sure?')">
                    🚨 Emergency Rotate Now
                </button>
            </form>
        </div>

        <!-- Rotation History -->
        <h3>📊 Rotation History</h3>
        <table class="history-table">
            <thead>
                <tr>
                    <th>Key ID</th>
                    <th>Created</th>
                    <th>Age</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= htmlspecialchars($status['current_key_id']) ?></strong></td>
                    <td><?= $status['last_rotation'] ?></td>
                    <td><?= $status['current_key_age_days'] ?> days</td>
                    <td><span style="color: #27ae60;">✓ Current</span></td>
                </tr>
                <?php
                $keys_data = get_secret_keys();
                foreach ($keys_data['rotation_history'] ?? [] as $key_info):
                    $age_days = round((time() - $key_info['created_at']) / 86400, 1);
                ?>
                <tr>
                    <td><?= htmlspecialchars($key_info['key_id']) ?></td>
                    <td><?= date('Y-m-d H:i:s', $key_info['created_at']) ?></td>
                    <td><?= $age_days ?> days</td>
                    <td><span style="color: #6c757d;">Rotated</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <!-- Actions -->
    <div class="actions-footer">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="audit_logger.php" class="btn btn-secondary">View Audit Logs</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>