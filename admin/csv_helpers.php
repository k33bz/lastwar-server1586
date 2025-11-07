<?php
/**
 * CSV Helpers for Power History Tracking
 *
 * Functions to update power-history.csv with datetime stamps
 *
 * @version 1.3.0
 * @date 2025-10-28
 * @changelog
 *   1.3.0 (2025-10-28) - Added support for custom timestamp parameter
 *                       - append_power_snapshot() now accepts optional timestamp
 *                       - Enables accurate historical data entry (Issue #32)
 *   1.2.1 (2025-10-28) - Deployment fix: Force re-upload to production
 *   1.2.0 (2025-10-27) - Updated to ISO 8601 datetime format (YYYY-MM-DD HH:mm:ss)
 *                       - Better sorting and international standard compliance
 *   1.1.0 (2025-10-27) - Updated to M/d/yyyy HH:mm datetime format
 *                       - Deduplication: keeps latest entry per day
 *                       - Changed header from 'date' to 'datetime'
 *   1.0.0 (2025-10-15) - Initial implementation
 *                       - DateTime stamps for tracking multiple edits per day
 *                       - Support for -1 (deleted) and 0 (display as empty)
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}

require_once __DIR__ . '/audit_logger.php';

// Path to power history CSV
define('POWER_HISTORY_CSV', __DIR__ . '/../data/power-history.csv');

/**
 * Append power snapshot to CSV
 *
 * @param array $alliances Array of alliance data from alliances.json
 * @param string|null $timestamp Optional ISO 8601 timestamp (e.g., 2025-10-28T14:30:00.000Z)
 *                                If not provided, uses current UTC time
 * @param bool $overwrite_duplicates If true, replaces existing row with same datetime
 * @param string $user_email User performing operation (for audit log)
 * @return array Result with success status and duplicate info
 */
function append_power_snapshot($alliances, $timestamp = null, $overwrite_duplicates = false, $user_email = 'system') {
    try {
        $csv_path = POWER_HISTORY_CSV;

        // Read existing CSV to get alliance order from header
        if (!file_exists($csv_path)) {
            throw new Exception("CSV file not found: $csv_path");
        }

        // Acquire exclusive lock
        $lock_file = $csv_path . '.lock';
        $lock_handle = fopen($lock_file, 'w');
        if (!$lock_handle || !flock($lock_handle, LOCK_EX)) {
            throw new Exception("Failed to acquire CSV lock");
        }

        $file = fopen($csv_path, 'r');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Failed to open CSV file");
        }

        // Read header row and all data
        $header = fgetcsv($file);
        $all_data = [];
        while (($row = fgetcsv($file)) !== false) {
            $all_data[] = $row;
        }
        fclose($file);

        if (!$header || ($header[0] !== 'datetime' && $header[0] !== 'date')) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Invalid CSV format: missing or incorrect header");
        }

        // Remove 'datetime' column from header to get alliance tags in order
        array_shift($header);
        $alliance_tags = $header;

        // Create power map from current alliances data
        $power_map = [];
        foreach ($alliances as $alliance) {
            $tag = $alliance['tag'] ?? '';
            $power = $alliance['power'] ?? 0;
            $power_map[$tag] = $power;
        }

        // Build new row with datetime timestamp in ISO 8601 format
        if ($timestamp) {
            // Convert ISO 8601 timestamp to Y-m-d H:i:s format
            $dt = new DateTime($timestamp);
            $datetime = $dt->format('Y-m-d H:i:s');
        } else {
            // Use current UTC time
            $datetime = gmdate('Y-m-d H:i:s');  // Format: 2025-10-27 14:30:00
        }
        $row = [$datetime];

        // Add power values in the order of CSV header
        foreach ($alliance_tags as $tag) {
            $row[] = $power_map[$tag] ?? 0;  // Default to 0 if alliance not found
        }

        // Check for duplicate datetime
        $duplicate_index = -1;
        foreach ($all_data as $index => $existing_row) {
            if ($existing_row[0] === $datetime) {
                $duplicate_index = $index;
                break;
            }
        }

        $result = ['success' => false, 'duplicate' => false, 'merged' => false];

        if ($duplicate_index !== -1) {
            if ($overwrite_duplicates) {
                // Replace existing row with new data (merge: take non-zero values)
                $existing = $all_data[$duplicate_index];
                $merged_row = [$datetime];
                for ($i = 1; $i < count($row); $i++) {
                    $new_val = (int)($row[$i] ?? 0);
                    $old_val = (int)($existing[$i] ?? 0);
                    // Take new value if it's non-zero, otherwise keep old
                    $merged_row[] = $new_val > 0 ? $new_val : $old_val;
                }
                $all_data[$duplicate_index] = $merged_row;
                $result['merged'] = true;
                $result['success'] = true;

                // Log merge operation
                log_csv_merge($user_email, $datetime, true);
            } else {
                // Return duplicate flag without writing
                flock($lock_handle, LOCK_UN);
                fclose($lock_handle);
                @unlink($lock_file);

                // Log duplicate detection
                log_csv_merge($user_email, $datetime, false);

                return [
                    'success' => false,
                    'duplicate' => true,
                    'datetime' => $datetime,
                    'message' => "Duplicate datetime found: {$datetime}. Set overwrite_duplicates=true to merge."
                ];
            }
        } else {
            // No duplicate, append new row
            $all_data[] = $row;
            $result['success'] = true;
        }

        // Write updated CSV
        $file = fopen($csv_path, 'w');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            throw new Exception("Failed to open CSV file for writing");
        }

        fputcsv($file, array_merge(['datetime'], $alliance_tags));
        foreach ($all_data as $data_row) {
            fputcsv($file, $data_row);
        }
        fclose($file);

        // Release lock
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
        @unlink($lock_file);

        // Log successful snapshot append (not merge, which is logged separately)
        if ($result['success'] && !$result['merged']) {
            log_csv_operation('append', $user_email, [
                'datetime' => $datetime,
                'alliances_count' => count($alliances),
                'timestamp_provided' => $timestamp ? 'custom' : 'current'
            ]);
        }

        return $result;

    } catch (Exception $e) {
        error_log("CSV Helper Error [append_power_snapshot]: " . $e->getMessage() . " | File: " . ($csv_path ?? 'unknown'));
        log_csv_error('append', $user_email, $e->getMessage(), [
            'file' => basename($csv_path ?? 'unknown'),
            'alliances_count' => count($alliances ?? [])
        ]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get latest power snapshot for a specific date
 *
 * Returns only the most recent datetime entry for each date
 *
 * @param int $limit Number of days to return (default: 30)
 * @return array Array of power snapshots
 */
function get_latest_power_snapshots($limit = 30) {
    try {
        $csv_path = POWER_HISTORY_CSV;

        if (!file_exists($csv_path)) {
            return [];
        }

        $file = fopen($csv_path, 'r');
        if (!$file) {
            return [];
        }

        // Read header
        $header = fgetcsv($file);
        if (!$header) {
            fclose($file);
            return [];
        }

        // Read all data rows
        $all_rows = [];
        while (($row = fgetcsv($file)) !== false) {
            $all_rows[] = $row;
        }
        fclose($file);

        // Group by date (extract date from datetime)
        $by_date = [];
        foreach ($all_rows as $row) {
            if (empty($row[0])) continue;

            $datetime = $row[0];
            // Extract date portion from "YYYY-MM-DD HH:mm:ss" format
            $date = explode(' ', $datetime)[0];

            // Keep only the latest datetime for each date
            if (!isset($by_date[$date]) || $datetime > $by_date[$date]['datetime']) {
                $by_date[$date] = [
                    'datetime' => $datetime,
                    'data' => array_combine($header, $row)
                ];
            }
        }

        // Sort by date descending and limit
        krsort($by_date);
        $by_date = array_slice($by_date, 0, $limit);

        // Extract data arrays
        $result = [];
        foreach ($by_date as $date => $entry) {
            $result[] = $entry['data'];
        }

        return $result;

    } catch (Exception $e) {
        error_log("CSV Helper Error [get_latest_power_snapshots]: " . $e->getMessage() . " | Limit: " . $limit);
        return [];
    }
}

/**
 * Sync CSV with alliances.json - add missing alliance columns
 *
 * Ensures all alliances in alliances.json have corresponding columns in CSV
 * Never removes columns (no deletions), only adds missing ones
 *
 * @param array $alliances Current alliance data from alliances.json
 * @param string $user_email User performing operation (for audit log)
 * @return array Result with success status and stats
 */
function sync_csv_with_alliances($alliances, $user_email = 'system') {
    try {
        $csv_path = POWER_HISTORY_CSV;

        if (!file_exists($csv_path)) {
            throw new Exception("CSV file not found");
        }

        // Get alliance tags from alliances.json
        $json_tags = [];
        foreach ($alliances as $alliance) {
            $json_tags[] = $alliance['tag'] ?? '';
        }

        // Acquire exclusive lock on CSV file
        $lock_file = $csv_path . '.lock';
        $lock_handle = fopen($lock_file, 'w');
        if (!$lock_handle || !flock($lock_handle, LOCK_EX)) {
            throw new Exception("Failed to acquire CSV lock");
        }

        // Read existing CSV
        $file = fopen($csv_path, 'r');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Failed to open CSV file");
        }

        $csv_header = fgetcsv($file);
        $csv_data = [];
        while (($row = fgetcsv($file)) !== false) {
            $csv_data[] = $row;
        }
        fclose($file);

        // Get current CSV tags (skip 'datetime' column)
        $csv_tags = array_slice($csv_header, 1);

        // Find missing tags (in JSON but not in CSV)
        $missing_tags = array_diff($json_tags, $csv_tags);

        if (empty($missing_tags)) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            return ['success' => true, 'added' => 0, 'message' => 'CSV already in sync'];
        }

        // Add missing tags to header
        $new_header = array_merge($csv_header, array_values($missing_tags));

        // Extend all data rows with zeros for new columns
        $new_data = [];
        foreach ($csv_data as $row) {
            $new_row = array_merge($row, array_fill(0, count($missing_tags), 0));
            $new_data[] = $new_row;
        }

        // Write updated CSV
        $file = fopen($csv_path, 'w');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Failed to open CSV for writing");
        }

        fputcsv($file, $new_header);
        foreach ($new_data as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Release lock
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
        @unlink($lock_file);

        // Log sync operation
        log_csv_sync($user_email, count($missing_tags), array_values($missing_tags));

        return [
            'success' => true,
            'added' => count($missing_tags),
            'tags' => array_values($missing_tags),
            'message' => 'Added ' . count($missing_tags) . ' missing alliance(s) to CSV'
        ];

    } catch (Exception $e) {
        error_log("CSV Helper Error [sync_csv_with_alliances]: " . $e->getMessage() . " | Alliances: " . count($alliances ?? []));
        log_csv_error('sync', $user_email, $e->getMessage(), [
            'alliances_count' => count($alliances ?? [])
        ]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Sort CSV rows by date and power
 *
 * Sorts: latest date first, then by total power descending for columns
 * Within same date, sorts alliance columns alphabetically
 *
 * @param string $user_email User performing operation (for audit log)
 * @return bool Success status
 */
function sort_csv_rows($user_email = 'system') {
    try {
        $csv_path = POWER_HISTORY_CSV;

        if (!file_exists($csv_path)) {
            throw new Exception("CSV file not found");
        }

        // Acquire exclusive lock
        $lock_file = $csv_path . '.lock';
        $lock_handle = fopen($lock_file, 'w');
        if (!$lock_handle || !flock($lock_handle, LOCK_EX)) {
            throw new Exception("Failed to acquire CSV lock");
        }

        // Read CSV
        $file = fopen($csv_path, 'r');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Failed to open CSV file");
        }

        $header = fgetcsv($file);
        $data = [];
        while (($row = fgetcsv($file)) !== false) {
            $data[] = $row;
        }
        fclose($file);

        // Sort data rows by datetime descending (latest first)
        usort($data, function($a, $b) {
            return strcmp($b[0], $a[0]); // Reverse compare for descending
        });

        // Calculate total power for each alliance column (for column sorting)
        $alliance_totals = [];
        $num_cols = count($header);
        for ($col = 1; $col < $num_cols; $col++) {
            $total = 0;
            foreach ($data as $row) {
                $total += (int)($row[$col] ?? 0);
            }
            $alliance_totals[$col] = $total;
        }

        // Sort alliance columns by total power descending, then alphabetically
        $col_order = range(1, $num_cols - 1); // Skip datetime column
        usort($col_order, function($a, $b) use ($alliance_totals, $header) {
            $power_diff = $alliance_totals[$b] - $alliance_totals[$a];
            if ($power_diff != 0) {
                return $power_diff;
            }
            // If power is equal, sort alphabetically by tag
            return strcmp($header[$a], $header[$b]);
        });

        // Rebuild header with new column order
        $new_header = ['datetime'];
        foreach ($col_order as $col) {
            $new_header[] = $header[$col];
        }

        // Rebuild data rows with new column order
        $new_data = [];
        foreach ($data as $row) {
            $new_row = [$row[0]]; // Keep datetime
            foreach ($col_order as $col) {
                $new_row[] = $row[$col] ?? 0;
            }
            $new_data[] = $new_row;
        }

        // Write sorted CSV
        $file = fopen($csv_path, 'w');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Failed to open CSV for writing");
        }

        fputcsv($file, $new_header);
        foreach ($new_data as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Release lock
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
        @unlink($lock_file);

        // Log sort operation
        log_csv_sort($user_email, count($new_data), count($col_order));

        return true;

    } catch (Exception $e) {
        error_log("CSV Helper Error [sort_csv_rows]: " . $e->getMessage());
        log_csv_error('sort', $user_email, $e->getMessage(), []);
        return false;
    }
}

/**
 * Check if duplicate date exists and handle merge/overwrite
 *
 * @param string $datetime Datetime string to check
 * @param array $new_data New power data to add
 * @return array Result with action taken
 */
function handle_duplicate_date($datetime, $new_data) {
    try {
        $csv_path = POWER_HISTORY_CSV;

        if (!file_exists($csv_path)) {
            return ['exists' => false];
        }

        // Acquire exclusive lock
        $lock_file = $csv_path . '.lock';
        $lock_handle = fopen($lock_file, 'w');
        if (!$lock_handle || !flock($lock_handle, LOCK_EX)) {
            throw new Exception("Failed to acquire CSV lock");
        }

        // Read CSV
        $file = fopen($csv_path, 'r');
        if (!$file) {
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            @unlink($lock_file);
            throw new Exception("Failed to open CSV file");
        }

        $header = fgetcsv($file);
        $data = [];
        $duplicate_index = -1;
        $row_index = 0;

        while (($row = fgetcsv($file)) !== false) {
            if ($row[0] === $datetime) {
                $duplicate_index = $row_index;
            }
            $data[] = $row;
            $row_index++;
        }
        fclose($file);

        // Release lock
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
        @unlink($lock_file);

        if ($duplicate_index === -1) {
            return ['exists' => false];
        }

        // Return existing data for comparison/merge decision
        return [
            'exists' => true,
            'index' => $duplicate_index,
            'existing_data' => $data[$duplicate_index],
            'datetime' => $datetime
        ];

    } catch (Exception $e) {
        error_log("CSV Helper Error [handle_duplicate_date]: " . $e->getMessage() . " | Datetime: " . ($datetime ?? 'unknown'));
        return ['exists' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update CSV header when alliance tags change
 *
 * Call this when alliances are added or removed
 *
 * @param array $alliances Current alliance data
 * @return bool Success status
 */
function update_csv_header($alliances) {
    try {
        $csv_path = POWER_HISTORY_CSV;

        if (!file_exists($csv_path)) {
            throw new Exception("CSV file not found");
        }

        // Get current alliance tags in order
        $new_tags = [];
        foreach ($alliances as $alliance) {
            $new_tags[] = $alliance['tag'] ?? '';
        }

        // Read existing CSV
        $file = fopen($csv_path, 'r');
        if (!$file) {
            throw new Exception("Failed to open CSV file");
        }

        $old_header = fgetcsv($file);
        $old_data = [];
        while (($row = fgetcsv($file)) !== false) {
            $old_data[] = $row;
        }
        fclose($file);

        // Build new header
        $new_header = array_merge(['datetime'], $new_tags);

        // Map old columns to new columns
        $old_tags = array_slice($old_header, 1);  // Remove 'datetime'/'date'
        $column_map = [];
        foreach ($old_tags as $index => $tag) {
            $new_index = array_search($tag, $new_tags);
            if ($new_index !== false) {
                $column_map[$index] = $new_index;
            }
        }

        // Rebuild data rows
        $new_data = [];
        foreach ($old_data as $row) {
            $datetime = $row[0];
            $new_row = array_fill(0, count($new_tags) + 1, 0);
            $new_row[0] = $datetime;

            // Map old values to new positions
            foreach ($column_map as $old_index => $new_index) {
                $new_row[$new_index + 1] = $row[$old_index + 1] ?? 0;
            }

            $new_data[] = $new_row;
        }

        // Write new CSV
        $file = fopen($csv_path, 'w');
        if (!$file) {
            throw new Exception("Failed to open CSV file for writing");
        }

        if (flock($file, LOCK_EX)) {
            fputcsv($file, $new_header);
            foreach ($new_data as $row) {
                fputcsv($file, $row);
            }
            flock($file, LOCK_UN);
            fclose($file);
            return true;
        } else {
            fclose($file);
            throw new Exception("Failed to acquire file lock");
        }

    } catch (Exception $e) {
        error_log("CSV Helper Error [update_csv_header]: " . $e->getMessage() . " | Alliances: " . count($alliances ?? []));
        return false;
    }
}

/**
 * Clean up stray lock files
 *
 * Removes any orphaned .lock files that may have been left behind
 * Call this periodically or when encountering lock acquisition failures
 *
 * @param string $user_email User or system performing cleanup (for audit log)
 * @return array Result with count of removed lock files
 */
function cleanup_csv_lock_files($user_email = 'system') {
    try {
        $data_dir = dirname(POWER_HISTORY_CSV);
        $lock_files = glob($data_dir . '/*.lock');
        $removed = 0;

        foreach ($lock_files as $lock_file) {
            // Check if lock file is stale (older than 5 minutes)
            if (file_exists($lock_file) && (time() - filemtime($lock_file)) > 300) {
                if (@unlink($lock_file)) {
                    $removed++;
                    error_log("CSV Helper: Cleaned up stale lock file: " . basename($lock_file));
                }
            }
        }

        // Log cleanup operation
        log_csv_cleanup($user_email, $removed);

        return [
            'success' => true,
            'removed' => $removed,
            'message' => "Cleaned up {$removed} stale lock file(s)"
        ];

    } catch (Exception $e) {
        error_log("CSV Helper Error [cleanup_csv_lock_files]: " . $e->getMessage());
        log_csv_error('cleanup', $user_email, $e->getMessage(), []);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
