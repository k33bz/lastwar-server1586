# CSRF Protection Implementation

**Version:** 1.0.0
**Date:** 2025-10-28
**GitHub Issue:** [#21 - Implement CSRF protection](https://github.com/k33bz/lastwar-server1586/issues/21)

## Overview

Cross-Site Request Forgery (CSRF) protection has been implemented across the admin panel to prevent unauthorized state-changing operations. The system uses cryptographically secure tokens stored in PHP sessions and validated on every POST, PUT, DELETE, and PATCH request.

## Architecture

### Core Components

1. **Token Generation** (`admin/includes/csrf.php`)
   - Generates 32-byte cryptographically secure random tokens
   - Stores tokens in PHP session
   - Provides helper functions for token management

2. **Token Distribution** (`admin/includes/header.php`)
   - Meta tag in `<head>` for JavaScript access
   - Form helpers for HTML forms
   - Automatically included on all admin pages

3. **Token Validation** (API endpoints)
   - Server-side validation using timing-attack-safe comparison
   - Required on all state-changing operations
   - Returns 403 Forbidden if validation fails

4. **JavaScript Integration** (`admin/includes/scripts.js`)
   - Automatically includes CSRF token in API requests
   - Extracts token from meta tag
   - Adds `X-CSRF-Token` header to POST/PUT/DELETE/PATCH requests

## How It Works

### Token Lifecycle

```
1. User loads admin page
   ↓
2. PHP session starts, CSRF token generated
   ↓
3. Token embedded in <meta> tag
   ↓
4. JavaScript extracts token from meta tag
   ↓
5. User submits form or makes API call
   ↓
6. JavaScript includes token in X-CSRF-Token header
   ↓
7. Server validates token matches session
   ↓
8. Request processed or rejected (403)
```

### Token Storage

**Session Storage:**
```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// Example: "a7f3d2e8b1c4f5a6d9e2b8c1a4f7d3e9b2c5a8d1f4e7b3c6a9d2e5f8b1c4a7d3"
```

**Meta Tag Distribution:**
```html
<meta name="csrf-token" content="a7f3d2e8b1c4f5a6d9e2b8c1a4f7d3e9b2c5a8d1f4e7b3c6a9d2e5f8b1c4a7d3">
```

### Validation Process

```php
// 1. Get token from request (header, POST, or JSON body)
$provided_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? null;

// 2. Get token from session
$session_token = $_SESSION['csrf_token'] ?? null;

// 3. Timing-attack-safe comparison
if (!hash_equals($session_token, $provided_token)) {
    http_response_code(403);
    exit('CSRF token validation failed');
}
```

## Usage Guide

### For Backend Developers (PHP)

#### API Endpoints

Add CSRF validation right after authentication and before processing any state-changing requests:

```php
<?php
require_once 'jwt.php';

$user = require_jwt_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection - ADD THIS LINE
    requireCsrfToken();

    // Process request...
}
```

**Examples:**
- ✅ `admin/alliance_edit_api.php:56`
- ✅ `admin/user_management_api.php:34`
- ✅ `admin/security_keys.php:30`
- ✅ `admin/revoke_token_api.php:43`

#### HTML Forms (if any)

Add hidden CSRF token field to forms:

```php
<form method="POST" action="process.php">
    <?php echo csrfField(); ?>
    <!-- Rest of form fields -->
</form>
```

This generates:
```html
<input type="hidden" name="_csrf_token" value="a7f3d2e8...">
```

#### Critical Operations

For extra-sensitive operations (e.g., account deletion, fund transfer), use operation-specific tokens:

```php
// Generate operation-specific token
$operation_token = generateOperationToken('delete_user');

// Validate
if (!validateOperationToken('delete_user')) {
    http_response_code(403);
    exit('Operation token validation failed');
}
```

### For Frontend Developers (JavaScript)

#### Automatic Protection

The shared `apiRequest()` function automatically includes CSRF tokens:

```javascript
// This already includes CSRF token automatically
await apiRequest('/admin/user_management_api.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'add', email: 'user@example.com' })
});
```

#### Manual CSRF Token Access

If needed, get the token manually:

```javascript
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
}

// Include in custom fetch() calls
fetch('/admin/custom_api.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken()
    },
    body: JSON.stringify({ data: 'value' })
});
```

#### Helper Functions

```javascript
// Use existing helper for API calls
await apiPost('/admin/endpoint.php', { action: 'update', id: 123 });

// Token is automatically included in:
// - apiRequest()
// - apiPost()
// - Any fetch() with method POST/PUT/DELETE/PATCH
```

## Security Features

### 1. Cryptographically Secure Token Generation

```php
bin2hex(random_bytes(32))  // 64-character hexadecimal string
```

- Uses PHP's `random_bytes()` (CSPRNG)
- 256 bits of entropy (2^256 possible values)
- Resistant to prediction and brute-force attacks

### 2. Timing-Attack Protection

```php
hash_equals($session_token, $provided_token)
```

- Constant-time comparison prevents timing attacks
- Attacker cannot deduce token by measuring response times

### 3. Multiple Token Sources

Tokens accepted from:
1. **HTTP Header:** `X-CSRF-Token` (preferred for AJAX)
2. **POST Data:** `_csrf_token` (for HTML forms)
3. **JSON Body:** `csrf_token` (for REST APIs)

### 4. Token Regeneration

Regenerate tokens after sensitive operations:

```php
// After password change, privilege escalation, etc.
regenerateCsrfToken();
```

### 5. Exempt Methods

Safe HTTP methods automatically exempt:
```php
GET, HEAD, OPTIONS, TRACE  // No CSRF check needed
```

## File Reference

### Core Files

| File | Purpose | Lines |
|------|---------|-------|
| `admin/includes/csrf.php` | Token generation and validation functions | 254 |
| `admin/config.php:156` | Load CSRF functions globally | 1 |
| `admin/includes/header.php:49` | Embed CSRF meta tag in HTML | 1 |
| `admin/includes/scripts.js:349-398` | JavaScript token handling | 50 |

### Protected Endpoints

| Endpoint | Method Check Line | CSRF Check Line |
|----------|------------------|-----------------|
| `alliance_edit_api.php` | 49, 168 | 56, 175 |
| `user_management_api.php` | 29 | 34 |
| `admin_api.php` | 67, 310 | 69, 312 |
| `allies_api.php` | 76 | 78 |
| `revoke_token_api.php` | 36 | 43 |
| `security_keys.php` | 28 | 30 |

## Testing CSRF Protection

### Test 1: Valid Request (Should Succeed)

```javascript
// With valid CSRF token
const token = document.querySelector('meta[name="csrf-token"]').content;
fetch('/admin/alliance_edit_api.php?action=update', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ tag: 'TEST', name: 'Test Alliance' })
});
// Expected: 200 OK, alliance updated
```

### Test 2: Missing Token (Should Fail)

```javascript
// Without CSRF token
fetch('/admin/alliance_edit_api.php?action=update', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tag: 'TEST', name: 'Test Alliance' })
});
// Expected: 403 Forbidden, "CSRF token validation failed"
```

### Test 3: Invalid Token (Should Fail)

```javascript
// With wrong CSRF token
fetch('/admin/alliance_edit_api.php?action=update', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': 'invalid_token_12345',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ tag: 'TEST', name: 'Test Alliance' })
});
// Expected: 403 Forbidden, "CSRF token validation failed"
```

### Test 4: GET Request (Should Succeed Without Token)

```javascript
// GET request - no CSRF needed
fetch('/admin/alliance_edit_api.php?action=list', {
    method: 'GET'
});
// Expected: 200 OK, data returned
```

## Troubleshooting

### Issue: "CSRF token validation failed"

**Possible Causes:**
1. Session not started or expired
2. Token not included in request
3. JavaScript not loading CSRF token correctly
4. Session timeout between page load and request

**Solutions:**
```php
// 1. Check session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Verify token exists
echo getCsrfToken();  // Should output 64-char hex string

// 3. Check JavaScript console
console.log(getCsrfToken());  // Should match PHP output

// 4. Increase session timeout
ini_set('session.gc_maxlifetime', 3600);  // 1 hour
```

### Issue: Token not in meta tag

**Check:**
```html
<!-- Verify meta tag exists in <head> -->
<meta name="csrf-token" content="...">
```

**Fix:**
```php
// In admin/includes/header.php (line 49)
<?php echo csrfMetaTag(); ?>
```

### Issue: JavaScript not sending token

**Debug:**
```javascript
// Check if getCsrfToken() is defined
console.log(typeof getCsrfToken);  // Should be "function"

// Check if token is extracted
console.log(getCsrfToken());  // Should output token string

// Check headers in Network tab
// POST request should have: X-CSRF-Token: <token>
```

## Best Practices

### ✅ Do

- Always call `requireCsrfToken()` after authentication check
- Use timing-safe `hash_equals()` for validation
- Regenerate tokens after privilege changes
- Include CSRF in all POST/PUT/DELETE/PATCH requests
- Use `apiRequest()` helper for consistency

### ❌ Don't

- Don't require CSRF for GET/HEAD/OPTIONS requests
- Don't store tokens in cookies (use session only)
- Don't log or display tokens in error messages
- Don't reuse tokens across sessions
- Don't validate tokens before authentication

## Migration Checklist

When adding new API endpoints:

- [ ] Require JWT authentication first
- [ ] Check for POST/PUT/DELETE/PATCH method
- [ ] Call `requireCsrfToken()` before processing
- [ ] Use `apiRequest()` or `apiPost()` for JavaScript calls
- [ ] Test with and without valid CSRF tokens
- [ ] Document in API changelog

## References

- **OWASP CSRF Prevention Cheat Sheet:** https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- **PHP random_bytes():** https://www.php.net/manual/en/function.random-bytes.php
- **hash_equals():** https://www.php.net/manual/en/function.hash-equals.php
- **GitHub Issue #21:** https://github.com/k33bz/lastwar-server1586/issues/21

## Changelog

### v1.0.0 (2025-10-28)
- Initial CSRF protection implementation
- Token generation with `random_bytes(32)`
- Server-side validation with `hash_equals()`
- JavaScript auto-injection in API requests
- Meta tag distribution system
- Operation-specific tokens for critical operations
- Protected 6 major API endpoints

---

**Built with [Claude Code](https://claude.com/claude-code)**
