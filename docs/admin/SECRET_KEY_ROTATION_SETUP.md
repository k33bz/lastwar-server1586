# JWT Secret Key Rotation Setup Guide

## Overview

This system provides comprehensive JWT secret key rotation with the following features:

- **Automatic scheduled rotation** (configurable interval)
- **Emergency rotation** (immediate invalidation)
- **Grace period support** (5-minute overlap for seamless transition)
- **All token invalidation** (sessions, magic links, refresh tokens)
- **Admin notifications** (email alerts for rotations)
- **Audit logging** (complete rotation history)
- **Web interface** (admin panel for manual control)

## Quick Setup

### 1. Update Environment Configuration

Add to your `.env` file:

```bash
# Secret Key Rotation Configuration
AUTO_KEY_ROTATION_ENABLED=true
KEY_ROTATION_INTERVAL_DAYS=30    # Rotate every 30 days
KEY_ROTATION_GRACE_PERIOD=300    # 5 minutes grace period
```

### 2. Set Up Cron Job

Add to your server's crontab:

```bash
# Check for key rotation daily at 2 AM
0 2 * * * /usr/bin/php /path/to/admin/cron_key_rotation.php

# Clean up expired tokens hourly
0 * * * * /usr/bin/php /path/to/admin/cron_token_cleanup.php
```

### 3. Update Existing Code (Optional)

For enhanced security, replace JWT functions in your existing files:

```php
// OLD
require_once 'jwt.php';
$user = require_jwt_session();

// NEW (with key rotation support)
require_once 'enhanced_jwt_with_key_rotation.php';
$user = require_enhanced_jwt_session_with_key_rotation();
```

## File Structure

```
admin/
├── secret_key_rotation.php              # Core rotation functions
├── enhanced_jwt_with_key_rotation.php   # Enhanced JWT functions
├── key_rotation_admin_panel.php         # Web interface
├── cron_key_rotation.php               # Automated rotation
├── cron_token_cleanup.php              # Token cleanup
├── enhanced_callback_with_key_rotation.php  # Example callback
└── secret_keys.json                    # Key storage (auto-created)
```

## How It Works

### Automatic Rotation

1. **Cron job runs daily** and checks key age
2. **If key is older than configured interval**, rotation triggers
3. **New key generated** and stored in `secret_keys.json`
4. **Environment file updated** with new key
5. **All active sessions cleared** from `users.json`
6. **Token blacklist cleared** (all old tokens invalid)
7. **Admin notifications sent** via email
8. **Audit log entry created**

### Grace Period

- **5-minute overlap** where both old and new keys work
- **Prevents session interruption** during rotation
- **Automatic fallback** to old key if new key fails
- **Configurable duration** via environment variable

### Emergency Rotation

- **Immediate invalidation** of all tokens
- **No grace period** for security incidents
- **Admin alerts sent** to all admin users
- **Audit trail** with incident details

## Security Benefits

### 1. **Reduced Token Lifetime Exposure**
- Keys rotate automatically every 30 days (configurable)
- Compromised keys have limited validity window
- Historical keys stored for audit purposes

### 2. **Complete Session Invalidation**
- All active sessions cleared on rotation
- Magic links invalidated immediately
- Refresh tokens (if implemented) also invalidated

### 3. **Emergency Response**
- Instant key rotation for security incidents
- Immediate notification to all administrators
- Complete audit trail for compliance

### 4. **Zero Downtime**
- Grace period prevents service interruption
- Seamless transition between keys
- Automatic fallback mechanisms

## Admin Interface

Access the web interface at: `/admin/key_rotation_admin_panel.php`

### Features:
- **Current key status** and age
- **Manual rotation** with custom reason
- **Emergency rotation** for security incidents
- **Rotation history** with timestamps
- **Environment sync validation**
- **Grace period monitoring**

### Manual Rotation

1. Navigate to Key Rotation Admin Panel
2. Enter rotation reason
3. Click "Rotate Secret Key"
4. Confirm the action
5. All users will need to log in again

### Emergency Rotation

1. Navigate to Key Rotation Admin Panel
2. Scroll to Emergency section
3. Enter detailed incident description
4. Click "Emergency Rotate Now"
5. Immediate invalidation + admin alerts

## Configuration Options

### Environment Variables

```bash
# Enable/disable automatic rotation
AUTO_KEY_ROTATION_ENABLED=true

# Rotation interval (days)
KEY_ROTATION_INTERVAL_DAYS=30

# Grace period (seconds)
KEY_ROTATION_GRACE_PERIOD=300

# Token expiry times
SESSION_TOKEN_EXPIRY=3600
MAGIC_LINK_EXPIRY=600
REFRESH_TOKEN_EXPIRY=604800
```

### Rotation Thresholds

You can customize when rotation occurs:

- **Daily rotation**: `KEY_ROTATION_INTERVAL_DAYS=1`
- **Weekly rotation**: `KEY_ROTATION_INTERVAL_DAYS=7`
- **Monthly rotation**: `KEY_ROTATION_INTERVAL_DAYS=30`
- **Quarterly rotation**: `KEY_ROTATION_INTERVAL_DAYS=90`

## Monitoring & Alerts

### Email Notifications

Automatic emails sent to all admin users for:
- **Scheduled rotations** (daily cron job)
- **Emergency rotations** (security incidents)
- **Rotation failures** (error conditions)

### Audit Logging

All rotation events logged with:
- **Timestamp** and **user** who initiated
- **Rotation reason** and **key IDs**
- **Session counts** cleared
- **Success/failure** status

### Status Monitoring

Check rotation status via:
- **Admin web interface** (real-time status)
- **Audit logs** (historical data)
- **Log files** (error debugging)

## Troubleshooting

### Common Issues

#### 1. Environment Key Mismatch
**Symptom**: "Environment key out of sync" warning
**Solution**: 
```bash
# Check current key in admin panel
# Manually update .env file if needed
# Or trigger rotation to sync automatically
```

#### 2. Cron Job Not Running
**Symptom**: Keys not rotating automatically
**Solution**:
```bash
# Test cron job manually
/usr/bin/php /path/to/admin/cron_key_rotation.php

# Check cron logs
tail -f /var/log/cron

# Verify crontab entry
crontab -l
```

#### 3. All Users Logged Out Unexpectedly
**Symptom**: Mass logout events
**Possible Causes**:
- Automatic key rotation occurred
- Emergency rotation triggered
- Manual rotation performed

**Check**: Admin panel rotation history and audit logs

#### 4. Grace Period Issues
**Symptom**: Users getting "invalid token" during rotation
**Solution**:
- Increase `KEY_ROTATION_GRACE_PERIOD` to 600 (10 minutes)
- Check server time synchronization
- Verify rotation timing in logs

### Recovery Procedures

#### Emergency Key Reset
If rotation system fails completely:

1. **Generate new key manually**:
```bash
openssl rand -base64 64
```

2. **Update .env file** with new key

3. **Clear all sessions**:
```php
// Run this PHP script
require_once 'config.php';
require_once 'json_helpers.php';

// Clear all active sessions
update_json_file(USERS_FILE, function(&$data) {
    foreach ($data['users'] as &$user) {
        $user['active_sessions'] = [];
    }
    return true;
});

// Clear token blacklist
write_json_file(BLACKLIST_FILE, ['jti' => [], 'expires' => []]);
```

4. **Reset key storage**:
```bash
rm admin/secret_keys.json
# Will be recreated on next access
```

## Security Considerations

### Key Storage
- **secret_keys.json** contains sensitive data
- **File permissions** set to 600 (owner read/write only)
- **Backup encryption** recommended for production
- **Regular cleanup** of old keys from history

### Rotation Frequency
- **More frequent** = better security, more user disruption
- **Less frequent** = convenience, longer exposure window
- **Recommended**: 30 days for most applications

### Grace Period
- **Longer period** = less disruption, slightly less secure
- **Shorter period** = more secure, potential disruption
- **Recommended**: 5 minutes (300 seconds)

### Emergency Procedures
- **Document incident response** procedures
- **Train administrators** on emergency rotation
- **Test emergency rotation** in staging environment
- **Monitor for abuse** of emergency features

## Integration Examples

### Existing Dashboard
```php
// Replace this:
$user = require_jwt_session();

// With this:
require_once 'enhanced_jwt_with_key_rotation.php';
$user = require_enhanced_jwt_session_with_key_rotation();
```

### API Endpoints
```php
// Add rotation support to APIs:
require_once 'enhanced_jwt_with_key_rotation.php';

$user = require_enhanced_jwt_session_with_key_rotation();
// Rest of API logic unchanged
```

### Magic Link Callback
```php
// Enhanced callback with key rotation:
$magic_token = validate_magic_link_with_key_rotation($token_string);
// Handles both current and previous keys automatically
```

## Compliance & Auditing

### Audit Trail
- **Complete rotation history** stored
- **Timestamps and reasons** for all rotations
- **User identification** for manual rotations
- **Incident documentation** for emergency rotations

### Compliance Benefits
- **Regular key rotation** (security best practice)
- **Incident response** capability
- **Complete audit trail** for compliance
- **Automated processes** reduce human error

### Reporting
- **Monthly rotation reports** from audit logs
- **Security incident documentation** 
- **Key age monitoring** and alerts
- **Session invalidation tracking**

---

## Quick Reference

### Manual Rotation
```bash
# Via web interface
https://yoursite.com/admin/key_rotation_admin_panel.php

# Via command line
php admin/cron_key_rotation.php
```

### Emergency Rotation
```php
require_once 'secret_key_rotation.php';
emergency_key_rotation('admin@example.com', 'Security breach detected');
```

### Status Check
```php
require_once 'secret_key_rotation.php';
$status = get_key_rotation_status();
print_r($status);
```

### Force User Logout
```php
require_once 'secret_key_rotation.php';
force_rotate_user_sessions('user@example.com');
```

This system provides enterprise-grade JWT key rotation with minimal operational overhead and maximum security benefits.