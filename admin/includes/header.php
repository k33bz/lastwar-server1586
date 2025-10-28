<?php
/**
 * Admin Panel Shared Header
 * Version: 1.2.0
 * Provides consistent navigation and security checks
 *
 * Changelog:
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
        
        .email-mask-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .email-mask-toggle:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
        }
        
        .email-text, .email-masked {
            font-family: inherit;
            font-weight: 500;
            font-style: normal;
            font-size: inherit;
            line-height: inherit;
            letter-spacing: inherit;
        }
        
        .email-text {
            color: #333;
        }
        
        .email-masked {
            color: #666;
        }
        
        .email-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
            margin-left: 4px;
            border-radius: 3px;
            transition: all 0.2s ease;
            opacity: 0.6;
        }
        
        .email-toggle-btn:hover {
            background: #f0f0f0;
            opacity: 1;
        }
        
        .email-toggle-btn svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
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
            
            .user-info {
                order: 1;
                justify-content: center;
            }
            
            .main-container {
                padding: 0 1rem;
            }
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
        
        // Email masking functionality
        function toggleSingleEmail(button) {
            var emailSpan = button.previousElementSibling;
            var isShowingMasked = emailSpan.classList.contains('email-masked');
            
            if (isShowingMasked) {
                // Show full email
                emailSpan.textContent = emailSpan.dataset.email;
                emailSpan.classList.remove('email-masked');
                button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                button.title = 'Hide email';
            } else {
                // Show masked email
                emailSpan.textContent = emailSpan.dataset.masked;
                emailSpan.classList.add('email-masked');
                button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                button.title = 'Show email';
            }
        }
        
        function toggleAllEmails() {
            var elements = document.querySelectorAll('.email-text[data-email]');
            var anyMasked = false;
            
            // Check if any emails are currently masked
            elements.forEach(function(el) {
                if (el.classList.contains('email-masked')) {
                    anyMasked = true;
                }
            });
            
            // Toggle all emails to the opposite state
            elements.forEach(function(el) {
                var toggleBtn = el.nextElementSibling;
                if (toggleBtn && toggleBtn.classList.contains('email-toggle-btn')) {
                    if (anyMasked) {
                        // Show all
                        el.textContent = el.dataset.email;
                        el.classList.remove('email-masked');
                        toggleBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                        toggleBtn.title = 'Hide email';
                    } else {
                        // Hide all
                        el.textContent = el.dataset.masked;
                        el.classList.add('email-masked');
                        toggleBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                        toggleBtn.title = 'Show email';
                    }
                }
            });
            
            // Update global toggle button
            var globalBtn = document.getElementById('global-email-toggle');
            if (globalBtn) {
                if (anyMasked) {
                    globalBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                    globalBtn.title = 'Hide all emails';
                } else {
                    globalBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                    globalBtn.title = 'Show all emails';
                }
            }
        }
        
        window.addEventListener('load', function() {
            try {
                // Create global toggle button
                var toggleBtn = document.createElement('button');
                toggleBtn.id = 'global-email-toggle';
                toggleBtn.className = 'email-mask-toggle';
                toggleBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
                toggleBtn.title = 'Toggle all email visibility';
                toggleBtn.onclick = toggleAllEmails;
                document.body.appendChild(toggleBtn);
            } catch (e) {
                // Silently fail if there are issues
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

                <!-- Alliances Dropdown -->
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
            
            <div class="user-info">
                <span class="user-welcome"><?php echo emailDisplay($user->sub ?? 'User', true); ?></span>
                <span class="user-role">
                    <?php echo strtoupper($user->aud ?? 'USER'); ?>
                    <?php if (($user->aud === 'r4' || $user->aud === 'r5') && isset($user->powereditor) && $user->powereditor): ?>
                        <span style="background: #ffc107; color: #212529; padding: 0.1rem 0.3rem; border-radius: 4px; font-size: 0.6rem; margin-left: 0.25rem;">APE</span>
                    <?php endif; ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    Logout <span class="token-countdown" id="token-countdown"></span>
                </a>
            </div>
        </div>
    </header>
    
    <main class="main-container">