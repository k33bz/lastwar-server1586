<?php
/**
 * Test Runner Script
 *
 * Executes all role-based access control unit tests
 * and generates comprehensive reports
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change to tests directory
chdir(__DIR__);

// Check if RoleBasedTest.php exists
if (!file_exists('RoleBasedTest.php')) {
    die("ERROR: RoleBasedTest.php not found in current directory\n");
}

// Load test class
require_once 'RoleBasedTest.php';

// Display banner
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║          Last War 1586 Admin Panel Unit Tests               ║\n";
echo "║          Role-Based Access Control Test Suite               ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // Initialize tester
    echo "Initializing test suite...\n";
    $tester = new RoleBasedTest();

    // Run all tests
    echo "Executing tests...\n\n";
    $results = $tester->runAllTests();

    // Generate and display report
    echo "\n";
    $report = $tester->generateReport($results);
    echo $report;

    // Export results
    $tester->exportJSON('test-results.json');

    // Calculate pass/fail
    $failed = count(array_filter($results, fn($r) => !$r['passed']));
    $passed = count($results) - $failed;

    // Display summary box
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                       Test Summary                           ║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    printf("║  Total Tests:  %-44d ║\n", count($results));
    printf("║  Passed:       %-44d ║\n", $passed);
    printf("║  Failed:       %-44d ║\n", $failed);
    printf("║  Pass Rate:    %-43.2f%% ║\n", ($passed / count($results)) * 100);
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";

    // Exit with appropriate code
    if ($failed > 0) {
        echo "❌ TESTS FAILED - Fix errors and re-run\n\n";
        exit(1);
    } else {
        echo "✅ ALL TESTS PASSED\n\n";
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
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";
    exit(1);
}
?>
