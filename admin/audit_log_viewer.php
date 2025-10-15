<?php
/**
 * Audit Log Viewer
 *
 * Real-time tail-like viewer for admin activity logs
 *
 * @version 1.0.0
 * @date 2025-10-15
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation with real-time updates
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Require admin session
$user = require_admin_session();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Viewer - Last War 1586 Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .header {
            background: #252526;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            color: #4ec9b0;
            font-size: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .header .nav {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            background: #0e639c;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .btn:hover {
            background: #1177bb;
        }
        .btn-secondary {
            background: #3a3d41;
        }
        .btn-secondary:hover {
            background: #4a4d51;
        }
        .filters {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .filters label {
            color: #9cdcfe;
            font-size: 14px;
        }
        .filters input, .filters select {
            padding: 6px 10px;
            background: #3c3c3c;
            border: 1px solid #555;
            border-radius: 3px;
            color: #d4d4d4;
            font-size: 14px;
        }
        .filters input:focus, .filters select:focus {
            outline: none;
            border-color: #0e639c;
        }
        .controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .status {
            background: #1e1e1e;
            padding: 10px 15px;
            border-radius: 3px;
            border-left: 3px solid #4ec9b0;
            font-size: 13px;
        }
        .status.auto-refresh {
            border-left-color: #4ec9b0;
        }
        .status.paused {
            border-left-color: #f48771;
        }
        .log-container {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            border-radius: 5px;
            padding: 15px;
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .log-entry {
            border-bottom: 1px solid #3c3c3c;
            padding: 10px 0;
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .log-entry.new {
            animation: highlight 1s ease-out;
        }
        @keyframes highlight {
            0% { background: #2d5016; }
            100% { background: transparent; }
        }
        .log-time {
            color: #858585;
            margin-right: 10px;
        }
        .log-action {
            color: #4ec9b0;
            font-weight: bold;
            margin-right: 10px;
        }
        .log-action.login { color: #4ec9b0; }
        .log-action.logout { color: #9cdcfe; }
        .log-action.edit_alliance_power { color: #dcdcaa; }
        .log-action.add_alliance { color: #b5cea8; }
        .log-action.delete_alliance { color: #f48771; }
        .log-action.restore_alliance_backup { color: #c586c0; }
        .log-user {
            color: #ce9178;
        }
        .log-ip {
            color: #858585;
            font-size: 11px;
            margin-left: 10px;
        }
        .log-details {
            color: #808080;
            margin-top: 5px;
            padding-left: 20px;
            font-size: 12px;
        }
        .log-details .key {
            color: #9cdcfe;
        }
        .log-details .value {
            color: #ce9178;
        }
        .log-details .change {
            margin-left: 15px;
        }
        .log-details .old {
            color: #f48771;
            text-decoration: line-through;
        }
        .log-details .new {
            color: #b5cea8;
        }
        .empty {
            text-align: center;
            color: #858585;
            padding: 40px;
        }
        .scroll-hint {
            text-align: center;
            color: #858585;
            font-size: 12px;
            margin-top: 10px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Audit Log Viewer</h1>
        <div class="nav">
            <button class="btn btn-secondary" onclick="location.href='dashboard.php'">← Dashboard</button>
            <button class="btn" onclick="downloadLogs()">💾 Export Logs</button>
        </div>
    </div>

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
        <div class="controls">
            <button class="btn" onclick="applyFilters()">Apply Filters</button>
            <button class="btn btn-secondary" onclick="clearFilters()">Clear</button>
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
                        <span class="log-time"><?= htmlspecialchars($log['timestamp']) ?></span>
                        <span class="log-action <?= htmlspecialchars($log['action']) ?>">[<?= htmlspecialchars($log['action']) ?>]</span>
                        <span class="log-user"><?= htmlspecialchars($log['user']) ?></span>
                        <span class="log-ip">(<?= htmlspecialchars($log['ip']) ?>)</span>
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

                const response = await fetch(url);
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
                    <span class="log-time">${escapeHtml(log.timestamp)}</span>
                    <span class="log-action ${escapeHtml(log.action)}">[${escapeHtml(log.action)}]</span>
                    <span class="log-user">${escapeHtml(log.user)}</span>
                    <span class="log-ip">(${escapeHtml(log.ip)})</span>
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

        // Start auto-refresh on load
        startAutoRefresh();

        // Enter key applies filters
        document.getElementById('filter-user').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
    </script>
</body>
</html>

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
?>
