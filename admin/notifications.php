<?php
/**
 * Notifications Center
 * Version: 1.0.0
 *
 * View and manage all notifications
 */

require_once 'jwt.php';

$user = require_admin_session();

$page_title = "Notifications";

include 'includes/header.php';
?>

<div class="page-header">
    <div class="header-content">
        <div class="header-left">
            <h1 class="page-title">
                <span class="title-icon">🔔</span>
                Notifications
            </h1>
            <p class="page-subtitle">View and manage your notifications</p>
        </div>
        <div class="header-right">
            <button id="markAllReadBtn" class="btn btn-secondary">
                <span class="btn-icon">✓</span>
                Mark All Read
            </button>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs mb-6">
    <button class="filter-tab active" data-filter="all">
        All <span class="tab-badge" id="countAll">0</span>
    </button>
    <button class="filter-tab" data-filter="unread">
        Unread <span class="tab-badge" id="countUnread">0</span>
    </button>
    <button class="filter-tab" data-filter="info">
        Info <span class="tab-badge info" id="countInfo">0</span>
    </button>
    <button class="filter-tab" data-filter="success">
        Success <span class="tab-badge success" id="countSuccess">0</span>
    </button>
    <button class="filter-tab" data-filter="warning">
        Warning <span class="tab-badge warning" id="countWarning">0</span>
    </button>
    <button class="filter-tab" data-filter="error">
        Error <span class="tab-badge error" id="countError">0</span>
    </button>
</div>

<!-- Notifications List -->
<div id="notificationsList" class="notifications-list">
    <div class="loading-spinner">Loading notifications...</div>
</div>

<style>
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 0.5rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.5rem 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-secondary);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-tab:hover {
    background: var(--bg-tertiary);
    border-color: var(--accent);
}

.filter-tab.active {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.tab-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.filter-tab.active .tab-badge {
    background: rgba(255, 255, 255, 0.3);
}

.tab-badge.info { background: #3498db; color: white; }
.tab-badge.success { background: #27ae60; color: white; }
.tab-badge.warning { background: #f39c12; color: white; }
.tab-badge.error { background: #e74c3c; color: white; }

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-height: 300px;
}

.notification-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-left: 4px solid var(--accent);
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.2s;
    cursor: pointer;
}

.notification-card:hover {
    border-color: var(--accent);
    box-shadow: var(--shadow-md);
}

.notification-card.unread {
    background: linear-gradient(to right, rgba(52, 152, 219, 0.05), transparent);
}

.notification-card.info { border-left-color: #3498db; }
.notification-card.success { border-left-color: #27ae60; }
.notification-card.warning { border-left-color: #f39c12; }
.notification-card.error { border-left-color: #e74c3c; }

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.notification-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.notification-type-badge.info { background: #3498db; color: white; }
.notification-type-badge.success { background: #27ae60; color: white; }
.notification-type-badge.warning { background: #f39c12; color: white; }
.notification-type-badge.error { background: #e74c3c; color: white; }
.notification-type-badge.vote_request { background: #9b59b6; color: white; }

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.unread-dot {
    width: 8px;
    height: 8px;
    background: #3498db;
    border-radius: 50%;
    display: inline-block;
}

.notification-message {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1rem;
    white-space: pre-wrap;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.notification-action-btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.notification-action-btn.primary {
    background: var(--accent);
    color: white;
}

.notification-action-btn.primary:hover {
    opacity: 0.9;
}

.notification-action-btn.secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.notification-action-btn.secondary:hover {
    background: var(--border-color);
}

.loading-spinner {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<script>
let allNotifications = [];
let currentFilter = 'all';

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('notifications_api.php?action=get_notifications', {
            credentials: 'include'
        });

        const result = await response.json();

        if (result.success) {
            allNotifications = result.notifications;
            updateCounts();
            renderNotifications();
        } else {
            showError('Failed to load notifications');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        showError('Failed to load notifications');
    }
}

// Update notification counts
function updateCounts() {
    const counts = {
        all: allNotifications.length,
        unread: allNotifications.filter(n => !n.read_by.includes('<?php echo $user->sub; ?>')).length,
        info: allNotifications.filter(n => n.type === 'info').length,
        success: allNotifications.filter(n => n.type === 'success').length,
        warning: allNotifications.filter(n => n.type === 'warning').length,
        error: allNotifications.filter(n => n.type === 'error').length
    };

    Object.entries(counts).forEach(([type, count]) => {
        const badge = document.getElementById(`count${type.charAt(0).toUpperCase() + type.slice(1)}`);
        if (badge) {
            badge.textContent = count;
        }
    });
}

// Render notifications
function renderNotifications() {
    const list = document.getElementById('notificationsList');

    // Filter notifications
    let filtered = allNotifications;
    if (currentFilter === 'unread') {
        filtered = allNotifications.filter(n => !n.read_by.includes('<?php echo $user->sub; ?>'));
    } else if (currentFilter !== 'all') {
        filtered = allNotifications.filter(n => n.type === currentFilter);
    }

    if (filtered.length === 0) {
        list.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No notifications</h3>
                <p>You're all caught up!</p>
            </div>
        `;
        return;
    }

    list.innerHTML = filtered.map(notification => {
        const isUnread = !notification.read_by.includes('<?php echo $user->sub; ?>');
        const timestamp = new Date(notification.created_at).toLocaleString();

        let actionButtons = '';
        if (notification.action_url) {
            actionButtons = `<a href="${notification.action_url}" class="notification-action-btn primary">View Details</a>`;
        }
        if (isUnread) {
            actionButtons += `<button onclick="markAsRead('${notification.id}')" class="notification-action-btn secondary">Mark as Read</button>`;
        }

        return `
            <div class="notification-card ${notification.type} ${isUnread ? 'unread' : ''}" onclick="openNotification('${notification.id}')">
                <div class="notification-header">
                    <div class="notification-title">
                        ${isUnread ? '<span class="unread-dot"></span>' : ''}
                        ${notification.title}
                    </div>
                    <span class="notification-type-badge ${notification.type}">${notification.type}</span>
                </div>

                <div class="notification-meta">
                    <span>📅 ${timestamp}</span>
                    ${notification.priority ? `<span>⚡ ${notification.priority}</span>` : ''}
                </div>

                <div class="notification-message">${notification.message}</div>

                ${actionButtons ? `<div class="notification-actions">${actionButtons}</div>` : ''}
            </div>
        `;
    }).join('');
}

// Open notification (mark as read)
async function openNotification(notificationId) {
    const notification = allNotifications.find(n => n.id === notificationId);
    if (!notification) return;

    // Mark as read if unread
    if (!notification.read_by.includes('<?php echo $user->sub; ?>')) {
        await markAsRead(notificationId);
    }

    // Navigate to action URL if exists
    if (notification.action_url) {
        window.location.href = notification.action_url;
    }
}

// Mark notification as read
async function markAsRead(notificationId) {
    try {
        const response = await fetch('notifications_api.php?action=mark_read', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        });

        const result = await response.json();

        if (result.success) {
            // Update local state
            const notification = allNotifications.find(n => n.id === notificationId);
            if (notification && !notification.read_by.includes('<?php echo $user->sub; ?>')) {
                notification.read_by.push('<?php echo $user->sub; ?>');
            }
            updateCounts();
            renderNotifications();
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark all as read
async function markAllRead() {
    const unreadIds = allNotifications
        .filter(n => !n.read_by.includes('<?php echo $user->sub; ?>'))
        .map(n => n.id);

    if (unreadIds.length === 0) {
        return;
    }

    if (!confirm(`Mark ${unreadIds.length} notifications as read?`)) {
        return;
    }

    try {
        for (const id of unreadIds) {
            await markAsRead(id);
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
        showError('Failed to mark all notifications as read');
    }
}

// Filter tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        renderNotifications();
    });
});

// Mark all read button
document.getElementById('markAllReadBtn').addEventListener('click', markAllRead);

// Show error
function showError(message) {
    const list = document.getElementById('notificationsList');
    list.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">⚠️</div>
            <h3>Error</h3>
            <p>${message}</p>
        </div>
    `;
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', loadNotifications);

// Auto-refresh every 60 seconds
setInterval(loadNotifications, 60000);
</script>

<?php include 'includes/footer.php'; ?>
