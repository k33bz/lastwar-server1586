<?php
/**
 * Discord Bot Configuration
 * Version: 1.0.0 (Phase 1)
 * Admin-only page for testing bot connection and viewing status
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'discord_webhook.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Admin only
if ($user->aud !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Log page access
log_audit_event('discord_config_accessed', $user->sub, [
    'user_roles' => get_user_roles($user)
]);

// Set page title for header
$page_title = "Discord Configuration";

// Include shared header
include 'includes/header.php';

// Get bot status
$bot_status = validate_discord_bot_token();
?>

<div class="page-header">
    <h1 class="page-title">⚙️ Discord Configuration</h1>
    <p class="page-description">Bot status and connection testing</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .status-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }

        .status-card h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-label {
            font-weight: 600;
            color: #555;
        }

        .status-value {
            color: #333;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .config-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .config-section:last-child {
            border-bottom: none;
        }

        .config-section h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: monospace;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
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

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
            overflow-x: auto;
        }

        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }
    </style>

    <div id="alertContainer"></div>

    <!-- Bot Status -->
    <div class="status-card">
        <h3>🤖 Bot Status</h3>

        <?php if ($bot_status['valid']): ?>
            <div class="status-item">
                <span class="status-label">Connection</span>
                <span class="status-badge success">✓ Connected</span>
            </div>
            <div class="status-item">
                <span class="status-label">Bot Username</span>
                <span class="status-value"><?php echo htmlspecialchars($bot_status['bot_username']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">Bot ID</span>
                <span class="status-value"><code><?php echo htmlspecialchars($bot_status['bot_id']); ?></code></span>
            </div>
            <div class="status-item">
                <span class="status-label">Discord Integration</span>
                <span class="status-badge success"><?php echo DISCORD_ENABLED ? 'Enabled' : 'Disabled'; ?></span>
            </div>
        <?php else: ?>
            <div class="status-item">
                <span class="status-label">Connection</span>
                <span class="status-badge error">✗ Not Connected</span>
            </div>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($bot_status['error']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Configuration Information -->
    <div class="config-section">
        <h3>📋 Configuration</h3>

        <?php if (!DISCORD_ENABLED): ?>
            <div class="alert alert-warning">
                ⚠️ Discord integration is currently <strong>disabled</strong>. To enable it, set <code>DISCORD_ENABLED=true</code> in your <code>admin/.env</code> file.
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Bot Application ID</label>
            <input type="text" readonly value="<?php echo DISCORD_CLIENT_ID ?: 'Not configured'; ?>">
            <div class="help-text">From Discord Developer Portal</div>
        </div>

        <div class="form-group">
            <label>Bot Token</label>
            <input type="text" readonly value="<?php echo DISCORD_BOT_TOKEN ? str_repeat('•', 20) . substr(DISCORD_BOT_TOKEN, -8) : 'Not configured'; ?>">
            <div class="help-text">Configured in admin/.env file (never displayed publicly)</div>
        </div>

        <div class="form-group">
            <label>Rate Limits</label>
            <div class="code-block">
Instant messages: <?php echo DISCORD_MAX_INSTANT_PER_HOUR; ?> per hour per user<br>
Scheduled messages: <?php echo DISCORD_MAX_SCHEDULED_PENDING; ?> pending per user<br>
Recurring messages: <?php echo DISCORD_MAX_RECURRING_ACTIVE; ?> active per user
            </div>
        </div>
    </div>

    <!-- Connection Test -->
    <div class="config-section">
        <h3>🔌 Connection Test</h3>

        <div class="form-group">
            <label for="testChannelId">Channel ID to test *</label>
            <input type="text" id="testChannelId" placeholder="Enter a Discord channel ID (18-20 digits)">
            <div class="help-text">
                To get a channel ID: Enable Discord Developer Mode → Right-click on channel → Copy ID
            </div>
        </div>

        <button type="button" class="btn btn-success" onclick="testConnection()" id="testBtn">
            🧪 Test Connection
        </button>

        <div id="testResult" style="margin-top: 1rem;"></div>
    </div>

    <!-- Setup Instructions -->
    <div class="config-section">
        <h3>📚 Setup Instructions</h3>

        <div class="alert alert-info">
            <strong>Quick Setup Guide:</strong>
            <ol style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <li>Generate bot token in Discord Developer Portal</li>
                <li>Add bot token to <code>admin/.env</code> file</li>
                <li>Invite bot to your Discord server using OAuth2 URL</li>
                <li>Get channel IDs (Developer Mode → Right-click → Copy ID)</li>
                <li>Configure channels in <code>data/discord-channels.json</code></li>
                <li>Test connection above</li>
            </ol>
        </div>

        <div style="margin-top: 1rem;">
            <strong>Documentation:</strong>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                <li><a href="../docs/discord-announcements/BOT-SETUP.md" target="_blank">Complete Bot Setup Guide</a></li>
                <li><a href="../docs/FEATURE_REQUEST_DISCORD_BOT.md" target="_blank">Feature Documentation</a></li>
                <li><a href="https://github.com/k33bz/lastwar-server1586/issues/59" target="_blank">GitHub Issue #59</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
async function testConnection() {
    const channelId = document.getElementById('testChannelId').value.trim();
    const resultDiv = document.getElementById('testResult');
    const testBtn = document.getElementById('testBtn');

    if (!channelId) {
        showAlert('Please enter a channel ID', 'error');
        return;
    }

    if (!/^\d{15,20}$/.test(channelId)) {
        showAlert('Invalid channel ID format. Must be 15-20 digits.', 'error');
        return;
    }

    testBtn.disabled = true;
    testBtn.textContent = 'Testing...';
    resultDiv.innerHTML = '';

    try {
        // Get CSRF token
        const csrfToken = getCsrfToken();

        const formData = new FormData();
        formData.append('action', 'test_connection');
        formData.append('channel_id', channelId);

        const response = await fetch('discord_api.php', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>✓ Success!</strong> Test message sent successfully!<br>
                    Message ID: <code>${data.message_id || 'N/A'}</code>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-error">
                    <strong>✗ Failed:</strong> ${data.message}<br>
                    <small>Check bot permissions and ensure bot is in the server.</small>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-error">
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    } finally {
        testBtn.disabled = false;
        testBtn.textContent = '🧪 Test Connection';
    }
}

function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = message;
    container.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>
