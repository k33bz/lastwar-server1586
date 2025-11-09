<?php
/**
 * Votes Management Dashboard
 * Version: 1.0.0
 *
 * Allows presidents and admins to create, edit, and manage council votes
 * with screenshot uploads for transparency
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

log_audit_event('votes_management_page_accessed', $user->sub, [
    'user_role' => $user->aud
]);

$page_title = "Votes Management";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🗳️ Votes Management</h1>
    <p class="page-description">Record and manage council votes with supporting documentation</p>
</div>

<div class="container">
    <style>
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }

        .votes-grid { display: grid; gap: 1.5rem; }
        .vote-card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .vote-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .vote-date { color: #6c757d; font-size: 0.875rem; }
        .vote-resolution { margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 4px; }
        .vote-results { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .vote-result-group { background: #f8f9fa; padding: 1rem; border-radius: 6px; }
        .vote-result-group h4 { margin: 0 0 0.5rem 0; font-size: 0.875rem; text-transform: uppercase; color: #6c757d; }
        .vote-tag { display: inline-block; background: #e9ecef; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; margin: 0.25rem; }
        .vote-tag.yes { background: #d4edda; color: #155724; }
        .vote-tag.no { background: #f8d7da; color: #721c24; }
        .vote-tag.abstain { background: #fff3cd; color: #856404; }
        .vote-tag.absent { background: #e9ecef; color: #6c757d; }

        .screenshots { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1rem 0; }
        .screenshot-item { position: relative; }
        .screenshot-item img { width: 150px; height: 150px; object-fit: cover; border-radius: 6px; cursor: pointer; }
        .screenshot-item .delete-screenshot { position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.875rem; }

        .vote-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }

        .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d; }
        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 6px; font-size: 1rem; }
        .form-group textarea { min-height: 150px; resize: vertical; }

        .alliance-checkboxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
        .alliance-vote-item { background: #f8f9fa; padding: 1rem; border-radius: 6px; }
        .alliance-vote-item label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
        .alliance-vote-item select { width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; }

        .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; display: none; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; display: none; }

        .loading { text-align: center; padding: 2rem; color: #667eea; }
        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }

        .upload-area { border: 2px dashed #ced4da; border-radius: 6px; padding: 2rem; text-align: center; margin-top: 1rem; }
        .upload-area.dragover { border-color: #667eea; background: #f8f9fa; }
    </style>

    <div id="successAlert" class="success-box"></div>
    <div id="errorAlert" class="error-box"></div>

    <div class="actions-bar">
        <h2>Council Votes</h2>
        <button class="btn btn-primary" onclick="openCreateModal()">
            ➕ Create New Vote
        </button>
    </div>

    <div id="votesLoading" class="loading">Loading votes...</div>
    <div id="votesContainer" class="votes-grid" style="display: none;"></div>
    <div id="emptyState" class="empty-state" style="display: none;">
        <p>No votes recorded yet. Create your first vote to get started.</p>
    </div>
</div>

<!-- Create/Edit Vote Modal -->
<div id="voteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Vote</h2>
            <button class="modal-close" onclick="closeVoteModal()">&times;</button>
        </div>

        <form id="voteForm">
            <input type="hidden" id="voteId" name="id">

            <div class="form-group">
                <label for="voteDate">Vote Date *</label>
                <input type="date" id="voteDate" name="vote_date" required>
            </div>

            <div class="form-group">
                <label for="resolution">Resolution *</label>
                <textarea id="resolution" name="resolution" required placeholder="Enter the resolution text that was voted on..."></textarea>
            </div>

            <div class="form-group">
                <label>Alliance Votes</label>
                <div id="allianceVotes" class="alliance-checkboxes"></div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeVoteModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Save Vote</button>
            </div>
        </form>
    </div>
</div>

<!-- Screenshot Upload Modal -->
<div id="screenshotModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Upload Screenshots</h2>
            <button class="modal-close" onclick="closeScreenshotModal()">&times;</button>
        </div>

        <div>
            <p>Upload screenshots of the vote from Discord or other sources for transparency.</p>

            <div class="upload-area" id="uploadArea">
                <input type="file" id="screenshotInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
                <p>📸 Click to select or drag and drop images here</p>
                <p style="font-size: 0.875rem; color: #6c757d;">Supported formats: JPG, PNG, GIF, WebP (max 10MB)</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('screenshotInput').click()">
                    Select Images
                </button>
            </div>

            <div id="uploadProgress" style="display: none; margin-top: 1rem;">
                <div style="background: #e9ecef; border-radius: 4px; height: 8px;">
                    <div id="progressBar" style="background: #667eea; height: 100%; border-radius: 4px; width: 0%; transition: width 0.3s;"></div>
                </div>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeScreenshotModal()">Close</button>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div id="imagePreviewModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h2>Screenshot Preview</h2>
            <button class="modal-close" onclick="closeImagePreview()">&times;</button>
        </div>
        <div style="text-align: center;">
            <img id="previewImage" src="" style="max-width: 100%; height: auto; border-radius: 8px;">
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeImagePreview()">Close</button>
        </div>
    </div>
</div>

<script>
    let currentVote = null;
    let alliances = [];
    let votes = [];

    async function loadAlliances() {
        try {
            const response = await fetch('../data/alliances.json');
            const data = await response.json();
            alliances = data.map(a => a.tag).sort();
        } catch (error) {
            console.error('Failed to load alliances:', error);
        }
    }

    async function loadVotes() {
        const loading = document.getElementById('votesLoading');
        const container = document.getElementById('votesContainer');
        const emptyState = document.getElementById('emptyState');

        loading.style.display = 'block';
        container.style.display = 'none';
        emptyState.style.display = 'none';

        try {
            const response = await fetch('votes_api.php?action=list');
            const data = await response.json();

            if (data.success) {
                votes = data.votes;

                if (votes.length === 0) {
                    emptyState.style.display = 'block';
                } else {
                    renderVotes();
                    container.style.display = 'grid';
                }
            } else {
                showError('Failed to load votes: ' + data.error);
            }
        } catch (error) {
            showError('Error loading votes: ' + error.message);
        } finally {
            loading.style.display = 'none';
        }
    }

    function renderVotes() {
        const container = document.getElementById('votesContainer');
        container.innerHTML = '';

        votes.forEach(vote => {
            const card = document.createElement('div');
            card.className = 'vote-card';

            // Group votes by result
            const yesVotes = [];
            const noVotes = [];
            const abstainVotes = [];
            const absentVotes = [];

            for (const [tag, voteChoice] of Object.entries(vote.votes || {})) {
                if (voteChoice === 'yes') yesVotes.push(tag);
                else if (voteChoice === 'no') noVotes.push(tag);
                else if (voteChoice === 'abstain') abstainVotes.push(tag);
                else if (voteChoice === 'absent') absentVotes.push(tag);
            }

            const screenshotsHtml = (vote.screenshots && vote.screenshots.length > 0) ? `
                <div class="screenshots">
                    ${vote.screenshots.map(screenshot => `
                        <div class="screenshot-item">
                            <img src="../${screenshot}" alt="Vote screenshot" onclick="previewImage('../${screenshot}')">
                            <div class="delete-screenshot" onclick="deleteScreenshot('${vote.id}', '${screenshot}')" title="Delete screenshot">×</div>
                        </div>
                    `).join('')}
                </div>
            ` : '<p style="color: #6c757d; font-size: 0.875rem;">No screenshots uploaded</p>';

            card.innerHTML = `
                <div class="vote-header">
                    <div>
                        <div class="vote-date">📅 ${formatDate(vote.vote_date)}</div>
                    </div>
                </div>

                <div class="vote-resolution">
                    <strong>Resolution:</strong><br>
                    ${escapeHtml(vote.resolution)}
                </div>

                <div class="vote-results">
                    ${yesVotes.length > 0 ? `
                        <div class="vote-result-group">
                            <h4>✅ Yes (${yesVotes.length})</h4>
                            ${yesVotes.map(tag => `<span class="vote-tag yes">${tag}</span>`).join('')}
                        </div>
                    ` : ''}
                    ${noVotes.length > 0 ? `
                        <div class="vote-result-group">
                            <h4>❌ No (${noVotes.length})</h4>
                            ${noVotes.map(tag => `<span class="vote-tag no">${tag}</span>`).join('')}
                        </div>
                    ` : ''}
                    ${abstainVotes.length > 0 ? `
                        <div class="vote-result-group">
                            <h4>🤐 Abstain (${abstainVotes.length})</h4>
                            ${abstainVotes.map(tag => `<span class="vote-tag abstain">${tag}</span>`).join('')}
                        </div>
                    ` : ''}
                    ${absentVotes.length > 0 ? `
                        <div class="vote-result-group">
                            <h4>⚪ Absent (${absentVotes.length})</h4>
                            ${absentVotes.map(tag => `<span class="vote-tag absent">${tag}</span>`).join('')}
                        </div>
                    ` : ''}
                </div>

                ${screenshotsHtml}

                <div class="vote-actions">
                    <button class="btn btn-primary btn-sm" onclick="openUploadModal('${vote.id}')">
                        📸 Add Screenshots
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="editVote('${vote.id}')">
                        ✏️ Edit
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteVote('${vote.id}')">
                        🗑️ Delete
                    </button>
                </div>
            `;

            container.appendChild(card);
        });
    }

    function openCreateModal() {
        currentVote = null;
        document.getElementById('modalTitle').textContent = 'Create New Vote';
        document.getElementById('voteForm').reset();
        document.getElementById('voteId').value = '';
        document.getElementById('voteDate').value = new Date().toISOString().split('T')[0];

        renderAllianceVotes({});
        document.getElementById('voteModal').classList.add('active');
    }

    function editVote(voteId) {
        const vote = votes.find(v => v.id === voteId);
        if (!vote) return;

        currentVote = vote;
        document.getElementById('modalTitle').textContent = 'Edit Vote';
        document.getElementById('voteId').value = vote.id;
        document.getElementById('voteDate').value = vote.vote_date;
        document.getElementById('resolution').value = vote.resolution;

        renderAllianceVotes(vote.votes || {});
        document.getElementById('voteModal').classList.add('active');
    }

    function renderAllianceVotes(currentVotes) {
        const container = document.getElementById('allianceVotes');
        container.innerHTML = '';

        alliances.forEach(tag => {
            const item = document.createElement('div');
            item.className = 'alliance-vote-item';

            const currentValue = currentVotes[tag] || 'absent';

            item.innerHTML = `
                <label>${tag}</label>
                <select name="vote_${tag}" class="alliance-vote-select">
                    <option value="yes" ${currentValue === 'yes' ? 'selected' : ''}>✅ Yes</option>
                    <option value="no" ${currentValue === 'no' ? 'selected' : ''}>❌ No</option>
                    <option value="abstain" ${currentValue === 'abstain' ? 'selected' : ''}>🤐 Abstain</option>
                    <option value="absent" ${currentValue === 'absent' ? 'selected' : ''}>⚪ Absent</option>
                </select>
            `;

            container.appendChild(item);
        });
    }

    function closeVoteModal() {
        document.getElementById('voteModal').classList.remove('active');
    }

    document.getElementById('voteForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = {
            vote_date: document.getElementById('voteDate').value,
            resolution: document.getElementById('resolution').value,
            votes: {}
        };

        // Collect all alliance votes
        document.querySelectorAll('.alliance-vote-select').forEach(select => {
            const tag = select.name.replace('vote_', '');
            formData.votes[tag] = select.value;
        });

        const voteId = document.getElementById('voteId').value;
        const isEdit = !!voteId;

        if (isEdit) {
            formData.id = voteId;
        }

        try {
            const response = await fetch(`votes_api.php?action=${isEdit ? 'update' : 'create'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data.message);
                closeVoteModal();
                loadVotes();
            } else {
                showError('Failed to save vote: ' + data.error);
            }
        } catch (error) {
            showError('Error saving vote: ' + error.message);
        }
    });

    async function confirmDeleteVote(voteId) {
        if (!confirm('Are you sure you want to delete this vote? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('votes_api.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: voteId })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data.message);
                loadVotes();
            } else {
                showError('Failed to delete vote: ' + data.error);
            }
        } catch (error) {
            showError('Error deleting vote: ' + error.message);
        }
    }

    function openUploadModal(voteId) {
        currentVote = votes.find(v => v.id === voteId);
        document.getElementById('screenshotModal').classList.add('active');
    }

    function closeScreenshotModal() {
        document.getElementById('screenshotModal').classList.remove('active');
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('progressBar').style.width = '0%';
    }

    async function handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file || !currentVote) return;

        const formData = new FormData();
        formData.append('screenshot', file);
        formData.append('vote_id', currentVote.id);

        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');

        progressDiv.style.display = 'block';
        progressBar.style.width = '50%';

        try {
            const response = await fetch('votes_api.php?action=upload_screenshot', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                progressBar.style.width = '100%';
                showSuccess(data.message);
                setTimeout(() => {
                    closeScreenshotModal();
                    loadVotes();
                }, 1000);
            } else {
                showError('Failed to upload screenshot: ' + data.error);
                progressDiv.style.display = 'none';
            }
        } catch (error) {
            showError('Error uploading screenshot: ' + error.message);
            progressDiv.style.display = 'none';
        }

        event.target.value = '';
    }

    async function deleteScreenshot(voteId, filename) {
        if (!confirm('Delete this screenshot?')) return;

        try {
            const response = await fetch('votes_api.php?action=delete_screenshot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ vote_id: voteId, filename: filename })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data.message);
                loadVotes();
            } else {
                showError('Failed to delete screenshot: ' + data.error);
            }
        } catch (error) {
            showError('Error deleting screenshot: ' + error.message);
        }
    }

    function previewImage(src) {
        document.getElementById('previewImage').src = src;
        document.getElementById('imagePreviewModal').classList.add('active');
    }

    function closeImagePreview() {
        document.getElementById('imagePreviewModal').classList.remove('active');
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showSuccess(message) {
        const alert = document.getElementById('successAlert');
        alert.innerHTML = `<strong>✅ Success!</strong> ${message}`;
        alert.style.display = 'block';
        setTimeout(() => { alert.style.display = 'none'; }, 5000);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showError(message) {
        const alert = document.getElementById('errorAlert');
        alert.innerHTML = `<strong>❌ Error!</strong> ${message}`;
        alert.style.display = 'block';
        setTimeout(() => { alert.style.display = 'none'; }, 5000);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Drag and drop support
    const uploadArea = document.getElementById('uploadArea');
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            const input = document.getElementById('screenshotInput');
            input.files = e.dataTransfer.files;
            handleFileSelect({ target: input });
        }
    });

    // Load data on page load
    document.addEventListener('DOMContentLoaded', async function() {
        await loadAlliances();
        await loadVotes();
    });
</script>

<?php include 'includes/footer.php'; ?>
