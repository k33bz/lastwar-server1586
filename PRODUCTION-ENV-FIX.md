# Production .env Fix - Disable Automatic Key Rotation

## Problem
Users getting "Security keys have been rotated" errors because automatic key rotation is enabled in production.

## Immediate Fix (SSH)

### Step 1: SSH into Production Server

```bash
ssh -p YOUR_SSH_PORT YOUR_SSH_USER@YOUR_SSH_HOST
```

### Step 2: Navigate to Project Directory

```bash
cd ~/YOUR_DEPLOY_PATH
```

### Step 3: Backup Current .env File

```bash
cp admin/.env admin/.env.backup.$(date +%Y%m%d_%H%M%S)
```

### Step 4: Check Current Setting

```bash
grep "AUTO_KEY_ROTATION_ENABLED" admin/.env
```

### Step 5: Update .env File

**Option A: If the setting exists**
```bash
sed -i 's/^AUTO_KEY_ROTATION_ENABLED=.*/AUTO_KEY_ROTATION_ENABLED=false/' admin/.env
```

**Option B: If the setting doesn't exist**
```bash
# Add it before APP_ENV line
sed -i '/^APP_ENV=/i # JWT Secret Key Rotation Configuration\nAUTO_KEY_ROTATION_ENABLED=false\nKEY_ROTATION_INTERVAL_DAYS=90\nKEY_ROTATION_GRACE_PERIOD=300\n' admin/.env
```

### Step 6: Verify the Change

```bash
grep -A 3 "AUTO_KEY_ROTATION_ENABLED" admin/.env
```

**Expected output:**
```
AUTO_KEY_ROTATION_ENABLED=false
KEY_ROTATION_INTERVAL_DAYS=90
KEY_ROTATION_GRACE_PERIOD=300
```

### Step 7: Check for Cron Jobs

```bash
crontab -l | grep -i "key_rotation"
```

**If you see a cron job like:**
```
0 2 * * * /usr/bin/php /path/to/admin/cron_key_rotation.php
```

**Disable it:**
```bash
crontab -e
# Comment out or remove the line:
# 0 2 * * * /usr/bin/php /path/to/admin/cron_key_rotation.php
```

---

## Alternative: One-Line Fix

**Quick fix in one command:**

```bash
ssh -p PORT USER@HOST "cd ~/DEPLOY_PATH && cp admin/.env admin/.env.backup.\$(date +%Y%m%d_%H%M%S) && sed -i 's/^AUTO_KEY_ROTATION_ENABLED=.*/AUTO_KEY_ROTATION_ENABLED=false/' admin/.env && echo 'Fix applied! Current setting:' && grep AUTO_KEY_ROTATION_ENABLED admin/.env"
```

Replace:
- `PORT` with your SSH port
- `USER` with your SSH username
- `HOST` with your server hostname
- `DEPLOY_PATH` with your deployment path (e.g., `public_html/server1586`)

---

## Automated Fix via GitHub Actions

The deployment workflow will automatically update the .env file on the next successful deployment.

**Current deployment status:**
- Commit: `d37ab8c` - Removed Unicode checkmarks
- Should complete successfully and auto-fix .env

**Monitor at:** https://github.com/k33bz/lastwar-server1586/actions

---

## Post-Fix Verification

After applying the fix:

1. **Users can log in again** without being logged out unexpectedly
2. **Key rotation only happens when:**
   - Minor/major version bump (3.1.0 → 3.2.0)
   - Manual trigger via commit message: `[rotate-keys]`
   - Admin panel: `/admin/key_rotation_admin_panel.php`

---

## Troubleshooting

### Still getting rotation errors?

**Check if rotation happened recently:**
```bash
ls -lah admin/secret_keys.json
cat admin/secret_keys.json | python -m json.tool
```

**Check audit logs:**
```bash
ls -lah admin/logs/audit.log
tail -n 50 admin/logs/audit.log | grep "key_rotation"
```

**Check cron logs:**
```bash
grep "cron_key_rotation" /var/log/syslog
```

---

**Generated:** 2025-10-25
**Related Commits:**
- `730775e` - Disabled auto-rotation in workflow
- `d37ab8c` - Fixed Unicode issues for deployment
