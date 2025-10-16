<?php
/**
 * User List and Management
 * Version: 1.0.0
 * Display all users with edit/delete options
 */

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Check if user has admin access
if ($user->aud !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Set page title for header
$page_title = "User Management";

// Include shared header
include 'includes/header.php';

// Include email utilities
require_once 'includes/email_utils.php';

// Load users
$users = [];
$users_file = __DIR__ . '/users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    $users = $users_data['users'] ?? [];
}

// Load alliances for reference
$alliances = [];
$alliances_file = __DIR__ . '/../data/alliances.json';
if (file_exists($alliances_file)) {
    $alliances_data = json_decode(file_get_contents($alliances_file), true);
    $alliances = $alliances_data ?? [];
}

// Function to check if user has active JWT token
function isUserTokenActive($email) {
    // Use the JWT system's built-in active session tracking
    $active_sessions = get_active_sessions($email);
    return !empty($active_sessions);
}




?>

<div class="page-header">
    <h1 class="page-title">👥 User Management</h1>
    <p class="page-description">Manage user accounts, roles, and alliance access</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .actions {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .search-filters {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .search-bar {
            margin-bottom: 1rem;
        }
        
        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            background: white;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        
        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            font-size: 0.9rem;
            min-width: 120px;
        }
        
        .filters select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters select {
                min-width: auto;
            }
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .action-buttons .btn {
            margin: 0;
            white-space: nowrap;
            font-size: 0.9rem;
            padding: 0.75rem 1.5rem;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .users-table th.sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: background-color 0.2s;
        }
        
        .users-table th.sortable:hover {
            background: #e9ecef;
        }
        
        .sort-indicator {
            margin-left: 0.5rem;
            opacity: 0.5;
        }
        
        .sort-indicator:after {
            content: "↕️";
        }
        
        .sort-indicator.asc:after {
            content: "↑";
        }
        
        .sort-indicator.desc:after {
            content: "↓";
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-r5 {
            background: #28a745;
            color: white;
        }
        
        .role-r4 {
            background: #007bff;
            color: white;
        }
        
        .ape-badge {
            background: #ffc107;
            color: #212529;
            padding: 0.15rem 0.4rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 0.25rem;
            text-transform: uppercase;
        }
        
        .alliance-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        
        .alliance-tag {
            background: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            color: #495057;
        }
        
        .alliance-all {
            background: #ffc107;
            color: #212529;
            font-weight: 600;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-dot.active {
            background: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
        }
        
        .status-dot.inactive {
            background: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
        }
        
        .status-text {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .email-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .email-text, .email-masked {
            font-family: inherit;
            font-weight: 500;
            font-style: normal;
            font-size: inherit;
            line-height: inherit;
            letter-spacing: inherit;
        }
        
        .email-text {
            color: #333;
        }
        
        .email-masked {
            color: #666;
        }
        
        .email-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
            margin-left: 4px;
            border-radius: 3px;
            transition: all 0.2s ease;
            opacity: 0.6;
        }
        
        .email-toggle-btn:hover {
            background: #f0f0f0;
            opacity: 1;
        }
        
        .email-toggle-btn svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
    </style>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($users); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?></div>
            <div class="stat-label">Admins</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['role'] === 'r5'; })); ?></div>
            <div class="stat-label">R5 Leaders</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['role'] === 'r4'; })); ?></div>
            <div class="stat-label">R4 Officers</div>
        </div>
    </div>

    <!-- Actions -->
    <div class="actions">
        <button class="btn btn-primary" onclick="openAddModal()">➕ Add New User</button>
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Search and Filters -->
    <div class="search-filters">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search by email..." onkeyup="filterUsers()">
        </div>
        <div class="filters">
            <select id="roleFilter" onchange="filterUsers()">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="r5">R5 Leaders</option>
                <option value="r4">R4 Officers</option>
                <option value="ape">Power Editors (APE)</option>
            </select>
            <select id="statusFilter" onchange="filterUsers()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select id="allianceFilter" onchange="filterUsers()">
                <option value="">All Alliances</option>
                <option value="*">All Alliances (*)</option>
                <?php foreach ($alliances as $alliance): ?>
                    <option value="<?php echo htmlspecialchars($alliance['tag']); ?>"><?php echo htmlspecialchars($alliance['tag']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-secondary" onclick="clearFilters()">Clear Filters</button>
            <button class="btn btn-secondary" onclick="resetSort()">Reset Sort</button>
        </div>
    </div>

    <!-- Users Table -->
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <h3>No Users Found</h3>
            <p>No user accounts have been created yet.</p>
            <button class="btn btn-primary" onclick="openAddModal()">Add First User</button>
        </div>
    <?php else: ?>
        <table class="users-table">
            <thead>
                <tr>
                    <th class="sortable" onclick="sortTable(0)">Email <span class="sort-indicator"></span></th>
                    <th class="sortable" onclick="sortTable(1)">Role <span class="sort-indicator"></span></th>
                    <th class="sortable" onclick="sortTable(2)">Alliances <span class="sort-indicator"></span></th>
                    <th class="sortable" onclick="sortTable(3)">Status <span class="sort-indicator"></span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?php echo emailDisplay($user['email'], true); ?>
                        </td>
                        <td>
                            <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                                <?php if (($user['role'] === 'r4' || $user['role'] === 'r5') && isset($user['powereditor']) && $user['powereditor']): ?>
                                    <span class="ape-badge">+APE</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <div class="alliance-tags">
                                <?php if (in_array('*', $user['alliances'])): ?>
                                    <span class="alliance-tag alliance-all">ALL</span>
                                <?php else: ?>
                                    <?php foreach ($user['alliances'] as $alliance_tag): ?>
                                        <span class="alliance-tag"><?php echo htmlspecialchars($alliance_tag); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php $isActive = isUserTokenActive($user['email']); ?>
                            <div class="status-indicator">
                                <span class="status-dot <?php echo $isActive ? 'active' : 'inactive'; ?>"></span>
                                <span class="status-text"><?php echo $isActive ? 'Active' : 'Inactive'; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-secondary" onclick="openEditModal('<?php echo htmlspecialchars($user['email']); ?>')">Edit</button>
                                <button class="btn btn-primary" onclick="generateMagicLink('<?php echo htmlspecialchars($user['email']); ?>')" title="Generate magic link">🔗 Magic Link</button>
                                <button class="btn btn-danger" onclick="deleteUser('<?php echo htmlspecialchars($user['email']); ?>')" title="Delete user">🗑️ Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editUserForm">
                <input type="hidden" id="editEmail" name="email">
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" id="displayEmail" readonly class="readonly-input">
                </div>
                
                <div class="form-group">
                    <label>Role:</label>
                    <select id="editRole" name="role" onchange="updatePowerEditorVisibility()">
                        <option value="r4">R4 (Can edit all alliance data)</option>
                        <option value="r5">R5 (Can edit + sign rules)</option>
                        <option value="admin">Admin (Full access)</option>
                    </select>
                </div>
                
                <div class="form-group" id="powerEditorGroup">
                    <label class="checkbox-label">
                        <input type="checkbox" id="editPowerEditor" name="powereditor" value="1">
                        <strong>Power Editor</strong> - Can edit all alliance power values
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Alliances:</label>
                    <div class="checkbox-group">
                        <div class="checkbox-all">
                            <label class="checkbox-label">
                                <input type="checkbox" id="editAllianceAll" onchange="toggleAllAlliances(this)">
                                <strong>All Alliances (*)</strong>
                            </label>
                        </div>
                        <div id="allianceCheckboxes">
                            <?php foreach ($alliances as $alliance): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="alliances[]" value="<?php echo htmlspecialchars($alliance['tag']); ?>" data-alliance="<?php echo htmlspecialchars($alliance['tag']); ?>">
                                    <?php echo htmlspecialchars($alliance['tag']); ?> - <?php echo htmlspecialchars($alliance['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="saveUser()">Save Changes</button>
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New User</h2>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addUserForm">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="addEmail" name="email" required placeholder="user@example.com">
                </div>
                
                <div class="form-group">
                    <label>Role:</label>
                    <select id="addRole" name="role" onchange="updateAddPowerEditorVisibility()">
                        <option value="r4">R4 (Can edit all alliance data)</option>
                        <option value="r5">R5 (Can edit + sign rules)</option>
                        <option value="admin">Admin (Full access)</option>
                    </select>
                </div>
                
                <div class="form-group" id="addPowerEditorGroup">
                    <label class="checkbox-label">
                        <input type="checkbox" id="addPowerEditor" name="powereditor" value="1">
                        <strong>Power Editor</strong> - Can edit all alliance power values
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Alliances:</label>
                    <div class="checkbox-group">
                        <div class="checkbox-all">
                            <label class="checkbox-label">
                                <input type="checkbox" id="addAllianceAll" onchange="toggleAddAllAlliances(this)">
                                <strong>All Alliances (*)</strong>
                            </label>
                        </div>
                        <div id="addAllianceCheckboxes">
                            <?php foreach ($alliances as $alliance): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="add_alliances[]" value="<?php echo htmlspecialchars($alliance['tag']); ?>" data-alliance="<?php echo htmlspecialchars($alliance['tag']); ?>">
                                    <?php echo htmlspecialchars($alliance['tag']); ?> - <?php echo htmlspecialchars($alliance['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="addUser()">Add User</button>
            <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none !important;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.show {
    display: block !important;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    transition: color 0.2s;
}

.close:hover {
    color: #333;
}

.modal-body {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-group input[type="text"], 
.form-group input[type="email"],
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.readonly-input {
    background-color: #f8f9fa;
    color: #666;
}

.checkbox-group {
    border: 1px solid #ddd;
    padding: 1rem;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    background: #f8f9fa;
}

.checkbox-all {
    background: #e9ecef;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    border-radius: 4px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    margin: 0.5rem 0;
    cursor: pointer;
}

.checkbox-label input {
    margin-right: 0.5rem;
    width: auto;
}

.magic-link-container {
    margin: 1rem 0;
}

.link-display {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.link-display input {
    flex: 1;
    font-family: monospace;
    font-size: 0.85rem;
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 0.5rem;
    border-radius: 4px;
}

.magic-link-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 1rem;
    margin-top: 1rem;
}

.magic-link-warning p {
    margin: 0 0 0.5rem 0;
    color: #856404;
    font-weight: 600;
}

.magic-link-warning ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #856404;
}

.magic-link-warning li {
    margin-bottom: 0.25rem;
}

.results-count {
    margin-top: 1rem;
    padding: 0.5rem 0.75rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #666;
    text-align: center;
}
</style>

<script>
// Store users data for JavaScript access
const usersData = <?php echo json_encode($users); ?>;

function openEditModal(email) {
    const user = usersData.find(u => u.email === email);
    if (!user) return;
    
    // Populate form fields
    document.getElementById('editEmail').value = user.email;
    document.getElementById('displayEmail').value = user.email;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editPowerEditor').checked = user.powereditor || false;
    
    // Handle alliances
    const hasAllAlliances = user.alliances.includes('*');
    document.getElementById('editAllianceAll').checked = hasAllAlliances;
    
    // Set individual alliance checkboxes
    const allianceCheckboxes = document.querySelectorAll('input[name="alliances[]"]');
    allianceCheckboxes.forEach(cb => {
        cb.checked = user.alliances.includes(cb.value);
        cb.disabled = hasAllAlliances;
    });
    
    updatePowerEditorVisibility();
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function toggleAllAlliances(checkbox) {
    const allianceCheckboxes = document.querySelectorAll('input[name="alliances[]"]');
    allianceCheckboxes.forEach(cb => {
        cb.disabled = checkbox.checked;
        if (checkbox.checked) {
            cb.checked = false;
        }
    });
}

function updatePowerEditorVisibility() {
    const roleSelect = document.getElementById('editRole');
    const powerEditorGroup = document.getElementById('powerEditorGroup');
    const powerEditorCheckbox = document.getElementById('editPowerEditor');
    
    if (roleSelect.value === 'admin') {
        powerEditorGroup.style.display = 'none';
        powerEditorCheckbox.checked = false;
    } else {
        powerEditorGroup.style.display = 'block';
    }
}

function saveUser() {
    const formData = new FormData();
    const email = document.getElementById('editEmail').value;
    const role = document.getElementById('editRole').value;
    const powerEditor = document.getElementById('editPowerEditor').checked;
    
    formData.append('action', 'update');
    formData.append('email', email);
    formData.append('role', role);
    formData.append('powereditor', powerEditor ? '1' : '0');
    
    // Get selected alliances
    const alliances = [];
    if (document.getElementById('editAllianceAll').checked) {
        alliances.push('*');
    } else {
        const allianceCheckboxes = document.querySelectorAll('input[name="alliances[]"]:checked');
        allianceCheckboxes.forEach(cb => alliances.push(cb.value));
    }
    
    alliances.forEach(alliance => formData.append('alliances[]', alliance));
    
    fetch('user_management_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating user: ' + error);
    });
}

function deleteUser(email) {
    if (!email) {
        // Fallback to modal context if no email provided (for backward compatibility)
        email = document.getElementById('editEmail').value;
    }
    
    if (!confirm(`Are you sure you want to delete user: ${email}?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('email', email);
    
    fetch('user_management_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error deleting user: ' + error);
    });
}

// Add User Modal Functions
function openAddModal() {
    // Reset form
    document.getElementById('addUserForm').reset();
    document.getElementById('addRole').value = 'r4';
    document.getElementById('addPowerEditor').checked = false;
    document.getElementById('addAllianceAll').checked = false;
    
    // Reset alliance checkboxes
    const addAllianceCheckboxes = document.querySelectorAll('input[name="add_alliances[]"]');
    addAllianceCheckboxes.forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
    });
    
    updateAddPowerEditorVisibility();
    document.getElementById('addModal').classList.add('show');
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('show');
}

function toggleAddAllAlliances(checkbox) {
    const allianceCheckboxes = document.querySelectorAll('input[name="add_alliances[]"]');
    allianceCheckboxes.forEach(cb => {
        cb.disabled = checkbox.checked;
        if (checkbox.checked) {
            cb.checked = false;
        }
    });
}

function updateAddPowerEditorVisibility() {
    const roleSelect = document.getElementById('addRole');
    const powerEditorGroup = document.getElementById('addPowerEditorGroup');
    const powerEditorCheckbox = document.getElementById('addPowerEditor');
    
    if (roleSelect.value === 'admin') {
        powerEditorGroup.style.display = 'none';
        powerEditorCheckbox.checked = false;
    } else {
        powerEditorGroup.style.display = 'block';
    }
}

function addUser() {
    const email = document.getElementById('addEmail').value.trim();
    const role = document.getElementById('addRole').value;
    const powerEditor = document.getElementById('addPowerEditor').checked;
    
    if (!email) {
        alert('Please enter an email address');
        return;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('email', email);
    formData.append('role', role);
    formData.append('powereditor', powerEditor ? '1' : '0');
    
    // Get selected alliances
    const alliances = [];
    if (document.getElementById('addAllianceAll').checked) {
        alliances.push('*');
    } else {
        const allianceCheckboxes = document.querySelectorAll('input[name="add_alliances[]"]:checked');
        allianceCheckboxes.forEach(cb => alliances.push(cb.value));
    }
    
    if (alliances.length === 0) {
        alert('Please select at least one alliance');
        return;
    }
    
    alliances.forEach(alliance => formData.append('alliances[]', alliance));
    
    fetch('user_management_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding user: ' + error);
    });
}

// Email toggle functionality
function toggleSingleEmail(button) {
    const emailSpan = button.previousElementSibling;
    const currentText = emailSpan.textContent;
    const email = emailSpan.getAttribute('data-email');
    const masked = emailSpan.getAttribute('data-masked');
    
    if (currentText === masked) {
        emailSpan.textContent = email;
        emailSpan.classList.remove('email-masked');
        emailSpan.classList.add('email-text');
        button.title = 'Hide email';
    } else {
        emailSpan.textContent = masked;
        emailSpan.classList.remove('email-text');
        emailSpan.classList.add('email-masked');
        button.title = 'Show email';
    }
}

// Magic link generation
function generateMagicLink(email) {
    const formData = new FormData();
    formData.append('action', 'generate_magic_link');
    formData.append('email', email);
    
    fetch('user_management_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMagicLinkModal(data.magic_link, data.expires_at, email, data.expiry_minutes);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error generating magic link: ' + error);
    });
}

function showMagicLinkModal(magicLink, expiresAt, email, expiryMinutes = 10) {
    // Create modal HTML
    const modalHtml = `
        <div id="magicLinkModal" class="modal show">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>🔗 Magic Login Link</h2>
                    <span class="close" onclick="closeMagicLinkModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p><strong>Generated for:</strong> ${email}</p>
                    <p><strong>Expires:</strong> ${expiresAt}</p>
                    
                    <div class="magic-link-container">
                        <label>Magic Link:</label>
                        <div class="link-display">
                            <input type="text" id="magicLinkInput" value="${magicLink}" readonly>
                            <button type="button" class="btn btn-secondary" onclick="copyMagicLink()">📋 Copy</button>
                        </div>
                    </div>
                    
                    <div class="magic-link-warning">
                        <p><strong>⚠️ Security Notice:</strong></p>
                        <ul>
                            <li>This link provides immediate access without password</li>
                            <li>Share only through secure channels</li>
                            <li>Link expires in ${expiryMinutes} minutes</li>
                            <li>Can only be used once</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="emailMagicLinkFromModal('${email}', '${magicLink}')">📧 Email Link</button>
                    <button type="button" class="btn btn-primary" onclick="copyMagicLinkFromModal()">📋 Copy Link</button>
                    <button type="button" class="btn btn-secondary" onclick="closeMagicLinkModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('magicLinkModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeMagicLinkModal() {
    const modal = document.getElementById('magicLinkModal');
    if (modal) {
        modal.remove();
    }
}

function copyMagicLink() {
    const input = document.getElementById('magicLinkInput');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show feedback
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '✅ Copied!';
        button.style.background = '#28a745';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
    } catch (err) {
        alert('Failed to copy link. Please select and copy manually.');
    }
}

function copyMagicLinkFromModal() {
    const input = document.getElementById('magicLinkInput');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Visual feedback
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '✅ Copied!';
        button.style.background = '#28a745';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
        
        showToast('Magic link copied to clipboard!', 'success');
    } catch (err) {
        alert('Failed to copy link. Please select and copy manually.');
    }
}

function emailMagicLinkFromModal(email, magicLink) {
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '📧 Sending...';
    button.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'email_magic_link');
    formData.append('email', email);
    formData.append('magic_link', magicLink);
    
    fetch('user_management_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.textContent = '✅ Sent!';
            button.style.background = '#28a745';
            showToast(`Magic link emailed to ${email}`, 'success');
            
            setTimeout(() => {
                closeMagicLinkModal();
            }, 2000);
        } else {
            button.textContent = originalText;
            button.disabled = false;
            showToast('Failed to send email: ' + data.message, 'error');
        }
    })
    .catch(error => {
        button.textContent = originalText;
        button.disabled = false;
        showToast('Error sending email: ' + error, 'error');
    });
}

// Search and filter functionality
function filterUsers() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const allianceFilter = document.getElementById('allianceFilter').value;
    
    const rows = document.querySelectorAll('.users-table tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const email = row.cells[0].textContent.toLowerCase();
        const roleCell = row.cells[1];
        const allianceCell = row.cells[2];
        const statusCell = row.cells[3];
        
        // Extract role from badge
        const roleBadge = roleCell.querySelector('.role-badge');
        const role = roleBadge ? roleBadge.textContent.toLowerCase().replace('+ape', '').trim() : '';
        
        // Extract status
        const statusDot = statusCell.querySelector('.status-dot');
        const status = statusDot ? (statusDot.classList.contains('active') ? 'active' : 'inactive') : '';
        
        // Extract alliances
        const allianceTags = allianceCell.querySelectorAll('.alliance-tag');
        const alliances = Array.from(allianceTags).map(tag => tag.textContent.trim());
        const hasAllAlliances = alliances.includes('ALL');
        
        // Apply filters
        let show = true;
        
        // Search filter
        if (searchTerm && !email.includes(searchTerm)) {
            show = false;
        }
        
        // Role filter
        if (roleFilter) {
            if (roleFilter === 'ape') {
                // Check if user has APE badge
                const apeBadge = roleCell.querySelector('.ape-badge');
                if (!apeBadge) {
                    show = false;
                }
            } else if (role !== roleFilter) {
                show = false;
            }
        }
        
        // Status filter
        if (statusFilter && status !== statusFilter) {
            show = false;
        }
        
        // Alliance filter
        if (allianceFilter) {
            if (allianceFilter === '*') {
                if (!hasAllAlliances) {
                    show = false;
                }
            } else {
                if (!hasAllAlliances && !alliances.includes(allianceFilter)) {
                    show = false;
                }
            }
        }
        
        // Show/hide row
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    // Update results count
    updateResultsCount(visibleCount, rows.length);
}

function updateResultsCount(visible, total) {
    let countElement = document.getElementById('resultsCount');
    if (!countElement) {
        countElement = document.createElement('div');
        countElement.id = 'resultsCount';
        countElement.className = 'results-count';
        document.querySelector('.search-filters').appendChild(countElement);
    }
    
    if (visible === total) {
        countElement.textContent = `Showing all ${total} users`;
    } else {
        countElement.textContent = `Showing ${visible} of ${total} users`;
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('allianceFilter').value = '';
    resetSort(); // Also reset sort when clearing filters
    filterUsers();
}

// Sorting functionality
let currentSort = { column: -1, direction: 'asc' };
let originalRowOrder = [];

function sortTable(columnIndex) {
    const table = document.querySelector('.users-table tbody');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    // Determine sort direction
    if (currentSort.column === columnIndex) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.direction = 'asc';
    }
    currentSort.column = columnIndex;
    
    // Update sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.className = 'sort-indicator';
    });
    document.querySelectorAll('.sort-indicator')[columnIndex].className = `sort-indicator ${currentSort.direction}`;
    
    // Sort rows
    rows.sort((a, b) => {
        let aValue = getCellValue(a, columnIndex);
        let bValue = getCellValue(b, columnIndex);
        
        // Handle different data types
        if (columnIndex === 1) { // Role column
            const roleOrder = { 'admin': 3, 'r5': 2, 'r4': 1 };
            aValue = roleOrder[aValue] || 0;
            bValue = roleOrder[bValue] || 0;
        } else if (columnIndex === 3) { // Status column
            aValue = aValue === 'active' ? 1 : 0;
            bValue = bValue === 'active' ? 1 : 0;
        }
        
        if (currentSort.direction === 'asc') {
            return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
        } else {
            return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
        }
    });
    
    // Re-append sorted rows
    rows.forEach(row => table.appendChild(row));
}

function getCellValue(row, columnIndex) {
    const cell = row.cells[columnIndex];
    
    switch (columnIndex) {
        case 0: // Email
            return cell.textContent.trim().toLowerCase();
        case 1: // Role
            const roleBadge = cell.querySelector('.role-badge');
            return roleBadge ? roleBadge.textContent.toLowerCase().replace('+ape', '').trim() : '';
        case 2: // Alliances
            const allianceTags = cell.querySelectorAll('.alliance-tag');
            return Array.from(allianceTags).map(tag => tag.textContent.trim()).join(', ');
        case 3: // Status
            const statusDot = cell.querySelector('.status-dot');
            return statusDot ? (statusDot.classList.contains('active') ? 'active' : 'inactive') : '';
        default:
            return cell.textContent.trim().toLowerCase();
    }
}

function resetSort() {
    const table = document.querySelector('.users-table tbody');
    
    // Clear sort indicators
    document.querySelectorAll('.sort-indicator').forEach(indicator => {
        indicator.className = 'sort-indicator';
    });
    
    // Reset sort state
    currentSort = { column: -1, direction: 'asc' };
    
    // Restore original order
    originalRowOrder.forEach(row => table.appendChild(row));
}

// Initialize filters on page load
document.addEventListener('DOMContentLoaded', function() {
    // Store original row order
    const table = document.querySelector('.users-table tbody');
    if (table) {
        originalRowOrder = Array.from(table.querySelectorAll('tr'));
    }
    
    filterUsers(); // Initialize count
});

// Close modal when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const addModal = document.getElementById('addModal');
    
    if (event.target === editModal) {
        closeEditModal();
    } else if (event.target === addModal) {
        closeAddModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>