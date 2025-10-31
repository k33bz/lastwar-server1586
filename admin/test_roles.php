<?php
/**
 * Role Testing Page
 * Switch between different user roles to test access control
 */

session_start();

// Handle role switching
if (isset($_GET['role'])) {
    $role = $_GET['role'];
    switch ($role) {
        case 'admin':
            $_SESSION['user_id'] = 1;
            $_SESSION['role'] = 'admin';
            $_SESSION['username'] = 'Admin User';
            $_SESSION['powereditor'] = false;
            break;
        case 'r5':
            $_SESSION['user_id'] = 2;
            $_SESSION['role'] = 'r5';
            $_SESSION['username'] = 'R5 Leader';
            $_SESSION['powereditor'] = false;
            break;
        case 'r4':
            $_SESSION['user_id'] = 3;
            $_SESSION['role'] = 'r4';
            $_SESSION['username'] = 'R4 Officer';
            $_SESSION['powereditor'] = false;
            break;
        case 'r4_power':
            $_SESSION['user_id'] = 4;
            $_SESSION['role'] = 'r4';
            $_SESSION['username'] = 'R4 Power Editor';
            $_SESSION['powereditor'] = true;
            break;
        case 'r5_power':
            $_SESSION['user_id'] = 5;
            $_SESSION['role'] = 'r5';
            $_SESSION['username'] = 'R5 Power Editor';
            $_SESSION['powereditor'] = true;
            break;
        case 'none':
            $_SESSION['user_id'] = 6;
            $_SESSION['role'] = 'none';
            $_SESSION['username'] = 'Read-Only User';
            $_SESSION['powereditor'] = false;
            break;
        case 'disabled':
            $_SESSION['user_id'] = 7;
            $_SESSION['role'] = 'disabled';
            $_SESSION['username'] = 'Disabled User';
            $_SESSION['powereditor'] = false;
            break;
    }
    header('Location: dashboard.php');
    exit();
}

// Set page title for header
$page_title = "Role Testing";

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🧪 Role Testing</h1>
    <p class="page-description">Switch between different user roles to test access control</p>
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
        
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .role-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            text-align: center;
            transition: all 0.3s;
        }
        
        .role-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .role-card.current {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .role-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .role-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .role-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .role-permissions {
            background: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: left;
        }
        
        .role-permissions h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .role-permissions ul {
            list-style: none;
            padding: 0;
        }
        
        .role-permissions li {
            padding: 0.25rem 0;
            font-size: 0.8rem;
            color: #666;
        }
        
        .role-permissions li::before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .current-role {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }
    </style>

    <div class="current-role">
        <h3>Current Role: <?php echo strtoupper($_SESSION['role'] ?? 'NONE'); ?><?php if (isset($_SESSION['powereditor']) && $_SESSION['powereditor']) echo ' + APE'; ?></h3>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Not logged in'); ?></p>
        <?php if (isset($_SESSION['powereditor']) && $_SESSION['powereditor']): ?>
            <p><strong>Alliance Power Editor (APE):</strong> ✅ Enabled - Can edit alliance power values</p>
        <?php else: ?>
            <p><strong>Alliance Power Editor (APE):</strong> ❌ Disabled - View-only access to power data</p>
        <?php endif; ?>
    </div>

    <div class="role-cards">
        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'admin' ? 'current' : ''; ?>">
            <div class="role-icon">👑</div>
            <div class="role-title">Admin</div>
            <div class="role-description">Full system access and control</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul>
                    <li>All alliance management</li>
                    <li>User management</li>
                    <li>System administration</li>
                    <li>Security monitoring</li>
                    <li>Backup & restore</li>
                    <li>Audit logs</li>
                </ul>
            </div>
            <a href="?role=admin" class="btn btn-primary">Switch to Admin</a>
        </div>

        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'r5' ? 'current' : ''; ?>">
            <div class="role-icon">⭐</div>
            <div class="role-title">R5 Leader</div>
            <div class="role-description">Alliance leader with signing privileges</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul>
                    <li>View all alliances</li>
                    <li>Edit alliance info</li>
                    <li>Sign server rules</li>
                    <li>Manage alliance members</li>
                </ul>
            </div>
            <a href="?role=r5" class="btn btn-primary">Switch to R5</a>
        </div>

        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'r4' && !isset($_SESSION['powereditor']) ? 'current' : ''; ?>">
            <div class="role-icon">🛡️</div>
            <div class="role-title">R4 Officer</div>
            <div class="role-description">Alliance officer with edit access</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul>
                    <li>View all alliances</li>
                    <li>Edit alliance info</li>
                    <li>View statistics</li>
                </ul>
            </div>
            <a href="?role=r4" class="btn btn-primary">Switch to R4</a>
        </div>

        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'r4' && isset($_SESSION['powereditor']) && $_SESSION['powereditor'] ? 'current' : ''; ?>">
            <div class="role-icon">⚡</div>
            <div class="role-title">R4 Power Editor</div>
            <div class="role-description">R4 with power editing privileges</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul>
                    <li>View all alliances</li>
                    <li>Edit alliance info</li>
                    <li>Edit alliance power</li>
                    <li>Add new alliances</li>
                </ul>
            </div>
            <a href="?role=r4_power" class="btn btn-primary">Switch to R4 Power Editor</a>
        </div>

        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'r5' && isset($_SESSION['powereditor']) && $_SESSION['powereditor'] ? 'current' : ''; ?>">
            <div class="role-icon">⭐⚡</div>
            <div class="role-title">R5 Power Editor</div>
            <div class="role-description">R5 leader with power editing privileges</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul>
                    <li>View all alliances</li>
                    <li>Edit alliance info</li>
                    <li>Sign server rules</li>
                    <li>Edit alliance power</li>
                    <li>Add new alliances</li>
                    <li>Manage alliance members</li>
                </ul>
            </div>
            <a href="?role=r5_power" class="btn btn-primary">Switch to R5 Power Editor</a>
        </div>

        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'none' ? 'current' : ''; ?>">
            <div class="role-icon">👁️</div>
            <div class="role-title">None (Read-Only)</div>
            <div class="role-description">Can view but cannot edit anything</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul>
                    <li>View assigned alliances</li>
                    <li>View statistics (read-only)</li>
                </ul>
            </div>
            <a href="?role=none" class="btn btn-primary">Switch to None</a>
        </div>

        <div class="role-card <?php echo ($_SESSION['role'] ?? '') === 'disabled' ? 'current' : ''; ?>">
            <div class="role-icon">🚫</div>
            <div class="role-title">Disabled</div>
            <div class="role-description">Account suspended - cannot log in</div>
            <div class="role-permissions">
                <h4>Permissions:</h4>
                <ul style="color: #dc3545;">
                    <li style="color: #dc3545;">❌ No access</li>
                    <li style="color: #dc3545;">❌ Cannot request magic links</li>
                    <li style="color: #dc3545;">❌ Account suspended</li>
                </ul>
            </div>
            <a href="?role=disabled" class="btn btn-primary">Switch to Disabled</a>
        </div>
    </div>

    <div style="margin-top: 2rem; text-align: center;">
        <a href="dashboard.php" class="btn btn-success">Go to Dashboard</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>