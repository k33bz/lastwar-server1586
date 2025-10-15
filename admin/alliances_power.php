<?php
/**
 * Alliance Power Editor
 *
 * Admin-only interface for bulk editing alliance power values
 * Displays all alliances in a single editable table
 */

require_once 'config.php';
require_once 'jwt.php';

$user = require_jwt_session();

if ($user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alliance Power Editor - Last War 1586</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
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
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Alliance Power Editor</h1>
                <p style="color: #666; margin-top: 5px;">Manage alliance tags, names, and power values</p>
            </div>
            <div class="user-info">
                Logged in as: <strong><?php echo htmlspecialchars($user['email']); ?></strong> (Admin)
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
        let alliances = [];
        let hasUnsavedChanges = false;

        // Load alliances on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAlliances();

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
                .then(response => response.json())
                .then(data => {
                    alliances = data.alliances;
                    renderAlliances();
                    updateStats();
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                    hasUnsavedChanges = false;
                })
                .catch(error => {
                    showError('Failed to load alliances: ' + error.message);
                    document.getElementById('loadingIndicator').style.display = 'none';
                });
        }

        function renderAlliances() {
            const tbody = document.getElementById('alliancesBody');
            tbody.innerHTML = '';

            // Sort by power (descending) for rank calculation
            const sorted = [...alliances].sort((a, b) => (b.power || 0) - (a.power || 0));

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
                            : `<button class="btn btn-danger" onclick="deleteAlliance(${alliance.index}, '${escapeHtml(alliance.tag)}')">Delete</button>`
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
            const total = alliances.length;
            const totalPower = alliances.reduce((sum, a) => sum + (a.power || 0), 0);
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

            // Separate new alliances from updates
            const newAlliances = updates.filter(u => {
                const alliance = alliances.find(a => a.index === u.index);
                return alliance && alliance.isNew;
            });

            const existingUpdates = updates.filter(u => {
                const alliance = alliances.find(a => a.index === u.index);
                return alliance && !alliance.isNew;
            });

            // Save existing alliance updates
            if (existingUpdates.length > 0) {
                fetch('alliances_power_api.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alliances: existingUpdates })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Now save new alliances individually
                        saveNewAlliances(newAlliances);
                    } else {
                        showError(data.error || 'Failed to save changes');
                    }
                })
                .catch(error => {
                    showError('Failed to save changes: ' + error.message);
                });
            } else if (newAlliances.length > 0) {
                // Only new alliances to save
                saveNewAlliances(newAlliances);
            } else {
                showSuccess('No changes to save');
            }
        }

        function saveNewAlliances(newAlliances) {
            if (newAlliances.length === 0) {
                showSuccess('All changes saved successfully!');
                loadAlliances();
                return;
            }

            const promises = newAlliances.map(alliance => {
                return fetch('alliances_power_api.php?action=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(alliance)
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
            if (!confirm(`Are you sure you want to delete alliance "${tag}"?\n\nThis action cannot be undone.`)) {
                return;
            }

            fetch('alliances_power_api.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ index: index })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    loadAlliances();
                } else {
                    showError(data.error || 'Failed to delete alliance');
                }
            })
            .catch(error => {
                showError('Failed to delete alliance: ' + error.message);
            });
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
</body>
</html>
