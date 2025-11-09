<?php
/**
 * Discord Recurring Messages - Manage repeating announcements
 * Version: 1.0.0 (Phase 3 - Recurring messaging)
 */

require_once 'jwt.php';
require_once 'discord_webhook.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

if (!DISCORD_ENABLED) {
    header('Location: dashboard.php?error=discord_disabled');
    exit();
}

log_audit_event('discord_recurring_accessed', $user->sub, [
    'user_roles' => get_user_roles($user)
]);

$page_title = "Recurring Messages";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🔄 Recurring Discord Messages</h1>
    <p class="page-description">Set up repeating announcements that send automatically (daily, weekly, monthly)</p>
</div>

<div class="container">
    <style>
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .tab-buttons { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e9ecef; }
        .tab-button { padding: 0.75rem 1.5rem; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-size: 1rem; font-weight: 500; color: #6c757d; transition: all 0.2s; }
        .tab-button:hover { color: #495057; }
        .tab-button.active { color: #667eea; border-bottom-color: #667eea; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 2px solid #e9ecef; border-radius: 6px; font-size: 1rem; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .help-text { font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem; }
        .frequency-options { display: none; padding: 1rem; background: #f8f9fa; border-radius: 6px; margin-top: 1rem; }
        .frequency-options.active { display: block; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .message-card { background: white; border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .message-card.enabled { border-left: 4px solid #28a745; }
        .message-card.disabled { border-left: 4px solid #dc3545; opacity: 0.7; }
        .message-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.enabled { background: #d4edda; color: #155724; }
        .status-badge.disabled { background: #f8d7da; color: #721c24; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(26px); }
        .btn-variable { background: white; border: 1px solid #667eea; color: #667eea; padding: 0.5rem 0.75rem; border-radius: 4px; font-size: 0.875rem; cursor: pointer; font-family: 'Courier New', monospace; transition: all 0.2s; }
        .btn-variable:hover { background: #667eea; color: white; }
    </style>

    <div class="tab-buttons">
        <button class="tab-button active" onclick="switchTab('create')">➕ Create Recurring Message</button>
        <button class="tab-button" onclick="switchTab('list')">📋 Manage Messages</button>
    </div>

    <div id="createTab" class="tab-content active">
        <div id="createAlert"></div>
        <form id="recurringForm">
            <div class="form-group">
                <label>Select Channel *</label>
                <select id="channelSelect" required><option>Loading channels...</option></select>
            </div>

            <div class="form-group">
                <label>Frequency *</label>
                <select id="frequency" required onchange="updateFrequencyOptions()">
                    <option value="">-- Select frequency --</option>
                    <option value="daily">Daily - Every day at same time</option>
                    <option value="weekly">Weekly - Once per week on same day</option>
                    <option value="monthly">Monthly - Once per month on same date</option>
                </select>
            </div>

            <div id="weeklyOptions" class="frequency-options">
                <label>Day of Week *</label>
                <select id="dayOfWeek">
                    <option value="monday">Monday</option>
                    <option value="tuesday">Tuesday</option>
                    <option value="wednesday">Wednesday</option>
                    <option value="thursday">Thursday</option>
                    <option value="friday">Friday</option>
                    <option value="saturday">Saturday</option>
                    <option value="sunday">Sunday</option>
                </select>
            </div>

            <div id="monthlyOptions" class="frequency-options">
                <label>Day of Month *</label>
                <select id="dayOfMonth">
                    <?php for ($i = 1; $i <= 28; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <div class="help-text">Days 1-28 only (safe for all months)</div>
            </div>

            <div class="form-group">
                <label>Time of Day *</label>
                <input type="time" id="timeOfDay" required>
                <div class="help-text">When should this message send each time?</div>
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
                    <button type="button" class="btn-variable" onclick="insertVariable('{alliance_name}')">alliance_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{date}')">date</button>
                </div>
                <div class="help-text">
                    <a href="discord_templates.php" target="_blank" style="color: #667eea;">View all variables & manage templates →</a>
                </div>
            </div>

            <div class="form-group">
                <label>Message Content *</label>
                <textarea id="messageContent" required placeholder="Enter recurring announcement...

You can use variables like {sender_name}, {r5_name}, etc." maxlength="2000"></textarea>
            </div>

            <div class="form-group">
                <label><input type="checkbox" id="useEmbed" checked> Use Rich Embed</label>
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
                <div class="help-text">Automatically delete each message after the specified time</div>
            </div>

            <button type="submit" class="btn btn-primary">🔄 Create Recurring Message</button>
        </form>
    </div>

    <div id="listTab" class="tab-content">
        <div id="listAlert"></div>
        <div id="loading">Loading recurring messages...</div>
        <div id="messageList" class="message-list" style="display: none;"></div>
    </div>
</div>

<script>
    let channels = [], recurringMessages = [], templates = [];

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

    function switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        if (tab === 'create') {
            document.querySelector('.tab-button:nth-child(1)').classList.add('active');
            document.getElementById('createTab').classList.add('active');
        } else if (tab === 'list') {
            document.querySelector('.tab-button:nth-child(2)').classList.add('active');
            document.getElementById('listTab').classList.add('active');
            loadRecurringMessages();
        }
    }

    function updateFrequencyOptions() {
        const frequency = document.getElementById('frequency').value;
        document.querySelectorAll('.frequency-options').forEach(opt => opt.classList.remove('active'));

        if (frequency === 'weekly') {
            document.getElementById('weeklyOptions').classList.add('active');
        } else if (frequency === 'monthly') {
            document.getElementById('monthlyOptions').classList.add('active');
        }
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

    // Load recurring messages
    async function loadRecurringMessages() {
        const loading = document.getElementById('loading');
        const messageList = document.getElementById('messageList');

        loading.style.display = 'block';
        messageList.style.display = 'none';

        try {
            const response = await fetch('discord_recurring_api.php?action=list');
            const data = await response.json();

            loading.style.display = 'none';

            if (data.success) {
                recurringMessages = data.messages;
                renderRecurringMessages();
                messageList.style.display = 'block';
            } else {
                showAlert('listAlert', 'error', 'Failed to load messages: ' + data.error);
            }
        } catch (error) {
            loading.style.display = 'none';
            showAlert('listAlert', 'error', 'Error loading messages: ' + error.message);
        }
    }

    // Render recurring messages
    function renderRecurringMessages() {
        const messageList = document.getElementById('messageList');

        if (recurringMessages.length === 0) {
            messageList.innerHTML = `
                <div class="empty-state">
                    <p>No recurring messages yet. Create one to get started!</p>
                </div>
            `;
            return;
        }

        messageList.innerHTML = recurringMessages.map(msg => {
            const statusClass = msg.enabled ? 'enabled' : 'disabled';
            const statusBadge = msg.enabled ?
                '<span class="status-badge enabled">✓ Active</span>' :
                '<span class="status-badge disabled">✗ Disabled</span>';

            let frequencyText = '';
            if (msg.frequency === 'daily') {
                frequencyText = `Daily at ${msg.time_of_day}`;
            } else if (msg.frequency === 'weekly') {
                frequencyText = `Weekly on ${msg.day_of_week} at ${msg.time_of_day}`;
            } else if (msg.frequency === 'monthly') {
                frequencyText = `Monthly on day ${msg.day_of_month} at ${msg.time_of_day}`;
            }

            return `
                <div class="message-card ${statusClass}">
                    <div class="message-header">
                        <div>
                            <strong>${msg.channel_name || msg.channel_id}</strong>
                            ${msg.alliance ? `<span style="color: #6c757d;"> (${msg.alliance})</span>` : ''}
                            ${statusBadge}
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <label class="toggle-switch">
                                <input type="checkbox" ${msg.enabled ? 'checked' : ''}
                                       onchange="toggleMessage('${msg.id}', this.checked)">
                                <span class="slider"></span>
                            </label>
                            <button class="btn btn-danger" onclick="deleteMessage('${msg.id}')">🗑️ Delete</button>
                        </div>
                    </div>

                    <div class="message-content">
                        ${escapeHtml(msg.message)}
                    </div>

                    <div style="font-size: 0.85rem; color: #6c757d;">
                        <div><strong>Frequency:</strong> ${frequencyText}</div>
                        <div><strong>Next Send:</strong> ${formatDateTime(msg.next_send_time)}</div>
                        ${msg.last_sent_at ? `<div><strong>Last Sent:</strong> ${formatDateTime(msg.last_sent_at)} (Count: ${msg.send_count})</div>` : ''}
                        <div><strong>Created:</strong> ${formatDateTime(msg.created_at)} by ${msg.created_by}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Create recurring message
    document.getElementById('recurringForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const channelId = document.getElementById('channelSelect').value;
        const frequency = document.getElementById('frequency').value;
        const timeOfDay = document.getElementById('timeOfDay').value;
        const message = document.getElementById('messageContent').value;
        const useEmbed = document.getElementById('useEmbed').checked;

        if (!channelId || !frequency || !timeOfDay || !message) {
            showAlert('createAlert', 'error', 'Please fill in all required fields');
            return;
        }

        const payload = {
            channel_id: channelId,
            frequency: frequency,
            time_of_day: timeOfDay,
            message: message,
            use_embed: useEmbed,
            delete_after_hours: document.getElementById('deleteAfterHours').value
        };

        if (frequency === 'weekly') {
            payload.day_of_week = document.getElementById('dayOfWeek').value;
        } else if (frequency === 'monthly') {
            payload.day_of_month = document.getElementById('dayOfMonth').value;
        }

        try {
            const csrfToken = getCsrfToken();
            const response = await fetch('discord_recurring_api.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                showAlert('createAlert', 'success', 'Recurring message created successfully! It will send automatically at the scheduled time.');
                document.getElementById('recurringForm').reset();
                document.getElementById('useEmbed').checked = true;
                updateFrequencyOptions();
            } else {
                showAlert('createAlert', 'error', data.error || 'Failed to create recurring message');
            }
        } catch (error) {
            showAlert('createAlert', 'error', 'Error: ' + error.message);
        }
    });

    // Toggle message enabled/disabled
    async function toggleMessage(messageId, enabled) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('message_id', messageId);
            formData.append('enabled', enabled);

            const response = await fetch('discord_recurring_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showAlert('listAlert', 'success', `Message ${enabled ? 'enabled' : 'disabled'} successfully`);
                loadRecurringMessages();
            } else {
                showAlert('listAlert', 'error', data.error || 'Failed to toggle message');
                loadRecurringMessages(); // Reload to reset toggle
            }
        } catch (error) {
            showAlert('listAlert', 'error', 'Error: ' + error.message);
            loadRecurringMessages(); // Reload to reset toggle
        }
    }

    // Delete message
    async function deleteMessage(messageId) {
        if (!confirm('Are you sure you want to delete this recurring message? This cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('message_id', messageId);

            const response = await fetch('discord_recurring_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showAlert('listAlert', 'success', 'Recurring message deleted successfully');
                loadRecurringMessages();
            } else {
                showAlert('listAlert', 'error', data.error || 'Failed to delete message');
            }
        } catch (error) {
            showAlert('listAlert', 'error', 'Error: ' + error.message);
        }
    }

    // Show alert
    function showAlert(elementId, type, message) {
        const alertDiv = document.getElementById(elementId);
        alertDiv.innerHTML = `<div class="alert alert-${type === 'error' ? 'error' : 'success'}">${message}</div>`;
        setTimeout(() => { alertDiv.innerHTML = ''; }, 5000);
    }

    // Helper: Format datetime
    function formatDateTime(datetime) {
        if (!datetime) return 'Never';
        const date = new Date(datetime);
        return date.toLocaleString();
    }

    // Helper: Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize
    loadChannels();
    loadTemplates();
</script>

<?php include 'includes/footer.php'; ?>
