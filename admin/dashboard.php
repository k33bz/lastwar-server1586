<?php
/**
 * Admin Dashboard - Main interface for alliance and admin users
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

// Require valid session
$user_token = require_jwt_session();

// Load data
$users_data = read_json_file(USERS_FILE);
$alliances_data = file_exists(ALLIANCES_FILE) ? read_json_file(ALLIANCES_FILE) : ['alliances' => []];

$is_admin = ($user_token->aud === 'admin');
$user_email = $user_token->sub;
$user_alliances = $user_token->alliances;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Last War 1586 Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 { color: #333; font-size: 24px; }
        .user-info { color: #666; font-size: 14px; }
        .badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-danger { background: #e74c3c; color: white; }
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .alliance-card {
            border: 2px solid #eee;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .alliance-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Last War 1586 Admin Dashboard</h1>
            <div class="user-info">
                Logged in as: <strong><?= htmlspecialchars($user_email) ?></strong>
                <?php if ($is_admin): ?>
                    <span class="badge">Admin</span>
                <?php endif; ?>
            </div>
        </div>
        <form method="POST" action="logout.php" style="margin: 0;">
            <button type="submit" class="btn btn-danger">Logout</button>
        </form>
    </div>

    <?php if ($is_admin): ?>
        <div class="section">
            <h2>User Management</h2>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Alliances</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_data['users'] as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars(implode(', ', $user['alliances'])) ?></td>
                            <td>
                                <a href="admin_api.php?action=edit&email=<?= urlencode($user['email']) ?>" class="btn btn-primary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <a href="admin_api.php?action=add" class="btn btn-primary">Add New User</a>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Your Alliances</h2>
        <?php
        $accessible_alliances = [];
        if (in_array('*', $user_alliances)) {
            $accessible_alliances = $alliances_data['alliances'];
        } else {
            foreach ($alliances_data['alliances'] as $alliance) {
                if (in_array(strtolower($alliance['tag'] ?? ''), array_map('strtolower', $user_alliances))) {
                    $accessible_alliances[] = $alliance;
                }
            }
        }

        if (empty($accessible_alliances)): ?>
            <p>No alliances assigned to your account.</p>
        <?php else: ?>
            <?php foreach ($accessible_alliances as $alliance): ?>
                <div class="alliance-card">
                    <h3><?= htmlspecialchars($alliance['tag'] ?? 'N/A') ?> - <?= htmlspecialchars($alliance['name'] ?? 'Unknown') ?></h3>
                    <p><strong>R5:</strong> <?= htmlspecialchars($alliance['r5'] ?? 'N/A') ?></p>
                    <p><strong>Rank:</strong> <?= htmlspecialchars($alliance['rank'] ?? 'N/A') ?></p>
                    <p><strong>Signed:</strong> <?= ($alliance['signed'] ?? false) ? 'Yes' : 'No' ?></p>
                    <div class="actions">
                        <a href="allies_api.php?action=edit&tag=<?= urlencode($alliance['tag'] ?? '') ?>" class="btn btn-primary">Edit Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Quick Links</h2>
        <p><a href="../index.html" class="btn btn-primary">View Public Site</a></p>
    </div>
</body>
</html>
