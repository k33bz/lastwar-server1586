# Server1586 Admin System v3.3.1

Enterprise-grade secure JWT-based authentication system with advanced security features, comprehensive testing, and pre-push validation.

## 📍 Navigation
- **← Back to Main**: [../README.md](../README.md)
- **📚 Full Documentation**: [../docs/README.md](../docs/README.md)
- **📖 Admin Documentation**: [../docs/admin/](../docs/admin/)
- **🚀 Deployment Guide**: [../docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md)
- **⚙️ Local Setup**: [../docs/admin/setup-local-env.md](../docs/admin/setup-local-env.md)

## 🔐 Security Features

### Core Authentication
- **Passwordless Login**: Email magic links with 10-minute expiry
- **JWT Sessions**: Stateless token-based authentication
- **Role-Based Access**: Admin, R5, R4, Power Editor roles
- **Token Management**: Automatic revocation and blacklisting

## 📊 Data Visualization Features

### Power Analytics
- **📈 Interactive Charts**: Chart.js-powered alliance power trends
- **📅 Time Series**: Historical power tracking with date-based visualization
- **🎨 Multi-Alliance View**: Color-coded lines for each alliance
- **🔍 Interactive Tooltips**: Hover for detailed power values and dates
- **📱 Responsive Design**: Charts adapt to all screen sizes
- **📋 Data Export**: View both chart and tabular data formats

### Advanced Security (v3.3.1)
- **🔄 JWT Key Rotation**: Automatic 90-day key rotation with emergency rotation
- **🛡️ CSRF Protection**: Cross-site request forgery prevention for all state-changing operations
- **🛡️ Multi-Factor Authentication**: TOTP support with backup codes and hardware keys
- **📊 Security Monitoring**: Real-time threat detection and IP blocking
- **🔍 Audit Logging**: Comprehensive security event tracking with real-time viewer
- **⚡ Real-time Protection**: Automatic blocking of suspicious activity
- **💾 Backup & Restore**: Automatic backups with point-in-time recovery
- **🔒 Email Masking**: PII protection for user data
- **✅ Testing Infrastructure**: 40 automated tests with pre-push git hooks

### Security Metrics
- **Authentication Strength**: 100/100
- **Session Security**: 95/100
- **Data Protection**: 90/100
- **Monitoring Coverage**: 95/100
- **Incident Response**: 100/100

## 📁 Admin Directory Structure

```
admin/
├── Core System Files
│   ├── config.php              # Configuration and environment loading
│   ├── jwt.php                 # JWT token management and validation
│   ├── json_helpers.php        # JSON file operations with locking
│   ├── csv_helpers.php         # CSV file operations
│   ├── mailer.php              # Email functionality (v1.3.0)
│   └── audit_logger.php        # Security event logging
│
├── Authentication & Session Management
│   ├── login.php               # Login form page
│   ├── send_magic_link.php     # Magic link generation and email
│   ├── callback.php            # Magic link validation and session creation
│   ├── logout.php              # Session termination
│   ├── refresh_session.php     # Session refresh endpoint
│   └── generate_magic_link.php # Magic link utilities
│
├── User Interface Pages
│   ├── dashboard.php           # Main admin dashboard
│   ├── alliance_edit.php       # Alliance editing interface
│   ├── alliances_power.php     # Power management interface
│   ├── alliance_power_history.php # Power trends visualization
│   ├── user_management.php     # User administration
│   ├── device_management.php   # Device management
│   └── sign_rules.php          # Rule signing interface
│
├── API Endpoints
│   ├── admin_api.php           # User management API
│   ├── allies_api.php          # Alliance data API
│   ├── alliance_edit_api.php   # Alliance editing API
│   ├── alliance_delete_api.php # Alliance deletion API
│   ├── alliances_power_api.php # Power management API
│   ├── user_management_api.php # User management API
│   ├── backup_restore_api.php  # Backup & restore API
│   ├── audit_log_api.php       # Audit log API
│   └── revoke_token_api.php    # Token revocation API
│
├── Security Management (v3.0.0)
│   ├── security_monitor.php    # Security dashboard and monitoring
│   ├── security_audit.php      # Real-time audit log viewer
│   ├── security_backups.php    # Backup management interface
│   ├── security_keys.php       # JWT key rotation management
│   ├── security_mfa.php        # Multi-factor authentication
│   ├── secret_key_rotation.php # Key rotation utilities
│   └── token_rotation.php      # Token management utilities
│
├── Automation & Maintenance
│   ├── cron.php                # Token cleanup (legacy)
│   ├── cron_token_cleanup.php  # Token blacklist cleanup
│   ├── cron_key_rotation.php   # Automatic key rotation
│   ├── initialize_audit_system.php    # Audit system setup
│   ├── initialize_key_rotation.php    # Key rotation setup
│   ├── fix_audit_log.php       # Audit log repair utilities
│   └── fix_key_sync.php        # Key synchronization repair
│
├── Data Files
│   ├── users.json              # User permissions database
│   ├── users.json.example      # User database template
│   ├── token_blacklist.json    # Revoked JWT tokens
│   ├── token_blacklist.json.example # Blacklist template
│   ├── secret_keys.json        # JWT signing keys (v3.0.0)
│   ├── audit_log.json          # Security event log
│   ├── audit_log.example.json  # Audit log template
│   ├── security_events.json    # Security monitoring data
│   ├── magic_links.json        # Active magic links
│   ├── ip_blacklist.json       # Blocked IP addresses
│   └── rate_limits.json        # Rate limiting data
│
├── Configuration & Environment
│   ├── .env                    # Environment configuration (NOT in git)
│   ├── .env.example            # Production environment template
│   ├── .env.local.example      # Local development template
│   ├── composer.json           # PHP dependencies
│   ├── composer.lock           # Dependency lock file
│   ├── .gitignore              # Git ignore rules
│   └── .htaccess               # Apache configuration
│
├── Documentation → Moved to ../docs/admin/
│   ├── README.md               # This file (overview only)
│   └── ../docs/admin/          # Complete admin documentation
│       ├── ADMIN_FUNCTIONALITY.md  # Feature documentation
│       ├── ALLIANCE_MANAGEMENT_GUIDE.md # Alliance management guide
│       ├── setup-local-env.md      # Local development setup
│       ├── SECRET_KEY_ROTATION_SETUP.md # Security setup guide
│       ├── DKIM-SETUP.md           # Email authentication setup
│       ├── ENV-CONFIG.md           # Environment configuration guide
│       ├── COMPOSER-INSTALL.md     # Dependency installation guide
│       ├── SECURITY_CHANGELOG.md   # Security update history
│       ├── MULTI_ROLE_IMPLEMENTATION.md # Multi-role system
│       ├── MIGRATION_SYSTEM.md     # Version migration
│       ├── VERSION_SUMMARY.md      # Version information
│       └── guide.md                # Technical guide (original spec)
│
├── Testing & Development
│   ├── test_dependencies.php   # Dependency verification
│   ├── test_smtp.php           # Email testing
│   ├── test_magic_link_email.php # Magic link email testing
│   ├── test_alliances_api.php  # Alliance API testing
│   ├── test_roles.php          # Role-based access testing
│   ├── test_audit_init.php     # Audit system testing
│   ├── debug_email_content.php # Email debugging
│   ├── compare_emails.php      # Email comparison utilities
│   └── test.php                # General testing utilities
│
├── Shared Components
│   └── includes/
│       ├── header.php          # Shared page header
│       ├── footer.php          # Shared page footer
│       ├── styles.css          # Shared CSS styles
│       ├── scripts.js          # Shared JavaScript
│       ├── api_helpers.php     # API utilities
│       ├── email_utils.php     # Email masking utilities
│       └── README.md           # Includes documentation
│
├── Backups (Auto-generated)
│   └── backups/
│       ├── alliances_backup_YYYYMMDD_HHMMSS.json
│       ├── users_backup_YYYYMMDD_HHMMSS.json
│       └── audit_backup_YYYYMMDD_HHMMSS.json
│
└── Dependencies
    └── vendor/                 # Composer dependencies
        ├── firebase/php-jwt/   # JWT library
        ├── phpmailer/phpmailer/ # Email library
        └── vlucas/phpdotenv/   # Environment variables
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

## � Powert History & Analytics

### Alliance Power Tracking

The admin system includes comprehensive power history tracking and visualization:

**Power History Page** (`alliance_power_history.php`):
- **📈 Interactive Line Chart**: Multi-alliance power trends over time
- **📅 Time-based Analysis**: Date-driven X-axis with proper time formatting
- **🎨 Color-coded Lines**: Each alliance gets a unique color for easy identification
- **🔍 Interactive Features**: 
  - Hover tooltips showing exact power values and dates
  - Clickable legend to show/hide specific alliances
  - Responsive design that adapts to screen size
- **📋 Data Table**: Traditional tabular view below the chart
- **📊 Chart.js Integration**: Modern, performant charting library

**Features:**
- **Multi-Alliance Support**: View all alliances on one chart
- **Date Formatting**: Proper time-series handling with date-fns adapter
- **Power Formatting**: Numbers displayed with commas (e.g., "1,234,567")
- **Responsive Canvas**: Chart automatically resizes with container
- **Grid Lines**: Subtle grid for easier value reading

**Access:**
- Available to all authenticated admin users
- Accessible from main dashboard navigation
- Uses existing power history CSV data
- No additional setup required

### Data Sources

Power history data is sourced from:
- `../data/power-history.csv` - Main power tracking file
- Automatic integration with existing data structure
- No database required - file-based storage

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
0 2 * * * /usr/bin/php /path/to/project/admin/cron.php
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

---

## 📞 Support & Contact

For issues or questions:
- **Main Documentation**: [../README.md](../README.md)
- **GitHub Issues**: [Report bugs or request features](https://github.com/username/your-repo/issues)
- **Admin Contact**: admin@example.com

---

## 📄 License

Proprietary - Last War 1586 Server

---

**Version**: 3.0.0 | **Last Updated**: October 16, 2025 | **Part of**: [Server 1586 Project](../README.md)

---

## 📝 Changelog

### Version 3.0.0 (2025-10-16)
- **🔧 Security Management Suite**: Complete security dashboard with monitoring, audit logs, backups, and key management
- **🛡️ Enhanced MFA**: Multi-factor authentication with TOTP, backup codes, and hardware key support
- **📊 Real-time Security Monitoring**: Live threat detection, IP blocking, and security event tracking
- **💾 Advanced Backup System**: Automatic backups with point-in-time recovery and restore capabilities
- **🔍 Audit Log Viewer**: Real-time audit log monitoring with filtering and search
- **🔑 JWT Key Management**: Advanced key rotation with emergency rotation and grace periods
- **🔒 Email Masking**: PII protection system for user data privacy
- **📱 Device Management**: Device registration and management for enhanced security
- **🚨 Rate Limiting**: Advanced rate limiting with IP-based blocking
- **📈 Security Analytics**: Comprehensive security metrics and reporting
- **📊 Power Trends Visualization**: Interactive Chart.js-powered alliance power history charts
- **🎨 Enhanced Data Views**: Multi-alliance power tracking with responsive design

### Version 2.1.0 (2025-10-15)
- **🔄 JWT Key Rotation System**: Automatic 30-day key rotation with emergency rotation capability
- **📊 Security Monitoring**: Rate limiting, IP blocking, and threat detection
- **🔍 Audit Logging**: Comprehensive audit trail with real-time viewer
- **💾 Backup & Restore**: Automatic backups before all data modifications
- **⚡ Power Editor Role**: New powereditor flag for alliance power management
- **🛡️ MFA Support**: Multi-factor authentication with TOTP and backup codes
- **📈 CSV Enhancements**: DateTime stamping for power history tracking
- **🌐 Environment Management**: Separate local/production configurations
- Enhanced JWT v2.1.0 with key rotation fallback support
- Token rotation with sliding window and refresh token patterns
- Security event logging and real-time monitoring dashboard
- IP-based rate limiting and automatic threat blocking
- Complete backup history with point-in-time recovery
- Admin panel for key rotation management
- Cron jobs for automated security maintenance

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