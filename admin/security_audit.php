<?php
/**
 * Security: Audit Log Viewer
 *
 * Real-time tail-like viewer for admin activity logs
 *
 * @version 3.0.0
 * @date 2025-10-16
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation with real-time updates
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'json_helpers.php';

$user = require_jwt_session();

// Check if user has admin access
if ($user->aud !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Set page title for header
$page_title = "Audit Logs";

// Mock function to get audit logs
function get_audit_logs($filters = [], $limit = 100, $offset = 0) {
    $audit_file = __DIR__ . '/audit_log.json';
    $logs = [];
    
    if (file_exists($audit_file)) {
        $data = json_decode(file_get_contents($audit_file), true);
        $raw_logs = $data['logs'] ?? [];
        
        // Normalize log entries to handle inconsistent field names
        foreach ($raw_logs as $log) {
            $normalized_log = [
                'id' => $log['id'] ?? 'unknown',
                'timestamp' => $log['timestamp'] ?? 'unknown',
                'action' => $log['action'] ?? 'unknown',
                'user' => $log['user'] ?? $log['user_email'] ?? 'unknown',
                'ip' => $log['ip'] ?? $log['ip_address'] ?? 'unknown',
                'user_agent' => $log['user_agent'] ?? 'unknown',
                'details' => $log['details'] ?? []
            ];
            $logs[] = $normalized_log;
        }
        
        // Apply filters
        if (!empty($filters['user'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return stripos($log['user'], $filters['user']) !== false;
            });
        }
        
        if (!empty($filters['action'])) {
            $logs = array_filter($logs, function($log) use ($filters) {
                return $log['action'] === $filters['action'];
            });
        }
        
        // Sort by timestamp (newest first)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Apply limit and offset
        $logs = array_slice($logs, $offset, $limit);
    }
    
    return $logs;
}

// Get filters from query string
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$limit = (int)($_GET['limit'] ?? 100);

// Get logs
$logs = get_audit_logs([
    'user' => $filter_user,
    'action' => $filter_action
], $limit, 0);

// Get unique actions for filter dropdown
$all_logs = get_audit_logs([], 1000, 0);
$actions = [];
foreach ($all_logs as $log) {
    if (!in_array($log['action'], $actions)) {
        $actions[] = $log['action'];
    }
}
sort($actions);

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📋 Audit Logs</h1>
    <p class="page-description">Real-time viewer for admin activity logs</p>
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
        
        .filters {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            border: 1px solid #e9ecef;
        }
        
        .filters label {
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .filters input, .filters select {
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 4px;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .filters input:focus, .filters select:focus {
            outline: none;
            border-color: #2c3e50;
            box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.1);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #34495e;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .timezone-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .timezone-label {
            font-size: 0.9rem;
            color: #495057;
            margin: 0;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
        }
        
        .toggle-switch input[type="checkbox"] {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: relative;
            display: flex;
            align-items: center;
            width: 120px;
            height: 32px;
            background: #e9ecef;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
        }
        
        .toggle-slider:hover {
            border-color: #adb5bd;
        }
        
        .toggle-option {
            position: absolute;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: all 0.3s ease;
            z-index: 2;
            pointer-events: none;
        }
        
        .toggle-utc {
            left: 8px;
            color: #495057;
        }
        
        .toggle-local {
            right: 8px;
            color: #495057;
        }
        
        .toggle-slider::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 56px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1;
        }
        
        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(56px);
        }
        
        .toggle-switch input:checked + .toggle-slider .toggle-utc {
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .toggle-switch input:not(:checked) + .toggle-slider .toggle-local {
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .toggle-switch input:checked + .toggle-slider .toggle-local {
            color: #343a40;
            font-weight: 600;
        }
        
        .toggle-switch input:not(:checked) + .toggle-slider .toggle-utc {
            color: #343a40;
            font-weight: 600;
        }
        
        .status {
            background: #e9ecef;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            border-left: 3px solid #2c3e50;
            font-size: 0.9rem;
            color: #495057;
        }
        
        .status.auto-refresh {
            border-left-color: #28a745;
        }
        
        .status.paused {
            border-left-color: #dc3545;
        }
        
        .log-container {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .log-entry {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry.new {
            animation: highlight 1s ease-out;
        }
        
        @keyframes highlight {
            0% { background: #d4edda; }
            100% { background: transparent; }
        }
        
        .log-time {
            color: #6c757d;
            margin-right: 1rem;
            font-weight: 500;
        }
        
        .log-action {
            color: #2c3e50;
            font-weight: bold;
            margin-right: 1rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .log-action.login { background: #d4edda; color: #155724; }
        .log-action.logout { background: #d1ecf1; color: #0c5460; }
        .log-action.edit_alliance_power { background: #fff3cd; color: #856404; }
        .log-action.add_alliance { background: #d4edda; color: #155724; }
        .log-action.delete_alliance { background: #f8d7da; color: #721c24; }
        .log-action.restore_alliance_backup { background: #e2e3f1; color: #383d41; }
        
        .log-user {
            color: #495057;
            font-weight: 500;
            cursor: help;
            position: relative;
            border-bottom: 1px dotted #6c757d;
        }

        .log-user:hover {
            color: #212529;
        }

        /* User Info Tooltip */
        .user-tooltip {
            position: absolute;
            background: #2c3e50;
            color: white;
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            min-width: 200px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .user-tooltip.show {
            opacity: 1;
        }

        .user-tooltip-header {
            font-weight: 600;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-tooltip-row {
            display: flex;
            justify-content: space-between;
            margin: 0.25rem 0;
            font-size: 0.8rem;
        }

        .user-tooltip-label {
            color: rgba(255, 255, 255, 0.7);
            margin-right: 0.5rem;
        }

        .user-tooltip-value {
            font-weight: 500;
        }

        .user-tooltip-badge {
            display: inline-block;
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
            margin: 0 0.15rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .user-tooltip-badge.role-admin {
            background: #e74c3c;
        }

        .user-tooltip-badge.role-r5 {
            background: #3498db;
        }

        .user-tooltip-badge.role-r4 {
            background: #2ecc71;
        }

        .user-tooltip-badge.role-president {
            background: #16a085;
        }

        .user-tooltip-badge.role-ape {
            background: #ffc107;
            color: #212529;
        }

        .user-tooltip-loading {
            color: rgba(255, 255, 255, 0.6);
            font-style: italic;
        }

        .log-ip {
            color: #6c757d;
            font-size: 0.8rem;
            margin-left: 1rem;
        }
        
        .log-details {
            color: #6c757d;
            margin-top: 0.5rem;
            padding-left: 1.5rem;
            font-size: 0.85rem;
        }
        
        .log-details .key {
            color: #495057;
            font-weight: 500;
        }
        
        .log-details .value {
            color: #2c3e50;
        }
        
        .log-details .change {
            margin-left: 1rem;
        }
        
        .log-details .old {
            color: #dc3545;
            text-decoration: line-through;
        }
        
        .log-details .new {
            color: #28a745;
        }
        
        .empty {
            text-align: center;
            color: #6c757d;
            padding: 3rem;
        }
        
        .scroll-hint {
            text-align: center;
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
    </style>

    <div class="filters">
        <div>
            <label>Filter by User:</label>
            <input type="text" id="filter-user" placeholder="user@example.com" value="<?= htmlspecialchars($filter_user) ?>">
        </div>
        <div>
            <label>Filter by Action:</label>
            <select id="filter-action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars($action) ?>" <?= $action === $filter_action ? 'selected' : '' ?>>
                        <?= htmlspecialchars($action) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Limit:</label>
            <select id="filter-limit">
                <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                <option value="200" <?= $limit === 200 ? 'selected' : '' ?>>200</option>
                <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500</option>
            </select>
        </div>
        <div class="timezone-control">
            <label class="timezone-label">Timezone:</label>
            <div class="toggle-switch">
                <input type="checkbox" id="timezone-toggle" onchange="toggleTimezone()">
                <label for="timezone-toggle" class="toggle-slider">
                    <span class="toggle-option toggle-utc">UTC</span>
                    <span class="toggle-option toggle-local">Local</span>
                </label>
            </div>
        </div>
        <div class="controls">
            <button class="btn" onclick="applyFilters()">Apply Filters</button>
            <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
            <button class="btn btn-secondary" onclick="downloadLogs()">📥 Download</button>
            <button class="btn btn-secondary" onclick="showRawLogs()">📄 Raw View</button>
        </div>
        <div class="status auto-refresh" id="status">
            🔄 Auto-refresh: <strong id="refresh-countdown">10</strong>s
        </div>
        <div>
            <button class="btn btn-secondary" id="toggle-refresh" onclick="toggleAutoRefresh()">⏸ Pause</button>
        </div>
    </div>

    <div class="log-container" id="log-container">
        <?php if (empty($logs)): ?>
            <div class="empty">No logs found. Logs will appear here as actions are performed.</div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <div class="log-entry" data-log-id="<?= htmlspecialchars($log['id']) ?>">
                    <div>
                        <span class="log-time" data-utc="<?= htmlspecialchars($log['timestamp'] ?? 'Unknown') ?>"><?= htmlspecialchars($log['timestamp'] ?? 'Unknown') ?></span>
                        <span class="log-action <?= htmlspecialchars($log['action'] ?? 'unknown') ?>">[<?= htmlspecialchars($log['action'] ?? 'unknown') ?>]</span>
                        <span class="log-user" data-email="<?= htmlspecialchars($log['user'] ?? 'unknown') ?>"><?= htmlspecialchars(get_user_display_name($log['user'] ?? 'unknown')) ?></span>
                        <span class="log-ip">(<?= htmlspecialchars($log['ip'] ?? 'unknown') ?>)</span>
                    </div>
                    <?php if (!empty($log['details'])): ?>
                        <div class="log-details">
                            <?= formatLogDetails($log['action'], $log['details']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="scroll-hint">
        Scroll to see more logs · Auto-refreshes every 10 seconds
    </div>

    <script>
        let autoRefreshEnabled = true;
        let refreshCountdown = 10;
        let countdownInterval = null;
        let refreshInterval = null;
        let lastLogId = '<?= !empty($logs) ? $logs[0]['id'] : '' ?>';

        function applyFilters() {
            const user = document.getElementById('filter-user').value;
            const action = document.getElementById('filter-action').value;
            const limit = document.getElementById('filter-limit').value;

            let url = 'audit_log_viewer.php?';
            if (user) url += 'user=' + encodeURIComponent(user) + '&';
            if (action) url += 'action=' + encodeURIComponent(action) + '&';
            url += 'limit=' + limit;

            window.location.href = url;
        }

        function clearFilters() {
            window.location.href = 'audit_log_viewer.php';
        }

        function toggleAutoRefresh() {
            autoRefreshEnabled = !autoRefreshEnabled;
            const btn = document.getElementById('toggle-refresh');
            const status = document.getElementById('status');

            if (autoRefreshEnabled) {
                btn.innerHTML = '⏸ Pause';
                status.className = 'status auto-refresh';
                startAutoRefresh();
            } else {
                btn.innerHTML = '▶ Resume';
                status.className = 'status paused';
                stopAutoRefresh();
            }
        }

        function startAutoRefresh() {
            refreshCountdown = 10;
            updateCountdown();

            countdownInterval = setInterval(() => {
                refreshCountdown--;
                updateCountdown();
                if (refreshCountdown <= 0) {
                    refreshCountdown = 10;
                    fetchNewLogs();
                }
            }, 1000);
        }

        function stopAutoRefresh() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
        }

        function updateCountdown() {
            const el = document.getElementById('refresh-countdown');
            if (el) el.textContent = refreshCountdown;
        }

        async function fetchNewLogs() {
            try {
                const user = document.getElementById('filter-user').value;
                const action = document.getElementById('filter-action').value;
                const limit = document.getElementById('filter-limit').value;

                let url = 'audit_log_api.php?';
                if (user) url += 'user=' + encodeURIComponent(user) + '&';
                if (action) url += 'action=' + encodeURIComponent(action) + '&';
                url += 'limit=' + limit;

                const response = await fetch(url, {
                    credentials: 'include'
                });
                const data = await response.json();

                if (data.logs && data.logs.length > 0) {
                    const container = document.getElementById('log-container');
                    const existingIds = new Set(
                        Array.from(container.querySelectorAll('.log-entry')).map(el => el.dataset.logId)
                    );

                    let hasNewLogs = false;
                    data.logs.forEach(log => {
                        if (!existingIds.has(log.id)) {
                            prependLogEntry(log);
                            hasNewLogs = true;
                        }
                    });

                    if (hasNewLogs && lastLogId !== data.logs[0].id) {
                        lastLogId = data.logs[0].id;
                    }
                }
            } catch (error) {
                console.error('Failed to fetch new logs:', error);
            }
        }

        function prependLogEntry(log) {
            const container = document.getElementById('log-container');
            const entry = document.createElement('div');
            entry.className = 'log-entry new';
            entry.dataset.logId = log.id;

            let html = `
                <div>
                    <span class="log-time" data-utc="${escapeHtml(log.timestamp || 'Unknown')}">${escapeHtml(log.timestamp || 'Unknown')}</span>
                    <span class="log-action ${escapeHtml(log.action || 'unknown')}">[${escapeHtml(log.action || 'unknown')}]</span>
                    <span class="log-user" data-email="${escapeHtml(log.user || 'unknown')}">${escapeHtml(log.display_name || log.user || 'unknown')}</span>
                    <span class="log-ip">(${escapeHtml(log.ip || 'unknown')})</span>
                </div>
            `;

            if (log.details && Object.keys(log.details).length > 0) {
                html += '<div class="log-details">' + formatDetailsClient(log.action, log.details) + '</div>';
            }

            entry.innerHTML = html;

            // Remove empty message if exists
            const emptyMsg = container.querySelector('.empty');
            if (emptyMsg) {
                emptyMsg.remove();
            }

            container.insertBefore(entry, container.firstChild);
        }

        function formatDetailsClient(action, details) {
            // Simple client-side formatting (PHP does the heavy lifting on initial load)
            let html = '';
            for (const [key, value] of Object.entries(details)) {
                html += `<span class="key">${escapeHtml(key)}:</span> `;
                html += `<span class="value">${JSON.stringify(value)}</span><br>`;
            }
            return html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function downloadLogs() {
            window.location.href = 'audit_log_api.php?action=export';
        }

        async function showRawLogs() {
            try {
                const response = await fetch('audit_log_api.php?action=raw', {
                    credentials: 'include'
                });
                const data = await response.text();

                document.getElementById('rawLogContent').value = data;
                document.getElementById('rawLogModal').style.display = 'flex';
            } catch (error) {
                alertModal('Error loading raw logs: ' + error.message, 'Error', 'error');
            }
        }

        function closeRawLogModal() {
            document.getElementById('rawLogModal').style.display = 'none';
        }

        function copyRawLogs() {
            const textarea = document.getElementById('rawLogContent');
            textarea.select();
            textarea.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');

                // Visual feedback
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.style.background = '#28a745';

                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            } catch (err) {
                showToast('Failed to copy automatically. Text has been selected - press Ctrl+C to copy.', 'warning');
            }
        }

        function downloadRawLogs() {
            const content = document.getElementById('rawLogContent').value;
            const blob = new Blob([content], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `audit_logs_raw_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // User Info Tooltip System
        const userInfoCache = {};
        let activeTooltip = null;

        async function getUserInfo(email) {
            // Return cached data if available
            if (userInfoCache[email]) {
                return userInfoCache[email];
            }

            // Fetch user info from server
            try {
                const response = await fetch('audit_log_api.php?action=get_user_info&email=' + encodeURIComponent(email), {
                    credentials: 'include'
                });
                const data = await response.json();

                if (data.success) {
                    userInfoCache[email] = data.user_info;
                    return data.user_info;
                }
            } catch (error) {
                console.error('Failed to fetch user info:', error);
            }

            return null;
        }

        function createTooltip(email, userInfo) {
            const tooltip = document.createElement('div');
            tooltip.className = 'user-tooltip';

            if (!userInfo) {
                tooltip.innerHTML = '<div class="user-tooltip-loading">Loading...</div>';
                return tooltip;
            }

            let html = '<div class="user-tooltip-header">' + escapeHtml(email) + '</div>';

            // Roles
            if (userInfo.roles && userInfo.roles.length > 0) {
                html += '<div class="user-tooltip-row">';
                html += '<span class="user-tooltip-label">Roles:</span>';
                html += '<span class="user-tooltip-value">';
                userInfo.roles.forEach(role => {
                    html += '<span class="user-tooltip-badge role-' + escapeHtml(role) + '">' + escapeHtml(role.toUpperCase()) + '</span>';
                });
                html += '</span>';
                html += '</div>';
            }

            // Alliances
            if (userInfo.alliances && userInfo.alliances.length > 0) {
                html += '<div class="user-tooltip-row">';
                html += '<span class="user-tooltip-label">Alliances:</span>';
                html += '<span class="user-tooltip-value">' + escapeHtml(userInfo.alliances.join(', ')) + '</span>';
                html += '</div>';
            }

            // In-game name
            if (userInfo.in_game_name) {
                html += '<div class="user-tooltip-row">';
                html += '<span class="user-tooltip-label">In-Game:</span>';
                html += '<span class="user-tooltip-value">' + escapeHtml(userInfo.in_game_name) + '</span>';
                html += '</div>';
            }

            tooltip.innerHTML = html;
            return tooltip;
        }

        function showTooltip(element) {
            const email = element.getAttribute('data-email');
            if (!email || email === 'unknown') return;

            // Remove any existing tooltip
            hideTooltip();

            // Create and show new tooltip
            activeTooltip = createTooltip(email, null);
            document.body.appendChild(activeTooltip);

            // Position tooltip
            const rect = element.getBoundingClientRect();
            activeTooltip.style.left = rect.left + 'px';
            activeTooltip.style.top = (rect.bottom + 5) + 'px';

            // Show tooltip
            setTimeout(() => activeTooltip.classList.add('show'), 10);

            // Fetch and update user info
            getUserInfo(email).then(userInfo => {
                if (activeTooltip && userInfo) {
                    const newTooltip = createTooltip(email, userInfo);
                    newTooltip.style.left = activeTooltip.style.left;
                    newTooltip.style.top = activeTooltip.style.top;
                    newTooltip.classList.add('show');

                    document.body.removeChild(activeTooltip);
                    activeTooltip = newTooltip;
                    document.body.appendChild(activeTooltip);
                }
            });
        }

        function hideTooltip() {
            if (activeTooltip) {
                activeTooltip.remove();
                activeTooltip = null;
            }
        }

        // Attach tooltip event listeners to all log-user elements
        function attachTooltipListeners() {
            document.querySelectorAll('.log-user').forEach(element => {
                element.addEventListener('mouseenter', function() {
                    showTooltip(this);
                });
                element.addEventListener('mouseleave', hideTooltip);
            });
        }

        // Initial attachment
        attachTooltipListeners();

        // Re-attach after new logs are prepended
        const originalPrependLogEntry = prependLogEntry;
        window.prependLogEntry = function(log) {
            originalPrependLogEntry(log);
            attachTooltipListeners();
        };

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rawLogModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Start auto-refresh on load
        startAutoRefresh();

        // Enter key applies filters
        document.getElementById('filter-user').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });

        // Timezone functionality
        function toggleTimezone() {
            const toggle = document.getElementById('timezone-toggle');
            const isLocal = toggle.checked; // true = local, false = UTC
            
            // Update all timestamp displays
            const timeElements = document.querySelectorAll('.log-time[data-utc]');
            timeElements.forEach(element => {
                const utcTime = element.getAttribute('data-utc');
                if (utcTime && utcTime !== 'Unknown') {
                    if (isLocal) {
                        element.textContent = formatToLocalTime(utcTime);
                    } else {
                        element.textContent = utcTime;
                    }
                }
            });
            
            // Save preference
            localStorage.setItem('audit-log-timezone', isLocal ? 'local' : 'utc');
        }

        function formatToLocalTime(utcTimeString) {
            try {
                // Parse the UTC time string
                const utcDate = new Date(utcTimeString);
                
                // Check if the date is valid
                if (isNaN(utcDate.getTime())) {
                    return utcTimeString; // Return original if invalid
                }
                
                // Format to local time with timezone info
                const options = {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZoneName: 'short'
                };
                
                return utcDate.toLocaleString(undefined, options);
            } catch (error) {
                console.warn('Error formatting timestamp:', error);
                return utcTimeString; // Return original if error
            }
        }

        function getTimezoneInfo() {
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const now = new Date();
                const offset = -now.getTimezoneOffset() / 60;
                const offsetString = offset >= 0 ? `+${offset}` : `${offset}`;
                return `${timezone} (UTC${offsetString})`;
            } catch (error) {
                return 'Local timezone';
            }
        }

        // Initialize timezone on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved preference
            const savedTimezone = localStorage.getItem('audit-log-timezone') || 'local';
            const toggle = document.getElementById('timezone-toggle');
            toggle.checked = savedTimezone === 'local';

            // Apply initial timezone setting
            toggleTimezone();
        });
    </script>
</div>

<!-- Raw Log Modal -->
<div id="rawLogModal" class="modal" style="display: none;">
    <div class="modal-content raw-log-modal">
        <div class="modal-header">
            <h3>📄 Raw Log Data</h3>
            <span class="close" onclick="closeRawLogModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="raw-log-controls">
                <button class="btn btn-secondary" onclick="copyRawLogs()">📋 Copy All</button>
                <button class="btn btn-secondary" onclick="downloadRawLogs()">📥 Download</button>
            </div>
            <textarea id="rawLogContent" readonly></textarea>
        </div>
    </div>
</div>

<style>
.raw-log-modal {
    max-width: 90vw;
    max-height: 90vh;
    width: 800px;
}

.raw-log-controls {
    margin-bottom: 1rem;
    display: flex;
    gap: 0.5rem;
}

#rawLogContent {
    width: 100%;
    height: 60vh;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    background: #1e1e1e;
    color: #d4d4d4;
    border: 1px solid #444;
    border-radius: 4px;
    padding: 1rem;
    resize: none;
    white-space: pre;
    overflow: auto;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #333;
}

.modal-body {
    padding: 1.5rem;
}
</style>

<?php
/**
 * Format log details for display
 */
function formatLogDetails($action, $details) {
    $html = '';

    switch ($action) {
        case 'edit_alliance_power':
            if (isset($details['alliances_modified'])) {
                $html .= '<span class="key">Modified:</span> <span class="value">' . $details['alliances_modified'] . ' alliances</span><br>';
            }
            if (isset($details['changes'])) {
                foreach ($details['changes'] as $tag => $changes) {
                    $html .= '<div class="change"><strong>' . htmlspecialchars($tag) . ':</strong> ';
                    foreach ($changes as $field => $change) {
                        $html .= '<span class="key">' . htmlspecialchars($field) . ':</span> ';
                        $html .= '<span class="old">' . htmlspecialchars($change['old']) . '</span> → ';
                        $html .= '<span class="new">' . htmlspecialchars($change['new']) . '</span> ';
                    }
                    $html .= '</div>';
                }
            }
            break;

        case 'add_alliance':
            $html .= '<span class="key">Tag:</span> <span class="value">' . htmlspecialchars($details['alliance_tag'] ?? '') . '</span>, ';
            $html .= '<span class="key">Name:</span> <span class="value">' . htmlspecialchars($details['alliance_name'] ?? '') . '</span>, ';
            $html .= '<span class="key">Power:</span> <span class="value">' . number_format($details['initial_power'] ?? 0) . '</span>';
            break;

        case 'delete_alliance':
            $html .= '<span class="key">Tag:</span> <span class="value">' . htmlspecialchars($details['alliance_tag'] ?? '') . '</span>, ';
            $html .= '<span class="key">Name:</span> <span class="value">' . htmlspecialchars($details['alliance_name'] ?? '') . '</span>, ';
            $html .= '<span class="key">Power:</span> <span class="value">' . number_format($details['alliance_power'] ?? 0) . '</span>';
            break;

        case 'restore_alliance_backup':
            $html .= '<span class="key">Backup:</span> <span class="value">' . htmlspecialchars($details['backup_file'] ?? '') . '</span>, ';
            $html .= '<span class="key">Original User:</span> <span class="value">' . htmlspecialchars($details['original_user'] ?? '') . '</span>';
            break;

        case 'login':
            $html .= '<span class="key">Method:</span> <span class="value">' . htmlspecialchars($details['method'] ?? 'unknown') . '</span>';
            break;

        default:
            foreach ($details as $key => $value) {
                $html .= '<span class="key">' . htmlspecialchars($key) . ':</span> ';
                $html .= '<span class="value">' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</span><br>';
            }
    }

    return $html;
}

include 'includes/footer.php';
?>
