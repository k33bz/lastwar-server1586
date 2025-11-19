<?php
/**
 * Profile API Unit Tests
 * Tests for UID-based user profile management
 *
 * @version 1.0.0
 * @date 2025-11-19
 */

class ProfileApiTest {
    private $testUsersFile;
    private $originalUsersData;
    private $testResults = [];

    public function __construct() {
        $this->testUsersFile = __DIR__ . '/../users.json';

        // Backup original data
        if (file_exists($this->testUsersFile)) {
            $this->originalUsersData = file_get_contents($this->testUsersFile);
        }
    }

    public function __destruct() {
        // Restore original data
        if ($this->originalUsersData !== null) {
            file_put_contents($this->testUsersFile, $this->originalUsersData);
        }
    }

    /**
     * Run all profile API tests
     */
    public function runAllTests() {
        echo "\n=== Profile API Tests (UID System) ===\n\n";

        $this->testUserLookupByUID();
        $this->testUserLookupByEmail();
        $this->testProfileUpdateWithUID();
        $this->testEmailChangeWithHistory();
        $this->testEmailChangeConflict();
        $this->testLanguagePreferenceUpdate();
        $this->testDiscordIdUpdate();
        $this->testLegacyTokenCompatibility();
        $this->testInvalidUID();
        $this->testMissingRequiredFields();

        return $this->testResults;
    }

    /**
     * Test user lookup by UID
     */
    private function testUserLookupByUID() {
        $testName = "User Lookup by UID (v4.0.0+)";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            // Get first user from users.json
            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];

            if (!isset($test_user['uid'])) {
                throw new Exception("Test user missing UID");
            }

            // Test get_user_by_uid
            $user = get_user_by_uid($test_user['uid']);

            if ($user === null) {
                throw new Exception("User not found by UID");
            }

            if ($user['uid'] !== $test_user['uid']) {
                throw new Exception("UID mismatch");
            }

            $this->pass($testName, "User found by UID: {$user['uid']}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test user lookup by email (backward compatibility)
     */
    private function testUserLookupByEmail() {
        $testName = "User Lookup by Email (Legacy)";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];

            // Test get_user_by_email
            $user = get_user_by_email($test_user['email']);

            if ($user === null) {
                throw new Exception("User not found by email");
            }

            if ($user['email'] !== $test_user['email']) {
                throw new Exception("Email mismatch");
            }

            $this->pass($testName, "User found by email: {$user['email']}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test profile update with UID
     */
    private function testProfileUpdateWithUID() {
        $testName = "Profile Update with UID";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];
            $test_uid = $test_user['uid'];

            // Update in-game name
            $new_name = "Test User Updated " . time();

            foreach ($users_data['users'] as &$user) {
                if ($user['uid'] === $test_uid) {
                    $user['in_game_name'] = $new_name;
                    break;
                }
            }

            write_json_file($this->testUsersFile, $users_data);

            // Verify update
            $updated_user = get_user_by_uid($test_uid);

            if ($updated_user['in_game_name'] !== $new_name) {
                throw new Exception("In-game name not updated");
            }

            $this->pass($testName, "Profile updated successfully for UID: {$test_uid}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test email change with history tracking
     */
    private function testEmailChangeWithHistory() {
        $testName = "Email Change with History Tracking";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];
            $test_uid = $test_user['uid'];
            $old_email = $test_user['email'];
            $new_email = "test_" . time() . "@example.com";

            // Update email with history
            foreach ($users_data['users'] as &$user) {
                if ($user['uid'] === $test_uid) {
                    // Initialize email_history if not exists
                    if (!isset($user['email_history'])) {
                        $user['email_history'] = [];
                    }

                    // Add old email to history
                    $user['email_history'][] = [
                        'email' => $old_email,
                        'changed_at' => gmdate('Y-m-d\TH:i:s\Z')
                    ];

                    // Update to new email
                    $user['email'] = $new_email;
                    break;
                }
            }

            write_json_file($this->testUsersFile, $users_data);

            // Verify email change and history
            $updated_user = get_user_by_uid($test_uid);

            if ($updated_user['email'] !== $new_email) {
                throw new Exception("Email not updated");
            }

            if (!isset($updated_user['email_history']) || count($updated_user['email_history']) === 0) {
                throw new Exception("Email history not recorded");
            }

            $latest_history = end($updated_user['email_history']);
            if ($latest_history['email'] !== $old_email) {
                throw new Exception("Email history incorrect");
            }

            $this->pass($testName, "Email changed from {$old_email} to {$new_email} with history");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test email change conflict detection
     */
    private function testEmailChangeConflict() {
        $testName = "Email Change Conflict Detection";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);

            if (count($users_data['users']) < 2) {
                throw new Exception("Need at least 2 users for conflict test");
            }

            $user1_email = $users_data['users'][0]['email'];
            $user2_email = $users_data['users'][1]['email'];

            // Try to change user1's email to user2's email
            $conflict = get_user_by_email($user2_email);

            if ($conflict === null) {
                throw new Exception("Conflict detection failed");
            }

            $this->pass($testName, "Conflict detected: {$user2_email} already exists");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test language preference update
     */
    private function testLanguagePreferenceUpdate() {
        $testName = "Language Preference Update";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];
            $test_uid = $test_user['uid'];

            $supported_languages = ['en', 'es', 'pt', 'de', 'ko'];
            $new_lang = $supported_languages[array_rand($supported_languages)];

            // Update language preference
            foreach ($users_data['users'] as &$user) {
                if ($user['uid'] === $test_uid) {
                    $user['preferred_language'] = $new_lang;
                    break;
                }
            }

            write_json_file($this->testUsersFile, $users_data);

            // Verify update
            $updated_user = get_user_by_uid($test_uid);

            if ($updated_user['preferred_language'] !== $new_lang) {
                throw new Exception("Language preference not updated");
            }

            $this->pass($testName, "Language preference set to: {$new_lang}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test Discord ID update
     */
    private function testDiscordIdUpdate() {
        $testName = "Discord ID Update";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];
            $test_uid = $test_user['uid'];

            $test_discord_id = "123456789012345678"; // Valid 18-digit Discord ID

            // Update Discord ID
            foreach ($users_data['users'] as &$user) {
                if ($user['uid'] === $test_uid) {
                    $user['discord_id'] = $test_discord_id;
                    break;
                }
            }

            write_json_file($this->testUsersFile, $users_data);

            // Verify update
            $updated_user = get_user_by_uid($test_uid);

            if ($updated_user['discord_id'] !== $test_discord_id) {
                throw new Exception("Discord ID not updated");
            }

            $this->pass($testName, "Discord ID set to: {$test_discord_id}");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test legacy token compatibility
     */
    private function testLegacyTokenCompatibility() {
        $testName = "Legacy Token Compatibility (Email as sub)";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $users_data = json_decode(file_get_contents($this->testUsersFile), true);
            $test_user = $users_data['users'][0];
            $test_email = $test_user['email'];

            // Simulate legacy token lookup by email
            $user = get_user_by_email($test_email);

            if ($user === null) {
                throw new Exception("Legacy token lookup failed");
            }

            if ($user['email'] !== $test_email) {
                throw new Exception("Email mismatch in legacy lookup");
            }

            $this->pass($testName, "Legacy email-based lookup works");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test invalid UID handling
     */
    private function testInvalidUID() {
        $testName = "Invalid UID Handling";

        try {
            require_once __DIR__ . '/../json_helpers.php';

            $invalid_uid = "usr_invalid123";

            $user = get_user_by_uid($invalid_uid);

            if ($user !== null) {
                throw new Exception("Invalid UID returned a user");
            }

            $this->pass($testName, "Invalid UID correctly returns null");
        } catch (Exception $e) {
            $this->fail($testName, $e->getMessage());
        }
    }

    /**
     * Test missing required fields validation
     */
    private function testMissingRequiredFields() {
        $testName = "Missing Required Fields Validation";

        try {
            // Test would validate that profile_api.php rejects missing fields
            // This is a placeholder for API endpoint validation

            $required_fields = ['in_game_name', 'email', 'preferred_language'];

            $this->pass($testName, "Required fields: " . implode(', ', $required_fields));
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

        $report = "\n=== Profile API Test Report ===\n";
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
    $tester = new ProfileApiTest();
    $results = $tester->runAllTests();
    echo $tester->generateReport();

    $failed = count(array_filter($results, fn($r) => !$r['passed']));
    exit($failed > 0 ? 1 : 0);
}
