# Deployment Guide - Server 1586

**Version:** 3.4.0
**Last Updated:** 2025-11-02
**Status:** ✅ Production Active

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Deployment Methods](#deployment-methods)
   - [Automated CI/CD (GitHub Actions)](#1-automated-cicd-github-actions)
   - [Manual FTP Deployment](#2-manual-ftp-deployment)
   - [Incremental Deployment](#3-incremental-deployment)
   - [Public Site Only](#4-public-site-only-deployment)
4. [Version Migration System](#version-migration-system)
5. [Environment Configuration](#environment-configuration)
6. [Verification & Testing](#verification--testing)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

---

## Overview

Server 1586 supports multiple deployment strategies:

| Deployment Type | Use Case | Method |
|----------------|----------|--------|
| **Automated CI/CD** | Production | GitHub Actions (FTP + SSH) |
| **Incremental** | Fast updates | Python script with caching |
| **Manual** | Emergency/testing | Python FTP script |
| **Public site only** | Static frontend | FTP/Netlify/Vercel |

**Production URLs:**
- Public Site: https://www.example.com
- Admin Panel: https://www.example.com/admin/dashboard.php

---

## Quick Start

### For Developers (Automated)

```bash
# 1. Make changes
git add .
git commit -m "feat: Update alliance rankings"

# 2. Push to production
git push origin mainline

# 3. GitHub Actions automatically:
#    - Runs tests
#    - Deploys via FTP
#    - Installs Composer dependencies
#    - Verifies deployment
```

### For Production (After Deployment)

```bash
# 1. Check for migration warning in admin panel
# 2. If migration needed, run:
php admin/migrate.php

# 3. Verify site is working
curl https://www.example.com/version.json
```

---

## Deployment Methods

### 1. Automated CI/CD (GitHub Actions)

**Best for: Production deployments**

#### How It Works

Every push to `mainline` branch triggers automated deployment:

```yaml
Test → FTP Deploy → Composer Install → Verify → Migrations (if needed)
```

#### GitHub Secrets Required

Navigate to **Settings → Secrets and variables → Actions**:

| Secret | Description | Example |
|--------|-------------|---------|
| `FTP_HOST` | FTP server | `ftp.example.com` |
| `FTP_USER` | FTP username | `user@example.com` |
| `FTP_PASS` | FTP password | `secure_password` |
| `SSH_HOST` | SSH server | `ssh.example.com` |
| `SSH_PORT` | SSH port | `21098` |
| `SSH_USER` | SSH username | `sshuser` |
| `SSH_PRIVATE_KEY` | ED25519 key | `-----BEGIN OPENSSH...` |
| `DEPLOY_PATH` | Server directory | `public_html` |
| `APP_URL` | Production URL | `https://www.example.com` |
| `JWT_SECRET_KEY` | JWT signing key | `base64_string` |
| `SMTP_HOST` | Mail server | `smtp.example.com` |
| `SMTP_PORT` | Mail port | `587` |
| `SMTP_USER` | Mail username | `admin@example.com` |
| `SMTP_PASS` | Mail password | `secure_password` |
| `SMTP_FROM` | From email | `admin@example.com` |
| `SMTP_FROM_NAME` | From name | `Server 1586 Admin` |
| `ADMIN_EMAIL` | Admin email | `admin@example.com` |

#### SSH Key Setup

```bash
# 1. Generate ED25519 key
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_deploy

# 2. Add public key to server
cat ~/.ssh/github_deploy.pub
# Copy to server's ~/.ssh/authorized_keys

# 3. Add private key to GitHub Secrets
cat ~/.ssh/github_deploy
# Copy entire output to SSH_PRIVATE_KEY secret
```

#### Workflow Triggers

**Standard deployment:**
```bash
git commit -m "Update alliance rankings"
git push origin mainline
```

**With key rotation:**
```bash
git commit -m "Security update [rotate-keys]"
# or
git commit -m "feat: New API

BREAKING CHANGE: Old endpoints removed"
```

#### What Gets Deployed

**Included:**
- `index.html`, CSS, JavaScript
- `data/*.json`, `data/*.csv`
- `admin/*.php`
- `admin/vendor/` (Composer dependencies)
- `version.json`

**Excluded (.ftpignore):**
- `admin/.env` ⚠️ Must be manually configured
- `admin/test_*.php`
- `.git/`, `.github/`, `.claude/`
- `scripts/`, `docs/`, `*.md`
- Backup files (`*.backup`, `*.bak`)

---

### 2. Manual FTP Deployment

**Best for: Emergency updates, testing**

#### Prerequisites

```bash
pip install pywin32  # Windows only
```

#### Setup Credentials

**Windows Credential Manager:**
```powershell
cmdkey /generic:ftp_example.com /user:USERNAME /pass:"PASSWORD"
```

**Or set environment variables:**
```bash
set FTP_HOST=ftp.example.com
set FTP_USER=username
set FTP_PASS=password
```

#### Deploy

```bash
# Deploy everything
python scripts/deploy-ftp-ci.py

# Check what will be deployed
python scripts/deploy-ftp-ci.py --dry-run
```

#### Output Example

```
==================================================
  Connecting to ftp.example.com...
  ✓ Connected successfully
==================================================

Uploading files...
  [  5%] index.html (15.2 KB)
  [ 10%] admin/dashboard.php (18.7 KB)
  ...
  [100%] version.json (1.2 KB)

==================================================
Deployment Summary:
  Uploaded: 198 files
  Failed:   0 files
  Duration: 287 seconds
==================================================
```

---

### 3. Incremental Deployment

**Best for: Fast updates (80-90% faster)**

#### How It Works

Uses MD5 checksums + modification timestamps to detect changes:

```python
Scan files → Calculate checksums → Compare with cache → Upload only changed
```

**Performance:**
- Full deployment: 200 files, 4-5 minutes
- Incremental: 5-10 files, 30-45 seconds

#### Usage

**Standard (incremental):**
```bash
python scripts/deploy-ftp-incremental.py
```

**Force full upload:**
```bash
python scripts/deploy-ftp-incremental.py --force
```

**Checksum-only (ignore timestamps):**
```bash
python scripts/deploy-ftp-incremental.py --checksum-only
```

#### Cache Management

**Local deployment:**
- State cached in `.deploy-state.json` (gitignored)
- Safe to delete (triggers full upload next time)

**GitHub Actions:**
- Uses `actions/cache` with 7-day retention
- Automatically restored/saved

#### Output Example

```
==================================================
Server 1586 - Incremental FTP Deployment
==================================================

[1/5] Loading deployment state...
      ✓ Last deployment: 2025-11-02T12:00:00
      ✓ 198 files in cache

[2/5] Loading .ftpignore patterns...
      ✓ Loaded 45 patterns

[3/5] Analyzing files...
      ✓ Total files: 200
      ✓ To upload: 8
      ✓ Skipped: 192 (unchanged)

[4/5] Uploading 8 changed files (47.3 KB)...
      [ 12.5%] admin/migrate.php (15.2 KB) [modified]
      [ 25.0%] version.json (1.2 KB) [modified]
      ...
      [100.0%] admin/jwt.php (8.1 KB) [modified]

[5/5] Saving deployment state...
      ✓ State saved

==================================================
Deployment Summary:
  Total files:   200
  Uploaded:      8 files
  Skipped:       192 files (unchanged)
  Time savings:  96% faster
  Duration:      38.2 seconds
==================================================
```

#### Change Detection Logic

1. **Force flag** (`--force`): Upload everything
2. **New file**: Not in cache → Upload
3. **Modification time**: File modified → Check checksum
4. **MD5 Checksum**: Checksum different → Upload

---

### 4. Public Site Only Deployment

**Best for: Static frontend without admin panel**

#### Files Required

```
Server1586-clean/
├── index.html
├── css/styles.css
├── js/app.js
├── data/
│   ├── alliances.json
│   ├── rules.json
│   ├── amendments.json
│   ├── rotation-schedule.json
│   ├── server-info.json
│   ├── signature-history.json
│   ├── power-history.csv
│   └── council.js
├── version.json
└── images/ (optional)
```

**Total Size:** ~150-200 KB (uncompressed)

#### Method A: FTP Client (FileZilla)

1. Connect to FTP server
2. Navigate to `public_html/`
3. Upload files (preserve structure)
4. Set permissions: Files `644`, Directories `755`

#### Method B: Python Script

```python
# deploy_public_site.py
import ftplib

FTP_HOST = "ftp.example.com"
FTP_USER = "username"
FTP_PASS = "password"
REMOTE_DIR = "/public_html"

files = [
    "index.html",
    "version.json",
    "css/styles.css",
    "js/app.js",
    "data/alliances.json",
    "data/rules.json",
    # ... add all data files
]

ftp = ftplib.FTP(FTP_HOST)
ftp.login(FTP_USER, FTP_PASS)
ftp.cwd(REMOTE_DIR)

for file in files:
    with open(file, 'rb') as f:
        ftp.storbinary(f'STOR {file}', f)
    print(f"✓ {file}")

ftp.quit()
```

#### Method C: Static Hosts (Zero Config)

**Netlify Drop:**
- Drag & drop folder to https://app.netlify.com/drop
- Instant deployment with HTTPS

**Vercel:**
```bash
npm install -g vercel
vercel --prod
```

**GitHub Pages:**
- Enable in repo settings
- Select `mainline` branch
- Site at: `https://username.github.io/repo-name`

#### Method D: AWS S3

```bash
aws s3 sync . s3://your-bucket-name \
  --exclude "admin/*" \
  --exclude "scripts/*" \
  --exclude ".git/*" \
  --exclude "*.md"
```

---

## Version Migration System

⭐ Automatic schema migrations for production deployments (v3.1.0+)

### Overview

After code deployment, if versions don't match:
1. Admin page shows orange warning banner: **"⬆️ Migration Required"**
2. Click "Run Migration Now" or run `php admin/migrate.php`
3. Migrations execute in order (e.g., 3.0.0 → 3.1.0 → 3.2.0)
4. `.installed_version` updated to match code version

### Running Migrations

**Option 1: Web Interface**
1. Log into admin panel
2. Click **"🔧 Run Migration Now"** in warning banner

**Option 2: CLI (Recommended)**
```bash
# SSH into production server
ssh user@server
cd /path/to/admin
php migrate.php
```

**Example Output:**
```
=== Version Migration System ===
Code version: 3.4.0
Installed version: 3.3.2

🔄 Migration needed: 3.3.2 → 3.4.0

🔧 Running migration: 3.4.0
   - Migrating to multi-role system...
   💾 Backup created: users.json.bak.2025-11-02_120000
   ✓ Users migrated to multi-role format
   ✓ Completed: 3.4.0

=== Migration Summary ===
Migrations run: 1
Errors: 0

✅ Migration completed successfully!
```

### Available Migrations

| Version | Description |
|---------|-------------|
| v3.0.0 | JWT authentication setup, validate .env |
| v3.1.0 | Add r5History to alliances |
| v3.2.0 | Initialize audit logging system |
| v3.3.0 | Create backup directory with .htaccess |
| v3.4.0 | Multi-role system migration |

### Troubleshooting

**Migration warning still appears:**
```bash
cat version.json                  # Check code version
cat admin/.installed_version      # Check installed version
echo "3.4.0" > admin/.installed_version  # Manual sync if needed
```

**Migration failed:**
```bash
# Restore from backup
cp admin/users.json.bak.2025-11-02_120000 admin/users.json
# Fix issue and re-run
php admin/migrate.php
```

See [docs/admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md) for complete documentation.

---

## Environment Configuration

### Production .env File

⚠️ **CRITICAL**: Admin panel requires `.env` file for JWT authentication. This file is **NOT deployed automatically**.

**Manual setup required:**

```bash
# 1. SSH into production server
ssh user@server

# 2. Navigate to admin directory
cd /path/to/public_html/admin

# 3. Create .env file
nano .env
```

**File contents:**
```ini
# JWT Configuration
SECRET_KEY=your-64-character-production-secret-key-here
TOKEN_EXPIRY=3600

# Application Configuration
APP_URL=https://www.example.com
ADMIN_EMAIL=admin@example.com

# Email Configuration (PHPMailer)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=noreply@example.com
SMTP_PASSWORD=your-smtp-password-here
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME=Server 1586 Admin

# Token Expiry
MAGIC_LINK_EXPIRY=600        # 10 minutes
SESSION_TOKEN_EXPIRY=3600    # 1 hour

# Environment
APP_ENV=production
```

**Generate secure SECRET_KEY:**
```bash
# Using OpenSSL
openssl rand -base64 32

# Using PHP
php -r "echo bin2hex(random_bytes(32));"
```

**Set permissions:**
```bash
chmod 644 .env
```

### GitHub Actions .env Creation

GitHub Actions automatically creates `.env` from secrets during deployment.

⚠️ **SECURITY NOTE**: The `.env` file is excluded from FTP deployment (`.ftpignore`) to prevent overwriting production keys. This prevents mass user logouts caused by JWT key rotation. See [docs/PRODUCTION-ENV-SETUP.md](PRODUCTION-ENV-SETUP.md).

---

## Verification & Testing

### Post-Deployment Checklist

**1. Check Homepage**
```bash
curl -I https://www.example.com/
# Should return: HTTP/1.1 200 OK
```

**2. Verify Version**
```bash
curl https://www.example.com/version.json
# Should return JSON with current version
```

**3. Check Admin Panel**
```bash
curl -I https://www.example.com/admin/dashboard.php
# Should return: HTTP/1.1 200 OK (or redirect to login)
```

**4. Test Data Files**
```bash
curl https://www.example.com/data/alliances.json
curl https://www.example.com/data/rules.json
# Each should return valid JSON
```

**5. Run Migrations (if needed)**
```bash
# Check for migration warning in admin panel
# If present, run:
php admin/migrate.php
```

**6. Test in Browser**
- Visit https://www.example.com
- Check console for errors (F12 → Console)
- Verify all sections load correctly
- Test admin panel access

**7. Mobile Test**
- Resize browser to mobile width
- Check responsive layout
- Test hamburger menu
- Verify all functionality works

---

## Troubleshooting

### Deployment Failures

**GitHub Actions failed:**
1. Go to **Actions** tab in GitHub
2. Click failed workflow run
3. Expand failed step for error details

**Common issues:**

**FTP Connection Failed**
- Verify `FTP_HOST`, `FTP_USER`, `FTP_PASS` secrets
- Check server firewall allows FTP connections
- Test credentials manually with FileZilla

**SSH Connection Failed**
- Verify `SSH_PRIVATE_KEY` includes headers
- Check public key in server's `authorized_keys`
- Test SSH access: `ssh -i key user@host`

**Composer Install Failed**
- Verify `DEPLOY_PATH` is correct
- Check SSH user has write permissions
- Ensure Composer installed: `composer --version`

### Admin Panel Issues

**"JWT Secret Key Not Configured"**
- `.env` file missing or `SECRET_KEY` not set
- Upload `.env` to `admin/` directory
- Generate secure key (see Environment Configuration)

**"Invalid Token" Error**
- Token expired (10 minutes for magic links)
- Generate new link: `php admin/generate_magic_link.php email@example.com`

**"Failed to Load Alliances"**
- Path to `data/alliances.json` incorrect
- Verify file exists and is readable (644 permissions)

**Composer Dependencies Missing**
- `vendor/` directory not deployed
- Run: `cd admin && composer install`

### Website Issues

**JSON Errors in Console**
```bash
# Validate JSON syntax
python -m json.tool data/alliances.json
python -m json.tool data/rules.json
```

**Chart Doesn't Load**
- Check browser console for CDN errors
- Verify `data/power-history.csv` format
- Run: `python scripts/validate-csv.py`

**Styles Not Applied**
- Clear browser cache: Ctrl+Shift+R
- Check CSS loaded: `curl https://www.example.com/css/styles.css`
- Verify version cache bust in page source

**Navigation Menu Not Working**
- Check JavaScript loaded (F12 console)
- Verify `data/council.js` accessible
- Check for JavaScript errors

### Incremental Deployment Issues

**Cache Not Working (always uploads all files)**
```bash
# Local: Check state file exists
ls -la .deploy-state.json

# GitHub Actions: Check cache step in logs
# Look for "Cache restored from key: ftp-deploy-state-..."
```

**Files Not Uploading When They Should**
```bash
# Force full upload
python scripts/deploy-ftp-incremental.py --force

# Or delete state and re-deploy
rm .deploy-state.json
python scripts/deploy-ftp-incremental.py
```

---

## Best Practices

### Before Deployment

1. ✅ Test locally first
2. ✅ Run unit tests: `python scripts/run-tests.py`
3. ✅ Validate JSON: `python -m json.tool data/*.json`
4. ✅ Update version numbers
5. ✅ Write clear commit messages

### After Deployment

1. ✅ Verify website loads
2. ✅ Check version.json accessible
3. ✅ Look for migration warning banner
4. ✅ Run migrations if needed: `php admin/migrate.php`
5. ✅ Test admin panel login
6. ✅ Review deployment logs
7. ✅ Monitor error logs

### Security

- 🔒 Never commit `.env` files
- 🔒 Rotate secrets periodically (every 90 days)
- 🔒 Use strong passwords for FTP/SSH
- 🔒 Enable 2FA on GitHub account
- 🔒 Limit SSH key access (deploy-only user)
- 🔒 Always use HTTPS in production

### Performance

- ⚡ Use incremental deployment for fast updates
- ⚡ Enable gzip compression (`.htaccess`)
- ⚡ Set browser caching headers
- ⚡ Minify CSS/JS for production (optional)
- ⚡ Use CDN for static assets

---

## Quick Reference

| Task | Command |
|------|---------|
| **Deploy to production** | `git push origin mainline` |
| **Run migrations** | `php admin/migrate.php` |
| **Manual deploy** | `python scripts/deploy-ftp-ci.py` |
| **Incremental deploy** | `python scripts/deploy-ftp-incremental.py` |
| **Force full upload** | `python scripts/deploy-ftp-incremental.py --force` |
| **Run tests** | `python scripts/run-tests.py` |
| **Validate JSON** | `python -m json.tool data/alliances.json` |
| **Check deployment** | `curl https://www.example.com/version.json` |
| **Generate admin access** | `php admin/generate_magic_link.php email@example.com` |

---

## Related Documentation

- [docs/admin/MIGRATION_SYSTEM.md](admin/MIGRATION_SYSTEM.md) - Version migration system
- [docs/PRODUCTION-ENV-SETUP.md](PRODUCTION-ENV-SETUP.md) - Production .env management
- [docs/admin/ENV-CONFIG.md](admin/ENV-CONFIG.md) - Environment variables
- [docs/GIT_HOOKS.md](GIT_HOOKS.md) - Git hooks for quality gates
- [docs/GITHUB_RELEASES.md](GITHUB_RELEASES.md) - Release process
- [scripts/README.md](../scripts/README.md) - Deployment scripts

---

**Last Updated:** 2025-11-02
**Maintained By:** k33bz
**Production URL:** https://www.example.com
**GitHub Issues:** https://github.com/k33bz/lastwar-server1586/issues
