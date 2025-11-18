<?php
/**
 * Data Migration Script: Convert Email-based Identity to UID-based
 *
 * This migration:
 * 1. Generates unique UIDs for all users
 * 2. Adds email_history field for tracking email changes
 * 3. Updates R4 assignments in alliances to reference UIDs
 * 4. Maintains backward compatibility during transition
 *
 * Old format: email is primary identifier
 * New format: uid is primary, email can change with history
 *
 * Usage: php admin/migrate_to_uid.php
 *
 * @version 4.0.0
 * @date 2025-01-18
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

echo "===========================================\n";
echo "UID Migration Script v4.0.0\n";
echo "===========================================\n\n";

try {
    // Step 1: Migrate users.json
    echo "Step 1: Migrating users to UID-based identity...\n";
    $users_data = read_json_file(USERS_FILE);

    if (!isset($users_data['users']) || !is_array($users_data['users'])) {
        throw new Exception("Invalid users.json format");
    }

    $total_users = count($users_data['users']);
    $migrated_count = 0;
    $skipped_count = 0;

    echo "Found {$total_users} users\n";

    // Create email to UID mapping for later use
    $email_to_uid = [];

    foreach ($users_data['users'] as $index => &$user) {
        $email = $user['email'] ?? 'unknown';

        // Check if already has UID
        if (isset($user['uid'])) {
            echo "  ⏭ Skipped: {$email} (already has UID: {$user['uid']})\n";
            $email_to_uid[$email] = $user['uid'];
            $skipped_count++;
            continue;
        }

        // Generate new UID
        $user['uid'] = 'usr_' . bin2hex(random_bytes(8));

        // Add email_history if not present
        if (!isset($user['email_history'])) {
            $user['email_history'] = [];
        }

        $email_to_uid[$email] = $user['uid'];
        $migrated_count++;

        echo "  ✓ Migrated: {$email} → {$user['uid']}\n";
    }

    // Save updated users
    write_json_file(USERS_FILE, $users_data);

    echo "\nUser migration complete:\n";
    echo "  • Migrated: {$migrated_count}\n";
    echo "  • Skipped: {$skipped_count}\n";
    echo "  • Total: {$total_users}\n\n";

    // Step 2: Migrate alliances.json R4 references
    echo "Step 2: Migrating R4 assignments in alliances...\n";

    $alliances_file = __DIR__ . '/../data/alliances.json';
    if (!file_exists($alliances_file)) {
        echo "  ⚠ alliances.json not found, skipping alliance migration\n\n";
    } else {
        $alliances = json_decode(file_get_contents($alliances_file), true);
        $r4_migrated = 0;
        $r4_skipped = 0;

        foreach ($alliances as &$alliance) {
            if (!isset($alliance['r4s']) || !is_array($alliance['r4s'])) {
                continue;
            }

            foreach ($alliance['r4s'] as &$r4) {
                // Check if already has user_uid
                if (isset($r4['user_uid'])) {
                    $r4_skipped++;
                    continue;
                }

                // If has email, try to map to UID
                if (isset($r4['email']) && isset($email_to_uid[$r4['email']])) {
                    $r4['user_uid'] = $email_to_uid[$r4['email']];
                    $r4_migrated++;
                    echo "  ✓ Mapped R4 {$r4['name']} ({$r4['email']}) → {$r4['user_uid']}\n";
                } else {
                    // No email or couldn't map - this R4 isn't linked to a user account
                    echo "  ⚠ R4 {$r4['name']} has no user account link\n";
                }
            }
        }

        file_put_contents($alliances_file, json_encode($alliances, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        echo "\nR4 assignment migration complete:\n";
        echo "  • Migrated: {$r4_migrated}\n";
        echo "  • Skipped: {$r4_skipped}\n\n";
    }

    echo "===========================================\n";
    echo "✓ Migration completed successfully!\n";
    echo "===========================================\n\n";

    echo "Next steps:\n";
    echo "1. Update JWT system to use UID as subject\n";
    echo "2. Update authentication functions\n";
    echo "3. Update magic link system\n";
    echo "4. Test login flow\n";
    echo "5. Deploy email change functionality\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Migration failed. Please review and try again.\n";
    exit(1);
}
