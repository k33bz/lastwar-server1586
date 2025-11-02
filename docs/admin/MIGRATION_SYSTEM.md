# Version Migration System

Automatic data file and environment configuration upgrading for production deployments.

## Overview

When deploying new code versions to production, data files (JSON, .env) may need schema changes to match new features. The migration system automatically detects version mismatches and applies necessary upgrades to:

- **Environment variables** (.env) - Add new config, deprecate old
- **JSON data files** (alliances.json, users.json, etc.) - Add fields, restructure
- **System files** (audit_log.json, backups/) - Initialize new features

## How It Works

### Version Tracking

**Code Version**: Stored in `/version.json`
```json
{
  "version": "3.2.0",
  "date": "2025-10-19"
}
```

**Installed Version**: Stored in `/admin/.installed_version`
```
3.1.0
```

### Automatic Detection

On every page load, `config.php` includes `version_check.php` which:

1. Reads code version from `version.json`
2. Reads installed version from `.installed_version`
3. Compares using semantic versioning
4. Sets `$GLOBALS['version_info']` with comparison result

### Migration Warning

If versions don't match, a banner appears at the top of all admin pages:

```
⬆️ Migration Required
Code version: 3.2.0 | Installed: 3.1.0 | Action: upgrade
[🔧 Run Migration Now]
```

### Migration Execution

Migrations run when:
- Admin clicks "Run Migration Now" button (web)
- Admin runs `php admin/migrate.php` (CLI)
- Auto-migration is enabled in `config.php` (optional, disabled by default)

## Migration Workflow

### 1. Deploy New Code

```bash
# GitHub Actions deploys via FTP
# New version.json: 3.2.0
# Installed version: 3.1.0
```

### 2. Detection

Next page load:
```php
// config.php includes version_check.php
require_once __DIR__ . '/version_check.php';

// version_info set globally
$GLOBALS['version_info'] = [
    'needed' => true,
    'current' => '3.2.0',
    'installed' => '3.1.0',
    'is_upgrade' => true
];
```

### 3. Warning Display

Admin sees orange banner:
```
⬆️ Migration Required
```

### 4. Run Migration

**Option A: Web Interface**
```
Click "Run Migration Now" → /admin/migrate.php
```

**Option B: CLI (recommended)**
```bash
php admin/migrate.php
```

**Output:**
```
=== Version Migration System ===
Code version: 3.2.0
Installed version: 3.1.0

🔄 Migration needed: 3.1.0 → 3.2.0

🔧 Running migration: 3.2.0
   - Setting up audit logging...
   - Creating audit_log.json...
   ✓ audit_log.json created
   ✓ Completed: 3.2.0

=== Migration Summary ===
Migrations run: 1
Errors: 0

✅ Migration completed successfully!
```

### 5. Version Update

`.installed_version` updated to `3.2.0`

Warning banner disappears on next page load.

## Writing Migrations

### Migration Structure

Migrations are defined in `migrate.php` as methods:

```php
private function runMigrations($from_version, $to_version) {
    $migrations = [
        '3.0.0' => 'migrateToV3',      // JWT authentication
        '3.1.0' => 'migrateToV3_1',    // Alliance tags
        '3.2.0' => 'migrateToV3_2',    // Audit logging
        '3.3.0' => 'migrateToV3_3',    // Backup support
        // Add new versions here
    ];

    foreach ($migrations as $version => $method) {
        // Runs if: installed < version <= code
        if ($this->compareVersions($from_version, $version) < 0 &&
            $this->compareVersions($version, $to_version) <= 0) {
            $this->$method();
        }
    }
}
```

### Migration Method Template

```php
/**
 * Migration: vX.Y.Z - Feature Name
 */
private function migrateToVX_Y_Z() {
    $this->log("   - Doing something...");

    try {
        // 1. Check if migration is needed
        $file = $this->data_dir . 'example.json';
        if (!file_exists($file)) {
            throw new Exception("example.json not found");
        }

        // 2. Load data
        $data = json_decode(file_get_contents($file), true);

        // 3. Check if already migrated
        if (isset($data[0]['newField'])) {
            $this->log("   ✓ Already up to date");
            return;
        }

        // 4. Backup before modifying
        $this->backupFile($file);

        // 5. Apply changes
        foreach ($data as &$item) {
            $item['newField'] = 'default_value';
        }

        // 6. Save
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

        $this->log("   ✓ Migration completed");

    } catch (Exception $e) {
        throw new Exception("Migration failed: " . $e->getMessage());
    }
}
```

### Best Practices

1. **Always check if migration is needed** - Don't assume clean state
2. **Backup before modifying** - Use `$this->backupFile()`
3. **Handle missing files gracefully** - New installations may not have old files
4. **Log progress** - Use `$this->log()` for status updates
5. **Throw exceptions on failure** - Migration will stop and report error
6. **Test rollbacks** - Ensure migrations are idempotent (can run multiple times safely)

## Example Migrations

### v3.1.0: Add r5History to alliances

```php
private function migrateToV3_1() {
    $this->log("   - Migrating alliances.json schema...");

    $alliances_file = $this->data_dir . 'alliances.json';
    $alliances = json_decode(file_get_contents($alliances_file), true);
    $modified = false;

    foreach ($alliances as &$alliance) {
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
```

### v3.2.0: Initialize Audit Logging

```php
private function migrateToV3_2() {
    $this->log("   - Setting up audit logging...");

    // Create audit_log.json if doesn't exist
    $audit_file = $this->admin_dir . 'audit_log.json';
    if (!file_exists($audit_file)) {
        $this->log("   - Creating audit_log.json...");
        file_put_contents($audit_file, json_encode([], JSON_PRETTY_PRINT));
        $this->log("   ✓ audit_log.json created");
    } else {
        $this->log("   ✓ audit_log.json already exists");
    }

    // Check .env for audit settings
    $env_file = $this->admin_dir . '.env';
    $env_content = file_get_contents($env_file);

    if (strpos($env_content, 'AUDIT_ENABLED') === false) {
        $this->log("   ⚠️  AUDIT_ENABLED not set in .env");
        $this->log("   ℹ️  Add: AUDIT_ENABLED=true");
    } else {
        $this->log("   ✓ Audit configuration present");
    }
}
```

### v3.3.0: Setup Backup System

```php
private function migrateToV3_3() {
    $this->log("   - Setting up backup system...");

    // Create backups directory
    $backups_dir = $this->admin_dir . 'backups/';
    if (!is_dir($backups_dir)) {
        $this->log("   - Creating backups directory...");
        mkdir($backups_dir, 0755, true);
        $this->log("   ✓ backups/ directory created");
    }

    // Protect with .htaccess
    $htaccess_file = $backups_dir . '.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "# Deny all access to backup files\nDeny from all\n";
        file_put_contents($htaccess_file, $htaccess_content);
        $this->log("   ✓ Backup directory protected");
    }
}
```

## Environment Variable Migrations

### Adding New Variables

Migrations cannot automatically add to `.env` (file is not in git, manually managed). Instead:

1. **Document in migration output**:
   ```php
   $this->log("   ⚠️  Missing .env variables: NEW_FEATURE_ENABLED");
   $this->log("   ℹ️  Add: NEW_FEATURE_ENABLED=true");
   ```

2. **Update `.env.example`** in codebase:
   ```bash
   # New Feature (added v3.2.0)
   NEW_FEATURE_ENABLED=true
   ```

3. **Admin manually updates** production `.env`

4. **Migration validates** on next run

### Checking for Variables

```php
private function migrateToVX() {
    $env_file = $this->admin_dir . '.env';
    $env_content = file_get_contents($env_file);

    $required_vars = ['JWT_SECRET_KEY', 'AUDIT_ENABLED'];
    $missing_vars = [];

    foreach ($required_vars as $var) {
        if (strpos($env_content, $var . '=') === false) {
            $missing_vars[] = $var;
        }
    }

    if (count($missing_vars) > 0) {
        $this->log("   ⚠️  Missing .env variables: " . implode(', ', $missing_vars));
        $this->log("   ℹ️  Add these to .env manually");
    } else {
        $this->log("   ✓ All required variables present");
    }
}
```

## Rollback Scenarios

### Code Rollback (Newer → Older)

```
Installed: 3.2.0
Code: 3.1.0 (rolled back)
```

**Migration behavior**:
```
⚠️  WARNING: Code version is OLDER than installed version!
    This might indicate a rollback. Proceed with caution.
```

Migration will run but may not have rollback logic. **Manual intervention required**.

### Handling Rollbacks

Migrations should be **forward-compatible** where possible:

```php
// Good: Check for existence
if (!isset($data['newField'])) {
    $data['newField'] = 'default';
}

// Bad: Assume field doesn't exist
$data['newField'] = 'default'; // Overwrites if exists!
```

## Auto-Migration (Optional)

By default, migrations require manual trigger for safety. To enable automatic migration on deployment:

### Enable in config.php

```php
// config.php (line 161)
auto_migrate_if_needed(true);  // Uncomment this line
```

**WARNING**: Only enable if:
- You have automated backups
- Migrations are well-tested
- You monitor deployment logs
- You can quickly rollback if needed

### CLI Auto-Migration

Migrations always run automatically in CLI mode:

```bash
php admin/migrate.php  # Runs immediately
```

## Testing Migrations

### Local Testing

1. **Lower installed version**:
   ```bash
   echo "3.0.0" > admin/.installed_version
   ```

2. **Set code version**:
   ```json
   // version.json
   {"version": "3.2.0", "date": "2025-10-19"}
   ```

3. **Run migration**:
   ```bash
   php admin/migrate.php
   ```

4. **Verify**:
   - Check `.installed_version` updated
   - Check data files modified correctly
   - Check backups created

### Production Testing

1. **Test in staging environment first**
2. **Backup production before deploying**
3. **Deploy code**
4. **Monitor for migration warning**
5. **Run migration during low-traffic period**
6. **Verify application functions correctly**

## Backup System

All migrations create backups before modifying files:

```php
$this->backupFile($alliances_file);
// Creates: alliances.json.bak.2025-10-19_143052
```

**Restore from backup**:
```bash
cp admin/alliances.json.bak.2025-10-19_143052 data/alliances.json
```

## Troubleshooting

### Migration Fails Midway

**Symptoms**: Error in migration output, `.installed_version` not updated

**Recovery**:
1. Check error message in output
2. Restore from backup if needed
3. Fix issue (missing file, permissions, etc.)
4. Re-run migration

### Version Mismatch After Migration

**Symptoms**: Warning banner still appears

**Check**:
```bash
cat admin/.installed_version   # Should match version.json
cat version.json                # Check code version
```

**Fix**:
```bash
# Manually update installed version
echo "3.2.0" > admin/.installed_version
```

### Backup Files Accumulating

Backups are created as `.bak.YYYY-MM-DD_HHMMSS`. Clean old backups manually:

```bash
# Delete backups older than 30 days
find admin/ -name "*.bak.*" -mtime +30 -delete
```

## File Reference

- `/admin/migrate.php` - Migration system core
- `/admin/version_check.php` - Version comparison and warning display
- `/admin/config.php` - Includes version check on every page load
- `/admin/includes/header.php` - Displays migration warning banner
- `/admin/.installed_version` - Tracks installed version (not in git)
- `/version.json` - Code version (in git)

## Adding a New Migration

### Step 1: Update version.json

```json
{
  "version": "3.4.0",
  "date": "2025-10-20"
}
```

### Step 2: Add migration method to migrate.php

```php
private function migrateToV3_4() {
    $this->log("   - Adding new feature...");
    // Migration logic here
}
```

### Step 3: Register in migrations array

```php
$migrations = [
    '3.0.0' => 'migrateToV3',
    '3.1.0' => 'migrateToV3_1',
    '3.2.0' => 'migrateToV3_2',
    '3.3.0' => 'migrateToV3_3',
    '3.4.0' => 'migrateToV3_4',  // Add this line
];
```

### Step 4: Test locally

```bash
echo "3.3.0" > admin/.installed_version
php admin/migrate.php
```

### Step 5: Commit and deploy

```bash
git add version.json admin/migrate.php
git commit -m "feat: Add migration for v3.4.0 feature"
git push origin mainline
```

### Step 6: Run in production

After deployment:
```bash
# SSH into server
php admin/migrate.php
```

Or use web interface migration button.

---

**Documentation**: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/MIGRATION_SYSTEM.md
**GitHub Issues**: https://github.com/k33bz/lastwar-server1586/issues
