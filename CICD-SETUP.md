# CI/CD Setup Guide

This guide walks you through setting up automated deployment with GitHub Actions.

## Overview

When you push to the `mainline` branch, GitHub Actions will automatically:
1. ✅ Run unit tests (`scripts/run-tests.py`)
2. ✅ Validate JSON files format
3. ✅ Validate CSV files format
4. 🚀 Deploy to production via FTP (if tests pass)

**No manual deployment needed!** Just `git push` and everything happens automatically.

---

## Initial Setup (One-Time)

### Step 1: Add FTP Credentials to GitHub Secrets

1. Go to your repository on GitHub
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add three secrets:

   | Name | Value | Example |
   |------|-------|---------|
   | `FTP_HOST` | Your FTP server hostname | `ftp.example.com` |
   | `FTP_USER` | Your FTP username | `ftpuser@example.com` |
   | `FTP_PASS` | Your FTP password | `your-password-here` |

**Important:** Never commit FTP credentials to the repository! They must only be stored in GitHub Secrets.

### Step 2: Verify Workflow Files Exist

The following files should already be in your repository:

```
.github/
└── workflows/
    └── deploy.yml            # GitHub Actions workflow

scripts/
├── deploy-ftp-ci.py         # CI-compatible FTP deployment
├── validate-csv.py          # CSV validation script
└── run-tests.py             # Unit tests
```

If any files are missing, they need to be created.

### Step 3: Push Workflow Files to GitHub

```bash
git add .github/ scripts/
git commit -m "Add GitHub Actions CI/CD pipeline"
git push origin mainline
```

---

## How It Works

### Automatic Deployment

Every time you push to `mainline`:

```bash
git add .
git commit -m "Your changes"
git push origin mainline
```

GitHub Actions will:
1. **Run Tests** - Validates all data files and runs unit tests
2. **Deploy** - Uploads files to production FTP server (if tests pass)
3. **Notify** - Shows success/failure in GitHub UI

### Manual Deployment

You can also trigger deployment manually:

1. Go to **Actions** tab in GitHub
2. Click **Deploy to Production** workflow
3. Click **Run workflow** → **Run workflow**

---

## Monitoring Deployments

### View Deployment Status

1. Go to **Actions** tab in your GitHub repository
2. Click on the latest workflow run
3. View logs for each step (test, deploy)

### Deployment Failed?

If deployment fails, check the logs:

1. **Test failures** - Fix the failing test, commit, and push again
2. **FTP errors** - Verify FTP credentials in GitHub Secrets
3. **Permission errors** - Check FTP user has write permissions

---

## Workflow Configuration

### File: `.github/workflows/deploy.yml`

```yaml
on:
  push:
    branches: [mainline]      # Auto-deploy on push to mainline
  workflow_dispatch:          # Allow manual trigger
```

### Jobs

**1. Test Job**
- Runs unit tests (`run-tests.py`)
- Validates JSON files
- Validates CSV files

**2. Deploy Job**
- Only runs if tests pass
- Uploads files via FTP
- Excludes files listed in `.ftpignore`

---

## Local vs CI Deployment

### Local Deployment (Manual)

```bash
python scripts/deploy-ftp.py
```
- Reads credentials from **Windows Credential Manager**
- Used for manual deployments from your local machine
- Still works for testing/emergencies

### CI Deployment (Automatic)

```bash
# Triggered automatically on push to mainline
git push origin mainline
```
- Reads credentials from **GitHub Secrets**
- Used by GitHub Actions
- Fully automated

---

## Testing the Pipeline

### Test Without Deploying

You can test validation locally:

```bash
# Run unit tests
python scripts/run-tests.py

# Validate JSON files
python -m json.tool data/alliances.json

# Validate CSV files
python scripts/validate-csv.py
```

### Test Full Pipeline

1. Make a small change (e.g., add a comment to a file)
2. Commit and push:
   ```bash
   git add .
   git commit -m "Test CI/CD pipeline"
   git push origin mainline
   ```
3. Go to **Actions** tab in GitHub
4. Watch the workflow run in real-time
5. Verify deployment succeeded
6. Check https://www.example.com to confirm changes

---

## Rollback Strategy

If a bad deployment goes live:

### Option 1: Revert the Commit

```bash
git revert HEAD
git push origin mainline
```
- Creates a new commit that undoes the bad changes
- CI/CD automatically deploys the reverted version

### Option 2: Manual Deployment

```bash
git checkout <good-commit-hash>
python scripts/deploy-ftp.py
git checkout mainline
```
- Manually deploy a previous good version
- Then fix the issue and push the fix

---

## Troubleshooting

### "FTP_HOST secret not found"

**Problem:** GitHub Secrets not configured

**Solution:** Add secrets in GitHub Settings → Secrets and variables → Actions

### "Tests failed"

**Problem:** JSON syntax error or CSV format issue

**Solution:**
1. Check the error message in GitHub Actions logs
2. Fix the issue locally
3. Commit and push again

### "Permission denied" during deployment

**Problem:** FTP user doesn't have write permissions

**Solution:** Contact FTP host administrator to grant write permissions

### Workflow doesn't run

**Problem:** Workflow file syntax error or wrong branch

**Solution:**
1. Verify `.github/workflows/deploy.yml` exists
2. Verify you pushed to `mainline` branch
3. Check GitHub Actions tab for error messages

---

## Advanced Configuration

### Modify Deployment Trigger

Edit `.github/workflows/deploy.yml`:

```yaml
# Deploy on multiple branches
on:
  push:
    branches: [mainline, staging]

# Deploy only on tags
on:
  push:
    tags:
      - 'v*'
```

### Add More Tests

Add validation scripts in `scripts/` and update workflow:

```yaml
- name: Custom validation
  run: python scripts/my-validation.py
```

### Deployment Notifications

Add a notification step to workflow:

```yaml
- name: Notify deployment
  if: success()
  run: |
    curl -X POST https://discord.com/api/webhooks/... \
      -d '{"content": "Deployment successful!"}'
```

---

## Benefits of CI/CD

✅ **Automated Testing** - Catch errors before deployment
✅ **Fast Deployment** - No manual FTP uploads
✅ **Audit Trail** - All deployments tracked in GitHub
✅ **Consistency** - Same process every time
✅ **Rollback** - Easy to revert bad deployments
✅ **Free** - GitHub Actions is free for public repos

---

## Support

For issues with:
- **GitHub Actions** - Check GitHub Actions documentation
- **FTP Deployment** - Contact FTP host support
- **This project** - Open an issue on GitHub

---

**Last Updated:** 2025-10-10
