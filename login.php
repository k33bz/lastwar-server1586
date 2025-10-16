<?php
/**
 * Login Redirect - Redirects to proper admin login
 * 
 * This file redirects users to the correct admin login page
 * which uses magic link authentication with JWT tokens
 */

// Redirect to the proper admin login page
header('Location: admin/login.php');
exit();
