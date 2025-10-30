# Using LM Studio for Unit Test Generation

This document explains how to use LM Studio (with qwen3-coder-30b) to generate and evaluate unit tests for the admin panel.

## What LM Studio CAN Do

### ✅ Test Generation
- Generate PHPUnit test cases from source code
- Suggest comprehensive test scenarios
- Identify edge cases automatically
- Create test method names and assertions

### ✅ Test Analysis
- Review existing tests for coverage gaps
- Suggest additional test cases
- Identify missing boundary tests
- Recommend edge cases not covered

### ✅ Code Review for Testability
- Analyze functions for testability
- Suggest refactoring for easier testing
- Identify dependencies that make testing hard
- Recommend mocking strategies

### ✅ Edge Case Discovery
- Identify boundary values to test
- Suggest special character inputs
- Recommend negative test cases
- Find overflow/underflow scenarios

## What LM Studio CANNOT Do

### ❌ Test Execution
- Cannot run PHPUnit itself
- Cannot execute tests in PHP environment
- Cannot generate code coverage reports
- Needs PHPUnit installed separately

### ❌ Environment Setup
- Cannot install composer packages
- Cannot configure PHP environment
- Cannot set up test database

## Usage Examples

### 1. Generate Tests for a File

```bash
# Start LM Studio server first
python scripts/generate-tests.py admin/includes/input_validator.php
```

This will:
1. Read the source code
2. Analyze functions and their parameters
3. Generate comprehensive PHPUnit tests
4. Save to `tests/InputValidatorTest.php`

### 2. Analyze Edge Cases

```bash
python scripts/analyze-edge-cases.py admin/alliances_power_api.php
```

Output example:
```
Edge Cases for validate_alliance_power():
1. [Empty Input] - null - Should return valid=false
2. [Boundary] - 0 - Should return valid=true
3. [Boundary] - -1 - Should return valid=false
4. [Boundary] - 10000000000000 - Should return valid=true
5. [Overflow] - 10000000000001 - Should return valid=false
6. [Type] - "abc" - Should cast to 0, return valid=true
7. [Type] - array - Should cause error
```

### 3. Review Test Coverage

```bash
python scripts/review-tests.py tests/InputValidatorTest.php
```

LM Studio will analyze the test file and suggest:
- Missing test cases
- Uncovered edge cases
- Assertion improvements
- Test organization improvements

## Example: Test Generation Output

For `validate_alliance_tag()`, LM Studio generates:

```php
<?php
use PHPUnit\Framework\TestCase;

class InputValidatorTest extends TestCase
{
    public function testValidateAllianceTagWithValidInput()
    {
        $result = validate_alliance_tag('ABC');
        $this->assertTrue($result['valid']);
        $this->assertEquals('ABC', $result['sanitized']);
        $this->assertNull($result['error']);
    }

    public function testValidateAllianceTagWithLowercaseInput()
    {
        $result = validate_alliance_tag('abc');
        $this->assertTrue($result['valid']);
        $this->assertEquals('ABC', $result['sanitized']); // Uppercased
    }

    public function testValidateAllianceTagWithEmptyString()
    {
        $result = validate_alliance_tag('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('', $result['sanitized']);
        $this->assertStringContainsString('cannot be empty', $result['error']);
    }

    public function testValidateAllianceTagTooShort()
    {
        $result = validate_alliance_tag('A');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('2-10 characters', $result['error']);
    }

    public function testValidateAllianceTagTooLong()
    {
        $result = validate_alliance_tag('ABCDEFGHIJK'); // 11 chars
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('2-10 characters', $result['error']);
    }

    public function testValidateAllianceTagWithSpecialCharacters()
    {
        $result = validate_alliance_tag('AB$C');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('letters and numbers', $result['error']);
    }

    public function testValidateAllianceTagWithWhitespace()
    {
        $result = validate_alliance_tag('  ABC  ');
        $this->assertTrue($result['valid']);
        $this->assertEquals('ABC', $result['sanitized']); // Trimmed
    }

    public function testValidateAllianceTagBoundaryMinLength()
    {
        $result = validate_alliance_tag('AB');
        $this->assertTrue($result['valid']);
        $this->assertEquals('AB', $result['sanitized']);
    }

    public function testValidateAllianceTagBoundaryMaxLength()
    {
        $result = validate_alliance_tag('ABCDEFGHIJ'); // Exactly 10
        $this->assertTrue($result['valid']);
        $this->assertEquals('ABCDEFGHIJ', $result['sanitized']);
    }

    public function testValidateAllianceTagWithNumbers()
    {
        $result = validate_alliance_tag('ABC123');
        $this->assertTrue($result['valid']);
        $this->assertEquals('ABC123', $result['sanitized']);
    }
}
```

## Running the Tests

Once tests are generated:

```bash
# Install PHPUnit (if not already installed)
cd admin
composer require --dev phpunit/phpunit

# Run all tests
vendor/bin/phpunit tests/

# Run specific test file
vendor/bin/phpunit tests/InputValidatorTest.php

# Run with coverage report
vendor/bin/phpunit --coverage-html coverage/ tests/
```

## Integration with CI/CD

Add to `.github/workflows/tests.yml`:

```yaml
name: Unit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install Dependencies
        run: cd admin && composer install

      - name: Run Tests
        run: cd admin && vendor/bin/phpunit tests/
```

## Best Practices

1. **Review Generated Tests**: LM Studio generates good tests, but always review them
2. **Add Custom Tests**: Add domain-specific tests that LM Studio might miss
3. **Update Tests**: When code changes, regenerate or update tests
4. **Run Tests Locally**: Always run tests before committing
5. **Coverage Goals**: Aim for 80%+ code coverage on critical functions

## Limitations

- LM Studio doesn't understand business logic context
- May miss domain-specific edge cases
- Cannot test database interactions without mocking
- Cannot test authentication/authorization flows
- Requires manual review of generated tests

## Summary

**LM Studio is excellent for:**
- Generating boilerplate test code
- Discovering technical edge cases
- Suggesting test structure
- Accelerating test-driven development

**But still requires human developers for:**
- Running actual tests
- Understanding business requirements
- Reviewing and adjusting generated tests
- Setting up test environment
- Interpreting test failures
