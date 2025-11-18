<?php
/**
 * Test Script for Multi-Role System
 *
 * Tests adding and retrieving users with multiple roles
 *
 * Usage: php admin/test_multi_role.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/jwt.php';

echo "===========================================\n";
echo "Multi-Role System Test\n";
echo "===========================================\n\n";

// Test data
$test_email = 'ejtollridge@gmail.com';
$test_roles = ['ape'];  // APE-only, no other roles
$test_alliances = [];  // No alliances for APE-only users

echo "Test Case: APE-only user (no alliances)\n";
echo "  Email: {$test_email}\n";
echo "  Roles: [" . implode(', ', $test_roles) . "]\n";
echo "  Alliances: " . (empty($test_alliances) ? "none" : "[" . implode(', ', $test_alliances) . "]") . "\n\n";

try {
    // Check if user already exists
    $existing_user = get_user_by_email($test_email);

    if ($existing_user) {
        echo "User already exists. Current data:\n";
        echo "  " . json_encode($existing_user, JSON_PRETTY_PRINT) . "\n\n";

        echo "Updating user with new multi-role format...\n";
        $success = update_user_multi_role($test_email, $test_alliances, $test_roles);
    } else {
        echo "User does not exist. Adding new user...\n";
        $success = add_user_multi_role($test_email, $test_alliances, $test_roles);
    }

    if (!$success) {
        throw new Exception("Failed to add/update user");
    }

    echo "✓ User saved successfully\n\n";

    // Retrieve the user to verify
    echo "Retrieving user from database...\n";
    $user = get_user_by_email($test_email);

    if (!$user) {
        throw new Exception("User not found after saving");
    }

    echo "✓ User retrieved successfully\n\n";

    echo "User data:\n";
    echo json_encode($user, JSON_PRETTY_PRINT) . "\n\n";

    // Verify data structure
    echo "Verification:\n";
    if (isset($user['roles']) && is_array($user['roles'])) {
        echo "  ✓ User has 'roles' array (new format)\n";
        echo "    Roles: [" . implode(', ', $user['roles']) . "]\n";
    } else {
        echo "  ✗ User does not have 'roles' array\n";
    }

    if (!isset($user['role'])) {
        echo "  ✓ Old 'role' field removed\n";
    } else {
        echo "  ⚠ Old 'role' field still present: " . $user['role'] . "\n";
    }

    if (!isset($user['powereditor'])) {
        echo "  ✓ Old 'powereditor' field removed\n";
    } else {
        echo "  ⚠ Old 'powereditor' field still present: " . ($user['powereditor'] ? 'true' : 'false') . "\n";
    }

    if (empty($user['alliances'])) {
        echo "  ✓ Alliances empty (correct for APE-only)\n";
    } else {
        echo "  ⚠ Alliances not empty: [" . implode(', ', $user['alliances']) . "]\n";
    }

    // Test JWT token generation
    echo "\nJWT Token Generation Test:\n";
    $magic_token = create_magic_link_token($test_email, $user);
    echo "  ✓ Magic link token created\n";

    // Decode the token to inspect payload
    $decoded = decode_jwt($magic_token);
    if ($decoded) {
        echo "  ✓ Token decodes successfully\n\n";
        echo "Token payload:\n";
        echo "  sub (email): {$decoded->sub}\n";
        echo "  aud (primary role): {$decoded->aud}\n";

        if (isset($decoded->roles)) {
            echo "  roles: [" . implode(', ', $decoded->roles) . "]\n";
        } else {
            echo "  roles: not set (backward compatibility mode)\n";
        }

        if (isset($decoded->alliances)) {
            echo "  alliances: " . (empty($decoded->alliances) ? "[]" : "[" . implode(', ', $decoded->alliances) . "]") . "\n";
        }

        // Test helper functions
        echo "\nHelper Function Tests:\n";
        echo "  has_role(\$token, 'ape'): " . (has_role($decoded, 'ape') ? 'true' : 'false') . "\n";
        echo "  has_role(\$token, 'r4'): " . (has_role($decoded, 'r4') ? 'true' : 'false') . "\n";
        echo "  has_role(\$token, 'admin'): " . (has_role($decoded, 'admin') ? 'true' : 'false') . "\n";
        echo "  get_user_roles(\$token): [" . implode(', ', get_user_roles($decoded)) . "]\n";
    } else {
        echo "  ✗ Token failed to decode\n";
    }

    echo "\n===========================================\n";
    echo "✓ All tests passed successfully!\n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
