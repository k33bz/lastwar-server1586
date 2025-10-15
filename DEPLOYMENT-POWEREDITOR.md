# Power Editor Role - Production Deployment Guide

**Date:** 2025-10-15
**Feature:** Power Editor Role Support
**Commits:** 7899235 (Part 1) + f6003be (Part 2)

## Overview

The power editor role allows R5 or R4 users to edit alliance power values for ALL alliances without needing admin access. Power editors can edit but cannot delete alliances.

## What Was Implemented

### Part 1 - Backend (Commit 7899235)
- JWT tokens now include `powereditor` boolean flag
- `is_power_editor()` helper function (admins + powereditor users)
- `can_delete_alliances()` helper function (admins only)
- Alliance Power Editor page accessible to power editors
- Delete buttons hidden for non-admins
- Power editor API endpoints allow list/update/add but block delete

### Part 2 - Dashboard & UI (Commit f6003be)
- Dashboard displays "R5/POWEREDITOR" or "R4/POWEREDITOR" badges
- User management table shows powereditor flag
- Add/edit user forms include power editor checkbox (admin-only)
- Delete Alliance button on dashboard (admin-only)
- New API endpoint: `alliance_delete_api.php`
- Security: `.htaccess` blocks direct access to PII files

## Production Deployment Steps

### Step 1: Verify Code Deployment
GitHub Actions should have automatically deployed the code to production. Verify:
- ✅ `admin/jwt.php` v2.0.0 deployed
- ✅ `admin/alliances_power.php` v2.0.0 deployed
- ✅ `admin/alliances_power_api.php` v2.0.0 deployed
- ✅ `admin/dashboard.php` v1.6.0 deployed
- ✅ `admin/admin_api.php` v1.6.0 deployed
- ✅ `admin/json_helpers.php` v1.1.0 deployed
- ✅ `admin/alliance_delete_api.php` v1.0.0 deployed
- ✅ `admin/.htaccess` deployed
- ✅ `admin/email_utils.js` deployed

### Step 2: Backup Production users.json

**IMPORTANT:** Before making any changes, create a backup!

```bash
# Using FTP client (FileZilla, WinSCP, etc.)
1. Connect to: lastwar1586.online
2. Navigate to: /admin/users.json
3. Download and save as: users.json.backup-2025-10-15
```

### Step 3: Update users.json

**Current Structure (OLD):**
```json
{
  "users": [
    {
      "email": "admin@example.com",
      "alliances": ["*"],
      "role": "admin"
    },
    {
      "email": "r5-user@example.com",
      "alliances": ["UvvU"],
      "role": "r5"
    }
  ]
}
```

**New Structure (REQUIRED):**
```json
{
  "users": [
    {
      "email": "admin@example.com",
      "alliances": ["*"],
      "role": "admin",
      "powereditor": false
    },
    {
      "email": "r5-user@example.com",
      "alliances": ["UvvU"],
      "role": "r5",
      "powereditor": false
    }
  ]
}
```

**Steps:**
1. Download production `users.json`
2. Open in text editor (VS Code, Notepad++, etc.)
3. Add `"powereditor": false` to each user object
4. **Important:** Set `"powereditor": false` for ALL users initially
5. Save the file
6. Validate JSON syntax: https://jsonlint.com/
7. Upload back to production: `/admin/users.json`

### Step 4: Verify Security (.htaccess)

After deployment, verify that `.htaccess` is protecting PII files:

**Test 1: Try accessing users.json directly**
```
https://www.lastwar1586.online/admin/users.json
```
Expected result: **403 Forbidden** error

**Test 2: Verify dashboard still works**
```
https://www.lastwar1586.online/admin/dashboard.php
```
Expected result: Dashboard loads normally (PHP can still access users.json internally)

**Test 3: Verify login still works**
```
https://www.lastwar1586.online/admin/login.php
```
Expected result: Magic link email generation works (PHP can still read users.json)

### Step 5: Grant Power Editor Access (Optional)

After deployment, admins can grant power editor access to specific users:

1. Login as admin: https://www.lastwar1586.online/admin/dashboard.php
2. Click "Edit" next to user who needs power editor access
3. Check the **"Power Editor"** checkbox
4. Click "Update User"
5. User will need to log out and log back in for changes to take effect

**Note:** The power editor checkbox only appears for R5 and R4 users. Admins automatically have power editor access and don't need the flag.

## Rollback Instructions

If something goes wrong, follow these steps:

### Rollback Code:
```bash
# SSH into production server
cd ~/lastwar1586.online
git checkout 7899235^  # Go back one commit before Part 1
```

### Restore users.json:
```bash
# Using FTP client
1. Upload your backup: users.json.backup-2025-10-15
2. Rename to: users.json
```

## Testing Checklist

After deployment, test the following:

### As Admin:
- [ ] Dashboard displays "ADMIN" badge (no powereditor suffix)
- [ ] User management table shows roles correctly
- [ ] Can access Alliance Power Editor from Quick Links
- [ ] Can see and use Delete buttons in Alliance Power Editor
- [ ] Can see Delete Alliance button on dashboard
- [ ] Can grant power editor access to R5/R4 users
- [ ] Power editor checkbox appears in add/edit user forms
- [ ] Power editor checkbox hides when admin role selected

### As Power Editor (R5 or R4 with powereditor=true):
- [ ] Dashboard displays "R5/POWEREDITOR" or "R4/POWEREDITOR" badge
- [ ] Can access Alliance Power Editor from Quick Links
- [ ] Can edit alliance power values for ALL alliances
- [ ] Can add new alliances
- [ ] Cannot see Delete buttons (should show "Edit only" text)
- [ ] Cannot access delete alliance API endpoint (403 error)

### As Regular R5/R4 (powereditor=false):
- [ ] Dashboard displays "R5" or "R4" badge (no powereditor suffix)
- [ ] Cannot see Alliance Power Editor link in Quick Links
- [ ] Cannot access Alliance Power Editor directly (redirected to dashboard)

### Security Tests:
- [ ] Cannot access users.json directly via browser (403 Forbidden)
- [ ] Cannot access blacklist.json directly via browser (403 Forbidden)
- [ ] Dashboard and login still work normally (PHP can access files)

## Troubleshooting

### Issue: 403 Forbidden on all admin pages
**Cause:** .htaccess configuration error
**Fix:** Check Apache error logs, verify .htaccess syntax

### Issue: Power editor checkbox not appearing
**Cause:** Not logged in as admin, or JavaScript not loading
**Fix:** Verify admin role, check browser console for errors

### Issue: Changes not taking effect
**Cause:** JWT tokens cached with old user data
**Fix:** User needs to log out and log back in (sessions expire after 12 hours)

### Issue: Cannot edit alliance power
**Cause:** powereditor flag not in JWT token
**Fix:** User needs to log out and log back in to get new token with powereditor flag

## Production Environment Variables

Verify these are set correctly in `admin/.env`:

```env
APP_ENV=production
APP_URL=https://www.lastwar1586.online

# JWT Secret (DO NOT CHANGE - will invalidate all sessions)
SECRET_KEY=<existing-secret-key>

# Session expiry (12 hours)
SESSION_TOKEN_EXPIRY=43200

# Magic link expiry (15 minutes)
MAGIC_LINK_EXPIRY=900
```

## Support & Contact

If you encounter issues:
1. Check server error logs: `~/lastwar1586.online/error_log`
2. Check Apache error logs: `/var/log/apache2/error.log` (if accessible)
3. Test in browser console for JavaScript errors
4. Verify users.json syntax: https://jsonlint.com/

## Post-Deployment Notes

- All existing sessions will continue to work (they don't have powereditor flag, which defaults to false)
- Users who need power editor access must be granted it via the admin dashboard
- The powereditor flag is stored in JWT tokens, so changes require logout/login
- Admin users automatically have power editor access (no flag needed)

---

**Deployed by:** Claude Code
**Documentation version:** 1.0.0
**Last updated:** 2025-10-15
