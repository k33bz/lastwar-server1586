# Server 1586 Test Suite

Comprehensive PHPUnit test suite for the Discord integration and template system.

## Overview

This test suite covers:
- **Discord Variable Replacer** - Template variable replacement logic
- **Discord Templates API** - Template CRUD operations and submission workflow
- **Discord Scheduled Processor** - Scheduled and recurring message processing

## Requirements

- PHP 7.4 or higher
- PHPUnit 10.5 or higher
- Composer (for PHPUnit installation)

## Installation

### Install PHPUnit via Composer

```bash
composer require --dev phpunit/phpunit ^10.5
```

Alternatively, download PHPUnit PHAR:

```bash
wget https://phar.phpunit.de/phpunit-10.5.phar
chmod +x phpunit-10.5.phar
```

## Running Tests

### Run All Tests

```bash
# Using Composer
./vendor/bin/phpunit

# Using PHAR
php phpunit-10.5.phar

# On Windows with Composer
vendor\bin\phpunit.bat
```

### Run Specific Test Suite

```bash
# Run only admin tests
./vendor/bin/phpunit --testsuite "Admin Tests"

# Run specific test file
./vendor/bin/phpunit tests/admin/DiscordVariableReplacerTest.php

# Run specific test method
./vendor/bin/phpunit --filter testReplaceServerNameVariable
```

### Run Tests with Coverage

```bash
# Generate HTML coverage report
./vendor/bin/phpunit --coverage-html tests/coverage

# Generate text coverage report
./vendor/bin/phpunit --coverage-text
```

Then open `tests/coverage/index.html` in your browser.

### Run Tests in Verbose Mode

```bash
./vendor/bin/phpunit --verbose

# Or with testdox format (readable output)
./vendor/bin/phpunit --testdox
```

## Test Files

### DiscordVariableReplacerTest.php

Tests for the variable replacement engine (`admin/discord_variable_replacer.php`).

**Covers:**
- Server name variable replacement
- Sender name with/without IGN
- Alliance variables (name, tag, R5)
- Date/time variables
- Multiple variables in one message
- Edge cases (empty messages, no variables, special characters)

**Example:**
```bash
./vendor/bin/phpunit tests/admin/DiscordVariableReplacerTest.php
```

### DiscordTemplatesApiTest.php

Tests for the template management API (`admin/discord_templates_api.php`).

**Covers:**
- Template structure validation
- Scope filtering (global vs alliance-specific)
- Variable extraction from content
- Template CRUD operations
- Submission workflow (create, approve, reject)
- User permissions (can only delete own templates)

**Example:**
```bash
./vendor/bin/phpunit tests/admin/DiscordTemplatesApiTest.php
```

### DiscordScheduledProcessorTest.php

Tests for scheduled and recurring message processing (`admin/discord_scheduled_processor.php`).

**Covers:**
- Scheduled message structure validation
- Recurring message structure validation
- Time-based filtering (due messages, enabled messages)
- Status transitions (pending → sent)
- Daily/weekly scheduling logic
- Timezone handling
- Variable replacement in scheduled messages

**Example:**
```bash
./vendor/bin/phpunit tests/admin/DiscordScheduledProcessorTest.php
```

## Test Data

All tests use **temporary files** created in the system temp directory:
- Test users: `sys_get_temp_dir()/test_users_*.json`
- Test alliances: `sys_get_temp_dir()/test_alliances_*.json`
- Test templates: `sys_get_temp_dir()/test_templates_*.json`
- Test scheduled messages: `sys_get_temp_dir()/test_scheduled_*.json`
- Test recurring messages: `sys_get_temp_dir()/test_recurring_*.json`

**Files are automatically cleaned up** in the `tearDown()` method after each test.

## Configuration

### phpunit.xml

The configuration file includes:
- Test suites definition
- Coverage reporting settings
- Environment variables for testing
- PHP ini settings

**Key settings:**
```xml
<env name="APP_ENV" value="testing"/>
<env name="APP_NAME" value="Server 1586 Test"/>
```

### tests/bootstrap.php

The bootstrap file:
- Loads environment variables from `.env`
- Sets up test paths
- Cleans up old temp files
- Initializes autoloader

## Writing New Tests

### Test Class Template

```php
<?php
use PHPUnit\Framework\TestCase;

class YourFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        // Create test data files
        // Initialize test environment
    }

    protected function tearDown(): void
    {
        // Clean up test files
        // Reset state
    }

    public function testYourFeature()
    {
        // Arrange
        $data = $this->loadTestData();

        // Act
        $result = yourFunction($data);

        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

### Best Practices

1. **Use descriptive test names** - `testFilterTemplatesByScope()` instead of `testFilter()`
2. **One assertion per concept** - Test one thing at a time
3. **Use setUp/tearDown** - Clean up resources after each test
4. **Mock external dependencies** - Don't make real API calls
5. **Use temp files** - Never modify production data files
6. **Test edge cases** - Empty data, null values, special characters

## Continuous Integration

To run tests in CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Install dependencies
  run: composer install

- name: Run tests
  run: ./vendor/bin/phpunit --coverage-text

- name: Generate coverage
  run: ./vendor/bin/phpunit --coverage-clover coverage.xml
```

## Troubleshooting

### Tests Fail to Find Files

**Problem:** `file_get_contents(): failed to open stream`

**Solution:** Check that the bootstrap file is loading correctly:
```bash
./vendor/bin/phpunit --bootstrap tests/bootstrap.php
```

### Permission Errors

**Problem:** Cannot write to temp directory

**Solution:** Check temp directory permissions:
```bash
# Linux/Mac
chmod 777 /tmp

# Windows
# Check %TEMP% directory permissions
```

### PHPUnit Not Found

**Problem:** `Command 'phpunit' not found`

**Solution:** Install via Composer or use full path:
```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit
```

### Coverage Requires Xdebug

**Problem:** `No code coverage driver available`

**Solution:** Install Xdebug or PCOV:
```bash
# Install Xdebug
pecl install xdebug

# Or use PCOV (faster)
pecl install pcov
```

## Test Results Interpretation

### Successful Test Output

```
PHPUnit 10.5.0 by Sebastian Bergmann and contributors.

...............                                                   15 / 15 (100%)

Time: 00:00.123, Memory: 6.00 MB

OK (15 tests, 42 assertions)
```

### Failed Test Output

```
F

Time: 00:00.456, Memory: 6.00 MB

There was 1 failure:

1) DiscordVariableReplacerTest::testReplaceServerNameVariable
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'Welcome to Server 1586!'
+'Welcome to {server_name}!'

FAILURES!
Tests: 15, Assertions: 42, Failures: 1.
```

## Adding Tests to New Features

When adding new features to the Discord system:

1. **Create test file** in `tests/admin/`
2. **Extend TestCase** class
3. **Add setUp/tearDown** methods
4. **Write tests** for all public methods
5. **Test edge cases** and error handling
6. **Run tests** before committing
7. **Update this README** if needed

## Test Coverage Goals

Target coverage levels:
- **Variable Replacer:** 90%+ coverage
- **Templates API:** 85%+ coverage
- **Scheduled Processor:** 80%+ coverage
- **Overall:** 85%+ coverage

Check current coverage:
```bash
./vendor/bin/phpunit --coverage-text
```

## Related Documentation

- [Discord Templates Guide](../docs/discord-announcements/TEMPLATES.md)
- [Variable Replacement Documentation](../docs/discord-announcements/VARIABLES.md)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## Support

For issues or questions about tests:
1. Check this README first
2. Review test files for examples
3. Create GitHub issue with `testing` label
4. Check PHPUnit documentation

---

**Last Updated:** 2025-01-06
**PHPUnit Version:** 10.5+
**PHP Version:** 7.4+
