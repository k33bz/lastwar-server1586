<?php
/**
 * Council Proposals
 * Version: 1.0.0
 *
 * Allows R5s and designated R4s to propose votes to the council
 * Requests are sent to the president for approval
 *
 * Access: R5, R4, APE roles only (must be current council members)
 */

require_once 'jwt.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Check if user has R5, R4, or APE role
if (!has_role($user, ['r5', 'r4', 'ape', 'admin', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

log_audit_event('council_proposals_page_accessed', $user->sub, [
    'user_role' => $user->aud
]);

$page_title = "Council Proposals";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🗳️ Council Proposals</h1>
    <p class="page-description">
        <?php if (has_role($user, ['admin', 'president'])): ?>
            Submit vote requests or create votes directly as president/admin
        <?php else: ?>
            Submit vote proposals to the president for approval
        <?php endif; ?>
    </p>
</div>

<div class="container">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }

        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e9ecef; }
        .tab { padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #6c757d; position: relative; }
        .tab.active { color: #667eea; }
        .tab.active::after { content: ''; position: absolute; bottom: -2px; left: 0; right: 0; height: 2px; background: #667eea; }

        .card { background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .card h3 { margin: 0 0 1.5rem 0; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 6px; font-size: 1rem; }
        .form-group textarea { min-height: 150px; resize: vertical; font-family: inherit; }
        .form-group small { display: block; margin-top: 0.25rem; color: #6c757d; }

        .requests-list { display: grid; gap: 1.5rem; }
        .request-card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .request-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .request-title { font-size: 1.25rem; font-weight: 700; margin: 0; }
        .request-meta { color: #6c757d; font-size: 0.875rem; margin-top: 0.5rem; }
        .request-description { margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #667eea; }
        .request-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef; }

        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }

        .category-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.875rem; background: #e9ecef; color: #495057; margin-left: 0.5rem; }

        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 2rem; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert.hidden { display: none; }

        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
        .loading { text-align: center; padding: 2rem; color: #667eea; }

        .help-box { background: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 6px; padding: 1rem; margin-bottom: 2rem; }
        .help-box h4 { margin: 0 0 0.5rem 0; color: #004085; }
        .help-box p { margin: 0; color: #004085; }
    </style>

    <div id="successAlert" class="alert alert-success hidden"></div>
    <div id="errorAlert" class="alert alert-error hidden"></div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="switchTab('submit')">Submit Proposal</button>
        <button class="tab" onclick="switchTab('my-requests')">My Requests</button>
    </div>

    <!-- Submit Proposal Tab -->
    <div id="submitTab" class="tab-content">
        <div class="card">
            <h3>📝 Submit Vote Proposal</h3>

            <div class="help-box">
                <h4>How Vote Proposals Work</h4>
                <p>
                    As a council member, you can propose votes to the council. Your proposal will be sent to the president for approval.
                    <?php if (has_role($user, ['admin', 'president'])): ?>
                    <strong>As president/admin, your votes will be created immediately without approval.</strong>
                    <?php else: ?>
                    If the president doesn't respond within 12 hours, your proposal will be automatically approved.
                    <?php endif; ?>
                </p>
            </div>

            <form id="proposalForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="title">Vote Title *</label>
                    <input type="text" id="title" name="title" maxlength="100" required>
                    <small>Keep it concise - maximum 100 characters</small>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                    <small>Provide details about what council members are voting on</small>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="rule_change">Rule Change</option>
                        <option value="alliance_action">Alliance Action</option>
                        <option value="server_event">Server Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php if (has_role($user, ['admin', 'president'])): ?>
                        Create Vote Now
                    <?php else: ?>
                        Submit Proposal
                    <?php endif; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- My Requests Tab -->
    <div id="myRequestsTab" class="tab-content" style="display: none;">
        <div class="card">
            <h3>📋 My Vote Requests</h3>
            <div id="requestsList" class="requests-list">
                <div class="loading">Loading your requests...</div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    // Show/hide content
    document.getElementById('submitTab').style.display = tab === 'submit' ? 'block' : 'none';
    document.getElementById('myRequestsTab').style.display = tab === 'my-requests' ? 'block' : 'none';

    if (tab === 'my-requests') {
        loadMyRequests();
    }
}

// Submit proposal form
document.getElementById('proposalForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = {
        title: formData.get('title'),
        description: formData.get('description'),
        category: formData.get('category')
    };

    try {
        <?php if (has_role($user, ['admin', 'president'])): ?>
            // President/Admin creates vote directly
            const response = await apiRequest('POST', 'discord_votes_api.php?action=create_vote', data);
        <?php else: ?>
            // R5/R4 creates request
            const response = await apiRequest('POST', 'discord_votes_api.php?action=create_request', data);
        <?php endif; ?>

        if (response.success) {
            showSuccess(response.message || 'Proposal submitted successfully!');
            e.target.reset();

            // Switch to my requests tab
            setTimeout(() => {
                document.querySelectorAll('.tab')[1].click();
            }, 1500);
        } else {
            showError(response.error || 'Failed to submit proposal');
        }
    } catch (error) {
        showError(error.message || 'An error occurred');
    }
});

// Load my requests
async function loadMyRequests() {
    const container = document.getElementById('requestsList');
    container.innerHTML = '<div class="loading">Loading your requests...</div>';

    try {
        const response = await apiRequest('GET', 'discord_votes_api.php?action=get_requests');

        if (!response.success) {
            throw new Error(response.error || 'Failed to load requests');
        }

        const requests = response.requests || [];

        if (requests.length === 0) {
            container.innerHTML = '<div class="empty-state">You haven\'t submitted any vote requests yet</div>';
            return;
        }

        container.innerHTML = requests.map(req => `
            <div class="request-card">
                <div class="request-header">
                    <div>
                        <h4 class="request-title">${escapeHtml(req.vote_details.title)}</h4>
                        <div class="request-meta">
                            Submitted: ${formatDate(req.created_at)}
                            <span class="category-badge">${formatCategory(req.vote_details.category)}</span>
                        </div>
                    </div>
                    <span class="status-badge ${req.status}">${req.status.toUpperCase()}</span>
                </div>

                <div class="request-description">${escapeHtml(req.vote_details.description)}</div>

                <div class="request-footer">
                    <div>
                        <strong>Request ID:</strong> ${req.request_id}
                    </div>
                    <div>
                        ${req.status === 'approved' && req.created_vote_id ?
                            `<span style="color: #28a745;">✓ Vote Created: ${req.created_vote_id}</span>` :
                            req.status === 'rejected' ?
                                `<span style="color: #dc3545;">✗ Rejected: ${req.president_response?.reason || 'No reason provided'}</span>` :
                                `<span style="color: #856404;">⏳ Awaiting president approval</span>`
                        }
                    </div>
                </div>
            </div>
        `).join('');

    } catch (error) {
        container.innerHTML = `<div class="empty-state" style="color: #dc3545;">Error: ${error.message}</div>`;
    }
}

// Helper functions
async function apiRequest(method, url, data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        }
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
    setTimeout(() => alert.classList.add('hidden'), 5000);
}

function showError(message) {
    const alert = document.getElementById('errorAlert');
    alert.textContent = message;
    alert.classList.remove('hidden');
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
</script>

<?php include 'includes/footer.php'; ?>
