<?php
/**
 * Logout Redirect - Redirects to proper admin logout
 * 
 * This file redirects users to the correct admin logout page
 * which properly handles JWT token revocation
 */

// Redirect to the proper admin logout page
header('Location: admin/logout.php');
exit();
?>