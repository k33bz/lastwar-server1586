<?php
/**
 * Discord Rate Limit Management (Admin Only)
 * View and manage user rate limit requests
 */

require_once 'jwt.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Admin only
if ($user->aud !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Log page access
log_audit_event('discord_rate_limits_accessed', $user->sub);

$page_title = "Discord Rate Limits";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📊 Discord Rate Limit Management</h1>
    <p class="page-description">Review and approve user rate limit increase requests</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .filter-tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.2s;
        }

        .filter-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .filter-tab:hover {
            color: #667eea;
        }

        .requests-grid {
            display: grid;
            gap: 1rem;
        }

        .request-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-left: 4px solid #667eea;
            border-radius: 6px;
            padding: 1.5rem;
        }

        .request-card.pending {
            border-left-color: #f39c12;
            background: #fffef5;
        }

        .request-card.approved {
            border-left-color: #27ae60;
            background: #f0fdf4;
        }

        .request-card.rejected {
            border-left-color: #e74c3c;
            background: #fef2f2;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .request-user {
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
        }

        .request-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .detail-value.limit-change {
            font-size: 1.2rem;
            color: #667eea;
        }

        .request-reason {
            background: white;
            padding: 1rem;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }

        .request-reason-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .request-reason-text {
            color: #333;
            line-height: 1.5;
        }

        .request-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-approve {
            background: #27ae60;
            color: white;
        }

        .btn-approve:hover {
            background: #229954;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.4);
        }

        .btn-reject {
            background: #e74c3c;
            color: white;
        }

        .btn-reject:hover {
            background: #c0392b;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Reject Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: #e74c3c;
            color: white;
            padding: 1.5rem;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-body textarea {
            width: 100%;
            min-height: 100px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>

    <div id="alertContainer"></div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-tab active" data-status="pending" onclick="filterRequests('pending')">
            📋 Pending
        </button>
        <button class="filter-tab" data-status="approved" onclick="filterRequests('approved')">
            ✅ Approved
        </button>
        <button class="filter-tab" data-status="rejected" onclick="filterRequests('rejected')">
            ❌ Rejected
        </button>
        <button class="filter-tab" data-status="all" onclick="filterRequests('all')">
            📊 All
        </button>
    </div>

    <!-- Requests Grid -->
    <div id="requestsGrid" class="requests-grid">
        <div class="empty-state">
            <div class="empty-state-icon">⏳</div>
            <p>Loading rate limit requests...</p>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>❌ Reject Request</h3>
            <button class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <label for="rejectReason" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                Reason for rejection:
            </label>
            <textarea id="rejectReason" placeholder="Explain why this request is being rejected..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
            <button class="btn btn-reject" onclick="submitReject()">Reject Request</button>
        </div>
    </div>
</div>

<script>
let currentFilter = 'pending';
let allRequests = [];
let currentRejectId = null;

// Load requests
async function loadRequests() {
    try {
        const response = await fetch(`discord_rate_limit_api.php?action=list_requests&status=${currentFilter}`, {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            allRequests = data.requests;
            renderRequests();
        } else {
            showAlert('Failed to load requests: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error loading requests: ' + error.message, 'error');
    }
}

// Render requests
function renderRequests() {
    const container = document.getElementById('requestsGrid');

    if (allRequests.length === 0) {
        let emptyMessage = 'No rate limit requests';
        if (currentFilter === 'pending') {
            emptyMessage = 'No pending rate limit requests';
        } else if (currentFilter === 'approved') {
            emptyMessage = 'No approved rate limit requests';
        } else if (currentFilter === 'rejected') {
            emptyMessage = 'No rejected rate limit requests';
        }

        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>${emptyMessage}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = allRequests.map(request => `
        <div class="request-card ${request.status}">
            <div class="request-header">
                <div>
                    <div class="request-user">${escapeHtml(request.user_ign || request.user_email)}</div>
                    <div style="font-size: 0.85rem; color: #666;">${escapeHtml(request.user_email)}</div>
                </div>
                <span class="request-status status-${request.status}">${request.status}</span>
            </div>

            <div class="request-details">
                <div class="detail-item">
                    <div class="detail-label">Current Limit</div>
                    <div class="detail-value">${request.current_limit} /hour</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Requested Limit</div>
                    <div class="detail-value limit-change">${request.requested_limit} /hour</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Increase</div>
                    <div class="detail-value" style="color: #27ae60;">+${request.requested_limit - request.current_limit}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Requested Date</div>
                    <div class="detail-value">${formatDate(request.requested_at)}</div>
                </div>
            </div>

            <div class="request-reason">
                <div class="request-reason-label">Reason:</div>
                <div class="request-reason-text">${escapeHtml(request.reason)}</div>
            </div>

            ${request.status === 'pending' ? `
                <div class="request-actions">
                    <button class="btn btn-approve" onclick="approveRequest('${request.id}')">
                        ✅ Approve
                    </button>
                    <button class="btn btn-reject" onclick="showRejectModal('${request.id}')">
                        ❌ Reject
                    </button>
                </div>
            ` : ''}

            ${request.status === 'approved' ? `
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef; font-size: 0.85rem; color: #666;">
                    <strong>Approved by:</strong> ${escapeHtml(request.reviewed_by)} on ${formatDate(request.reviewed_at)}
                </div>
            ` : ''}

            ${request.status === 'rejected' ? `
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef; font-size: 0.85rem; color: #666;">
                    <strong>Rejected by:</strong> ${escapeHtml(request.reviewed_by)} on ${formatDate(request.reviewed_at)}<br>
                    <strong>Reason:</strong> ${escapeHtml(request.rejection_reason || 'No reason provided')}
                </div>
            ` : ''}
        </div>
    `).join('');
}

// Filter requests
function filterRequests(status) {
    currentFilter = status;

    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-status="${status}"]`).classList.add('active');

    loadRequests();
}

// Approve request
async function approveRequest(requestId) {
    if (!confirm('Are you sure you want to approve this rate limit increase?')) {
        return;
    }

    try {
        const csrfToken = getCsrfToken();
        const formData = new FormData();
        formData.append('action', 'approve_request');
        formData.append('request_id', requestId);

        const response = await fetch('discord_rate_limit_api.php', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showAlert('Rate limit request approved successfully', 'success');
            loadRequests();
        } else {
            showAlert('Failed to approve request: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error approving request: ' + error.message, 'error');
    }
}

// Show reject modal
function showRejectModal(requestId) {
    currentRejectId = requestId;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectModal').classList.add('active');
}

// Close reject modal
function closeRejectModal() {
    currentRejectId = null;
    document.getElementById('rejectModal').classList.remove('active');
}

// Submit rejection
async function submitReject() {
    const reason = document.getElementById('rejectReason').value.trim();

    if (!reason) {
        alert('Please provide a reason for rejection');
        return;
    }

    try {
        const csrfToken = getCsrfToken();
        const formData = new FormData();
        formData.append('action', 'reject_request');
        formData.append('request_id', currentRejectId);
        formData.append('reason', reason);

        const response = await fetch('discord_rate_limit_api.php', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showAlert('Rate limit request rejected', 'success');
            closeRejectModal();
            loadRequests();
        } else {
            showAlert('Failed to reject request: ' + data.error, 'error');
        }
    } catch (error) {
        showAlert('Error rejecting request: ' + error.message, 'error');
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = message;
    container.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Close modal on outside click
document.addEventListener('click', function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target === modal) {
        closeRejectModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRejectModal();
    }
});

// Initialize
loadRequests();
</script>

<?php include 'includes/footer.php'; ?>
