<?php
/**
 * System Metrics Dashboard
 * Version: 1.0.0
 *
 * CloudWatch-style metrics dashboard for monitoring system activity
 */

require_once 'jwt.php';

$user = require_admin_session();

$page_title = "System Metrics";

include 'includes/header.php';
?>

<div class="page-header">
    <div class="header-content">
        <div class="header-left">
            <h1 class="page-title">
                <span class="title-icon">📊</span>
                System Metrics
            </h1>
            <p class="page-subtitle">Monitor system activity and performance</p>
        </div>
        <div class="header-right">
            <select id="timeRange" class="time-range-select">
                <option value="1h">Last Hour</option>
                <option value="24h" selected>Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
                <option value="90d">Last 90 Days</option>
            </select>
            <button id="refreshBtn" class="btn btn-primary">
                <span class="btn-icon">🔄</span>
                Refresh
            </button>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="summary-section" id="summarySection">
    <div class="summary-card">
        <div class="summary-label">Total Events</div>
        <div class="summary-value" id="totalEvents">—</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Active Users</div>
        <div class="summary-value" id="activeUsers">—</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Time Range</div>
        <div class="summary-value" id="timeRangeDisplay">24 Hours</div>
    </div>
</div>

<!-- Metrics Grid -->
<div class="metrics-grid">
    <!-- Discord Messages -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>💬 Discord Messages Sent</h3>
            <span class="metric-info" title="Tracks all Discord messages sent through the admin panel">ℹ️</span>
        </div>
        <div class="chart-container">
            <canvas id="discordChart"></canvas>
        </div>
        <div class="metric-footer">
            <span class="legend-item"><span class="legend-dot" style="background: #3498db"></span> Announcements</span>
            <span class="legend-item"><span class="legend-dot" style="background: #9b59b6"></span> Scheduled</span>
            <span class="legend-item"><span class="legend-dot" style="background: #e67e22"></span> Recurring</span>
        </div>
    </div>

    <!-- Login Attempts -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>🔐 Login Attempts</h3>
            <span class="metric-info" title="Successful vs failed login attempts">ℹ️</span>
        </div>
        <div class="chart-container">
            <canvas id="loginChart"></canvas>
        </div>
        <div class="metric-footer">
            <span class="legend-item"><span class="legend-dot" style="background: #27ae60"></span> Successful</span>
            <span class="legend-item"><span class="legend-dot" style="background: #e74c3c"></span> Failed</span>
        </div>
    </div>

    <!-- Data Operations -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>📝 Data Operations</h3>
            <span class="metric-info" title="Create, update, and delete operations">ℹ️</span>
        </div>
        <div class="chart-container">
            <canvas id="dataOpsChart"></canvas>
        </div>
        <div class="metric-footer">
            <span class="legend-item"><span class="legend-dot" style="background: #27ae60"></span> Creates</span>
            <span class="legend-item"><span class="legend-dot" style="background: #f39c12"></span> Updates</span>
            <span class="legend-item"><span class="legend-dot" style="background: #e74c3c"></span> Deletes</span>
        </div>
    </div>

    <!-- User Activity -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>👥 User Activity by Role</h3>
            <span class="metric-info" title="Activity breakdown by user role">ℹ️</span>
        </div>
        <div class="chart-container">
            <canvas id="userActivityChart"></canvas>
        </div>
        <div class="metric-footer">
            <span class="legend-item"><span class="legend-dot" style="background: #e74c3c"></span> Admins</span>
            <span class="legend-item"><span class="legend-dot" style="background: #f39c12"></span> R5</span>
            <span class="legend-item"><span class="legend-dot" style="background: #3498db"></span> R4</span>
        </div>
    </div>

    <!-- Backups -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>💾 Backup Operations</h3>
            <span class="metric-info" title="Manual, automatic backups and restores">ℹ️</span>
        </div>
        <div class="chart-container">
            <canvas id="backupsChart"></canvas>
        </div>
        <div class="metric-footer">
            <span class="legend-item"><span class="legend-dot" style="background: #3498db"></span> Manual</span>
            <span class="legend-item"><span class="legend-dot" style="background: #27ae60"></span> Auto</span>
            <span class="legend-item"><span class="legend-dot" style="background: #e74c3c"></span> Restores</span>
        </div>
    </div>

    <!-- Top Actions -->
    <div class="metric-card">
        <div class="metric-header">
            <h3>🔥 Top Actions</h3>
            <span class="metric-info" title="Most frequent audit log actions">ℹ️</span>
        </div>
        <div class="top-actions-list" id="topActionsList">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<script src="includes/chart.umd.min.js"></script>
<script>
// Chart.js configuration
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary');

const charts = {};

// Initialize all charts
function initCharts() {
    const chartConfig = {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        tooltipFormat: 'MMM d, h:mm a'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    };

    charts.discord = new Chart(document.getElementById('discordChart'), { ...chartConfig });
    charts.login = new Chart(document.getElementById('loginChart'), { ...chartConfig });
    charts.dataOps = new Chart(document.getElementById('dataOpsChart'), { ...chartConfig });
    charts.userActivity = new Chart(document.getElementById('userActivityChart'), { ...chartConfig });
    charts.backups = new Chart(document.getElementById('backupsChart'), { ...chartConfig });
}

// Load metrics data
async function loadMetrics() {
    const timeRange = document.getElementById('timeRange').value;

    try {
        // Load all metrics in parallel
        const [discord, login, dataOps, userActivity, backups, summary] = await Promise.all([
            fetch(`metrics_api.php?action=discord_messages&timeRange=${timeRange}`).then(r => r.json()),
            fetch(`metrics_api.php?action=login_attempts&timeRange=${timeRange}`).then(r => r.json()),
            fetch(`metrics_api.php?action=data_operations&timeRange=${timeRange}`).then(r => r.json()),
            fetch(`metrics_api.php?action=user_activity&timeRange=${timeRange}`).then(r => r.json()),
            fetch(`metrics_api.php?action=backups&timeRange=${timeRange}`).then(r => r.json()),
            fetch(`metrics_api.php?action=summary&timeRange=${timeRange}`).then(r => r.json())
        ]);

        // Update summary stats
        if (summary.success) {
            document.getElementById('totalEvents').textContent = summary.summary.total_events.toLocaleString();
            document.getElementById('activeUsers').textContent = summary.summary.unique_users.toLocaleString();

            // Update top actions
            updateTopActions(summary.summary.top_actions);
        }

        // Update charts
        updateChart(charts.discord, discord.data);
        updateChart(charts.login, login.data);
        updateChart(charts.dataOps, dataOps.data);
        updateChart(charts.userActivity, userActivity.data);
        updateChart(charts.backups, backups.data);

    } catch (error) {
        console.error('Failed to load metrics:', error);
        showAlert('error', 'Failed to load metrics data');
    }
}

// Update chart data
function updateChart(chart, data) {
    chart.data.labels = data.labels.map(ts => new Date(ts * 1000));
    chart.data.datasets = data.datasets.map(ds => ({
        label: ds.label,
        data: ds.data,
        borderColor: ds.color,
        backgroundColor: ds.color + '20',
        borderWidth: 2,
        fill: true,
        tension: 0.4
    }));
    chart.update();
}

// Update top actions list
function updateTopActions(actions) {
    const list = document.getElementById('topActionsList');
    if (!actions || Object.keys(actions).length === 0) {
        list.innerHTML = '<div class="no-data">No data available</div>';
        return;
    }

    let html = '<div class="actions-list">';
    for (const [action, count] of Object.entries(actions)) {
        const displayName = action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        html += `
            <div class="action-item">
                <span class="action-name">${displayName}</span>
                <span class="action-count">${count.toLocaleString()}</span>
            </div>
        `;
    }
    html += '</div>';
    list.innerHTML = html;
}

// Time range change handler
document.getElementById('timeRange').addEventListener('change', function() {
    const labels = {
        '1h': 'Last Hour',
        '24h': '24 Hours',
        '7d': '7 Days',
        '30d': '30 Days',
        '90d': '90 Days'
    };
    document.getElementById('timeRangeDisplay').textContent = labels[this.value];
    loadMetrics();
});

// Refresh button
document.getElementById('refreshBtn').addEventListener('click', loadMetrics);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadMetrics();

    // Auto-refresh every 60 seconds
    setInterval(loadMetrics, 60000);
});

// Simple alert function
function showAlert(type, message) {
    alert(message);
}
</script>

<style>
.page-header .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.header-left {
    flex: 1;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.time-range-select {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 0.95rem;
    cursor: pointer;
}

/* Summary Section */
.summary-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.summary-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.summary-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
}

.metric-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.metric-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.metric-info {
    font-size: 1.2rem;
    cursor: help;
    opacity: 0.5;
}

.chart-container {
    height: 250px;
    position: relative;
}

.metric-footer {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

/* Top Actions List */
.top-actions-list {
    min-height: 250px;
}

.actions-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.action-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--bg-tertiary);
    border-radius: 8px;
}

.action-name {
    font-size: 0.9rem;
    color: var(--text-primary);
}

.action-count {
    font-weight: 700;
    font-size: 1rem;
    color: #3498db;
}

.loading,
.no-data {
    text-align: center;
    color: var(--text-secondary);
    padding: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }

    .page-header .header-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-right {
        width: 100%;
    }

    .time-range-select {
        flex: 1;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
