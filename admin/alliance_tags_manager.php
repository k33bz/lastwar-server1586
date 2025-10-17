<?php
/**
 * Alliance Tags Manager
 * Admin interface for managing alliance tag categories and tags
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

// Require admin authentication
require_once 'jwt.php';
$user = require_admin_session();

// Set page title for header
$page_title = "Alliance Tags Manager";

// Include shared header
include 'includes/header.php';

// Include breadcrumbs helper
require_once 'includes/breadcrumbs.php';
?>

<?php echo render_breadcrumbs(); ?>

<div class="tags-manager-container">
    <div class="page-header">
        <h1 class="page-title">
            <span class="title-icon">🏷️</span>
            Alliance Tags Manager
        </h1>
        <p class="page-subtitle">Manage tag categories, tags, and user suggestions</p>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('categories')">
            <span class="tab-icon">📂</span>
            Categories
        </button>
        <button class="tab-btn" onclick="switchTab('tags')">
            <span class="tab-icon">🏷️</span>
            Tags
        </button>
        <button class="tab-btn" onclick="switchTab('suggestions')">
            <span class="tab-icon">💡</span>
            Suggestions
            <span class="badge" id="suggestions-badge">0</span>
        </button>
    </div>

    <!-- Categories Tab -->
    <div id="categories-tab" class="tab-content active">
        <div class="tab-header">
            <h2>Tag Categories</h2>
            <button onclick="showCategoryModal()" class="btn btn-primary">
                <span class="btn-icon">➕</span>
                Add Category
            </button>
        </div>

        <div class="search-bar">
            <input type="text" id="category-search" placeholder="Search categories..." onkeyup="filterCategories()">
        </div>

        <div id="categories-list" class="list-container">
            <div class="loading">Loading categories...</div>
        </div>
    </div>

    <!-- Tags Tab -->
    <div id="tags-tab" class="tab-content">
        <div class="tab-header">
            <h2>Alliance Tags</h2>
            <button onclick="showTagModal()" class="btn btn-primary">
                <span class="btn-icon">➕</span>
                Add Tag
            </button>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Category:</label>
                <select id="tag-category-filter" onchange="loadTags()">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Status:</label>
                <select id="tag-status-filter" onchange="loadTags()">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="search-bar">
                <input type="text" id="tag-search" placeholder="Search tags..." onkeyup="filterTags()">
            </div>
        </div>

        <div id="tags-list" class="list-container">
            <div class="loading">Loading tags...</div>
        </div>
    </div>

    <!-- Suggestions Tab -->
    <div id="suggestions-tab" class="tab-content">
        <div class="tab-header">
            <h2>User Suggestions</h2>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Status:</label>
                <select id="suggestion-status-filter" onchange="loadSuggestions()">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div id="suggestions-list" class="list-container">
            <div class="loading">Loading suggestions...</div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div id="category-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="category-modal-title">Add Category</h3>
            <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <form id="category-form" onsubmit="saveCategoryForm(event)">
            <input type="hidden" id="category-id" name="id">

            <div class="form-group">
                <label for="category-name">Category Name *</label>
                <input type="text" id="category-name" name="name" required>
            </div>

            <div class="form-group">
                <label for="category-icon">Icon</label>
                <input type="text" id="category-icon" name="icon" placeholder="🏷️" value="🏷️">
                <small class="form-help">Emoji or icon for the category</small>
            </div>

            <div class="form-group">
                <label for="category-description">Description</label>
                <textarea id="category-description" name="description" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category-order">Display Order</label>
                    <input type="number" id="category-order" name="order" value="999" min="0">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="category-active" name="active" checked>
                        Active
                    </label>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Tag Modal -->
<div id="tag-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="tag-modal-title">Add Tag</h3>
            <button class="modal-close" onclick="closeTagModal()">&times;</button>
        </div>
        <form id="tag-form" onsubmit="saveTagForm(event)">
            <input type="hidden" id="tag-id" name="id">

            <div class="form-group">
                <label for="tag-name">Tag Name *</label>
                <input type="text" id="tag-name" name="name" required>
            </div>

            <div class="form-group">
                <label for="tag-category">Category *</label>
                <select id="tag-category" name="category" required>
                    <option value="">Select category...</option>
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="tag-active" name="active" checked>
                    Active
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeTagModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Tag</button>
            </div>
        </form>
    </div>
</div>

<!-- Suggestion Review Modal -->
<div id="suggestion-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Review Suggestion</h3>
            <button class="modal-close" onclick="closeSuggestionModal()">&times;</button>
        </div>
        <div id="suggestion-details"></div>
        <form id="suggestion-form" onsubmit="reviewSuggestion(event)">
            <input type="hidden" id="suggestion-id" name="id">

            <div class="form-group">
                <label for="review-notes">Review Notes</label>
                <textarea id="review-notes" name="notes" rows="3" placeholder="Optional notes about this decision..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeSuggestionModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="rejectSuggestion()">Reject</button>
                <button type="button" class="btn btn-success" onclick="approveSuggestion()">Approve</button>
            </div>
        </form>
    </div>
</div>

<style>
.tags-manager-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.title-icon {
    font-size: 3rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
}

.tab-btn {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    color: #6c757d;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
}

.tab-btn:hover {
    color: #495057;
    background: #f8f9fa;
}

.tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab-icon {
    font-size: 1.25rem;
}

.badge {
    background: #dc3545;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* Tab Content */
.tab-content {
    display: none;
    animation: fadeIn 0.3s;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.tab-header h2 {
    color: #2c3e50;
    margin: 0;
}

/* Search and Filters */
.search-bar {
    margin-bottom: 1.5rem;
}

.search-bar input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 1rem;
}

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.filter-group select {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    min-width: 150px;
}

/* List Container */
.list-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.loading {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* List Items */
.list-item {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.list-item:hover {
    background: #f8f9fa;
}

.list-item:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.item-icon {
    font-size: 1.5rem;
}

.item-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
}

.item-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.meta-item {
    font-size: 0.875rem;
    color: #6c757d;
}

.item-description {
    color: #6c757d;
    margin-top: 0.5rem;
}

.item-actions {
    display: flex;
    gap: 0.5rem;
}

/* Badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.rejected {
    background: #f8d7da;
    color: #721c24;
}

/* Buttons */
.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-icon {
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #6c757d;
    cursor: pointer;
    line-height: 1;
}

.modal-close:hover {
    color: #495057;
}

.modal form {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 1rem;
}

.form-group textarea {
    resize: vertical;
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

/* Suggestion Details */
#suggestion-details {
    padding: 1.5rem;
}

.suggestion-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.suggestion-info p {
    margin: 0.5rem 0;
    color: #495057;
}

.suggestion-info strong {
    color: #2c3e50;
}

/* Responsive */
@media (max-width: 768px) {
    .tags-manager-container {
        padding: 1rem;
    }

    .page-title {
        font-size: 2rem;
        flex-direction: column;
    }

    .tabs {
        flex-wrap: wrap;
    }

    .tab-btn {
        flex: 1;
        min-width: 120px;
        justify-content: center;
    }

    .tab-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .filters {
        flex-direction: column;
    }

    .filter-group select {
        width: 100%;
    }

    .list-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Global state
let categories = [];
let tags = [];
let suggestions = [];
let currentTab = 'categories';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadTags();
    loadSuggestions();
});

// Tab switching
function switchTab(tab) {
    currentTab = tab;

    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');

    // Load data if needed
    if (tab === 'categories' && categories.length === 0) {
        loadCategories();
    } else if (tab === 'tags' && tags.length === 0) {
        loadTags();
    } else if (tab === 'suggestions' && suggestions.length === 0) {
        loadSuggestions();
    }
}

// Categories functions
async function loadCategories() {
    try {
        const response = await fetch('alliance_tags_api.php?action=list_categories');
        const data = await response.json();

        if (data.success) {
            categories = data.categories;
            renderCategories();
            updateCategorySelects();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        document.getElementById('categories-list').innerHTML = '<div class="loading">Error loading categories</div>';
    }
}

function renderCategories() {
    const container = document.getElementById('categories-list');

    if (categories.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📂</div>
                <p>No categories found</p>
            </div>
        `;
        return;
    }

    container.innerHTML = categories.map(cat => `
        <div class="list-item">
            <div class="item-info">
                <div class="item-header">
                    <span class="item-icon">${cat.icon}</span>
                    <span class="item-name">${escapeHtml(cat.name)}</span>
                    <span class="status-badge ${cat.active ? 'active' : 'inactive'}">${cat.active ? 'Active' : 'Inactive'}</span>
                </div>
                <div class="item-meta">
                    <span class="meta-item">ID: ${escapeHtml(cat.id)}</span>
                    <span class="meta-item">Order: ${cat.order}</span>
                </div>
                ${cat.description ? `<p class="item-description">${escapeHtml(cat.description)}</p>` : ''}
            </div>
            <div class="item-actions">
                <button class="btn btn-sm btn-secondary" onclick='editCategory(${JSON.stringify(cat)})'>
                    <span class="btn-icon">✏️</span>
                    Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCategory('${cat.id}')">
                    <span class="btn-icon">🗑️</span>
                    Delete
                </button>
            </div>
        </div>
    `).join('');
}

function filterCategories() {
    const search = document.getElementById('category-search').value.toLowerCase();
    const filtered = categories.filter(cat =>
        cat.name.toLowerCase().includes(search) ||
        cat.id.toLowerCase().includes(search) ||
        (cat.description && cat.description.toLowerCase().includes(search))
    );

    const container = document.getElementById('categories-list');
    container.innerHTML = filtered.map(cat => `
        <div class="list-item">
            <div class="item-info">
                <div class="item-header">
                    <span class="item-icon">${cat.icon}</span>
                    <span class="item-name">${escapeHtml(cat.name)}</span>
                    <span class="status-badge ${cat.active ? 'active' : 'inactive'}">${cat.active ? 'Active' : 'Inactive'}</span>
                </div>
                <div class="item-meta">
                    <span class="meta-item">ID: ${escapeHtml(cat.id)}</span>
                    <span class="meta-item">Order: ${cat.order}</span>
                </div>
                ${cat.description ? `<p class="item-description">${escapeHtml(cat.description)}</p>` : ''}
            </div>
            <div class="item-actions">
                <button class="btn btn-sm btn-secondary" onclick='editCategory(${JSON.stringify(cat)})'>
                    <span class="btn-icon">✏️</span>
                    Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCategory('${cat.id}')">
                    <span class="btn-icon">🗑️</span>
                    Delete
                </button>
            </div>
        </div>
    `).join('');
}

function showCategoryModal(category = null) {
    const modal = document.getElementById('category-modal');
    const form = document.getElementById('category-form');

    // Reset form
    form.reset();

    if (category) {
        document.getElementById('category-modal-title').textContent = 'Edit Category';
        document.getElementById('category-id').value = category.id;
        document.getElementById('category-name').value = category.name;
        document.getElementById('category-icon').value = category.icon || '';
        document.getElementById('category-description').value = category.description || '';
        document.getElementById('category-order').value = category.order || 999;
        document.getElementById('category-active').checked = category.active !== false;
    } else {
        document.getElementById('category-modal-title').textContent = 'Add Category';
        document.getElementById('category-active').checked = true;
    }

    modal.style.display = 'flex';
}

function closeCategoryModal() {
    document.getElementById('category-modal').style.display = 'none';
}

function editCategory(category) {
    showCategoryModal(category);
}

async function saveCategoryForm(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const categoryId = formData.get('id');

    const action = categoryId ? 'update_category' : 'create_category';

    try {
        const response = await fetch(`alliance_tags_api.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeCategoryModal();
            loadCategories();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        console.error('Error saving category:', error);
        showToast('Error saving category', 'error');
    }
}

async function deleteCategory(categoryId) {
    if (!confirm('Are you sure you want to delete this category?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', categoryId);

    try {
        const response = await fetch('alliance_tags_api.php?action=delete_category', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            loadCategories();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        showToast('Error deleting category', 'error');
    }
}

// Tags functions
async function loadTags() {
    try {
        const response = await fetch('alliance_tags_api.php?action=list_tags');
        const data = await response.json();

        if (data.success) {
            tags = data.tags;
            renderTags();
        }
    } catch (error) {
        console.error('Error loading tags:', error);
        document.getElementById('tags-list').innerHTML = '<div class="loading">Error loading tags</div>';
    }
}

function renderTags() {
    const container = document.getElementById('tags-list');

    if (tags.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🏷️</div>
                <p>No tags found</p>
            </div>
        `;
        return;
    }

    container.innerHTML = tags.map(tag => `
        <div class="list-item">
            <div class="item-info">
                <div class="item-header">
                    <span class="item-name">${escapeHtml(tag.name)}</span>
                    <span class="status-badge ${tag.active ? 'active' : 'inactive'}">${tag.active ? 'Active' : 'Inactive'}</span>
                </div>
                <div class="item-meta">
                    <span class="meta-item">Category: ${escapeHtml(tag.category)}</span>
                    <span class="meta-item">Votes: ${tag.votes || 0}</span>
                </div>
            </div>
            <div class="item-actions">
                <button class="btn btn-sm btn-secondary" onclick='editTag(${JSON.stringify(tag)})'>
                    <span class="btn-icon">✏️</span>
                    Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteTag('${tag.id}')">
                    <span class="btn-icon">🗑️</span>
                    Delete
                </button>
            </div>
        </div>
    `).join('');
}

function filterTags() {
    const search = document.getElementById('tag-search').value.toLowerCase();
    const statusFilter = document.getElementById('tag-status-filter').value;
    const categoryFilter = document.getElementById('tag-category-filter').value;

    let filtered = tags.filter(tag => {
        const matchesSearch = tag.name.toLowerCase().includes(search);
        const matchesStatus = !statusFilter || (statusFilter === 'active' && tag.active) || (statusFilter === 'inactive' && !tag.active);
        const matchesCategory = !categoryFilter || tag.category === categoryFilter;

        return matchesSearch && matchesStatus && matchesCategory;
    });

    const container = document.getElementById('tags-list');
    container.innerHTML = filtered.map(tag => `
        <div class="list-item">
            <div class="item-info">
                <div class="item-header">
                    <span class="item-name">${escapeHtml(tag.name)}</span>
                    <span class="status-badge ${tag.active ? 'active' : 'inactive'}">${tag.active ? 'Active' : 'Inactive'}</span>
                </div>
                <div class="item-meta">
                    <span class="meta-item">Category: ${escapeHtml(tag.category)}</span>
                    <span class="meta-item">Votes: ${tag.votes || 0}</span>
                </div>
            </div>
            <div class="item-actions">
                <button class="btn btn-sm btn-secondary" onclick='editTag(${JSON.stringify(tag)})'>
                    <span class="btn-icon">✏️</span>
                    Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteTag('${tag.id}')">
                    <span class="btn-icon">🗑️</span>
                    Delete
                </button>
            </div>
        </div>
    `).join('');
}

function showTagModal(tag = null) {
    const modal = document.getElementById('tag-modal');
    const form = document.getElementById('tag-form');

    // Reset form
    form.reset();

    if (tag) {
        document.getElementById('tag-modal-title').textContent = 'Edit Tag';
        document.getElementById('tag-id').value = tag.id;
        document.getElementById('tag-name').value = tag.name;
        document.getElementById('tag-category').value = tag.category;
        document.getElementById('tag-active').checked = tag.active !== false;
    } else {
        document.getElementById('tag-modal-title').textContent = 'Add Tag';
        document.getElementById('tag-active').checked = true;
    }

    modal.style.display = 'flex';
}

function closeTagModal() {
    document.getElementById('tag-modal').style.display = 'none';
}

function editTag(tag) {
    showTagModal(tag);
}

async function saveTagForm(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const tagId = formData.get('id');

    const action = tagId ? 'update_tag' : 'create_tag';

    try {
        const response = await fetch(`alliance_tags_api.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeTagModal();
            loadTags();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        console.error('Error saving tag:', error);
        showToast('Error saving tag', 'error');
    }
}

async function deleteTag(tagId) {
    if (!confirm('Are you sure you want to delete this tag?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', tagId);

    try {
        const response = await fetch('alliance_tags_api.php?action=delete_tag', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            loadTags();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        console.error('Error deleting tag:', error);
        showToast('Error deleting tag', 'error');
    }
}

// Suggestions functions
async function loadSuggestions() {
    try {
        const response = await fetch('alliance_tags_api.php?action=list_suggestions');
        const data = await response.json();

        if (data.success) {
            suggestions = data.suggestions;
            renderSuggestions();
            updateSuggestionsBadge();
        }
    } catch (error) {
        console.error('Error loading suggestions:', error);
        document.getElementById('suggestions-list').innerHTML = '<div class="loading">Error loading suggestions</div>';
    }
}

function renderSuggestions() {
    const container = document.getElementById('suggestions-list');
    const statusFilter = document.getElementById('suggestion-status-filter').value;

    let filtered = suggestions;
    if (statusFilter) {
        filtered = suggestions.filter(s => s.status === statusFilter);
    }

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">💡</div>
                <p>No suggestions found</p>
            </div>
        `;
        return;
    }

    container.innerHTML = filtered.map(suggestion => `
        <div class="list-item">
            <div class="item-info">
                <div class="item-header">
                    <span class="item-name">${escapeHtml(suggestion.name)}</span>
                    <span class="status-badge ${suggestion.status}">${suggestion.status.toUpperCase()}</span>
                </div>
                <div class="item-meta">
                    <span class="meta-item">Category: ${escapeHtml(suggestion.category)}</span>
                    <span class="meta-item">By: ${escapeHtml(suggestion.submittedBy)}</span>
                    <span class="meta-item">${formatDate(suggestion.submittedAt)}</span>
                </div>
                ${suggestion.reason ? `<p class="item-description">${escapeHtml(suggestion.reason)}</p>` : ''}
            </div>
            <div class="item-actions">
                ${suggestion.status === 'pending' ? `
                    <button class="btn btn-sm btn-primary" onclick='showSuggestionModal(${JSON.stringify(suggestion)})'>
                        <span class="btn-icon">👁️</span>
                        Review
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function updateSuggestionsBadge() {
    const pending = suggestions.filter(s => s.status === 'pending').length;
    const badge = document.getElementById('suggestions-badge');
    badge.textContent = pending;
    badge.style.display = pending > 0 ? 'inline-block' : 'none';
}

function showSuggestionModal(suggestion) {
    const modal = document.getElementById('suggestion-modal');
    const details = document.getElementById('suggestion-details');

    document.getElementById('suggestion-id').value = suggestion.id;
    document.getElementById('review-notes').value = '';

    details.innerHTML = `
        <div class="suggestion-info">
            <p><strong>Tag Name:</strong> ${escapeHtml(suggestion.name)}</p>
            <p><strong>Category:</strong> ${escapeHtml(suggestion.category)}</p>
            <p><strong>Submitted By:</strong> ${escapeHtml(suggestion.submittedBy)}</p>
            <p><strong>Date:</strong> ${formatDate(suggestion.submittedAt)}</p>
            ${suggestion.reason ? `<p><strong>Reason:</strong> ${escapeHtml(suggestion.reason)}</p>` : ''}
        </div>
    `;

    modal.style.display = 'flex';
}

function closeSuggestionModal() {
    document.getElementById('suggestion-modal').style.display = 'none';
}

async function approveSuggestion() {
    await reviewSuggestionAction('approved');
}

async function rejectSuggestion() {
    await reviewSuggestionAction('rejected');
}

async function reviewSuggestionAction(status) {
    const suggestionId = document.getElementById('suggestion-id').value;
    const notes = document.getElementById('review-notes').value;

    const formData = new FormData();
    formData.append('id', suggestionId);
    formData.append('status', status);
    formData.append('notes', notes);

    try {
        const response = await fetch('alliance_tags_api.php?action=review_suggestion', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            closeSuggestionModal();
            loadSuggestions();
            loadTags(); // Reload tags if approved
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        console.error('Error reviewing suggestion:', error);
        showToast('Error reviewing suggestion', 'error');
    }
}

// Utility functions
function updateCategorySelects() {
    // Update tag category select
    const tagCategorySelect = document.getElementById('tag-category');
    tagCategorySelect.innerHTML = '<option value="">Select category...</option>' +
        categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');

    // Update filter category select
    const filterCategorySelect = document.getElementById('tag-category-filter');
    filterCategorySelect.innerHTML = '<option value="">All Categories</option>' +
        categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function showToast(message, type = 'info') {
    // Use existing toast system from footer
    if (typeof showToast !== 'undefined') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
