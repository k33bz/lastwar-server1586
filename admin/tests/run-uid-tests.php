<?php
/**
 * UID System Test Runner
 * Runs all UID-related API tests
 *
 * @version 1.0.0
 * @date 2025-11-19
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to tests directory
chdir(__DIR__);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║          UID System API Unit Tests (v4.0.0)                 ║\n";
echo "║          Profile API & R4 Management Tests                  ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    $all_results = [];

    // Run Profile API tests
    echo "Loading Profile API tests...\n";
    require_once 'ProfileApiTest.php';
    $profile_tester = new ProfileApiTest();
    $profile_results = $profile_tester->runAllTests();
    echo $profile_tester->generateReport();
    $all_results = array_merge($all_results, $profile_results);

    // Run R4 API tests
    echo "\nLoading R4 API tests...\n";
    require_once 'R4ApiTest.php';
    $r4_tester = new R4ApiTest();
    $r4_results = $r4_tester->runAllTests();
    echo $r4_tester->generateReport();
    $all_results = array_merge($all_results, $r4_results);

    // Calculate overall summary
    $total = count($all_results);
    $passed = count(array_filter($all_results, fn($r) => $r['passed']));
    $failed = $total - $passed;
    $pass_rate = $total > 0 ? ($passed / $total) * 100 : 0;

    // Display overall summary
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                  Overall Test Summary                        ║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    printf("║  Total Tests:  %-44d ║\n", $total);
    printf("║  Passed:       %-44d ║\n", $passed);
    printf("║  Failed:       %-44d ║\n", $failed);
    printf("║  Pass Rate:    %-43.2f%% ║\n", $pass_rate);
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";

    // Exit with appropriate code
    if ($failed > 0) {
        echo "❌ SOME TESTS FAILED\n\n";
        exit(1);
    } else {
        echo "✅ ALL UID SYSTEM TESTS PASSED\n\n";
        exit(0);
    }

} catch (Exception $e) {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                       FATAL ERROR                            ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    exit(1);
}
