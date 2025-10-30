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

---

## Repository Review with Log Monitoring (v3.0.0+)

### Overview

The `repo-review.py` script now includes intelligent **LM Studio log monitoring** for comprehensive repository analysis. This monitors `~/.lmstudio/server-logs/` in real-time to detect request completion even after HTTP timeouts.

### Key Features

- ✅ **Real-time log monitoring** of LM Studio server logs
- ✅ **Request lifecycle tracking**: Received → BeginProcessing → FinishedProcessing → Generated
- ✅ **Performance stats extraction**: tokens/sec, eval time, generation duration
- ✅ **Completion detection** even after HTTP client timeout
- ✅ **Intelligent polling** with exponential backoff (5s → 30s)
- ✅ **Auto-save results** with no interactive prompts
- ✅ **Parallel-safe**: Multiple reviews can run simultaneously

### Usage

```bash
# Run a single review mode
python scripts/repo-review.py overview
python scripts/repo-review.py security
python scripts/repo-review.py quality
python scripts/repo-review.py docs
python scripts/repo-review.py improvements

# Run all reviews in parallel (recommended)
python scripts/repo-review.py overview &
python scripts/repo-review.py security &
python scripts/repo-review.py quality &
python scripts/repo-review.py docs &
python scripts/repo-review.py improvements &
```

### How Log Monitoring Works

1. **Log File Location**: `~/.lmstudio/server-logs/YYYY-MM/YYYY-MM-DD.1.log`

2. **Events Tracked**:
   - `Received request: POST to /v1/chat/completions` - Request queued
   - `BeginProcessingPrompt` - Model started processing
   - `FinishedProcessingPrompt. Progress: 100` - Prompt processing complete
   - `Generated prediction` - Response ready
   - `eval time = XX ms / YY runs` - Performance stats

3. **Smart Detection**:
   - Counts events after request start time
   - Determines `in_progress` (processing) vs `completed` status
   - Extracts performance metrics from log entries

### Example Output

```
================================================================================
  ⚡ Querying LM Studio
================================================================================

   >> Sending request to LM Studio...
   [Attempt 1] Waiting up to 40s... (elapsed: 0s)
   [TIMEOUT] After 40s - monitoring logs, waiting 5s...
   [LOG DETECT] LM Studio still processing (events: 1 began, 0 completed)
   [Attempt 2] Waiting up to 50s... (elapsed: 47s)
   [LOG DETECT] LM Studio completed processing! Retrieving result...
   [TIMEOUT] After 50s - monitoring logs, waiting 8s...
   [LOG MONITOR] Still processing... (90s elapsed)
   [LOG DETECT] Generation complete! Attempting to retrieve...
   [STATS] Total: 59.4s @ 15.6 tok/s
   [SUCCESS] Retrieved result after 179.9s total

================================================================================
  📊 Review Results (241.2s)
================================================================================
```

### Configuration

Located in `scripts/repo-review.py`:

```python
# Configuration
LMSTUDIO_URL = 'http://localhost:1234/v1'
LMSTUDIO_MODEL = 'qwen/qwen3-coder-30b'
TEMPERATURE = 0.3
MAX_TOKENS = 4000

# Polling configuration
INITIAL_TIMEOUT = 30          # Initial HTTP timeout (seconds)
MAX_POLL_TIME = 600           # Maximum total wait (10 minutes)
POLL_INTERVAL_START = 5       # Initial retry delay
POLL_INTERVAL_MAX = 30        # Maximum retry delay

# Log monitoring
LMSTUDIO_LOG_DIR = Path.home() / '.lmstudio' / 'server-logs'
```

### Review Modes

**1. Overview** (`overview`)
- Architecture and structure assessment
- Technology stack analysis
- Code organization review
- Key strengths and weaknesses
- Priority improvements

**2. Security** (`security`)
- Authentication/authorization audit
- Input validation review
- XSS/CSRF/SQL injection checks
- Data protection analysis
- Configuration security
- Access control review

**3. Quality** (`quality`)
- Code organization and naming
- Coding standards compliance
- Error handling patterns
- Testing coverage
- Performance considerations
- Maintainability analysis

**4. Documentation** (`docs`)
- README completeness
- API documentation
- Code comments quality
- Architecture documentation
- Developer guides
- Changelog review

**5. Improvements** (`improvements`)
- Architecture refactoring opportunities
- Technology upgrades
- Performance optimizations
- Developer experience improvements
- Automation opportunities

### Output

Results are automatically saved to timestamped files:

```
review-overview-20251030-083942.md       (12KB)
review-security-20251030-084000.md       (9.3KB)
review-quality-20251030-084018.md        (timed out)
review-docs-20251030-085951.md           (5.0KB)
review-improvements-20251030-084031.md   (timed out)
```

### Benefits

1. **Never loses work**: Results retrieved even after HTTP timeout
2. **Better visibility**: Real-time progress with log monitoring
3. **Queue awareness**: Knows when LM Studio is busy vs processing
4. **Performance insights**: Actual processing metrics (tokens/sec)
5. **Parallel execution**: Run multiple reviews simultaneously
6. **Unattended operation**: Auto-saves results, no user input needed

### Troubleshooting

**Issue**: Log monitoring reports "Log file not found"
- **Solution**: Ensure LM Studio server is running and has created today's log file

**Issue**: Reviews timeout after 10 minutes
- **Solution**: Increase `MAX_POLL_TIME` in `scripts/repo-review.py` or break into smaller reviews

**Issue**: Multiple reviews interfere with each other
- **Solution**: They shouldn't! Log monitoring tracks events by timestamp. Each review monitors only events after its own start time.

**Issue**: Performance stats not showing
- **Solution**: Stats only appear after successful generation. Check that LM Studio completed the request.

### Technical Details

The log monitoring implementation:

```python
def monitor_lmstudio_logs(start_time: float) -> dict:
    """
    Monitor LM Studio logs to detect request completion.

    Returns:
        dict with keys:
        - completed (bool): Generation finished
        - in_progress (bool): Model currently processing
        - stats (dict): Performance metrics
        - events (dict): Event counts
    """
    # Read last 50KB of today's log file
    # Parse events after start_time
    # Count: received, begin_processing, finished_processing, generated
    # Extract: eval_time_ms, eval_runs, tokens_per_sec
    # Return status and metrics
```

Log monitoring runs automatically when HTTP requests timeout, providing seamless fallback for long-running analysis tasks.

---

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
