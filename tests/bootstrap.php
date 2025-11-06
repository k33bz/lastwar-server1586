<?php
/**
 * PHPUnit Bootstrap File
 * Initializes testing environment and loads dependencies
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('ADMIN_PATH', BASE_PATH . '/admin');
define('TESTS_PATH', BASE_PATH . '/tests');

// Load environment variables if .env file exists
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Set default environment variables for testing
if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'testing';
}

if (!isset($_ENV['APP_NAME'])) {
    $_ENV['APP_NAME'] = 'Server 1586 Test';
}

// Create temp directory for test files if it doesn't exist
$tempDir = sys_get_temp_dir() . '/server1586_tests';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Clean up old test files from previous runs
$testFiles = glob($tempDir . '/test_*');
foreach ($testFiles as $file) {
    if (is_file($file) && (time() - filemtime($file)) > 3600) { // Older than 1 hour
        @unlink($file);
    }
}

// Autoload helper function for test dependencies
spl_autoload_register(function ($class) {
    // Convert class name to file path
    $file = ADMIN_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "PHPUnit Bootstrap: Testing environment initialized\n";
echo "Base Path: " . BASE_PATH . "\n";
echo "Temp Directory: " . $tempDir . "\n";
