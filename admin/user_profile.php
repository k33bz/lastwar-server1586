<?php
/**
 * User Profile Management
 * Allows users to update their in-game name and email address
 */

require_once 'jwt.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Get current user data
$user_data = get_user_by_email($user->sub);

// Log page access
log_audit_event('user_profile_accessed', $user->sub, [
    'user_roles' => get_user_roles($user)
]);

$page_title = "My Profile";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">👤 My Profile</h1>
    <p class="page-description">Manage your account settings and in-game information</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .profile-section:last-child {
            border-bottom: none;
        }

        .profile-section h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.1rem;
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

        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            margin-bottom: 1rem;
        }

        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .info-box p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
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

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
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

        .badge-list {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #667eea;
            color: white;
        }

        .badge.role-admin {
            background: #e74c3c;
        }

        .badge.role-r5 {
            background: #f39c12;
        }

        .badge.role-r4 {
            background: #3498db;
        }

        .badge.role-ape {
            background: #9b59b6;
        }

        .badge.role-president {
            background: #16a085;
        }
    </style>

    <div id="alertContainer"></div>

    <!-- Profile Information -->
    <div class="profile-section">
        <h3>📋 Profile Information</h3>

        <div class="info-box">
            <strong>Current Roles</strong>
            <div class="badge-list">
                <?php
                $roles = get_user_roles($user);
                foreach ($roles as $role):
                ?>
                    <span class="badge role-<?php echo htmlspecialchars($role); ?>">
                        <?php echo strtoupper(htmlspecialchars($role)); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="info-box">
            <strong>Alliance Access</strong>
            <p>
                <?php
                $alliances = $user_data['alliances'] ?? [];
                if (empty($alliances)) {
                    echo 'No alliance assignments';
                } elseif (in_array('*', $alliances)) {
                    echo 'All Alliances (Admin Access)';
                } else {
                    echo htmlspecialchars(implode(', ', $alliances));
                }
                ?>
            </p>
        </div>
    </div>

    <!-- Display Name Preview -->
    <div class="info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-left: none; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <strong style="font-size: 0.9rem; opacity: 0.9; font-weight: 500;">You appear as:</strong>
                <div id="displayNamePreview" style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">
                    <?php echo htmlspecialchars(get_user_display_name($user->sub)); ?>
                </div>
            </div>
            <div style="font-size: 3rem; opacity: 0.3;">👤</div>
        </div>
        <div style="font-size: 0.85rem; margin-top: 0.75rem; opacity: 0.9;">
            This is how you appear throughout the admin panel (header, audit logs, announcements, etc.)
        </div>
    </div>

    <!-- In-Game Information -->
    <form id="profileForm">
        <div class="profile-section">
            <h3>🎮 In-Game Information</h3>

            <div class="form-group">
                <label for="inGameName">In-Game Name *</label>
                <input
                    type="text"
                    id="inGameName"
                    name="in_game_name"
                    value="<?php echo htmlspecialchars($user_data['in_game_name'] ?? ''); ?>"
                    placeholder="e.g., commander 1a2b3c4d5e"
                    required
                    maxlength="50"
                >
                <div class="help-text">
                    This name will appear in Discord announcements you send. Choose your in-game commander name.
                </div>
            </div>
        </div>

        <!-- Discord Integration -->
        <div class="profile-section">
            <h3>💬 Discord Integration</h3>

            <div class="info-box">
                <strong>🔗 Link Your Discord Account</strong>
                <p>
                    Link your Discord account to enable voting bot features and role-based permissions.
                    <?php if (in_array('president', $roles)): ?>
                    <br><strong>As President, this is required to create and manage council votes.</strong>
                    <?php elseif (in_array('r5', $roles) || in_array('r4', $roles)): ?>
                    <br><strong>This allows you to vote on council proposals via Discord DMs.</strong>
                    <?php endif; ?>
                </p>
            </div>

            <div class="form-group">
                <label for="discordId">Discord User ID</label>
                <input
                    type="text"
                    id="discordId"
                    name="discord_id"
                    value="<?php echo htmlspecialchars($user_data['discord_id'] ?? ''); ?>"
                    placeholder="199257650154831872"
                    pattern="[0-9]{17,19}"
                    maxlength="19"
                >
                <div class="help-text">
                    <strong>How to get your Discord User ID:</strong><br>
                    1. Open Discord → Settings → Advanced → Enable "Developer Mode"<br>
                    2. Right-click your name anywhere in Discord → "Copy User ID"<br>
                    3. Paste the 18-digit number here
                </div>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="profile-section">
            <h3>⚙️ Account Settings</h3>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($user->sub); ?>"
                    required
                >
                <div class="help-text">
                    Changing your email will require you to log in again with your new email address.
                </div>
            </div>

            <div class="alert alert-warning">
                <strong>⚠️ Important:</strong> If you change your email address, your magic link login will be sent to the new email. Make sure it's an address you can access.
            </div>
        </div>

        <!-- Submit Button -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                💾 Save Changes
            </button>
        </div>
    </form>

    <!-- Discord Rate Limits Section -->
    <?php if (defined('DISCORD_ENABLED') && DISCORD_ENABLED): ?>
    <div class="profile-section">
        <h3>📊 Discord Rate Limits</h3>

        <div id="rateLimitInfo" style="min-height: 100px;">
            <p style="text-align: center; color: #666;">Loading rate limit information...</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.rate-limit-display {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.rate-limit-value {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin: 0.5rem 0;
}

.rate-limit-label {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.btn-secondary:hover {
    background: #5a6268;
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
    transform: translateY(-2px);
}

.btn-secondary:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.request-form {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-top: 1rem;
}

.request-form input[type="number"],
.request-form textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
    margin-bottom: 1rem;
}

.request-form textarea {
    min-height: 80px;
    resize: vertical;
}

.pending-request {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    color: #856404;
    padding: 1rem;
    border-radius: 6px;
    margin-top: 1rem;
}

.pending-request strong {
    display: block;
    margin-bottom: 0.5rem;
}
</style>

<script>
// Real-time display name preview
document.getElementById('inGameName').addEventListener('input', function(e) {
    const preview = document.getElementById('displayNamePreview');
    const currentEmail = '<?php echo addslashes($user->sub); ?>';
    const inGameName = e.target.value.trim();

    if (inGameName) {
        preview.textContent = inGameName;
    } else {
        // Fallback to email local part
        preview.textContent = currentEmail.split('@')[0];
    }
});

// Form submission
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';

    try {
        const csrfToken = getCsrfToken();
        const formData = new FormData();
        formData.append('in_game_name', document.getElementById('inGameName').value.trim());
        formData.append('discord_id', document.getElementById('discordId').value.trim());
        formData.append('email', document.getElementById('email').value.trim());

        const response = await fetch('profile_api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');

            // If email changed, show additional message
            if (data.email_changed) {
                setTimeout(() => {
                    showAlert('Email changed. Redirecting to login...', 'warning');
                }, 1500);

                // Redirect to logout after 3 seconds
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 3000);
            }
        } else {
            showAlert('Failed to update profile: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error updating profile: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '💾 Save Changes';
    }
});

// Show alert
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

// Load Discord rate limit information
async function loadRateLimitInfo() {
    const container = document.getElementById('rateLimitInfo');
    if (!container) return; // Discord not enabled

    try {
        const response = await fetch('discord_rate_limit_api.php?action=get_my_limit', {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            displayRateLimitInfo(data);
        } else {
            container.innerHTML = '<div class="alert alert-error">Failed to load rate limit information</div>';
        }
    } catch (error) {
        console.error('Error loading rate limit:', error);
        container.innerHTML = '<div class="alert alert-error">Error loading rate limit information</div>';
    }
}

// Display rate limit information
function displayRateLimitInfo(data) {
    const container = document.getElementById('rateLimitInfo');
    let html = '';

    if (data.is_admin) {
        html = `
            <div class="rate-limit-display">
                <div class="rate-limit-label">Discord Message Rate Limit</div>
                <div class="rate-limit-value">∞ Unlimited</div>
                <p style="color: #666; margin: 0;">Administrators have no rate limits</p>
            </div>
        `;
    } else {
        html = `
            <div class="rate-limit-display">
                <div class="rate-limit-label">Discord Message Rate Limit</div>
                <div class="rate-limit-value">${data.current_limit} messages/hour</div>
                <p style="color: #666; margin-bottom: 1rem;">Maximum instant Discord announcements you can send per hour</p>

                ${data.pending_request ? `
                    <div class="pending-request">
                        <strong>⏳ Pending Request</strong>
                        <p style="margin: 0;">
                            You requested an increase to <strong>${data.pending_request.requested_limit} messages/hour</strong>
                            on ${new Date(data.pending_request.requested_at).toLocaleDateString()}.
                            <br><em>Waiting for admin approval.</em>
                        </p>
                    </div>
                ` : `
                    <button type="button" class="btn btn-secondary" onclick="showRequestForm()">
                        📈 Request Limit Increase
                    </button>
                    <div id="requestForm" style="display: none;" class="request-form">
                        <h4 style="margin-top: 0;">Request Rate Limit Increase</h4>
                        <label>
                            Requested Limit (messages per hour):
                            <input type="number" id="requestedLimit" min="${data.current_limit + 1}" value="${data.current_limit + 10}" required>
                        </label>
                        <label>
                            Reason for increase:
                            <textarea id="requestReason" placeholder="Explain why you need a higher rate limit..." required></textarea>
                        </label>
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" class="btn btn-primary" onclick="submitRateLimitRequest()">Submit Request</button>
                            <button type="button" class="btn btn-secondary" onclick="hideRequestForm()">Cancel</button>
                        </div>
                    </div>
                `}
            </div>
        `;
    }

    container.innerHTML = html;
}

// Show request form
function showRequestForm() {
    document.getElementById('requestForm').style.display = 'block';
    document.querySelector('[onclick="showRequestForm()"]').style.display = 'none';
}

// Hide request form
function hideRequestForm() {
    document.getElementById('requestForm').style.display = 'none';
    document.querySelector('[onclick="showRequestForm()"]').style.display = 'block';
}

// Submit rate limit increase request
async function submitRateLimitRequest() {
    const requestedLimit = parseInt(document.getElementById('requestedLimit').value);
    const reason = document.getElementById('requestReason').value.trim();

    if (!requestedLimit || !reason) {
        showAlert('Please fill in all fields', 'error');
        return;
    }

    try {
        const csrfToken = getCsrfToken();
        const response = await fetch('discord_rate_limit_api.php?action=request_increase', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                requested_limit: requestedLimit,
                reason: reason
            })
        });

        if (!response.ok) {
            const text = await response.text();
            try {
                const errorData = JSON.parse(text);
                throw new Error(errorData.error || `Server error (${response.status})`);
            } catch (parseError) {
                throw new Error(`Server error (${response.status})`);
            }
        }

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            // Reload rate limit info
            loadRateLimitInfo();
        } else {
            showAlert('Failed to submit request: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error submitting request: ' + error.message, 'error');
    }
}

// Load rate limit info on page load
if (document.getElementById('rateLimitInfo')) {
    loadRateLimitInfo();
}
</script>

<?php include 'includes/footer.php'; ?>
