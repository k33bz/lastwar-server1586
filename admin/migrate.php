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
require_once __DIR__ . '/audit_logger.php';

// Get user email for audit logging
$admin_email = 'cli_user';

// CLI mode doesn't need JWT, web mode does
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/jwt.php';
    $user = require_jwt_session();

    // Only admins can run migrations via web
    if ($user->aud !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Admin access required for migrations']));
    }

    $admin_email = $user->sub;
}

class MigrationManager {
    private $version_file;
    private $data_dir;
    private $admin_dir;
    private $migrations_run = [];
    private $errors = [];
    private $admin_email;

    public function __construct($admin_email = 'system') {
        $this->version_file = __DIR__ . '/../version.json';
        $this->data_dir = __DIR__ . '/../data/';
        $this->admin_dir = __DIR__ . '/';
        $this->admin_email = $admin_email;
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

            // Log rollback detection
            log_audit_event('migration_rollback_detected', $this->admin_email, [
                'from_version' => $current_version,
                'to_version' => $installed_version,
                'direction' => 'downgrade'
            ]);
        }

        $this->log("🔄 Migration needed: {$installed_version} → {$current_version}");
        $this->log("");

        // Log migration start
        log_audit_event('migration_started', $this->admin_email, [
            'from_version' => $installed_version,
            'to_version' => $current_version,
            'mode' => php_sapi_name() === 'cli' ? 'CLI' : 'Web'
        ]);

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

            // Log migration failure
            log_audit_event('migration_failed', $this->admin_email, [
                'from_version' => $installed_version,
                'to_version' => $current_version,
                'migrations_run' => $this->migrations_run,
                'error_count' => count($this->errors),
                'errors' => $this->errors
            ]);

            return ['success' => false, 'migrations' => $this->migrations_run, 'errors' => $this->errors];
        }

        $this->log("");
        $this->log("✅ Migration completed successfully!");

        // Log migration completion
        log_audit_event('migration_completed', $this->admin_email, [
            'from_version' => $installed_version,
            'to_version' => $current_version,
            'migrations_run' => $this->migrations_run,
            'migration_count' => count($this->migrations_run)
        ]);

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
            '3.4.0' => 'migrateToV3_4',    // Multi-role system (roles array)
            // Add future migrations here
        ];

        foreach ($migrations as $version => $method) {
            // Run migration if: from_version < version <= to_version
            if ($this->compareVersions($from_version, $version) < 0 &&
                $this->compareVersions($version, $to_version) <= 0) {

                $this->log("🔧 Running migration: {$version}");

                try {
                    // Log individual migration start
                    log_audit_event('migration_step_started', $this->admin_email, [
                        'migration_version' => $version,
                        'migration_name' => $method
                    ]);

                    $this->$method();
                    $this->migrations_run[] = $version;
                    $this->log("   ✅ Completed: {$version}");

                    // Log individual migration completion
                    log_audit_event('migration_step_completed', $this->admin_email, [
                        'migration_version' => $version,
                        'migration_name' => $method
                    ]);
                } catch (Exception $e) {
                    $error = "Migration {$version} failed: " . $e->getMessage();
                    $this->errors[] = $error;
                    $this->log("   ❌ Failed: {$error}");

                    // Log individual migration failure
                    log_audit_event('migration_step_failed', $this->admin_email, [
                        'migration_version' => $version,
                        'migration_name' => $method,
                        'error' => $e->getMessage()
                    ]);
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
     * Migration: v3.4.0 - Multi-Role System
     */
    private function migrateToV3_4() {
        $this->log("   - Migrating users to multi-role system...");

        require_once $this->admin_dir . 'json_helpers.php';

        $users_file = $this->admin_dir . 'users.json';
        if (!file_exists($users_file)) {
            throw new Exception("users.json not found");
        }

        // Read current users
        $users_data = json_decode(file_get_contents($users_file), true);

        if (!isset($users_data['users']) || !is_array($users_data['users'])) {
            throw new Exception("Invalid users.json format");
        }

        $total_users = count($users_data['users']);
        $migrated_count = 0;
        $skipped_count = 0;

        $this->log("   - Found {$total_users} user(s)");

        // Create backup before migration
        $this->backupFile($users_file);

        foreach ($users_data['users'] as $index => &$user) {
            $email = $user['email'] ?? 'unknown';

            // Check if already migrated (has roles array)
            if (isset($user['roles']) && is_array($user['roles'])) {
                $skipped_count++;
                continue;
            }

            // Check if has old format fields
            if (!isset($user['role'])) {
                $this->log("   ⚠️  {$email} - Missing role field, skipping");
                continue;
            }

            // Convert old format to new format
            $old_role = $user['role'];
            $old_powereditor = $user['powereditor'] ?? false;

            // Build roles array
            $new_roles = [$old_role];
            if ($old_powereditor === true) {
                $new_roles[] = 'ape';
            }

            // Update user record
            $user['roles'] = $new_roles;

            // Remove old format fields
            unset($user['role']);
            unset($user['powereditor']);

            $migrated_count++;
        }
        unset($user); // Break reference

        // Save updated data
        file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT));

        $this->log("   ✓ Migrated {$migrated_count} user(s) to multi-role format");
        if ($skipped_count > 0) {
            $this->log("   ℹ️  Skipped {$skipped_count} user(s) (already migrated)");
        }
        $this->log("   ✓ Users can now have multiple simultaneous roles");
        $this->log("   ✓ APE role can be assigned independently");
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
$manager = new MigrationManager($admin_email);

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
