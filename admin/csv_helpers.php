<?php
/**
 * CSV Helpers for Power History Tracking
 *
 * Functions to update power-history.csv with datetime stamps
 *
 * @version 1.2.0
 * @date 2025-10-27
 * @changelog
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

// Path to power history CSV
define('POWER_HISTORY_CSV', __DIR__ . '/../data/power-history.csv');

/**
 * Append power snapshot to CSV
 *
 * @param array $alliances Array of alliance data from alliances.json
 * @return bool Success status
 */
function append_power_snapshot($alliances) {
    try {
        $csv_path = POWER_HISTORY_CSV;

        // Read existing CSV to get alliance order from header
        if (!file_exists($csv_path)) {
            throw new Exception("CSV file not found: $csv_path");
        }

        $file = fopen($csv_path, 'r');
        if (!$file) {
            throw new Exception("Failed to open CSV file");
        }

        // Read header row
        $header = fgetcsv($file);
        fclose($file);

        if (!$header || ($header[0] !== 'datetime' && $header[0] !== 'date')) {
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
        $datetime = gmdate('Y-m-d H:i:s');  // Format: 2025-10-27 14:30:00
        $row = [$datetime];

        // Add power values in the order of CSV header
        foreach ($alliance_tags as $tag) {
            $row[] = $power_map[$tag] ?? 0;  // Default to 0 if alliance not found
        }

        // Append row to CSV
        $file = fopen($csv_path, 'a');
        if (!$file) {
            throw new Exception("Failed to open CSV file for writing");
        }

        // Use file locking to prevent concurrent writes
        if (flock($file, LOCK_EX)) {
            fputcsv($file, $row);
            flock($file, LOCK_UN);
            fclose($file);
            return true;
        } else {
            fclose($file);
            throw new Exception("Failed to acquire file lock");
        }

    } catch (Exception $e) {
        error_log("Failed to append power snapshot: " . $e->getMessage());
        return false;
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
        error_log("Failed to get latest power snapshots: " . $e->getMessage());
        return [];
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
        error_log("Failed to update CSV header: " . $e->getMessage());
        return false;
    }
}
?>
