# Developer Setup Guide

Complete guide for setting up a local development environment for Server 1586.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Initial Setup](#initial-setup)
- [Environment Configuration](#environment-configuration)
- [Running Locally](#running-locally)
- [Development Workflow](#development-workflow)
- [Testing Setup](#testing-setup)
- [Troubleshooting](#troubleshooting)
- [Useful Commands](#useful-commands)
- [IDE Setup](#ide-setup)
- [Common Tasks](#common-tasks)

---

## Prerequisites

Before you begin, ensure you have the following installed:

### Required
- **PHP 8.1+** with extensions:
  - `json`
  - `mbstring`
  - `openssl`
  - `curl`
  - `fileinfo`
- **Composer** (PHP dependency manager)
- **Git** (version control)
- **Web Server** (choose one):
  - PHP built-in server (recommended for development)
  - Apache with mod_php
  - Nginx with PHP-FPM

### Optional
- **LM Studio** - For AI-assisted test generation and commit reviews
- **Node.js & npm** - For frontend development (if working on React client)
- **Python 3.8+** - For utility scripts

### Check Versions
```bash
php -v           # Should be 8.1 or higher
composer --version
git --version
```

---

## Initial Setup

### 1. Clone the Repository

```bash
git clone https://github.com/k33bz/Server1586-clean.git
cd Server1586-clean
```

### 2. Install PHP Dependencies

```bash
cd admin
composer install
cd ..
```

This installs:
- PHPMailer (email functionality)
- Other required packages

### 3. Generate JWT Secret

```bash
php -r "echo bin2hex(random_bytes(32));" > admin/jwt_secret.txt
```

**Important:** Keep this file secure and never commit it to version control!

### 4. Create Environment File

```bash
cd admin
cp .env.example .env
```

Edit `.env` with your configuration (see [Environment Configuration](#environment-configuration))

### 5. Set File Permissions

**Unix/Linux/macOS:**
```bash
chmod 600 admin/jwt_secret.txt
chmod 600 admin/.env
chmod 755 admin/includes
chmod 644 admin/includes/*.php
chmod 755 data
chmod 666 data/*.json
```

**Windows:**
```powershell
# Right-click files -> Properties -> Security
# Ensure only your user account has access
```

### 6. Initialize Data Files

Most data files are included in the repository. If any are missing:

```bash
# Check required files exist
ls -la data/*.json

# If notifications.json is missing:
echo '{"version":"1.0.0","last_updated":"","notifications":[]}' > data/notifications.json
```

---

## Environment Configuration

Edit `admin/.env` with your settings:

### JWT Configuration (Required)

```env
# Copy the value from jwt_secret.txt
JWT_SECRET=your_generated_secret_from_jwt_secret_txt

# Token expiry in seconds (default: 8 hours)
JWT_EXPIRY=28800
```

### Email Configuration (Optional)

For magic link authentication and notifications:

```env
# SMTP Server Settings
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password      # Use app password, not regular password
SMTP_FROM=noreply@server1586.com
SMTP_FROM_NAME=Server 1586 Admin

# Email Feature Toggles
MAGIC_LINK_ENABLED=true
MAGIC_LINK_EXPIRY=600             # 10 minutes
```

**Gmail Setup:**
1. Enable 2FA on your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the app password in `SMTP_PASS`

### Rate Limiting (Optional)

```env
# Login rate limiting
LOGIN_RATE_LIMIT=5                # Max attempts
LOGIN_RATE_WINDOW=900             # 15 minutes in seconds

# API rate limiting
API_RATE_LIMIT=100                # Requests per window
API_RATE_WINDOW=60                # 1 minute
```

### Discord Integration (Optional)

```env
DISCORD_ENABLED=true
DISCORD_BOT_TOKEN=your_bot_token
DISCORD_GUILD_ID=your_server_id
```

### Application Settings

```env
APP_NAME=Server 1586 Admin
APP_ENV=development               # development | production
DEBUG_MODE=true                   # Enable error logging in dev
```

---

## Running Locally

### Option 1: PHP Built-in Server (Recommended)

**From project root:**
```bash
php -S localhost:8000
```

**From admin directory:**
```bash
cd admin
php -S localhost:8000
```

Access at: `http://localhost:8000/admin/dashboard.php`

### Option 2: Apache

Create virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName server1586.local
    DocumentRoot "/path/to/Server1586-clean"

    <Directory "/path/to/Server1586-clean">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/server1586-error.log
    CustomLog ${APACHE_LOG_DIR}/server1586-access.log combined
</VirtualHost>
```

Add to `/etc/hosts`:
```
127.0.0.1  server1586.local
```

### Option 3: Nginx

```nginx
server {
    listen 80;
    server_name server1586.local;
    root /path/to/Server1586-clean;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Frontend Development (React Client)

If working on the React frontend:

```bash
cd client
npm install
npm run dev          # Development server at http://localhost:5173
npm run build        # Production build to client/dist/
npm run preview      # Preview production build at http://localhost:4173
```

---

## Development Workflow

### Branch Naming Convention

```bash
git checkout -b feature/notification-system
git checkout -b fix/jwt-expiry-bug
git checkout -b docs/api-documentation
```

Patterns:
- `feature/*` - New features
- `fix/*` - Bug fixes
- `docs/*` - Documentation
- `refactor/*` - Code refactoring
- `test/*` - Test additions

### Commit Message Format

Follow Conventional Commits format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Examples:**
```bash
git commit -m "feat(admin): Add notification system with header badge"
git commit -m "fix(auth): Correct JWT token expiry validation"
git commit -m "docs: Add developer setup guide"
```

**Types:**
- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation changes
- `refactor` - Code refactoring
- `test` - Adding tests
- `chore` - Maintenance tasks

### Pre-commit Hooks

The repository includes pre-commit hooks that:
- ✅ Check for protected files (secrets, keys)
- ✅ Scan for sensitive data (emails, tokens)
- ✅ Validate JSON files
- ✅ Check PHP syntax
- ✅ Detect debug statements
- ✅ Run LM Studio security scan (if available)
- ✅ Validate commit message format

**To skip hooks (emergency only):**
```bash
git commit --no-verify -m "emergency fix"
```

### Creating Pull Requests

1. Push your branch:
   ```bash
   git push origin feature/your-feature
   ```

2. Create PR on GitHub with:
   - Clear title and description
   - Link to related issues
   - List of changes
   - Testing performed

3. Ensure CI checks pass

---

## Testing Setup

### Install PHPUnit

```bash
cd admin
composer require --dev phpunit/phpunit
```

### Run Existing Tests

```bash
# Run all tests
vendor/bin/phpunit tests/

# Run specific test file
vendor/bin/phpunit tests/InputValidatorTest.php

# Run with coverage (requires xdebug)
vendor/bin/phpunit --coverage-html coverage/
```

### Generate New Tests with LM Studio

If you have LM Studio running on localhost:1234:

```bash
# Generate tests for a PHP file
python scripts/generate-tests.py admin/includes/input_validator.php

# Tests are created in admin/tests/
```

### Manual Test Creation

Create test file in `admin/tests/`:

```php
<?php
use PHPUnit\Framework\TestCase;

class YourFeatureTest extends TestCase {
    public function testSomething() {
        $this->assertTrue(true);
    }
}
```

---

## Troubleshooting

### File Permission Errors

**Error:** "Permission denied" when accessing data files

**Solution:**
```bash
# Unix/Linux/macOS
chmod 755 data
chmod 666 data/*.json

# Check ownership
ls -la data/
chown -R $USER:$USER data/
```

### JWT Token Issues

**Error:** "Invalid JWT token" or "Token expired"

**Solutions:**
1. Check `jwt_secret.txt` exists and matches `.env`
2. Verify JWT_EXPIRY in `.env`
3. Clear browser cookies
4. Check server time is correct

```bash
# Regenerate JWT secret
php -r "echo bin2hex(random_bytes(32));" > admin/jwt_secret.txt
# Update .env with new secret
```

### SMTP Configuration

**Error:** "Failed to send email"

**Solutions:**
1. Verify SMTP credentials
2. Check firewall allows port 587
3. Use app password (not regular password)
4. Test with telnet:
   ```bash
   telnet smtp.gmail.com 587
   ```

### CORS Issues

**Error:** "CORS policy blocked" in browser console

**Solution:** Check `admin/includes/header.php` has correct CORS headers

### Session Problems

**Error:** "Session expired" immediately after login

**Solutions:**
1. Check PHP session directory is writable:
   ```bash
   php -i | grep session.save_path
   ```
2. Verify cookies are being set
3. Check browser isn't blocking third-party cookies

### Database Connection Errors

**Note:** This project uses JSON file storage, not a database. If you see database errors, you may be running the wrong version of the code.

---

## Useful Commands

### PHP Information
```bash
# Check PHP version and modules
php -v
php -m

# Check specific extension
php -m | grep json

# Display PHP configuration
php -i | less

# Check syntax of PHP file
php -l admin/includes/jwt.php
```

### Composer Commands
```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Validate composer.json
composer validate

# Check for security vulnerabilities
composer audit

# Show installed packages
composer show

# Require new package
composer require vendor/package
```

### Git Commands
```bash
# View commit history
git log --oneline --graph --all

# See what changed
git diff
git diff --staged

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Clean untracked files (dry run first!)
git clean -n
git clean -fd
```

### Project-Specific Scripts
```bash
# Run security audit
python scripts/repo-review.py security

# Generate tests for file
python scripts/generate-tests.py <file>

# Update rotation schedule
python scripts/update-rotation-schedule.py

# Verify data integrity
python scripts/verify-data.py
```

### Data Management
```bash
# Backup data files
tar -czf backup-$(date +%Y%m%d).tar.gz data/

# View audit log
tail -f data/audit-log.json | jq .

# Count notifications
jq '.notifications | length' data/notifications.json

# Find user by email
jq '.users[] | select(.email=="admin@example.com")' admin/users.json
```

---

## IDE Setup

### Visual Studio Code

**Recommended Extensions:**
- PHP Intelephense
- PHP Debug
- PHPDoc Comment Generator
- EditorConfig for VS Code
- GitLens

**Settings (`.vscode/settings.json`):**
```json
{
  "php.validate.executablePath": "/usr/bin/php",
  "editor.formatOnSave": true,
  "files.associations": {
    "*.php": "php"
  }
}
```

### PHPStan (Static Analysis)

```bash
composer require --dev phpstan/phpstan

# Run analysis
vendor/bin/phpstan analyse admin/includes
```

**Configuration (phpstan.neon):**
```yaml
parameters:
  level: 6
  paths:
    - admin/includes
    - admin/api
```

### PHP-CS-Fixer (Code Style)

```bash
composer require --dev friendsofphp/php-cs-fixer

# Fix code style
vendor/bin/php-cs-fixer fix admin/includes
```

### Xdebug (Debugging)

Install Xdebug:
```bash
# Ubuntu/Debian
sudo apt install php8.1-xdebug

# macOS
pecl install xdebug
```

**VS Code launch.json:**
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003
    }
  ]
}
```

---

## Common Tasks

### Adding a New User

```bash
# Option 1: Via admin panel
# Navigate to: http://localhost:8000/admin/user_management.php

# Option 2: Manually edit users.json
nano admin/users.json
```

**User structure:**
```json
{
  "email": "newuser@example.com",
  "role": "r5",
  "created_at": "2025-11-12 12:00:00",
  "last_login": null
}
```

### Creating a Magic Link

```bash
# Via admin panel
# Navigate to: http://localhost:8000/admin/generate_magic_link.php

# Or use CLI
php admin/cli/generate-magic-link.php newuser@example.com
```

### Viewing Audit Logs

```bash
# Pretty print recent logs
tail -n 50 data/audit-log.json | jq .

# Filter by user
jq 'select(.user=="admin@example.com")' data/audit-log.json

# Filter by action
jq 'select(.action=="login_success")' data/audit-log.json
```

### Backup & Restore Data

**Backup:**
```bash
# Backup all data files
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz data/

# Backup specific file
cp data/users.json data/users.json.backup
```

**Restore:**
```bash
# Restore from backup
tar -xzf backup-20251112-120000.tar.gz

# Restore specific file
cp data/users.json.backup data/users.json
```

### Updating Alliance Power

```bash
# Via admin panel (recommended)
# Navigate to: http://localhost:8000/admin/alliances_power.php

# Or edit JSON directly
nano data/alliances.json
```

### Rotating JWT Keys

```bash
# Generate new key
php -r "echo bin2hex(random_bytes(32));" > admin/jwt_secret_new.txt

# Update .env to use new key
# All users will need to re-authenticate
```

### Clear All Sessions

```bash
# Sessions are JWT-based, so clearing requires:
# 1. Rotate JWT secret (see above)
# OR
# 2. Wait for token expiry (8 hours by default)
```

---

## Additional Resources

- **Main README:** [README.md](../README.md)
- **Architecture:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **Deployment:** [DEPLOYMENT.md](../DEPLOYMENT.md)
- **API Documentation:** [API.md](API.md) *(coming soon)*
- **Contributing:** [CONTRIBUTING.md](../CONTRIBUTING.md)
- **Workflow Guide:** [CLAUDE.md](../CLAUDE.md)

---

## Getting Help

- **GitHub Issues:** https://github.com/k33bz/Server1586-clean/issues
- **Discussions:** https://github.com/k33bz/Server1586-clean/discussions
- **Email:** k33bz@example.com *(if configured)*

---

## Quick Reference Card

```bash
# Start development server
php -S localhost:8000

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit tests/

# Check PHP syntax
php -l admin/includes/file.php

# Generate JWT secret
php -r "echo bin2hex(random_bytes(32));"

# Backup data
tar -czf backup.tar.gz data/

# View audit logs
tail -f data/audit-log.json | jq .

# Run security audit
python scripts/repo-review.py security
```

---

**Last Updated:** 2025-11-12
**Version:** 1.0.0
