<?php
/**
 * User Management API
 * Handles AJAX requests for user CRUD operations
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';

header('Content-Type: application/json');

try {
    $user = require_jwt_session();

    // Check if user has admin access
    if ($user->aud !== 'admin') {
        throw new Exception('Access denied. Admin privileges required.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $role = $_POST['role'] ?? '';
            $powereditor = ($_POST['powereditor'] ?? '0') === '1';
            $alliances = $_POST['alliances'] ?? [];

            if (empty($email) || empty($role)) {
                throw new Exception('Email and role are required');
            }

            // Basic email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Validate role
            if (!in_array($role, ['admin', 'r5', 'r4'])) {
                throw new Exception('Invalid role');
            }

            // Validate alliances
            if (empty($alliances)) {
                throw new Exception('At least one alliance must be selected');
            }

            // Admins cannot have powereditor flag (they automatically have access)
            if ($role === 'admin') {
                $powereditor = false;
            }

            // Check if user already exists
            $existing_user = get_user_by_email($email);
            if ($existing_user) {
                throw new Exception('User with this email already exists');
            }

            // Add user
            $success = add_user($email, $alliances, $role, $powereditor);
            
            if ($success) {
                log_audit_event('user_added', $user->sub, [
                    'target_email' => $email,
                    'role' => $role,
                    'powereditor' => $powereditor,
                    'alliances' => $alliances
                ]);
                
                echo json_encode(['success' => true, 'message' => 'User added successfully']);
            } else {
                throw new Exception('Failed to add user');
            }
            break;

        case 'update':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $role = $_POST['role'] ?? '';
            $powereditor = ($_POST['powereditor'] ?? '0') === '1';
            $alliances = $_POST['alliances'] ?? [];

            if (empty($email) || empty($role)) {
                throw new Exception('Email and role are required');
            }

            // Validate role
            if (!in_array($role, ['admin', 'r5', 'r4'])) {
                throw new Exception('Invalid role');
            }

            // Admins cannot have powereditor flag (they automatically have access)
            if ($role === 'admin') {
                $powereditor = false;
            }

            // Update user
            $success = update_user($email, $alliances, $role, $powereditor);
            
            if ($success) {
                log_audit_event('user_updated', $user->sub, [
                    'target_email' => $email,
                    'role' => $role,
                    'powereditor' => $powereditor,
                    'alliances' => $alliances
                ]);
                
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                throw new Exception('Failed to update user');
            }
            break;

        case 'delete':
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (empty($email)) {
                throw new Exception('Email is required');
            }

            // Prevent self-deletion
            if (strtolower($email) === strtolower($user->sub)) {
                throw new Exception('Cannot delete your own account');
            }

            // Revoke all active sessions before deletion
            $active_sessions = get_active_sessions($email);
            foreach ($active_sessions as $session) {
                if (isset($session['jti']) && isset($session['exp'])) {
                    blacklist_token($session['jti'], $session['exp']);
                }
            }

            // Delete user
            $success = delete_user($email);
            
            if ($success) {
                log_audit_event('user_deleted', $user->sub, [
                    'target_email' => $email
                ]);
                
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                throw new Exception('Failed to delete user');
            }
            break;

        case 'generate_magic_link':
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (empty($email)) {
                throw new Exception('Email is required');
            }

            // Check if user exists
            $target_user = get_user_by_email($email);
            if (!$target_user) {
                throw new Exception('User not found');
            }

            // Generate magic link token
            $magic_token = bin2hex(random_bytes(32));
            $expires_at = time() + (int)($_ENV['MAGIC_LINK_EXPIRY'] ?? 600); // Default 10 minutes
            
            // Store magic link in file
            $magic_links_file = __DIR__ . '/magic_links.json';
            $magic_links = [];
            
            if (file_exists($magic_links_file)) {
                $magic_links = json_decode(file_get_contents($magic_links_file), true) ?? [];
            }
            
            // Clean up expired links
            $magic_links = array_filter($magic_links, function($link) {
                return $link['expires_at'] > time();
            });
            
            // Add new magic link
            $magic_links[$magic_token] = [
                'email' => $email,
                'created_by' => $user->sub,
                'created_at' => time(),
                'expires_at' => $expires_at,
                'used' => false
            ];
            
            // Save magic links
            file_put_contents($magic_links_file, json_encode($magic_links, JSON_PRETTY_PRINT));
            
            // Generate the magic link URL
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                       '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            $magic_link = $base_url . '/../login.php?magic=' . $magic_token;
            
            log_audit_event('magic_link_generated', $user->sub, [
                'target_email' => $email,
                'expires_at' => date('Y-m-d H:i:s', $expires_at)
            ]);
            
            $expiry_minutes = (int)($_ENV['MAGIC_LINK_EXPIRY'] ?? 600) / 60;
            
            echo json_encode([
                'success' => true, 
                'magic_link' => $magic_link,
                'expires_at' => date('Y-m-d H:i:s', $expires_at),
                'expiry_minutes' => $expiry_minutes,
                'message' => 'Magic link generated successfully'
            ]);
            break;

        case 'email_magic_link':
            require_once 'mailer.php';
            
            $email = strtolower(trim($_POST['email'] ?? ''));
            $magic_link = $_POST['magic_link'] ?? '';

            if (empty($email) || empty($magic_link)) {
                throw new Exception('Email and magic link are required');
            }

            // Check if user exists
            $target_user = get_user_by_email($email);
            if (!$target_user) {
                throw new Exception('User not found');
            }

            // Extract username from email for personalized greeting
            $username = explode('@', $email)[0];

            // Send the magic link email
            $email_sent = send_magic_link_email($email, $magic_link, $username);
            
            if ($email_sent) {
                log_audit_event('magic_link_emailed', $user->sub, [
                    'target_email' => $email,
                    'magic_link_url' => $magic_link
                ]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Magic link email sent successfully'
                ]);
            } else {
                throw new Exception('Failed to send email. Please check SMTP configuration.');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>