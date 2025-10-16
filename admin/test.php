<?php
/**
 * Simple Test Page
 * Version: 1.0.0
 * Test the shared header/footer system
 */

session_start();

// Mock authentication for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'Test Admin';

// Set page title for header
$page_title = "Test Page";

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🧪 Test Page</h1>
    <p class="page-description">Testing the shared header and footer components</p>
</div>

<div class="container">
    <h2>System Information</h2>
    <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
        <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
    
    <h2>Session Information</h2>
    <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
        <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'Not set'; ?></p>
        <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Not set'; ?></p>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
    </div>
    
    <h2>Test Alerts</h2>
    <div class="alert alert-success">This is a success alert!</div>
    <div class="alert alert-warning">This is a warning alert!</div>
    <div class="alert alert-error">This is an error alert!</div>
    
    <h2>Test Buttons</h2>
    <div style="margin: 1rem 0;">
        <a href="#" class="btn btn-primary">Primary Button</a>
        <a href="#" class="btn btn-secondary">Secondary Button</a>
        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>