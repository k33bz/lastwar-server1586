# JWT Key Rotation Guide

## Overview

This system supports automatic JWT key rotation on production deployments to enhance security. Key rotation invalidates old tokens while maintaining a grace period for active sessions.

## How It Works

### Automatic Rotation on Deployment

Key rotation happens automatically during deployment when triggered by specific commit message tags:

**Trigger Patterns:**
- `[rotate-keys]` - Explicit key rotation request
- `[major]` - Major version changes
- `BREAKING CHANGE:` - Breaking changes per Conventional Commits

### Example Commit Messages

```bash
# Will rotate keys
git commit -m "Add user authentication system [rotate-keys]"
git commit -m "[major] Upgrade to v2.0.0 with new security features"
git commit -m "feat: New API endpoints

BREAKING CHANGE: Old API endpoints removed"

# Will NOT rotate keys
git commit -m "Fix typo in documentation"
git commit -m "Update alliance power values"
```

## Grace Period

When keys are rotated:
- **Old key**: Valid for 5 minutes (configurable via `GRACE_PERIOD_MINUTES`)
- **New key**: Used immediately for new tokens
- **Active sessions**: Continue working during grace period
- **After grace period**: Users must log in again

## Manual Key Rotation

### On Production (via SSH)

```bash
# SSH into production server
ssh user@your-server.com

# Navigate to admin directory
cd path/to/admin

# Run rotation script
php rotate_keys_cli.php
```

### Script Output

```
🔄 Starting JWT Key Rotation on Production...

Current Key Status:
  Key ID: key_2025_10_17_12_30_45
  Created: 2025-10-17 12:30:45
  Age: 24.5 hours

🔑 Generating new key...
✅ New key generated: key_2025_10_18_13_15_22

📝 Updating .env file...
✅ Created backup: .env.backup.2025_10_18_13_15_22
✅ Updated .env with new key

Grace Period Information:
  Duration: 5 minutes
  Old tokens will work until: 2025-10-18 13:20:22
  New tokens use new key immediately

✅ Key rotation completed successfully!
```

## Architecture

### Files Involved

1. **`admin/rotate_keys_cli.php`** - CLI script for key rotation
2. **`admin/secret_key_rotation.php`** - Key rotation logic
3. **`admin/secret_keys.json`** - Key storage with rotation history
4. **`admin/.env`** - Current active key (sync with secret_keys.json)
5. **`.github/workflows/deploy.yml`** - Deployment workflow with rotation step

### Key Storage Format

```json
{
  "current_key_id": "key_2025_10_17_12_30_45",
  "keys": [
    {
      "id": "key_2025_10_17_12_30_45",
      "key": "base64_encoded_secret_here",
      "created_at": "2025-10-17 12:30:45",
      "rotated_at": null,
      "grace_period_until": null
    },
    {
      "id": "key_2025_10_16_10_15_30",
      "key": "old_base64_encoded_secret",
      "created_at": "2025-10-16 10:15:30",
      "rotated_at": "2025-10-17 12:30:45",
      "grace_period_until": "2025-10-17 12:35:45"
    }
  ]
}
```

## Security Benefits

1. **Compromised Key Mitigation**: If a key is compromised, rotation limits exposure window
2. **Regular Rotation**: Forces periodic key updates for better security
3. **Audit Trail**: All key rotations are logged with timestamps
4. **Minimal Disruption**: Grace period prevents immediate session termination

## When to Rotate Keys

### Recommended

- ✅ Major version releases
- ✅ Security-related changes
- ✅ Authentication system updates
- ✅ After security incidents
- ✅ Quarterly as security best practice

### Not Necessary

- ❌ Minor bug fixes
- ❌ UI/UX changes
- ❌ Documentation updates
- ❌ Data updates (alliances, rules, etc.)
- ❌ Content changes

## Troubleshooting

### Keys Out of Sync

If `.env` and `secret_keys.json` become out of sync:

```bash
# Check sync status
curl https://your-site.com/admin/check_key_sync.php

# Fix sync
curl https://your-site.com/admin/fix_key_sync.php
```

### Users Can't Log In After Rotation

This is expected behavior:
1. Old magic links expire after grace period
2. Users need to request new magic link
3. New link uses current key

### Verify Key Rotation

```bash
# Check current key ID
ssh user@server "grep SECRET_KEY /path/to/admin/.env"

# Check rotation history
ssh user@server "cat /path/to/admin/secret_keys.json | jq .keys"
```

## Deployment Workflow Integration

The GitHub Actions workflow automatically:

1. ✅ Deploys code via FTP
2. ✅ Installs Composer dependencies via SSH
3. ✅ Checks commit message for rotation triggers
4. ✅ Runs key rotation if triggered
5. ✅ Updates both `.env` and `secret_keys.json`
6. ✅ Maintains key rotation history

## Environment Differences

### Development

- Key rotation is **disabled** (always uses `.env` key)
- Prevents interference with testing
- Allows stable test tokens

### Production

- Key rotation is **enabled**
- Triggered by commit message tags
- Automatic backup before rotation
- Grace period for active sessions

## Configuration

In `admin/config.php`:

```php
// Grace period for old keys (minutes)
define('GRACE_PERIOD_MINUTES', 5);

// Environment
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
```

## Best Practices

1. **Plan Rotations**: Rotate during low-traffic periods if possible
2. **Notify Users**: Inform users before major rotations
3. **Monitor Logs**: Check for authentication failures after rotation
4. **Keep Backups**: `.env.backup.*` files are created automatically
5. **Document Changes**: Note rotation in deployment notes

## Example Deployment with Key Rotation

```bash
# Make your changes
git add .

# Commit with rotation trigger
git commit -m "feat: Add new admin features [major]

BREAKING CHANGE: Removed deprecated API endpoints
- Removed /api/old-endpoint
- Updated authentication flow
- Enhanced security measures

🤖 Generated with Claude Code"

# Push to trigger deployment
git push origin mainline
```

The GitHub Actions workflow will:
1. Run tests
2. Deploy code
3. Install dependencies
4. **Rotate JWT keys** (because of [major] tag)
5. Complete deployment

---

## Quick Reference

| Trigger | Rotates Keys | Example |
|---------|--------------|---------|
| `[rotate-keys]` | Yes | `fix: Security patch [rotate-keys]` |
| `[major]` | Yes | `[major] Release v2.0.0` |
| `BREAKING CHANGE:` | Yes | `feat: New API\n\nBREAKING CHANGE: ...` |
| Any other | No | `fix: Update UI styling` |

**Grace Period**: 5 minutes
**Script**: `admin/rotate_keys_cli.php`
**Check Sync**: `admin/check_key_sync.php`
**Fix Sync**: `admin/fix_key_sync.php`
