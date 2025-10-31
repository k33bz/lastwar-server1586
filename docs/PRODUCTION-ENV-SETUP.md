# Production .env Setup and Maintenance

**CRITICAL**: The production `admin/.env` file is **NEVER deployed** by CI/CD. It is managed manually and by the key rotation system only.

---

## Why .env is Production-Only

**The Problem We Solved:**
- Deployments were creating `.env` from GitHub Secrets with the original `SECRET_KEY`
- This overwrote the production `.env` containing rotated keys
- All user sessions became invalid after every deployment
- Users saw "Security keys have been rotated. Please log in again" error

**The Solution:**
- `.env` is excluded from deployment (`.ftpignore` line 39)
- GitHub Actions no longer generates `.env` (`deploy.yml` lines 144-148)
- Production `.env` is the source of truth for JWT keys
- Only the key rotation system modifies `.env` in production

---

## Initial Production Setup

**One-time setup** when first deploying the admin panel:

### 1. SSH into Production Server

```bash
ssh -p [SSH_PORT] [SSH_USER]@[SSH_HOST]
cd ~/[DEPLOY_PATH]/admin
```

### 2. Create Production .env

```bash
cat > .env << 'EOF'
# JWT Secret Keys
# IMPORTANT: These are managed by the key rotation system
# Do not manually edit SECRET_KEY unless performing emergency rotation
SECRET_KEY=YOUR_GENERATED_SECRET_KEY_HERE

# SMTP Configuration
SMTP_HOST=smtp.example.com
SMTP_PORT=465
SMTP_USER=your-email@example.com
SMTP_PASS=your-smtp-password
SMTP_FROM=noreply@example.com
SMTP_FROM_NAME="Server 1586 Admin"

# Application Configuration
APP_URL=https://www.example.com
ADMIN_EMAIL=admin@example.com

# Token Expiry (in seconds)
MAGIC_LINK_EXPIRY=600        # 10 minutes
SESSION_TOKEN_EXPIRY=28800   # 8 hours (recommended)

# JWT Secret Key Rotation Configuration
AUTO_KEY_ROTATION_ENABLED=true
KEY_ROTATION_INTERVAL_DAYS=90
KEY_ROTATION_GRACE_PERIOD=300

# Environment
APP_ENV=production
EOF
```

### 3. Generate SECRET_KEY

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

Copy the output and replace `YOUR_GENERATED_SECRET_KEY_HERE` in `.env`.

### 4. Set Permissions

```bash
chmod 600 .env
```

### 5. Verify Configuration

```bash
php -r "require 'config.php'; echo 'SECRET_KEY: ' . SECRET_KEY . PHP_EOL;"
```

Should output your generated key.

---

## Files That Are NEVER Deployed

These files in `admin/` are excluded from deployment and exist **only in production**:

1. **`.env`** - Contains JWT keys, SMTP credentials, app configuration
2. **`key_rotation.json`** - Key rotation history and state
3. **`blacklisted_tokens.json`** - Revoked JWT tokens
4. **`users.json`** - User accounts and permissions (if not using database)

See `.ftpignore` lines 37-42 for exclusion rules.

---

## Key Rotation System

### Automatic Rotation

**Runs via cron** (every day at 3 AM):
```bash
0 3 * * * cd ~/[DEPLOY_PATH]/admin && php cron_key_rotation.php >> ../logs/key_rotation.log 2>&1
```

**What it does:**
1. Checks if 90 days have passed since last rotation
2. If yes:
   - Generates new `SECRET_KEY`
   - Moves current `SECRET_KEY` to `PREVIOUS_SECRET_KEY`
   - Updates `.env` with both keys (5-minute grace period)
   - Records rotation in `key_rotation.json`
3. After grace period: Removes `PREVIOUS_SECRET_KEY` from `.env`

### Manual Emergency Rotation

If you need to rotate keys immediately (e.g., security breach):

```bash
ssh -p [SSH_PORT] [SSH_USER]@[SSH_HOST]
cd ~/[DEPLOY_PATH]/admin
php rotate_keys_cli.php
```

**Warning**: All users will be logged out immediately.

### Version-Based Rotation

GitHub Actions workflow includes automatic key rotation on version changes:
- Runs when major.minor version changes (e.g., 3.0.0 → 3.1.0)
- Can be triggered via commit message: `[rotate-keys]`
- Executes `rotate_keys_cli.php` via SSH
- See `deploy.yml` lines 273-327

---

## Verifying Production .env After Rotation

### 1. Check Current Keys

```bash
ssh -p [SSH_PORT] [SSH_USER]@[SSH_HOST]
cd ~/[DEPLOY_PATH]/admin
grep "SECRET_KEY" .env
```

**Expected output during grace period:**
```
SECRET_KEY=new_rotated_key_here
PREVIOUS_SECRET_KEY=old_key_here
```

**Expected output after grace period:**
```
SECRET_KEY=current_key_here
```

### 2. Check Rotation History

```bash
cat key_rotation.json
```

**Expected format:**
```json
{
  "current_rotation_date": "2025-10-30",
  "next_rotation_date": "2026-01-28",
  "rotation_history": [
    {
      "date": "2025-10-30",
      "previous_key": "old_key_hash",
      "new_key": "new_key_hash"
    }
  ]
}
```

### 3. Verify Active Sessions

```bash
php -r "
require 'config.php';
require 'jwt.php';
\$users = json_decode(file_get_contents('users.json'), true);
foreach (\$users['users'] ?? [] as \$user) {
    if (isset(\$user['active_sessions'])) {
        echo \$user['email'] . ': ' . count(\$user['active_sessions']) . ' active sessions\n';
    }
}
"
```

---

## Troubleshooting

### Problem: Users Getting "Keys Rotated" Error After Deployment

**Cause**: Deployment overwriting production `.env` with original keys.

**Verification:**
```bash
ssh -p [SSH_PORT] [SSH_USER]@[SSH_HOST]
cd ~/[DEPLOY_PATH]/admin
grep "SECRET_KEY" .env
```

If `SECRET_KEY` matches the one in GitHub Secrets (not the rotated key), deployment is overwriting .env.

**Solution:**
1. Verify `.ftpignore` excludes `admin/.env` (line 39)
2. Verify `deploy.yml` does NOT create `.env` file
3. Manually restore production `.env` with rotated keys
4. Check `key_rotation.json` for last known good keys

### Problem: Key Rotation Not Running

**Check cron job:**
```bash
crontab -l | grep key_rotation
```

**Check rotation logs:**
```bash
tail -f ~/[DEPLOY_PATH]/logs/key_rotation.log
```

**Test rotation manually:**
```bash
cd ~/[DEPLOY_PATH]/admin
php cron_key_rotation.php
```

### Problem: Lost Production .env

If production `.env` was accidentally deleted or overwritten:

1. **Emergency**: Use GitHub Secrets to recreate basic .env:
   ```bash
   # Generate new SECRET_KEY
   NEW_KEY=$(php -r "echo bin2hex(random_bytes(32));")

   # Create .env with GitHub Secrets values
   # (You'll need to manually enter values from GitHub Secrets)
   nano .env
   ```

2. **Update GitHub Secrets** with new `SECRET_KEY` if needed

3. **All users must log in again** (all old sessions invalidated)

---

## GitHub Secrets

These secrets are used for:
- SMTP configuration
- App configuration
- **NOT for SECRET_KEY** (production .env is source of truth)

**Required Secrets:**
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`
- `APP_URL`, `ADMIN_EMAIL`
- `FTP_HOST`, `FTP_USER`, `FTP_PASS`
- `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_PRIVATE_KEY`
- `DEPLOY_PATH`

**Deprecated Secret:**
- `JWT_SECRET_KEY` - No longer used by deployment (kept for emergency recovery)

---

## Best Practices

1. ✅ **Never** manually edit `SECRET_KEY` in production unless emergency rotation
2. ✅ **Never** commit `.env` to git
3. ✅ **Never** include `.env` in deployments
4. ✅ **Always** use automatic key rotation (90-day schedule)
5. ✅ **Always** backup `.env` before manual changes
6. ✅ **Always** verify `.env` after deployment (should be unchanged)
7. ✅ Monitor `key_rotation.json` for rotation history
8. ✅ Check rotation logs if users report unexpected logouts

---

## Quick Reference Commands

```bash
# SSH to production
ssh -p [SSH_PORT] [SSH_USER]@[SSH_HOST]

# Check current .env
cd ~/[DEPLOY_PATH]/admin && cat .env

# Check key rotation status
cat key_rotation.json

# Test key rotation (dry run)
php cron_key_rotation.php --dry-run

# Emergency rotate keys
php rotate_keys_cli.php

# Generate new secret key
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Verify .env not in deployment
grep "admin/.env" ~/[DEPLOY_PATH]/.ftpignore
```

---

**Version:** 1.0.0
**Created:** 2025-10-30
**Purpose:** Document production .env management after fixing deployment overwrite issue
