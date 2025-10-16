<?php
/**
 * Alliance Helper API
 * Centralized functions for alliance operations, ranking, CSV generation, etc.
 * 
 * @version 1.0.0
 * @date 2025-10-15
 */

require_once __DIR__ . '/../json_helpers.php';

class AllianceHelper {
    
    private static $alliances_file = __DIR__ . '/../../data/alliances.json';
    private static $csv_file = __DIR__ . '/../../data/alliances.csv';
    private static $history_file = __DIR__ . '/../../data/alliance_history.json';
    
    /**
     * Load alliances data from JSON file
     */
    public static function loadAlliances() {
        $alliances_data = read_json_file(self::$alliances_file);
        return is_array($alliances_data) && isset($alliances_data[0]) ? 
               $alliances_data : 
               ($alliances_data['alliances'] ?? []);
    }
    
    /**
     * Save alliances data to JSON file
     */
    public static function saveAlliances($alliances_array) {
        return write_json_file(self::$alliances_file, $alliances_array);
    }
    
    /**
     * Calculate alliance rankings based on power (descending order)
     * Returns array with alliance tags as keys and ranks as values
     */
    public static function calculateRanks($alliances_array = null) {
        if ($alliances_array === null) {
            $alliances_array = self::loadAlliances();
        }
        
        // Sort by power descending
        $ranked_alliances = $alliances_array;
        usort($ranked_alliances, function($a, $b) {
            return ($b['power'] ?? 0) - ($a['power'] ?? 0);
        });
        
        // Create rank lookup
        $ranks = [];
        foreach ($ranked_alliances as $index => $alliance) {
            $ranks[$alliance['tag'] ?? ''] = $index + 1;
        }
        
        return $ranks;
    }
    
    /**
     * Get alliance by tag
     */
    public static function getAllianceByTag($tag, $alliances_array = null) {
        if ($alliances_array === null) {
            $alliances_array = self::loadAlliances();
        }
        
        foreach ($alliances_array as $index => $alliance) {
            if (strtolower($alliance['tag'] ?? '') === strtolower($tag)) {
                return ['alliance' => $alliance, 'index' => $index];
            }
        }
        
        return null;
    }
    
    /**
     * Update alliance power and trigger related operations
     */
    public static function updateAlliancePower($tag, $new_power, $user_info = null) {
        $alliances_array = self::loadAlliances();
        $result = self::getAllianceByTag($tag, $alliances_array);
        
        if (!$result) {
            return ['success' => false, 'error' => 'Alliance not found'];
        }
        
        $old_power = $result['alliance']['power'] ?? 0;
        $alliances_array[$result['index']]['power'] = $new_power;
        
        // Save updated alliances
        if (!self::saveAlliances($alliances_array)) {
            return ['success' => false, 'error' => 'Failed to save alliance data'];
        }
        
        // Update CSV for graphs
        self::updateAllianceCSV($alliances_array);
        
        // Record history
        self::recordPowerChange($tag, $old_power, $new_power, $user_info);
        
        // Calculate new rankings
        $new_ranks = self::calculateRanks($alliances_array);
        $new_rank = $new_ranks[$tag] ?? '?';
        
        return [
            'success' => true,
            'old_power' => $old_power,
            'new_power' => $new_power,
            'new_rank' => $new_rank,
            'power_change' => $new_power - $old_power
        ];
    }
    
    /**
     * Generate/update CSV file for alliance graphs
     */
    public static function updateAllianceCSV($alliances_array = null) {
        if ($alliances_array === null) {
            $alliances_array = self::loadAlliances();
        }
        
        // Sort by power descending for CSV
        $sorted_alliances = $alliances_array;
        usort($sorted_alliances, function($a, $b) {
            return ($b['power'] ?? 0) - ($a['power'] ?? 0);
        });
        
        $csv_content = "Rank,Tag,Power,R5\n";
        
        foreach ($sorted_alliances as $index => $alliance) {
            $rank = $index + 1;
            $tag = $alliance['tag'] ?? 'Unknown';
            $power = $alliance['power'] ?? 0;
            $r5 = is_array($alliance['r5'] ?? null) ? 
                  ($alliance['r5']['name'] ?? 'Unknown') : 
                  ($alliance['r5'] ?? 'Unknown');
            
            // Escape CSV values
            $tag = '"' . str_replace('"', '""', $tag) . '"';
            $r5 = '"' . str_replace('"', '""', $r5) . '"';
            
            $csv_content .= "{$rank},{$tag},{$power},{$r5}\n";
        }
        
        return file_put_contents(self::$csv_file, $csv_content) !== false;
    }
    
    /**
     * Record power change in history
     */
    public static function recordPowerChange($tag, $old_power, $new_power, $user_info = null) {
        $history = [];
        if (file_exists(self::$history_file)) {
            $history = read_json_file(self::$history_file) ?? [];
        }
        
        $change_record = [
            'timestamp' => date('Y-m-d H:i:s'),
            'alliance_tag' => $tag,
            'old_power' => $old_power,
            'new_power' => $new_power,
            'power_change' => $new_power - $old_power,
            'user' => $user_info ? [
                'sub' => $user_info->sub ?? null,
                'aud' => $user_info->aud ?? null
            ] : null
        ];
        
        $history[] = $change_record;
        
        // Keep only last 1000 records to prevent file from growing too large
        if (count($history) > 1000) {
            $history = array_slice($history, -1000);
        }
        
        write_json_file(self::$history_file, $history);
    }
    
    /**
     * Get alliance statistics
     */
    public static function getAllianceStats($alliances_array = null) {
        if ($alliances_array === null) {
            $alliances_array = self::loadAlliances();
        }
        
        $total_alliances = count($alliances_array);
        $total_power = array_sum(array_column($alliances_array, 'power'));
        $average_power = $total_alliances > 0 ? $total_power / $total_alliances : 0;
        
        $powers = array_column($alliances_array, 'power');
        sort($powers);
        $median_power = 0;
        if ($total_alliances > 0) {
            $middle = floor($total_alliances / 2);
            if ($total_alliances % 2 === 0) {
                $median_power = ($powers[$middle - 1] + $powers[$middle]) / 2;
            } else {
                $median_power = $powers[$middle];
            }
        }
        
        return [
            'total_alliances' => $total_alliances,
            'total_power' => $total_power,
            'average_power' => round($average_power),
            'median_power' => round($median_power),
            'highest_power' => max($powers ?: [0]),
            'lowest_power' => min($powers ?: [0])
        ];
    }
    
    /**
     * Get alliances with calculated ranks
     */
    public static function getAlliancesWithRanks($alliances_array = null) {
        if ($alliances_array === null) {
            $alliances_array = self::loadAlliances();
        }
        
        $ranks = self::calculateRanks($alliances_array);
        
        $alliances_with_ranks = [];
        foreach ($alliances_array as $alliance) {
            $alliance_with_rank = $alliance;
            $alliance_with_rank['rank'] = $ranks[$alliance['tag'] ?? ''] ?? '?';
            $alliances_with_ranks[] = $alliance_with_rank;
        }
        
        return $alliances_with_ranks;
    }
    
    /**
     * Get power change history for an alliance
     */
    public static function getAlliancePowerHistory($tag, $limit = 50) {
        if (!file_exists(self::$history_file)) {
            return [];
        }
        
        $history = read_json_file(self::$history_file) ?? [];
        
        // Filter for specific alliance
        $alliance_history = array_filter($history, function($record) use ($tag) {
            return strtolower($record['alliance_tag'] ?? '') === strtolower($tag);
        });
        
        // Sort by timestamp descending (newest first)
        usort($alliance_history, function($a, $b) {
            return strtotime($b['timestamp'] ?? '0') - strtotime($a['timestamp'] ?? '0');
        });
        
        return array_slice($alliance_history, 0, $limit);
    }
    
    /**
     * Initialize CSV file if it doesn't exist
     */
    public static function initializeCSV() {
        if (!file_exists(self::$csv_file)) {
            return self::updateAllianceCSV();
        }
        return true;
    }
    
    /**
     * Validate alliance data structure
     */
    public static function validateAllianceData($alliance_data) {
        $required_fields = ['tag', 'power'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($alliance_data[$field]) || $alliance_data[$field] === '') {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        if (isset($alliance_data['power']) && !is_numeric($alliance_data['power'])) {
            $errors[] = "Power must be a number";
        }
        
        if (isset($alliance_data['power']) && $alliance_data['power'] < 0) {
            $errors[] = "Power cannot be negative";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

// Initialize CSV on first load
AllianceHelper::initializeCSV();