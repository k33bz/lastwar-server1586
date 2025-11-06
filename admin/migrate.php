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

    // Only users with admin role can run migrations via web
    if (!has_role($user, 'admin')) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 Forbidden</h1><p>Admin role required for migrations.</p></body></html>');
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

        // Record migration in history
        $this->recordMigrationHistory($installed_version, $current_version, $this->migrations_run);

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
            '3.5.0' => 'migrateToV3_5',    // Discord integration
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
     * Migration: v3.5.0 - Discord Integration
     */
    private function migrateToV3_5() {
        $this->log("   - Checking .env for Discord configuration...");

        $env_file = $this->admin_dir . '.env';
        if (!file_exists($env_file)) {
            throw new Exception(".env file not found");
        }

        $env_content = file_get_contents($env_file);
        $missing_vars = [];
        $needs_attention = [];

        // Check required Discord variables
        $required_discord_vars = [
            'DISCORD_BOT_TOKEN' => 'Discord bot authentication token (get from https://discord.com/developers)',
            'DISCORD_CLIENT_ID' => 'Discord application ID',
            'DISCORD_PUBLIC_KEY' => 'Discord application public key',
            'DISCORD_ENABLED' => 'Enable/disable Discord integration (true/false)',
            'DISCORD_RATE_LIMIT_ENABLED' => 'Enable rate limiting (true/false)',
            'DISCORD_MAX_INSTANT_PER_HOUR' => 'Maximum instant messages per hour (default: 10)',
            'DISCORD_MAX_SCHEDULED_PENDING' => 'Maximum scheduled messages (default: 50)',
            'DISCORD_MAX_RECURRING_ACTIVE' => 'Maximum recurring messages (default: 5)'
        ];

        foreach ($required_discord_vars as $var => $description) {
            if (strpos($env_content, $var . '=') === false) {
                $missing_vars[] = "$var - $description";
            } elseif ($var === 'DISCORD_BOT_TOKEN' && strpos($env_content, 'your_discord_bot_token_here') !== false) {
                $needs_attention[] = "$var needs to be updated with actual bot token";
            }
        }

        if (count($missing_vars) > 0) {
            $this->log("   ⚠️  Missing .env variables:");
            foreach ($missing_vars as $var) {
                $this->log("      - $var");
            }
            $this->log("   ℹ️  Please add these to .env manually or copy from .env.example");
            $this->log("   ℹ️  See docs/discord-announcements/BOT-SETUP.md for setup instructions");
        } else {
            $this->log("   ✓ All Discord configuration variables present");
        }

        if (count($needs_attention) > 0) {
            $this->log("   ⚠️  Configuration needs attention:");
            foreach ($needs_attention as $item) {
                $this->log("      - $item");
            }
            $this->log("   ℹ️  Get your Discord bot token from https://discord.com/developers");
            $this->log("   ℹ️  See docs/discord-announcements/BOT-SETUP.md for detailed setup");
        }

        // Initialize discord_rate_limits.json if doesn't exist
        $rate_limits_file = $this->admin_dir . 'discord_rate_limits.json';
        if (!file_exists($rate_limits_file)) {
            $this->log("   - Creating discord_rate_limits.json...");
            file_put_contents($rate_limits_file, json_encode([], JSON_PRETTY_PRINT));
            $this->log("   ✓ discord_rate_limits.json created");
        }

        // Check if composer dependencies are installed (Guzzle for Discord API)
        $vendor_dir = $this->admin_dir . 'vendor';
        if (!file_exists($vendor_dir) || !file_exists($vendor_dir . '/autoload.php')) {
            $this->log("   ⚠️  Composer dependencies not installed");
            $this->log("   ℹ️  Run 'composer install' in admin/ directory");
            $this->log("   ℹ️  Discord integration requires GuzzleHTTP library");
        } else {
            $this->log("   ✓ Composer dependencies installed");
        }

        $this->log("   ✓ Discord integration files ready");
        $this->log("   ℹ️  Configure channels in data/alliances.json under discord.channels");
        $this->log("   ℹ️  Visit /admin/discord_config.php to verify bot connection");
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
     * Update .env file with new variables
     *
     * @param array $variables Key-value pairs to add/update
     * @return bool Success status
     */
    public function updateEnvFile($variables) {
        $env_file = $this->admin_dir . '.env';

        if (!file_exists($env_file)) {
            $this->errors[] = ".env file not found";
            return false;
        }

        // Backup before modifying
        $this->backupFile($env_file);

        $env_content = file_get_contents($env_file);
        $updated = false;

        foreach ($variables as $key => $value) {
            // Check if variable exists
            if (preg_match('/^' . preg_quote($key, '/') . '=/m', $env_content)) {
                // Update existing variable
                $env_content = preg_replace(
                    '/^' . preg_quote($key, '/') . '=.*/m',
                    $key . '=' . $value,
                    $env_content
                );
            } else {
                // Add new variable at the end
                $env_content .= "\n" . $key . '=' . $value;
            }
            $updated = true;
        }

        if ($updated) {
            file_put_contents($env_file, $env_content);
            $this->log("   ✓ Updated .env file with " . count($variables) . " variable(s)");
        }

        return true;
    }

    /**
     * Get missing environment variables for a migration
     *
     * @param string $version Migration version
     * @return array Array of missing variables with descriptions
     */
    public function getMissingEnvVars($version) {
        $env_file = $this->admin_dir . '.env';
        if (!file_exists($env_file)) {
            return [];
        }

        $env_content = file_get_contents($env_file);
        $missing = [];

        // Define required variables per version
        $requirements = [
            '3.5.0' => [
                'DISCORD_BOT_TOKEN' => [
                    'description' => 'Discord bot authentication token',
                    'placeholder' => 'MTQzNTMzNjA3OTQwOTU0NTI1Ng.GorOcC.your_token_here',
                    'required' => true
                ],
                'DISCORD_CLIENT_ID' => [
                    'description' => 'Discord application client ID',
                    'placeholder' => '1435336079409545256',
                    'required' => true
                ],
                'DISCORD_PUBLIC_KEY' => [
                    'description' => 'Discord application public key',
                    'placeholder' => 'your_public_key_here',
                    'required' => true
                ],
                'DISCORD_ENABLED' => [
                    'description' => 'Enable Discord integration',
                    'placeholder' => 'true',
                    'required' => true
                ],
                'DISCORD_RATE_LIMIT_ENABLED' => [
                    'description' => 'Enable Discord rate limiting',
                    'placeholder' => 'true',
                    'required' => false
                ],
                'DISCORD_MAX_INSTANT_PER_HOUR' => [
                    'description' => 'Maximum instant messages per hour',
                    'placeholder' => '10',
                    'required' => false
                ]
            ]
        ];

        if (!isset($requirements[$version])) {
            return [];
        }

        foreach ($requirements[$version] as $var => $info) {
            // Check if variable exists and is not a placeholder
            if (!preg_match('/^' . preg_quote($var, '/') . '=/m', $env_content)) {
                $missing[$var] = $info;
            } elseif (preg_match('/^' . preg_quote($var, '/') . '=(your_.*|.*_here)/m', $env_content)) {
                // Variable exists but has placeholder value
                $info['has_placeholder'] = true;
                $missing[$var] = $info;
            }
        }

        return $missing;
    }

    /**
     * Record migration in history
     *
     * @param string $from_version
     * @param string $to_version
     * @param array $migrations_run
     */
    public function recordMigrationHistory($from_version, $to_version, $migrations_run) {
        $history_file = $this->admin_dir . 'migration_history.json';

        $history = ['migrations' => []];
        if (file_exists($history_file)) {
            $history = json_decode(file_get_contents($history_file), true) ?? ['migrations' => []];
        }

        $history['migrations'][] = [
            'from_version' => $from_version,
            'to_version' => $to_version,
            'migrations_run' => $migrations_run,
            'timestamp' => date('c'),
            'admin_email' => $this->admin_email
        ];

        file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT));
    }

    /**
     * Get migration history
     *
     * @return array Migration history
     */
    public function getMigrationHistory() {
        $history_file = $this->admin_dir . 'migration_history.json';

        if (!file_exists($history_file)) {
            return [];
        }

        $history = json_decode(file_get_contents($history_file), true);
        return $history['migrations'] ?? [];
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

// CLI mode - run migration immediately
if (php_sapi_name() === 'cli') {
    $result = $manager->migrate();
    exit($result['success'] ? 0 : 1);
}

// Web mode - interactive interface
$current_version = $manager->getCurrentVersion();
$installed_version = $manager->getInstalledVersion();
$needs_upgrade = version_compare($current_version, $installed_version, '>');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['env_vars'])) {
    $env_vars = [];
    foreach ($_POST['env_vars'] as $key => $value) {
        if (!empty($value)) {
            $env_vars[$key] = $value;
        }
    }

    if (!empty($env_vars)) {
        $manager->updateEnvFile($env_vars);
        $success_message = "Environment variables updated successfully!";
    }
}

// Handle migration run request
$migration_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    ob_start();
    $migration_result = $manager->migrate();
    ob_end_clean();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Server 1586 Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header .version-info {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .content {
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .section {
            margin-bottom: 2rem;
        }

        .section h2 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .required-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 3px;
            margin-left: 0.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .history-table th,
        .history-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        .migration-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin: 0.1rem;
        }

        .no-history {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .migration-result {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .migration-result h3 {
            color: #28a745;
            margin-bottom: 1rem;
        }

        .migration-result.error h3 {
            color: #dc3545;
        }

        .migration-result pre {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Database Migration</h1>
            <div class="version-info">
                Current Version: <?php echo htmlspecialchars($current_version); ?> |
                Installed Version: <?php echo htmlspecialchars($installed_version); ?>
            </div>
        </div>

        <div class="content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($migration_result): ?>
                <div class="migration-result <?php echo $migration_result['success'] ? '' : 'error'; ?>">
                    <h3><?php echo $migration_result['success'] ? '✓ Migration Completed Successfully!' : '✗ Migration Failed'; ?></h3>
                    <?php if (!empty($migration_result['message'])): ?>
                        <p><?php echo htmlspecialchars($migration_result['message']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($migration_result['migrations_run'])): ?>
                        <p><strong>Migrations applied:</strong></p>
                        <ul>
                            <?php foreach ($migration_result['migrations_run'] as $migration): ?>
                                <li><?php echo htmlspecialchars($migration); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="button-group">
                        <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                        <a href="migrate.php" class="btn btn-secondary">Run Another Migration</a>
                    </div>
                </div>
            <?php elseif ($needs_upgrade): ?>
                <?php
                $missing_vars = $manager->getMissingEnvVars($current_version);
                ?>

                <?php if (!empty($missing_vars)): ?>
                    <div class="section">
                        <h2>🔧 Configuration Required</h2>
                        <div class="alert alert-warning">
                            The migration to version <strong><?php echo htmlspecialchars($current_version); ?></strong> requires
                            additional configuration. Please provide the following environment variables:
                        </div>

                        <form method="POST" action="migrate.php">
                            <?php foreach ($missing_vars as $var => $info): ?>
                                <div class="form-group">
                                    <label>
                                        <?php echo htmlspecialchars($var); ?>
                                        <?php if ($info['required']): ?>
                                            <span class="required-badge">REQUIRED</span>
                                        <?php endif; ?>
                                    </label>
                                    <input
                                        type="<?php echo (strpos($var, 'TOKEN') !== false || strpos($var, 'KEY') !== false) ? 'password' : 'text'; ?>"
                                        name="env_vars[<?php echo htmlspecialchars($var); ?>]"
                                        placeholder="<?php echo htmlspecialchars($info['placeholder']); ?>"
                                        <?php echo $info['required'] ? 'required' : ''; ?>
                                    >
                                    <div class="help-text">
                                        <?php echo htmlspecialchars($info['description']); ?>
                                        <?php if (isset($info['has_placeholder'])): ?>
                                            <br><strong>Note:</strong> Current value appears to be a placeholder. Please update.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="button-group">
                                <button type="submit" class="btn btn-primary">💾 Save Configuration</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="section">
                        <h2>🚀 Ready to Migrate</h2>
                        <div class="alert alert-info">
                            All configuration requirements are met. Click below to migrate from version
                            <strong><?php echo htmlspecialchars($installed_version); ?></strong> to
                            <strong><?php echo htmlspecialchars($current_version); ?></strong>.
                        </div>

                        <form method="POST" action="migrate.php">
                            <input type="hidden" name="run_migration" value="1">
                            <div class="button-group">
                                <button type="submit" class="btn btn-success">▶ Run Migration</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="section">
                    <h2>📜 Migration History</h2>
                    <div class="alert alert-success">
                        Your system is up to date. Current version: <strong><?php echo htmlspecialchars($current_version); ?></strong>
                    </div>

                    <?php
                    $history = $manager->getMigrationHistory();
                    if (!empty($history)):
                    ?>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Migration</th>
                                    <th>Applied By</th>
                                    <th>Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($history) as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($entry['timestamp']))); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($entry['from_version']); ?></strong>
                                            →
                                            <strong><?php echo htmlspecialchars($entry['to_version']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($entry['admin_email']); ?></td>
                                        <td>
                                            <?php foreach ($entry['migrations_run'] as $migration): ?>
                                                <span class="migration-badge"><?php echo htmlspecialchars($migration); ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-history">
                            <p>No migration history recorded yet.</p>
                        </div>
                    <?php endif; ?>

                    <div class="button-group">
                        <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
