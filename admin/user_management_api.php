<?php
/**
 * User Management API
 * Version: 1.0.1
 * Handles AJAX requests for user CRUD operations
 *
 * Changelog:
 * - 1.0.1: Fixed authentication to use require_jwt_session_api() for proper JSON error responses
 *
 * Documentation:
 * - User Personas (Roles): https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/USER-PERSONAS.md
 * - Admin Functionality: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ADMIN_FUNCTIONALITY.md
 * - Admin Panel Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/README.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';

header('Content-Type: application/json');

try {
    $user = require_jwt_session_api();

    // Check if user has admin access
    if ($user->aud !== 'admin') {
        throw new Exception('Access denied. Admin privileges required.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }

    // CSRF Protection
    requireCsrfToken();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $roles_json = $_POST['roles'] ?? '';
            $alliances = $_POST['alliances'] ?? [];

            if (empty($email)) {
                throw new Exception('Email is required');
            }

            // Basic email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Parse roles JSON
            $roles = json_decode($roles_json, true);
            if (!is_array($roles) || empty($roles)) {
                throw new Exception('At least one role must be selected');
            }

            // Validate each role
            $valid_roles = ['admin', 'president', 'r5', 'r4', 'ape', 'none', 'disabled'];
            foreach ($roles as $role) {
                if (!in_array($role, $valid_roles)) {
                    throw new Exception("Invalid role: $role");
                }
            }

            // Validate alliances (allow empty for APE-only or disabled users)
            $requires_alliance = !in_array('disabled', $roles) && !in_array('ape', $roles);
            if ($requires_alliance && empty($alliances)) {
                throw new Exception('At least one alliance must be selected for this role combination');
            }

            // Check if user already exists
            $existing_user = get_user_by_email($email);
            if ($existing_user) {
                throw new Exception('User with this email already exists');
            }

            // Add user with new roles format
            $success = add_user_multi_role($email, $alliances, $roles);

            if ($success) {
                log_audit_event('user_added', $user->sub, [
                    'target_email' => $email,
                    'roles' => $roles,
                    'alliances' => $alliances
                ]);

                echo json_encode(['success' => true, 'message' => 'User added successfully']);
            } else {
                throw new Exception('Failed to add user');
            }
            break;

        case 'update':
            $email = strtolower(trim($_POST['email'] ?? ''));
            $roles_json = $_POST['roles'] ?? '';
            $alliances = $_POST['alliances'] ?? [];

            if (empty($email)) {
                throw new Exception('Email is required');
            }

            // Parse roles JSON
            $roles = json_decode($roles_json, true);
            if (!is_array($roles) || empty($roles)) {
                throw new Exception('At least one role must be selected');
            }

            // Validate each role
            $valid_roles = ['admin', 'president', 'r5', 'r4', 'ape', 'none', 'disabled'];
            foreach ($roles as $role) {
                if (!in_array($role, $valid_roles)) {
                    throw new Exception("Invalid role: $role");
                }
            }

            // Validate alliances (allow empty for APE-only or disabled users)
            $requires_alliance = !in_array('disabled', $roles) && !in_array('ape', $roles);
            if ($requires_alliance && empty($alliances)) {
                throw new Exception('At least one alliance must be selected for this role combination');
            }

            // Get old user data to detect changes
            $old_user = get_user_by_email($email);
            if (!$old_user) {
                throw new Exception('User not found');
            }

            // Detect changes
            $changes = [];

            // Compare roles (convert old format if needed)
            $old_roles = [];
            if (isset($old_user['roles']) && is_array($old_user['roles'])) {
                $old_roles = $old_user['roles'];
            } else {
                // Backward compatibility
                if (isset($old_user['role'])) $old_roles[] = $old_user['role'];
                if (isset($old_user['powereditor']) && $old_user['powereditor']) $old_roles[] = 'ape';
            }

            sort($old_roles);
            sort($roles);
            if ($old_roles !== $roles) {
                $changes['roles'] = [
                    'old' => $old_roles,
                    'new' => $roles
                ];
            }

            // Compare alliances (arrays)
            $old_alliances = $old_user['alliances'] ?? [];
            sort($old_alliances);
            sort($alliances);
            if ($old_alliances !== $alliances) {
                $changes['alliances'] = [
                    'old' => $old_user['alliances'] ?? [],
                    'new' => $alliances
                ];
            }

            // Update user
            $success = update_user_multi_role($email, $alliances, $roles);

            if ($success) {
                log_audit_event('user_updated', $user->sub, [
                    'target_email' => $email,
                    'roles' => $roles,
                    'alliances' => $alliances,
                    'changes_detected' => !empty($changes)
                ]);

                // Send email notification if anything changed
                if (!empty($changes)) {
                    try {
                        require_once 'mailer.php';
                        $email_sent = send_role_change_email($email, $changes, $user->sub);

                        if ($email_sent) {
                            // Log successful email notification to audit log
                            log_audit_event('email_sent_role_change', $user->sub, [
                                'type' => 'role_change_notification',
                                'recipient' => $email,
                                'changes' => $changes,
                                'status' => 'success'
                            ]);
                            error_log("Role change notification sent to: $email");
                        } else {
                            // Log failed email to audit log
                            log_audit_event('email_failed_role_change', $user->sub, [
                                'type' => 'role_change_notification',
                                'recipient' => $email,
                                'changes' => $changes,
                                'status' => 'failed',
                                'reason' => 'Email send returned false'
                            ]);
                            error_log("Failed to send role change email to $email: send_email returned false");
                        }
                    } catch (Exception $e) {
                        // Log failed email to audit log
                        log_audit_event('email_failed_role_change', $user->sub, [
                            'type' => 'role_change_notification',
                            'recipient' => $email,
                            'changes' => $changes,
                            'status' => 'failed',
                            'reason' => $e->getMessage()
                        ]);
                        error_log("Failed to send role change email to $email: " . $e->getMessage());
                        // Don't fail the update if email fails
                    }
                }

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

            // Check if user is disabled
            if (isset($target_user['role']) && $target_user['role'] === 'disabled') {
                throw new Exception('Cannot generate magic link for disabled user');
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
                log_audit_event('email_sent_magic_link', $user->sub, [
                    'type' => 'magic_link_email',
                    'recipient' => $email,
                    'magic_link_url' => $magic_link,
                    'status' => 'success'
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Magic link email sent successfully'
                ]);
            } else {
                // Log failed email to audit log
                log_audit_event('email_failed_magic_link', $user->sub, [
                    'type' => 'magic_link_email',
                    'recipient' => $email,
                    'status' => 'failed',
                    'reason' => 'Email send returned false'
                ]);
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