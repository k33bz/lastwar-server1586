<?php
/**
 * Data Migration Script: Convert Users to Multi-Role Format
 *
 * Converts users from old format (role + powereditor) to new format (roles array)
 *
 * Old format: {email, alliances, role: "r4", powereditor: true}
 * New format: {email, alliances, roles: ["r4", "ape"]}
 *
 * Usage: php admin/migrate_to_multi_role.php
 *
 * @version 1.0.0
 * @date 2025-10-31
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

echo "===========================================\n";
echo "Multi-Role Migration Script\n";
echo "===========================================\n\n";

try {
    // Read current users data
    echo "Reading users.json...\n";
    $users_data = read_json_file(USERS_FILE);

    if (!isset($users_data['users']) || !is_array($users_data['users'])) {
        throw new Exception("Invalid users.json format");
    }

    $total_users = count($users_data['users']);
    $migrated_count = 0;
    $skipped_count = 0;
    $errors = [];

    echo "Found {$total_users} users\n\n";

    // Migrate each user
    foreach ($users_data['users'] as $index => &$user) {
        $email = $user['email'] ?? 'unknown';

        // Check if already migrated (has roles array)
        if (isset($user['roles']) && is_array($user['roles'])) {
            echo "[SKIP] {$email} - Already using new format (roles: " . implode(', ', $user['roles']) . ")\n";
            $skipped_count++;
            continue;
        }

        // Check if has old format fields
        if (!isset($user['role'])) {
            echo "[ERROR] {$email} - Missing role field\n";
            $errors[] = $email;
            continue;
        }

        // Convert old format to new format
        $old_role = $user['role'];
        $old_powereditor = $user['powereditor'] ?? false;

        // Build roles array
        $new_roles = [$old_role];

        // Add APE role if powereditor was true
        if ($old_powereditor === true) {
            $new_roles[] = 'ape';
        }

        // Update user record
        $user['roles'] = $new_roles;

        // Remove old format fields
        unset($user['role']);
        unset($user['powereditor']);

        echo "[MIGRATE] {$email}\n";
        echo "  Old: role={$old_role}, powereditor=" . ($old_powereditor ? 'true' : 'false') . "\n";
        echo "  New: roles=[" . implode(', ', $new_roles) . "]\n";

        $migrated_count++;
    }
    unset($user); // Break reference

    // Show summary
    echo "\n===========================================\n";
    echo "Migration Summary\n";
    echo "===========================================\n";
    echo "Total users:    {$total_users}\n";
    echo "Migrated:       {$migrated_count}\n";
    echo "Skipped:        {$skipped_count}\n";
    echo "Errors:         " . count($errors) . "\n";

    if (count($errors) > 0) {
        echo "\nUsers with errors:\n";
        foreach ($errors as $error_email) {
            echo "  - {$error_email}\n";
        }
    }

    // Ask for confirmation before saving
    if ($migrated_count > 0) {
        echo "\n===========================================\n";
        echo "Ready to save changes to users.json\n";
        echo "===========================================\n";
        echo "This will modify {$migrated_count} user(s).\n";
        echo "A backup will be created at: " . USERS_FILE . ".backup\n\n";
        echo "Proceed? (yes/no): ";

        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'yes') {
            echo "\nMigration cancelled. No changes were made.\n";
            exit(0);
        }

        // Create backup
        echo "\nCreating backup...\n";
        $backup_file = USERS_FILE . '.backup';
        copy(USERS_FILE, $backup_file);
        echo "Backup created: {$backup_file}\n";

        // Write updated data
        echo "Writing updated users.json...\n";
        write_json_file(USERS_FILE, $users_data);

        echo "\n✓ Migration completed successfully!\n";
        echo "  {$migrated_count} user(s) migrated to multi-role format\n";
        echo "  Backup saved to: {$backup_file}\n\n";
    } else {
        echo "\nNo users needed migration. No changes made.\n";
    }

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
