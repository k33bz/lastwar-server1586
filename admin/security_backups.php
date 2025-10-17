<?php
/**
 * Security: Backup & Restore Interface
 *
 * View and restore alliance data from automatic backups
 *
 * @version 3.1.0
 * @date 2025-10-17
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation
 *   3.0.0 (2025-10-16) - Renamed to security_backups.php for consistency
 *   3.1.0 (2025-10-17) - Fixed modal popup issue on page load
 *                       - Added DOMContentLoaded and pageshow event listeners to force modals closed
 *                       - Added parameter validation to prevent accidental modal triggers
 *                       - Prevents modals from appearing on browser back/forward navigation
 */

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Check if user has admin access
if ($user->aud !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Set page title for header
$page_title = "Backup & Restore";

// Load audit logger for backup functions
require_once __DIR__ . '/audit_logger.php';

// Get backups (function is defined in audit_logger.php)
$backups = get_alliance_backups(100);

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">💾 Backup & Restore</h1>
    <p class="page-description">View and restore alliance data from automatic backups</p>
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
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #31708f;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #856404;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .backup-reason {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 11px;
            color: white;
        }
        .reason-power_edit { background: #3498db; }
        .reason-add_alliance { background: #28a745; }
        .reason-delete_alliance { background: #e74c3c; }
        .reason-pre_restore { background: #9b59b6; }
        .actions {
            display: flex;
            gap: 8px;
        }
        .empty {
            text-align: center;
            color: #999;
            padding: 40px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow: auto;
        }
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            line-height: 1;
            padding: 0;
        }
        .modal-close:hover {
            color: #333;
        }
    </style>

    <div class="section">
        <h2>About Backups</h2>
        <div class="info-box">
            <strong>ℹ️ Automatic Backups</strong><br>
            A backup is automatically created every time an admin or power editor:
            <ul style="margin: 10px 0 0 20px;">
                <li><strong>Edits</strong> alliance power values</li>
                <li><strong>Adds</strong> a new alliance</li>
                <li><strong>Deletes</strong> an alliance</li>
                <li><strong>Restores</strong> from a backup (creates a "pre-restore" backup)</li>
            </ul>
            <div style="margin-top: 15px;">
                <button onclick="createManualBackup()" class="btn btn-primary">
                    💾 Create Manual Backup Now
                </button>
            </div>
        </div>

        <div class="warning-box">
            <strong>⚠️ Restore Warning</strong><br>
            Restoring a backup will:
            <ul style="margin: 10px 0 0 20px;">
                <li>Replace the current alliance data with the backup data</li>
                <li>Create a "pre-restore" backup of the current state first</li>
                <li>Update the public site rankings immediately</li>
                <li>Be logged in the audit log</li>
            </ul>
        </div>
    </div>

    <div class="section">
        <h2>Available Backups (<?= count($backups) ?>)</h2>

        <?php if (empty($backups)): ?>
            <div class="empty">
                No backups found. Backups will appear here after alliance data is modified.
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Reason</th>
                        <th>User</th>
                        <th>Alliances</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <strong><?= isset($backup['timestamp']) ? htmlspecialchars(date('Y-m-d H:i:s', strtotime($backup['timestamp']))) : 'N/A' ?></strong><br>
                                <small style="color: #999;"><?= isset($backup['timestamp']) ? htmlspecialchars(time_ago($backup['timestamp'])) : 'N/A' ?></small>
                            </td>
                            <td>
                                <span class="backup-reason reason-<?= htmlspecialchars($backup['reason']) ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $backup['reason']))) ?>
                                </span>
                            </td>
                            <td>
                                <?= emailDisplay($backup['user'] ?? 'unknown', true) ?>
                            </td>
                            <td><?= isset($backup['alliance_count']) ? htmlspecialchars($backup['alliance_count']) : '0' ?></td>
                            <td><?= isset($backup['size']) ? htmlspecialchars(format_bytes($backup['size'])) : 'N/A' ?></td>
                            <td>
                                <div class="actions">
                                    <button
                                        class="btn btn-success btn-small"
                                        onclick="restoreBackup('<?= htmlspecialchars($backup['filename'], ENT_QUOTES) ?>', '<?= htmlspecialchars($backup['timestamp'], ENT_QUOTES) ?>')"
                                    >
                                        ⏮ Restore
                                    </button>
                                    <button
                                        class="btn btn-primary btn-small"
                                        onclick="viewBackup('<?= htmlspecialchars($backup['filename'], ENT_QUOTES) ?>')"
                                    >
                                        👁 Preview
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Preview Modal -->
    <div id="preview-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 style="color: #333; margin: 0;">Backup Preview</h2>
                <button onclick="closeModal('preview-modal')" class="modal-close">×</button>
            </div>
            <div id="preview-content" style="max-height: 500px; overflow-y: auto; margin-top: 20px;">
                Loading...
            </div>
        </div>
    </div>

    <!-- Manual Backup Modal -->
    <div id="manual-backup-modal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 style="color: #333; margin: 0;">Create Manual Backup</h2>
                <button onclick="closeModal('manual-backup-modal')" class="modal-close">×</button>
            </div>
            <div style="margin-top: 20px;">
                <label for="backup-reason" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">Reason for backup (optional):</label>
                <input type="text" id="backup-reason" placeholder="e.g., Before major changes" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px;">
                <div style="margin-top: 20px; text-align: right;">
                    <button onclick="closeModal('manual-backup-modal')" class="btn btn-secondary" style="margin-right: 10px;">Cancel</button>
                    <button onclick="confirmManualBackup()" class="btn btn-primary">Create Backup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restore-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 style="color: #333; margin: 0;">⚠️ Confirm Restore</h2>
                <button onclick="closeModal('restore-modal')" class="modal-close">×</button>
            </div>
            <div style="margin-top: 20px;">
                <div class="warning-box" style="margin-bottom: 20px;">
                    <strong>Warning: This action will affect the live site immediately!</strong>
                    <div style="margin-top: 10px;">
                        <div><strong>Backup:</strong> <span id="restore-filename"></span></div>
                        <div><strong>Timestamp:</strong> <span id="restore-timestamp"></span></div>
                    </div>
                </div>
                <p style="margin-bottom: 15px;">This will:</p>
                <ul style="margin-left: 20px; margin-bottom: 20px; color: #666;">
                    <li>Replace the current alliance data with the backup data</li>
                    <li>Create a "pre-restore" backup of the current state first</li>
                    <li>Update the public site rankings immediately</li>
                </ul>
                <label for="restore-confirm" style="display: block; margin-bottom: 8px; color: #333; font-weight: 500;">Type "OK" to confirm (case-sensitive):</label>
                <input type="text" id="restore-confirm" placeholder="OK" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px; margin-bottom: 20px;">
                <div style="text-align: right;">
                    <button onclick="closeModal('restore-modal')" class="btn btn-secondary" style="margin-right: 10px;">Cancel</button>
                    <button onclick="confirmRestore()" class="btn btn-success">Restore Backup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="message-modal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="message-title" style="color: #333; margin: 0;"></h2>
                <button onclick="closeMessageModal()" class="modal-close">×</button>
            </div>
            <div id="message-content" style="margin-top: 20px; padding: 15px; border-radius: 5px;">
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button onclick="closeMessageModal()" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <script>
        let pendingRestoreFilename = '';

        // Ensure all modals are hidden on page load
        window.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });

        // Ensure modals stay hidden on page show (back/forward navigation)
        window.addEventListener('pageshow', function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });

        function createManualBackup() {
            document.getElementById('backup-reason').value = '';
            openModal('manual-backup-modal');
        }

        function confirmManualBackup() {
            const reason = document.getElementById('backup-reason').value || 'manual';

            const formData = new FormData();
            formData.append('action', 'manual_backup');
            formData.append('reason', reason);

            closeModal('manual-backup-modal');

            fetch('backup_restore_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Success', '✅ Manual backup created successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('Error', '❌ ' + (data.error || 'Unknown error occurred'), 'error');
                }
            })
            .catch(error => {
                console.error('Error creating manual backup:', error);
                showMessage('Error', '❌ Failed to create manual backup. Please try again.', 'error');
            });
        }

        function restoreBackup(filename, timestamp) {
            // Validate parameters to prevent accidental triggers
            if (!filename || filename === '' || filename === 'undefined') {
                console.error('Invalid filename for restore:', filename);
                return;
            }

            pendingRestoreFilename = filename;
            document.getElementById('restore-filename').textContent = filename;
            document.getElementById('restore-timestamp').textContent = timestamp || 'N/A';
            document.getElementById('restore-confirm').value = '';
            openModal('restore-modal');
        }

        function confirmRestore() {
            const confirmation = document.getElementById('restore-confirm').value;

            if (confirmation !== 'OK') {
                showMessage('Error', '❌ You must type "OK" exactly to confirm the restore.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('filename', pendingRestoreFilename);

            closeModal('restore-modal');

            fetch('backup_restore_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Success', '✅ Backup restored successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('Error', '❌ ' + (data.error || 'Unknown error occurred'), 'error');
                }
            })
            .catch(error => {
                console.error('Error restoring backup:', error);
                showMessage('Error', '❌ Failed to restore backup. Please try again.', 'error');
            });
        }

        async function viewBackup(filename) {
            // Validate parameters to prevent accidental triggers
            if (!filename || filename === '' || filename === 'undefined') {
                console.error('Invalid filename for preview:', filename);
                return;
            }

            const content = document.getElementById('preview-content');
            content.innerHTML = 'Loading backup preview...';
            openModal('preview-modal');

            try {
                const response = await fetch('backup_restore_api.php?action=preview&filename=' + encodeURIComponent(filename));
                const data = await response.json();

                if (data.success && data.alliances) {
                    let html = '<table style="width: 100%; border-collapse: collapse;">';
                    html += '<thead><tr style="background: #f8f9fa;"><th style="padding: 10px; text-align: left;">Rank</th><th style="padding: 10px; text-align: left;">Tag</th><th style="padding: 10px; text-align: left;">Name</th><th style="padding: 10px; text-align: left;">Power</th></tr></thead>';
                    html += '<tbody>';

                    data.alliances.forEach((alliance, index) => {
                        html += `<tr style="border-bottom: 1px solid #eee;">`;
                        html += `<td style="padding: 10px;">${index + 1}</td>`;
                        html += `<td style="padding: 10px; font-weight: bold;">${escapeHtml(alliance.tag || 'N/A')}</td>`;
                        html += `<td style="padding: 10px;">${escapeHtml(alliance.name || 'N/A')}</td>`;
                        html += `<td style="padding: 10px;">${Number(alliance.power || 0).toLocaleString()}</td>`;
                        html += `</tr>`;
                    });

                    html += '</tbody></table>';

                    html += `<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">`;
                    html += `<strong>Backup Metadata:</strong><br>`;
                    html += `<small>Timestamp: ${escapeHtml(data.timestamp || 'N/A')}<br>`;
                    html += `User: ${escapeHtml(data.user || 'N/A')}<br>`;
                    html += `Reason: ${escapeHtml(data.reason || 'N/A')}<br>`;
                    html += `Total Alliances: ${data.alliances.length}</small>`;
                    html += `</div>`;

                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<p style="color: #e74c3c;">Error loading backup preview.</p>';
                }
            } catch (error) {
                console.error('Error viewing backup:', error);
                content.innerHTML = '<p style="color: #e74c3c;">Failed to load backup preview.</p>';
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function showMessage(title, message, type) {
            document.getElementById('message-title').textContent = title;
            const content = document.getElementById('message-content');
            content.textContent = message;

            // Style based on type
            if (type === 'success') {
                content.style.backgroundColor = '#d4edda';
                content.style.color = '#155724';
                content.style.border = '1px solid #c3e6cb';
            } else if (type === 'error') {
                content.style.backgroundColor = '#f8d7da';
                content.style.color = '#721c24';
                content.style.border = '1px solid #f5c6cb';
            } else {
                content.style.backgroundColor = '#d1ecf1';
                content.style.color = '#0c5460';
                content.style.border = '1px solid #bee5eb';
            }

            openModal('message-modal');
        }

        function closeMessageModal() {
            closeModal('message-modal');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
</div>

<?php
/**
 * Format bytes to human-readable size
 */
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Calculate time ago from timestamp
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

include 'includes/footer.php';
?>
