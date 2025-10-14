<?php
/**
 * One-time script to delete test files from production
 */

$files_to_delete = [
    __DIR__ . '/test_alliance_edit.php',
    __DIR__ . '/test_alliance_edit2.php'
];

echo "<h1>Deleting Test Files</h1>\n";
echo "<pre>\n";

foreach ($files_to_delete as $file) {
    echo "Checking: $file\n";
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "  ✓ Deleted successfully\n";
        } else {
            echo "  ✗ Failed to delete\n";
        }
    } else {
        echo "  - File does not exist (already deleted)\n";
    }
}

echo "\n✓ Cleanup complete!\n";
echo "</pre>\n";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>\n";
?>
