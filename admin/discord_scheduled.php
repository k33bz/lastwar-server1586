<?php
/**
 * Discord Scheduled Messages - Manage scheduled announcements
 * Version: 1.0.0 (Phase 2 - Scheduled messaging)
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'discord_webhook.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Check if user has at least R4 access or president role
if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Check if Discord is enabled
if (!DISCORD_ENABLED) {
    header('Location: dashboard.php?error=discord_disabled');
    exit();
}

// Log page access
log_audit_event('discord_scheduled_accessed', $user->sub, [
    'user_roles' => get_user_roles($user)
]);

// Set page title for header
$page_title = "Scheduled Messages";

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">⏰ Scheduled Discord Messages</h1>
    <p class="page-description">Schedule announcements to be sent automatically at a future time</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .tab-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.2s;
        }

        .tab-button:hover {
            color: #495057;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="datetime-local"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .embed-options {
            display: none;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-top: 1rem;
        }

        .embed-options.active {
            display: block;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .message-list {
            margin-top: 1rem;
        }

        .message-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .message-card.pending {
            border-left: 4px solid #ffc107;
        }

        .message-card.sent {
            border-left: 4px solid #28a745;
            opacity: 0.7;
        }

        .message-card.failed {
            border-left: 4px solid #dc3545;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .message-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .message-content {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .message-actions {
            display: flex;
            gap: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.sent {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.failed {
            background: #f8d7da;
            color: #721c24;
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

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        #loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .btn-variable {
            background: white;
            border: 1px solid #667eea;
            color: #667eea;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            transition: all 0.2s;
        }
        .btn-variable:hover {
            background: #667eea;
            color: white;
        }
    </style>

    <!-- Tab Navigation -->
    <div class="tab-buttons">
        <button class="tab-button active" onclick="switchTab('create')">📝 Create Scheduled Message</button>
        <button class="tab-button" onclick="switchTab('list')">📋 Manage Messages</button>
    </div>

    <!-- Create Tab -->
    <div id="createTab" class="tab-content active">
        <div id="createAlert"></div>

        <form id="scheduleForm">
            <div class="form-group">
                <label for="channelSelect">Select Channel(s) *</label>
                <select id="channelSelect" name="channel_id" required>
                    <option value="">Loading channels...</option>
                </select>
                <div class="help-text">Choose the Discord channel where this message will be sent</div>
            </div>

            <div class="form-group">
                <label for="scheduledTime">Schedule Date & Time *</label>
                <input type="datetime-local" id="scheduledTime" name="scheduled_time" required>
                <div class="help-text">When should this message be sent? (Your local timezone)</div>
            </div>

            <!-- Template & Variables -->
            <div class="form-group">
                <label for="templateSelect">📝 Use Template (Optional)</label>
                <select id="templateSelect" onchange="loadTemplate()">
                    <option value="">-- Select a template --</option>
                </select>
            </div>

            <div class="form-group">
                <label>📌 Quick Variables</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <button type="button" class="btn-variable" onclick="insertVariable('{sender_name}')">sender_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{r5_name}')">r5_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{event_time}')">event_time</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{date}')">date</button>
                </div>
                <div class="help-text">
                    <a href="discord_templates.php" target="_blank" style="color: #667eea;">View all variables & manage templates →</a>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="useEmbed" name="use_embed" checked onclick="toggleEmbedOptions()">
                    <label for="useEmbed" style="margin-bottom: 0;">Use Rich Embed (Recommended)</label>
                </div>
            </div>

            <div id="embedOptions" class="embed-options active">
                <div class="form-group">
                    <label for="embedTitle">Embed Title</label>
                    <input type="text" id="embedTitle" name="embed_title" placeholder="Scheduled Announcement">
                    <div class="help-text">Optional: Add a title to the embed</div>
                </div>

                <div class="form-group">
                    <label for="embedColor">Embed Color</label>
                    <input type="color" id="embedColor" name="embed_color" value="#5865F2">
                    <div class="help-text">Choose a color for the embed sidebar</div>
                </div>
            </div>

            <div class="form-group">
                <label for="messageContent">Message Content *</label>
                <textarea id="messageContent" name="message" required placeholder="Enter your announcement message...

You can use variables like {sender_name}, {event_time}, etc." maxlength="2000"></textarea>
                <div class="help-text">Maximum 2000 characters</div>
            </div>

            <div class="form-group">
                <label for="deleteAfterHours">Auto-Delete After</label>
                <select id="deleteAfterHours" name="delete_after_hours">
                    <option value="">Never (keep forever)</option>
                    <option value="1">1 hour</option>
                    <option value="6">6 hours</option>
                    <option value="12">12 hours</option>
                    <option value="24">24 hours</option>
                    <option value="48">48 hours (2 days)</option>
                </select>
                <div class="help-text">Automatically delete message after the specified time</div>
            </div>

            <button type="submit" class="btn btn-primary">⏰ Schedule Message</button>
        </form>
    </div>

    <!-- List Tab -->
    <div id="listTab" class="tab-content">
        <div id="listAlert"></div>
        <div id="loading">Loading scheduled messages...</div>
        <div id="messageList" class="message-list" style="display: none;"></div>
    </div>
</div>

<script>
    let channels = [];
    let scheduledMessages = [];
    let templates = [];

    // Load templates
    async function loadTemplates() {
        try {
            const response = await fetch('discord_templates_api.php?action=list');
            const data = await response.json();
            if (data.success) {
                templates = data.templates;
                const select = document.getElementById('templateSelect');
                select.innerHTML = '<option value="">-- Select a template --</option>';
                templates.forEach(t => {
                    const option = document.createElement('option');
                    option.value = t.id;
                    option.textContent = `${t.name} (${t.scope === 'global' ? '🌍' : '🏢'})`;
                    option.dataset.content = t.content;
                    option.dataset.name = t.name; // Store template name for title
                    select.appendChild(option);
                });
            }
        } catch (error) { console.error('Error loading templates:', error); }
    }

    // Load selected template
    function loadTemplate() {
        const select = document.getElementById('templateSelect');
        const option = select.options[select.selectedIndex];
        if (option.dataset.content) {
            document.getElementById('messageContent').value = option.dataset.content;
            // Auto-populate embed title with template name
            if (option.dataset.name) {
                document.getElementById('embedTitle').value = option.dataset.name;
            }
        } else {
            // Clear fields when no template selected
            document.getElementById('messageContent').value = '';
            document.getElementById('embedTitle').value = '';
        }
    }

    // Insert variable
    function insertVariable(variable) {
        const textarea = document.getElementById('messageContent');
        const start = textarea.selectionStart;
        const text = textarea.value;
        textarea.value = text.substring(0, start) + variable + text.substring(textarea.selectionEnd);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
    }

    // Switch between tabs
    function switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        if (tab === 'create') {
            document.querySelector('.tab-button:nth-child(1)').classList.add('active');
            document.getElementById('createTab').classList.add('active');
        } else if (tab === 'list') {
            document.querySelector('.tab-button:nth-child(2)').classList.add('active');
            document.getElementById('listTab').classList.add('active');
            loadScheduledMessages();
        }
    }

    // Toggle embed options
    function toggleEmbedOptions() {
        const embedOptions = document.getElementById('embedOptions');
        const useEmbed = document.getElementById('useEmbed').checked;
        embedOptions.classList.toggle('active', useEmbed);
    }

    // Load accessible channels
    async function loadChannels() {
        try {
            const response = await fetch('discord_api.php?action=get_channels');
            const data = await response.json();

            if (data.success) {
                channels = data.channels;
                populateChannelSelect();
            } else {
                showAlert('createAlert', 'error', 'Failed to load channels: ' + data.error);
            }
        } catch (error) {
            showAlert('createAlert', 'error', 'Error loading channels: ' + error.message);
        }
    }

    // Populate channel select
    function populateChannelSelect() {
        const select = document.getElementById('channelSelect');
        select.innerHTML = '<option value="">-- Select a channel --</option>';

        if (channels.length === 0) {
            select.innerHTML = '<option value="">No channels configured</option>';
            return;
        }

        channels.forEach(channel => {
            const option = document.createElement('option');
            option.value = channel.id;
            option.textContent = `${channel.display_name || channel.name}`;
            select.appendChild(option);
        });
    }

    // Load scheduled messages
    async function loadScheduledMessages() {
        const loading = document.getElementById('loading');
        const messageList = document.getElementById('messageList');

        loading.style.display = 'block';
        messageList.style.display = 'none';

        try {
            const response = await fetch('discord_scheduled_api.php?action=list');
            const data = await response.json();

            if (data.success) {
                scheduledMessages = data.messages;
                renderMessageList();
            } else {
                showAlert('listAlert', 'error', 'Failed to load messages: ' + data.error);
            }
        } catch (error) {
            showAlert('listAlert', 'error', 'Error loading messages: ' + error.message);
        } finally {
            loading.style.display = 'none';
            messageList.style.display = 'block';
        }
    }

    // Render message list
    function renderMessageList() {
        const container = document.getElementById('messageList');

        if (scheduledMessages.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                    </svg>
                    <h3>No Scheduled Messages</h3>
                    <p>Create your first scheduled message to get started</p>
                </div>
            `;
            return;
        }

        // Sort: pending first, then by scheduled time
        scheduledMessages.sort((a, b) => {
            if (a.status === 'pending' && b.status !== 'pending') return -1;
            if (a.status !== 'pending' && b.status === 'pending') return 1;
            return new Date(a.scheduled_time) - new Date(b.scheduled_time);
        });

        container.innerHTML = scheduledMessages.map(msg => createMessageCard(msg)).join('');
    }

    // Create message card HTML
    function createMessageCard(msg) {
        const scheduledDate = new Date(msg.scheduled_time);
        const now = new Date();
        const isPast = scheduledDate < now;
        const timeStr = isPast ? 'Was scheduled for' : 'Scheduled for';

        return `
            <div class="message-card ${msg.status}">
                <div class="message-header">
                    <div>
                        <strong>${msg.channel_name || msg.channel_id}</strong>
                        <span class="status-badge ${msg.status}">${msg.status}</span>
                        <div class="message-meta">
                            ${timeStr}: ${formatDateTime(msg.scheduled_time)}<br>
                            Created by: ${msg.created_by}
                        </div>
                    </div>
                </div>
                <div class="message-content">
                    ${escapeHtml(msg.message).substring(0, 200)}${msg.message.length > 200 ? '...' : ''}
                </div>
                ${msg.status === 'failed' ? `<div class="alert alert-error">Error: ${escapeHtml(msg.error)}</div>` : ''}
                <div class="message-actions">
                    ${msg.status === 'pending' ? `<button class="btn btn-danger" onclick="deleteMessage('${msg.id}')">🗑️ Delete</button>` : ''}
                    ${msg.status === 'sent' ? `<span class="message-meta">Sent at: ${formatDateTime(msg.sent_at)}</span>` : ''}
                </div>
            </div>
        `;
    }

    // Delete scheduled message
    async function deleteMessage(messageId) {
        if (!confirm('Are you sure you want to delete this scheduled message?')) {
            return;
        }

        try {
            const response = await fetch('discord_scheduled_api.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'message_id=' + encodeURIComponent(messageId)
            });

            const data = await response.json();

            if (data.success) {
                showAlert('listAlert', 'success', 'Message deleted successfully');
                loadScheduledMessages();
            } else {
                showAlert('listAlert', 'error', 'Failed to delete: ' + data.error);
            }
        } catch (error) {
            showAlert('listAlert', 'error', 'Error deleting message: ' + error.message);
        }
    }

    // Handle form submission
    document.getElementById('scheduleForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            channel_id: document.getElementById('channelSelect').value,
            message: document.getElementById('messageContent').value,
            scheduled_time: document.getElementById('scheduledTime').value,
            use_embed: document.getElementById('useEmbed').checked,
            embed_title: document.getElementById('embedTitle').value,
            embed_color: document.getElementById('embedColor').value,
            delete_after_hours: document.getElementById('deleteAfterHours').value
        };

        try {
            const response = await fetch('discord_scheduled_api.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showAlert('createAlert', 'success', 'Message scheduled successfully!');
                document.getElementById('scheduleForm').reset();
                document.getElementById('useEmbed').checked = true;
                toggleEmbedOptions();
            } else {
                showAlert('createAlert', 'error', 'Failed to schedule: ' + data.error);
            }
        } catch (error) {
            showAlert('createAlert', 'error', 'Error scheduling message: ' + error.message);
        }
    });

    // Show alert
    function showAlert(containerId, type, message) {
        const container = document.getElementById(containerId);
        container.innerHTML = `<div class="alert alert-${type}">${escapeHtml(message)}</div>`;
        setTimeout(() => container.innerHTML = '', 5000);
    }

    // Format date/time
    function formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString();
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize
    loadChannels();
    loadTemplates();

    // Set minimum datetime to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('scheduledTime').min = now.toISOString().slice(0, 16);
</script>

<?php include 'includes/footer.php'; ?>
