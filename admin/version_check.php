<?php
/**
 * Version Check Utility
 *
 * Automatically checks version on every page load and triggers migration if needed.
 * Include this file in config.php to enable automatic migration detection.
 *
 * Documentation:
 * - Deployment Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/DEPLOYMENT.md
 * - Migration System: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/migrate.php
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-19
 */

if (!defined('ADMIN_INIT')) {
    http_response_code(403);
    die('Direct access not permitted');
}

/**
 * Check if migration is needed
 *
 * @return array ['needed' => bool, 'current' => string, 'installed' => string]
 */
function check_version_migration_needed() {
    $version_file = __DIR__ . '/../version.json';
    $installed_file = __DIR__ . '/.installed_version';

    // Get current code version
    $current_version = '0.0.0';
    if (file_exists($version_file)) {
        $version_data = json_decode(file_get_contents($version_file), true);
        $current_version = $version_data['version'] ?? '0.0.0';
    }

    // Get installed version
    $installed_version = '0.0.0';
    if (file_exists($installed_file)) {
        $installed_version = trim(file_get_contents($installed_file));
    }

    $comparison = version_compare($current_version, $installed_version);

    return [
        'needed' => $comparison !== 0,
        'current' => $current_version,
        'installed' => $installed_version,
        'is_upgrade' => $comparison > 0,
        'is_rollback' => $comparison < 0
    ];
}

/**
 * Display migration warning banner (for admin pages)
 *
 * @param array $version_info Result from check_version_migration_needed()
 */
function display_migration_warning($version_info) {
    if (!$version_info['needed']) {
        return;
    }

    $type = $version_info['is_upgrade'] ? 'upgrade' : 'rollback';
    $icon = $version_info['is_upgrade'] ? '⬆️' : '⬇️';
    $color = $version_info['is_upgrade'] ? '#ff9800' : '#f44336';

    echo <<<HTML
<div id="migration-warning" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, {$color} 0%, darken({$color}, 10%) 100%);
    color: white;
    padding: 1rem 2rem;
    text-align: center;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="flex: 1; text-align: left;">
            <strong style="font-size: 1.1rem;">{$icon} Migration Required</strong><br>
            <span style="font-size: 0.9rem; opacity: 0.95;">
                Code version: <strong>{$version_info['current']}</strong> |
                Installed: <strong>{$version_info['installed']}</strong> |
                Action: <strong>{$type}</strong>
            </span>
        </div>
        <div>
            <a href="/admin/migrate.php" style="
                background: white;
                color: {$color};
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                display: inline-block;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.3)';"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.2)';">
                🔧 Run Migration Now
            </a>
        </div>
    </div>
</div>
<script>
// Add padding to body to account for fixed warning banner
document.addEventListener('DOMContentLoaded', function() {
    document.body.style.paddingTop = '80px';
});
</script>
HTML;
}

/**
 * Auto-run migration if in CLI mode or if explicitly requested
 *
 * @param bool $auto_migrate If true, automatically runs migration (use carefully!)
 */
function auto_migrate_if_needed($auto_migrate = false) {
    $version_info = check_version_migration_needed();

    if (!$version_info['needed']) {
        return;
    }

    // Only auto-migrate in CLI mode or if explicitly enabled
    if ($auto_migrate || php_sapi_name() === 'cli') {
        error_log("Auto-migration triggered: {$version_info['installed']} → {$version_info['current']}");

        // Run migration
        require_once __DIR__ . '/migrate.php';
        exit; // Exit after migration completes
    }
}

// Check version on every include (but don't auto-migrate unless explicitly called)
$version_check = check_version_migration_needed();

// Store in global for easy access
$GLOBALS['version_info'] = $version_check;
?>
