<?php
/**
 * Migration History
 * Version: 1.0.0
 *
 * Track all system migrations, version changes, and data backups
 * Admin-only access
 */

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Require admin role
if (!has_role($user, ['admin'])) {
    header('Location: error_403.php');
    exit();
}

// Set page title for header
$page_title = "Migration History";

// Include shared header
include 'includes/header.php';
?>

<div class="migration-container">
    <div class="page-header">
        <div class="header-content">
            <h1>🔄 Migration History</h1>
            <p class="subtitle">System version tracking and data migration management</p>
        </div>
        <div class="header-actions">
            <button id="createBackupBtn" class="btn btn-primary">
                <span class="btn-icon">💾</span>
                Create Backup
            </button>
        </div>
    </div>

    <!-- Current Version Card -->
    <div class="version-card">
        <div class="loading-spinner" id="versionLoading">Loading...</div>
        <div id="versionContent" style="display: none;">
            <div class="version-main">
                <div class="version-badge">
                    <span class="version-label">Current Version</span>
                    <span class="version-number" id="currentVersion">-</span>
                </div>
                <div class="version-info">
                    <div class="info-item">
                        <span class="info-label">Last Migration:</span>
                        <span class="info-value" id="lastMigrationDate">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Backup:</span>
                        <span class="info-value" id="lastBackupDate">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Migration Timeline -->
    <div class="timeline-section">
        <h2>Migration Timeline</h2>
        <div class="loading-spinner" id="timelineLoading">Loading migrations...</div>
        <div id="timelineContent" style="display: none;">
            <div id="migrationsTimeline" class="timeline">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="system-info">
        <h3>System Information</h3>
        <div id="systemInfo" class="info-grid">
            <!-- Populated by JavaScript -->
        </div>
    </div>
</div>

<style>
.migration-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.header-content h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.subtitle {
    color: #666;
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.version-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.version-main {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.version-badge {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.version-label {
    font-size: 0.875rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.version-number {
    font-size: 3rem;
    font-weight: 700;
    color: #667eea;
}

.version-info {
    display: flex;
    gap: 3rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.875rem;
    color: #666;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

.timeline-section {
    margin: 3rem 0;
}

.timeline-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}

.timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2rem;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    border: 3px solid #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.timeline-item.warning::before {
    border-color: #f39c12;
}

.migration-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border-left: 4px solid #667eea;
}

.migration-card.warning {
    border-left-color: #f39c12;
}

.migration-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.migration-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.migration-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #666;
}

.migration-type {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.migration-type.major { background: #fee; color: #c33; }
.migration-type.feature { background: #e6f7ff; color: #0066cc; }
.migration-type.minor { background: #f0f0f0; color: #666; }
.migration-type.initial { background: #e8f5e9; color: #2e7d32; }

.migration-changes {
    margin-top: 1rem;
}

.migration-changes ul {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0 0 0;
}

.migration-changes li {
    padding: 0.25rem 0 0.25rem 1.5rem;
    position: relative;
    font-size: 0.875rem;
}

.migration-changes li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #27ae60;
    font-weight: bold;
}

.backup-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
}

.backup-status.yes { color: #27ae60; }
.backup-status.no { color: #e74c3c; }

.system-info {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.system-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-grid .info-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.loading-spinner {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
    }

    .version-main {
        flex-direction: column;
        align-items: flex-start;
    }

    .version-info {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script src="includes/scripts.js"></script>
<script>
let currentHistory = null;

async function loadMigrationHistory() {
    const versionLoading = document.getElementById('versionLoading');
    const versionContent = document.getElementById('versionContent');
    const timelineLoading = document.getElementById('timelineLoading');
    const timelineContent = document.getElementById('timelineContent');

    try {
        const response = await fetch('migration_history_api.php?action=get_history', {
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success) {
            currentHistory = result.data;

            // Update current version
            document.getElementById('currentVersion').textContent = 'v' + result.data.currentVersion;

            // Last migration
            const lastMigration = result.data.migrations[result.data.migrations.length - 1];
            if (lastMigration) {
                const date = new Date(lastMigration.timestamp);
                document.getElementById('lastMigrationDate').textContent = date.toLocaleDateString();
            }

            // Last backup
            if (result.data.lastBackup) {
                const date = new Date(result.data.lastBackup.timestamp);
                document.getElementById('lastBackupDate').textContent = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            } else {
                document.getElementById('lastBackupDate').innerHTML = '<span style="color: #e74c3c;">Never</span>';
            }

            versionLoading.style.display = 'none';
            versionContent.style.display = 'block';

            // Render timeline
            renderTimeline(result.data.migrations);
            timelineLoading.style.display = 'none';
            timelineContent.style.display = 'block';

            // Render system info
            renderSystemInfo(result.data.system);
        } else {
            showError('Failed to load migration history: ' + result.error);
        }
    } catch (error) {
        showError('Error loading migration history: ' + error.message);
    }
}

function renderTimeline(migrations) {
    const timeline = document.getElementById('migrationsTimeline');
    timeline.innerHTML = '';

    // Reverse to show newest first
    const sortedMigrations = [...migrations].reverse();

    sortedMigrations.forEach(migration => {
        const item = document.createElement('div');
        item.className = 'timeline-item' + (migration.notes ? ' warning' : '');

        const card = document.createElement('div');
        card.className = 'migration-card' + (migration.notes ? ' warning' : '');

        const header = document.createElement('div');
        header.className = 'migration-header';

        const titleDiv = document.createElement('div');
        const title = document.createElement('div');
        title.className = 'migration-title';
        title.textContent = 'v' + migration.version;
        titleDiv.appendChild(title);

        const meta = document.createElement('div');
        meta.className = 'migration-meta';
        const date = new Date(migration.timestamp);
        meta.innerHTML = '<span>' + date.toLocaleDateString() + '</span> <span>•</span> <span>by ' + escapeHtml(migration.performedBy) + '</span>';
        titleDiv.appendChild(meta);

        const typeSpan = document.createElement('span');
        typeSpan.className = 'migration-type ' + migration.type;
        typeSpan.textContent = migration.type;

        header.appendChild(titleDiv);
        header.appendChild(typeSpan);

        const desc = document.createElement('p');
        desc.textContent = migration.description;

        card.appendChild(header);
        card.appendChild(desc);

        if (migration.changes && migration.changes.length > 0) {
            const changesDiv = document.createElement('div');
            changesDiv.className = 'migration-changes';
            changesDiv.innerHTML = '<strong>Changes:</strong>';
            const changesList = document.createElement('ul');
            migration.changes.forEach(change => {
                const li = document.createElement('li');
                li.textContent = change;
                changesList.appendChild(li);
            });
            changesDiv.appendChild(changesList);
            card.appendChild(changesDiv);
        }

        const backupStatus = document.createElement('div');
        backupStatus.className = 'backup-status ' + (migration.backupTaken ? 'yes' : 'no');
        backupStatus.innerHTML = migration.backupTaken
            ? '✓ Backup taken' + (migration.backupPath ? ' (' + escapeHtml(migration.backupPath) + ')' : '')
            : '⚠ No backup taken';
        card.appendChild(backupStatus);

        if (migration.notes) {
            const notes = document.createElement('div');
            notes.className = 'alert alert-warning';
            notes.textContent = migration.notes;
            card.appendChild(notes);
        }

        item.appendChild(card);
        timeline.appendChild(item);
    });
}

function renderSystemInfo(system) {
    const info = document.getElementById('systemInfo');
    const writable = system.data_directory_writable;
    const backupExists = system.backup_directory_exists;

    info.innerHTML = `
        <div class="info-item">
            <span class="info-label">PHP Version</span>
            <span class="info-value">${escapeHtml(system.php_version)}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Server Time</span>
            <span class="info-value">${new Date(system.server_time).toLocaleString()}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data Directory</span>
            <span class="info-value" style="color: ${writable ? '#27ae60' : '#e74c3c'}">${writable ? '✓ Writable' : '✗ Not writable'}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Backup Directory</span>
            <span class="info-value" style="color: ${backupExists ? '#27ae60' : '#f39c12'}">${backupExists ? '✓ Exists' : '⚠ Will be created'}</span>
        </div>
    `;
}

async function createBackup() {
    if (!confirm('Create a backup of all critical data files?\n\nThis will backup:\n- alliances.json\n- rotation-schedule.json\n- signature-history.json\n- users.json\n- and more...')) {
        return;
    }

    const btn = document.getElementById('createBackupBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-icon">⏳</span> Creating backup...';

    try {
        const response = await fetch('migration_history_api.php?action=create_backup', {
            method: 'POST',
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success) {
            showSuccess('Backup created successfully! Files backed up: ' + result.backup.files_backed_up);
            loadMigrationHistory(); // Reload to show new backup
        } else {
            showError('Failed to create backup: ' + result.error);
        }
    } catch (error) {
        showError('Error creating backup: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-icon">💾</span> Create Backup';
    }
}

function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.textContent = message;
    const container = document.querySelector('.migration-container');
    container.insertBefore(alert, container.firstChild);
    setTimeout(() => alert.remove(), 5000);
}

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.textContent = message;
    const container = document.querySelector('.migration-container');
    container.insertBefore(alert, container.firstChild);
    setTimeout(() => alert.remove(), 5000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadMigrationHistory();
    document.getElementById('createBackupBtn').addEventListener('click', createBackup);
});
</script>

<?php include 'includes/footer.php'; ?>
