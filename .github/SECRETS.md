# GitHub Secrets Configuration

This document lists all secrets required for the GitHub Actions deployment workflow.

## How to Add Secrets

1. Go to your GitHub repository
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret listed below

## Required Secrets

### FTP Deployment (Already Configured ✅)

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `FTP_HOST` | FTP server hostname | `ftp.example.com` |
| `FTP_USER` | FTP username | `username@example.com` |
| `FTP_PASS` | FTP password | `your-ftp-password` |

### Admin Dashboard .env Configuration (New - Need to Add)

| Secret Name | Description | Example Value | Current Production Value |
|-------------|-------------|---------------|--------------------------|
| `JWT_SECRET_KEY` | JWT signing key (min 32 chars) | `your-random-secret-key-here` | `your-production-jwt-secret` |
| `SMTP_HOST` | SMTP server hostname | `mail.example.com` | `smtp.example.com` |
| `SMTP_PORT` | SMTP port (465 for SSL, 587 for TLS) | `465` | `465` |
| `SMTP_USER` | SMTP username (email address) | `noreply@example.com` | `noreply@yourdomain.com` |
| `SMTP_PASS` | SMTP password | `your-smtp-password` | `your-production-password` |
| `SMTP_FROM` | From email address | `noreply@example.com` | `noreply@yourdomain.com` |
| `SMTP_FROM_NAME` | From name displayed in emails | `Your Site Name` | `Your Production Name` |
| `APP_URL` | Production URL | `https://www.example.com` | `https://www.yourdomain.com` |
| `ADMIN_EMAIL` | Admin contact email | `admin@example.com` | `admin@yourdomain.com` |

## Quick Copy Commands

Copy the values from your local `.env` file and add them to GitHub Secrets:

```bash
# JWT Secret Key
JWT_SECRET_KEY=your-jwt-secret-key-from-local-env

# SMTP Configuration
SMTP_HOST=your-smtp-host
SMTP_PORT=465
SMTP_USER=noreply@yourdomain.com
SMTP_PASS=your-smtp-password
SMTP_FROM=noreply@yourdomain.com
SMTP_FROM_NAME=Your Site Name

# Application
APP_URL=https://www.yourdomain.com
ADMIN_EMAIL=admin@yourdomain.com
```

## Security Notes

⚠️ **IMPORTANT:**
- Never commit secrets to git
- Use strong, random JWT secret keys (minimum 32 characters)
- Rotate secrets regularly (every 90 days recommended)
- Use different secrets for development and production
- Keep backups of secrets in secure location (password manager)

## Generating New JWT Secret Key

If you need to generate a new JWT secret key:

### Using OpenSSL (recommended):
```bash
openssl rand -base64 64
```

### Using PHP:
```bash
php -r "echo base64_encode(random_bytes(64));"
```

### Using Python:
```bash
python -c "import secrets; print(secrets.token_urlsafe(64))"
```

## How the Workflow Uses Secrets

The GitHub Actions workflow (`.github/workflows/deploy.yml`) creates the production `.env` file during deployment:

1. **Before Deployment:** Workflow reads secrets from GitHub
2. **During Deployment:** Creates `admin/.env` with production values
3. **FTP Upload:** Deploys `.env` file to production server
4. **After Deployment:** Temporary `.env` file is discarded (not committed)

## Troubleshooting

### "Secret not found" Error
- Verify secret name matches exactly (case-sensitive)
- Check you added secret to correct repository
- Ensure secret is not empty

### JWT Errors After Deployment
- Verify `JWT_SECRET_KEY` matches exactly (no extra spaces)
- Check secret is at least 32 characters long
- Regenerate magic links after changing secret

### Email Not Sending
- Verify SMTP credentials are correct
- Check SMTP port (465 for SSL, 587 for TLS)
- Test SMTP credentials using admin test scripts

## Verification Checklist

After adding secrets, verify:

- [ ] All 9 new secrets added to GitHub repository settings
- [ ] Secret values copied exactly from production .env file
- [ ] No extra spaces or newlines in secret values
- [ ] FTP secrets (FTP_HOST, FTP_USER, FTP_PASS) still working
- [ ] Workflow file (`.github/workflows/deploy.yml`) committed and pushed
- [ ] Test deployment triggered successfully
- [ ] Admin dashboard accessible after deployment
- [ ] Magic link email sends successfully
- [ ] JWT authentication working

## Support

If you encounter issues:
1. Check GitHub Actions logs for specific error messages
2. Verify secret values match production .env file exactly
3. Test SMTP credentials using `admin/test-smtp.php` script
4. Generate new JWT secret if authentication fails
