<?php
/**
 * Admin Panel Shared Header
 * Version: 1.3.0
 * Provides consistent navigation and security checks
 *
 * Changelog:
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
    <style>
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
    </style>
    
    <script>
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
                    window.location.href = '../logout.php';
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
        });
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
                <?php endif; ?>
            </nav>

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
                <a href="../logout.php" class="logout-btn">
                    Logout <span class="token-countdown" id="token-countdown"></span>
                </a>
            </div>
        </div>
    </header>
    
    <main class="main-container">