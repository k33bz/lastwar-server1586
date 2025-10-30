<?php
/**
 * Version Migration System
 *
 * Automatically upgrades data files and environment configuration when deploying new versions.
 * Checks version.json and applies necessary migrations to bring production up to date.
 *
 * Documentation:
 * - Deployment Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/DEPLOYMENT.md
 * - Version Management: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/VERSION_SUMMARY.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-19
 *
 * Usage:
 * - CLI: php migrate.php
 * - Web: Navigate to /admin/migrate.php (admin access only)
 * - Auto: Called by config.php on version mismatch
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';

// CLI mode doesn't need JWT, web mode does
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/jwt.php';
    $user = require_jwt_session();

    // Only admins can run migrations via web
    if ($user->aud !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Admin access required for migrations']));
    }
}

class MigrationManager {
    private $version_file;
    private $data_dir;
    private $admin_dir;
    private $migrations_run = [];
    private $errors = [];

    public function __construct() {
        $this->version_file = __DIR__ . '/../version.json';
        $this->data_dir = __DIR__ . '/../data/';
        $this->admin_dir = __DIR__ . '/';
    }

    /**
     * Get current deployed version from version.json
     */
    private function getCurrentVersion() {
        if (!file_exists($this->version_file)) {
            return '0.0.0';
        }

        $version_data = json_decode(file_get_contents($this->version_file), true);
        return $version_data['version'] ?? '0.0.0';
    }

    /**
     * Get installed version from tracking file
     */
    private function getInstalledVersion() {
        $installed_file = $this->admin_dir . '.installed_version';

        if (!file_exists($installed_file)) {
            return '0.0.0';
        }

        return trim(file_get_contents($installed_file));
    }

    /**
     * Update installed version tracker
     */
    private function setInstalledVersion($version) {
        $installed_file = $this->admin_dir . '.installed_version';
        file_put_contents($installed_file, $version);
    }

    /**
     * Compare semantic versions
     * Returns: -1 if v1 < v2, 0 if equal, 1 if v1 > v2
     */
    private function compareVersions($v1, $v2) {
        return version_compare($v1, $v2);
    }

    /**
     * Main migration runner
     */
    public function migrate() {
        $current_version = $this->getCurrentVersion();
        $installed_version = $this->getInstalledVersion();

        $this->log("=== Version Migration System ===");
        $this->log("Code version: {$current_version}");
        $this->log("Installed version: {$installed_version}");
        $this->log("");

        // Check if migration needed
        $comparison = $this->compareVersions($current_version, $installed_version);

        if ($comparison === 0) {
            $this->log("✅ No migration needed - versions match");
            return ['success' => true, 'migrations' => [], 'message' => 'Already up to date'];
        }

        if ($comparison < 0) {
            $this->log("⚠️  WARNING: Code version is OLDER than installed version!");
            $this->log("    This might indicate a rollback. Proceed with caution.");
            // Continue anyway - might need to revert schema changes
        }

        $this->log("🔄 Migration needed: {$installed_version} → {$current_version}");
        $this->log("");

        // Run migrations in order
        $this->runMigrations($installed_version, $current_version);

        // Update installed version
        $this->setInstalledVersion($current_version);

        $this->log("");
        $this->log("=== Migration Summary ===");
        $this->log("Migrations run: " . count($this->migrations_run));
        $this->log("Errors: " . count($this->errors));

        if (count($this->errors) > 0) {
            $this->log("");
            $this->log("❌ ERRORS:");
            foreach ($this->errors as $error) {
                $this->log("  - {$error}");
            }
            return ['success' => false, 'migrations' => $this->migrations_run, 'errors' => $this->errors];
        }

        $this->log("");
        $this->log("✅ Migration completed successfully!");

        return ['success' => true, 'migrations' => $this->migrations_run];
    }

    /**
     * Run all necessary migrations between versions
     */
    private function runMigrations($from_version, $to_version) {
        // Define migrations with version thresholds
        // Each migration runs if: installed_version < migration_version <= code_version

        $migrations = [
            '3.0.0' => 'migrateToV3',      // Add JWT authentication fields
            '3.1.0' => 'migrateToV3_1',    // Add alliance tags, R5 history
            '3.2.0' => 'migrateToV3_2',    // Add audit logging
            '3.3.0' => 'migrateToV3_3',    // Add backup/restore support
            // Add future migrations here
        ];

        foreach ($migrations as $version => $method) {
            // Run migration if: from_version < version <= to_version
            if ($this->compareVersions($from_version, $version) < 0 &&
                $this->compareVersions($version, $to_version) <= 0) {

                $this->log("🔧 Running migration: {$version}");

                try {
                    $this->$method();
                    $this->migrations_run[] = $version;
                    $this->log("   ✅ Completed: {$version}");
                } catch (Exception $e) {
                    $error = "Migration {$version} failed: " . $e->getMessage();
                    $this->errors[] = $error;
                    $this->log("   ❌ Failed: {$error}");
                }

                $this->log("");
            }
        }
    }

    /**
     * Migration: v3.0.0 - JWT Authentication
     */
    private function migrateToV3() {
        $this->log("   - Checking .env for JWT configuration...");

        $env_file = $this->admin_dir . '.env';
        if (!file_exists($env_file)) {
            throw new Exception(".env file not found");
        }

        $env_content = file_get_contents($env_file);
        $missing_vars = [];

        // Check required JWT variables
        $required_jwt_vars = [
            'JWT_SECRET_KEY',
            'JWT_ISSUER',
            'JWT_TOKEN_LIFETIME'
        ];

        foreach ($required_jwt_vars as $var) {
            if (strpos($env_content, $var . '=') === false) {
                $missing_vars[] = $var;
            }
        }

        if (count($missing_vars) > 0) {
            $this->log("   ⚠️  Missing .env variables: " . implode(', ', $missing_vars));
            $this->log("   ℹ️  Please add these to .env manually or regenerate from .env.example");
        } else {
            $this->log("   ✓ JWT configuration present");
        }

        // Initialize users.json if doesn't exist
        $users_file = $this->admin_dir . 'users.json';
        if (!file_exists($users_file)) {
            $this->log("   - Creating users.json...");
            file_put_contents($users_file, json_encode([], JSON_PRETTY_PRINT));
            $this->log("   ✓ users.json created");
        }
    }

    /**
     * Migration: v3.1.0 - Alliance Tags & R5 History
     */
    private function migrateToV3_1() {
        $this->log("   - Migrating alliances.json schema...");

        $alliances_file = $this->data_dir . 'alliances.json';
        if (!file_exists($alliances_file)) {
            throw new Exception("alliances.json not found");
        }

        $alliances = json_decode(file_get_contents($alliances_file), true);
        $modified = false;

        foreach ($alliances as &$alliance) {
            // Add r5History if missing
            if (!isset($alliance['r5History'])) {
                $current_r5 = $alliance['r5'] ?? 'Unknown';
                $alliance['r5History'] = [
                    [
                        'name' => $current_r5,
                        'start_date' => date('Y-m-d'),
                        'end_date' => null,
                        'is_current' => true
                    ]
                ];
                $modified = true;
            }
        }

        if ($modified) {
            $this->backupFile($alliances_file);
            file_put_contents($alliances_file, json_encode($alliances, JSON_PRETTY_PRINT));
            $this->log("   ✓ Added r5History to alliances");
        } else {
            $this->log("   ✓ alliances.json already up to date");
        }
    }

    /**
     * Migration: v3.2.0 - Audit Logging
     */
    private function migrateToV3_2() {
        $this->log("   - Setting up audit logging...");

        // Check .env for audit settings
        $env_file = $this->admin_dir . '.env';
        $env_content = file_get_contents($env_file);

        if (strpos($env_content, 'AUDIT_ENABLED') === false) {
            $this->log("   ⚠️  AUDIT_ENABLED not set in .env");
            $this->log("   ℹ️  Add: AUDIT_ENABLED=true");
        }

        // Initialize audit_log.json if doesn't exist
        $audit_file = $this->admin_dir . 'audit_log.json';
        if (!file_exists($audit_file)) {
            $this->log("   - Creating audit_log.json...");
            file_put_contents($audit_file, json_encode([], JSON_PRETTY_PRINT));
            $this->log("   ✓ audit_log.json created");
        }
    }

    /**
     * Migration: v3.3.0 - Backup/Restore Support
     */
    private function migrateToV3_3() {
        $this->log("   - Setting up backup system...");

        // Create backups directory if doesn't exist
        $backups_dir = $this->admin_dir . 'backups/';
        if (!is_dir($backups_dir)) {
            $this->log("   - Creating backups directory...");
            mkdir($backups_dir, 0755, true);
            $this->log("   ✓ backups/ directory created");
        }

        // Add .htaccess to protect backups
        $htaccess_file = $backups_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Deny all access to backup files\nDeny from all\n";
            file_put_contents($htaccess_file, $htaccess_content);
            $this->log("   ✓ Backup directory protected with .htaccess");
        }
    }

    /**
     * Backup a file before modification
     */
    private function backupFile($file_path) {
        $backup_path = $file_path . '.bak.' . date('Y-m-d_His');
        copy($file_path, $backup_path);
        $this->log("   💾 Backup created: " . basename($backup_path));
    }

    /**
     * Log message to console/output
     */
    private function log($message) {
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        } else {
            // Store for web output
            static $logs = [];
            $logs[] = $message;

            // Return logs for web display
            if (strpos($message, '===') !== false && count($logs) > 1) {
                echo '<pre>' . implode("\n", $logs) . '</pre>';
                flush();
                $logs = [];
            }
        }
    }
}

// Run migration
$manager = new MigrationManager();

// For web mode, buffer output to prevent header issues
if (php_sapi_name() !== 'cli') {
    ob_start();
}

$result = $manager->migrate();

// Output result
if (php_sapi_name() === 'cli') {
    exit($result['success'] ? 0 : 1);
} else {
    // Clear buffered output (migration logs)
    ob_end_clean();

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}
?>
