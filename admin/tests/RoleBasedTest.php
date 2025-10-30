<?php
/**
 * Role-Based Access Control Unit Tests
 *
 * Comprehensive test suite for all user personas:
 * - Admin
 * - R5+APE (Alliance Leader with Power Editor)
 * - R5 (Alliance Leader)
 * - R4+APE (Alliance Officer with Power Editor)
 * - R4 (Alliance Officer)
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../json_helpers.php';

class RoleBasedTest {

    private $testResults = [];
    private $testUsers = [];
    private $baseUrl = 'http://localhost:8080/admin';

    public function __construct() {
        $this->initializeTestUsers();
    }

    /**
     * Initialize test users for each persona
     */
    private function initializeTestUsers() {
        $this->testUsers = [
            'admin' => [
                'identifier' => 'test-admin-' . time(),
                'role' => 'admin',
                'alliances' => ['*'],
                'powereditor' => false, // Implicit for admin
                'description' => 'System Administrator'
            ],
            'r5_ape' => [
                'identifier' => 'test-r5ape-' . time(),
                'role' => 'r5',
                'alliances' => ['UvvU', '1984'],
                'powereditor' => true,
                'description' => 'Alliance Leader with Power Editor'
            ],
            'r5' => [
                'identifier' => 'test-r5-' . time(),
                'role' => 'r5',
                'alliances' => ['K44'],
                'powereditor' => false,
                'description' => 'Alliance Leader'
            ],
            'r4_ape' => [
                'identifier' => 'test-r4ape-' . time(),
                'role' => 'r4',
                'alliances' => ['MTOP'],
                'powereditor' => true,
                'description' => 'Alliance Officer with Power Editor'
            ],
            'r4' => [
                'identifier' => 'test-r4-' . time(),
                'role' => 'r4',
                'alliances' => ['STR8'],
                'powereditor' => false,
                'description' => 'Alliance Officer'
            ]
        ];
    }

    /**
     * Generate JWT token for test user
     *
     * @param array $userData User configuration
     * @param int $expiry Token expiry in seconds
     * @return string JWT token
     */
    private function generateTestToken($userData, $expiry = 86400) {
        $payload = [
            'sub' => $userData['identifier'],
            'aud' => $userData['role'],
            'alliances' => $userData['alliances'],
            'powereditor' => $userData['powereditor'],
            'jti' => bin2hex(random_bytes(16)),
            'test_token' => true
        ];

        return encode_jwt($payload, $expiry);
    }

    /**
     * Make HTTP request with JWT token
     *
     * @param string $url Request URL
     * @param string $token JWT token
     * @param string $method HTTP method
     * @return array Response data [status, body, headers]
     */
    private function makeRequest($url, $token, $method = 'GET') {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIE => "jwt=$token",
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true
        ]);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        return [
            'status' => $statusCode,
            'body' => $body,
            'headers' => $headers
        ];
    }

    /**
     * Assert test condition and record result
     *
     * @param string $testName Test name
     * @param bool $condition Test condition
     * @param string $message Test message
     */
    private function assert($testName, $condition, $message) {
        $this->testResults[] = [
            'test' => $testName,
            'passed' => $condition,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    // ========================================================================
    // AUTHENTICATION TESTS
    // ========================================================================

    /**
     * Test 1.1: Admin token generation and validation
     */
    public function testAdminTokenGeneration() {
        $testName = 'Admin Token Generation';
        $user = $this->testUsers['admin'];

        try {
            $token = $this->generateTestToken($user);
            $decoded = decode_jwt($token);

            $this->assert(
                $testName,
                $decoded->aud === 'admin' &&
                in_array('*', $decoded->alliances),
                "Admin token generated with correct role and wildcard access"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test 1.2: R5+APE token generation and validation
     */
    public function testR5APETokenGeneration() {
        $testName = 'R5+APE Token Generation';
        $user = $this->testUsers['r5_ape'];

        try {
            $token = $this->generateTestToken($user);
            $decoded = decode_jwt($token);

            $this->assert(
                $testName,
                $decoded->aud === 'r5' &&
                $decoded->powereditor === true &&
                in_array('UvvU', $decoded->alliances),
                "R5+APE token generated with correct role, APE flag, and alliances"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    /**
     * Test 1.3: R4 token generation (no APE)
     */
    public function testR4TokenGeneration() {
        $testName = 'R4 Token Generation (No APE)';
        $user = $this->testUsers['r4'];

        try {
            $token = $this->generateTestToken($user);
            $decoded = decode_jwt($token);

            $this->assert(
                $testName,
                $decoded->aud === 'r4' &&
                $decoded->powereditor === false &&
                in_array('STR8', $decoded->alliances),
                "R4 token generated without APE access"
            );
        } catch (Exception $e) {
            $this->assert($testName, false, "Failed: " . $e->getMessage());
        }
    }

    // ========================================================================
    // AUTHORIZATION TESTS
    // ========================================================================

    /**
     * Test 2.1: Admin can access all pages
     */
    public function testAdminPageAccess() {
        $user = $this->testUsers['admin'];
        $token = $this->generateTestToken($user);

        $pages = [
            'dashboard.php',
            'user_management.php',
            'alliances_power.php',
            'alliance_edit.php',
            'security_monitor.php',
            'generate_test_token.php'
        ];

        foreach ($pages as $page) {
            $testName = "Admin Access: $page";
            $response = $this->makeRequest("{$this->baseUrl}/$page", $token);

            $this->assert(
                $testName,
                $response['status'] === 200,
                "Admin can access $page (HTTP {$response['status']})"
            );
        }
    }

    /**
     * Test 2.2: R5 has restricted access
     */
    public function testR5RestrictedAccess() {
        $user = $this->testUsers['r5'];
        $token = $this->generateTestToken($user);

        $allowedPages = [
            'dashboard.php' => 200,
            'alliance_edit.php' => 200
        ];

        $forbiddenPages = [
            'alliances_power.php' => 403,
            'user_management.php' => 403,
            'security_monitor.php' => 403,
            'generate_test_token.php' => 403
        ];

        foreach ($allowedPages as $page => $expectedStatus) {
            $testName = "R5 Allowed Access: $page";
            $response = $this->makeRequest("{$this->baseUrl}/$page", $token);

            $this->assert(
                $testName,
                $response['status'] === $expectedStatus,
                "R5 can access $page (HTTP {$response['status']})"
            );
        }

        foreach ($forbiddenPages as $page => $expectedStatus) {
            $testName = "R5 Denied Access: $page";
            $response = $this->makeRequest("{$this->baseUrl}/$page", $token);

            $this->assert(
                $testName,
                $response['status'] === $expectedStatus || $response['status'] === 302,
                "R5 cannot access $page (HTTP {$response['status']})"
            );
        }
    }

    /**
     * Test 2.3: R4+APE has APE access
     */
    public function testR4APEAccess() {
        $user = $this->testUsers['r4_ape'];
        $token = $this->generateTestToken($user);

        $testName = 'R4+APE Power Editor Access';
        $response = $this->makeRequest("{$this->baseUrl}/alliances_power.php", $token);

        $this->assert(
            $testName,
            $response['status'] === 200,
            "R4+APE can access Power Editor (HTTP {$response['status']})"
        );
    }

    /**
     * Test 2.4: R4 (no APE) denied APE access
     */
    public function testR4NoAPEAccess() {
        $user = $this->testUsers['r4'];
        $token = $this->generateTestToken($user);

        $testName = 'R4 Denied Power Editor Access';
        $response = $this->makeRequest("{$this->baseUrl}/alliances_power.php", $token);

        $this->assert(
            $testName,
            $response['status'] === 403 || $response['status'] === 302,
            "R4 cannot access Power Editor (HTTP {$response['status']})"
        );
    }

    // ========================================================================
    // PERMISSION HELPER TESTS
    // ========================================================================

    /**
     * Test 3.1: Alliance access filtering
     */
    public function testAllianceAccessFiltering() {
        $user = $this->testUsers['r5'];
        $token = $this->generateTestToken($user);
        $decoded = decode_jwt($token);

        // Test has_alliance_access() function
        $testName = 'Alliance Access: Assigned Alliance';
        $this->assert(
            $testName,
            has_alliance_access($decoded, 'K44'),
            "R5 has access to assigned alliance K44"
        );

        $testName = 'Alliance Access: Non-Assigned Alliance';
        $this->assert(
            $testName,
            !has_alliance_access($decoded, 'UvvU'),
            "R5 does NOT have access to non-assigned alliance UvvU"
        );

        // Test admin wildcard access
        $adminUser = $this->testUsers['admin'];
        $adminToken = $this->generateTestToken($adminUser);
        $adminDecoded = decode_jwt($adminToken);

        $testName = 'Admin Wildcard Access';
        $this->assert(
            $testName,
            has_alliance_access($adminDecoded, 'UvvU') &&
            has_alliance_access($adminDecoded, 'K44') &&
            has_alliance_access($adminDecoded, 'MTOP'),
            "Admin has wildcard access to all alliances"
        );
    }

    /**
     * Test 3.2: Rule signing permissions
     */
    public function testRuleSigningPermissions() {
        // R5 can sign
        $r5User = $this->testUsers['r5'];
        $r5Token = $this->generateTestToken($r5User);
        $r5Decoded = decode_jwt($r5Token);

        $testName = 'R5 Can Sign Rules';
        $this->assert(
            $testName,
            can_sign_rules($r5Decoded, 'K44'),
            "R5 can sign rules for assigned alliance"
        );

        // R4 cannot sign
        $r4User = $this->testUsers['r4'];
        $r4Token = $this->generateTestToken($r4User);
        $r4Decoded = decode_jwt($r4Token);

        $testName = 'R4 Cannot Sign Rules';
        $this->assert(
            $testName,
            !can_sign_rules($r4Decoded, 'STR8'),
            "R4 cannot sign rules even for assigned alliance"
        );

        // Admin can sign
        $adminUser = $this->testUsers['admin'];
        $adminToken = $this->generateTestToken($adminUser);
        $adminDecoded = decode_jwt($adminToken);

        $testName = 'Admin Can Sign Rules';
        $this->assert(
            $testName,
            can_sign_rules($adminDecoded, 'UvvU'),
            "Admin can sign rules for any alliance"
        );
    }

    /**
     * Test 3.3: Power editor validation
     */
    public function testPowerEditorValidation() {
        // Admin always has APE
        $adminUser = $this->testUsers['admin'];
        $adminToken = $this->generateTestToken($adminUser);
        $adminDecoded = decode_jwt($adminToken);

        $testName = 'Admin Implicit APE Access';
        $this->assert(
            $testName,
            is_power_editor($adminDecoded),
            "Admin implicitly has power editor access"
        );

        // R5+APE has explicit APE
        $r5ApeUser = $this->testUsers['r5_ape'];
        $r5ApeToken = $this->generateTestToken($r5ApeUser);
        $r5ApeDecoded = decode_jwt($r5ApeToken);

        $testName = 'R5+APE Has APE Access';
        $this->assert(
            $testName,
            is_power_editor($r5ApeDecoded),
            "R5+APE has power editor access via flag"
        );

        // R5 without APE
        $r5User = $this->testUsers['r5'];
        $r5Token = $this->generateTestToken($r5User);
        $r5Decoded = decode_jwt($r5Token);

        $testName = 'R5 No APE Access';
        $this->assert(
            $testName,
            !is_power_editor($r5Decoded),
            "R5 without flag does not have APE access"
        );

        // R4 without APE
        $r4User = $this->testUsers['r4'];
        $r4Token = $this->generateTestToken($r4User);
        $r4Decoded = decode_jwt($r4Token);

        $testName = 'R4 No APE Access';
        $this->assert(
            $testName,
            !is_power_editor($r4Decoded),
            "R4 without flag does not have APE access"
        );
    }

    /**
     * Test 3.4: Alliance deletion permissions
     */
    public function testAllianceDeletionPermissions() {
        // Only admin can delete
        $adminUser = $this->testUsers['admin'];
        $adminToken = $this->generateTestToken($adminUser);
        $adminDecoded = decode_jwt($adminToken);

        $testName = 'Admin Can Delete Alliances';
        $this->assert(
            $testName,
            can_delete_alliances($adminDecoded),
            "Admin can delete alliances"
        );

        // R5+APE cannot delete
        $r5ApeUser = $this->testUsers['r5_ape'];
        $r5ApeToken = $this->generateTestToken($r5ApeUser);
        $r5ApeDecoded = decode_jwt($r5ApeToken);

        $testName = 'R5+APE Cannot Delete Alliances';
        $this->assert(
            $testName,
            !can_delete_alliances($r5ApeDecoded),
            "R5+APE cannot delete alliances"
        );
    }

    // ========================================================================
    // TEST RUNNER
    // ========================================================================

    /**
     * Run all tests
     *
     * @return array Test results
     */
    public function runAllTests() {
        echo "\n=== Role-Based Access Control Unit Tests ===\n";
        echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

        // Authentication Tests
        echo "Running Authentication Tests...\n";
        $this->testAdminTokenGeneration();
        $this->testR5APETokenGeneration();
        $this->testR4TokenGeneration();

        // Authorization Tests
        echo "Running Authorization Tests...\n";
        $this->testAdminPageAccess();
        $this->testR5RestrictedAccess();
        $this->testR4APEAccess();
        $this->testR4NoAPEAccess();

        // Permission Helper Tests
        echo "Running Permission Helper Tests...\n";
        $this->testAllianceAccessFiltering();
        $this->testRuleSigningPermissions();
        $this->testPowerEditorValidation();
        $this->testAllianceDeletionPermissions();

        return $this->testResults;
    }

    /**
     * Generate test report
     *
     * @param array $results Test results
     * @return string Report output
     */
    public function generateReport($results = null) {
        if ($results === null) {
            $results = $this->testResults;
        }

        $passed = count(array_filter($results, fn($r) => $r['passed']));
        $failed = count($results) - $passed;
        $passRate = count($results) > 0 ? ($passed / count($results)) * 100 : 0;

        $report = "\n=== Test Report ===\n";
        $report .= "Total Tests: " . count($results) . "\n";
        $report .= "Passed: $passed\n";
        $report .= "Failed: $failed\n";
        $report .= "Pass Rate: " . number_format($passRate, 2) . "%\n\n";

        $report .= "=== Detailed Results ===\n\n";

        foreach ($results as $result) {
            $status = $result['passed'] ? '✓ PASS' : '✗ FAIL';
            $report .= "[$status] {$result['test']}\n";
            $report .= "  └─ {$result['message']}\n\n";
        }

        $report .= "=== End of Report ===\n";
        $report .= "Completed at: " . date('Y-m-d H:i:s') . "\n";

        return $report;
    }

    /**
     * Export results to JSON
     *
     * @param string $filename Output filename
     */
    public function exportJSON($filename = 'test-results.json') {
        $export = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total' => count($this->testResults),
                'passed' => count(array_filter($this->testResults, fn($r) => $r['passed'])),
                'failed' => count(array_filter($this->testResults, fn($r) => !$r['passed'])),
            ],
            'results' => $this->testResults
        ];

        file_put_contents(__DIR__ . "/$filename", json_encode($export, JSON_PRETTY_PRINT));
        echo "\nResults exported to: $filename\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $tester = new RoleBasedTest();
        $results = $tester->runAllTests();
        echo $tester->generateReport($results);
        $tester->exportJSON();

        // Exit with appropriate code
        $failed = count(array_filter($results, fn($r) => !$r['passed']));
        exit($failed > 0 ? 1 : 0);
    } catch (Exception $e) {
        echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
