<?php
/**
 * R4 API Unit Tests
 * Tests for UID-based R4 officer management
 *
 * @version 1.0.0
 * @date 2025-11-19
 */

class R4ApiTest {
    private $testAlliancesFile;
    private $testUsersFile;
    private $originalAlliancesData;
    private $originalUsersData;
    private $testResults = [];

    public function __construct() {
        $this->testAlliancesFile = __DIR__ . '/../../data/alliances.json';
        $this->testUsersFile = __DIR__ . '/../users.json';

        // Backup original data
        if (file_exists($this->testAlliancesFile)) {
            $this->originalAlliancesData = file_get_contents($this->testAlliancesFile);
        }
        if (file_exists($this->testUsersFile)) {
            $this->originalUsersData = file_get_contents($this->testUsersFile);
        }
    }

    public function __destruct() {
        // Restore original data
        if ($this->originalAlliancesData !== null) {
            file_put_contents($this->testAlliancesFile, $this->originalAlliancesData);
        }
        if ($this->originalUsersData !== null) {
            file_put_contents($this->testUsersFile, $this->originalUsersData);
        }
    }

    /**
     * Run all R4 API tests
     */
    public function runAllTests() {
        echo "\n=== R4 API Tests (UID System) ===\n\n";

        $this->testGetEligibleR4Users();
        $this->testAddR4WithUID();
        $this->testAddR4WithoutUID();
        $this->testUpdateR4UID();
        $this->testRemoveR4();
        $this->testR4Permissions();
        $this->testR4AllianceAccess();
        $this->testR4VotingRights();
        $this->testDuplicateR4Detection();
        $this->testInvalidAllianceTag();

        return $this->testResults;
    }

    /**
     * Test getting eligible R4 users
     */
    private function testGetEligibleR4Users() {
        $testName = "Get Eligible R4 Users by Alliance";

        try {
            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            // Get first alliance tag
            $test_alliance = $alliances_data[0]['tag'] ?? 'ORCE';

            // Find users with r4 role for this alliance
            $eligible_users = [];

            foreach ($users_data['users'] as $user) {
                $roles = $user['roles'] ?? [];
                if (!in_array('r4', $roles)) {
                    continue;
                }

                $user_alliances = $user['servers']['1586']['alliances'] ?? [];
                if (in_array($test_alliance, $user_alliances) || in_array('*', $user_alliances)) {
                    $eligible_users[] = $user;
                }
            }

            if (count($eligible_users) === 0) {
                throw new Exception("No eligible R4 users found for alliance {$test_alliance}");
            }

            $this->pass($testName, "Found " . count($eligible_users) . " eligible R4 users for {$test_alliance}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test adding R4 with UID
     */
    private function testAddR4WithUID() {
        $testName = "Add R4 Officer with UID";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);
            $users_data = json_decode(file_get_contents($this->testUsersFile), true);

            // Get test alliance and user
            $test_alliance_idx = 0;
            $test_alliance = &$alliances_data[$test_alliance_idx];
            $test_user = $users_data['users'][0];

            // Initialize r4s array if not exists
            if (!isset($test_alliance['r4s'])) {
                $test_alliance['r4s'] = [];
            }

            // Add R4 with UID
            $new_r4 = [
                'name' => $test_user['in_game_name'] ?? 'Test R4',
                'email' => $test_user['email'],
                'user_uid' => $test_user['uid'],
                'discordId' => $test_user['discord_id'] ?? null,
                'canVote' => true,
                'role' => 'Military Director',
                'addedDate' => gmdate('Y-m-d\TH:i:s\Z')
            ];

            $test_alliance['r4s'][] = $new_r4;
            file_put_contents($this->testAlliancesFile, json_encode($alliances_data, JSON_PRETTY_PRINT));

            // Verify addition
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);
            $r4s = $alliances_data[$test_alliance_idx]['r4s'];
            $found = false;

            foreach ($r4s as $r4) {
                if ($r4['user_uid'] === $test_user['uid']) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception("R4 not added successfully");
            }

            $this->pass($testName, "R4 added with UID: {$test_user['uid']}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test adding R4 without UID (legacy)
     */
    private function testAddR4WithoutUID() {
        $testName = "Add R4 Officer without UID (Legacy)";

        try {
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            $test_alliance_idx = 0;
            $test_alliance = &$alliances_data[$test_alliance_idx];

            // Add R4 without UID (legacy format)
            $legacy_r4 = [
                'name' => 'Legacy R4 Officer',
                'email' => null,
                'user_uid' => null,
                'discordId' => null,
                'canVote' => false,
                'role' => 'Recruiter',
                'addedDate' => gmdate('Y-m-d\TH:i:s\Z')
            ];

            $test_alliance['r4s'][] = $legacy_r4;
            file_put_contents($this->testAlliancesFile, json_encode($alliances_data, JSON_PRETTY_PRINT));

            // Verify addition
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);
            $r4s = $alliances_data[$test_alliance_idx]['r4s'];
            $found = false;

            foreach ($r4s as $r4) {
                if ($r4['name'] === 'Legacy R4 Officer') {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception("Legacy R4 not added");
            }

            $this->pass($testName, "Legacy R4 (no UID) added successfully");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test updating R4 UID
     */
    private function testUpdateR4UID() {
        $testName = "Update R4 Officer UID";

        try {
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);
            $users_data = json_decode(file_get_contents($this->testUsersFile), true);

            $test_alliance_idx = 0;
            $test_alliance = &$alliances_data[$test_alliance_idx];

            if (!isset($test_alliance['r4s']) || count($test_alliance['r4s']) === 0) {
                throw new Exception("No R4s to update");
            }

            // Update first R4's user_uid
            $r4_idx = 0;
            $test_user = $users_data['users'][0];
            $test_alliance['r4s'][$r4_idx]['user_uid'] = $test_user['uid'];
            $test_alliance['r4s'][$r4_idx]['email'] = $test_user['email'];

            file_put_contents($this->testAlliancesFile, json_encode($alliances_data, JSON_PRETTY_PRINT));

            // Verify update
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);
            $updated_r4 = $alliances_data[$test_alliance_idx]['r4s'][$r4_idx];

            if ($updated_r4['user_uid'] !== $test_user['uid']) {
                throw new Exception("R4 UID not updated");
            }

            $this->pass($testName, "R4 UID updated to: {$test_user['uid']}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test removing R4
     */
    private function testRemoveR4() {
        $testName = "Remove R4 Officer";

        try {
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            $test_alliance_idx = 0;
            $test_alliance = &$alliances_data[$test_alliance_idx];

            if (!isset($test_alliance['r4s']) || count($test_alliance['r4s']) === 0) {
                throw new Exception("No R4s to remove");
            }

            $initial_count = count($test_alliance['r4s']);

            // Remove last R4
            array_pop($test_alliance['r4s']);

            file_put_contents($this->testAlliancesFile, json_encode($alliances_data, JSON_PRETTY_PRINT));

            // Verify removal
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);
            $final_count = count($alliances_data[$test_alliance_idx]['r4s']);

            if ($final_count !== $initial_count - 1) {
                throw new Exception("R4 not removed");
            }

            $this->pass($testName, "R4 removed successfully (count: {$initial_count} → {$final_count})");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test R4 permissions check
     */
    private function testR4Permissions() {
        $testName = "R4 Role Permission Check";

        try {
            $users_data = json_decode(file_get_contents($this->testUsersFile), true);

            // Find user with r4 role
            $r4_user = null;
            foreach ($users_data['users'] as $user) {
                $roles = $user['roles'] ?? [];
                if (in_array('r4', $roles)) {
                    $r4_user = $user;
                    break;
                }
            }

            if ($r4_user === null) {
                throw new Exception("No user with r4 role found");
            }

            // Check role permissions
            $has_r4_role = in_array('r4', $r4_user['roles']);

            if (!$has_r4_role) {
                throw new Exception("R4 role permission check failed");
            }

            $this->pass($testName, "User {$r4_user['email']} has r4 role");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test R4 alliance access check
     */
    private function testR4AllianceAccess() {
        $testName = "R4 Alliance Access Check";

        try {
            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            // Find R4 user
            $r4_user = null;
            foreach ($users_data['users'] as $user) {
                $roles = $user['roles'] ?? [];
                if (in_array('r4', $roles)) {
                    $r4_user = $user;
                    break;
                }
            }

            if ($r4_user === null) {
                throw new Exception("No R4 user found");
            }

            // Get user's alliances
            $user_alliances = $r4_user['servers']['1586']['alliances'] ?? [];

            if (count($user_alliances) === 0 && !in_array('*', $user_alliances)) {
                throw new Exception("R4 user has no alliance access");
            }

            $alliance_list = in_array('*', $user_alliances) ? 'all alliances' : implode(', ', $user_alliances);

            $this->pass($testName, "R4 has access to: {$alliance_list}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test R4 voting rights
     */
    private function testR4VotingRights() {
        $testName = "R4 Voting Rights Check";

        try {
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            $test_alliance = $alliances_data[0];

            if (!isset($test_alliance['r4s']) || count($test_alliance['r4s']) === 0) {
                throw new Exception("No R4s to check voting rights");
            }

            $voting_r4s = array_filter($test_alliance['r4s'], fn($r4) => $r4['canVote'] === true);
            $voting_count = count($voting_r4s);

            $this->pass($testName, "{$voting_count} R4s have voting rights in {$test_alliance['tag']}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test duplicate R4 detection
     */
    private function testDuplicateR4Detection() {
        $testName = "Duplicate R4 Detection";

        try {
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            $test_alliance = $alliances_data[0];

            if (!isset($test_alliance['r4s']) || count($test_alliance['r4s']) === 0) {
                throw new Exception("No R4s to check for duplicates");
            }

            // Check for duplicate UIDs
            $uids = [];
            $duplicates = [];

            foreach ($test_alliance['r4s'] as $r4) {
                if (isset($r4['user_uid']) && $r4['user_uid'] !== null) {
                    if (in_array($r4['user_uid'], $uids)) {
                        $duplicates[] = $r4['user_uid'];
                    }
                    $uids[] = $r4['user_uid'];
                }
            }

            if (count($duplicates) > 0) {
                throw new Exception("Duplicate UIDs found: " . implode(', ', $duplicates));
            }

            $this->pass($testName, "No duplicate R4 UIDs found");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test invalid alliance tag handling
     */
    private function testInvalidAllianceTag() {
        $testName = "Invalid Alliance Tag Handling";

        try {
            $alliances_data = json_decode(file_get_contents($this->testAlliancesFile), true);

            $invalid_tag = "XXXX";
            $found = false;

            foreach ($alliances_data as $alliance) {
                if ($alliance['tag'] === $invalid_tag) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                throw new Exception("Invalid tag {$invalid_tag} should not exist");
            }

            $this->pass($testName, "Invalid alliance tag correctly not found");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Mark test as passed
     */
    private function pass($testName, $message) {
        $this->testResults[] = [
            'name' => $testName,
            'passed' => true,
            'message' => $message
        ];
        echo "[✓ PASS] {$testName}\n  └─ {$message}\n\n";
    }

    /**
     * Mark test as failed
     */
    private function fail($testName, $message) {
        $this->testResults[] = [
            'name' => $testName,
            'passed' => false,
            'message' => $message
        ];
        echo "[✗ FAIL] {$testName}\n  └─ {$message}\n\n";
    }

    /**
     * Generate test report
     */
    public function generateReport() {
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['passed']));
        $failed = $total - $passed;
        $pass_rate = $total > 0 ? ($passed / $total) * 100 : 0;

        $report = "\n=== R4 API Test Report ===\n";
        $report .= "Total Tests: {$total}\n";
        $report .= "Passed: {$passed}\n";
        $report .= "Failed: {$failed}\n";
        $report .= sprintf("Pass Rate: %.2f%%\n", $pass_rate);
        $report .= "\n";

        return $report;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $tester = new R4ApiTest();
    $results = $tester->runAllTests();
    echo $tester->generateReport();

    $failed = count(array_filter($results, fn($r) => !$r['passed']));
    exit($failed > 0 ? 1 : 0);
}
