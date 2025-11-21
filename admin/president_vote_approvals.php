<?php
/**
 * President Vote Approvals
 * Version: 1.1.0
 *
 * Allows president and admins to review and approve/reject vote requests
 * submitted by council members
 *
 * Access: President and Admin roles only
 * Changelog:
 *   1.1.0 - Added i18n support with translation consolidation pattern
 */

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'includes/i18n.php';

// Initialize i18n
i18n_init();

$user = require_jwt_session();

// Check if user is president or admin
if (!has_role($user, ['admin', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

log_audit_event('president_vote_approvals_page_accessed', $user->sub, [
    'user_role' => $user->aud
]);

$page_title = __('pages.president_vote_approvals.title');
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🗳️ <?php echo __('pages.president_vote_approvals.title'); ?></h1>
    <p class="page-description"><?php echo __('pages.president_vote_approvals.description_full'); ?></p>
</div>

<div class="container">
    <style>
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #667eea; margin-bottom: 0.5rem; }
        .stat-label { color: #6c757d; font-size: 0.875rem; text-transform: uppercase; }

        .requests-section { margin-bottom: 2rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-header h2 { margin: 0; }
        .filter-buttons { display: flex; gap: 0.5rem; }
        .filter-btn { padding: 0.5rem 1rem; border: 1px solid #ced4da; background: white; border-radius: 6px; cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }

        .requests-grid { display: grid; gap: 1.5rem; }
        .request-card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative; }
        .request-card.pending { border-left: 4px solid #ffc107; }
        .request-card.approved { border-left: 4px solid #28a745; }
        .request-card.rejected { border-left: 4px solid #dc3545; }

        .request-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .request-title { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem 0; }
        .request-meta { color: #6c757d; font-size: 0.875rem; }
        .request-submitter { background: #e9ecef; padding: 0.5rem 1rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.875rem; }

        .request-description { margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #667eea; }

        .request-actions { display: flex; gap: 0.75rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }

        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }

        .category-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.875rem; background: #e9ecef; color: #495057; margin-left: 0.5rem; }

        .auto-approve-warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 0.75rem 1rem; border-radius: 6px; margin-top: 1rem; font-size: 0.875rem; color: #856404; }

        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 2rem; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert.hidden { display: none; }

        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
        .loading { text-align: center; padding: 2rem; color: #667eea; }

        /* Rejection modal */
        .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d; }
        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 6px; font-size: 1rem; min-height: 100px; resize: vertical; font-family: inherit; }
    </style>

    <div id="successAlert" class="alert alert-success hidden"></div>
    <div id="errorAlert" class="alert alert-error hidden"></div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number" id="pendingCount">-</div>
            <div class="stat-label"><?php echo __('pages.president_vote_approvals.stats.pending_requests'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="activeVotesCount">-</div>
            <div class="stat-label"><?php echo __('pages.president_vote_approvals.stats.active_votes'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="totalRequestsCount">-</div>
            <div class="stat-label"><?php echo __('pages.president_vote_approvals.stats.total_requests'); ?></div>
        </div>
    </div>

    <!-- Requests Section -->
    <div class="requests-section">
        <div class="section-header">
            <h2><?php echo __('pages.president_vote_approvals.section_title'); ?></h2>
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterRequests('all')"><?php echo __('pages.president_vote_approvals.filters.all'); ?></button>
                <button class="filter-btn" onclick="filterRequests('pending')"><?php echo __('pages.president_vote_approvals.filters.pending'); ?></button>
                <button class="filter-btn" onclick="filterRequests('approved')"><?php echo __('pages.president_vote_approvals.filters.approved'); ?></button>
                <button class="filter-btn" onclick="filterRequests('rejected')"><?php echo __('pages.president_vote_approvals.filters.rejected'); ?></button>
            </div>
        </div>

        <div id="requestsGrid" class="requests-grid">
            <div class="loading"><?php echo __('common.messages.loading'); ?></div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php echo __('pages.president_vote_approvals.modal.reject_title'); ?></h3>
            <button class="modal-close" onclick="closeRejectionModal()">&times;</button>
        </div>

        <form id="rejectionForm">
            <div class="form-group">
                <label for="rejectionReason"><?php echo __('pages.president_vote_approvals.modal.reason_label'); ?></label>
                <textarea id="rejectionReason" name="reason" placeholder="<?php echo __('pages.president_vote_approvals.modal.reason_placeholder'); ?>"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeRejectionModal()"><?php echo __('common.buttons.cancel'); ?></button>
                <button type="submit" class="btn btn-danger"><?php echo __('common.buttons.reject'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
// i18n translations for JavaScript
const i18n = {
    loading: <?php echo json_encode(__('common.messages.loading')); ?>,
    noRequests: <?php echo json_encode(__('pages.president_vote_approvals.messages.no_requests')); ?>,
    loadError: <?php echo json_encode(__('pages.president_vote_approvals.messages.load_error')); ?>,
    submitted: <?php echo json_encode(__('pages.president_vote_approvals.request_card.submitted')); ?>,
    ago: <?php echo json_encode(__('pages.president_vote_approvals.request_card.ago')); ?>,
    hour: <?php echo json_encode(__('pages.president_vote_approvals.request_card.hour')); ?>,
    hours: <?php echo json_encode(__('pages.president_vote_approvals.request_card.hours')); ?>,
    autoApproveWarning: <?php echo json_encode(__('pages.president_vote_approvals.request_card.auto_approve_warning')); ?>,
    approvedBy: <?php echo json_encode(__('pages.president_vote_approvals.request_card.approved_by')); ?>,
    rejectedBy: <?php echo json_encode(__('pages.president_vote_approvals.request_card.rejected_by')); ?>,
    voteId: <?php echo json_encode(__('pages.president_vote_approvals.request_card.vote_id')); ?>,
    requestId: <?php echo json_encode(__('common.labels.request_id')); ?>,
    category: <?php echo json_encode(__('common.labels.category')); ?>,
    approveCreate: <?php echo json_encode(__('pages.president_vote_approvals.buttons.approve_create')); ?>,
    reject: <?php echo json_encode(__('common.buttons.reject')); ?>,
    confirmApprove: <?php echo json_encode(__('pages.president_vote_approvals.messages.confirm_approve')); ?>,
    approveSuccess: <?php echo json_encode(__('pages.president_vote_approvals.messages.approve_success')); ?>,
    approveError: <?php echo json_encode(__('pages.president_vote_approvals.messages.approve_error')); ?>,
    rejectSuccess: <?php echo json_encode(__('pages.president_vote_approvals.messages.reject_success')); ?>,
    rejectError: <?php echo json_encode(__('pages.president_vote_approvals.messages.reject_error')); ?>,
    errorOccurred: <?php echo json_encode(__('common.messages.error_occurred')); ?>,
    noReason: <?php echo json_encode(__('common.messages.no_reason')); ?>,
    pending: <?php echo json_encode(__('pages.president_vote_approvals.filters.pending')); ?>,
    approved: <?php echo json_encode(__('pages.president_vote_approvals.filters.approved')); ?>,
    rejected: <?php echo json_encode(__('pages.president_vote_approvals.filters.rejected')); ?>,
    categories: {
        rule_change: <?php echo json_encode(__('pages.discord_vote_proposals.categories.rule_change')); ?>,
        alliance_action: <?php echo json_encode(__('pages.discord_vote_proposals.categories.alliance_action')); ?>,
        server_event: <?php echo json_encode(__('pages.discord_vote_proposals.categories.server_event')); ?>,
        other: <?php echo json_encode(__('pages.discord_vote_proposals.categories.other')); ?>
    }
};

let currentFilter = 'all';
let allRequests = [];
let currentRejectRequestId = null;

// Load stats and requests on page load
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadRequests();
});

async function loadStats() {
    try {
        const [pendingResp, votesResp, allReqResp] = await Promise.all([
            apiRequest('GET', 'discord_votes_api.php?action=get_pending_requests'),
            apiRequest('GET', 'discord_votes_api.php?action=get_active_votes'),
            apiRequest('GET', 'discord_votes_api.php?action=get_requests')
        ]);

        document.getElementById('pendingCount').textContent = pendingResp.requests?.length || 0;
        document.getElementById('activeVotesCount').textContent = votesResp.votes?.length || 0;
        document.getElementById('totalRequestsCount').textContent = allReqResp.requests?.length || 0;
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

async function loadRequests() {
    const container = document.getElementById('requestsGrid');
    container.innerHTML = `<div class="loading">${i18n.loading}</div>`;

    try {
        const response = await apiRequest('GET', 'discord_votes_api.php?action=get_requests');

        if (!response.success) {
            throw new Error(response.error || 'Failed to load requests');
        }

        allRequests = response.requests || [];
        renderRequests();
    } catch (error) {
        container.innerHTML = `<div class="empty-state" style="color: #dc3545;">${i18n.loadError.replace('{error}', error.message)}</div>`;
    }
}

function renderRequests() {
    const container = document.getElementById('requestsGrid');

    let filtered = allRequests;
    if (currentFilter !== 'all') {
        filtered = allRequests.filter(r => r.status === currentFilter);
    }

    if (filtered.length === 0) {
        container.innerHTML = `<div class="empty-state">${i18n.noRequests}</div>`;
        return;
    }

    container.innerHTML = filtered.map(req => {
        const age = Math.floor((Date.now() - new Date(req.created_at).getTime()) / (1000 * 60 * 60));
        const autoApproveIn = Math.max(0, 12 - age);
        const hourUnit = autoApproveIn === 1 ? i18n.hour : i18n.hours;
        const statusLabel = i18n[req.status] || req.status.toUpperCase();

        return `
            <div class="request-card ${req.status}">
                <div class="request-header">
                    <div style="flex: 1;">
                        <h3 class="request-title">${escapeHtml(req.vote_details.title)}</h3>
                        <div class="request-meta">
                            ${i18n.submitted}: ${formatDate(req.created_at)} (${age}h ${i18n.ago})
                            <span class="category-badge">${formatCategory(req.vote_details.category)}</span>
                        </div>
                        <div class="request-submitter">
                            👤 ${escapeHtml(req.requested_by.username)}
                            ${req.requested_by.alliance ? ` - ${req.requested_by.alliance}` : ''}
                        </div>
                    </div>
                    <span class="status-badge ${req.status}">${statusLabel}</span>
                </div>

                <div class="request-description">${escapeHtml(req.vote_details.description)}</div>

                ${req.status === 'pending' ? `
                    <div class="auto-approve-warning">
                        ⏰ ${i18n.autoApproveWarning.replace('{hours}', autoApproveIn).replace('{unit}', hourUnit)}
                    </div>
                    <div class="request-actions">
                        <button class="btn btn-success" onclick="approveRequest('${req.request_id}')">
                            ✓ ${i18n.approveCreate}
                        </button>
                        <button class="btn btn-danger" onclick="openRejectionModal('${req.request_id}')">
                            ✗ ${i18n.reject}
                        </button>
                    </div>
                ` : req.status === 'approved' ? `
                    <div class="request-actions">
                        <span style="color: #28a745; font-weight: 600;">
                            ✓ ${i18n.approvedBy} ${escapeHtml(req.president_response?.approver_name || 'Unknown')}
                            ${req.created_vote_id ? `- ${i18n.voteId}: ${req.created_vote_id}` : ''}
                        </span>
                    </div>
                ` : `
                    <div class="request-actions">
                        <span style="color: #dc3545; font-weight: 600;">
                            ✗ ${i18n.rejectedBy} ${escapeHtml(req.president_response?.rejector_name || 'Unknown')}
                            ${req.president_response?.reason ? `: "${escapeHtml(req.president_response.reason)}"` : ''}
                        </span>
                    </div>
                `}

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef; font-size: 0.875rem; color: #6c757d;">
                    <strong>${i18n.requestId}:</strong> ${req.request_id}
                </div>
            </div>
        `;
    }).join('');
}

function filterRequests(status) {
    currentFilter = status;

    // Update filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    renderRequests();
}

async function approveRequest(requestId) {
    if (!confirm(i18n.confirmApprove)) {
        return;
    }

    try {
        const response = await apiRequest('POST', 'discord_votes_api.php?action=approve_request', {
            request_id: requestId
        });

        if (response.success) {
            showSuccess(i18n.approveSuccess.replace('{vote_id}', response.vote_id));
            loadStats();
            loadRequests();
        } else {
            showError(response.error || i18n.approveError);
        }
    } catch (error) {
        showError(error.message || i18n.errorOccurred);
    }
}

function openRejectionModal(requestId) {
    currentRejectRequestId = requestId;
    document.getElementById('rejectionModal').classList.add('active');
    document.getElementById('rejectionReason').value = '';
}

function closeRejectionModal() {
    document.getElementById('rejectionModal').classList.remove('active');
    currentRejectRequestId = null;
}

document.getElementById('rejectionForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const reason = document.getElementById('rejectionReason').value.trim();

    try {
        const response = await apiRequest('POST', 'discord_votes_api.php?action=reject_request', {
            request_id: currentRejectRequestId,
            reason: reason || i18n.noReason
        });

        if (response.success) {
            showSuccess(i18n.rejectSuccess);
            closeRejectionModal();
            loadStats();
            loadRequests();
        } else {
            showError(response.error || i18n.rejectError);
        }
    } catch (error) {
        showError(error.message || i18n.errorOccurred);
    }
});

// Helper functions
async function apiRequest(method, url, data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include' // Send cookies (JWT token) with request
    };

    if (method === 'POST' && data) {
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    return await response.json();
}

function showSuccess(message) {
    const alert = document.getElementById('successAlert');
    alert.textContent = message;
    alert.classList.remove('hidden');
    window.scrollTo(0, 0);
    setTimeout(() => alert.classList.add('hidden'), 5000);
}

function showError(message) {
    const alert = document.getElementById('errorAlert');
    alert.textContent = message;
    alert.classList.remove('hidden');
    window.scrollTo(0, 0);
    setTimeout(() => alert.classList.add('hidden'), 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleString();
}

function formatCategory(category) {
    return i18n.categories[category] || category.replace('_', ' ').toUpperCase();
}

// Close modal on backdrop click
document.getElementById('rejectionModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        closeRejectionModal();
    }
});
</script>

<?php
// Render help drawer
require_once 'includes/help_drawer.php';
$help_config = require 'includes/help_content/president_approvals_help.php';
render_help_drawer($help_config);
?>

<?php include 'includes/footer.php'; ?>
