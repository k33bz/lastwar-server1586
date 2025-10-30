# Admin Panel Unit Tests

**Version:** 1.0.0
**Date:** October 16, 2025

## Overview

Comprehensive unit test suite for role-based access control (RBAC) across all five user personas:
1. **Admin** - System administrator
2. **R5+APE** - Alliance leader with power editor
3. **R5** - Alliance leader
4. **R4+APE** - Alliance officer with power editor
5. **R4** - Alliance officer

---

## Test Structure

```
admin/tests/
├── README.md                      # This file
├── RoleBasedTest.php              # Role-based access control tests (29 tests)
├── UtilityFunctionsTest.php       # Shared utility function tests (11 tests)
├── run-tests.php                  # Test runner script
├── run-tests.sh                   # Unix test runner
├── run-tests.bat                  # Windows test runner
├── test-results.json              # Generated RBAC test results
└── utility-test-results.json      # Generated utility test results
```

---

## Requirements

- PHP 7.4 or higher
- Composer dependencies installed
- Local server running on `http://localhost:8080`
- Admin panel accessible at `http://localhost:8080/admin`

---

## Running Tests

### Method 1: PHP CLI (Recommended)

```bash
# From admin/tests/ directory

# Run RBAC tests (29 tests)
php RoleBasedTest.php

# Run utility function tests (11 tests)
php UtilityFunctionsTest.php

# Run all tests
php RoleBasedTest.php && php UtilityFunctionsTest.php
```

### Method 2: Test Runner Script

```bash
# Unix/Mac
./run-tests.sh

# Windows
run-tests.bat

# Or use PHP
php run-tests.php
```

### Method 3: Individual Test Methods

```php
<?php
require_once 'RoleBasedTest.php';

$tester = new RoleBasedTest();

// Run specific test
$tester->testAdminTokenGeneration();
$tester->testR5RestrictedAccess();

// Generate report
echo $tester->generateReport();
?>
```

---

## Test Categories

### 1. Authentication Tests

**Purpose:** Verify JWT token generation and validation for each persona

- Test 1.1: Admin token generation
- Test 1.2: R5+APE token generation
- Test 1.3: R4 token generation (no APE)

**Validates:**
- Correct role assignment
- Alliance access configuration
- Power editor flag setting
- Token signature and expiry

---

### 2. Authorization Tests

**Purpose:** Verify page access permissions for each role

- Test 2.1: Admin can access all pages
- Test 2.2: R5 has restricted access
- Test 2.3: R4+APE has APE access
- Test 2.4: R4 (no APE) denied APE access

**Tests Pages:**
- ✓ dashboard.php
- ✓ user_management.php (admin only)
- ✓ alliances_power.php (admin, APE users)
- ✓ alliance_edit.php (admin, r5, r4)
- ✓ security_monitor.php (admin only)
- ✓ generate_test_token.php (admin only)

**Expected Results:**
- HTTP 200: Access granted
- HTTP 403: Access denied
- HTTP 302: Redirect (auth required)

---

### 3. Permission Helper Tests

**Purpose:** Verify helper functions return correct permissions

- Test 3.1: Alliance access filtering
- Test 3.2: Rule signing permissions
- Test 3.3: Power editor validation
- Test 3.4: Alliance deletion permissions

**Functions Tested:**
- `has_alliance_access($token, $alliance_tag)`
- `can_sign_rules($token, $alliance_tag)`
- `is_power_editor($token)`
- `can_delete_alliances($token)`
- `is_r4_or_higher($token)`

---

### 4. Utility Function Tests

**Purpose:** Verify shared utility functions work correctly across the admin panel

**Test File:** `UtilityFunctionsTest.php` (11 tests)

**Categories:**

#### 4.1 CSRF Token Tests (4 tests)
- Token generation (64-character secure random)
- Token validation (accepts valid, rejects invalid)
- Token persistence (same token across calls)
- Token invalidation (rejects wrong tokens)

**Functions Tested:**
- `generateCsrfToken()`
- `validateCsrfToken($token)`
- `getCsrfToken()`

#### 4.2 Input Validation Tests (4 tests)
- Alliance tag validation (2-10 chars, alphanumeric)
- Alliance name validation (3-100 chars)
- Power value validation (non-negative, max 10 trillion)
- Input sanitization (trim, uppercase, escaping)

**Functions Tested:**
- `validate_alliance_tag($tag)`
- `validate_alliance_name($name)`
- `validate_alliance_power($power)`

#### 4.3 IP Detection Tests (3 tests)
- Basic IP detection (REMOTE_ADDR)
- Proxy header support (Cloudflare, X-Forwarded-For)
- IP validation (filters invalid IPs)

**Functions Tested:**
- `get_client_ip()`

**Running Utility Tests:**
```bash
cd admin/tests
php UtilityFunctionsTest.php
```

**Expected Output:**
```
=== Utility Functions Unit Tests ===
Total Tests: 11
Passed: 11
Failed: 0
Pass Rate: 100%
```

---

## Test Results

### Console Output

```
=== Role-Based Access Control Unit Tests ===
Started at: 2025-10-16 14:30:00

Running Authentication Tests...
Running Authorization Tests...
Running Permission Helper Tests...

=== Test Report ===
Total Tests: 24
Passed: 24
Failed: 0
Pass Rate: 100.00%

=== Detailed Results ===

[✓ PASS] Admin Token Generation
  └─ Admin token generated with correct role and wildcard access

[✓ PASS] R5+APE Token Generation
  └─ R5+APE token generated with correct role, APE flag, and alliances

[✓ PASS] R4 Token Generation (No APE)
  └─ R4 token generated without APE access

[✓ PASS] Admin Access: dashboard.php
  └─ Admin can access dashboard.php (HTTP 200)

...

=== End of Report ===
Completed at: 2025-10-16 14:30:15
```

### JSON Export

Test results are automatically exported to `test-results.json`:

```json
{
  "timestamp": "2025-10-16 14:30:15",
  "summary": {
    "total": 24,
    "passed": 24,
    "failed": 0
  },
  "results": [
    {
      "test": "Admin Token Generation",
      "passed": true,
      "message": "Admin token generated with correct role and wildcard access",
      "timestamp": "2025-10-16 14:30:01"
    },
    ...
  ]
}
```

---

## Test Coverage

### Persona Coverage

| Persona | Token Tests | Access Tests | Permission Tests | Total |
|---------|-------------|--------------|------------------|-------|
| Admin | ✓ | ✓✓✓✓✓✓ | ✓✓✓✓ | 11 |
| R5+APE | ✓ | ✓✓ | ✓✓ | 5 |
| R5 | ✓ | ✓✓ | ✓✓ | 5 |
| R4+APE | - | ✓ | ✓ | 2 |
| R4 | ✓ | ✓ | ✓✓ | 4 |

### Feature Coverage

- ✅ JWT token generation and validation
- ✅ Role-based page access control
- ✅ Alliance access filtering
- ✅ Power editor (APE) permissions
- ✅ Rule signing permissions
- ✅ Alliance deletion permissions
- ✅ Wildcard access for admins
- ✅ Multi-alliance access (R5+APE)

---

## Extending Tests

### Adding New Test Methods

```php
/**
 * Test 4.1: Your new test
 */
public function testYourFeature() {
    $testName = 'Feature Test Name';
    $user = $this->testUsers['admin'];
    $token = $this->generateTestToken($user);

    try {
        // Your test logic here
        $result = someTestFunction();

        $this->assert(
            $testName,
            $result === expectedValue,
            "Test passed with expected result"
        );
    } catch (Exception $e) {
        $this->assert($testName, false, "Failed: " . $e->getMessage());
    }
}
```

### Adding New Test Users

Edit `initializeTestUsers()` in `RoleBasedTest.php`:

```php
$this->testUsers['new_persona'] = [
    'identifier' => 'test-custom-' . time(),
    'role' => 'r5',
    'alliances' => ['CUSTOM'],
    'powereditor' => true,
    'description' => 'Custom Persona'
];
```

---

## Troubleshooting

### Common Issues

#### 1. Connection Refused

**Error:** `Failed to connect to localhost port 8080`

**Solution:**
- Ensure local server is running: `php -S localhost:8080`
- Check `$baseUrl` in `RoleBasedTest.php` matches your server

#### 2. JWT Decode Error

**Error:** `Invalid token signature or key rotated`

**Solution:**
- Verify `SECRET_KEY` is set in `.env`
- Check `config.php` loads correctly
- Ensure `jwt.php` is accessible

#### 3. Permission Denied

**Error:** `HTTP 403` when expecting `HTTP 200`

**Solution:**
- Check page access controls in target file
- Verify test token has correct role and flags
- Review `require_admin_session()` usage

#### 4. File Not Found

**Error:** `require_once(): Failed opening 'jwt.php'`

**Solution:**
- Run tests from `admin/tests/` directory
- Verify relative paths in `require_once` statements
- Check file permissions

---

## CI/CD Integration

### Current Setup

✅ **PHP tests are integrated into the CI/CD pipeline** as of Issue #51.

Tests run automatically on every push to `mainline` via GitHub Actions:
- Located in: `.github/workflows/deploy.yml`
- Runs before deployment (blocks deployment if tests fail)
- Uploads test results as artifacts
- Tests run in clean Ubuntu environment (avoids local environment issues)

### Workflow Steps

1. **Setup PHP 8.1** with curl and json extensions
2. **Install Composer dependencies** in admin folder
3. **Start PHP development server** on localhost:8080
4. **Run PHP tests** from admin/tests directory
5. **Upload test results** as GitHub artifact
6. **Stop PHP server** and clean up

### Viewing Test Results

After each CI/CD run:
1. Go to GitHub Actions tab
2. Click on the workflow run
3. Download "php-test-results" artifact
4. Contains `test-results.json` with full test details

### Local Testing

Run locally with:
```bash
python scripts/run-tests.py
```

This will:
- Check critical files exist
- Run PHP tests if PHP is available
- Show pass rate and detailed results
- Note: HTTP endpoint tests may fail locally (environment-specific)

### Manual CI/CD Example

If you need to run tests manually or in a different workflow:

```yaml
name: Admin Panel Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: curl, json

      - name: Install Composer Dependencies
        run: cd admin && composer install --no-dev --optimize-autoloader

      - name: Start PHP Server
        run: |
          php -S localhost:8080 > /dev/null 2>&1 &
          echo $! > php-server.pid
          sleep 3

      - name: Run Tests
        run: php RoleBasedTest.php
        working-directory: admin/tests

      - name: Stop PHP Server
        if: always()
        run: |
          if [ -f php-server.pid ]; then
            kill $(cat php-server.pid) || true
            rm php-server.pid
          fi

      - name: Upload Test Results
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: test-results
          path: admin/tests/test-results.json
```

---

## Best Practices

### Test Isolation

Each test should be independent:
- Generate fresh tokens per test
- Don't rely on test execution order
- Clean up test data after tests

### Descriptive Assertions

Use clear, descriptive messages:

```php
// Good
$this->assert(
    'Admin Power Editor Access',
    is_power_editor($token),
    'Admin implicitly has power editor access'
);

// Bad
$this->assert('Test 1', $result, 'Passed');
```

### Error Handling

Always wrap tests in try-catch:

```php
try {
    // Test code
    $this->assert($testName, $condition, $message);
} catch (Exception $e) {
    $this->assert($testName, false, "Failed: " . $e->getMessage());
}
```

---

## Performance

**Typical Test Run:**
- Total Tests: ~25-30
- Execution Time: ~5-10 seconds
- Memory Usage: <10 MB

**Optimization Tips:**
- Use curl instead of file_get_contents()
- Cache test tokens when possible
- Run critical tests first (fail fast)

---

## Maintenance

### Regular Updates

Update tests when:
- Adding new user roles
- Changing permission logic
- Adding new protected pages
- Modifying JWT structure

### Version Control

Commit test results for tracking:
```bash
git add admin/tests/test-results.json
git commit -m "Update test results - all passing"
```

---

## Support

**Documentation:** See `USER-PERSONAS.md` for persona definitions

**Issues:** Report bugs or request features on GitHub

**Questions:** Check admin panel README or contact maintainer

---

**Last Updated:** October 16, 2025
**Maintained By:** k33bz
