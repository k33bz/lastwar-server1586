# Quick Local Environment Setup

Follow these steps to set up your local development environment:

## Step 1: Copy .env File

```bash
cd admin/
cp .env.local.example .env
```

## Step 2: Copy Production Credentials

Open your production `.env` file and copy these values to your new local `.env`:

### Required Values to Copy:

```env
# From production .env:
SECRET_KEY=<copy exact value from production>
SMTP_HOST=<copy from production>
SMTP_PORT=<copy from production>
SMTP_USER=<copy from production>
SMTP_PASS=<copy from production>
SMTP_FROM=<copy from production>
```

### Keep These as Localhost:

```env
# Keep these for local development:
APP_URL=http://localhost:8080
APP_ENV=development
SMTP_FROM_NAME="Last War 1586 (Local Dev)"
```

## Step 3: Verify Your .env File

Your local `admin/.env` should look like:

```env
SECRET_KEY=<long production key>
SMTP_HOST=mail.example.com
SMTP_PORT=465
SMTP_USER=noreply@example.com
SMTP_PASS=<production password>
SMTP_FROM=noreply@example.com
SMTP_FROM_NAME="Last War 1586 (Local Dev)"

APP_URL=http://localhost:8080
ADMIN_EMAIL=admin@example.com

MAGIC_LINK_EXPIRY=600
SESSION_TOKEN_EXPIRY=3600

APP_ENV=development
```

## Step 4: Start Local Server

```bash
# From project root
cd C:\Users\k33bz\OneDrive\git\Server1586-clean
php -S localhost:8080 -t .
```

## Step 5: Access Admin Panel

Open in browser: http://localhost:8080/admin/login.php

## What Works Now

✅ **Emails send** - Using production SMTP server
✅ **Magic links work** - Point to http://localhost:8080
✅ **Login/logout** - Full authentication flow
✅ **All admin features** - Power editor, logs, backups
✅ **Real data** - Uses your local data files

## Troubleshooting

### "Cannot send email"

**Check:** SMTP credentials are correct in `.env`

**Test:** Try the manual magic link generator:
- http://localhost:8080/admin/generate_magic_link.php

### "Magic link points to production"

**Check:** Your `.env` has:
```env
APP_URL=http://localhost:8080
```

Not:
```env
APP_URL=https://www.example.com  ❌
```

### "Session expires immediately"

**Check:** Your `.env` has:
```env
APP_ENV=development
```

This disables HTTPS-only cookies for localhost.

## Switching Back to Production

When deploying to production, just use your production `.env` file with:

```env
APP_URL=https://www.example.com
APP_ENV=production
```

The code automatically adapts based on these variables!
