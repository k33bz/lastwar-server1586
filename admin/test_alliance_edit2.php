<?php
/**
 * Debug script to test alliance_edit.php with tag parameter
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

$user_token = require_jwt_session();

echo "<h1>Debug Alliance Edit with Tag</h1>";
echo "<pre>";

// Test tag parameter
echo "1. Testing tag parameter...\n";
$tag = $_GET['tag'] ?? 'ORCE';
echo "   Tag: $tag\n";

// Test permission check functions
echo "\n2. Testing permission functions...\n";
echo "   User role: " . $user_token->aud . "\n";
echo "   User email: " . $user_token->email . "\n";

if (function_exists('is_r4_or_higher')) {
    $is_r4 = is_r4_or_higher($user_token);
    echo "   is_r4_or_higher(): " . ($is_r4 ? "YES" : "NO") . "\n";
} else {
    echo "   ✗ is_r4_or_higher() function not found\n";
}

if (function_exists('has_alliance_access')) {
    $has_access = has_alliance_access($user_token, $tag);
    echo "   has_alliance_access('$tag'): " . ($has_access ? "YES" : "NO") . "\n";
} else {
    echo "   ✗ has_alliance_access() function not found\n";
}

// Load alliances data
echo "\n3. Loading alliances data...\n";
try {
    $alliances_data = read_json_file(ALLIANCES_FILE);
    $alliances_array = is_array($alliances_data) && isset($alliances_data[0]) ? $alliances_data : ($alliances_data['alliances'] ?? []);
    echo "   ✓ Loaded " . count($alliances_array) . " alliances\n";

    // Find the specific alliance
    echo "\n4. Finding alliance '$tag'...\n";
    $alliance = null;
    $index = -1;

    foreach ($alliances_array as $i => $a) {
        if (strtolower($a['tag'] ?? '') === strtolower($tag)) {
            $alliance = $a;
            $index = $i;
            break;
        }
    }

    if ($alliance) {
        echo "   ✓ Found alliance at index $index\n";
        echo "   Name: " . ($alliance['name'] ?? 'N/A') . "\n";
        echo "   Rank: " . ($alliance['rank'] ?? 'N/A') . "\n";
        echo "   R5: " . (is_string($alliance['r5'] ?? '') ? $alliance['r5'] : (is_array($alliance['r5']) ? ($alliance['r5']['name'] ?? 'N/A') : 'N/A')) . "\n";
    } else {
        echo "   ✗ Alliance not found!\n";
    }

} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// Test amendments loading
echo "\n5. Testing amendments loading...\n";
try {
    $amendments_file = __DIR__ . '/../data/amendments.json';
    $amendments = file_exists($amendments_file) ? read_json_file($amendments_file) : [];
    echo "   ✓ Loaded " . count($amendments) . " amendments\n";

    // Build versions list
    $all_versions = ['1.0'];
    if (!empty($amendments)) {
        $amendment_versions = array_column($amendments, 'version');
        $all_versions = array_merge($all_versions, $amendment_versions);
        $all_versions = array_unique($all_versions);
        usort($all_versions, 'version_compare');
    }
    echo "   Available versions: " . implode(', ', $all_versions) . "\n";
    $current_rules_version = end($all_versions);
    echo "   Latest version: $current_rules_version\n";

} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test can_sign_rules function
echo "\n6. Testing can_sign_rules function...\n";
if (function_exists('can_sign_rules')) {
    try {
        $can_sign = can_sign_rules($user_token, $tag);
        echo "   can_sign_rules('$tag'): " . ($can_sign ? "YES" : "NO") . "\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ can_sign_rules() function not found\n";
}

echo "\n✓ All tests completed!\n";
echo "\nIf you see this message, the problem is NOT in the data loading.\n";
echo "The 500 error might be in the HTML rendering part of alliance_edit.php.\n";
echo "</pre>";
?>
