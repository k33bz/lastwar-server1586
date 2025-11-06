<?php
// Diagnostic version of migrate.php to identify the exact failure point
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Migration Diagnostic</title></head><body>";
echo "<h1>Migration Diagnostic</h1>";

// Test 1: Basic PHP execution
echo "<p>✓ Step 1: PHP is executing</p>";

// Test 2: Define constant
try {
    define('ADMIN_INIT', true);
    echo "<p>✓ Step 2: ADMIN_INIT defined</p>";
} catch (Exception $e) {
    echo "<p>✗ Step 2 failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</body></html>");
}

// Test 3: Check __DIR__
echo "<p>✓ Step 3: __DIR__ = " . htmlspecialchars(__DIR__) . "</p>";

// Test 4: Check if config.php exists
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    echo "<p>✓ Step 4: config.php exists at: " . htmlspecialchars($config_file) . "</p>";
} else {
    echo "<p>✗ Step 4: config.php NOT FOUND at: " . htmlspecialchars($config_file) . "</p>";
    die("</body></html>");
}

// Test 5: Try to load config.php
try {
    echo "<p>Attempting to load config.php...</p>";
    require_once $config_file;
    echo "<p>✓ Step 5: config.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>✗ Step 5 failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</body></html>");
} catch (Error $e) {
    echo "<p>✗ Step 5 fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . htmlspecialchars($e->getLine()) . "</p>";
    die("</body></html>");
}

// Test 6: Check if audit_logger.php exists
$audit_file = __DIR__ . '/audit_logger.php';
if (file_exists($audit_file)) {
    echo "<p>✓ Step 6: audit_logger.php exists</p>";
} else {
    echo "<p>✗ Step 6: audit_logger.php NOT FOUND at: " . htmlspecialchars($audit_file) . "</p>";
    die("</body></html>");
}

// Test 7: Try to load audit_logger.php
try {
    echo "<p>Attempting to load audit_logger.php...</p>";
    require_once $audit_file;
    echo "<p>✓ Step 7: audit_logger.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>✗ Step 7 failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</body></html>");
} catch (Error $e) {
    echo "<p>✗ Step 7 fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . htmlspecialchars($e->getLine()) . "</p>";
    die("</body></html>");
}

// Test 8: Check if jwt.php exists
$jwt_file = __DIR__ . '/jwt.php';
if (file_exists($jwt_file)) {
    echo "<p>✓ Step 8: jwt.php exists</p>";
} else {
    echo "<p>✗ Step 8: jwt.php NOT FOUND at: " . htmlspecialchars($jwt_file) . "</p>";
    die("</body></html>");
}

// Test 9: Try to load jwt.php
try {
    echo "<p>Attempting to load jwt.php...</p>";
    require_once $jwt_file;
    echo "<p>✓ Step 9: jwt.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>✗ Step 9 failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</body></html>");
} catch (Error $e) {
    echo "<p>✗ Step 9 fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . htmlspecialchars($e->getLine()) . "</p>";
    die("</body></html>");
}

// Test 10: Try to get JWT session
try {
    echo "<p>Attempting to call require_jwt_session()...</p>";
    $user = require_jwt_session();
    echo "<p>✓ Step 10: JWT session obtained</p>";
    echo "<p>User email: " . htmlspecialchars($user->sub) . "</p>";
} catch (Exception $e) {
    echo "<p>✗ Step 10 failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Note: This is expected if you're not logged in</p>";
} catch (Error $e) {
    echo "<p>✗ Step 10 fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . htmlspecialchars($e->getLine()) . "</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>All basic file loading tests passed. The error in migrate.php is likely after these require statements.</p>";
echo "</body></html>";
?>
