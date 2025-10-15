<?php
// Test loading dependencies one by one
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];

// Test 1: Check if files exist
$results['files_exist'] = [
    'config.php' => file_exists(__DIR__ . '/config.php'),
    'jwt.php' => file_exists(__DIR__ . '/jwt.php'),
    'json_helpers.php' => file_exists(__DIR__ . '/json_helpers.php'),
    'alliances.json' => file_exists(__DIR__ . '/../data/alliances.json')
];

// Test 2: Try loading config.php
try {
    require_once __DIR__ . '/config.php';
    $results['config_loaded'] = true;
} catch (Exception $e) {
    $results['config_loaded'] = false;
    $results['config_error'] = $e->getMessage();
}

// Test 3: Try loading jwt.php
try {
    require_once __DIR__ . '/jwt.php';
    $results['jwt_loaded'] = true;
} catch (Exception $e) {
    $results['jwt_loaded'] = false;
    $results['jwt_error'] = $e->getMessage();
}

// Test 4: Try loading json_helpers.php
try {
    require_once __DIR__ . '/json_helpers.php';
    $results['json_helpers_loaded'] = true;
    $results['json_read_exists'] = function_exists('json_read');
    $results['json_write_exists'] = function_exists('json_write');
} catch (Exception $e) {
    $results['json_helpers_loaded'] = false;
    $results['json_helpers_error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
