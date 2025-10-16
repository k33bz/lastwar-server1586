<?php
/**
 * Main Index Page
 * Version: 1.0.0
 * Redirects to appropriate page based on authentication
 */

session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Redirect to login page
header('Location: login.php');
exit();
?>