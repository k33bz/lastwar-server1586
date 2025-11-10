<?php
/**
 * Discord Announcements - Send instant announcements
 * Version: 1.0.0 (Phase 1 - Basic instant messaging)
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'discord_webhook.php';
require_once 'audit_logger.php';

$user = require_jwt_session();

// Check if user has at least R4 access or president role
if (!has_role($user, ['admin', 'r5', 'r4', 'president'])) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Check if Discord is enabled
if (!DISCORD_ENABLED) {
    header('Location: dashboard.php?error=discord_disabled');
    exit();
}

// Log page access
log_audit_event('discord_announcements_accessed', $user->sub, [
    'user_roles' => get_user_roles($user)
]);

// Set page title for header
$page_title = "Discord Announcements";

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📢 Discord Announcements</h1>
    <p class="page-description">Send instant announcements to Discord channels</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-of-type {
            border-bottom: none;
        }

        .form-section h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .char-count {
            text-align: right;
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .char-count.warning {
            color: #f39c12;
        }

        .char-count.danger {
            color: #e74c3c;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .channels-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1rem;
            background: #f8f9fa;
        }

        .channel-item {
            padding: 0.75rem;
            background: white;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
        }

        .channel-item:last-child {
            margin-bottom: 0;
        }

        .channel-name {
            font-weight: 600;
            color: #333;
        }

        .channel-description {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
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

        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #loadingOverlay.active {
            display: flex;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .embed-options {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            display: none;
        }

        .embed-options.active {
            display: block;
        }

        .color-picker {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s;
        }

        .color-option.selected {
            border-color: #333;
            transform: scale(1.1);
        }

        .color-option:hover {
            transform: scale(1.05);
        }

        /* Error Modal Styles */
        .error-modal {
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

        .error-modal.active {
            display: flex;
        }

        .error-modal-content {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .error-modal-header {
            background: #dc3545;
            color: white;
            padding: 1.5rem;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .error-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-modal-close {
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

        .error-modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .error-modal-body {
            padding: 1.5rem;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .error-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .error-details h4 {
            margin: 0 0 0.75rem 0;
            font-size: 0.95rem;
            color: #495057;
        }

        .error-details ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .error-details li {
            margin-bottom: 0.5rem;
            color: #721c24;
        }

        .error-help {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }

        .error-help strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .error-help a {
            color: #0c5460;
            font-weight: 600;
            text-decoration: underline;
        }

        .error-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
        }

        .error-modal-footer button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .error-modal-footer button:hover {
            background: #5a6268;
        }

        /* Template & Variable Button Styles */
        .btn-variable {
            background: white;
            border: 1px solid #667eea;
            color: #667eea;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            transition: all 0.2s;
        }

        .btn-variable:hover {
            background: #667eea;
            color: white;
        }

        .btn-variable:active {
            transform: scale(0.95);
        }
    </style>

    <div id="alertContainer"></div>

    <form id="announcementForm">
        <!-- Message Content Section -->
        <div class="form-section">
            <h3>📝 Message Content</h3>

            <!-- Template Selection (First) -->
            <div class="form-group">
                <label for="templateSelect">📝 Use Template (Optional)</label>
                <select id="templateSelect" onchange="loadTemplate()">
                    <option value="">-- Select a template --</option>
                </select>
                <div class="help-text">Load a pre-made template with variables</div>
            </div>

            <!-- Custom Variable Inputs (shown when template has custom vars) -->
            <div id="customVariables" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #555;">✏️ Fill in Custom Variables</label>
                <div id="customVariableInputs"></div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="useEmbed" name="use_embed" checked>
                    <label for="useEmbed">Use rich embed format (recommended)</label>
                </div>
            </div>

            <div id="embedOptions" class="embed-options active">
                <div class="form-group">
                    <label for="embedTitle">Embed Title</label>
                    <input type="text" id="embedTitle" name="embed_title" placeholder="e.g., Important Announcement">
                </div>

                <div class="form-group">
                    <label>Embed Color</label>
                    <div class="color-picker">
                        <div class="color-option selected" data-color="3447003" style="background: #3498db;" title="Blue"></div>
                        <div class="color-option" data-color="15158332" style="background: #e74c3c;" title="Red"></div>
                        <div class="color-option" data-color="3066993" style="background: #2ecc71;" title="Green"></div>
                        <div class="color-option" data-color="15844367" style="background: #f1c40f;" title="Yellow"></div>
                        <div class="color-option" data-color="10181046" style="background: #9b59b6;" title="Purple"></div>
                        <div class="color-option" data-color="15105570" style="background: #e67e22;" title="Orange"></div>
                    </div>
                    <input type="hidden" id="embedColor" name="embed_color" value="3447003">
                </div>
            </div>

            <div class="form-group">
                <label>📌 Quick Variables</label>
                <div id="quickVariables" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <button type="button" class="btn-variable" onclick="insertVariable('{sender_name}')">sender_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{alliance_name}')">alliance_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{r5_name}')">r5_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{server_name}')">server_name</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{date}')">date</button>
                    <button type="button" class="btn-variable" onclick="insertVariable('{time}')">time</button>
                </div>
                <div class="help-text">
                    Click to insert variables.
                    <a href="discord_templates.php" target="_blank" style="color: #667eea;">View all variables & manage templates →</a>
                </div>
            </div>

            <div class="form-group">
                <label for="messageContent">Message *</label>
                <textarea id="messageContent" name="message" placeholder="Enter your announcement message here...

You can use variables like {sender_name}, {r5_name}, {alliance_name}, etc." required></textarea>
                <div class="char-count" id="charCount">0 / 2000 characters</div>
                <div class="help-text">Supports Discord markdown: **bold**, *italic*, __underline__, ~~strikethrough~~</div>
            </div>
        </div>

        <!-- Channel Selection Section -->
        <div class="form-section">
            <h3>🎯 Target Channels</h3>
            <div class="form-group">
                <label>Select channels to send announcement:</label>
                <div id="channelsList" class="channels-list">
                    <p style="text-align: center; color: #666;">Loading channels...</p>
                </div>
            </div>
        </div>

        <!-- Auto-Delete Section -->
        <div class="form-section">
            <h3>⏰ Auto-Delete</h3>
            <div class="form-group">
                <label for="deleteAfterHours">Automatically delete message after:</label>
                <select id="deleteAfterHours" name="delete_after_hours">
                    <option value="">Never (keep forever)</option>
                    <option value="1">1 hour</option>
                    <option value="6">6 hours</option>
                    <option value="12">12 hours</option>
                    <option value="24">24 hours</option>
                    <option value="48">48 hours (2 days)</option>
                </select>
                <div class="help-text">Select how long to keep the message before automatically deleting it. This helps keep channels clean.</div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                📤 Send Announcement
            </button>
        </div>
    </form>
</div>

<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="error-modal">
    <div class="error-modal-content">
        <div class="error-modal-header">
            <h3>❌ Error</h3>
            <button class="error-modal-close" onclick="closeErrorModal()">&times;</button>
        </div>
        <div class="error-modal-body" id="errorModalBody">
            <!-- Error content will be inserted here -->
        </div>
        <div class="error-modal-footer">
            <button onclick="closeErrorModal()">Close</button>
        </div>
    </div>
</div>

<script>
// Track selected channels
let selectedChannels = [];
let availableChannels = [];
let templates = [];

// Load templates
async function loadTemplates() {
    try {
        const response = await fetch('discord_templates_api.php?action=list', {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success) {
            templates = data.templates;
            populateTemplateSelect();
        }
    } catch (error) {
        console.error('Error loading templates:', error);
    }
}

// Populate template dropdown
function populateTemplateSelect() {
    const select = document.getElementById('templateSelect');
    select.innerHTML = '<option value="">-- Select a template --</option>';

    templates.forEach(template => {
        const option = document.createElement('option');
        option.value = template.id;
        option.textContent = `${template.name} (${template.scope === 'global' ? '🌍 Global' : '🏢 ' + (template.alliance || 'Alliance')})`;
        option.dataset.content = template.content;
        select.appendChild(option);
    });
}

// Load selected template
function loadTemplate() {
    const select = document.getElementById('templateSelect');
    const selectedOption = select.options[select.selectedIndex];

    if (!selectedOption.dataset.content) {
        // Clear custom variables if deselecting template
        document.getElementById('customVariables').style.display = 'none';
        return;
    }

    const templateId = selectedOption.value;
    const template = templates.find(t => t.id === templateId);

    if (!template) return;

    // Extract title from template name (remove tags like [S02])
    let title = template.name;
    // Remove [S02], [TAG], etc. from the beginning
    title = title.replace(/^\[[^\]]+\]\s*/, '');

    // Auto-fill title if using embeds
    if (document.getElementById('useEmbed').checked) {
        document.getElementById('embedTitle').value = title;
    }

    // Load message content
    document.getElementById('messageContent').value = template.content;
    document.getElementById('messageContent').dispatchEvent(new Event('input'));

    // Detect custom variables in template
    const customVars = detectCustomVariables(template.content);

    if (customVars.length > 0) {
        showCustomVariableInputs(customVars);
    } else {
        document.getElementById('customVariables').style.display = 'none';
    }
}

// Detect custom variables (ones that aren't auto-populated)
function detectCustomVariables(content) {
    const allVars = content.match(/\{([^}]+)\}/g) || [];

    // Variables that are automatically replaced
    const autoVars = [
        '{server_name}', '{server_reset_time}',
        '{sender_name}', '{sender_alliance}', '{sender_tag}',
        '{alliance_name}', '{alliance_tag}', '{r5_name}',
        '{date}', '{time}', '{datetime}'
    ];

    // Filter to only custom variables
    const customVars = [...new Set(allVars)].filter(v => !autoVars.includes(v));

    return customVars;
}

// Show input fields for custom variables
function showCustomVariableInputs(variables) {
    const container = document.getElementById('customVariableInputs');
    const customVarsDiv = document.getElementById('customVariables');

    container.innerHTML = '';

    variables.forEach(varName => {
        const cleanName = varName.replace(/[{}]/g, '');
        const label = cleanName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        const inputGroup = document.createElement('div');
        inputGroup.style.marginBottom = '0.75rem';

        // Special handling for specific variables
        if (cleanName === 'event_time' || cleanName === 'time') {
            // Date-time picker with "Use Now" option
            inputGroup.innerHTML = `
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; color: #555;">
                    ${label}:
                </label>
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                    <input type="checkbox" id="var_${cleanName}_now" onchange="toggleTimeNow('${cleanName}')">
                    <label for="var_${cleanName}_now" style="margin: 0; font-weight: normal;">Use Now</label>
                </div>
                <input type="datetime-local"
                       id="var_${cleanName}"
                       data-variable="${varName}"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
            `;
        } else if (cleanName === 'location') {
            // Location name + X/Y coordinate inputs (all optional)
            inputGroup.innerHTML = `
                <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; color: #555;">
                    ${label} (optional):
                </label>
                <div style="margin-bottom: 0.5rem;">
                    <label for="var_${cleanName}_name" style="font-size: 0.85rem; color: #666;">Location Name:</label>
                    <input type="text"
                           id="var_${cleanName}_name"
                           data-variable="${varName}"
                           data-location="name"
                           placeholder="e.g., Enemy Base, Resource Point"
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <label for="var_${cleanName}_x" style="font-size: 0.85rem; color: #666;">X Coordinate:</label>
                        <input type="number"
                               id="var_${cleanName}_x"
                               data-variable="${varName}"
                               data-location="x"
                               placeholder="123"
                               min="0"
                               max="999"
                               style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label for="var_${cleanName}_y" style="font-size: 0.85rem; color: #666;">Y Coordinate:</label>
                        <input type="number"
                               id="var_${cleanName}_y"
                               data-variable="${varName}"
                               data-location="y"
                               placeholder="456"
                               min="0"
                               max="999"
                               style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
                    </div>
                </div>
                <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                    All fields optional. Will format as: "Name x:### y:###"
                </div>
            `;
        } else if (cleanName === 'notes') {
            // Textarea for notes
            inputGroup.innerHTML = `
                <label for="var_${cleanName}" style="display: block; margin-bottom: 0.25rem; font-weight: 500; color: #555;">
                    ${label} (optional):
                </label>
                <textarea id="var_${cleanName}"
                          data-variable="${varName}"
                          placeholder="Enter additional notes (leave blank to omit)"
                          rows="3"
                          style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem; resize: vertical;"></textarea>
                <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                    If left blank, the notes section will be removed from the message
                </div>
            `;
        } else {
            // Standard text input
            inputGroup.innerHTML = `
                <label for="var_${cleanName}" style="display: block; margin-bottom: 0.25rem; font-weight: 500; color: #555;">
                    ${label}:
                </label>
                <input type="text"
                       id="var_${cleanName}"
                       data-variable="${varName}"
                       placeholder="Enter ${label.toLowerCase()}"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem;">
            `;
        }

        container.appendChild(inputGroup);
    });

    customVarsDiv.style.display = 'block';
}

// Toggle "Use Now" for time fields
function toggleTimeNow(cleanName) {
    const checkbox = document.getElementById(`var_${cleanName}_now`);
    const input = document.getElementById(`var_${cleanName}`);

    if (checkbox.checked) {
        // Set to current date/time
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        input.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        input.disabled = true;
    } else {
        input.disabled = false;
    }
}

// Replace custom variables in message before sending
function replaceCustomVariables(message) {
    let processedMessage = message;
    const processedVars = new Set();

    // Handle location (name + coordinates, all optional)
    const locationNameInput = document.getElementById('var_location_name');
    const locationXInput = document.getElementById('var_location_x');
    const locationYInput = document.getElementById('var_location_y');

    if (locationNameInput || locationXInput || locationYInput) {
        const name = locationNameInput ? locationNameInput.value.trim() : '';
        const x = locationXInput ? locationXInput.value.trim() : '';
        const y = locationYInput ? locationYInput.value.trim() : '';

        // Build location string from available parts
        let locationParts = [];
        if (name) locationParts.push(name);
        if (x && y) {
            locationParts.push(`x:${x} y:${y}`);
        } else if (x) {
            locationParts.push(`x:${x}`);
        } else if (y) {
            locationParts.push(`y:${y}`);
        }

        if (locationParts.length > 0) {
            processedMessage = processedMessage.replace(/\{location\}/g, locationParts.join(' '));
            processedVars.add('{location}');
        } else {
            // If all location fields are blank, remove the line
            const lines = processedMessage.split('\n');
            processedMessage = lines.filter(line => !line.includes('{location}')).join('\n');
            processedVars.add('{location}');
        }
    }

    // Handle datetime inputs (format nicely)
    const datetimeInputs = document.querySelectorAll('#customVariableInputs input[type="datetime-local"]');
    datetimeInputs.forEach(input => {
        const varName = input.dataset.variable;
        const value = input.value;

        if (varName && value) {
            // Format datetime as "YYYY-MM-DD HH:MM"
            const date = new Date(value);
            const formatted = date.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).replace(',', '');

            processedMessage = processedMessage.replace(new RegExp(varName.replace(/[{}]/g, '\\$&'), 'g'), formatted);
            processedVars.add(varName);
        }
    });

    // Handle textarea inputs (notes)
    const textareas = document.querySelectorAll('#customVariableInputs textarea');
    textareas.forEach(textarea => {
        const varName = textarea.dataset.variable;
        const value = textarea.value.trim();

        if (varName) {
            if (value) {
                // Replace with value
                processedMessage = processedMessage.replace(new RegExp(varName.replace(/[{}]/g, '\\$&'), 'g'), value);
            } else {
                // Remove entire line containing the variable if it's blank
                const lines = processedMessage.split('\n');
                processedMessage = lines.filter(line => !line.includes(varName)).join('\n');
            }
            processedVars.add(varName);
        }
    });

    // Handle regular text inputs (excluding location coords)
    const textInputs = document.querySelectorAll('#customVariableInputs input[type="text"], #customVariableInputs input[type="number"]:not([data-coord])');
    textInputs.forEach(input => {
        const varName = input.dataset.variable;
        const value = input.value.trim();

        if (varName && value && !processedVars.has(varName)) {
            processedMessage = processedMessage.replace(new RegExp(varName.replace(/[{}]/g, '\\$&'), 'g'), value);
            processedVars.add(varName);
        }
    });

    return processedMessage;
}

// Insert variable into message at cursor position
function insertVariable(variable) {
    const textarea = document.getElementById('messageContent');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    textarea.value = text.substring(0, start) + variable + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + variable.length;

    // Trigger character count update
    textarea.dispatchEvent(new Event('input'));
}

// Load available channels
async function loadChannels() {
    try {
        const response = await fetch('discord_api.php?action=get_channels', {
            credentials: 'include'
        });
        const data = await response.json();

        if (data.success) {
            availableChannels = data.channels;
            renderChannels();
        } else {
            showErrorModal('Failed to load channels: ' + data.error);
        }
    } catch (error) {
        showErrorModal('Error loading channels: ' + error.message);
    }
}

// Render channels list
function renderChannels() {
    const container = document.getElementById('channelsList');

    if (availableChannels.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666;">No channels configured yet. Please contact an administrator.</p>';
        return;
    }

    container.innerHTML = availableChannels.map(channel => `
        <div class="channel-item">
            <div class="checkbox-group">
                <input type="checkbox" id="channel_${channel.id}" value="${channel.id}" onchange="toggleChannel('${channel.id}')">
                <label for="channel_${channel.id}">
                    <span class="channel-name">#${channel.display_name || channel.name}</span>
                    ${channel.server_name ? `<span style="color: #999;"> (${channel.server_name})</span>` : ''}
                </label>
            </div>
            ${channel.description ? `<div class="channel-description">${channel.description}</div>` : ''}
        </div>
    `).join('');
}

// Toggle channel selection
function toggleChannel(channelId) {
    const checkbox = document.getElementById('channel_' + channelId);
    if (checkbox.checked) {
        selectedChannels.push(channelId);
    } else {
        selectedChannels = selectedChannels.filter(id => id !== channelId);
    }
}

// Character count
document.getElementById('messageContent').addEventListener('input', function() {
    const length = this.value.length;
    const counter = document.getElementById('charCount');
    counter.textContent = length + ' / 2000 characters';

    if (length > 2000) {
        counter.classList.add('danger');
        counter.classList.remove('warning');
    } else if (length > 1800) {
        counter.classList.add('warning');
        counter.classList.remove('danger');
    } else {
        counter.classList.remove('warning', 'danger');
    }
});

// Toggle embed options
document.getElementById('useEmbed').addEventListener('change', function() {
    const embedOptions = document.getElementById('embedOptions');
    if (this.checked) {
        embedOptions.classList.add('active');
    } else {
        embedOptions.classList.remove('active');
    }
});

// Color picker
document.querySelectorAll('.color-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('embedColor').value = this.dataset.color;
    });
});

// Form submission
document.getElementById('announcementForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validate
    if (selectedChannels.length === 0) {
        showErrorModal('Please select at least one channel');
        return;
    }

    let message = document.getElementById('messageContent').value.trim();
    if (!message) {
        showErrorModal('Please enter a message');
        return;
    }

    // Replace custom variables before validation and sending
    message = replaceCustomVariables(message);

    if (message.length > 2000) {
        showErrorModal('Message exceeds 2000 character limit');
        return;
    }

    // Show loading
    document.getElementById('loadingOverlay').classList.add('active');
    document.getElementById('submitBtn').disabled = true;

    try {
        // Get CSRF token
        const csrfToken = getCsrfToken();

        // Also replace custom variables in title if present
        let embedTitle = document.getElementById('embedTitle').value || '';
        embedTitle = replaceCustomVariables(embedTitle);

        const formData = new FormData();
        formData.append('action', 'send_instant');
        formData.append('channel_ids', JSON.stringify(selectedChannels));
        formData.append('message', message);
        formData.append('use_embed', document.getElementById('useEmbed').checked ? 'true' : 'false');
        formData.append('embed_title', embedTitle);
        formData.append('embed_color', document.getElementById('embedColor').value);

        // Add auto-delete setting
        const deleteAfterHours = document.getElementById('deleteAfterHours').value;
        if (deleteAfterHours) {
            formData.append('delete_after_hours', deleteAfterHours);
        }

        const response = await fetch('discord_api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });

        // Check if response is OK before parsing
        if (!response.ok) {
            const text = await response.text();
            console.error('Server error response:', text);

            try {
                const errorData = JSON.parse(text);
                throw new Error(errorData.error || `Server error (${response.status})`);
            } catch (parseError) {
                throw new Error(`Server error (${response.status}): ${text || 'No response body'}`);
            }
        }

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            resetForm();

            // If some channels failed, show warning modal
            if (data.failed_channels && data.failed_channels.length > 0) {
                showErrorModal('Some channels failed to receive the message', data);
            }
        } else {
            // Show detailed error modal
            showErrorModal(data.error || 'Failed to send message', data);
        }
    } catch (error) {
        showErrorModal('Error sending message: ' + error.message);
    } finally {
        document.getElementById('loadingOverlay').classList.remove('active');
        document.getElementById('submitBtn').disabled = false;
    }
});

// Reset form
function resetForm() {
    document.getElementById('announcementForm').reset();
    selectedChannels = [];
    document.querySelectorAll('.channels-list input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('embedOptions').classList.remove('active');
    document.getElementById('charCount').textContent = '0 / 2000 characters';
    document.getElementById('charCount').classList.remove('warning', 'danger');
}

// Show alert (for success messages)
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

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show error modal
function showErrorModal(error, details = null) {
    const modal = document.getElementById('errorModal');
    const body = document.getElementById('errorModalBody');

    let html = '<div class="error-message">' + escapeHtml(error) + '</div>';

    // If we have detailed error information
    if (details) {
        if (details.failed_channels && details.failed_channels.length > 0) {
            html += '<div class="error-details">';
            html += '<h4>Failed Channels:</h4>';
            html += '<ul>';

            details.failed_channels.forEach(channelId => {
                const channelName = getChannelName(channelId);
                const errorMsg = details.error_messages[channelId] || 'Unknown error';
                html += '<li><strong>' + escapeHtml(channelName) + ':</strong> ' + escapeHtml(errorMsg) + '</li>';
            });

            html += '</ul>';
            html += '</div>';

            // Check if any error mentions bot permissions or access
            const hasPermissionError = details.failed_channels.some(channelId => {
                const msg = details.error_messages[channelId] || '';
                return msg.includes('permission') || msg.includes('403') || msg.includes('404') || msg.includes('not found') || msg.includes('not in the server');
            });

            if (hasPermissionError) {
                html += '<div class="error-help">';
                html += '<strong>Need Help?</strong>';
                html += 'If you\'re seeing permission errors, the bot may need to be invited to your Discord server or given the correct permissions. ';
                html += '<a href="discord_config.php" target="_blank">Visit Discord Configuration</a> for the bot invite link and setup instructions.';
                html += '</div>';
            }
        }
    }

    body.innerHTML = html;
    modal.classList.add('active');
}

// Close error modal
function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    modal.classList.remove('active');
}

// Helper function to get channel name by ID
function getChannelName(channelId) {
    const channel = availableChannels.find(ch => ch.id === channelId);
    return channel ? channel.name : channelId;
}

// Close modal on outside click
document.addEventListener('click', function(event) {
    const modal = document.getElementById('errorModal');
    if (event.target === modal) {
        closeErrorModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeErrorModal();
    }
});

// Initialize
loadChannels();
loadTemplates();
</script>

<?php include 'includes/footer.php'; ?>
