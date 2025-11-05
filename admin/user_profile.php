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
                    placeholder="e.g., jonnybgood"
                    required
                    maxlength="50"
                >
                <div class="help-text">
                    This name will appear in Discord announcements you send. Choose your in-game commander name.
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
</div>

<script>
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
        formData.append('email', document.getElementById('email').value.trim());

        const response = await fetch('profile_api.php', {
            method: 'POST',
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
</script>

<?php include 'includes/footer.php'; ?>
