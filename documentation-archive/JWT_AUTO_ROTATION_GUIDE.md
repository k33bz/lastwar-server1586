# JWT Secret Key Auto-Rotation Guide
**Last Updated**: 2025-10-27
**Status**: ✅ ENABLED

---

## 📋 Overview

JWT secret key auto-rotation is now **ENABLED** in production to enhance security by automatically rotating encryption keys every 90 days.

### Current Configuration

```env
AUTO_KEY_ROTATION_ENABLED=true
KEY_ROTATION_INTERVAL_DAYS=90
KEY_ROTATION_GRACE_PERIOD=300  # 5 minutes
```

---

## 🔄 How Auto-Rotation Works

### Automatic Process

1. **Daily Check** - Cron job runs at 2:00 AM server time
2. **Age Verification** - Checks if current key is ≥ 90 days old
3. **Rotation Trigger** - If needed, generates new key
4. **Grace Period** - 5-minute overlap where both keys work
5. **User Session Reset** - All users logged out (must re-authenticate)
6. **Admin Notification** - Email sent to all admin users
7. **Audit Log** - Rotation recorded in history

### Grace Period Benefits

- **Seamless Transition** - No service interruption
- **Zero Downtime** - Both old and new keys valid for 5 minutes
- **Automatic Fallback** - Decoding tries new key, then old key
- **User Experience** - Active sessions transition smoothly

---

## ⏰ Rotation Schedule

| Event | Timing | Description |
|-------|--------|-------------|
| **Cron Check** | Daily at 2:00 AM | Checks if rotation needed |
| **Key Age Threshold** | 90 days | Automatic rotation triggers |
| **Grace Period** | 5 minutes | Old and new keys both valid |
| **Session Invalidation** | Immediate | All users must re-login |
| **Admin Notification** | Immediate | Email to all admins |

### Next Rotation

Current key created: **2025-10-17**
Next automatic rotation: **~2026-01-15** (90 days later)

---

## 🛠️ Setup & Maintenance

### Cron Job Setup

**Location**: Production server SSH

```bash
# SSH into server
ssh -i ~/.ssh/lastwar1586-github-actions -p 21098 k33bodux@68.65.120.149

# Run setup script
cd ~/public_html/server1586
bash setup_cron_job.sh

# Verify installation
crontab -l | grep key_rotation
```

**Expected Output**:
```
# JWT Secret Key Rotation - Check daily
0 2 * * * /usr/bin/php /home/k33bodux/public_html/server1586/admin/cron_key_rotation.php >> /home/k33bodux/public_html/server1586/admin/logs/key_rotation_cron.log 2>&1
```

### Manual Verification

```bash
# Test rotation system (doesn't rotate, just checks)
php test_rotation_system.php

# Check key status
php admin/cron_key_rotation.php  # Dry run

# View cron logs
tail -f admin/logs/key_rotation_cron.log
```

---

## 🎯 Manual Rotation Options

Auto-rotation is enabled, but manual rotation is still available when needed:

### Option 1: Admin Panel
```
https://www.lastwar1586.online/admin/key_rotation_admin_panel.php
```
- Login as admin
- Click "Rotate Keys Now"
- Confirm action

### Option 2: SSH Command Line
```bash
ssh -p 21098 k33bodux@68.65.120.149
php ~/public_html/server1586/admin/rotate_keys_cli.php
```

### Option 3: Commit Message Trigger
Include in commit message:
- `[rotate-keys]`
- `[major]`
- `BREAKING CHANGE:`

### Option 4: Version Bump
- Major version change: 3.x.x → 4.0.0
- Minor version change: 3.1.x → 3.2.0

**Note**: Patch updates (3.1.0 → 3.1.1) do NOT trigger rotation

---

## 🔐 Security Features

### What Gets Invalidated

On rotation, all of these become invalid:
- ✅ All session tokens (users logged out)
- ✅ All magic link tokens (login emails expire)
- ✅ All refresh tokens
- ✅ Token blacklist cleared

### What Persists

These remain unaffected:
- ✅ User accounts and data
- ✅ User roles and permissions
- ✅ Alliance data
- ✅ Server configuration

### Audit Trail

Every rotation is logged in:
- `admin/secret_keys.json` → rotation_history array
- `admin/logs/audit.log` → Full audit trail
- `admin/logs/key_rotation_cron.log` → Cron execution log

---

## 📧 Admin Notifications

On rotation, admins receive:

**Subject**: JWT Secret Key Rotated - Last War 1586 Admin

**Content**:
- New Key ID
- Rotation timestamp
- Grace period end time
- Reason for rotation (automatic/manual)
- Instructions for users

**Recipients**: All users with `role: admin` in `users.json`

---

## 🐛 Troubleshooting

### Users Getting "Security keys have been rotated" Error

**Cause**: Recent rotation occurred
**Solution**: Clear browser cookies and log in again

### Rotation Not Happening

1. Check if auto-rotation is enabled:
   ```bash
   grep AUTO_KEY_ROTATION_ENABLED admin/.env
   ```
   Should show: `AUTO_KEY_ROTATION_ENABLED=true`

2. Verify cron job is set up:
   ```bash
   crontab -l | grep key_rotation
   ```

3. Check key age:
   ```bash
   php test_rotation_system.php
   ```

4. View cron logs:
   ```bash
   tail -n 50 admin/logs/key_rotation_cron.log
   ```

### Keys Out of Sync

**Symptom**: "Invalid token signature" errors

**Fix**:
```python
python sync_secret_keys.py
```

This syncs the SECRET_KEY in .env with current key in secret_keys.json

---

## 📁 Related Files

### Production Configuration
- `admin/.env` - Environment configuration (rotation settings)
- `admin/secret_keys.json` - Current and historical keys
- `admin/config.php` - Loads rotation config

### Rotation System
- `admin/secret_key_rotation.php` - Core rotation functions
- `admin/cron_key_rotation.php` - Automated cron job
- `admin/key_rotation_admin_panel.php` - Web interface
- `admin/jwt.php` - JWT functions with rotation support

### Deployment
- `.github/workflows/deploy.yml` - Auto-deploys with rotation enabled
- `setup_cron_job.sh` - Cron installation script
- `test_rotation_system.php` - Test script (safe to run)

### Documentation
- `admin/SECRET_KEY_ROTATION_SETUP.md` - Detailed technical docs
- `JWT_AUTO_ROTATION_GUIDE.md` - This file
- `JWT_KEY_ROTATION_STATUS_REPORT.md` - Status verification report

---

## 🚀 Deployment

Auto-rotation configuration is automatically deployed via GitHub Actions:

1. **Push to mainline** → Triggers deployment
2. **Workflow creates .env** → With `AUTO_KEY_ROTATION_ENABLED=true`
3. **FTP uploads** → Files deploy to production
4. **Cron job** → Checks daily for rotation needs

---

## ⚠️ Important Notes

### User Impact

**When rotation occurs:**
- ✅ Expected: All users must log in again
- ✅ Expected: Active sessions end immediately
- ✅ Expected: Email login links expire

**Best practices:**
- Rotate during low-traffic hours (2 AM default)
- Send advance warning email to users
- Monitor admin@lastwar1586.online for notifications

### Disabling Auto-Rotation

If you need to disable (not recommended):

```python
# Via FTP
python enable_auto_rotation.py  # Has option to disable

# Or manually edit .env
AUTO_KEY_ROTATION_ENABLED=false
```

---

## 📊 Monitoring

### Check System Status

```bash
# Full system test
php test_rotation_system.php

# Key age and next rotation
php -r "require 'admin/secret_key_rotation.php';
        \$s = get_key_rotation_status();
        echo 'Age: ' . \$s['current_key_age_days'] . ' days\n';"

# Cron execution history
tail -n 100 admin/logs/key_rotation_cron.log
```

### Key Metrics

- **Current Key Age**: Check `test_rotation_system.php`
- **Rotation History Count**: View `secret_keys.json`
- **Last Rotation Date**: Check audit log
- **Next Scheduled Check**: `crontab -l`

---

## 🎓 Additional Resources

- **GitHub Repo**: https://github.com/k33bz/lastwar-server1586
- **GitHub Issues**: https://github.com/k33bz/lastwar-server1586/issues
- **Admin Panel**: https://www.lastwar1586.online/admin/
- **Technical Docs**: `admin/SECRET_KEY_ROTATION_SETUP.md`

---

## ✅ Status Checklist

Use this to verify auto-rotation is fully operational:

- [x] `AUTO_KEY_ROTATION_ENABLED=true` in production `.env`
- [x] `AUTO_KEY_ROTATION_ENABLED=true` in `.github/workflows/deploy.yml`
- [x] Rotation interval set to 90 days
- [x] Grace period set to 300 seconds (5 minutes)
- [ ] Cron job installed (`crontab -l | grep key_rotation`)
- [ ] Cron job tested (`php admin/cron_key_rotation.php`)
- [x] Test script uploaded (`test_rotation_system.php`)
- [x] Documentation complete
- [ ] Admin users notified of auto-rotation enablement

---

**Auto-rotation is now enabled and will maintain key security automatically! 🔐**
