# Server1586 Admin System

Secure JWT-based email magic link login system for alliance and admin management on `www.example.com`.

## 🔐 Features

- Passwordless login via email magic links
- JWT-based stateless sessions
- Role-based access: Admin, R5, R4
- JSON-backed user and alliance data
- Token revocation via blacklist
- Admin CRUD for users
- Cron cleanup for expired tokens

## 📁 Folder Structure

```
Server1586/
├── admin/
│   ├── config.php              # Configuration and environment loading
│   ├── jwt.php                 # JWT encoding/decoding and session management
│   ├── json_helpers.php        # JSON file operations with locking
│   ├── mailer.php              # Email sending via PHPMailer
│   ├── login.php               # Login form page
│   ├── send_magic_link.php     # Magic link generation and email sending
│   ├── callback.php            # Magic link validation and session creation
│   ├── dashboard.php           # Main dashboard for logged-in users
│   ├── allies_api.php          # Alliance data editing
│   ├── admin_api.php           # User management (admin only)
│   ├── logout.php              # Session termination
│   ├── cron.php                # Token blacklist cleanup script
│   ├── users.json              # User permissions database
│   ├── token_blacklist.json    # Revoked JWT tokens
│   ├── .env                    # Environment configuration (NOT in git)
│   ├── .env.example            # Environment template
│   ├── composer.json           # PHP dependencies
│   ├── .gitignore              # Git ignore rules
│   ├── guide.md                # Technical guide (original spec)
│   ├── README.md               # This file
│   └── vendor/                 # Composer dependencies
└── data/
    └── alliances.json          # Alliance data (parent directory)
```

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- Composer (PHP package manager)
- Web server (Apache/Nginx with PHP-FPM)
- SMTP email server access
- HTTPS certificate (required for production)

### Step 1: Install Dependencies

```bash
cd admin
composer install
```

This installs:
- `firebase/php-jwt` - JWT token handling
- `phpmailer/phpmailer` - Email sending
- `vlucas/phpdotenv` - Environment variable management

### Step 2: Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and set your configuration:

```env
# Generate a strong secret key (64+ characters)
SECRET_KEY=your-long-random-secret-key-here

# SMTP Configuration
SMTP_HOST=smtp.your-domain.com
SMTP_PORT=587
SMTP_USER=mailer@example.com
SMTP_PASS=your-smtp-password
SMTP_FROM=noreply@example.com
SMTP_FROM_NAME=Last War 1586

# Application Configuration
APP_URL=https://www.example.com
ADMIN_EMAIL=admin@example.com

# Token Expiry (in seconds)
MAGIC_LINK_EXPIRY=600        # 10 minutes
SESSION_TOKEN_EXPIRY=3600    # 1 hour

# Environment
APP_ENV=production
```

**Generate a secure SECRET_KEY:**
```bash
openssl rand -base64 64
```

### Step 3: Set File Permissions

```bash
chmod 600 admin/users.json admin/token_blacklist.json
chown www-data:www-data admin/users.json admin/token_blacklist.json
```

### Step 4: Initialize Data Files

The system automatically creates initial data files on first run. To manually initialize:

```php
<?php
require 'config.php';
initialize_data_files();
?>
```

## 🔑 Authentication Flow

### 1. Login Request
User visits `login.php` and enters their email address.

### 2. Magic Link Generation
`send_magic_link.php`:
- Validates email against `users.json`
- Generates short-lived JWT token (10 minutes)
- Emails magic link to user
- Returns success message (even if email not found - security)

### 3. Magic Link Click
User clicks link, loading `callback.php?token=...`:
- Validates JWT signature and expiry
- Checks token hasn't been used (blacklist check)
- Blacklists magic link token (single-use)
- Creates new session JWT token (1 hour)
- Sets secure HTTP-only cookie
- Redirects to dashboard

### 4. Session Management
All protected pages call `require_jwt_session()`:
- Reads JWT from cookie
- Validates signature, expiry, blacklist
- Returns user data (email, role, alliances)
- Redirects to login if invalid

### 5. Logout
`logout.php`:
- Blacklists current session token
- Clears cookie
- Redirects to login

## 👥 User Management

### User Data Structure

Users are stored in `users.json`:

```json
{
  "users": [
    {
      "email": "r5@example.com",
      "alliances": ["UvvU"],
      "role": "r5"
    },
    {
      "email": "r4@example.com",
      "alliances": ["UvvU"],
      "role": "r4"
    },
    {
      "email": "admin@example.com",
      "alliances": ["*"],
      "role": "admin"
    }
  ]
}
```

**Fields:**
- `email`: User's email address (authentication identifier)
- `alliances`: Array of alliance tags user can manage
  - Use `["*"]` for admin access to all alliances
- `role`: One of `"admin"`, `"r5"`, or `"r4"`
  - `"admin"` - Full system access, can manage all users and alliances
  - `"r5"` - Can sign rules, manage assigned alliances, and create/edit R5 and R4 users for their assigned alliances only
  - `"r4"` - Can view and edit assigned alliances (cannot sign rules or manage users)

### Adding Users

**Via Dashboard (Admin or R5):**
1. Log in as admin or R5
2. Click "Add New User" on dashboard
3. Enter email, alliances, and role
   - **R5 users** can only:
     - Create R5 or R4 users
     - Assign alliances they have access to
     - See only their assigned alliances in the form
   - **Admin users** can create any role and grant access to all alliances

**Via Direct JSON Edit:**
1. Edit `admin/users.json`
2. Add user object to `users` array
3. Save file

### Editing/Removing Users

**Via Dashboard (Admin or R5):**
1. Log in as admin or R5
2. Click "Edit" next to user
3. Update details or click "Delete User"
   - **R5 users** can only:
     - Edit/delete R5 and R4 users from their assigned alliances
     - Assign alliances they have access to
     - View only users who share at least one alliance with them
     - Cannot edit admin users or promote users to admin
   - **Admin users** have full access to edit/delete any user

## 🛡️ Security Features

### JWT Token Security
- **HS256 Signing**: All tokens signed with SECRET_KEY
- **Short Expiry**: Magic links (10 min), sessions (1 hour)
- **Unique JTI**: Each token has unique ID for blacklisting
- **Audience Claim**: Tokens specify role (admin/r5/r4)
- **Magic Flag**: Magic link tokens can't be used as session tokens

### Magic Link Security
- **Single-Use**: Tokens blacklisted after first use
- **Time-Limited**: 10-minute expiry window
- **Secure Transmission**: Only sent via email
- **HTTPS Required**: Production enforces HTTPS
- **No User Enumeration**: Success message for all emails

### Session Security
- **HTTP-Only Cookies**: JavaScript cannot access JWT
- **Secure Flag**: Cookies only sent over HTTPS
- **SameSite Strict**: CSRF protection
- **Path Restriction**: Cookies limited to `/admin/`
- **Token Revocation**: Logout blacklists tokens

### File Security
- **Exclusive Locking**: `flock()` prevents race conditions
- **Restricted Permissions**: 600 (owner read/write only)
- **Input Validation**: All user input sanitized
- **Output Escaping**: All output HTML-escaped

### Security Headers
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`

## 🛠️ Maintenance

### Token Blacklist Cleanup

Run cleanup periodically to remove expired tokens:

**Manual:**
```bash
php admin/cron.php
```

**Automated (Cron):**
```cron
# Run daily at 2 AM
0 2 * * * /usr/bin/php /path/to/admin/cron.php
```

### Backup Strategy

Regularly backup:
- `admin/users.json` - User permissions
- `admin/.env` - Configuration (NEVER commit to git)
- `data/alliances.json` - Alliance data

```bash
tar -czf backup-$(date +%Y%m%d).tar.gz admin/users.json admin/.env data/alliances.json
```

## 🐛 Troubleshooting

### Magic Link Not Working
- Check SMTP configuration in `.env`
- Verify email exists in `users.json`
- Check spam folder
- Check PHP error logs

### Email Not Sending
- Verify SMTP credentials in `.env`
- Check firewall allows outbound port 587
- Verify SPF/DKIM DNS records

### File Permission Errors
```bash
chmod 600 admin/users.json admin/token_blacklist.json
chown www-data:www-data admin/*.json
```

### Composer Dependencies Missing
```bash
cd admin
composer install
```

## 📝 Development

### Running Locally

```bash
cd admin
php -S localhost:8000
```

Set `APP_ENV=development` in `.env` to:
- Disable HTTPS enforcement
- Enable detailed error messages
- Use non-secure cookies (local testing)

**Never use development mode in production!**

### Testing Email

```php
<?php
require 'admin/mailer.php';
send_test_email('your-email@example.com');
echo "Test email sent!";
?>
```

## 📚 API Reference

### Public Endpoints
- `GET /admin/login.php` - Login form
- `POST /admin/send_magic_link.php` - Request magic link
- `GET /admin/callback.php?token=...` - Magic link validation

### Protected Endpoints (Require JWT)
- `GET /admin/dashboard.php` - Main dashboard
- `GET /admin/allies_api.php?action=edit&tag=...` - Edit alliance
- `POST /admin/logout.php` - Logout

### Admin-Only Endpoints
- `GET /admin/admin_api.php?action=add` - Add user
- `GET /admin/admin_api.php?action=edit&email=...` - Edit/delete user

## 🔒 Security Best Practices

1. **Always use HTTPS in production**
2. **Never commit `.env` or secrets to version control**
3. **Use strong, random SECRET_KEY (64+ characters)**
4. **Keep PHP and dependencies updated**
5. **Set restrictive file permissions (600 for data)**
6. **Run token cleanup regularly (daily cron)**
7. **Monitor authentication logs for suspicious activity**
8. **Use strong SMTP passwords with app-specific passwords**
9. **Regular backups of user and alliance data**

## 📄 License

Proprietary - Last War 1586 Server

## 📞 Support

For issues or questions, contact: admin@example.com

---

## 📝 Changelog

### Version 1.2.0 (2025-10-13)
- Added JWT token revocation functionality
- Added active session tracking in users.json
- Added "Revoke Sessions" button for admins on dashboard
- R5 users can now create and manage R5 and R4 users
- R5 users restricted to managing users from their assigned alliances only
- R5 users can only assign alliances they have access to
- User list filtered by alliance access for R5 users
- R5 users cannot manage admin accounts
- Added session status indicators on user management table

### Version 1.0.0 (2025-10-12)
- Initial complete implementation
- JWT-based passwordless authentication
- Magic link email login
- Role-based access control
- Alliance data management
- User administration panel
- Token blacklisting and revocation
- Comprehensive security features
- File locking for concurrent access
- Automatic data file initialization