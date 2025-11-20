<?php
/**
 * President Vote Approvals
 * Version: 1.0.0
 *
 * Allows president and admins to review and approve/reject vote requests
 * submitted by council members
 *
 * Access: President and Admin roles only
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
            <div class="stat-label">Pending Requests</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="activeVotesCount">-</div>
            <div class="stat-label">Active Votes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="totalRequestsCount">-</div>
            <div class="stat-label">Total Requests</div>
        </div>
    </div>

    <!-- Requests Section -->
    <div class="requests-section">
        <div class="section-header">
            <h2>Vote Requests</h2>
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterRequests('all')">All</button>
                <button class="filter-btn" onclick="filterRequests('pending')">Pending</button>
                <button class="filter-btn" onclick="filterRequests('approved')">Approved</button>
                <button class="filter-btn" onclick="filterRequests('rejected')">Rejected</button>
            </div>
        </div>

        <div id="requestsGrid" class="requests-grid">
            <div class="loading">Loading requests...</div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject Vote Request</h3>
            <button class="modal-close" onclick="closeRejectionModal()">&times;</button>
        </div>

        <form id="rejectionForm">
            <div class="form-group">
                <label for="rejectionReason">Reason for rejection (optional)</label>
                <textarea id="rejectionReason" name="reason" placeholder="Explain why this request is being rejected..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeRejectionModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Request</button>
            </div>
        </form>
    </div>
</div>

<script>
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
    container.innerHTML = '<div class="loading">Loading requests...</div>';

    try {
        const response = await apiRequest('GET', 'discord_votes_api.php?action=get_requests');

        if (!response.success) {
            throw new Error(response.error || 'Failed to load requests');
        }

        allRequests = response.requests || [];
        renderRequests();
    } catch (error) {
        container.innerHTML = `<div class="empty-state" style="color: #dc3545;">Error: ${error.message}</div>`;
    }
}

function renderRequests() {
    const container = document.getElementById('requestsGrid');

    let filtered = allRequests;
    if (currentFilter !== 'all') {
        filtered = allRequests.filter(r => r.status === currentFilter);
    }

    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-state">No requests found</div>';
        return;
    }

    container.innerHTML = filtered.map(req => {
        const age = Math.floor((Date.now() - new Date(req.created_at).getTime()) / (1000 * 60 * 60));
        const autoApproveIn = Math.max(0, 12 - age);

        return `
            <div class="request-card ${req.status}">
                <div class="request-header">
                    <div style="flex: 1;">
                        <h3 class="request-title">${escapeHtml(req.vote_details.title)}</h3>
                        <div class="request-meta">
                            Submitted: ${formatDate(req.created_at)} (${age}h ago)
                            <span class="category-badge">${formatCategory(req.vote_details.category)}</span>
                        </div>
                        <div class="request-submitter">
                            👤 ${escapeHtml(req.requested_by.username)}
                            ${req.requested_by.alliance ? ` - ${req.requested_by.alliance}` : ''}
                        </div>
                    </div>
                    <span class="status-badge ${req.status}">${req.status.toUpperCase()}</span>
                </div>

                <div class="request-description">${escapeHtml(req.vote_details.description)}</div>

                ${req.status === 'pending' ? `
                    <div class="auto-approve-warning">
                        ⏰ Auto-approval in ${autoApproveIn} hour(s) if not reviewed
                    </div>
                    <div class="request-actions">
                        <button class="btn btn-success" onclick="approveRequest('${req.request_id}')">
                            ✓ Approve & Create Vote
                        </button>
                        <button class="btn btn-danger" onclick="openRejectionModal('${req.request_id}')">
                            ✗ Reject
                        </button>
                    </div>
                ` : req.status === 'approved' ? `
                    <div class="request-actions">
                        <span style="color: #28a745; font-weight: 600;">
                            ✓ Approved by ${escapeHtml(req.president_response?.approver_name || 'Unknown')}
                            ${req.created_vote_id ? `- Vote ID: ${req.created_vote_id}` : ''}
                        </span>
                    </div>
                ` : `
                    <div class="request-actions">
                        <span style="color: #dc3545; font-weight: 600;">
                            ✗ Rejected by ${escapeHtml(req.president_response?.rejector_name || 'Unknown')}
                            ${req.president_response?.reason ? `: "${escapeHtml(req.president_response.reason)}"` : ''}
                        </span>
                    </div>
                `}

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef; font-size: 0.875rem; color: #6c757d;">
                    <strong>Request ID:</strong> ${req.request_id}
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
    if (!confirm('Are you sure you want to approve this request and create the vote?')) {
        return;
    }

    try {
        const response = await apiRequest('POST', 'discord_votes_api.php?action=approve_request', {
            request_id: requestId
        });

        if (response.success) {
            showSuccess(`Request approved! Vote created: ${response.vote_id}`);
            loadStats();
            loadRequests();
        } else {
            showError(response.error || 'Failed to approve request');
        }
    } catch (error) {
        showError(error.message || 'An error occurred');
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
            reason: reason || 'No reason provided'
        });

        if (response.success) {
            showSuccess('Request rejected successfully');
            closeRejectionModal();
            loadStats();
            loadRequests();
        } else {
            showError(response.error || 'Failed to reject request');
        }
    } catch (error) {
        showError(error.message || 'An error occurred');
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
    return category.replace('_', ' ').toUpperCase();
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
