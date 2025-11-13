<?php
/**
 * Admin Panel Shared Header
 * Version: 1.5.0
 * Provides consistent navigation and security checks
 *
 * Changelog:
 * v1.5.0 (2025-11-12) - Added Content Security Policy (CSP) headers
 *   - Implemented comprehensive CSP to prevent XSS attacks
 *   - Nonce-based inline script/style approval
 *   - Restricts all external resource loading
 *   - Blocks clickjacking with frame-ancestors
 *   - Prevents plugin execution
 *   - Upgrade insecure requests to HTTPS
 * v1.4.0 (2025-11-12) - Added generic notification system with header badge
 *   - Notification bell icon with unread count badge
 *   - Dropdown menu showing recent notifications
 *   - Auto-refresh every 60 seconds
 *   - Mark as read / Mark all read functionality
 *   - Priority indicators (high/medium/low)
 *   - Mobile-responsive design
 *   - Dark theme support
 * v1.3.0 (2025-11-12) - Refactored navigation to separate governance from Discord
 *   - Created new "Server Management" dropdown for all governance/voting operations
 *   - Moved Council Rotation, Council Proposals, Vote Approvals, and Votes to Server Management
 *   - Removed voting pages from Discord dropdown (not Discord-specific features)
 *   - Added Channels and Rate Limits to Discord dropdown
 *   - Renamed Votes to "Votes History" for clarity
 *   - Discord dropdown now only contains bot management (announcements, channels, config)
 *   - Improved logical organization: voting is server governance, not a Discord feature
 * v1.2.0 (2025-10-17) - Fixed dropdown menu hover behavior
 *   - Added invisible bridge between trigger and menu to prevent closing
 *   - Added smooth fade-in/fade-out transitions
 *   - Improved dropdown reliability and user experience
 * v1.1.0 (2025-10-17) - Enhanced navigation with dropdown menus
 *   - Converted nav to dropdown structure for better organization
 *   - Removed duplicate "Logs" link (now under Security dropdown)
 *   - Added Alliances dropdown (Editor, Power Editor, Tag Manager)
 *   - Added Users dropdown (Manage Users, Magic Links, Send Login Link)
 *   - Added Security dropdown (Monitor, Audit Logs, JWT Keys, MFA, Backups)
 *   - Added hover animations and better visual hierarchy
 * v1.0.0 (2025-10-15) - Initial header with flat navigation
 */

// Security check - ensure JWT user is available
// This should be set by the including page after calling require_jwt_session()
if (!isset($user)) {
    header('Location: login.php');
    exit();
}

// Include CSRF protection
require_once __DIR__ . '/csrf.php';

// Include email utilities
require_once __DIR__ . '/email_utils.php';

// Include and initialize CSP (Content Security Policy)
require_once __DIR__ . '/csp.php';
$csp_nonce = init_csp(true); // Initialize CSP in report-only mode (monitors but doesn't block)

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo $_ENV['APP_NAME'] ?? 'Admin Panel'; ?></title>

    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">

    <!-- CSRF Token -->
    <?php echo csrfMetaTag(); ?>

    <!-- Shared Styles -->
    <link rel="stylesheet" href="includes/styles.css">

    <!-- Page-specific Styles -->
    <style <?php echo csp_nonce(); ?>>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .admin-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 0.75rem 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-logo {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-logo::before {
            content: "🛡️";
            font-size: 1rem;
        }
        
        .admin-nav {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .nav-link {
            color: #6c757d;
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
            position: relative;
        }

        .nav-link:hover {
            background-color: #f8f9fa;
            color: #2c3e50;
        }

        .nav-link.active {
            background-color: #2c3e50;
            color: white;
        }

        /* Dropdown Menu Styles */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            cursor: pointer;
        }

        .nav-dropdown-trigger::after {
            content: '▾';
            font-size: 0.7rem;
            transition: transform 0.2s;
        }

        .nav-dropdown:hover .nav-dropdown-trigger::after {
            transform: translateY(2px);
        }

        /* Create invisible bridge between trigger and menu */
        .nav-dropdown::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            height: 0.5rem;
            background: transparent;
            display: none;
        }

        .nav-dropdown:hover::before {
            display: block;
        }

        .nav-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s linear 0.2s;
            z-index: 1000;
            padding: 0.5rem 0;
        }

        .nav-dropdown:hover .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s linear 0s;
        }

        .nav-dropdown-menu .nav-link {
            display: block;
            padding: 0.5rem 1rem;
            border-radius: 0;
            white-space: nowrap;
        }

        .nav-dropdown-menu .nav-link:hover {
            background-color: #f8f9fa;
        }

        .nav-dropdown-menu .nav-link.active {
            background-color: #e9ecef;
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }
        
        .user-welcome {
            color: #6c757d;
            font-weight: 500;
        }
        
        .user-role {
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .token-countdown {
            font-size: 0.75rem;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Theme Toggle */
        .theme-toggle-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 1rem;
        }

        .theme-toggle-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 500;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-slider {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 60px;
            height: 32px;
            background: #e9ecef;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
            padding: 0 6px;
        }

        .toggle-slider:hover {
            border-color: #adb5bd;
        }

        .toggle-option {
            font-size: 1rem;
            transition: all 0.3s ease;
            z-index: 2;
            pointer-events: none;
            opacity: 0.5;
        }

        .toggle-light {
            order: 1;
        }

        .toggle-dark {
            order: 2;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(28px);
        }

        .toggle-switch input:checked + .toggle-slider .toggle-dark {
            opacity: 1;
        }

        .toggle-switch input:not(:checked) + .toggle-slider .toggle-light {
            opacity: 1;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .page-description {
            color: #666;
            font-size: 1.1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        @media (max-width: 992px) {
            .admin-nav {
                gap: 0.25rem;
            }

            .nav-link {
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .user-info {
                gap: 0.5rem;
            }

            .user-welcome {
                display: none;
            }

            .theme-toggle-label {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0 1rem;
            }

            .admin-nav {
                order: 2;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }

            .theme-toggle-container {
                order: 1;
            }

            .user-info {
                order: 3;
                justify-content: center;
            }

            .main-container {
                padding: 0 1rem;
            }
        }

        /* Dark Theme */
        body.dark-theme {
            background: #1a1a2e;
            color: #e0e0e0;
        }

        body.dark-theme .admin-header {
            background: #16213e;
            border-bottom-color: #0f3460;
        }

        body.dark-theme .admin-logo {
            color: #e0e0e0;
        }

        body.dark-theme .nav-link {
            color: #a0a0a0;
        }

        body.dark-theme .nav-link:hover {
            background-color: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .nav-link.active {
            background-color: #667eea;
            color: white;
        }

        body.dark-theme .nav-dropdown-menu {
            background: #16213e;
            border-color: #0f3460;
        }

        body.dark-theme .nav-dropdown-menu .nav-link:hover {
            background-color: #0f3460;
        }

        body.dark-theme .nav-dropdown-menu .nav-link.active {
            background-color: #0f3460;
            color: #667eea;
        }

        body.dark-theme .user-welcome {
            color: #a0a0a0;
        }

        body.dark-theme .user-role {
            background: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .page-header {
            background: #16213e;
            border-color: #0f3460;
        }

        body.dark-theme .page-title {
            color: #e0e0e0;
        }

        body.dark-theme .page-description {
            color: #a0a0a0;
        }

        body.dark-theme .toggle-slider {
            background: #0f3460;
            border-color: #1a1a2e;
        }

        body.dark-theme .alert-success {
            background: #1e4620;
            color: #4ade80;
            border-color: #22c55e;
        }

        body.dark-theme .alert-error {
            background: #4a1e1e;
            color: #f87171;
            border-color: #ef4444;
        }

        body.dark-theme .alert-warning {
            background: #4a3e1e;
            color: #fbbf24;
            border-color: #f59e0b;
        }

        /* Dark theme - Cards and Containers */
        body.dark-theme .card,
        body.dark-theme .feature-card,
        body.dark-theme .profile-section,
        body.dark-theme .info-box {
            background: #16213e;
            border-color: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .card-header {
            background: #0f3460;
            border-color: #16213e;
            color: #e0e0e0;
        }

        /* Dark theme - Forms */
        body.dark-theme input[type="text"],
        body.dark-theme input[type="email"],
        body.dark-theme input[type="number"],
        body.dark-theme input[type="password"],
        body.dark-theme input[type="date"],
        body.dark-theme input[type="datetime-local"],
        body.dark-theme select,
        body.dark-theme textarea {
            background: #0f3460;
            border-color: #1a1a2e;
            color: #e0e0e0;
        }

        body.dark-theme input::placeholder,
        body.dark-theme textarea::placeholder {
            color: #6b7280;
        }

        body.dark-theme input:focus,
        body.dark-theme select:focus,
        body.dark-theme textarea:focus {
            border-color: #667eea;
            background: #16213e;
        }

        body.dark-theme label {
            color: #d1d5db;
        }

        body.dark-theme .help-text {
            color: #9ca3af;
        }

        /* Dark theme - Tables */
        body.dark-theme table {
            background: #16213e;
            color: #e0e0e0;
        }

        body.dark-theme thead th {
            background: #0f3460;
            color: #e0e0e0;
            border-color: #1a1a2e;
        }

        body.dark-theme tbody tr {
            border-color: #0f3460;
        }

        body.dark-theme tbody tr:hover {
            background-color: #0f3460;
        }

        body.dark-theme td {
            color: #e0e0e0;
        }

        /* Dark theme - Buttons */
        body.dark-theme .btn-secondary {
            background: #0f3460;
            color: #e0e0e0;
            border-color: #1a1a2e;
        }

        body.dark-theme .btn-secondary:hover {
            background: #16213e;
        }

        body.dark-theme .btn-danger {
            background: #7f1d1d;
            color: #fecaca;
        }

        body.dark-theme .btn-danger:hover {
            background: #991b1b;
        }

        /* Dark theme - Modals */
        body.dark-theme .modal-content {
            background: #16213e;
            border-color: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .modal-header {
            border-color: #0f3460;
        }

        body.dark-theme .modal-footer {
            border-color: #0f3460;
        }

        body.dark-theme .modal-close {
            color: #e0e0e0;
        }

        /* Dark theme - Tabs */
        body.dark-theme .tab-button {
            background: #16213e;
            color: #a0a0a0;
            border-color: #0f3460;
        }

        body.dark-theme .tab-button:hover {
            background: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .tab-button.active {
            background: #667eea;
            color: white;
        }

        /* Dark theme - Status badges */
        body.dark-theme .status-badge {
            background: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .badge-success {
            background: #166534;
            color: #86efac;
        }

        body.dark-theme .badge-danger {
            background: #7f1d1d;
            color: #fca5a5;
        }

        body.dark-theme .badge-warning {
            background: #78350f;
            color: #fde047;
        }

        /* Dark theme - Empty states and messages */
        body.dark-theme .empty-state {
            color: #9ca3af;
        }

        body.dark-theme .loading {
            color: #9ca3af;
        }

        /* Dark theme - Links */
        body.dark-theme a {
            color: #818cf8;
        }

        body.dark-theme a:hover {
            color: #a5b4fc;
        }

        /* Dark theme - Code blocks */
        body.dark-theme code,
        body.dark-theme pre {
            background: #0f3460;
            color: #e0e0e0;
            border-color: #1a1a2e;
        }

        /* Dark theme - HR dividers */
        body.dark-theme hr {
            border-color: #0f3460;
        }

        /* Dark theme - Specific components */
        body.dark-theme .alliance-card {
            background: #16213e;
            border-color: #0f3460;
            color: #e0e0e0;
        }

        body.dark-theme .r4-card {
            background: #16213e;
            border-color: #0f3460;
        }

        body.dark-theme .r4-card h4 {
            color: #e0e0e0;
        }

        body.dark-theme .message-preview {
            background: #0f3460;
            color: #e0e0e0;
        }

        /* Notification System Styles */
        .notification-container {
            position: relative;
            margin-right: 1rem;
        }

        .notification-bell {
            position: relative;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-bell:hover {
            background-color: #f8f9fa;
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: white;
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.15rem 0.35rem;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }

        .notification-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 400px;
            max-height: 500px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s linear 0.2s;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s linear 0s;
        }

        .notification-dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .notification-dropdown-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .mark-all-read-btn {
            background: transparent;
            border: none;
            color: #667eea;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .mark-all-read-btn:hover {
            background-color: #f8f9fa;
            color: #5568d3;
        }

        .notification-list {
            overflow-y: auto;
            max-height: 400px;
            flex: 1;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #e7f3ff;
        }

        .notification-item.unread:hover {
            background-color: #d0e7ff;
        }

        .notification-item.priority-high {
            border-left: 4px solid #dc3545;
        }

        .notification-item.priority-medium {
            border-left: 4px solid #ffc107;
        }

        .notification-item.priority-low {
            border-left: 4px solid #28a745;
        }

        .notification-title {
            font-weight: 600;
            font-size: 0.875rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.8125rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #adb5bd;
        }

        .notification-time {
            font-style: italic;
        }

        .notification-action {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .notification-action:hover {
            color: #5568d3;
            text-decoration: underline;
        }

        .notification-dropdown-footer {
            padding: 0.75rem;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }

        .view-all-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .view-all-link:hover {
            color: #5568d3;
            text-decoration: underline;
        }

        .notification-loading,
        .notification-empty {
            padding: 2rem;
            text-align: center;
            color: #adb5bd;
            font-size: 0.875rem;
        }

        /* Dark theme - Notifications */
        body.dark-theme .notification-bell:hover {
            background-color: #0f3460;
        }

        body.dark-theme .notification-dropdown {
            background: #16213e;
            border-color: #0f3460;
        }

        body.dark-theme .notification-dropdown-header {
            border-color: #0f3460;
        }

        body.dark-theme .notification-dropdown-header h3 {
            color: #e0e0e0;
        }

        body.dark-theme .mark-all-read-btn {
            color: #818cf8;
        }

        body.dark-theme .mark-all-read-btn:hover {
            background-color: #0f3460;
            color: #a5b4fc;
        }

        body.dark-theme .notification-item {
            border-color: #0f3460;
        }

        body.dark-theme .notification-item:hover {
            background-color: #0f3460;
        }

        body.dark-theme .notification-item.unread {
            background-color: #1a2642;
        }

        body.dark-theme .notification-item.unread:hover {
            background-color: #0f3460;
        }

        body.dark-theme .notification-title {
            color: #e0e0e0;
        }

        body.dark-theme .notification-message {
            color: #a0a0a0;
        }

        body.dark-theme .notification-footer {
            color: #6c757d;
        }

        body.dark-theme .notification-action {
            color: #818cf8;
        }

        body.dark-theme .notification-action:hover {
            color: #a5b4fc;
        }

        body.dark-theme .notification-dropdown-footer {
            border-color: #0f3460;
        }

        body.dark-theme .view-all-link {
            color: #818cf8;
        }

        body.dark-theme .view-all-link:hover {
            color: #a5b4fc;
        }

        body.dark-theme .notification-loading,
        body.dark-theme .notification-empty {
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .notification-dropdown {
                width: 90vw;
                max-width: 400px;
                right: -50px;
            }

            .notification-container {
                margin-right: 0.5rem;
            }
        }
    </style>

    <script <?php echo csp_nonce(); ?>>
        // Token expiration countdown
        const tokenExp = <?php echo isset($user->exp) ? $user->exp : 'null'; ?>;
        
        function updateTokenCountdown() {
            const countdownEl = document.getElementById('token-countdown');
            if (!countdownEl || !tokenExp) return;
            
            const now = Math.floor(Date.now() / 1000);
            const remaining = tokenExp - now;
            
            if (remaining <= 0) {
                countdownEl.textContent = '(expired)';
                countdownEl.style.color = '#ff6b6b';
                // Auto-redirect to login after a short delay
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 2000);
                return;
            }
            
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            
            if (remaining < 300) { // Less than 5 minutes
                countdownEl.style.color = '#ff6b6b';
                countdownEl.textContent = `(${minutes}:${seconds.toString().padStart(2, '0')})`;
            } else if (remaining < 900) { // Less than 15 minutes
                countdownEl.style.color = '#ffa726';
                countdownEl.textContent = `(${minutes}m)`;
            } else {
                countdownEl.style.color = 'rgba(255,255,255,0.7)';
                countdownEl.textContent = `(${minutes}m)`;
            }
        }
        
        // Update countdown every second
        if (tokenExp) {
            updateTokenCountdown();
            setInterval(updateTokenCountdown, 1000);
        }
        
        // Theme toggle functionality
        function toggleTheme() {
            const toggle = document.getElementById('theme-toggle');
            const isDark = toggle.checked; // true = dark, false = light

            if (isDark) {
                document.body.classList.add('dark-theme');
                localStorage.setItem('adminTheme', 'dark');
            } else {
                document.body.classList.remove('dark-theme');
                localStorage.setItem('adminTheme', 'light');
            }
        }

        window.addEventListener('load', function() {
            // Load saved theme preference
            const savedTheme = localStorage.getItem('adminTheme') || localStorage.getItem('dashboardTheme') || 'light';
            const toggle = document.getElementById('theme-toggle');

            if (toggle) {
                toggle.checked = savedTheme === 'dark';
                if (savedTheme === 'dark') {
                    document.body.classList.add('dark-theme');
                }
            }

            // Initialize notification system
            initNotificationSystem();
        });

        // Notification System
        let notificationDropdownOpen = false;
        let notificationsLoaded = false;

        function initNotificationSystem() {
            // Check if notification bell exists (not all pages have it)
            const notificationBell = document.getElementById('notification-bell');
            if (!notificationBell) {
                return; // Exit if notification system not present on this page
            }

            // Fetch initial unread count
            updateNotificationCount();

            // Auto-refresh every 60 seconds
            setInterval(updateNotificationCount, 60000);

            // Toggle dropdown on bell click
            notificationBell.addEventListener('click', toggleNotificationDropdown);

            // Mark all read button
            const markAllBtn = document.getElementById('mark-all-read-btn');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', markAllNotificationsRead);
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const container = document.querySelector('.notification-container');
                if (!container.contains(e.target) && notificationDropdownOpen) {
                    closeNotificationDropdown();
                }
            });
        }

        async function updateNotificationCount() {
            try {
                const response = await fetch('notifications_api.php?action=get_unread_count', {
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch notification count');
                }

                const data = await response.json();

                if (data.success) {
                    const badge = document.getElementById('notification-badge');
                    const markAllBtn = document.getElementById('mark-all-read-btn');

                    // Only update if badge element exists
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                            badge.style.display = 'block';
                            if (markAllBtn) markAllBtn.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                            if (markAllBtn) markAllBtn.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching notification count:', error);
            }
        }

        async function toggleNotificationDropdown() {
            if (notificationDropdownOpen) {
                closeNotificationDropdown();
            } else {
                openNotificationDropdown();
            }
        }

        async function openNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.add('show');
            notificationDropdownOpen = true;

            // Load notifications if not already loaded
            if (!notificationsLoaded) {
                await loadNotifications();
            }
        }

        function closeNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.remove('show');
            notificationDropdownOpen = false;
        }

        async function loadNotifications() {
            const listContainer = document.getElementById('notification-list');
            listContainer.innerHTML = '<div class="notification-loading">Loading notifications...</div>';

            try {
                const response = await fetch('notifications_api.php?action=get_notifications&per_page=10', {
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch notifications');
                }

                const data = await response.json();

                if (data.success) {
                    notificationsLoaded = true;
                    renderNotifications(data.notifications);
                } else {
                    listContainer.innerHTML = '<div class="notification-empty">Failed to load notifications</div>';
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                listContainer.innerHTML = '<div class="notification-empty">Error loading notifications</div>';
            }
        }

        function renderNotifications(notifications) {
            const listContainer = document.getElementById('notification-list');

            if (notifications.length === 0) {
                listContainer.innerHTML = '<div class="notification-empty">No notifications</div>';
                return;
            }

            listContainer.innerHTML = '';

            notifications.forEach(notification => {
                const item = document.createElement('div');
                item.className = `notification-item ${!notification.is_read ? 'unread' : ''} priority-${notification.priority}`;
                item.dataset.notificationId = notification.id;

                const timeAgo = getTimeAgo(notification.created_at);

                item.innerHTML = `
                    <div class="notification-title">${escapeHtml(notification.title)}</div>
                    <div class="notification-message">${escapeHtml(notification.message)}</div>
                    <div class="notification-footer">
                        <span class="notification-time">${timeAgo}</span>
                        ${notification.action_url ? `<a href="${escapeHtml(notification.action_url)}" class="notification-action">${escapeHtml(notification.action_text || 'View')}</a>` : ''}
                    </div>
                `;

                item.addEventListener('click', function(e) {
                    // If clicking the action link, let it navigate naturally
                    if (e.target.classList.contains('notification-action')) {
                        markNotificationRead(notification.id);
                        return;
                    }

                    // Otherwise, mark as read and navigate if there's an action URL
                    markNotificationRead(notification.id);
                    if (notification.action_url) {
                        window.location.href = notification.action_url;
                    }
                });

                listContainer.appendChild(item);
            });
        }

        async function markNotificationRead(notificationId) {
            try {
                const response = await fetch('notifications_api.php?action=mark_read', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ notification_id: notificationId })
                });

                if (response.ok) {
                    // Update UI
                    const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                    }

                    // Update count
                    updateNotificationCount();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        async function markAllNotificationsRead() {
            try {
                const response = await fetch('notifications_api.php?action=mark_all_read', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.ok) {
                    // Remove unread class from all items
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });

                    // Update count
                    updateNotificationCount();

                    // Reload notifications
                    notificationsLoaded = false;
                    await loadNotifications();
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        }

        function getTimeAgo(dateString) {
            const now = new Date();
            const past = new Date(dateString);
            const diffMs = now - past;
            const diffSecs = Math.floor(diffMs / 1000);
            const diffMins = Math.floor(diffSecs / 60);
            const diffHours = Math.floor(diffMins / 60);
            const diffDays = Math.floor(diffHours / 24);

            if (diffSecs < 60) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;

            return past.toLocaleDateString();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <!-- Shared JavaScript Utilities -->
    <script src="includes/scripts.js"></script>
</head>
<body>
    <?php
    // Display migration warning if version mismatch detected
    if (isset($GLOBALS['version_info'])) {
        display_migration_warning($GLOBALS['version_info']);
    }
    ?>

    <header class="admin-header">
        <div class="header-container">
            <div class="admin-logo">
                <?php echo $_ENV['APP_NAME'] ?? 'Admin Panel'; ?>
            </div>
            
            <nav class="admin-nav">
                <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="user_profile.php" class="nav-link <?php echo $current_page === 'user_profile.php' ? 'active' : ''; ?>">👤 My Profile</a>

                <!-- Alliances Dropdown -->
                <?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['alliance_edit.php', 'alliances_power.php', 'alliance_tags_manager.php']) ? 'active' : ''; ?>">
                        Alliances
                    </div>
                    <div class="nav-dropdown-menu">
                        <a href="alliance_edit.php" class="nav-link <?php echo $current_page === 'alliance_edit.php' ? 'active' : ''; ?>">Editor</a>
                        <?php if ($user->aud === 'admin' || (function_exists('is_power_editor') && is_power_editor($user))): ?>
                        <a href="alliances_power.php" class="nav-link <?php echo $current_page === 'alliances_power.php' ? 'active' : ''; ?>">Power Editor</a>
                        <?php endif; ?>
                        <?php if ($user->aud === 'admin'): ?>
                        <a href="alliance_tags_manager.php" class="nav-link <?php echo $current_page === 'alliance_tags_manager.php' ? 'active' : ''; ?>">Tag Manager</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Server Management Dropdown (Governance & Voting) -->
                <?php if (has_role($user, ['admin', 'r5', 'r4', 'president', 'ape'])): ?>
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['council_rotation.php', 'discord_vote_proposals.php', 'president_vote_approvals.php', 'votes_management.php']) ? 'active' : ''; ?>">
                        🏛️ Server Management
                    </div>
                    <div class="nav-dropdown-menu">
                        <?php if (has_role($user, ['admin', 'president'])): ?>
                        <a href="council_rotation.php" class="nav-link <?php echo $current_page === 'council_rotation.php' ? 'active' : ''; ?>">Council Rotation</a>
                        <?php endif; ?>
                        <a href="discord_vote_proposals.php" class="nav-link <?php echo $current_page === 'discord_vote_proposals.php' ? 'active' : ''; ?>">Council Proposals</a>
                        <?php if (has_role($user, ['admin', 'president'])): ?>
                        <a href="president_vote_approvals.php" class="nav-link <?php echo $current_page === 'president_vote_approvals.php' ? 'active' : ''; ?>">Vote Approvals</a>
                        <a href="votes_management.php" class="nav-link <?php echo $current_page === 'votes_management.php' ? 'active' : ''; ?>">Votes History</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Discord Dropdown (Bot Management Only) -->
                <?php if (defined('DISCORD_ENABLED') && DISCORD_ENABLED && has_role($user, ['admin', 'r5', 'r4', 'president', 'ape'])): ?>
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['discord_announcements.php', 'discord_scheduled.php', 'discord_recurring.php', 'discord_templates.php', 'discord_channels.php', 'discord_rate_limits.php', 'discord_config.php']) ? 'active' : ''; ?>">
                        Discord
                    </div>
                    <div class="nav-dropdown-menu">
                        <a href="discord_announcements.php" class="nav-link <?php echo $current_page === 'discord_announcements.php' ? 'active' : ''; ?>">Announcements</a>
                        <a href="discord_scheduled.php" class="nav-link <?php echo $current_page === 'discord_scheduled.php' ? 'active' : ''; ?>">Scheduled Messages</a>
                        <a href="discord_recurring.php" class="nav-link <?php echo $current_page === 'discord_recurring.php' ? 'active' : ''; ?>">Recurring Messages</a>
                        <a href="discord_templates.php" class="nav-link <?php echo $current_page === 'discord_templates.php' ? 'active' : ''; ?>">Message Templates</a>
                        <?php if ($user->aud === 'admin'): ?>
                        <a href="discord_channels.php" class="nav-link <?php echo $current_page === 'discord_channels.php' ? 'active' : ''; ?>">Channels</a>
                        <a href="discord_rate_limits.php" class="nav-link <?php echo $current_page === 'discord_rate_limits.php' ? 'active' : ''; ?>">Rate Limits</a>
                        <a href="discord_config.php" class="nav-link <?php echo $current_page === 'discord_config.php' ? 'active' : ''; ?>">Configuration</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Season 2 Dropdown -->
                <?php if (has_role($user, ['admin', 'r5', 'r4', 'president'])): ?>
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['season2_manager.php']) ? 'active' : ''; ?>">
                        ❄️ Season 2
                    </div>
                    <div class="nav-dropdown-menu">
                        <a href="season2_manager.php" class="nav-link <?php echo $current_page === 'season2_manager.php' ? 'active' : ''; ?>">Event Calendar</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($user->aud === 'admin'): ?>
                <!-- Users Dropdown -->
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['user_management.php', 'generate_magic_link.php', 'send_magic_link.php']) ? 'active' : ''; ?>">
                        Users
                    </div>
                    <div class="nav-dropdown-menu">
                        <a href="user_management.php" class="nav-link <?php echo $current_page === 'user_management.php' ? 'active' : ''; ?>">Manage Users</a>
                        <a href="generate_magic_link.php" class="nav-link <?php echo $current_page === 'generate_magic_link.php' ? 'active' : ''; ?>">Magic Links</a>
                        <a href="send_magic_link.php" class="nav-link <?php echo $current_page === 'send_magic_link.php' ? 'active' : ''; ?>">Send Login Link</a>
                    </div>
                </div>

                <!-- Security Dropdown -->
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['security_monitor.php', 'security_audit.php', 'audit_log_viewer.php', 'security_keys.php', 'security_mfa.php', 'security_backups.php']) ? 'active' : ''; ?>">
                        Security
                    </div>
                    <div class="nav-dropdown-menu">
                        <a href="security_monitor.php" class="nav-link <?php echo $current_page === 'security_monitor.php' ? 'active' : ''; ?>">Monitor</a>
                        <a href="security_audit.php" class="nav-link <?php echo $current_page === 'security_audit.php' || $current_page === 'audit_log_viewer.php' ? 'active' : ''; ?>">Audit Logs</a>
                        <a href="security_keys.php" class="nav-link <?php echo $current_page === 'security_keys.php' ? 'active' : ''; ?>">JWT Keys</a>
                        <a href="security_mfa.php" class="nav-link <?php echo $current_page === 'security_mfa.php' ? 'active' : ''; ?>">MFA</a>
                        <a href="security_backups.php" class="nav-link <?php echo $current_page === 'security_backups.php' ? 'active' : ''; ?>">Backups</a>
                    </div>
                </div>

                <!-- System Dropdown -->
                <div class="nav-dropdown">
                    <div class="nav-link nav-dropdown-trigger <?php echo in_array($current_page, ['migrate.php', 'metrics_dashboard.php', 'changelog.php', 'notifications.php']) ? 'active' : ''; ?>">
                        ⚙️ System
                    </div>
                    <div class="nav-dropdown-menu">
                        <a href="migrate.php" class="nav-link <?php echo $current_page === 'migrate.php' ? 'active' : ''; ?>">Migration</a>
                        <a href="metrics_dashboard.php" class="nav-link <?php echo $current_page === 'metrics_dashboard.php' ? 'active' : ''; ?>">Metrics</a>
                        <a href="notifications.php" class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">Notifications</a>
                        <a href="changelog.php" class="nav-link <?php echo $current_page === 'changelog.php' ? 'active' : ''; ?>">Changelog</a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>

            <!-- Notification Bell -->
            <div class="notification-container">
                <button class="notification-bell" id="notification-bell" aria-label="Notifications">
                    🔔
                    <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                </button>

                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-dropdown-header">
                        <h3>Notifications</h3>
                        <button class="mark-all-read-btn" id="mark-all-read-btn" style="display: none;">Mark all read</button>
                    </div>
                    <div class="notification-list" id="notification-list">
                        <div class="notification-loading">Loading notifications...</div>
                    </div>
                    <div class="notification-dropdown-footer">
                        <a href="notifications.php" class="view-all-link">View All Notifications</a>
                    </div>
                </div>
            </div>

            <div class="theme-toggle-container">
                <div class="toggle-switch">
                    <input type="checkbox" id="theme-toggle" onchange="toggleTheme()">
                    <label for="theme-toggle" class="toggle-slider">
                        <span class="toggle-option toggle-light">☀️</span>
                        <span class="toggle-option toggle-dark">🌙</span>
                    </label>
                </div>
            </div>

            <div class="user-info">
                <span class="user-welcome">Welcome back, <?php echo htmlspecialchars(get_user_display_name_from_token($user)); ?></span>
                <span class="user-role">
                    <?php
                    $user_roles = get_user_roles($user);
                    $primary_role = $user_roles[0] ?? 'user';
                    echo strtoupper($primary_role);

                    // Show badges for special roles
                    foreach ($user_roles as $role):
                        if ($role === 'ape'): ?>
                            <span style="background: #ffc107; color: #212529; padding: 0.1rem 0.3rem; border-radius: 4px; font-size: 0.6rem; margin-left: 0.25rem;">APE</span>
                        <?php elseif ($role === 'president'): ?>
                            <span style="background: #16a085; color: white; padding: 0.1rem 0.3rem; border-radius: 4px; font-size: 0.6rem; margin-left: 0.25rem;">PRESIDENT</span>
                        <?php endif;
                    endforeach;
                    ?>
                </span>
                <a href="logout.php" class="logout-btn">
                    Logout <span class="token-countdown" id="token-countdown"></span>
                </a>
            </div>
        </div>
    </header>
    
    <main class="main-container">