<?php
/**
 * Alliance Backup & Restore Interface
 *
 * View and restore alliance data from automatic backups
 *
 * @version 1.0.0
 * @date 2025-10-15
 * @changelog
 *   1.0.0 (2025-10-15) - Initial implementation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/audit_logger.php';

// Require admin session only
$user = require_admin_session();

// Get backups
$backups = get_alliance_backups(100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alliance Backup & Restore - Last War 1586 Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; font-size: 24px; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>💾 Alliance Backup & Restore</h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

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
                                <strong><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($backup['timestamp']))) ?></strong><br>
                                <small style="color: #999;"><?= htmlspecialchars(time_ago($backup['timestamp'])) ?></small>
                            </td>
                            <td>
                                <span class="backup-reason reason-<?= htmlspecialchars($backup['reason']) ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $backup['reason']))) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($backup['user']) ?></td>
                            <td><?= htmlspecialchars($backup['alliance_count']) ?></td>
                            <td><?= htmlspecialchars(format_bytes($backup['size'])) ?></td>
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
    <div id="preview-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; overflow: auto;">
        <div style="background: white; max-width: 800px; margin: 50px auto; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #333; margin: 0;">Backup Preview</h2>
                <button onclick="closePreview()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #999;">×</button>
            </div>
            <div id="preview-content" style="max-height: 500px; overflow-y: auto;">
                Loading...
            </div>
        </div>
    </div>

    <script>
        function restoreBackup(filename, timestamp) {
            if (!confirm(`⚠️ Are you sure you want to restore from this backup?\n\nBackup: ${filename}\nTimestamp: ${timestamp}\n\nThis will replace the current alliance data.\nA backup of the current state will be created first.`)) {
                return;
            }

            // Second confirmation
            if (!confirm('This action will affect the live site immediately.\n\nType OK to confirm (case-sensitive):') || prompt('Type OK to confirm:') !== 'OK') {
                alert('Restore cancelled.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('filename', filename);

            fetch('backup_restore_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Backup restored successfully!\n\n' + (data.message || ''));
                    location.reload();
                } else {
                    alert('❌ Error: ' + (data.error || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error restoring backup:', error);
                alert('Failed to restore backup. Please try again.');
            });
        }

        async function viewBackup(filename) {
            const modal = document.getElementById('preview-modal');
            const content = document.getElementById('preview-content');

            modal.style.display = 'block';
            content.innerHTML = 'Loading backup preview...';

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

        function closePreview() {
            document.getElementById('preview-modal').style.display = 'none';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on outside click
        document.getElementById('preview-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePreview();
            }
        });
    </script>
</body>
</html>

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
?>
