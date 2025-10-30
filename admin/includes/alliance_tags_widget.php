<?php
/**
 * Alliance Tags Widget
 * Interactive tag cloud component for alliance profiles
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

if (!defined('ALLIANCE_TAGS_WIDGET')) {
    define('ALLIANCE_TAGS_WIDGET', true);
}

/**
 * Render the alliance tags widget
 *
 * @param string $alliance_tag Alliance tag (e.g., "UvvU")
 * @param object $user_token Current user's JWT token
 * @return string HTML output
 */
function render_alliance_tags_widget($alliance_tag, $user_token) {
    $can_edit = has_alliance_access($user_token, $alliance_tag);
    $is_admin = ($user_token->aud === 'admin' && in_array('*', $user_token->alliances));

    ob_start();
    ?>

    <div class="alliance-tags-section">
        <div class="section-header">
            <h2>🏷️ Alliance Tags</h2>
            <p class="section-description">Tags that describe your alliance's identity, playstyle, and strengths</p>
        </div>

        <!-- Selected Tags Display -->
        <div id="selected-tags-container" class="selected-tags-container">
            <div class="loading-state">
                <span class="loading-spinner"></span>
                Loading tags...
            </div>
        </div>

        <?php if ($can_edit): ?>
        <!-- Edit Button -->
        <div class="tags-actions">
            <button onclick="openTagSelectorModal()" class="btn btn-primary">
                <span class="btn-icon">✏️</span>
                Edit Tags
            </button>
            <button onclick="openSuggestTagModal()" class="btn btn-secondary">
                <span class="btn-icon">💡</span>
                Suggest New Tag
            </button>
        </div>
        <?php endif; ?>

        <!-- Empty State (hidden by default) -->
        <div id="tags-empty-state" class="tags-empty-state" style="display: none;">
            <div class="empty-state-icon">🏷️</div>
            <p>No tags selected yet</p>
            <?php if ($can_edit): ?>
            <p class="empty-state-hint">Click "Edit Tags" to add tags that describe your alliance</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tag Selector Modal -->
    <div id="tag-selector-modal" class="tag-modal" style="display: none;">
        <div class="tag-modal-backdrop"></div>
        <div class="tag-modal-content">
            <div class="tag-modal-header">
                <h3>Select Alliance Tags</h3>
                <button class="tag-modal-close" onclick="closeTagSelectorModal()">&times;</button>
            </div>

            <div class="tag-modal-body">
                <!-- Category Tabs -->
                <div class="tag-categories" id="tag-categories">
                    <div class="loading-state">Loading categories...</div>
                </div>

                <!-- Tag Selection -->
                <div class="tag-selection-container" id="tag-selection-container">
                    <div class="loading-state">Loading tags...</div>
                </div>

                <!-- Selected Count -->
                <div class="selection-info">
                    <span id="selection-count">0 tags selected</span>
                    <span class="selection-hint">Select tags that best describe your alliance</span>
                </div>
            </div>

            <div class="tag-modal-footer">
                <button class="btn btn-secondary" onclick="closeTagSelectorModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveSelectedTags()">
                    <span class="btn-icon">💾</span>
                    Save Tags
                </button>
            </div>
        </div>
    </div>

    <!-- Suggest Tag Modal -->
    <div id="suggest-tag-modal" class="tag-modal" style="display: none;">
        <div class="tag-modal-backdrop"></div>
        <div class="tag-modal-content" style="max-width: 500px;">
            <div class="tag-modal-header">
                <h3>Suggest a New Tag</h3>
                <button class="tag-modal-close" onclick="closeSuggestTagModal()">&times;</button>
            </div>

            <form id="suggest-tag-form" onsubmit="submitTagSuggestion(event)">
                <div class="tag-modal-body">
                    <div class="form-group">
                        <label for="suggest-tag-name">Tag Name *</label>
                        <input type="text" id="suggest-tag-name" name="name" required placeholder="e.g., PvE Focused">
                        <small class="form-help">Enter a descriptive name for the tag</small>
                    </div>

                    <div class="form-group">
                        <label for="suggest-tag-category">Category *</label>
                        <select id="suggest-tag-category" name="category" required>
                            <option value="">Select a category...</option>
                        </select>
                        <small class="form-help">Choose the category that best fits this tag</small>
                    </div>

                    <div class="form-group">
                        <label for="suggest-tag-reason">Reason (Optional)</label>
                        <textarea id="suggest-tag-reason" name="reason" rows="3" placeholder="Why is this tag useful? How does it describe alliances?"></textarea>
                        <small class="form-help">Help admins understand why this tag should be added</small>
                    </div>

                    <div class="info-box">
                        <strong>ℹ️ Note:</strong>
                        <p>Tag suggestions are reviewed by administrators before being added to the system. You'll be notified once your suggestion is reviewed.</p>
                    </div>
                </div>

                <div class="tag-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeSuggestTagModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💡</span>
                        Submit Suggestion
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    /* Alliance Tags Section */
    .alliance-tags-section {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .section-header {
        margin-bottom: 1.5rem;
    }

    .section-header h2 {
        color: #2c3e50;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .section-description {
        color: #6c757d;
        font-size: 0.95rem;
        margin: 0;
    }

    /* Selected Tags Container */
    .selected-tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        min-height: 60px;
    }

    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: default;
    }

    .tag-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .tag-chip.category-identity {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .tag-chip.category-gameplay {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .tag-chip.category-performance {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .tag-chip.category-dynamics {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    /* Tags Actions */
    .tags-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* Empty State */
    .tags-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state-hint {
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    /* Loading State */
    .loading-state {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 2rem;
        color: #6c757d;
    }

    .loading-spinner {
        width: 20px;
        height: 20px;
        border: 3px solid #e9ecef;
        border-top-color: #667eea;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Tag Modal */
    .tag-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .tag-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .tag-modal-content {
        position: relative;
        background: white;
        border-radius: 16px;
        max-width: 900px;
        width: 90%;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .tag-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e9ecef;
    }

    .tag-modal-header h3 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.5rem;
    }

    .tag-modal-close {
        background: none;
        border: none;
        font-size: 2rem;
        color: #6c757d;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .tag-modal-close:hover {
        background: #f8f9fa;
        color: #495057;
    }

    .tag-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
    }

    .tag-modal-footer {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding: 1.5rem 2rem;
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
        border-radius: 0 0 16px 16px;
    }

    /* Category Tabs */
    .tag-categories {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .category-tab {
        padding: 0.75rem 1.5rem;
        background: #f8f9fa;
        border: 2px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .category-tab:hover {
        background: #e9ecef;
        color: #495057;
    }

    .category-tab.active {
        background: white;
        border-color: #667eea;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    }

    .category-icon {
        font-size: 1.25rem;
    }

    /* Tag Selection */
    .tag-selection-container {
        min-height: 200px;
    }

    .tag-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }

    .tag-option {
        padding: 1rem;
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .tag-option:hover {
        background: #e9ecef;
        border-color: #ced4da;
    }

    .tag-option.selected {
        background: linear-gradient(135deg, #667eea15, #764ba215);
        border-color: #667eea;
    }

    .tag-option input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #667eea;
    }

    .tag-option-label {
        flex: 1;
        font-weight: 500;
        color: #2c3e50;
        cursor: pointer;
    }

    /* Selection Info */
    .selection-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    #selection-count {
        font-weight: 600;
        color: #667eea;
    }

    .selection-hint {
        font-size: 0.875rem;
        color: #6c757d;
    }

    /* Form Elements */
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
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-group input[type="text"]:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

    .info-box {
        background: #e7f3ff;
        border: 1px solid #3498db;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }

    .info-box strong {
        color: #2c3e50;
        display: block;
        margin-bottom: 0.5rem;
    }

    .info-box p {
        margin: 0;
        font-size: 0.9rem;
        color: #495057;
    }

    /* Buttons */
    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-icon {
        font-size: 1.1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .tag-modal-content {
            width: 95%;
            max-height: 90vh;
        }

        .tag-modal-header,
        .tag-modal-body,
        .tag-modal-footer {
            padding: 1rem;
        }

        .tag-grid {
            grid-template-columns: 1fr;
        }

        .category-tab {
            flex: 1;
            justify-content: center;
            min-width: calc(50% - 0.25rem);
        }

        .selection-info {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }

        .tags-actions {
            width: 100%;
        }

        .tags-actions .btn {
            flex: 1;
            justify-content: center;
        }
    }
    </style>

    <script>
    // Global state for tag widget
    let allianceTag = '<?php echo addslashes($alliance_tag); ?>';
    let categories = [];
    let allTags = [];
    let selectedTags = [];
    let currentCategoryFilter = null;

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAllianceTags();
        loadCategoriesForSuggestion();
    });

    // Load current alliance tags
    async function loadAllianceTags() {
        try {
            const response = await fetch(`alliance_tags_api.php?action=get_alliance_tags&tag=${encodeURIComponent(allianceTag)}`);
            const data = await response.json();

            if (data.success) {
                selectedTags = data.data.tags || [];
                await loadAllTagsData();
                renderSelectedTags();
            }
        } catch (error) {
            console.error('Error loading alliance tags:', error);
            document.getElementById('selected-tags-container').innerHTML = '<div class="loading-state">Error loading tags</div>';
        }
    }

    // Load all tags data for rendering
    async function loadAllTagsData() {
        try {
            const response = await fetch('alliance_tags_api.php?action=list_tags');
            const data = await response.json();

            if (data.success) {
                allTags = data.tags;
            }
        } catch (error) {
            console.error('Error loading tags data:', error);
        }
    }

    // Render selected tags
    function renderSelectedTags() {
        const container = document.getElementById('selected-tags-container');
        const emptyState = document.getElementById('tags-empty-state');

        if (selectedTags.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        container.style.display = 'flex';
        emptyState.style.display = 'none';

        const tagsHtml = selectedTags.map(tagId => {
            const tag = allTags.find(t => t.id === tagId);
            if (!tag) return '';

            return `<div class="tag-chip category-${escapeAttr(tag.category)}">${escapeHtml(tag.name)}</div>`;
        }).filter(Boolean).join('');

        container.innerHTML = tagsHtml || '<div class="loading-state">No tags selected</div>';
    }

    // Open tag selector modal
    async function openTagSelectorModal() {
        const modal = document.getElementById('tag-selector-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        await loadTagSelectionData();
    }

    // Close tag selector modal
    function closeTagSelectorModal() {
        const modal = document.getElementById('tag-selector-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Load data for tag selection
    async function loadTagSelectionData() {
        try {
            // Load categories
            const catResponse = await fetch('alliance_tags_api.php?action=list_categories');
            const catData = await catResponse.json();

            if (catData.success) {
                categories = catData.categories;
                renderCategoryTabs();

                // Select first category by default
                if (categories.length > 0) {
                    filterTagsByCategory(categories[0].id);
                }
            }

            // Load all tags
            const tagsResponse = await fetch('alliance_tags_api.php?action=list_tags');
            const tagsData = await tagsResponse.json();

            if (tagsData.success) {
                allTags = tagsData.tags;
            }
        } catch (error) {
            console.error('Error loading tag selection data:', error);
        }
    }

    // Render category tabs
    function renderCategoryTabs() {
        const container = document.getElementById('tag-categories');

        container.innerHTML = categories.map(cat => `
            <div class="category-tab ${currentCategoryFilter === cat.id ? 'active' : ''}"
                 onclick="filterTagsByCategory('${escapeAttr(cat.id)}')">
                <span class="category-icon">${escapeHtml(cat.icon)}</span>
                <span>${escapeHtml(cat.name)}</span>
            </div>
        `).join('');
    }

    // Filter tags by category
    function filterTagsByCategory(categoryId) {
        currentCategoryFilter = categoryId;
        renderCategoryTabs();
        renderTagOptions();
    }

    // Render tag options
    function renderTagOptions() {
        const container = document.getElementById('tag-selection-container');

        const filtered = allTags.filter(tag => tag.category === currentCategoryFilter);

        if (filtered.length === 0) {
            container.innerHTML = '<div class="loading-state">No tags in this category</div>';
            return;
        }

        container.innerHTML = `
            <div class="tag-grid">
                ${filtered.map(tag => `
                    <div class="tag-option ${selectedTags.includes(tag.id) ? 'selected' : ''}"
                         onclick="toggleTag('${escapeAttr(tag.id)}')">
                        <input type="checkbox"
                               id="tag-${escapeAttr(tag.id)}"
                               ${selectedTags.includes(tag.id) ? 'checked' : ''}
                               onchange="toggleTag('${escapeAttr(tag.id)}')">
                        <label class="tag-option-label" for="tag-${escapeAttr(tag.id)}">${escapeHtml(tag.name)}</label>
                    </div>
                `).join('')}
            </div>
        `;

        updateSelectionCount();
    }

    // Toggle tag selection
    function toggleTag(tagId) {
        const index = selectedTags.indexOf(tagId);

        if (index > -1) {
            selectedTags.splice(index, 1);
        } else {
            selectedTags.push(tagId);
        }

        renderTagOptions();
    }

    // Update selection count
    function updateSelectionCount() {
        const countElement = document.getElementById('selection-count');
        countElement.textContent = `${selectedTags.length} tag${selectedTags.length !== 1 ? 's' : ''} selected`;
    }

    // Save selected tags
    async function saveSelectedTags() {
        const formData = new FormData();
        formData.append('tag', allianceTag);
        formData.append('tags', JSON.stringify(selectedTags));

        try {
            const response = await fetch('alliance_tags_api.php?action=update_alliance_tags', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeTagSelectorModal();
                renderSelectedTags();
            } else {
                showToast(data.error, 'error');
            }
        } catch (error) {
            console.error('Error saving tags:', error);
            showToast('Error saving tags', 'error');
        }
    }

    // Open suggest tag modal
    function openSuggestTagModal() {
        const modal = document.getElementById('suggest-tag-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Reset form
        document.getElementById('suggest-tag-form').reset();
    }

    // Close suggest tag modal
    function closeSuggestTagModal() {
        const modal = document.getElementById('suggest-tag-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Load categories for suggestion form
    async function loadCategoriesForSuggestion() {
        try {
            const response = await fetch('alliance_tags_api.php?action=list_categories');
            const data = await response.json();

            if (data.success) {
                const select = document.getElementById('suggest-tag-category');
                select.innerHTML = '<option value="">Select a category...</option>' +
                    data.categories.map(cat =>
                        `<option value="${escapeAttr(cat.id)}">${escapeHtml(cat.icon)} ${escapeHtml(cat.name)}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    // Submit tag suggestion
    async function submitTagSuggestion(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('alliance_tags_api.php?action=suggest_tag', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                closeSuggestTagModal();
            } else {
                showToast(data.error, 'error');
            }
        } catch (error) {
            console.error('Error submitting suggestion:', error);
            showToast('Error submitting suggestion', 'error');
        }
    }

    // Utility function
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Escape for use in HTML attributes (including single and double quotes)
    function escapeAttr(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;');
    }

    // Close modals on backdrop click
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('tag-modal-backdrop')) {
            if (document.getElementById('tag-selector-modal').style.display === 'flex') {
                closeTagSelectorModal();
            }
            if (document.getElementById('suggest-tag-modal').style.display === 'flex') {
                closeSuggestTagModal();
            }
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (document.getElementById('tag-selector-modal').style.display === 'flex') {
                closeTagSelectorModal();
            }
            if (document.getElementById('suggest-tag-modal').style.display === 'flex') {
                closeSuggestTagModal();
            }
        }
    });
    </script>

    <?php
    return ob_get_clean();
}
?>
