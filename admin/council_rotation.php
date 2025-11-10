<?php
/**
 * Council Rotation Schedule Management
 * Version: 1.0.0
 *
 * Allows admins and presidents to regenerate the council rotation schedule.
 * Preserves past and current weeks, only regenerates future weeks.
 *
 * Access: Admin and President roles only
 */

require_once 'jwt.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

if (!has_role($user, ['admin', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

log_audit_event('council_rotation_page_accessed', $user->sub, [
    'user_role' => $user->aud
]);

$page_title = "Council Rotation Management";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🗳️ Council Rotation Management</h1>
    <p class="page-description">Regenerate the council rotation schedule for future weeks</p>
</div>

<div class="container">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .status-card { background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .status-item { text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 6px; }
        .status-label { font-size: 0.875rem; color: #6c757d; margin-bottom: 0.5rem; }
        .status-value { font-size: 1.5rem; font-weight: 700; color: #333; }
        .info-box { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; display: none; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; display: none; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .pool-list { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }
        .pool-tag { background: #e9ecef; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600; }
        .schedule-preview { margin-top: 2rem; }
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .schedule-table th { background: #667eea; color: white; padding: 0.75rem; text-align: left; }
        .schedule-table td { padding: 0.75rem; border-bottom: 1px solid #e9ecef; }
        .schedule-table tr:hover { background: #f8f9fa; }
        .week-current { background: #d1ecf1 !important; font-weight: 600; }
        .week-next { background: #fff3cd !important; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(0,0,0,0.5); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d; }
        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
        .loading { display: none; text-align: center; padding: 1rem; color: #667eea; }
        .loading.active { display: block; }
        .fairness-table { width: 100%; margin-top: 1rem; border-collapse: collapse; }
        .fairness-table th, .fairness-table td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #e9ecef; }
        .fairness-table th { background: #f8f9fa; font-weight: 600; }
    </style>

    <div id="successAlert" class="success-box"></div>
    <div id="errorAlert" class="error-box"></div>

    <div class="info-box">
        <strong>ℹ️ How it works:</strong><br>
        This tool regenerates the council rotation schedule for future weeks while preserving all past history.
        The algorithm ensures fair distribution of rotating council seats (ranks 6-15) with no back-to-back rotations.
    </div>

    <div class="status-card">
        <h2>📊 Current Status</h2>
        <div class="loading active" id="statusLoading">Loading status...</div>
        <div id="statusContent" style="display: none;">
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Current Week</div>
                    <div class="status-value" id="currentWeek">-</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Next Rotation Week</div>
                    <div class="status-value" id="nextRotationWeek">-</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Next Rotation Date</div>
                    <div class="status-value" style="font-size: 1rem;" id="nextRotationDate">-</div>
                </div>
                <div class="status-item">
                    <div class="status-label">Total Weeks in Schedule</div>
                    <div class="status-value" id="scheduleWeeks">-</div>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <h3>Rotating Pool (Ranks 6-15)</h3>
                <div class="pool-list" id="rotatingPool"></div>
            </div>

            <div style="margin-top: 2rem;">
                <div class="status-label">Last Generated:</div>
                <div style="font-size: 1rem; color: #333; margin-top: 0.5rem;" id="lastGenerated">Never</div>
            </div>
        </div>
    </div>

    <div class="status-card">
        <h2>⚙️ Regenerate Schedule</h2>
        <div class="warning-box">
            <strong>⚠️ Important:</strong><br>
            • Past and current weeks will NOT be modified<br>
            • Only future weeks (starting from next rotation) will be regenerated<br>
            • All future weeks will be recalculated for fair distribution<br>
            • Use this after alliance rankings change
        </div>

        <button class="btn btn-primary" onclick="openRegenerateModal()">
            🔄 Regenerate Future Weeks
        </button>
    </div>
</div>

<!-- Regenerate Confirmation Modal -->
<div id="regenerateModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirm Schedule Regeneration</h2>
            <button class="modal-close" onclick="closeRegenerateModal()">&times;</button>
        </div>

        <div>
            <p>This will regenerate the council rotation schedule for the next <strong>52 weeks</strong> starting from week <strong id="confirmNextWeek">-</strong>.</p>

            <div class="warning-box">
                <strong>What will happen:</strong><br>
                • All weeks before week <span id="confirmBeforeWeek">-</span> will be preserved unchanged<br>
                • Weeks <span id="confirmAfterWeek">-</span> onwards will be regenerated<br>
                • Fair distribution algorithm will ensure all rotating alliances get equal representation<br>
                • No alliance will rotate in consecutive weeks
            </div>

            <p>Are you sure you want to proceed?</p>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeRegenerateModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmRegenerate()">Yes, Regenerate Schedule</button>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div id="resultsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>✅ Schedule Regenerated</h2>
            <button class="modal-close" onclick="closeResultsModal()">&times;</button>
        </div>

        <div id="resultsContent"></div>

        <div class="modal-actions">
            <button class="btn btn-primary" onclick="closeResultsModal()">Close</button>
        </div>
    </div>
</div>

<script>
    let currentStatus = null;

    async function loadStatus() {
        const statusLoading = document.getElementById('statusLoading');
        const statusContent = document.getElementById('statusContent');

        statusLoading.classList.add('active');
        statusContent.style.display = 'none';

        try {
            const response = await fetch('council_rotation_api.php?action=get_status', {
                credentials: 'include'
            });
            const data = await response.json();

            if (data.success) {
                currentStatus = data;

                document.getElementById('currentWeek').textContent = data.current_week;
                document.getElementById('nextRotationWeek').textContent = data.next_rotation_week;
                document.getElementById('nextRotationDate').textContent = data.next_rotation_date;
                document.getElementById('scheduleWeeks').textContent = data.schedule_weeks_count;
                document.getElementById('lastGenerated').textContent = data.last_generated || 'Never';

                // Rotating pool
                const poolList = document.getElementById('rotatingPool');
                poolList.innerHTML = data.rotating_pool_tags.map(tag =>
                    `<span class="pool-tag">${tag}</span>`
                ).join('');

                statusLoading.classList.remove('active');
                statusContent.style.display = 'block';
            } else {
                showError('Failed to load status: ' + data.error);
                statusLoading.classList.remove('active');
            }
        } catch (error) {
            showError('Error loading status: ' + error.message);
            statusLoading.classList.remove('active');
        }
    }

    function openRegenerateModal() {
        if (!currentStatus) {
            showError('Please wait for status to load');
            return;
        }

        document.getElementById('confirmNextWeek').textContent = currentStatus.next_rotation_week;
        document.getElementById('confirmBeforeWeek').textContent = currentStatus.next_rotation_week;
        document.getElementById('confirmAfterWeek').textContent = currentStatus.next_rotation_week;
        document.getElementById('regenerateModal').classList.add('active');
    }

    function closeRegenerateModal() {
        document.getElementById('regenerateModal').classList.remove('active');
    }

    async function confirmRegenerate() {
        closeRegenerateModal();

        try {
            const response = await fetch('council_rotation_api.php?action=regenerate', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data.message);
                showResults(data.stats, data.notifications);
                loadStatus(); // Reload status
            } else {
                // Show detailed error message from validation
                let errorMsg = data.error || 'Failed to regenerate schedule';
                if (data.days_remaining) {
                    errorMsg += `\n\nLast regeneration: ${data.last_regenerated || 'Unknown'}\nDays since last: ${data.days_since_last}\nPlease wait: ${data.days_remaining} more day(s)`;
                }
                if (data.days_since_power_update) {
                    errorMsg += `\n\nPower last updated: ${data.power_last_updated || 'Unknown'}\nDays ago: ${data.days_since_power_update}`;
                }
                showError(errorMsg);
            }
        } catch (error) {
            showError('Error regenerating schedule: ' + error.message);
        }
    }

    function showResults(stats, notifications) {
        const notificationSection = notifications ? `
            <div class="info-box" style="margin-top: 1.5rem;">
                <h3>📧 Email Notifications</h3>
                <table class="fairness-table">
                    <tr>
                        <td><strong>R5 Users Notified:</strong></td>
                        <td>${notifications.r5_users_notified}</td>
                    </tr>
                    <tr>
                        <td><strong>Total R5 Users:</strong></td>
                        <td>${notifications.total_r5_users}</td>
                    </tr>
                    ${notifications.notifications_failed > 0 ? `
                        <tr>
                            <td><strong>Failed:</strong></td>
                            <td style="color: #e74c3c;">${notifications.notifications_failed}</td>
                        </tr>
                    ` : ''}
                </table>
            </div>
        ` : '';

        const content = `
            <div class="success-box" style="display: block;">
                <strong>✅ Success!</strong> Schedule regenerated successfully.
            </div>

            <h3>Statistics</h3>
            <table class="fairness-table">
                <tr>
                    <td><strong>Past Weeks Preserved:</strong></td>
                    <td>${stats.past_weeks_preserved}</td>
                </tr>
                <tr>
                    <td><strong>New Weeks Generated:</strong></td>
                    <td>${stats.new_weeks_generated}</td>
                </tr>
                <tr>
                    <td><strong>Total Weeks:</strong></td>
                    <td>${stats.total_weeks}</td>
                </tr>
                <tr>
                    <td><strong>Next Rotation Week:</strong></td>
                    <td>Week ${stats.next_rotation_week}</td>
                </tr>
            </table>

            ${notificationSection}

            <h3 style="margin-top: 1.5rem;">Fairness Distribution (Next 52 Weeks)</h3>
            <table class="fairness-table">
                <thead>
                    <tr>
                        <th>Alliance</th>
                        <th>Rotations</th>
                    </tr>
                </thead>
                <tbody>
                    ${Object.entries(stats.future_rotation_counts).sort((a, b) => b[1] - a[1]).map(([tag, count]) => `
                        <tr>
                            <td>${tag}</td>
                            <td>${count}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        document.getElementById('resultsContent').innerHTML = content;
        document.getElementById('resultsModal').classList.add('active');
    }

    function closeResultsModal() {
        document.getElementById('resultsModal').classList.remove('active');
    }

    function showSuccess(message) {
        const alert = document.getElementById('successAlert');
        alert.innerHTML = `<strong>✅ Success!</strong> ${message}`;
        alert.style.display = 'block';
        setTimeout(() => { alert.style.display = 'none'; }, 5000);
    }

    function showError(message) {
        const alert = document.getElementById('errorAlert');
        alert.innerHTML = `<strong>❌ Error!</strong> ${message}`;
        alert.style.display = 'block';
        setTimeout(() => { alert.style.display = 'none'; }, 5000);
    }

    // Load status on page load
    document.addEventListener('DOMContentLoaded', loadStatus);
</script>

<?php include 'includes/footer.php'; ?>
