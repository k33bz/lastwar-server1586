<?php
/**
 * Discord Message Templates Manager
 * Version: 1.0.0
 *
 * Manage reusable message templates with variable support
 */

require_once 'jwt.php';
require_once 'discord_webhook.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

if (!DISCORD_ENABLED) {
    header('Location: dashboard.php?error=discord_disabled');
    exit();
}

log_audit_event('discord_templates_accessed', $user->sub, [
    'user_roles' => get_user_roles($user)
]);

$page_title = "Message Templates";
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📝 Discord Message Templates</h1>
    <p class="page-description">Create and manage reusable message templates with variables</p>
</div>

<div class="container">
    <style>
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .tab-buttons { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e9ecef; }
        .tab-button { padding: 0.75rem 1.5rem; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-size: 1rem; font-weight: 500; color: #6c757d; transition: all 0.2s; }
        .tab-button:hover { color: #495057; }
        .tab-button.active { color: #667eea; border-bottom-color: #667eea; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 2px solid #e9ecef; border-radius: 6px; font-size: 1rem; }
        .form-group textarea { resize: vertical; min-height: 150px; font-family: 'Courier New', monospace; }
        .form-group .help-text { font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.875rem; }
        .template-card { background: white; border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .template-card.global { border-left: 4px solid #28a745; }
        .template-card.alliance { border-left: 4px solid #667eea; }
        .template-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .template-content { padding: 1rem; background: #f8f9fa; border-radius: 6px; margin-bottom: 1rem; font-family: 'Courier New', monospace; white-space: pre-wrap; }
        .template-variables { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
        .variable-tag { background: #e9ecef; color: #495057; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-family: 'Courier New', monospace; }
        .scope-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .scope-badge.global { background: #d4edda; color: #155724; }
        .scope-badge.alliance { background: #d1ecf1; color: #0c5460; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .variables-picker { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; }
        .variables-picker h4 { margin-top: 0; margin-bottom: 0.75rem; font-size: 1rem; }
        .variable-group { margin-bottom: 1rem; }
        .variable-group-title { font-weight: 600; font-size: 0.875rem; color: #495057; margin-bottom: 0.5rem; }
        .variable-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .variable-btn { background: white; border: 1px solid #667eea; color: #667eea; padding: 0.5rem 0.75rem; border-radius: 4px; font-size: 0.875rem; cursor: pointer; font-family: 'Courier New', monospace; transition: all 0.2s; }
        .variable-btn:hover { background: #667eea; color: white; }
        .variable-btn:active { transform: scale(0.95); }
        .submission-card { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
    </style>

    <div class="tab-buttons">
        <button class="tab-button active" onclick="switchTab('create')">➕ Create Template</button>
        <button class="tab-button" onclick="switchTab('manage')">📋 My Templates</button>
        <?php if ($user->aud === 'admin'): ?>
        <button class="tab-button" onclick="switchTab('submissions')">🔍 Pending Approvals</button>
        <?php endif; ?>
    </div>

    <!-- Create Tab -->
    <div id="createTab" class="tab-content active">
        <div id="createAlert"></div>

        <form id="templateForm">
            <div class="form-group">
                <label for="templateName">Template Name *</label>
                <input type="text" id="templateName" required placeholder="e.g., Daily Event Reminder">
                <div class="help-text">Give your template a descriptive name</div>
            </div>

            <div class="form-group">
                <label for="templateContent">Template Content *</label>
                <textarea id="templateContent" required placeholder="Enter your message template here...

Use variables like {r5_name}, {event_time}, etc. Click variables below to insert them."></textarea>
                <div class="help-text">Maximum 2000 characters. Use variables for dynamic content.</div>
            </div>

            <!-- Variables Picker -->
            <div class="variables-picker">
                <h4>📌 Available Variables (Click to Insert)</h4>
                <div id="variablesPicker">Loading variables...</div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="submitForGlobal">
                    Submit for Global Approval
                </label>
                <div class="help-text">
                    <?php if ($user->aud === 'admin'): ?>
                    As admin, your templates will be global immediately.
                    <?php else: ?>
                    Submit this template to admins for approval as a global template (available to all alliances).
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Create Template</button>
        </form>
    </div>

    <!-- Manage Tab -->
    <div id="manageTab" class="tab-content">
        <div id="manageAlert"></div>
        <div id="loading">Loading templates...</div>
        <div id="templateList" style="display: none;"></div>
    </div>

    <!-- Submissions Tab (Admin Only) -->
    <?php if ($user->aud === 'admin'): ?>
    <div id="submissionsTab" class="tab-content">
        <div id="submissionsAlert"></div>
        <div id="submissionsLoading">Loading pending submissions...</div>
        <div id="submissionsList" style="display: none;"></div>
    </div>
    <?php endif; ?>
</div>

<script>
    let templates = [], variables = {}, userAlliance = null;

    function switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        if (tab === 'create') {
            document.querySelector('.tab-button:nth-child(1)').classList.add('active');
            document.getElementById('createTab').classList.add('active');
        } else if (tab === 'manage') {
            document.querySelector('.tab-button:nth-child(2)').classList.add('active');
            document.getElementById('manageTab').classList.add('active');
            loadTemplates();
        } else if (tab === 'submissions') {
            document.querySelector('.tab-button:nth-child(3)').classList.add('active');
            document.getElementById('submissionsTab').classList.add('active');
            loadSubmissions();
        }
    }

    // Insert variable into template content at cursor position
    function insertVariable(variable) {
        const textarea = document.getElementById('templateContent');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + variable + text.substring(end);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
    }

    // Load variables
    async function loadVariables() {
        try {
            const response = await fetch('discord_templates_api.php?action=get_variables');
            const data = await response.json();

            if (data.success) {
                variables = data.variables;
                renderVariablesPicker();
            }
        } catch (error) {
            console.error('Error loading variables:', error);
        }
    }

    // Render variables picker
    function renderVariablesPicker() {
        const picker = document.getElementById('variablesPicker');
        let html = '';

        for (const [category, group] of Object.entries(variables)) {
            html += `
                <div class="variable-group">
                    <div class="variable-group-title">${group.label}</div>
                    <div class="variable-list">
                        ${group.variables.map(v => `
                            <button type="button" class="variable-btn"
                                    onclick="insertVariable('${v.key}')"
                                    title="${v.description}">
                                ${v.key}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        picker.innerHTML = html;
    }

    // Load templates
    async function loadTemplates() {
        const loading = document.getElementById('loading');
        const templateList = document.getElementById('templateList');

        loading.style.display = 'block';
        templateList.style.display = 'none';

        try {
            const response = await fetch('discord_templates_api.php?action=list', {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            loading.style.display = 'none';

            if (data.success) {
                templates = data.templates;
                userAlliance = data.user_alliance;
                renderTemplates();
                templateList.style.display = 'block';
            } else {
                showAlert('manageAlert', 'error', 'Failed to load templates: ' + data.error);
            }
        } catch (error) {
            loading.style.display = 'none';
            showAlert('manageAlert', 'error', 'Error loading templates: ' + error.message);
        }
    }

    // Render templates
    function renderTemplates() {
        const templateList = document.getElementById('templateList');

        if (templates.length === 0) {
            templateList.innerHTML = `
                <div class="empty-state">
                    <p>No templates yet. Create one to get started!</p>
                </div>
            `;
            return;
        }

        templateList.innerHTML = templates.map(template => {
            const scopeClass = template.scope === 'global' ? 'global' : 'alliance';
            const scopeBadge = template.scope === 'global' ?
                '<span class="scope-badge global">🌍 Global</span>' :
                `<span class="scope-badge alliance">🏢 ${template.alliance || 'Alliance'}</span>`;

            const canDelete = template.created_by === '<?php echo $user->sub; ?>' || '<?php echo $user->aud; ?>' === 'admin';

            return `
                <div class="template-card ${scopeClass}">
                    <div class="template-header">
                        <div>
                            <strong>${escapeHtml(template.name)}</strong>
                            ${scopeBadge}
                        </div>
                        <div>
                            ${canDelete ? `<button class="btn btn-danger btn-small" onclick="deleteTemplate('${template.id}')">🗑️ Delete</button>` : ''}
                        </div>
                    </div>

                    <div class="template-content">${escapeHtml(template.content)}</div>

                    ${template.variables_used && template.variables_used.length > 0 ? `
                        <div class="template-variables">
                            <strong style="font-size: 0.875rem; margin-right: 0.5rem;">Variables:</strong>
                            ${template.variables_used.map(v => `<span class="variable-tag">${v}</span>`).join('')}
                        </div>
                    ` : ''}

                    <div style="font-size: 0.85rem; color: #6c757d;">
                        <div><strong>Created:</strong> ${formatDateTime(template.created_at)} by ${template.created_by}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Create template
    document.getElementById('templateForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('templateName').value;
        const content = document.getElementById('templateContent').value;
        const submitForGlobal = document.getElementById('submitForGlobal').checked;

        const payload = {
            name: name,
            content: content,
            scope: submitForGlobal ? 'global' : 'alliance',
            submit_for_global: submitForGlobal
        };

        try {
            const response = await fetch('discord_templates_api.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                const message = data.message || 'Template created successfully!';
                showAlert('createAlert', 'success', message);
                document.getElementById('templateForm').reset();
            } else {
                showAlert('createAlert', 'error', data.error || 'Failed to create template');
            }
        } catch (error) {
            showAlert('createAlert', 'error', 'Error: ' + error.message);
        }
    });

    // Delete template
    async function deleteTemplate(templateId) {
        if (!confirm('Are you sure you want to delete this template? This cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('template_id', templateId);

            const response = await fetch('discord_templates_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showAlert('manageAlert', 'success', 'Template deleted successfully');
                loadTemplates();
            } else {
                showAlert('manageAlert', 'error', data.error || 'Failed to delete template');
            }
        } catch (error) {
            showAlert('manageAlert', 'error', 'Error: ' + error.message);
        }
    }

    <?php if ($user->aud === 'admin'): ?>
    // Load pending submissions
    async function loadSubmissions() {
        const loading = document.getElementById('submissionsLoading');
        const list = document.getElementById('submissionsList');

        loading.style.display = 'block';
        list.style.display = 'none';

        try {
            const response = await fetch('discord_templates_api.php?action=list_submissions');
            const data = await response.json();

            loading.style.display = 'none';

            if (data.success) {
                renderSubmissions(data.submissions);
                list.style.display = 'block';
            } else {
                showAlert('submissionsAlert', 'error', 'Failed to load submissions: ' + data.error);
            }
        } catch (error) {
            loading.style.display = 'none';
            showAlert('submissionsAlert', 'error', 'Error loading submissions: ' + error.message);
        }
    }

    // Render submissions
    function renderSubmissions(submissions) {
        const list = document.getElementById('submissionsList');

        if (submissions.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <p>No pending submissions</p>
                </div>
            `;
            return;
        }

        list.innerHTML = submissions.map(sub => `
            <div class="submission-card">
                <div class="template-header">
                    <div>
                        <strong>${escapeHtml(sub.template_name)}</strong>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-success btn-small" onclick="approveSubmission('${sub.id}')">✓ Approve</button>
                        <button class="btn btn-danger btn-small" onclick="rejectSubmission('${sub.id}')">✗ Reject</button>
                    </div>
                </div>

                <div class="template-content">${escapeHtml(sub.template_content)}</div>

                <div style="font-size: 0.85rem; color: #6c757d;">
                    <div><strong>Submitted:</strong> ${formatDateTime(sub.submitted_at)} by ${sub.submitted_by}</div>
                </div>
            </div>
        `).join('');
    }

    // Approve submission
    async function approveSubmission(submissionId) {
        if (!confirm('Approve this template as global?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'approve_submission');
            formData.append('submission_id', submissionId);

            const response = await fetch('discord_templates_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showAlert('submissionsAlert', 'success', 'Template approved and now available globally');
                loadSubmissions();
            } else {
                showAlert('submissionsAlert', 'error', data.error || 'Failed to approve template');
            }
        } catch (error) {
            showAlert('submissionsAlert', 'error', 'Error: ' + error.message);
        }
    }

    // Reject submission
    async function rejectSubmission(submissionId) {
        const reason = prompt('Reason for rejection (optional):');
        if (reason === null) return; // User cancelled

        try {
            const formData = new FormData();
            formData.append('action', 'reject_submission');
            formData.append('submission_id', submissionId);
            formData.append('reason', reason);

            const response = await fetch('discord_templates_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showAlert('submissionsAlert', 'success', 'Template submission rejected');
                loadSubmissions();
            } else {
                showAlert('submissionsAlert', 'error', data.error || 'Failed to reject template');
            }
        } catch (error) {
            showAlert('submissionsAlert', 'error', 'Error: ' + error.message);
        }
    }
    <?php endif; ?>

    // Show alert
    function showAlert(elementId, type, message) {
        const alertDiv = document.getElementById(elementId);
        alertDiv.innerHTML = `<div class="alert alert-${type === 'error' ? 'error' : 'success'}">${message}</div>`;
        setTimeout(() => { alertDiv.innerHTML = ''; }, 5000);
    }

    // Helper: Format datetime
    function formatDateTime(datetime) {
        if (!datetime) return 'Never';
        const date = new Date(datetime);
        return date.toLocaleString();
    }

    // Helper: Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize
    loadVariables();
</script>

<?php include 'includes/footer.php'; ?>
