<?php
/**
 * Secret Key Rotation Admin Panel
 *
 * Web interface for managing JWT secret key rotation
 * Admin-only access required
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/enhanced_jwt_with_key_rotation.php';
require_once __DIR__ . '/secret_key_rotation.php';

// Require admin authentication
$user = require_enhanced_admin_session_with_key_rotation();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'rotate_key':
            $reason = trim($_POST['reason'] ?? 'Manual rotation via admin panel');
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JWT Key Rotation - Admin Panel</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .status-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .status-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .status-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
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
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .emergency-section {
            border-color: #e74c3c;
            background: #fdf2f2;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .history-table th, .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 JWT Secret Key Rotation</h1>
            <p>Manage JWT secret key rotation for enhanced security</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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
        <div style="margin-top: 30px; text-align: center;">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            <a href="audit_log_viewer.php" class="btn btn-secondary">View Audit Logs</a>
        </div>
    </div>
</body>
</html>