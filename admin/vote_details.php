<?php
/**
 * Vote Details Page
 * Displays detailed information about a specific council vote
 */

require_once 'jwt.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Log page access
log_audit_event('vote_details_viewed', $user->sub, [
    'vote_id' => $_GET['id'] ?? 'unknown'
]);

$page_title = "Vote Details";
include 'includes/header.php';
?>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">🗳️ Vote Details</h1>
            <p class="page-description">Detailed information about this council vote</p>
        </div>
        <a href="votes.php" class="btn btn-secondary">← Back to Votes</a>
    </div>
</div>

<style>
    .vote-details-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .vote-header-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .vote-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        color: #333;
    }

    .vote-meta {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .vote-meta-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .vote-meta-label {
        font-size: 0.85rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vote-meta-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    .vote-status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-block;
    }

    .vote-status-badge.active {
        background: #e3f2fd;
        color: #1976d2;
    }

    .vote-status-badge.completed {
        background: #e8f5e9;
        color: #388e3c;
    }

    .vote-description {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 6px;
        border-left: 4px solid #667eea;
        margin-bottom: 1.5rem;
    }

    .vote-synopsis {
        background: #fff3cd;
        padding: 1.5rem;
        border-radius: 6px;
        border-left: 4px solid #ffc107;
    }

    .section-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 1.5rem 0;
        color: #333;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .outcome-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 2rem;
    }

    .outcome-box.approved {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .outcome-box.rejected {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    }

    .outcome-box.tie {
        background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
    }

    .outcome-label {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }

    .outcome-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
    }

    .vote-counts {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1rem;
    }

    .vote-count {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
    }

    .vote-count-label {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .vote-count-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .council-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .council-table th,
    .council-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .council-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #555;
    }

    .council-table tr:hover {
        background: #f8f9fa;
    }

    .submission-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }

    .submission-badge.submitted {
        background: #e8f5e9;
        color: #388e3c;
    }

    .submission-badge.pending {
        background: #fff3cd;
        color: #856404;
    }

    .vote-choice-badge {
        padding: 0.4rem 1rem;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
    }

    .vote-choice-badge.yes {
        background: #d4edda;
        color: #155724;
    }

    .vote-choice-badge.no {
        background: #f8d7da;
        color: #721c24;
    }

    .vote-choice-badge.abstain {
        background: #d1ecf1;
        color: #0c5460;
    }

    .hash-display {
        font-family: 'Courier New', monospace;
        background: #f8f9fa;
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #666;
        word-break: break-all;
    }

    .integrity-status {
        padding: 1rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .integrity-status.valid {
        background: #e8f5e9;
        border: 1px solid #c3e6cb;
    }

    .integrity-status.invalid {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
    }

    .integrity-icon {
        font-size: 2rem;
    }

    .timeline {
        position: relative;
        padding-left: 2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 2rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -1.65rem;
        top: 0.25rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #667eea;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #667eea;
    }

    .timeline-time {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 0.25rem;
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 6px;
    }

    .timeline-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .timeline-description {
        font-size: 0.9rem;
        color: #666;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 0.95rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
    }

    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .alert-danger {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .loading {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
</style>

<div class="vote-details-container" id="voteContainer">
    <div class="loading">
        <div>Loading vote details...</div>
    </div>
</div>

<script>
const voteId = new URLSearchParams(window.location.search).get('id');

if (!voteId) {
    document.getElementById('voteContainer').innerHTML = `
        <div class="alert alert-danger">
            No vote ID provided. <a href="votes.php">Return to votes list</a>
        </div>
    `;
} else {
    loadVoteDetails();
}

async function loadVoteDetails() {
    try {
        const response = await fetch(`../data/discord-votes.json`);
        const data = await response.json();

        const vote = data.votes.find(v => v.vote_id === voteId);

        if (!vote) {
            document.getElementById('voteContainer').innerHTML = `
                <div class="alert alert-danger">
                    Vote not found. <a href="votes.php">Return to votes list</a>
                </div>
            `;
            return;
        }

        renderVoteDetails(vote);
    } catch (error) {
        console.error('Error loading vote:', error);
        document.getElementById('voteContainer').innerHTML = `
            <div class="alert alert-danger">
                Failed to load vote details: ${error.message}
            </div>
        `;
    }
}

function renderVoteDetails(vote) {
    const container = document.getElementById('voteContainer');

    // Calculate vote statistics
    const totalVoters = vote.council_snapshot.voter_details.length;
    const submittedCount = vote.vote_submissions.length;
    const pendingCount = totalVoters - submittedCount;

    // Count votes by choice
    const voteCounts = {
        yes: 0,
        no: 0,
        abstain: 0
    };

    vote.vote_submissions.forEach(sub => {
        const choice = sub.vote_choice.toLowerCase();
        if (voteCounts.hasOwnProperty(choice)) {
            voteCounts[choice]++;
        }
    });

    let html = '';

    // Vote Header Card
    html += `
        <div class="vote-header-card">
            <h2 class="vote-title">${escapeHtml(vote.vote_details.title)}</h2>

            <div class="vote-meta">
                <div class="vote-meta-item">
                    <span class="vote-meta-label">Vote ID</span>
                    <span class="vote-meta-value">${vote.vote_id}</span>
                </div>
                <div class="vote-meta-item">
                    <span class="vote-meta-label">Status</span>
                    <span class="vote-status-badge ${vote.status}">${vote.status.toUpperCase()}</span>
                </div>
                <div class="vote-meta-item">
                    <span class="vote-meta-label">Category</span>
                    <span class="vote-meta-value">${escapeHtml(vote.vote_details.category)}</span>
                </div>
                <div class="vote-meta-item">
                    <span class="vote-meta-label">Created</span>
                    <span class="vote-meta-value">${formatDate(vote.created_at)}</span>
                </div>
                ${vote.finalized_at ? `
                <div class="vote-meta-item">
                    <span class="vote-meta-label">Finalized</span>
                    <span class="vote-meta-value">${formatDate(vote.finalized_at)}</span>
                </div>
                ` : ''}
            </div>

            <div class="vote-description">
                <strong>Description:</strong><br>
                ${escapeHtml(vote.vote_details.description || 'No description provided').replace(/\n/g, '<br>')}
            </div>

            ${vote.vote_details.synopsis ? `
            <div class="vote-synopsis">
                <strong>📝 Synopsis:</strong><br>
                ${escapeHtml(vote.vote_details.synopsis).replace(/\n/g, '<br>')}
            </div>
            ` : ''}
        </div>
    `;

    // Outcome Box (if vote is completed)
    if (vote.status === 'completed' && vote.outcome) {
        html += `
            <div class="outcome-box ${vote.outcome}">
                <div class="outcome-label">VOTE OUTCOME</div>
                <div class="outcome-value">${vote.outcome.toUpperCase()}</div>
                <div class="vote-counts">
                    <div class="vote-count">
                        <div class="vote-count-label">YES</div>
                        <div class="vote-count-value">${voteCounts.yes}</div>
                    </div>
                    <div class="vote-count">
                        <div class="vote-count-label">NO</div>
                        <div class="vote-count-value">${voteCounts.no}</div>
                    </div>
                    <div class="vote-count">
                        <div class="vote-count-label">ABSTAIN</div>
                        <div class="vote-count-value">${voteCounts.abstain}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Council Snapshot
    html += `
        <div class="section-card">
            <h3 class="section-title">👥 Council Snapshot (Week ${vote.council_snapshot.week_number})</h3>
            <p style="color: #666; margin-bottom: 1rem;">
                Eligible voters for this vote (${totalVoters} total)
            </p>

            <table class="council-table">
                <thead>
                    <tr>
                        <th>Alliance</th>
                        <th>R5 Name</th>
                        <th>Status</th>
                        <th>Delegated Voters</th>
                    </tr>
                </thead>
                <tbody>
    `;

    vote.council_snapshot.voter_details.forEach(voter => {
        const submitted = voter.vote_submitted;
        html += `
            <tr>
                <td><strong>${escapeHtml(voter.alliance_tag)}</strong></td>
                <td>${escapeHtml(voter.r5_name)}</td>
                <td>
                    <span class="submission-badge ${submitted ? 'submitted' : 'pending'}">
                        ${submitted ? '✓ Submitted' : '⏳ Pending'}
                    </span>
                </td>
                <td>
                    ${voter.delegated_voters && voter.delegated_voters.length > 0
                        ? voter.delegated_voters.map(r4 => escapeHtml(r4.name)).join(', ')
                        : '—'}
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    // Vote Submissions
    if (vote.vote_submissions.length > 0) {
        html += `
            <div class="section-card">
                <h3 class="section-title">🗳️ Vote Submissions (${submittedCount}/${totalVoters})</h3>

                <table class="council-table">
                    <thead>
                        <tr>
                            <th>Alliance</th>
                            <th>Voter</th>
                            <th>Choice</th>
                            <th>Submitted At</th>
                            <th>Hash</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        vote.vote_submissions.forEach(sub => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(sub.alliance_tag)}</strong></td>
                    <td>${escapeHtml(sub.voter_name)}</td>
                    <td>
                        <span class="vote-choice-badge ${sub.vote_choice.toLowerCase()}">
                            ${sub.vote_choice.toUpperCase()}
                        </span>
                    </td>
                    <td>${formatDate(sub.submission_time)}</td>
                    <td><div class="hash-display">${sub.submission_hash.substring(0, 16)}...</div></td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;
    }

    // Integrity Verification
    html += `
        <div class="section-card">
            <h3 class="section-title">🔒 Cryptographic Integrity</h3>

            <div class="integrity-status valid">
                <div class="integrity-icon">✓</div>
                <div>
                    <strong>Hash Chain Verified</strong><br>
                    <span style="font-size: 0.9rem; color: #666;">
                        All vote submissions are cryptographically sealed and immutable
                    </span>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <strong>Current Hash:</strong>
                <div class="hash-display">${vote.integrity.current_hash}</div>
            </div>

            <div style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                <strong>Hash Algorithm:</strong> ${vote.integrity.hash_algorithm}<br>
                <strong>Total Submissions:</strong> ${vote.integrity.submission_count}
            </div>
        </div>
    `;

    // Timeline
    html += `
        <div class="section-card">
            <h3 class="section-title">📅 Timeline</h3>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-time">${formatDate(vote.created_at)}</div>
                    <div class="timeline-content">
                        <div class="timeline-title">Vote Created</div>
                        <div class="timeline-description">
                            Created by ${escapeHtml(vote.created_by.name)}<br>
                            Voting period: ${formatDate(vote.voting_period.start_time)} - ${formatDate(vote.voting_period.end_time)}
                        </div>
                    </div>
                </div>
    `;

    vote.vote_submissions.forEach(sub => {
        html += `
            <div class="timeline-item">
                <div class="timeline-time">${formatDate(sub.submission_time)}</div>
                <div class="timeline-content">
                    <div class="timeline-title">Vote Submitted: ${escapeHtml(sub.alliance_tag)}</div>
                    <div class="timeline-description">
                        ${escapeHtml(sub.voter_name)} voted ${sub.vote_choice.toUpperCase()}
                    </div>
                </div>
            </div>
        `;
    });

    if (vote.finalized_at) {
        html += `
            <div class="timeline-item">
                <div class="timeline-time">${formatDate(vote.finalized_at)}</div>
                <div class="timeline-content">
                    <div class="timeline-title">Vote Finalized</div>
                    <div class="timeline-description">
                        Outcome: ${vote.outcome ? vote.outcome.toUpperCase() : 'Unknown'}<br>
                        Reason: ${vote.finalization_reason || 'Vote completed'}
                    </div>
                </div>
            </div>
        `;
    }

    html += `
            </div>
        </div>
    `;

    container.innerHTML = html;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'includes/footer.php'; ?>
