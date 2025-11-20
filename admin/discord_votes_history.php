<?php
/**
 * Discord Votes History Dashboard
 * Version: 1.0.0
 *
 * Displays all Discord council votes (active and completed) with:
 * - Vote details, submissions, results
 * - Filter by status, date range
 * - Email notification tracking
 * - Real-time status updates
 *
 * Access: Admin, President, R5, R4, APE roles
 */

require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'includes/i18n.php';

// Initialize i18n
i18n_init();

$user = require_jwt_session();

if (!has_role($user, ['admin', 'president', 'r5', 'r4', 'ape'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

log_audit_event('discord_votes_history_accessed', $user->sub, [
    'user_role' => $user->aud
]);

$page_title = __('pages.discord_votes_history.title');
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🗳️ <?php echo __('pages.discord_votes_history.title'); ?></h1>
    <p class="page-description"><?php echo __('pages.discord_votes_history.description'); ?></p>
</div>

<div class="container">
    <style>
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        .filters-bar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.875rem;
            min-width: 150px;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-left: auto;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            font-size: 0.875rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .votes-grid {
            display: grid;
            gap: 1.5rem;
        }

        .vote-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .vote-card.completed {
            border-left-color: #28a745;
        }

        .vote-card.active {
            border-left-color: #ffc107;
        }

        .vote-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
        }

        .vote-title {
            flex: 1;
        }

        .vote-title h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .vote-meta {
            font-size: 0.875rem;
            color: #666;
        }

        .vote-status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .vote-status-badge.active {
            background: #fff3cd;
            color: #856404;
        }

        .vote-status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .vote-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .vote-details p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }

        .vote-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
        }

        .info-item label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .info-item span {
            display: block;
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        .submissions-section {
            margin-bottom: 1.5rem;
        }

        .submissions-section h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }

        .submissions-table th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }

        .submissions-table td {
            padding: 0.75rem;
            border-top: 1px solid #e9ecef;
            font-size: 0.875rem;
        }

        .vote-choice-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
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
            background: #fff3cd;
            color: #856404;
        }

        .method-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .method-badge.discord {
            background: #5865f2;
            color: white;
        }

        .method-badge.email {
            background: #28a745;
            color: white;
        }

        .results-section {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .results-section h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1565c0;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .result-item {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }

        .result-item .count {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .result-item .label {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .outcome-badge {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .outcome-badge.approved {
            background: #28a745;
            color: white;
        }

        .outcome-badge.rejected {
            background: #dc3545;
            color: white;
        }

        .outcome-badge.tied {
            background: #ffc107;
            color: #856404;
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #667eea;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .countdown {
            font-size: 0.875rem;
            color: #dc3545;
            font-weight: 600;
        }
    </style>

    <!-- Filters -->
    <div class="filters-bar">
        <div class="filter-group">
            <label>Status</label>
            <select id="statusFilter" onchange="applyFilters()">
                <option value="all">All Votes</option>
                <option value="active">Active Only</option>
                <option value="completed">Completed Only</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Category</label>
            <select id="categoryFilter" onchange="applyFilters()">
                <option value="all">All Categories</option>
                <option value="rule_change">Rule Change</option>
                <option value="alliance_action">Alliance Action</option>
                <option value="server_event">Server Event</option>
                <option value="other">Other</option>
            </select>
        </div>

        <button class="refresh-btn" onclick="loadVotes()">
            🔄 Refresh
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-cards" id="statsCards">
        <div class="stat-card">
            <div class="value" id="totalVotes">-</div>
            <div class="label">Total Votes</div>
        </div>
        <div class="stat-card">
            <div class="value" id="activeVotes">-</div>
            <div class="label">Active Votes</div>
        </div>
        <div class="stat-card">
            <div class="value" id="completedVotes">-</div>
            <div class="label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="value" id="totalSubmissions">-</div>
            <div class="label">Total Submissions</div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="loading">
        <div class="spinner"></div>
        <p>Loading vote history...</p>
    </div>

    <!-- Votes Grid -->
    <div id="votesGrid" class="votes-grid" style="display: none;">
        <!-- Votes will be inserted here -->
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 7H4C2.9 7 2 7.9 2 9V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V9C22 7.9 21.1 7 20 7Z" stroke="currentColor" stroke-width="2"/>
            <path d="M12 12L2 9L12 6L22 9L12 12Z" stroke="currentColor" stroke-width="2"/>
            <path d="M7 15H7.01M12 15H12.01M17 15H17.01" stroke="currentColor" stroke-width="2"/>
        </svg>
        <h3>No votes found</h3>
        <p>No votes match your current filters</p>
    </div>
</div>

<script>
    let allVotes = [];
    let currentFilters = {
        status: 'all',
        category: 'all'
    };

    // Load votes on page load
    window.addEventListener('DOMContentLoaded', loadVotes);

    async function loadVotes() {
        document.getElementById('loadingState').style.display = 'block';
        document.getElementById('votesGrid').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';

        try {
            const response = await fetch('discord_votes_api.php?action=get_votes');
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to load votes');
            }

            allVotes = data.votes || [];
            updateStats();
            applyFilters();

        } catch (error) {
            console.error('Error loading votes:', error);
            alert('Failed to load votes: ' + error.message);
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';
        }
    }

    function updateStats() {
        const total = allVotes.length;
        const active = allVotes.filter(v => v.status === 'active').length;
        const completed = allVotes.filter(v => v.status === 'completed').length;
        const totalSubs = allVotes.reduce((sum, v) => sum + (v.submissions?.length || 0), 0);

        document.getElementById('totalVotes').textContent = total;
        document.getElementById('activeVotes').textContent = active;
        document.getElementById('completedVotes').textContent = completed;
        document.getElementById('totalSubmissions').textContent = totalSubs;
    }

    function applyFilters() {
        currentFilters.status = document.getElementById('statusFilter').value;
        currentFilters.category = document.getElementById('categoryFilter').value;

        let filtered = allVotes.filter(vote => {
            if (currentFilters.status !== 'all' && vote.status !== currentFilters.status) {
                return false;
            }

            if (currentFilters.category !== 'all' && vote.vote_details.category !== currentFilters.category) {
                return false;
            }

            return true;
        });

        renderVotes(filtered);
    }

    function renderVotes(votes) {
        document.getElementById('loadingState').style.display = 'none';

        if (votes.length === 0) {
            document.getElementById('votesGrid').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';
            return;
        }

        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('votesGrid').style.display = 'grid';

        const votesGrid = document.getElementById('votesGrid');
        votesGrid.innerHTML = votes.map(vote => renderVoteCard(vote)).join('');
    }

    function renderVoteCard(vote) {
        const isCompleted = vote.status === 'completed';
        const createdDate = new Date(vote.created_at).toLocaleString();
        const endDate = new Date(vote.voting_period.end_time).toLocaleString();

        // Calculate time remaining or result
        let timeInfo = '';
        if (vote.status === 'active') {
            const now = new Date();
            const end = new Date(vote.voting_period.end_time);
            const diff = end - now;

            if (diff > 0) {
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                timeInfo = `<span class="countdown">⏰ ${hours}h ${minutes}m remaining</span>`;
            } else {
                timeInfo = `<span class="countdown">⏰ Expired (awaiting finalization)</span>`;
            }
        }

        // Render submissions
        const submissionsHtml = vote.submissions && vote.submissions.length > 0 ? `
            <div class="submissions-section">
                <h4>📝 Submissions (${vote.submissions.length}/${vote.council_snapshot.voter_details.length})</h4>
                <table class="submissions-table">
                    <thead>
                        <tr>
                            <th>Alliance</th>
                            <th>Voter</th>
                            <th>Choice</th>
                            <th>Method</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${vote.submissions.map(sub => `
                            <tr>
                                <td><strong>${sub.alliance_tag}</strong></td>
                                <td>${sub.submitted_by.username || sub.submitted_by.user_email || 'Unknown'}</td>
                                <td><span class="vote-choice-badge ${sub.vote_choice}">${sub.vote_choice.toUpperCase()}</span></td>
                                <td><span class="method-badge ${sub.submission_method}">${sub.submission_method}</span></td>
                                <td>${new Date(sub.submitted_at).toLocaleString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : '<p style="color: #999; text-align: center; padding: 1rem;">No submissions yet</p>';

        // Render results
        const resultsHtml = isCompleted && vote.results ? `
            <div class="results-section">
                <h4>📊 Final Results</h4>
                <div style="text-align: center; margin-bottom: 1rem;">
                    <span class="outcome-badge ${vote.results.outcome}">${vote.results.outcome}</span>
                </div>
                <div class="results-grid">
                    <div class="result-item">
                        <div class="count" style="color: #28a745;">✅ ${vote.results.yes_count}</div>
                        <div class="label">Yes</div>
                    </div>
                    <div class="result-item">
                        <div class="count" style="color: #dc3545;">❌ ${vote.results.no_count}</div>
                        <div class="label">No</div>
                    </div>
                    <div class="result-item">
                        <div class="count" style="color: #ffc107;">⚪ ${vote.results.abstain_count}</div>
                        <div class="label">Abstain</div>
                    </div>
                    <div class="result-item">
                        <div class="count" style="color: #6c757d;">⭕ ${vote.results.absent_count}</div>
                        <div class="label">Absent</div>
                    </div>
                </div>
            </div>
        ` : '';

        return `
            <div class="vote-card ${vote.status}">
                <div class="vote-header">
                    <div class="vote-title">
                        <h3>${vote.vote_details.title}</h3>
                        <div class="vote-meta">
                            <span>Created: ${createdDate}</span> •
                            <span>ID: ${vote.vote_id}</span> •
                            <span>Category: ${vote.vote_details.category.replace('_', ' ').toUpperCase()}</span>
                        </div>
                    </div>
                    <span class="vote-status-badge ${vote.status}">${vote.status}</span>
                </div>

                <div class="vote-details">
                    <p>${vote.vote_details.description}</p>
                </div>

                <div class="vote-info-grid">
                    <div class="info-item">
                        <label>Created By</label>
                        <span>${vote.created_by.username || vote.created_by.user_email}</span>
                    </div>
                    <div class="info-item">
                        <label>Source</label>
                        <span>${vote.created_by.source || 'Unknown'}</span>
                    </div>
                    <div class="info-item">
                        <label>Deadline</label>
                        <span>${endDate}</span>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <span>${timeInfo || 'Completed: ' + new Date(vote.results?.finalized_at || '').toLocaleString()}</span>
                    </div>
                </div>

                ${submissionsHtml}
                ${resultsHtml}
            </div>
        `;
    }

    // Auto-refresh every 30 seconds for active votes
    setInterval(() => {
        const hasActiveVotes = allVotes.some(v => v.status === 'active');
        if (hasActiveVotes) {
            loadVotes();
        }
    }, 30000);
</script>

<?php include 'includes/footer.php'; ?>
