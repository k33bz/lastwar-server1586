<?php
/**
 * Example: Dashboard with JWT Token Rotation
 *
 * Shows how to integrate automatic token rotation into existing pages
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/enhanced_jwt_middleware.php'; // Use enhanced middleware

// Use enhanced session validation with automatic rotation
$user = require_enhanced_jwt_session();

// Add rotation headers for API responses
add_rotation_headers($user);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $_ENV['APP_NAME'] ?? 'Last War 1586 Admin'; ?></title>
    <!-- Include rotation JavaScript -->
    <?= get_rotation_javascript() ?>
</head>
<body>
    <h1>Dashboard with Token Rotation</h1>
    <p>Welcome, <?= htmlspecialchars($user->sub) ?></p>
    <p>Your token will automatically rotate when needed.</p>
    
    <!-- Rest of dashboard content -->
</body>
</html>