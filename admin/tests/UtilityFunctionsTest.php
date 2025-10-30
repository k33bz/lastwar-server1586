<?php
/**
 * Utility Functions Unit Tests
 *
 * Tests for shared utility functions across the admin panel:
 * - CSRF token generation and validation
 * - Input validation functions
 * - IP address detection
 * - Security helpers
 *
 * @version 1.0.0
 * @date 2025-10-30
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

// Suppress session warnings in CLI mode
error_reporting(E_ALL & ~E_WARNING);

// Start session for CSRF tests
session_start();

// Set up paths
define('ADMIN_INIT', true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/input_validator.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

class UtilityFunctionsTest {
    private $testResults = [];
    private $testCount = 0;
    private $passCount = 0;
    private $failCount = 0;

    public function __construct() {
        echo "\n";
        echo "=== Utility Functions Unit Tests ===\n";
        echo "Started at: " . date('Y-m-d H:i:s') . "\n";
        echo "\n";
    }

    /**
     * Run all tests
     */
    public function runAllTests() {
        // CSRF Tests
        echo "Running CSRF Token Tests...\n";
        $this->testCsrfTokenGeneration();
        $this->testCsrfTokenValidation();
        $this->testCsrfTokenPersistence();
        $this->testCsrfTokenInvalidation();

        // Input Validation Tests
        echo "\nRunning Input Validation Tests...\n";
        $this->testValidateAllianceTag();
        $this->testValidateAllianceName();
        $this->testValidatePower();
        $this->testInputSanitization();

        // IP Detection Tests
        echo "\nRunning IP Detection Tests...\n";
        $this->testGetClientIpBasic();
        $this->testGetClientIpWithProxies();
        $this->testGetClientIpValidation();

        // Generate report
        $this->generateReport();
    }

    /**
     * Assert test condition
     */
    private function assert($testName, $condition, $message = '') {
        $this->testCount++;

        $result = [
            'test' => $testName,
            'passed' => $condition,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($condition) {
            $this->passCount++;
            echo "[✓ PASS] {$testName}\n";
            if ($message) {
                echo "  └─ {$message}\n";
            }
        } else {
            $this->failCount++;
            echo "[✗ FAIL] {$testName}\n";
            if ($message) {
                echo "  └─ {$message}\n";
            }
        }

        $this->testResults[] = $result;
    }

    // ==================== CSRF Token Tests ====================

    /**
     * Test CSRF token generation
     */
    public function testCsrfTokenGeneration() {
        $testName = 'CSRF Token Generation';

        try {
            // Clear any existing session
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }

            // Generate token
            $token = generateCsrfToken();

            $this->assert(
                $testName,
                !empty($token) && strlen($token) === 64,
                "Generated 64-character token: " . substr($token, 0, 16) . "..."
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test CSRF token validation
     */
    public function testCsrfTokenValidation() {
        $testName = 'CSRF Token Validation';

        try {
            // Generate a valid token
            $token = generateCsrfToken();

            // Validate correct token
            $isValid = validateCsrfToken($token);

            $this->assert(
                $testName,
                $isValid === true,
                "Valid token correctly validated"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test CSRF token persistence across calls
     */
    public function testCsrfTokenPersistence() {
        $testName = 'CSRF Token Persistence';

        try {
            // Get token twice
            $token1 = getCsrfToken();
            $token2 = getCsrfToken();

            $this->assert(
                $testName,
                $token1 === $token2,
                "Same token returned on multiple calls"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test CSRF token rejects invalid tokens
     */
    public function testCsrfTokenInvalidation() {
        $testName = 'CSRF Token Invalidation';

        try {
            // Generate valid token first
            generateCsrfToken();

            // Try invalid token
            $invalidToken = 'invalid_token_12345';
            $isValid = validateCsrfToken($invalidToken);

            $this->assert(
                $testName,
                $isValid === false,
                "Invalid token correctly rejected"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    // ==================== Input Validation Tests ====================

    /**
     * Test alliance tag validation
     */
    public function testValidateAllianceTag() {
        $testName = 'Validate Alliance Tag';

        try {
            // Valid tags
            $result1 = validate_alliance_tag('UvvU');
            $result2 = validate_alliance_tag('ABC');
            $result3 = validate_alliance_tag('TEST123');

            // Invalid tags
            $result4 = validate_alliance_tag(''); // empty
            $result5 = validate_alliance_tag('A'); // too short
            $result6 = validate_alliance_tag('TOOLONGTAGNAME'); // too long
            $result7 = validate_alliance_tag('ABC<script>'); // invalid chars

            $allCorrect = ($result1['valid'] && $result2['valid'] && $result3['valid']) &&
                         (!$result4['valid'] && !$result5['valid'] && !$result6['valid'] && !$result7['valid']);

            $this->assert(
                $testName,
                $allCorrect,
                "Valid tags accepted, invalid tags rejected"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test alliance name validation
     */
    public function testValidateAllianceName() {
        $testName = 'Validate Alliance Name';

        try {
            // Valid names
            $result1 = validate_alliance_name('veni vidi vici');
            $result2 = validate_alliance_name('Test Alliance 123');

            // Invalid names
            $result3 = validate_alliance_name(''); // empty
            $result4 = validate_alliance_name('AB'); // too short
            $result5 = validate_alliance_name(str_repeat('A', 101)); // too long

            $allCorrect = ($result1['valid'] && $result2['valid']) &&
                         (!$result3['valid'] && !$result4['valid'] && !$result5['valid']);

            $this->assert(
                $testName,
                $allCorrect,
                "Valid names accepted, invalid names rejected"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test power value validation
     */
    public function testValidatePower() {
        $testName = 'Validate Power Value';

        try {
            // Valid powers
            $result1 = validate_alliance_power(1000000);
            $result2 = validate_alliance_power('500000');
            $result3 = validate_alliance_power(0);

            // Invalid powers
            $result4 = validate_alliance_power(-100); // negative
            $result5 = validate_alliance_power(100000000000000); // too large

            $allCorrect = ($result1['valid'] && $result2['valid'] && $result3['valid']) &&
                         (!$result4['valid'] && !$result5['valid']);

            $this->assert(
                $testName,
                $allCorrect,
                "Valid power values accepted, invalid values rejected"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test basic input sanitization
     */
    public function testInputSanitization() {
        $testName = 'Input Sanitization';

        try {
            // Test that validation functions sanitize input
            $result1 = validate_alliance_tag('  uvvu  '); // should trim and uppercase
            $result2 = validate_alliance_name('  Test Name  '); // should trim

            $sanitizedCorrectly = ($result1['sanitized'] === 'UVVU') &&
                                 ($result2['sanitized'] === 'Test Name');

            $this->assert(
                $testName,
                $sanitizedCorrectly,
                "Inputs correctly sanitized (trimmed, uppercased)"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    // ==================== IP Detection Tests ====================

    /**
     * Test basic IP detection
     */
    public function testGetClientIpBasic() {
        $testName = 'Get Client IP (Basic)';

        try {
            // Mock REMOTE_ADDR
            $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

            $ip = get_client_ip();

            $this->assert(
                $testName,
                filter_var($ip, FILTER_VALIDATE_IP) !== false,
                "Returns valid IP address: {$ip}"
            );

            // Clean up
            unset($_SERVER['REMOTE_ADDR']);
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test IP detection with proxy headers
     */
    public function testGetClientIpWithProxies() {
        $testName = 'Get Client IP (Proxy Headers)';

        try {
            // Mock Cloudflare header
            $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.195';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1'; // proxy IP

            $ip = get_client_ip();

            // Should prioritize CF header
            $this->assert(
                $testName,
                $ip === '203.0.113.195',
                "Correctly uses Cloudflare header: {$ip}"
            );

            // Clean up
            unset($_SERVER['HTTP_CF_CONNECTING_IP']);
            unset($_SERVER['REMOTE_ADDR']);
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test IP detection validates addresses
     */
    public function testGetClientIpValidation() {
        $testName = 'Get Client IP (Validation)';

        try {
            // Mock invalid IP
            $_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-an-ip';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

            $ip = get_client_ip();

            // Should fall back to valid REMOTE_ADDR
            $this->assert(
                $testName,
                filter_var($ip, FILTER_VALIDATE_IP) !== false,
                "Falls back to valid IP when invalid header present: {$ip}"
            );

            // Clean up
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
            unset($_SERVER['REMOTE_ADDR']);
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    // ==================== Report Generation ====================

    /**
     * Generate test report
     */
    public function generateReport() {
        echo "\n";
        echo "=== Test Report ===\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passCount}\n";
        echo "Failed: {$this->failCount}\n";

        if ($this->testCount > 0) {
            $passRate = round(($this->passCount / $this->testCount) * 100, 2);
            echo "Pass Rate: {$passRate}%\n";
        }

        echo "\n=== Detailed Results ===\n\n";

        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? '✓ PASS' : '✗ FAIL';
            echo "[{$status}] {$result['test']}\n";
            if ($result['message']) {
                echo "  └─ {$result['message']}\n";
            }
        }

        echo "\n=== End of Report ===\n";
        echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
        echo "\n";

        // Export results to JSON
        $exportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total' => $this->testCount,
                'passed' => $this->passCount,
                'failed' => $this->failCount,
                'pass_rate' => $this->testCount > 0 ?
                    round(($this->passCount / $this->testCount) * 100, 2) : 0
            ],
            'results' => $this->testResults
        ];

        $jsonFile = __DIR__ . '/utility-test-results.json';
        file_put_contents($jsonFile, json_encode($exportData, JSON_PRETTY_PRINT));
        echo "Results exported to: {$jsonFile}\n";
        echo "\n";

        // Return exit code based on results
        return $this->failCount === 0 ? 0 : 1;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $tester = new UtilityFunctionsTest();
    $exitCode = $tester->runAllTests();
    exit($exitCode ?? 0);
}
?>
