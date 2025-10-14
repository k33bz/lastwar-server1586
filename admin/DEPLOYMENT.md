# Admin Dashboard Deployment Guide

## Automated Deployment

The admin dashboard is automatically deployed to production via GitHub Actions when changes are pushed to the `mainline` branch.

### Files Included in Deployment:
- All PHP files (dashboard.php, admin_api.php, jwt.php, etc.)
- Composer vendor dependencies
- JSON data files (users.json, token_blacklist.json)
- Static assets (CSS, images if any)

### Files Excluded from Deployment:
The following files are excluded via `.ftpignore` and must be handled manually:
- `admin/.env` - Environment configuration with secrets
- `admin/test_*.php` - Test files
- `admin/.claude/` - Claude Code configuration
- `admin/.vscode/` - VS Code settings
- `admin/*.backup` - Backup files

## Manual Setup Required

### 1. Create Production .env File

After automated deployment completes, you must manually upload the `.env` file to the production server's `admin/` directory.

**File:** `admin/.env`

```ini
# JWT Configuration
SECRET_KEY=your-production-jwt-secret-key-here
TOKEN_EXPIRY=3600

# Application Configuration
APP_URL=https://yourdomain.com

# Email Configuration (PHPMailer)
SMTP_HOST=smtp.yourdomain.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=noreply@yourdomain.com
SMTP_PASSWORD=your-smtp-password-here
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=Server 1586 Admin
```

**Important Security Notes:**
- Generate a new `SECRET_KEY` for production (minimum 32 characters, random)
- Use production SMTP credentials (not development credentials)
- Never commit this file to git (already in .gitignore)
- Keep backup copy in secure location (password manager, encrypted storage)

**To generate a secure SECRET_KEY:**
```bash
# Using OpenSSL
openssl rand -base64 32

# Using PHP
php -r "echo bin2hex(random_bytes(32));"
```

### 2. Upload .env via FTP/cPanel

**Using FTP Client (FileZilla, Cyberduck, etc.):**
1. Connect to your production server
2. Navigate to `/public_html/admin/` (or your document root path)
3. Upload the `.env` file
4. Verify file permissions: `644` (readable by owner, not executable)

**Using cPanel File Manager:**
1. Log into cPanel
2. Open File Manager
3. Navigate to `public_html/admin/`
4. Click "Upload" and select your `.env` file
5. Verify file uploaded successfully

### 3. Verify Deployment

After uploading `.env`, verify the admin dashboard is working:

1. Visit: `https://yourdomain.com/admin/dashboard.php`
2. You should see "Authentication Required" message
3. Generate a magic link using the CLI tool (see below)
4. Click the magic link to access the dashboard
5. Verify you can view alliances and edit data

## Generating Admin Access

### Using Magic Link Generator

**On production server via SSH:**
```bash
cd /path/to/public_html/admin
php generate_magic_link.php your-admin-email@example.com
```

**Locally (for testing):**
```bash
cd admin
php generate_magic_link.php your-admin-email@example.com
```

The script will:
1. Generate a secure JWT token
2. Create a magic link URL
3. Send email to the specified address
4. Display the link in terminal (for debugging)

**Magic link format:**
```
https://yourdomain.com/admin/dashboard.php?token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Links expire after 1 hour (3600 seconds) by default.

## Deployment Checklist

- [ ] Code pushed to GitHub mainline branch
- [ ] GitHub Actions deployment completed successfully
- [ ] Production `.env` file created with secure credentials
- [ ] `.env` uploaded to production server's `admin/` directory
- [ ] File permissions verified (644 for .env)
- [ ] Admin dashboard accessible at production URL
- [ ] Magic link authentication working
- [ ] Able to view and edit alliance data
- [ ] Composer vendor dependencies deployed correctly
- [ ] No sensitive data exposed in public directories

## Troubleshooting

### "JWT Secret Key Not Configured"
- **Cause:** `.env` file missing or `SECRET_KEY` not set
- **Fix:** Ensure `.env` file uploaded to `admin/` directory with valid `SECRET_KEY`

### "Invalid Token" Error
- **Cause:** Token expired, invalid secret key, or token blacklisted
- **Fix:** Generate a new magic link with `generate_magic_link.php`

### "Failed to Load Alliances"
- **Cause:** Path to `data/alliances.json` incorrect or file not readable
- **Fix:** Verify `../data/alliances.json` exists and is readable (644 permissions)

### Email Not Sending
- **Cause:** SMTP credentials incorrect or PHPMailer not configured
- **Fix:** Verify SMTP settings in `.env`, check server logs for errors

### "Vendor Dependencies Missing"
- **Cause:** `vendor/` directory not deployed or composer dependencies not installed
- **Fix:** Verify FTP deployment includes `vendor/` directory, or run `composer install` on server

## Security Recommendations

1. **Change JWT Secret Regularly:** Rotate `SECRET_KEY` every 90 days
2. **Monitor Token Blacklist:** Periodically review `token_blacklist.json` for suspicious activity
3. **Limit Admin Access:** Only generate magic links for trusted users
4. **Use HTTPS Only:** Never access admin dashboard over HTTP
5. **Enable IP Whitelisting:** Add IP restrictions in `.htaccess` if possible
6. **Backup Configuration:** Keep encrypted backup of `.env` file
7. **Monitor Access Logs:** Review server logs for unauthorized access attempts

## Production URLs

- **Admin Dashboard:** https://yourdomain.com/admin/dashboard.php
- **Alliance Edit:** https://yourdomain.com/admin/alliance_edit.php?tag={TAG}
- **Public Website:** https://yourdomain.com/index.html

## Support

For issues with deployment:
1. Check GitHub Actions logs for FTP deployment errors
2. Verify `.ftpignore` settings are correct
3. Check server logs for PHP errors
4. Ensure file permissions are correct (644 for files, 755 for directories)

## Rollback Procedure

If deployment causes issues:

1. **Via Git:**
   ```bash
   git revert HEAD
   git push origin mainline
   ```

2. **Manual Rollback:**
   - Use FTP to restore previous version of files from backup
   - Restore previous `data/alliances.json` from backup

3. **Database/JSON Rollback:**
   - Admin system keeps backup files (`*.backup`)
   - Restore from `alliances.json.backup` if needed
