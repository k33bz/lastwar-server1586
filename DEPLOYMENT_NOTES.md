# Server 1586 - Deployment Notes

## Recent Updates (2025-10-13)

This document summarizes the recent changes made to the Server 1586 admin system and provides deployment instructions.

## Changes Implemented

### 1. R4 User Restrictions ✓
**File Modified:** `admin/alliance_edit.php` (v1.2.0)

**Changes:**
- R4 users can no longer edit alliance name or R5 name fields
- Fields are displayed as read-only with visual styling (gray background, disabled cursor)
- Added notice message: "Note: R4 users cannot edit alliance name or R5 name."

**Backend Validation:** Lines 96-178
**Frontend UI:** Lines 408, 511

**Testing:**
- Log in as R4 user
- Navigate to alliance edit page
- Verify alliance name and R5 name fields are read-only
- Verify notice message is displayed

---

### 2. UID Label Consistency ✓
**File Modified:** `admin/alliance_edit.php` (v1.2.0)

**Changes:**
- Changed "Game ID" label to "UID" throughout the interface

**Location:** Line 516

**Testing:**
- Log in as any user
- Navigate to alliance edit page
- Verify label shows "UID" instead of "Game ID"

---

### 3. Game UID Privacy Protection ✓
**Status:** Already implemented

**Details:**
- Game UID is only shown in the admin edit form
- Not displayed publicly on dashboard or other pages
- Access restricted to admin/R5 users with appropriate permissions

**No changes required.**

---

### 4. Enhanced Email Masking ✓
**File Modified:** `admin/dashboard.php` (v1.5.0)

**Changes:**
- Updated email masking format from `ab•••@domain.com` to `a******b@domain.com`
- Shows only first and last character of email username
- Uses asterisks (*) instead of bullet points (•)
- Minimum 6 asterisks for consistent masking

**PHP Implementation:** Line 245
```php
$masked = substr($parts[0], 0, 1) . str_repeat('*', max(6, strlen($parts[0]) - 2)) . substr($parts[0], -1) . '@' . $parts[1];
```

**JavaScript Implementation:** Lines 586, 612
```javascript
const masked = parts[0].substring(0, 1) + '*'.repeat(Math.max(6, parts[0].length - 2)) + parts[0].substring(parts[0].length - 1) + '@' + parts[1];
```

**Examples:**
- `admin@example.com` → `m******o@gmail.com`
- `admin@example.com` → `a******n@example.com`

**Testing:**
- Log in as admin
- View dashboard user list
- Verify emails are masked correctly
- Check both server-side (PHP) and client-side (JavaScript) masking

---

### 5. Personalized Email Greetings ✓
**File Modified:** `admin/mailer.php` (v1.3.0)

**Changes:**
- Updated `send_magic_link_email()` function signature to accept optional `$username` parameter
- If no username provided, automatically extracts from email address (part before @)
- Email greeting changed from "Hello," to "Hello username,"

**Function Signature:** Line 84
```php
function send_magic_link_email($to, $magic_link_url, $username = null) {
    // If no username provided, extract from email
    if ($username === null) {
        $username = explode('@', $to)[0];
    }
    // ... rest of function
}
```

**Email Template:** Line 254
```html
<p><strong>Hello $username,</strong></p>
```

**Examples:**
- `user@example.com` receives "Hello user,"
- `admin@example.com` receives "Hello admin,"

**Backwards Compatible:** All existing calls to `send_magic_link_email()` will continue to work without modification.

**Testing:**
- Request a magic link via email
- Check inbox for email
- Verify greeting shows "Hello [username]," instead of generic "Hello,"

---

## Pending Tasks (Not Yet Implemented)

### 6. R5 Promotion/Demotion Rules ⏳
**Status:** In Progress

**Requirements:**
- R5 can promote users to R5 ✓ (already allowed)
- R5 cannot demote another R5 (needs implementation)
- R5 can only demote themselves (needs implementation)

**Implementation Plan:**
Add validation in `admin/admin_api.php` around line 231:
```php
// Check if trying to demote an R5 user to R4
if ($user['role'] === 'r5' && $role === 'r4') {
    // Get logged-in user's email
    $current_user_email = strtolower($user_token->sub);

    // Only allow if demoting self
    if (strtolower($email) !== $current_user_email) {
        $error = 'R5 users can only demote themselves, not other R5 users';
    }
}
```

---

### 7. JWT Token Revocation on User Deletion ⏳
**Status:** Pending

**Requirements:**
- When a user is deleted via the admin interface, all their JWT tokens should be immediately revoked
- This prevents deleted users from continuing to access the system with existing tokens

**Implementation Plan:**
Update `admin/admin_api.php` delete user handler (line 209):
```php
delete_user($email);

// Revoke all JWT tokens for deleted user
$user = get_user_by_email($email); // Get user data before deletion
if ($user && isset($user['active_sessions'])) {
    foreach ($user['active_sessions'] as $session) {
        blacklist_token($session['jti'], $session['exp']);
    }
}
```

**Also update `json_helpers.php`:**
Add token blacklisting to the `delete_user()` function.

---

### 8. Light/Dark Mode Toggle ⏳
**Status:** Pending

**Requirements:**
- Add theme toggle button to dashboard
- Implement light and dark color schemes
- Save user preference in cookie
- Persist preference across sessions

**Implementation Plan:**
1. Create CSS variables for light/dark themes
2. Add toggle button to dashboard header
3. JavaScript to handle theme switching
4. Cookie management (7-day expiration)
5. Apply saved theme on page load

**Files to modify:**
- `admin/dashboard.php` - Add theme toggle UI and JavaScript
- Consider creating `admin/css/themes.css` for theme definitions

---

## Files Modified Summary

| File | Version | Changes |
|------|---------|---------|
| `admin/alliance_edit.php` | v1.2.0 | R4 restrictions, UID labeling |
| `admin/dashboard.php` | v1.5.0 | Enhanced email masking |
| `admin/mailer.php` | v1.3.0 | Personalized email greetings |

## Deployment Instructions

### Pre-Deployment Checklist

1. **Backup Current Files**
   ```bash
   # On remote server
   cd /path/to/Server1586
   tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz admin/
   ```

2. **Review Changes**
   - Review git diff for all modified files
   - Verify no sensitive data (passwords, keys) in code
   - Check file permissions will be preserved

3. **Test Locally**
   - Run local PHP server: `php -S localhost:8000`
   - Test all modified functionality
   - Verify no PHP errors in logs

### Deployment Steps

#### Option 1: Git Deployment (Recommended)

```bash
# On local machine
cd C:/path/to/project

# Stage changes
git add admin/alliance_edit.php
git add admin/dashboard.php
git add admin/mailer.php

# Commit changes
git commit -m "Implement user requirements: R4 restrictions, UID labels, email masking, personalized greetings"

# Push to remote
git push origin mainline

# On remote server
cd /path/to/Server1586
git pull origin mainline
```

#### Option 2: FTP/SFTP Deployment

**Files to upload:**
```
admin/alliance_edit.php
admin/dashboard.php
admin/mailer.php
```

**Important:** Ensure file permissions are preserved:
- PHP files: 644 (-rw-r--r--)
- Directories: 755 (drwxr-xr-x)

### Post-Deployment Verification

1. **Check PHP Errors**
   ```bash
   # Monitor error log
   tail -f /var/log/php_errors.log
   # or
   tail -f /var/log/apache2/error.log
   ```

2. **Test R4 Restrictions**
   - Log in as R4 user
   - Navigate to alliance edit page
   - Verify fields are read-only

3. **Test Email Masking**
   - Log in as admin
   - View dashboard
   - Verify email masking format

4. **Test Magic Link Email**
   - Request magic link
   - Check email inbox
   - Verify personalized greeting

5. **Browser Console Check**
   - Open browser dev tools (F12)
   - Check for JavaScript errors
   - Verify no console warnings

6. **Database/JSON File Integrity**
   ```bash
   # Verify JSON files are valid
   php -r "json_decode(file_get_contents('admin/users.json')); echo json_last_error() === JSON_ERROR_NONE ? 'Valid' : 'Invalid';"
   ```

### Rollback Procedure

If issues occur after deployment:

#### Git Rollback
```bash
# On remote server
cd /path/to/Server1586
git log --oneline -5  # Find previous commit hash
git reset --hard <previous-commit-hash>
```

#### Manual Rollback
```bash
# Restore from backup
cd /path/to/Server1586
tar -xzf backup-YYYYMMDD-HHMMSS.tar.gz
```

### Monitoring

**First 24 Hours:**
- Monitor PHP error logs
- Check user feedback/reports
- Monitor server load
- Watch for failed login attempts

**Metrics to Track:**
- Magic link email delivery rate
- User login success rate
- Dashboard load times
- Browser console errors

---

## Known Issues / Limitations

### Current Limitations
1. **R5 Demotion Rules:** Not yet implemented (Task 6)
2. **JWT Token Revocation:** Not yet implemented on user deletion (Task 7)
3. **Theme Toggle:** Not yet implemented (Task 8)

### Future Enhancements
- Add user activity logging
- Implement session timeout warnings
- Add 2FA support
- Create admin audit trail

---

## Support & Troubleshooting

### Common Issues

#### Issue: R4 users report fields are not read-only
**Solution:** Clear browser cache, verify `admin/alliance_edit.php` version v1.2.0

#### Issue: Email masking not working
**Solution:** Check both PHP and JavaScript implementations in `dashboard.php`

#### Issue: Magic link emails show generic greeting
**Solution:** Verify `mailer.php` version v1.3.0, check email logs

### Contact
For deployment issues or questions:
- GitHub Issues: https://github.com/anthropics/claude-code/issues
- Review CLAUDE.md for project context

---

## Security Notes

### Authentication
- Magic link tokens expire after 10 minutes
- Tokens are single-use only
- JWT tokens use HS256 signing
- Token blacklisting implemented via `token_blacklist.json`

### Authorization
- Role-based access control (Admin, R5, R4)
- Alliance-based user management
- R5 users restricted to assigned alliances

### PII Protection
- Email addresses masked in UI (`a******b@domain.com`)
- Game UIDs only visible to admins/R5 in edit forms
- No UIDs displayed publicly

### Recommendations
- Rotate JWT secret key quarterly
- Monitor `token_blacklist.json` size (cleanup expired entries)
- Review user access logs monthly
- Keep PHPMailer updated (currently uses latest)

---

## Version History

### v1.3.0 (2025-10-13)
- Added R4 edit restrictions
- Changed labels from "Game ID" to "UID"
- Enhanced email masking (first + last char only)
- Personalized email greetings

### v1.2.0 (2025-10-13)
- Alliance-based access control for R5
- R5 user management capabilities
- Email configuration updates

### v1.1.0 (2025-10-12)
- JWT magic link authentication
- HTML email templates
- Token blacklisting

### v1.0.0 (2025-10-12)
- Initial admin system implementation

---

**Last Updated:** 2025-10-13
**Author:** Claude Code Assistant
**Status:** Ready for Deployment (Tasks 1-5 Complete)
