<?php
/**
 * Season 2: Polar Storm - Event Manager
 * Configure season start date and manage all Season 2 events with one-click announcements
 *
 * @version 1.0.0
 * @date 2025-11-07
 */

require_once 'jwt.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// R4+ access required to view, admin to configure
if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

$can_configure = ($user->aud === 'admin');

log_audit_event('season2_manager_accessed', $user->sub, [
    'can_configure' => $can_configure
]);

$page_title = "Season 2: Polar Storm Manager";
include 'includes/header.php';
?>

<style>
.season2-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

.config-card, .status-card, .calendar-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e0e0e0;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.status-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.status-label {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.status-value {
    font-size: 2rem;
    font-weight: 700;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #555;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
}

.help-text {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.event-grid {
    display: grid;
    gap: 1rem;
}

.event-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.2s;
}

.event-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.event-card.critical {
    border-left: 4px solid #ef4444;
}

.event-card.high {
    border-left: 4px solid #f59e0b;
}

.event-card.medium {
    border-left: 4px solid #3b82f6;
}

.event-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.event-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.event-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-critical {
    background: #fee2e2;
    color: #dc2626;
}

.badge-high {
    background: #fef3c7;
    color: #d97706;
}

.badge-medium {
    background: #dbeafe;
    color: #2563eb;
}

.event-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.event-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #666;
}

.event-description {
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.event-actions {
    display: flex;
    gap: 0.5rem;
}

.week-filter {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.week-tab {
    padding: 0.5rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.week-tab:hover {
    border-color: #667eea;
}

.week-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}

.loading {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

#channelModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

#channelModal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
}

.channel-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.channel-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.channel-item:hover {
    border-color: #667eea;
    background: #f5f7ff;
}

.channel-item.selected {
    border-color: #667eea;
    background: #e0e7ff;
}

.modal-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 1rem;
    margin-bottom: 2rem;
    border-radius: 12px;
}

.page-title {
    font-size: 2rem;
    margin: 0;
}

.page-description {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}
</style>

<div class="season2-container">
    <div class="page-header">
        <h1 class="page-title">❄️ Season 2: Polar Storm</h1>
        <p class="page-description">Event Calendar & One-Click Announcements</p>
    </div>

    <div id="alertContainer"></div>

    <!-- Configuration Card -->
    <?php if ($can_configure): ?>
    <div class="config-card">
        <div class="card-header">
            <h2 class="card-title">⚙️ Season Configuration</h2>
        </div>

        <form id="configForm">
            <div class="form-group">
                <label for="seasonStartDate">Season 2 Start Date *</label>
                <input type="date" id="seasonStartDate" name="season_start_date" required>
                <div class="help-text">Set the date when Season 2 begins. All events will be calculated from this date.</div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="seasonStartTime">Start Time</label>
                    <input type="time" id="seasonStartTime" name="season_start_time" value="00:00">
                </div>

                <div class="form-group">
                    <label for="factionWarTime">Faction War Time (Saturday)</label>
                    <input type="time" id="factionWarTime" name="faction_war_time" value="14:00">
                </div>

                <div class="form-group">
                    <label for="serverTimezone">Server Timezone</label>
                    <select id="serverTimezone" name="server_timezone">
                        <option value="UTC" selected>UTC</option>
                        <option value="America/New_York">EST (UTC-5)</option>
                        <option value="America/Chicago">CST (UTC-6)</option>
                        <option value="America/Los_Angeles">PST (UTC-8)</option>
                        <option value="Europe/London">GMT (UTC+0)</option>
                        <option value="Europe/Paris">CET (UTC+1)</option>
                        <option value="Asia/Shanghai">CST (UTC+8)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Save Configuration & Generate Calendar</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Current Status Card -->
    <div class="status-card">
        <div class="card-header">
            <h2 class="card-title">📊 Season Status</h2>
        </div>

        <div id="statusContent" class="loading">Loading status...</div>
    </div>

    <!-- Calendar Card -->
    <div class="calendar-card">
        <div class="card-header">
            <h2 class="card-title">📅 Event Calendar</h2>
        </div>

        <div class="week-filter" id="weekFilter"></div>
        <div id="calendarContent" class="loading">Loading calendar...</div>
    </div>
</div>

<!-- Channel Selection Modal -->
<div id="channelModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Select Channels</h3>
            <button class="modal-close" onclick="closeChannelModal()">&times;</button>
        </div>

        <div class="channel-list" id="channelList"></div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeChannelModal()">Cancel</button>
            <button class="btn btn-success" onclick="sendAnnouncement()">📤 Send Announcement</button>
        </div>
    </div>
</div>

<script>
let currentConfig = null;
let currentCalendar = null;
let selectedEventId = null;
let selectedChannels = [];
let availableChannels = [];
let activeWeekFilter = 0;

// Load data on page load
document.addEventListener('DOMContentLoaded', async function() {
    await loadConfig();
    await loadCalendar();
    await loadChannels();

    // Setup config form if admin
    <?php if ($can_configure): ?>
    document.getElementById('configForm').addEventListener('submit', handleConfigSubmit);
    <?php endif; ?>
});

async function loadConfig() {
    try {
        const response = await fetch('season2_api.php?action=get_config', {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success) {
            currentConfig = data.config;

            <?php if ($can_configure): ?>
            // Populate form
            if (data.config.season_start_date) {
                document.getElementById('seasonStartDate').value = data.config.season_start_date;
            }
            document.getElementById('seasonStartTime').value = data.config.season_start_time || '00:00';
            document.getElementById('factionWarTime').value = data.config.faction_war_time || '14:00';
            document.getElementById('serverTimezone').value = data.config.server_timezone || 'UTC';
            <?php endif; ?>
        }
    } catch (error) {
        showAlert('error', 'Failed to load configuration: ' + error.message);
    }
}

async function loadCalendar() {
    try {
        const response = await fetch('season2_api.php?action=get_calendar', {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success) {
            currentCalendar = data.calendar;
            renderStatus(data.calendar);
            renderCalendar(data.calendar);
        }
    } catch (error) {
        showAlert('error', 'Failed to load calendar: ' + error.message);
        document.getElementById('calendarContent').innerHTML = '<div class="empty-state">Error loading calendar</div>';
    }
}

async function loadChannels() {
    try {
        const response = await fetch('discord_api.php?action=get_channels', {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success) {
            availableChannels = data.channels;
        }
    } catch (error) {
        console.error('Failed to load channels:', error);
    }
}

function renderStatus(calendar) {
    const container = document.getElementById('statusContent');

    if (!calendar.season_start_date) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Season 2 start date not configured.<br><?php echo $can_configure ? 'Set the start date above to activate events.' : 'Contact an admin to configure the season start date.'; ?></p></div>';
        return;
    }

    const statusHtml = `
        <div class="status-grid">
            <div class="status-item">
                <div class="status-label">Current Week</div>
                <div class="status-value">Week ${calendar.current_week || 1}</div>
            </div>
            <div class="status-item">
                <div class="status-label">Day in Week</div>
                <div class="status-value">Day ${calendar.current_day || 1}</div>
            </div>
            <div class="status-item">
                <div class="status-label">Days Elapsed</div>
                <div class="status-value">${calendar.days_elapsed || 0}</div>
            </div>
            <div class="status-item">
                <div class="status-label">Total Events</div>
                <div class="status-value">${calendar.events ? calendar.events.length : 0}</div>
            </div>
        </div>
    `;

    container.innerHTML = statusHtml;
}

function renderCalendar(calendar) {
    const container = document.getElementById('calendarContent');
    const filterContainer = document.getElementById('weekFilter');

    if (!calendar.events || calendar.events.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📅</div><p>No events scheduled.<br>Configure the season start date to generate the calendar.</p></div>';
        filterContainer.innerHTML = '';
        return;
    }

    // Create week filter tabs
    const weeks = [0, 1, 2, 3, 4, 5, 6, 7];
    filterContainer.innerHTML = weeks.map(week =>
        `<button class="week-tab ${week === 0 ? 'active' : ''}" onclick="filterByWeek(${week})">
            ${week === 0 ? 'All' : 'Week ' + week}
        </button>`
    ).join('');

    renderEvents(calendar.events);
}

function filterByWeek(week) {
    activeWeekFilter = week;

    // Update active tab
    document.querySelectorAll('.week-tab').forEach((tab, index) => {
        tab.classList.toggle('active', index === week);
    });

    const events = week === 0
        ? currentCalendar.events
        : currentCalendar.events.filter(e => e.week === week);

    renderEvents(events);
}

function renderEvents(events) {
    const container = document.getElementById('calendarContent');

    if (events.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📅</div><p>No events for this week.</p></div>';
        return;
    }

    const eventsHtml = events.map(event => {
        const importanceClass = event.importance || 'medium';
        const badgeClass = 'badge-' + importanceClass;
        const datetime = new Date(event.datetime);
        const isPast = datetime < new Date();

        return `
            <div class="event-card ${importanceClass}">
                <div class="event-header">
                    <h3 class="event-name">${escapeHtml(event.name)}</h3>
                    <span class="event-badge ${badgeClass}">${importanceClass}</span>
                </div>

                <div class="event-details">
                    <div class="event-detail">
                        📅 ${formatDate(event.datetime)}
                    </div>
                    <div class="event-detail">
                        ⏰ ${formatTime(event.datetime)}
                    </div>
                    <div class="event-detail">
                        📍 Week ${event.week}
                    </div>
                    <div class="event-detail">
                        ${isPast ? '✅ Past' : '⏳ Upcoming'}
                    </div>
                </div>

                <div class="event-description">${escapeHtml(event.description)}</div>

                <div class="event-actions">
                    <button class="btn btn-success" onclick="openAnnouncementModal('${event.id}')">
                        📢 Announce Now
                    </button>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = `<div class="event-grid">${eventsHtml}</div>`;
}

async function handleConfigSubmit(e) {
    e.preventDefault();

    const formData = {
        season_start_date: document.getElementById('seasonStartDate').value,
        season_start_time: document.getElementById('seasonStartTime').value,
        faction_war_time: document.getElementById('factionWarTime').value,
        server_timezone: document.getElementById('serverTimezone').value
    };

    try {
        const response = await fetch('season2_api.php?action=update_config', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', `Configuration saved! Generated ${data.events_generated} events.`);
            await loadCalendar();
        } else {
            showAlert('error', data.error || 'Failed to save configuration');
        }
    } catch (error) {
        showAlert('error', 'Error saving configuration: ' + error.message);
    }
}

function openAnnouncementModal(eventId) {
    selectedEventId = eventId;
    selectedChannels = [];

    // Render channels
    const channelList = document.getElementById('channelList');
    channelList.innerHTML = availableChannels.map(channel => `
        <div class="channel-item" onclick="toggleChannel('${channel.id}')">
            <input type="checkbox" id="channel_${channel.id}">
            <label style="cursor: pointer; flex: 1;">${escapeHtml(channel.display_name || channel.name)}</label>
        </div>
    `).join('');

    document.getElementById('channelModal').classList.add('active');
}

function closeChannelModal() {
    document.getElementById('channelModal').classList.remove('active');
    selectedEventId = null;
    selectedChannels = [];
}

function toggleChannel(channelId) {
    const checkbox = document.getElementById('channel_' + channelId);
    checkbox.checked = !checkbox.checked;

    if (checkbox.checked) {
        if (!selectedChannels.includes(channelId)) {
            selectedChannels.push(channelId);
        }
    } else {
        selectedChannels = selectedChannels.filter(id => id !== channelId);
    }
}

async function sendAnnouncement() {
    if (selectedChannels.length === 0) {
        showAlert('warning', 'Please select at least one channel');
        return;
    }

    try {
        const response = await fetch('season2_api.php?action=announce_event', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify({
                event_id: selectedEventId,
                channel_ids: selectedChannels
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', data.message);
            closeChannelModal();
        } else {
            showAlert('error', data.error || 'Failed to send announcement');
        }
    } catch (error) {
        showAlert('error', 'Error sending announcement: ' + error.message);
    }
}

function showAlert(type, message) {
    const container = document.getElementById('alertContainer');
    const alertClass = 'alert-' + type;

    container.innerHTML = `<div class="alert ${alertClass}">${escapeHtml(message)}</div>`;

    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function formatDate(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
}
</script>

<?php include 'includes/footer.php'; ?>
