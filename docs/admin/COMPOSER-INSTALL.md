# Manual Composer Installation Guide

The vendor directory cannot be deployed via FTP due to nested directory structure limitations. This guide shows how to install Composer dependencies manually on your production server.

## Why Manual Installation?

- **FTP Limitations**: 161 vendor files failed to upload (error: "553 Can't open that file: No such file or directory")
- **SSH Not Available**: Port 22 and 2222 timeout - SSH access not enabled or restricted
- **Best Practice**: Installing dependencies on the server ensures correct PHP version compatibility

## Solution: Use cPanel or Manual Upload

### Option 1: cPanel Terminal (Recommended)

If your hosting provider offers cPanel with Terminal access:

1. **Log into cPanel**
   - URL: Usually `https://yourdomain.com:2083` or check with your hosting provider
   - Login with your hosting credentials

2. **Open Terminal**
   - Look for "Terminal" icon in cPanel
   - Or search for "Terminal" or "SSH Access" in cPanel search

3. **Navigate to Admin Directory**
   ```bash
   cd public_html/admin
   # or
   cd www/admin
   # (path varies by host)
   ```

4. **Install Composer Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. **Verify Installation**
   ```bash
   ls -la vendor/
   # Should show autoload.php and directories
   ```

6. **Test Admin Dashboard**
   - Visit: https://www.example.com/admin/dashboard.php
   - Should no longer show "Composer dependencies not installed" error

### Option 2: cPanel File Manager Upload

If Terminal is not available:

1. **Install Composer Locally**
   ```bash
   cd C:\path\to\project\admin
   composer install --no-dev --optimize-autoloader
   ```

2. **Create Zip Archive**
   ```bash
   # Windows PowerShell
   Compress-Archive -Path vendor -DestinationPath vendor.zip

   # Or use 7-Zip, WinRAR, etc.
   ```

3. **Upload via cPanel File Manager**
   - Log into cPanel
   - Open "File Manager"
   - Navigate to `public_html/admin/`
   - Click "Upload"
   - Select `vendor.zip` file
   - Wait for upload to complete

4. **Extract Archive**
   - Right-click `vendor.zip` in File Manager
   - Select "Extract"
   - Confirm extraction to current directory
   - Delete `vendor.zip` after extraction

5. **Verify Permissions**
   - Select `vendor` folder
   - Click "Permissions" or "Change Permissions"
   - Set to `755` (folders) and `644` (files)

### Option 3: FTP Upload Individual Directories

If cPanel is not available:

1. **Install Locally**
   ```bash
   cd C:\path\to\project\admin
   composer install --no-dev --optimize-autoloader
   ```

2. **Upload via FTP Client (FileZilla, etc.)**
   - Connect to FTP server
   - Navigate to `admin/` directory
   - Create `vendor/` directory manually
   - Upload `vendor/autoload.php`
   - Upload each vendor subdirectory one at a time:
     - vendor/composer/
     - vendor/firebase/
     - vendor/phpmailer/
     - vendor/vlucas/
     - vendor/symfony/
     - vendor/phpoption/
     - vendor/graham-campbell/

   **Note**: This is slow and tedious - use Option 1 or 2 if possible

## Required Dependencies

The following packages will be installed:

| Package | Version | Purpose |
|---------|---------|---------|
| `phpmailer/phpmailer` | ^6.9 | Email functionality (magic links) |
| `firebase/php-jwt` | ^6.10 | JWT authentication |
| `vlucas/phpdotenv` | ^5.6 | .env file loading |
| `symfony/polyfill-*` | Various | PHP compatibility layers |

## Troubleshooting

### "composer: command not found"

Your server doesn't have Composer installed. Contact your hosting provider or use Option 2/3.

### "Memory limit exceeded"

Increase PHP memory limit in cPanel or ask hosting provider:
```bash
php -d memory_limit=512M $(which composer) install --no-dev
```

### Permission Denied Errors

Fix file permissions:
```bash
chmod -R 755 vendor/
find vendor/ -type f -exec chmod 644 {} \;
```

### Existing vendor/ directory

Remove old incomplete vendor directory first:
```bash
rm -rf vendor/
composer install --no-dev --optimize-autoloader
```

## After Installation

1. Visit admin dashboard: https://www.example.com/admin/dashboard.php
2. Should see "Authentication Required" (not the Composer error)
3. Generate magic link to access dashboard
4. Verify all admin functions work correctly

## Why --no-dev --optimize-autoloader?

- `--no-dev`: Skips development dependencies (testing, debugging tools)
- `--optimize-autoloader`: Creates optimized class maps for faster loading
- Both flags are production best practices

## Future Updates

When updating dependencies:

1. Update `composer.json` locally
2. Run `composer update` locally
3. Test changes locally
4. Push `composer.json` and `composer.lock` to GitHub
5. Re-run one of the installation options above on production

## SSH Access (For Future)

If you want to enable SSH for automated deployments:

1. Contact your hosting provider about SSH access
2. Provide them with your public SSH key
3. They will enable SSH and provide connection details (hostname, port, username)
4. We can then automate composer install via GitHub Actions

Your hosting provider might use a non-standard SSH port or require specific configuration.

## Current Status

✅ **Admin .env deployed** - Configuration file with secrets working
✅ **Admin PHP files deployed** - All source code on server
❌ **Vendor dependencies** - Need manual installation (use this guide)

Once vendor is installed, the admin dashboard will be fully functional!
