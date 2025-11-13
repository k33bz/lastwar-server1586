<?php
/**
 * Migrate existing single-server data to multi-server format
 *
 * This script adds the 'server' field to all existing records,
 * defaulting to '1586' to maintain backwards compatibility.
 *
 * Usage: php migrate_to_multiserver.php [--dry-run] [--server=1586]
 */

$dryRun = in_array('--dry-run', $argv);
$defaultServer = '1586';

// Parse --server argument
foreach ($argv as $arg) {
    if (strpos($arg, '--server=') === 0) {
        $defaultServer = substr($arg, 9);
    }
}

echo "Multi-Server Migration Script\n";
echo "==============================\n";
echo "Default server: {$defaultServer}\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE (will modify files)") . "\n\n";

// Files to migrate with their record path
$migrations = [
    'data/alliances.json' => '',  // Root level array
    'data/users.json' => 'users',
    'data/discord-votes.json' => 'votes',
    'data/discord-vote-requests.json' => 'requests',
    'data/notifications.json' => 'notifications',
    'data/rotation-schedule.json' => null, // Special handling
    'data/signature-history.json' => 'signatures',
];

function add_server_field(&$records, $server_id, $path = '') {
    if (is_array($records)) {
        foreach ($records as &$record) {
            if (is_array($record) && !isset($record['server'])) {
                $record['server'] = $server_id;
            }
        }
    }
}

function migrate_users(&$data, $server_id) {
    // Convert flat permissions to server-based
    foreach ($data['users'] as &$user) {
        // If already migrated, skip
        if (isset($user['servers'])) {
            continue;
        }

        // Create new server-based structure
        $servers = [];

        if (isset($user['alliances'])) {
            $servers[$server_id] = [
                'alliances' => $user['alliances'],
                'ape' => $user['ape'] ?? false
            ];
            unset($user['alliances']);
            unset($user['ape']);
        }

        $user['servers'] = $servers;
    }
}

function migrate_rotation_schedule(&$data, $server_id) {
    // Rotation schedule is server-specific, wrap it
    if (!isset($data['server'])) {
        $wrapped = [
            'server' => $server_id,
            'data' => $data
        ];
        $data = $wrapped;
    }
}

// Migrate each file
foreach ($migrations as $file => $recordPath) {
    $fullPath = __DIR__ . '/../' . $file;

    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: {$file} (file not found)\n";
        continue;
    }

    echo "📄 Processing: {$file}\n";

    // Read file
    $json = file_get_contents($fullPath);
    $data = json_decode($json, true);

    if ($data === null) {
        echo "   ❌ ERROR: Invalid JSON\n";
        continue;
    }

    // Backup original
    if (!$dryRun) {
        $backupPath = $fullPath . '.backup.' . date('Y-m-d_H-i-s');
        copy($fullPath, $backupPath);
        echo "   💾 Backup: {$backupPath}\n";
    }

    // Migrate based on file type
    $modified = false;

    if ($file === 'data/users.json') {
        migrate_users($data, $defaultServer);
        $modified = true;
    } elseif ($file === 'data/rotation-schedule.json') {
        migrate_rotation_schedule($data, $defaultServer);
        $modified = true;
    } else {
        // Standard migration - add server field
        $records = &$data;
        if ($recordPath && isset($data[$recordPath])) {
            $records = &$data[$recordPath];
        }

        $countBefore = count($records);
        add_server_field($records, $defaultServer);

        // Check if anything changed
        $jsonAfter = json_encode($records);
        $modified = ($json !== json_encode($data));
    }

    if ($modified) {
        if (!$dryRun) {
            file_put_contents(
                $fullPath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            echo "   ✅ MIGRATED\n";
        } else {
            echo "   ✅ WOULD MIGRATE (dry run)\n";
        }
    } else {
        echo "   ℹ️  Already migrated (skipped)\n";
    }

    echo "\n";
}

// Audit log is special - it's append-only
$auditFile = __DIR__ . '/../admin/audit_log.json';
if (file_exists($auditFile)) {
    echo "📄 Processing: admin/audit_log.json\n";
    echo "   ℹ️  Audit log will automatically get 'server' field on new entries\n";
    echo "   ℹ️  Historical entries will remain without server field (backward compat)\n\n";
}

echo "==============================\n";
echo "Migration complete!\n\n";

if ($dryRun) {
    echo "This was a DRY RUN. No files were modified.\n";
    echo "Run without --dry-run to apply changes.\n";
} else {
    echo "✅ All files have been migrated to multi-server format.\n";
    echo "📦 Backup files created with .backup.YYYY-MM-DD_HH-MM-SS extension\n\n";
    echo "Next steps:\n";
    echo "1. Test the admin panel to ensure data loads correctly\n";
    echo "2. Update API calls to include ?server={$defaultServer} parameter\n";
    echo "3. Prepare server 9999 data for import\n";
}
