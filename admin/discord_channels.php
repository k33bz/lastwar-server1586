<?php
/**
 * Discord Channel Management
 * Version: 1.0.0
 *
 * Centralized interface for managing Discord channels across all alliances
 */

require_once 'jwt.php';

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

$page_title = "Discord Channels";

include 'includes/header.php';
?>

<div class="page-header">
    <div class="header-content">
        <h1 class="page-title">
            <span class="title-icon">📡</span>
            Discord Channel Management
        </h1>
        <p class="page-subtitle">Manage all Discord channels across alliances</p>
    </div>
</div>

<div class="channels-container">
    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label for="filterAlliance">Alliance:</label>
            <select id="filterAlliance" class="filter-select">
                <option value="">All Alliances</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="filterType">Type:</label>
            <select id="filterType" class="filter-select">
                <option value="">All Types</option>
                <option value="general">General</option>
                <option value="announcements">Announcements</option>
                <option value="events">Events</option>
                <option value="alerts">Alerts</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="filterSource">Source:</label>
            <select id="filterSource" class="filter-select">
                <option value="">All Sources</option>
                <option value="alliance">Alliance-Specific</option>
                <option value="global">Global</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="filterStatus">Status:</label>
            <select id="filterStatus" class="filter-select">
                <option value="">All Statuses</option>
                <option value="enabled">Enabled</option>
                <option value="disabled">Disabled</option>
            </select>
        </div>

        <div class="filter-group">
            <input type="text" id="filterSearch" class="filter-search" placeholder="Search channels...">
        </div>

        <div class="filter-actions">
            <button class="btn btn-secondary" onclick="clearFilters()">
                <span class="btn-icon">🔄</span>
                Clear Filters
            </button>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" style="display: none;"></div>

    <!-- Channels Grid -->
    <div class="channels-grid" id="channelsGrid">
        <!-- Channels will be loaded here -->
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading channels...</p>
        </div>
    </div>
</div>

<!-- Edit Channel Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Edit Channel</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editChannelForm">
                <input type="hidden" id="editChannelId">
                <input type="hidden" id="editChannelSource">
                <input type="hidden" id="editChannelAlliance">

                <div class="form-group">
                    <label for="editChannelName">Channel Name:</label>
                    <input type="text" id="editChannelName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="editChannelType">Channel Type:</label>
                    <select id="editChannelType" class="form-input">
                        <option value="general">General</option>
                        <option value="announcements">Announcements</option>
                        <option value="events">Events</option>
                        <option value="alerts">Alerts</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editWebhookUrl">Webhook URL:</label>
                    <input type="url" id="editWebhookUrl" class="form-input" required>
                    <small>Discord webhook URL for this channel</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="editChannelEnabled">
                        <span>Channel Enabled</span>
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="testWebhook()">
                        <span class="btn-icon">🧪</span>
                        Test Webhook
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #5865F2 0%, #4752C4 100%);
    color: white;
    padding: 2rem 0;
    margin: -2rem -2rem 2rem -2rem;
    border-radius: 0 0 20px 20px;
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.title-icon {
    font-size: 2.5rem;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.channels-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Filters */
.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 150px;
}

.filter-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}

.filter-select,
.filter-search {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.filter-select:focus,
.filter-search:focus {
    outline: none;
    border-color: #5865F2;
    box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1);
}

.filter-search {
    min-width: 200px;
}

.filter-actions {
    margin-left: auto;
    display: flex;
    align-items: flex-end;
}

/* Channels Grid */
.channels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.channel-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #5865F2;
    transition: all 0.3s ease;
    position: relative;
}

.channel-card.disabled {
    opacity: 0.6;
    border-left-color: #6c757d;
}

.channel-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

.channel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.channel-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.channel-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.channel-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-alliance {
    background: #e8eafc;
    color: #5865F2;
}

.badge-global {
    background: #caf0f8;
    color: #0077b6;
}

.badge-type {
    background: #f8f9fa;
    color: #6c757d;
}

.badge-enabled {
    background: #d4edda;
    color: #155724;
}

.badge-disabled {
    background: #f8d7da;
    color: #721c24;
}

.channel-info {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.channel-info strong {
    color: #495057;
}

.channel-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.btn-small {
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
}

.loading-spinner {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #5865F2;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #6c757d;
    cursor: pointer;
    transition: color 0.3s ease;
}

.modal-close:hover {
    color: #e74c3c;
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #5865F2;
    box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1);
}

.form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #6c757d;
    font-size: 0.85rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

/* Alert */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
}

.alert-info {
    background: #d1ecf1;
    border-left: 4px solid #17a2b8;
    color: #0c5460;
}

/* Buttons */
.btn {
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    border: none;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #5865F2 0%, #4752C4 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(88, 101, 242, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-icon {
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .channels-grid {
        grid-template-columns: 1fr;
    }

    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        width: 100%;
    }

    .filter-actions {
        margin-left: 0;
    }

    .page-header {
        margin: -1rem -1rem 1rem -1rem;
    }
}
</style>

<script>
let allChannels = [];
let filteredChannels = [];

// Load channels on page load
document.addEventListener('DOMContentLoaded', function() {
    loadChannels();

    // Setup filter event listeners
    document.getElementById('filterAlliance').addEventListener('change', applyFilters);
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterSource').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    document.getElementById('filterSearch').addEventListener('input', applyFilters);

    // Setup form submission
    document.getElementById('editChannelForm').addEventListener('submit', saveChannel);
});

// Load all channels
async function loadChannels() {
    try {
        const response = await fetch('discord_channels_api.php?action=list');
        const data = await response.json();

        if (data.success) {
            allChannels = data.channels;
            filteredChannels = [...allChannels];
            populateAllianceFilter();
            renderChannels();
        } else {
            showAlert('error', 'Failed to load channels: ' + data.error);
        }
    } catch (error) {
        showAlert('error', 'Error loading channels: ' + error.message);
    }
}

// Populate alliance filter dropdown
function populateAllianceFilter() {
    const allianceFilter = document.getElementById('filterAlliance');
    const alliances = new Set();

    allChannels.forEach(channel => {
        alliances.add(channel.alliance_name);
    });

    Array.from(alliances).sort().forEach(alliance => {
        const option = document.createElement('option');
        option.value = alliance;
        option.textContent = alliance;
        allianceFilter.appendChild(option);
    });
}

// Apply filters
function applyFilters() {
    const allianceFilter = document.getElementById('filterAlliance').value.toLowerCase();
    const typeFilter = document.getElementById('filterType').value.toLowerCase();
    const sourceFilter = document.getElementById('filterSource').value.toLowerCase();
    const statusFilter = document.getElementById('filterStatus').value.toLowerCase();
    const searchFilter = document.getElementById('filterSearch').value.toLowerCase();

    filteredChannels = allChannels.filter(channel => {
        if (allianceFilter && channel.alliance_name.toLowerCase() !== allianceFilter) return false;
        if (typeFilter && channel.type !== typeFilter) return false;
        if (sourceFilter && channel.source !== sourceFilter) return false;
        if (statusFilter === 'enabled' && !channel.enabled) return false;
        if (statusFilter === 'disabled' && channel.enabled) return false;
        if (searchFilter && !channel.name.toLowerCase().includes(searchFilter)) return false;

        return true;
    });

    renderChannels();
}

// Clear filters
function clearFilters() {
    document.getElementById('filterAlliance').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterSource').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSearch').value = '';
    applyFilters();
}

// Render channels grid
function renderChannels() {
    const grid = document.getElementById('channelsGrid');

    if (filteredChannels.length === 0) {
        grid.innerHTML = `
            <div class="loading-spinner">
                <p style="font-size: 1.1rem; color: #6c757d;">No channels found</p>
                <p style="font-size: 0.9rem; color: #adb5bd;">Try adjusting your filters</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = filteredChannels.map(channel => `
        <div class="channel-card ${channel.enabled ? '' : 'disabled'}">
            <div class="channel-header">
                <div>
                    <div class="channel-name">${escapeHtml(channel.name)}</div>
                    <div class="channel-meta">
                        <span class="channel-badge ${channel.source === 'alliance' ? 'badge-alliance' : 'badge-global'}">
                            ${channel.source === 'alliance' ? '🏛️ Alliance' : '🌐 Global'}
                        </span>
                        <span class="channel-badge badge-type">${escapeHtml(channel.type)}</span>
                        <span class="channel-badge ${channel.enabled ? 'badge-enabled' : 'badge-disabled'}">
                            ${channel.enabled ? '✓ Enabled' : '✗ Disabled'}
                        </span>
                    </div>
                </div>
            </div>

            <div class="channel-info">
                <strong>Alliance:</strong> ${escapeHtml(channel.alliance_name)}
            </div>
            <div class="channel-info">
                <strong>Server:</strong> ${escapeHtml(channel.server_name)}
            </div>

            <div class="channel-actions">
                ${channel.can_edit ? `
                    <button class="btn btn-primary btn-small" onclick='editChannel(${JSON.stringify(channel)})'>
                        <span class="btn-icon">✏️</span>
                        Edit
                    </button>
                    <button class="btn btn-secondary btn-small" onclick="toggleChannel('${escapeHtml(channel.id)}', '${channel.source}', '${escapeHtml(channel.alliance)}', ${!channel.enabled})">
                        <span class="btn-icon">${channel.enabled ? '🚫' : '✅'}</span>
                        ${channel.enabled ? 'Disable' : 'Enable'}
                    </button>
                ` : `
                    <span style="color: #6c757d; font-size: 0.85rem;">Read-only</span>
                `}
            </div>
        </div>
    `).join('');
}

// Edit channel
function editChannel(channel) {
    document.getElementById('editChannelId').value = channel.id;
    document.getElementById('editChannelSource').value = channel.source;
    document.getElementById('editChannelAlliance').value = channel.alliance;
    document.getElementById('editChannelName').value = channel.name;
    document.getElementById('editChannelType').value = channel.type;
    document.getElementById('editWebhookUrl').value = channel.webhook_url;
    document.getElementById('editChannelEnabled').checked = channel.enabled;
    document.getElementById('modalTitle').textContent = `Edit Channel: ${channel.name}`;

    document.getElementById('editModal').classList.add('active');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// Save channel
async function saveChannel(e) {
    e.preventDefault();

    const csrfToken = getCsrfToken();
    const payload = {
        id: document.getElementById('editChannelId').value,
        source: document.getElementById('editChannelSource').value,
        alliance: document.getElementById('editChannelAlliance').value,
        name: document.getElementById('editChannelName').value,
        type: document.getElementById('editChannelType').value,
        webhook_url: document.getElementById('editWebhookUrl').value,
        enabled: document.getElementById('editChannelEnabled').checked
    };

    try {
        const response = await fetch('discord_channels_api.php?action=update_channel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', 'Channel updated successfully!');
            closeEditModal();
            loadChannels();
        } else {
            showAlert('error', 'Failed to update channel: ' + data.error);
        }
    } catch (error) {
        showAlert('error', 'Error updating channel: ' + error.message);
    }
}

// Toggle channel enabled/disabled
async function toggleChannel(channelId, source, alliance, enabled) {
    const csrfToken = getCsrfToken();
    const formData = new FormData();
    formData.append('channel_id', channelId);
    formData.append('source', source);
    formData.append('alliance', alliance);
    formData.append('enabled', enabled);

    try {
        const response = await fetch('discord_channels_api.php?action=toggle', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', `Channel ${enabled ? 'enabled' : 'disabled'} successfully!`);
            loadChannels();
        } else {
            showAlert('error', 'Failed to toggle channel: ' + data.error);
        }
    } catch (error) {
        showAlert('error', 'Error toggling channel: ' + error.message);
    }
}

// Test webhook
async function testWebhook() {
    const webhookUrl = document.getElementById('editWebhookUrl').value;

    if (!webhookUrl) {
        showAlert('error', 'Please enter a webhook URL first');
        return;
    }

    const csrfToken = getCsrfToken();
    const formData = new FormData();
    formData.append('webhook_url', webhookUrl);

    try {
        const response = await fetch('discord_channels_api.php?action=test_webhook', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('error', 'Webhook test failed: ' + data.error);
        }
    } catch (error) {
        showAlert('error', 'Error testing webhook: ' + error.message);
    }
}

// Show alert
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-error' : 'alert-info');

    alertContainer.innerHTML = `
        <div class="alert ${alertClass}">
            <span>${escapeHtml(message)}</span>
        </div>
    `;
    alertContainer.style.display = 'block';

    setTimeout(() => {
        alertContainer.style.display = 'none';
    }, 5000);
}

// Get CSRF token
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'includes/footer.php'; ?>
