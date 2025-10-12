<?php
/**
 * Cron Cleanup Script - Remove expired tokens from blacklist
 *
 * Run this periodically (e.g., daily) via cron:
 * 0 2 * * * /usr/bin/php /path/to/admin/cron.php
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

echo "Running blacklist cleanup...\n";

try {
    $removed = cleanup_blacklist();
    echo "Cleanup complete. Removed $removed expired tokens.\n";
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
?>
