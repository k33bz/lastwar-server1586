# Environment Configuration Guide

The admin system uses environment variables to support both local development and production deployment without code changes.

## Quick Setup

### Local Development

1. **Copy the example file:**
   ```bash
   cd admin/
   cp .env.local.example .env
   ```

2. **Update APP_URL in .env:**
   ```env
   APP_URL=http://localhost:8080
   ```

3. **Start local server:**
   ```bash
   cd ..  # Go to project root
   php -S localhost:8080 -t .
   ```

4. **Access admin panel:**
   - http://localhost:8080/admin/login.php

### Production

Your production `.env` file should already be configured with:
```env
APP_URL=https://www.example.com
APP_ENV=production
```

## Environment Variables

### Required Variables

| Variable | Description | Example (Local) | Example (Production) |
|----------|-------------|-----------------|---------------------|
| `APP_URL` | Base URL for the application | `http://localhost:8080` | `https://www.example.com` |
| `SECRET_KEY` | JWT signing secret (64+ chars) | `dev-secret-key...` | `[generated random key]` |
| `SMTP_HOST` | SMTP server hostname | `localhost` | `mail.example.com` |
| `SMTP_PORT` | SMTP server port | `1025` | `465` |
| `SMTP_USER` | SMTP username | `test@localhost` | `noreply@example.com` |
| `SMTP_PASS` | SMTP password | `test` | `[secure password]` |
| `SMTP_FROM` | From email address | `noreply@localhost` | `noreply@example.com` |
| `ADMIN_EMAIL` | Default admin email | `admin@localhost` | `admin@example.com` |

### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment mode | `production` |
| `MAGIC_LINK_EXPIRY` | Magic link expiry (seconds) | `600` (10 min) |
| `SESSION_TOKEN_EXPIRY` | Session expiry (seconds) | `3600` (1 hour) |
| `SMTP_FROM_NAME` | Display name for emails | `Last War 1586` |

## How APP_URL is Used

The `APP_URL` environment variable is used throughout the admin system for:

1. **Magic Links**: Generating login URLs sent via email
   - Example: `{APP_URL}/admin/callback.php?token=...`

2. **Redirects**: Post-login and post-logout redirects

3. **Email Templates**: Links back to the admin panel

4. **Security**: HTTPS enforcement in production

## Switching Between Local and Production

### Method 1: Multiple .env Files (Recommended)

Keep separate .env files and copy the one you need:

```bash
# Local development
cp admin/.env.local admin/.env

# Production deployment
cp admin/.env.production admin/.env
```

### Method 2: Git-ignored .env

Add to `.gitignore`:
```
admin/.env
```

Then each environment has its own `.env` file that's never committed.

### Method 3: Environment Variables

Set environment variables directly (useful for Docker/CI/CD):
```bash
export APP_URL=http://localhost:8080
export APP_ENV=development
php -S localhost:8080 -t .
```

## Testing Email Functionality Locally

### Recommended: Use Production SMTP (Default)

The `.env.local.example` is configured to use your production SMTP server so emails actually send from localhost:

1. Copy SMTP credentials from your production `.env`
2. Keep `APP_URL=http://localhost:8080`
3. Magic link emails will send successfully with localhost URLs

**How it works:**
- SMTP connection → production email server ✅
- Email delivered to user's inbox ✅
- Magic link URL → http://localhost:8080 ✅
- User clicks link → logs into your local dev environment ✅

This is the easiest setup and lets you test the full email flow!

### Alternative Options

**Option 1: Use Manual Magic Link Generator**

Skip emails entirely and generate links manually:
- Go to: http://localhost:8080/admin/generate_magic_link.php
- Enter user email → Copy link → Paste in browser
- Perfect for quick testing without email

**Option 2: Use a Test SMTP Service**

If you want to avoid using production SMTP:

**MailHog** (local email viewer):
```bash
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog
# Update .env: SMTP_HOST=localhost, SMTP_PORT=1025
# View emails at: http://localhost:8025
```

**Mailtrap** (cloud-based test inbox):
```env
SMTP_HOST=sandbox.smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USER=[your-mailtrap-username]
SMTP_PASS=[your-mailtrap-password]
```

## Security Notes

- **Never commit `.env` files** to version control (already in `.gitignore`)
- **Use strong SECRET_KEY** in production (generate with `openssl rand -base64 64`)
- **Use real SMTP credentials** in production
- **Enable HTTPS** in production (automatic when `APP_ENV=production`)

## Troubleshooting

### Magic links point to wrong URL

**Problem**: Clicking magic link goes to production instead of localhost

**Solution**: Check your `.env` file:
```bash
cat admin/.env | grep APP_URL
```

Should show: `APP_URL=http://localhost:8080`

### SMTP connection errors

**Problem**: Cannot send magic link emails

**Solutions**:
1. **Local dev**: Use MailHog or Mailtrap (see above)
2. **Production**: Verify SMTP credentials with your host
3. **Workaround**: Use "Generate Magic Link" button in admin panel

### Session/login issues

**Problem**: Sessions don't persist or logout immediately

**Solution**: Check `APP_ENV` is set correctly:
- Local: `APP_ENV=development` (disables HTTPS-only cookies)
- Production: `APP_ENV=production` (enforces HTTPS)

## Files Reference

- `.env` - Your active configuration (gitignored)
- `.env.example` - Production configuration template (committed)
- `.env.local.example` - Local development template (committed)
- `config.php` - Loads and validates .env variables
- `ENV-CONFIG.md` - This documentation file

## Getting Help

If you encounter issues:
1. Check `.env` file exists in `admin/` directory
2. Verify all required variables are set
3. Check PHP error logs for configuration errors
4. Ensure `composer install` has been run
