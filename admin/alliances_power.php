<?php
/**
 * Alliance Power Editor
 *
 * Admin-only interface for bulk editing alliance power values
 * Displays all alliances in a single editable table
 *
 * @version 2.1.0
 * @date 2025-10-28
 * @changelog
 *   2.1.0 (2025-10-28) - Added datetime picker for accurate power history (Issue #32)
 *                       - HTML5 datetime-local input with "Use Current Time" button
 *                       - Timestamp selector shows when data was collected
 *                       - Enables backdating power entries from screenshots
 *                       - Sends timestamp to API for accurate CSV power-history.csv
 *   2.0.0 (2025-10-15) - Added powereditor role support
 *                       - Admins and power editors can now access page
 *                       - Only admins can delete alliances (power editors edit-only)
 *                       - Role display shows "R5/Power Editor" or "R4/Power Editor"
 *                       - Delete buttons hidden for non-admin users
 *   1.1.0 (2025-10-15) - Refactored to stage all changes locally before saving
 *   1.0.2 (2025-10-15) - Fixed delete function to update UI immediately
 *   1.0.1 (2025-10-15) - Fixed JWT token object/array access bug
 *   1.0.0 (2025-10-14) - Initial implementation
 */

// Require JWT authentication and power editor access
require_once 'jwt.php';
require_once 'includes/email_utils.php';

$user = require_jwt_session();

// Check if user has power editor access (admin or powereditor flag)
if (!is_power_editor($user)) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Set page title for header
$page_title = "Alliance Power Editor";

// Check if user can delete alliances (admin only)
$can_delete = can_delete_alliances($user);

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">⚔️ Alliance Power Editor</h1>
    <p class="page-description">Bulk edit alliance power values and manage alliance data</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        h1 {
            color: #333;
            font-size: 28px;
        }

        .user-info {
            color: #666;
            font-size: 14px;
        }
        
        .email-text {
            font-weight: normal;
        }
        
        .email-masked {
            font-family: monospace;
            color: #666;
            font-style: italic;
        }
        
        .email-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.2rem;
            border-radius: 3px;
            transition: all 0.2s ease;
            margin-left: 0.25rem;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .email-toggle-btn:hover {
            background: rgba(0,0,0,0.1);
        }
        
        .email-toggle-btn svg {
            width: 16px;
            height: 16px;
            fill: #666;
            transition: fill 0.2s ease;
        }
        
        .email-toggle-btn:hover svg {
            fill: #333;
        }

        /* Timestamp Selector */
        .timestamp-selector {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .timestamp-header {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .timestamp-header label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .timestamp-icon {
            font-size: 1.5rem;
        }

        .timestamp-help {
            font-size: 0.9rem;
            color: #6c757d;
            font-style: italic;
        }

        .timestamp-inputs {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .datetime-picker {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem 1rem;
            border: 2px solid #ced4da;
            border-radius: 8px;
            font-size: 1rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .datetime-picker:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-sm {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        .timestamp-display {
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            background: white;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #495057;
            border-left: 4px solid #667eea;
        }

        .timestamp-display span {
            font-weight: 600;
            color: #2c3e50;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .rank-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            background: #e9ecef;
            color: #495057;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .stat {
            flex: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-top: 5px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .new-alliance-row {
            background: #e7f3ff !important;
        }

        .new-alliance-row input {
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px 10px;
            }
        }
    </style>

    <div class="container">
        <div class="header">
            <div>
                <h1>Alliance Power Editor</h1>
                <p style="color: #666; margin-top: 5px;">Manage alliance tags, names, and power values</p>
            </div>
            <div class="user-info">
                Logged in as: <?php echo emailDisplay($user->sub, true); ?>
                (<?php
                    $role_display = ucfirst($user->aud);
                    if ($user->aud !== 'admin' && isset($user->powereditor) && $user->powereditor) {
                        $role_display .= '/Power Editor';
                    }
                    echo htmlspecialchars($role_display);
                ?>)
            </div>
        </div>

        <div class="alert alert-success" id="successAlert"></div>
        <div class="alert alert-error" id="errorAlert"></div>

        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total Alliances</div>
                <div class="stat-value" id="totalAlliances">-</div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Power</div>
                <div class="stat-value" id="totalPower">-</div>
            </div>
            <div class="stat">
                <div class="stat-label">Avg Power</div>
                <div class="stat-value" id="avgPower">-</div>
            </div>
        </div>

        <!-- Data Source Timestamp -->
        <div class="timestamp-selector">
            <div class="timestamp-header">
                <label for="dataTimestamp">
                    <span class="timestamp-icon">📅</span>
                    Data Source Date/Time
                </label>
                <span class="timestamp-help">When was this data collected? (for accurate power history)</span>
            </div>
            <div class="timestamp-inputs">
                <input type="datetime-local"
                       id="dataTimestamp"
                       name="dataTimestamp"
                       class="datetime-picker"
                       step="60">
                <button type="button" class="btn btn-sm btn-secondary" onclick="setCurrentTime()">
                    🕐 Use Current Time
                </button>
            </div>
            <div class="timestamp-display">
                Selected: <span id="timestampDisplay">Not set (will use current time)</span>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-success" onclick="saveAlliances()">💾 Save All Changes</button>
            <button class="btn btn-primary" onclick="addNewAlliance()">➕ Add New Alliance</button>
            <button class="btn btn-secondary" onclick="reloadAlliances()">🔄 Reload Data</button>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div id="loadingIndicator" class="loading">
            <div class="spinner"></div>
            <p>Loading alliances...</p>
        </div>

        <div class="table-container" id="tableContainer" style="display: none;">
            <table id="alliancesTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">Rank</th>
                        <th style="width: 100px;">Tag</th>
                        <th style="width: 300px;">Alliance Name</th>
                        <th style="width: 150px;">Power</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="alliancesBody">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // DateTime Picker Functions
        function setCurrentTime() {
            const now = new Date();
            // Format as YYYY-MM-DDTHH:MM for datetime-local input
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            const datetimeString = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('dataTimestamp').value = datetimeString;
            updateTimestampDisplay();
        }

        function updateTimestampDisplay() {
            const input = document.getElementById('dataTimestamp');
            const display = document.getElementById('timestampDisplay');

            if (input.value) {
                const date = new Date(input.value);
                const options = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                };
                display.textContent = date.toLocaleString('en-US', options);
            } else {
                display.textContent = 'Not set (will use current time)';
            }
        }

        function getDataTimestamp() {
            const input = document.getElementById('dataTimestamp');
            if (input.value) {
                // Convert to ISO string for API
                return new Date(input.value).toISOString();
            }
            return null; // API will use current time
        }

        // Email toggle function
        function toggleSingleEmail(button) {
            var emailSpan = button.previousElementSibling;
            var isShowingMasked = emailSpan.classList.contains('email-masked');
            
            if (isShowingMasked) {
                // Show full email
                emailSpan.textContent = emailSpan.dataset.email;
                emailSpan.classList.remove('email-masked');
                button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                button.title = 'Hide email';
            } else {
                // Show masked email
                emailSpan.textContent = emailSpan.dataset.masked;
                emailSpan.classList.add('email-masked');
                button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                button.title = 'Show email';
            }
        }
        
        const CAN_DELETE_ALLIANCES = <?= $can_delete ? 'true' : 'false' ?>;
        let alliances = [];
        let deletedIndices = []; // Track indices of deleted alliances
        let hasUnsavedChanges = false;

        // Load alliances on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAlliances();

            // Initialize datetime picker
            const datetimePicker = document.getElementById('dataTimestamp');
            datetimePicker.addEventListener('change', updateTimestampDisplay);

            // Warn before leaving with unsaved changes
            window.addEventListener('beforeunload', function(e) {
                if (hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                    return e.returnValue;
                }
            });
        });

        function loadAlliances() {
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('tableContainer').style.display = 'none';

            fetch('alliances_power_api.php?action=list')
                .then(response => {
                    // Check if response is OK
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    // Get the raw text first to debug
                    return response.text().then(text => {
                        console.log('API Response:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from API: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    alliances = data.alliances;
                    deletedIndices = []; // Reset deleted indices on fresh load
                    renderAlliances();
                    updateStats();
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                    hasUnsavedChanges = false;
                })
                .catch(error => {
                    console.error('Load error:', error);
                    showError('Failed to load alliances: ' + error.message);
                    document.getElementById('loadingIndicator').style.display = 'none';
                });
        }

        function renderAlliances() {
            const tbody = document.getElementById('alliancesBody');
            tbody.innerHTML = '';

            // Filter out deleted alliances and sort by power (descending) for rank calculation
            const active = alliances.filter(a => !deletedIndices.includes(a.index));
            const sorted = [...active].sort((a, b) => (b.power || 0) - (a.power || 0));

            sorted.forEach((alliance, rank) => {
                const row = document.createElement('tr');
                if (alliance.isNew) {
                    row.className = 'new-alliance-row';
                }

                row.innerHTML = `
                    <td><span class="rank-badge">#${rank + 1}</span></td>
                    <td>
                        <input type="text"
                               value="${escapeHtml(alliance.tag)}"
                               data-index="${alliance.index}"
                               data-field="tag"
                               onchange="markChanged()"
                               ${alliance.isNew ? '' : 'readonly style="background: #f8f9fa; cursor: not-allowed;"'}
                               placeholder="TAG">
                    </td>
                    <td>
                        <input type="text"
                               value="${escapeHtml(alliance.name)}"
                               data-index="${alliance.index}"
                               data-field="name"
                               onchange="markChanged()"
                               placeholder="Alliance Name">
                    </td>
                    <td>
                        <input type="number"
                               value="${alliance.power || 0}"
                               data-index="${alliance.index}"
                               data-field="power"
                               onchange="markChanged()"
                               min="0"
                               step="1"
                               placeholder="0">
                    </td>
                    <td>
                        ${alliance.isNew
                            ? `<button class="btn btn-danger" onclick="cancelNewAlliance(${alliance.index})">Cancel</button>`
                            : (CAN_DELETE_ALLIANCES
                                ? `<button class="btn btn-danger" onclick="deleteAlliance(${alliance.index}, '${escapeHtml(alliance.tag)}')">Delete</button>`
                                : '<span style="color: #999; font-size: 12px;">Edit only</span>')
                        }
                    </td>
                `;

                tbody.appendChild(row);
            });
        }

        function markChanged() {
            hasUnsavedChanges = true;
        }

        function updateStats() {
            // Only count non-deleted alliances
            const active = alliances.filter(a => !deletedIndices.includes(a.index));
            const total = active.length;
            const totalPower = active.reduce((sum, a) => sum + (a.power || 0), 0);
            const avgPower = total > 0 ? Math.round(totalPower / total) : 0;

            document.getElementById('totalAlliances').textContent = total;
            document.getElementById('totalPower').textContent = totalPower.toLocaleString();
            document.getElementById('avgPower').textContent = avgPower.toLocaleString();
        }

        function saveAlliances() {
            // Collect all input values
            const inputs = document.querySelectorAll('#alliancesTable input');
            const updates = [];

            inputs.forEach(input => {
                const index = parseInt(input.dataset.index);
                const field = input.dataset.field;
                const value = field === 'power' ? parseInt(input.value) || 0 : input.value.trim();

                // Find or create update object for this index
                let update = updates.find(u => u.index === index);
                if (!update) {
                    update = { index: index };
                    updates.push(update);
                }

                update[field] = value;
            });

            // Separate new alliances, updates, and deletes
            const newAlliances = updates.filter(u => {
                const alliance = alliances.find(a => a.index === u.index);
                return alliance && alliance.isNew;
            });

            const existingUpdates = updates.filter(u => {
                const alliance = alliances.find(a => a.index === u.index);
                return alliance && !alliance.isNew && !deletedIndices.includes(u.index);
            });

            // Process in order: deletes -> updates -> adds
            if (deletedIndices.length > 0) {
                saveDeletes(deletedIndices).then(() => {
                    if (existingUpdates.length > 0) {
                        saveUpdates(existingUpdates).then(() => {
                            saveNewAlliances(newAlliances);
                        });
                    } else {
                        saveNewAlliances(newAlliances);
                    }
                }).catch(error => {
                    showError('Failed to delete alliances: ' + error.message);
                });
            } else if (existingUpdates.length > 0) {
                saveUpdates(existingUpdates).then(() => {
                    saveNewAlliances(newAlliances);
                }).catch(error => {
                    showError('Failed to update alliances: ' + error.message);
                });
            } else if (newAlliances.length > 0) {
                saveNewAlliances(newAlliances);
            } else {
                showSuccess('No changes to save');
            }
        }

        function saveDeletes(indices) {
            const promises = indices.map(index => {
                return fetch('alliances_power_api.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ index: index })
                }).then(r => r.json());
            });

            return Promise.all(promises).then(results => {
                const errors = results.filter(r => r.error);
                if (errors.length > 0) {
                    throw new Error('Some deletes failed: ' + errors.map(e => e.error).join(', '));
                }
                console.log(`Deleted ${indices.length} alliance(s)`);
                return results;
            });
        }

        function saveUpdates(updates) {
            const timestamp = getDataTimestamp();
            const requestBody = { alliances: updates };
            if (timestamp) {
                requestBody.timestamp = timestamp;
            }

            return fetch('alliances_power_api.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to save updates');
                }
                console.log(`Updated ${updates.length} alliance(s)`);
                return data;
            });
        }

        function saveNewAlliances(newAlliances) {
            if (newAlliances.length === 0) {
                showSuccess('All changes saved successfully!');
                loadAlliances();
                return;
            }

            const timestamp = getDataTimestamp();

            const promises = newAlliances.map(alliance => {
                const requestBody = { ...alliance };
                if (timestamp) {
                    requestBody.timestamp = timestamp;
                }

                return fetch('alliances_power_api.php?action=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                }).then(r => r.json());
            });

            Promise.all(promises)
                .then(results => {
                    const errors = results.filter(r => r.error);
                    if (errors.length > 0) {
                        showError('Some alliances failed to add: ' + errors.map(e => e.error).join(', '));
                    } else {
                        showSuccess('All changes saved successfully!');
                    }
                    loadAlliances();
                })
                .catch(error => {
                    showError('Failed to add new alliances: ' + error.message);
                });
        }

        function addNewAlliance() {
            const newIndex = alliances.length > 0 ? Math.max(...alliances.map(a => a.index)) + 1 : 0;

            alliances.push({
                index: newIndex,
                tag: '',
                name: '',
                power: 0,
                isNew: true
            });

            renderAlliances();
            updateStats();
            markChanged();

            // Focus on the first input of the new row
            setTimeout(() => {
                const newInputs = document.querySelectorAll(`input[data-index="${newIndex}"]`);
                if (newInputs.length > 0) {
                    newInputs[0].focus();
                }
            }, 100);
        }

        function cancelNewAlliance(index) {
            alliances = alliances.filter(a => a.index !== index);
            renderAlliances();
            updateStats();
            hasUnsavedChanges = alliances.some(a => a.isNew);
        }

        function deleteAlliance(index, tag) {
            if (!confirm(`Mark alliance "${tag}" for deletion?\n\nClick "Save All Changes" to permanently delete.`)) {
                return;
            }

            // Mark as deleted locally
            deletedIndices.push(index);
            hasUnsavedChanges = true;

            // Re-render to hide the deleted row
            renderAlliances();
            updateStats();

            showSuccess(`Alliance "${tag}" marked for deletion. Click "Save All Changes" to commit.`);
        }

        function reloadAlliances() {
            if (hasUnsavedChanges) {
                if (!confirm('You have unsaved changes. Are you sure you want to reload?')) {
                    return;
                }
            }
            loadAlliances();
        }

        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 8000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</div>

<?php include 'includes/footer.php'; ?>
