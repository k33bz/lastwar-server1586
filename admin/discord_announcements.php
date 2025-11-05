<?php
/**
 * Discord Announcements - Send instant announcements
 * Version: 1.0.0 (Phase 1 - Basic instant messaging)
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'discord_webhook.php';

$user = require_jwt_session();

// Check if user has at least R4 access
if (!has_role($user, ['admin', 'r5', 'r4'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Check if Discord is enabled
if (!DISCORD_ENABLED) {
    header('Location: dashboard.php?error=discord_disabled');
    exit();
}

// Set page title for header
$page_title = "Discord Announcements";

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📢 Discord Announcements</h1>
    <p class="page-description">Send instant announcements to Discord channels</p>
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

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-of-type {
            border-bottom: none;
        }

        .form-section h3 {
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

        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .char-count {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .char-count.warning {
            color: #f39c12;
        }

        .char-count.danger {
            color: #e74c3c;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .channels-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1rem;
            background: #f8f9fa;
        }

        .channel-item {
            padding: 0.75rem;
            background: white;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
        }

        .channel-item:last-child {
            margin-bottom: 0;
        }

        .channel-name {
            font-weight: 600;
            color: #333;
        }

        .channel-description {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #loadingOverlay.active {
            display: flex;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .embed-options {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            display: none;
        }

        .embed-options.active {
            display: block;
        }

        .color-picker {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s;
        }

        .color-option.selected {
            border-color: #333;
            transform: scale(1.1);
        }

        .color-option:hover {
            transform: scale(1.05);
        }
    </style>

    <div id="alertContainer"></div>

    <?php if (DISCORD_ENABLED): ?>
        <div class="alert alert-info">
            ℹ️ <strong>Phase 1:</strong> Basic instant messaging is now available. Scheduled and recurring messages will be added in future updates.
        </div>
    <?php endif; ?>

    <form id="announcementForm">
        <!-- Message Content Section -->
        <div class="form-section">
            <h3>📝 Message Content</h3>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="useEmbed" name="use_embed">
                    <label for="useEmbed">Use rich embed format (recommended)</label>
                </div>
            </div>

            <div id="embedOptions" class="embed-options">
                <div class="form-group">
                    <label for="embedTitle">Embed Title</label>
                    <input type="text" id="embedTitle" name="embed_title" placeholder="e.g., Important Announcement">
                </div>

                <div class="form-group">
                    <label>Embed Color</label>
                    <div class="color-picker">
                        <div class="color-option selected" data-color="3447003" style="background: #3498db;" title="Blue"></div>
                        <div class="color-option" data-color="15158332" style="background: #e74c3c;" title="Red"></div>
                        <div class="color-option" data-color="3066993" style="background: #2ecc71;" title="Green"></div>
                        <div class="color-option" data-color="15844367" style="background: #f1c40f;" title="Yellow"></div>
                        <div class="color-option" data-color="10181046" style="background: #9b59b6;" title="Purple"></div>
                        <div class="color-option" data-color="15105570" style="background: #e67e22;" title="Orange"></div>
                    </div>
                    <input type="hidden" id="embedColor" name="embed_color" value="3447003">
                </div>
            </div>

            <div class="form-group">
                <label for="messageContent">Message *</label>
                <textarea id="messageContent" name="message" placeholder="Enter your announcement message here..." required></textarea>
                <div class="char-count" id="charCount">0 / 2000 characters</div>
                <div class="help-text">Supports Discord markdown: **bold**, *italic*, __underline__, ~~strikethrough~~</div>
            </div>
        </div>

        <!-- Channel Selection Section -->
        <div class="form-section">
            <h3>🎯 Target Channels</h3>
            <div class="form-group">
                <label>Select channels to send announcement:</label>
                <div id="channelsList" class="channels-list">
                    <p style="text-align: center; color: #666;">Loading channels...</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                📤 Send Announcement
            </button>
        </div>
    </form>
</div>

<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<script>
// Track selected channels
let selectedChannels = [];
let availableChannels = [];

// Load available channels
async function loadChannels() {
    try {
        const response = await fetch('discord_api.php?action=get_channels');
        const data = await response.json();

        if (data.success) {
            availableChannels = data.channels;
            renderChannels();
        } else {
            showAlert('Failed to load channels: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error loading channels: ' + error.message, 'error');
    }
}

// Render channels list
function renderChannels() {
    const container = document.getElementById('channelsList');

    if (availableChannels.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666;">No channels configured yet. Please contact an administrator.</p>';
        return;
    }

    container.innerHTML = availableChannels.map(channel => `
        <div class="channel-item">
            <div class="checkbox-group">
                <input type="checkbox" id="channel_${channel.id}" value="${channel.id}" onchange="toggleChannel('${channel.id}')">
                <label for="channel_${channel.id}">
                    <span class="channel-name">#${channel.name}</span>
                    ${channel.server_name ? `<span style="color: #999;"> (${channel.server_name})</span>` : ''}
                </label>
            </div>
            ${channel.description ? `<div class="channel-description">${channel.description}</div>` : ''}
        </div>
    `).join('');
}

// Toggle channel selection
function toggleChannel(channelId) {
    const checkbox = document.getElementById('channel_' + channelId);
    if (checkbox.checked) {
        selectedChannels.push(channelId);
    } else {
        selectedChannels = selectedChannels.filter(id => id !== channelId);
    }
}

// Character count
document.getElementById('messageContent').addEventListener('input', function() {
    const length = this.value.length;
    const counter = document.getElementById('charCount');
    counter.textContent = length + ' / 2000 characters';

    if (length > 2000) {
        counter.classList.add('danger');
        counter.classList.remove('warning');
    } else if (length > 1800) {
        counter.classList.add('warning');
        counter.classList.remove('danger');
    } else {
        counter.classList.remove('warning', 'danger');
    }
});

// Toggle embed options
document.getElementById('useEmbed').addEventListener('change', function() {
    const embedOptions = document.getElementById('embedOptions');
    if (this.checked) {
        embedOptions.classList.add('active');
    } else {
        embedOptions.classList.remove('active');
    }
});

// Color picker
document.querySelectorAll('.color-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('embedColor').value = this.dataset.color;
    });
});

// Form submission
document.getElementById('announcementForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validate
    if (selectedChannels.length === 0) {
        showAlert('Please select at least one channel', 'error');
        return;
    }

    const message = document.getElementById('messageContent').value.trim();
    if (!message) {
        showAlert('Please enter a message', 'error');
        return;
    }

    if (message.length > 2000) {
        showAlert('Message exceeds 2000 character limit', 'error');
        return;
    }

    // Show loading
    document.getElementById('loadingOverlay').classList.add('active');
    document.getElementById('submitBtn').disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'send_instant');
        formData.append('channel_ids', JSON.stringify(selectedChannels));
        formData.append('message', message);
        formData.append('use_embed', document.getElementById('useEmbed').checked ? 'true' : 'false');
        formData.append('embed_title', document.getElementById('embedTitle').value || '');
        formData.append('embed_color', document.getElementById('embedColor').value);
        formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');

        const response = await fetch('discord_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            resetForm();
        } else {
            showAlert('Failed to send: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error sending message: ' + error.message, 'error');
    } finally {
        document.getElementById('loadingOverlay').classList.remove('active');
        document.getElementById('submitBtn').disabled = false;
    }
});

// Reset form
function resetForm() {
    document.getElementById('announcementForm').reset();
    selectedChannels = [];
    document.querySelectorAll('.channels-list input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('embedOptions').classList.remove('active');
    document.getElementById('charCount').textContent = '0 / 2000 characters';
    document.getElementById('charCount').classList.remove('warning', 'danger');
}

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

// Initialize
loadChannels();
</script>

<?php include 'includes/footer.php'; ?>
