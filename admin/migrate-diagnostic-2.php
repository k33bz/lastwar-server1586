<?php
// Phase 2: Test MigrationManager creation and version checks
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Migration Diagnostic Phase 2</title></head><body>";
echo "<h1>Migration Diagnostic - Phase 2</h1>";
echo "<p>Testing MigrationManager creation and version checks...</p><hr>";

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit_logger.php';
require_once __DIR__ . '/jwt.php';

// Get user
$user = require_jwt_session();
echo "<p>✓ User authenticated: " . htmlspecialchars($user->sub) . "</p>";

// Test role check
try {
    $has_admin = has_role($user, 'admin');
    echo "<p>✓ Role check completed: " . ($has_admin ? "User IS admin" : "User is NOT admin") . "</p>";

    if (!$has_admin) {
        echo "<p>⚠️ User does not have admin role - migrate.php would return 403</p>";
    }
} catch (Exception $e) {
    echo "<p>✗ Role check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</body></html>");
}

// Check if json_helpers.php exists (required by MigrationManager)
$json_helpers = __DIR__ . '/json_helpers.php';
if (file_exists($json_helpers)) {
    echo "<p>✓ json_helpers.php exists</p>";
    try {
        require_once $json_helpers;
        echo "<p>✓ json_helpers.php loaded</p>";
    } catch (Exception $e) {
        echo "<p>✗ json_helpers.php failed to load: " . htmlspecialchars($e->getMessage()) . "</p>";
        die("</body></html>");
    } catch (Error $e) {
        echo "<p>✗ json_helpers.php fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . htmlspecialchars($e->getLine()) . "</p>";
        die("</body></html>");
    }
} else {
    echo "<p>⚠️ json_helpers.php NOT FOUND (may not be required)</p>";
}

// Test creating MigrationManager
echo "<hr><h2>Testing MigrationManager Creation</h2>";

// First, let's check if the MigrationManager class is defined in migrate.php
$migrate_file = __DIR__ . '/migrate.php';
if (file_exists($migrate_file)) {
    echo "<p>✓ migrate.php exists</p>";

    // Read migrate.php to extract just the class definition
    $migrate_content = file_get_contents($migrate_file);

    // Find where the class starts
    if (strpos($migrate_content, 'class MigrationManager') !== false) {
        echo "<p>✓ MigrationManager class found in migrate.php</p>";

        // Try to extract and evaluate just the class
        // This is tricky because we need to avoid executing the bottom part
        // Let's try a different approach - just test the paths

        $admin_email = $user->sub;

        // Test version file path
        $version_file = dirname(__DIR__) . '/version.json';
        echo "<p>Testing version file path: " . htmlspecialchars($version_file) . "</p>";
        if (file_exists($version_file)) {
            echo "<p>✓ version.json exists</p>";
            $version_data = json_decode(file_get_contents($version_file), true);
            if ($version_data) {
                echo "<p>✓ version.json is valid JSON</p>";
                echo "<p>Current version: " . htmlspecialchars($version_data['version'] ?? 'unknown') . "</p>";
            } else {
                echo "<p>✗ version.json is invalid JSON</p>";
            }
        } else {
            echo "<p>✗ version.json NOT FOUND at: " . htmlspecialchars($version_file) . "</p>";
        }

        // Test installed version file
        $installed_file = __DIR__ . '/.installed_version';
        echo "<p>Testing installed version file: " . htmlspecialchars($installed_file) . "</p>";
        if (file_exists($installed_file)) {
            echo "<p>✓ .installed_version exists</p>";
            $installed_version = trim(file_get_contents($installed_file));
            echo "<p>Installed version: " . htmlspecialchars($installed_version) . "</p>";
        } else {
            echo "<p>⚠️ .installed_version NOT FOUND (will be created on first migration)</p>";
        }

        // Test data directory
        $data_dir = dirname(__DIR__) . '/data/';
        echo "<p>Testing data directory: " . htmlspecialchars($data_dir) . "</p>";
        if (is_dir($data_dir)) {
            echo "<p>✓ data/ directory exists</p>";
            if (is_writable($data_dir)) {
                echo "<p>✓ data/ directory is writable</p>";
            } else {
                echo "<p>✗ data/ directory is NOT writable</p>";
            }
        } else {
            echo "<p>✗ data/ directory NOT FOUND</p>";
        }

        // Test admin directory writability
        echo "<p>Testing admin directory: " . htmlspecialchars(__DIR__) . "</p>";
        if (is_writable(__DIR__)) {
            echo "<p>✓ admin/ directory is writable</p>";
        } else {
            echo "<p>✗ admin/ directory is NOT writable</p>";
        }

        // Now try to actually create the MigrationManager
        echo "<hr><h2>Attempting to Create MigrationManager Object</h2>";

        try {
            // We need to define the class first
            // Let's use a hack: extract the class definition
            $class_start = strpos($migrate_content, 'class MigrationManager');
            $class_end = strrpos($migrate_content, '}', $class_start);

            if ($class_start !== false && $class_end !== false) {
                // Extract just the class code
                $class_code = substr($migrate_content, $class_start, $class_end - $class_start + 1);

                // Evaluate it
                eval($class_code);

                echo "<p>✓ MigrationManager class definition loaded</p>";

                // Now try to instantiate it
                $manager = new MigrationManager($admin_email);
                echo "<p>✓ MigrationManager object created successfully!</p>";

                // Try to get versions
                $current = $manager->getCurrentVersion();
                $installed = $manager->getInstalledVersion();

                echo "<p>✓ getCurrentVersion() = " . htmlspecialchars($current) . "</p>";
                echo "<p>✓ getInstalledVersion() = " . htmlspecialchars($installed) . "</p>";

                $needs_upgrade = version_compare($current, $installed, '>');
                echo "<p>Needs upgrade: " . ($needs_upgrade ? 'YES' : 'NO') . "</p>";

                echo "<hr>";
                echo "<h2>✅ SUCCESS!</h2>";
                echo "<p>All MigrationManager operations work correctly.</p>";
                echo "<p><strong>This means the error in migrate.php is likely in the HTML output section, not the PHP logic.</strong></p>";

            } else {
                echo "<p>✗ Could not extract MigrationManager class definition</p>";
            }

        } catch (ParseError $e) {
            echo "<p>✗ Parse error in MigrationManager class: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . htmlspecialchars($e->getLine()) . "</p>";
        } catch (Exception $e) {
            echo "<p>✗ Exception creating MigrationManager: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . htmlspecialchars($e->getLine()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } catch (Error $e) {
            echo "<p>✗ Fatal error creating MigrationManager: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . htmlspecialchars($e->getLine()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }

    } else {
        echo "<p>✗ MigrationManager class NOT FOUND in migrate.php</p>";
    }

} else {
    echo "<p>✗ migrate.php NOT FOUND</p>";
}

echo "</body></html>";
?>
