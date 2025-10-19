# Deployment Guide - Server 1586

**Version:** 3.0.0
**Last Updated:** 2025-10-19
**Status:** ✅ Production Deployment Active

---

## Table of Contents

1. [Overview](#overview)
2. [Automated CI/CD Deployment](#automated-cicd-deployment)
3. [Manual Deployment](#manual-deployment)
4. [Deployment History](#deployment-history)
5. [GitHub Actions Setup](#github-actions-setup)
6. [Environment Configuration](#environment-configuration)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The Server 1586 website uses automated GitHub Actions for continuous deployment. Every push to the `mainline` branch triggers:

1. ✅ **Test & Validation** - Unit tests, JSON/CSV validation
2. ✅ **FTP Deployment** - Automatic upload to production server
3. ✅ **Composer Install** - Backend dependencies installed via SSH
4. ✅ **Key Rotation** (conditional) - JWT keys rotated on major changes

**Production URL:** https://www.lastwar1586.online
**Admin Panel:** https://www.lastwar1586.online/admin/dashboard.php

---

## Automated CI/CD Deployment

### How It Works

GitHub Actions workflow (`.github/workflows/deploy.yml`) automatically deploys on every push to `mainline`:

```bash
git add .
git commit -m "Your changes"
git push origin mainline
```

### Workflow Steps

1. **Test Phase:**
   - Runs unit tests (`scripts/run-tests.py`)
   - Validates JSON files (alliances, rules, amendments, rotation schedule, server info, signatures)
   - Validates CSV files (power-history.csv)

2. **Deploy Phase:**
   - Creates production `.env` file from GitHub Secrets
   - Deploys files via FTP using `scripts/deploy-ftp-ci.py`
   - Installs Composer dependencies via SSH
   - Optionally rotates JWT keys (on `[major]`, `[rotate-keys]`, or `BREAKING CHANGE`)

3. **Deployment Summary:**
   - Shows deployed commit SHA
   - Lists deployment URLs
   - Confirms Composer installation

### Deployment Triggers

**Standard Deployment:**
```bash
git commit -m "Update alliance rankings"
```

**With Key Rotation:**
```bash
git commit -m "Major security update [major]"
# or
git commit -m "Security patch [rotate-keys]"
# or
git commit -m "feat: New API

BREAKING CHANGE: Old endpoints removed"
```

See [KEY_ROTATION_GUIDE.md](../KEY_ROTATION_GUIDE.md) for key rotation details.

---

## Manual Deployment

For emergency deployments or local testing:

### Prerequisites

**Python 3.7+** with `pywin32`:
```bash
pip install pywin32
```

### Deployment Script

```bash
python scripts/deploy-ftp-ci.py
```

The script:
- ✅ Reads FTP credentials from environment variables or Windows Credential Manager
- ✅ Respects `.ftpignore` exclusions
- ✅ Creates remote directories as needed
- ✅ Shows upload progress and summary

### FTP Credentials Setup

**GitHub Actions (automatic):**
- Stored in GitHub Secrets
- `FTP_HOST`, `FTP_USER`, `FTP_PASS`

**Local Development:**
- Stored in Windows Credential Manager
- Target: `ftp://your-server.com`
- Username: `your-ftp-username`
- Password: `your-ftp-password`

### Excluded Files (.ftpignore)

The following files are NOT deployed:
```
.git/
.github/
node_modules/
vendor/
*.md (except README.md)
.env.local*
test-token-*.json
admin/.env.backup.*
admin/secret_keys.json.backup.*
scripts/
ocr/
tesseract_training/
```

---

## Deployment History

### v3.0.0 - October 16, 2025
- ✅ Complete admin panel with JWT authentication
- ✅ Role-based access control (Admin, R5, R4, Power Editor)
- ✅ Multi-factor authentication
- ✅ Security monitoring and audit logging
- ✅ Backup & restore system
- ✅ Email masking for PII protection

### v2.0.0 - October 7, 2025
- ✅ Dynamic rank calculation based on power
- ✅ Alliance tags in rotation schedule (stable rankings)
- ✅ Fair rotation algorithm with fairness reporting
- ✅ Power trends chart with accurate date spacing

### v1.4.0 - October 6, 2025
- ✅ JSON data migration (no more hardcoded JS data)
- ✅ Amendment system with "Show Changes" toggle
- ✅ Collapsible sections for rules and amendments

### Power Editor Deployment (October 15, 2025)
- ✅ Power Editor (APE) role implementation
- ✅ Alliance power bulk editor with live statistics
- ✅ Add/delete alliances with dynamic rank calculation
- ✅ Unsaved changes warning

### CI/CD Setup (October 2025)
- ✅ GitHub Actions workflow implemented
- ✅ Automated FTP deployment
- ✅ SSH Composer installation
- ✅ Test validation pipeline
- ✅ JWT key rotation integration

---

## GitHub Actions Setup

### Required GitHub Secrets

Navigate to **Settings → Secrets and variables → Actions**:

| Secret Name | Description | Example |
|------------|-------------|---------|
| `FTP_HOST` | FTP server hostname | `ftp.example.com` |
| `FTP_USER` | FTP username | `ftpuser@example.com` |
| `FTP_PASS` | FTP password | `[secure_password]` |
| `SSH_HOST` | SSH server hostname | `ssh.example.com` |
| `SSH_PORT` | SSH port | `22` or `2222` |
| `SSH_USER` | SSH username | `sshuser` |
| `SSH_PRIVATE_KEY` | SSH private key (ED25519) | `-----BEGIN OPENSSH PRIVATE KEY-----` |
| `DEPLOY_PATH` | Server directory | `public_html` or `lastwar1586.online` |
| `APP_URL` | Production URL | `https://www.lastwar1586.online` |
| `JWT_SECRET_KEY` | JWT signing key | `[base64_encoded_key]` |
| `SMTP_HOST` | SMTP server | `mail.privateemail.com` |
| `SMTP_PORT` | SMTP port | `587` |
| `SMTP_USER` | SMTP username | `admin@example.com` |
| `SMTP_PASS` | SMTP password | `[secure_password]` |
| `SMTP_FROM` | From email | `admin@example.com` |
| `SMTP_FROM_NAME` | From name | `Last War 1586 Admin` |
| `ADMIN_EMAIL` | Admin email | `admin@example.com` |

### SSH Key Setup

1. **Generate ED25519 key:**
   ```bash
   ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_deploy
   ```

2. **Add public key to server:**
   ```bash
   cat ~/.ssh/github_deploy.pub
   # Copy output to server's ~/.ssh/authorized_keys
   ```

3. **Add private key to GitHub Secrets:**
   ```bash
   cat ~/.ssh/github_deploy
   # Copy entire output (including BEGIN/END lines) to SSH_PRIVATE_KEY secret
   ```

### Workflow File

Located at `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [mainline]
  workflow_dispatch:  # Manual trigger

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - Checkout code
      - Setup Python
      - Run unit tests
      - Validate JSON/CSV files

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - Create .env file
      - Deploy via FTP
      - Install Composer dependencies (SSH)
      - Rotate JWT keys (conditional)
```

---

## Environment Configuration

### Production .env File

Created automatically by GitHub Actions from secrets:

```env
# JWT Secret Key
SECRET_KEY=[from JWT_SECRET_KEY secret]

# SMTP Configuration
SMTP_HOST=[from SMTP_HOST secret]
SMTP_PORT=[from SMTP_PORT secret]
SMTP_USER=[from SMTP_USER secret]
SMTP_PASS=[from SMTP_PASS secret]
SMTP_FROM=[from SMTP_FROM secret]
SMTP_FROM_NAME="[from SMTP_FROM_NAME secret]"

# Application Configuration
APP_URL=[from APP_URL secret]
ADMIN_EMAIL=[from ADMIN_EMAIL secret]

# Token Expiry
MAGIC_LINK_EXPIRY=600        # 10 minutes
SESSION_TOKEN_EXPIRY=3600    # 1 hour

# Environment
APP_ENV=production
```

### Local .env File

For local development, copy `.env.example`:

```bash
cd admin
cp .env.example .env
# Edit .env with your local configuration
```

See [admin/ENV-CONFIG.md](../admin/ENV-CONFIG.md) for all environment variables.

---

## Troubleshooting

### Deployment Fails

**Check GitHub Actions logs:**
1. Go to **Actions** tab in GitHub
2. Click on failed workflow run
3. Expand failed step to see error

**Common Issues:**

**FTP Connection Failed:**
- Verify `FTP_HOST`, `FTP_USER`, `FTP_PASS` secrets
- Check if FTP server is accessible
- Verify server firewall allows GitHub Actions IPs

**SSH Connection Failed:**
- Verify `SSH_PRIVATE_KEY` includes `-----BEGIN OPENSSH PRIVATE KEY-----` headers
- Check `SSH_HOST`, `SSH_PORT`, `SSH_USER` secrets
- Ensure public key is in server's `~/.ssh/authorized_keys`

**Composer Install Failed:**
- Check `DEPLOY_PATH` points to correct directory
- Verify SSH user has write permissions
- Check if Composer is installed on server: `composer --version`

**Test Failures:**
- Run tests locally: `python scripts/run-tests.py`
- Validate JSON manually: `python -m json.tool data/alliances.json`
- Check CSV format: `python scripts/validate-csv.py`

### Manual Deployment Issues

**Windows Credential Manager not found:**
- Install `pywin32`: `pip install pywin32`
- Or set environment variables:
  ```bash
  set FTP_HOST=ftp.example.com
  set FTP_USER=username
  set FTP_PASS=password
  python scripts/deploy-ftp-ci.py
  ```

**Permission Denied:**
- Check FTP credentials
- Verify user has write access to target directory

### Key Rotation Issues

See [KEY_ROTATION_GUIDE.md](../KEY_ROTATION_GUIDE.md) for:
- Key sync problems
- Manual rotation
- Grace period configuration

---

## Best Practices

### Before Deployment

1. ✅ Test locally first
2. ✅ Run unit tests: `python scripts/run-tests.py`
3. ✅ Validate JSON: `python -m json.tool data/*.json`
4. ✅ Update version numbers in affected files
5. ✅ Write clear commit messages

### After Deployment

1. ✅ Verify website loads: https://www.lastwar1586.online
2. ✅ Check admin panel: https://www.lastwar1586.online/admin/
3. ✅ Review deployment logs in GitHub Actions
4. ✅ Test critical functionality (login, data updates)
5. ✅ Monitor error logs if available

### Security

- 🔒 Never commit `.env` files
- 🔒 Rotate secrets periodically
- 🔒 Use strong passwords for FTP/SSH
- 🔒 Enable 2FA on GitHub account
- 🔒 Limit SSH key access (deploy-only user recommended)

---

## Quick Reference

| Task | Command |
|------|---------|
| **Deploy to production** | `git push origin mainline` |
| **Manual deploy** | `python scripts/deploy-ftp-ci.py` |
| **Run tests** | `python scripts/run-tests.py` |
| **Validate JSON** | `python -m json.tool data/alliances.json` |
| **Update rotation** | `python scripts/update-rotation-schedule.py` |
| **View workflow** | GitHub → Actions tab |
| **Check deployment** | https://www.lastwar1586.online |

---

## Related Documentation

- **[README.md](../README.md)** - Project overview
- **[KEY_ROTATION_GUIDE.md](../KEY_ROTATION_GUIDE.md)** - JWT key rotation
- **[admin/DEPLOYMENT.md](../admin/DEPLOYMENT.md)** - Admin panel deployment
- **[admin/ENV-CONFIG.md](../admin/ENV-CONFIG.md)** - Environment variables
- **[scripts/README.md](../scripts/README.md)** - Deployment scripts

---

**Last Updated:** 2025-10-19
**Maintained By:** k33bz
**Production URL:** https://www.lastwar1586.online
