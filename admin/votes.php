<?php
/**
 * Votes Management Page
 * View and manage council votes from Discord bot
 *
 * @version 1.0.0
 * @date 2025-11-09
 */

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Set page title for header
$page_title = "Votes Management";

// Create proper user token for role checking
$user_token = (object)[
    'sub' => $user->sub,
    'aud' => $user->aud,
    'alliances' => $user->alliances ?? []
];

// Only admins can view all votes
$is_admin = $user_token->aud === 'admin';

include 'includes/header.php';
?>

<style>
    .votes-container {
        padding: 20px;
    }

    .votes-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }

    .votes-header h1 {
        margin: 0;
        color: #2c3e50;
        font-size: 2rem;
    }

    .votes-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .stat-card.active {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.completed {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.approved {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-card.rejected {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .filters-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
    }

    .filter-group select,
    .filter-group input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.3s;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #667eea;
    }

    .btn-filter {
        padding: 10px 24px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-filter:hover {
        background: #5a67d8;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .votes-table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .votes-table {
        width: 100%;
        border-collapse: collapse;
    }

    .votes-table thead {
        background: #2c3e50;
        color: white;
    }

    .votes-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .votes-table td {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
    }

    .votes-table tbody tr {
        transition: background-color 0.2s;
        cursor: pointer;
    }

    .votes-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .vote-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 4px;
    }

    .vote-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .vote-status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .vote-status-badge.active {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .vote-status-badge.completed {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .vote-status-badge.approved {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .vote-status-badge.rejected {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .vote-category-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #e7f1ff;
        color: #004085;
        border: 1px solid #b8daff;
    }

    .vote-progress {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .progress-bar {
        flex: 1;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s;
    }

    .progress-text {
        font-size: 0.85rem;
        font-weight: 600;
        color: #2c3e50;
        min-width: 60px;
        text-align: right;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state svg {
        width: 80px;
        height: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: #667eea;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .votes-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .votes-stats {
            grid-template-columns: 1fr;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .votes-table {
            font-size: 0.9rem;
        }

        .votes-table th,
        .votes-table td {
            padding: 10px;
        }
    }
</style>

<div class="votes-container">
    <div class="votes-header">
        <h1>📊 Council Votes</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="votes-stats" id="votesStats">
        <div class="stat-card active">
            <div class="stat-value" id="statActive">0</div>
            <div class="stat-label">Active Votes</div>
        </div>
        <div class="stat-card completed">
            <div class="stat-value" id="statCompleted">0</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-value" id="statApproved">0</div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-value" id="statRejected">0</div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label for="filterStatus">Status</label>
                <select id="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterCategory">Category</label>
                <select id="filterCategory">
                    <option value="">All Categories</option>
                    <option value="rule_change">Rule Change</option>
                    <option value="alliance_action">Alliance Action</option>
                    <option value="server_event">Server Event</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterOutcome">Outcome</label>
                <select id="filterOutcome">
                    <option value="">All Outcomes</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="tie">Tie</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterSearch">Search</label>
                <input type="text" id="filterSearch" placeholder="Search title or description...">
            </div>

            <div class="filter-group" style="align-self: end;">
                <button class="btn-filter" onclick="applyFilters()">Apply Filters</button>
            </div>
        </div>
    </div>

    <!-- Votes Table -->
    <div class="votes-table-container">
        <div id="loadingState" class="loading">Loading votes...</div>

        <table class="votes-table" id="votesTable" style="display: none;">
            <thead>
                <tr>
                    <th>Vote</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Created</th>
                    <th>Outcome</th>
                </tr>
            </thead>
            <tbody id="votesTableBody">
                <!-- Populated via JavaScript -->
            </tbody>
        </table>

        <div id="emptyState" class="empty-state" style="display: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
            <h3>No Votes Found</h3>
            <p>No council votes match your current filters.</p>
        </div>
    </div>
</div>

<script>
let allVotes = [];
let filteredVotes = [];

// Load votes on page load
document.addEventListener('DOMContentLoaded', async () => {
    await loadVotes();
});

async function loadVotes() {
    try {
        const response = await fetch('../data/discord-votes.json');
        const data = await response.json();

        allVotes = data.votes || [];
        filteredVotes = [...allVotes];

        updateStatistics();
        renderVotesTable();

        document.getElementById('loadingState').style.display = 'none';

        if (filteredVotes.length > 0) {
            document.getElementById('votesTable').style.display = 'table';
        } else {
            document.getElementById('emptyState').style.display = 'block';
        }
    } catch (error) {
        console.error('Failed to load votes:', error);
        document.getElementById('loadingState').textContent = 'Failed to load votes. Please try again.';
    }
}

function updateStatistics() {
    const stats = {
        active: 0,
        completed: 0,
        approved: 0,
        rejected: 0
    };

    allVotes.forEach(vote => {
        if (vote.status === 'active') {
            stats.active++;
        } else if (vote.status === 'completed') {
            stats.completed++;

            if (vote.outcome === 'approved') {
                stats.approved++;
            } else if (vote.outcome === 'rejected') {
                stats.rejected++;
            }
        }
    });

    document.getElementById('statActive').textContent = stats.active;
    document.getElementById('statCompleted').textContent = stats.completed;
    document.getElementById('statApproved').textContent = stats.approved;
    document.getElementById('statRejected').textContent = stats.rejected;
}

function renderVotesTable() {
    const tbody = document.getElementById('votesTableBody');

    if (filteredVotes.length === 0) {
        document.getElementById('votesTable').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
        return;
    }

    document.getElementById('votesTable').style.display = 'table';
    document.getElementById('emptyState').style.display = 'none';

    tbody.innerHTML = filteredVotes.map(vote => {
        const createdDate = new Date(vote.created_at);
        const totalVoters = vote.council_snapshot.voter_details.length;
        const submittedVotes = vote.vote_submissions.length;
        const progressPercent = Math.round((submittedVotes / totalVoters) * 100);

        const statusClass = vote.status === 'active' ? 'active' :
                           vote.outcome === 'approved' ? 'approved' :
                           vote.outcome === 'rejected' ? 'rejected' : 'completed';

        return `
            <tr onclick="viewVoteDetails('${vote.vote_id}')">
                <td>
                    <div class="vote-title">${escapeHtml(vote.vote_details.title)}</div>
                    <div class="vote-meta">ID: ${vote.vote_id}</div>
                </td>
                <td>
                    <span class="vote-category-badge">${formatCategory(vote.vote_details.category)}</span>
                </td>
                <td>
                    <span class="vote-status-badge ${statusClass}">${vote.status}</span>
                </td>
                <td>
                    <div class="vote-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressPercent}%"></div>
                        </div>
                        <span class="progress-text">${submittedVotes}/${totalVoters}</span>
                    </div>
                </td>
                <td>${formatDate(createdDate)}</td>
                <td>${vote.outcome ? formatOutcome(vote.outcome) : '—'}</td>
            </tr>
        `;
    }).join('');
}

function applyFilters() {
    const status = document.getElementById('filterStatus').value;
    const category = document.getElementById('filterCategory').value;
    const outcome = document.getElementById('filterOutcome').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();

    filteredVotes = allVotes.filter(vote => {
        if (status && vote.status !== status) return false;
        if (category && vote.vote_details.category !== category) return false;
        if (outcome && vote.outcome !== outcome) return false;

        if (search) {
            const titleMatch = vote.vote_details.title.toLowerCase().includes(search);
            const descMatch = vote.vote_details.description.toLowerCase().includes(search);
            if (!titleMatch && !descMatch) return false;
        }

        return true;
    });

    renderVotesTable();
}

function viewVoteDetails(voteId) {
    // Will implement detailed view modal
    window.location.href = `vote_details.php?id=${encodeURIComponent(voteId)}`;
}

function formatCategory(category) {
    const categories = {
        'rule_change': 'Rule Change',
        'alliance_action': 'Alliance Action',
        'server_event': 'Server Event',
        'other': 'Other'
    };
    return categories[category] || category;
}

function formatOutcome(outcome) {
    const outcomes = {
        'approved': '✓ Approved',
        'rejected': '✗ Rejected',
        'tie': '⚖ Tie'
    };
    return outcomes[outcome] || outcome;
}

function formatDate(date) {
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) return 'Today';
    if (days === 1) return 'Yesterday';
    if (days < 7) return `${days} days ago`;

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'includes/footer.php'; ?>
