<?php
/**
 * Admin API Helper Functions
 *
 * Multi-server support helpers for admin panel APIs
 *
 * Key differences from public API helpers:
 * - Admin panel shows ALL servers by default (no filtering)
 * - Optional ?server= parameter for filtering when needed
 * - Adds [SERVER] prefixes for display purposes
 *
 * @version 1.0.0
 * @created 2025-11-13
 * @see ../api/api_helpers.php for public API equivalents
 */

/**
 * Get server ID from query parameter (admin version)
 *
 * Returns null if not specified (admin shows all servers by default)
 *
 * @return string|null Server ID or null to show all servers
 */
function get_server_id_admin() {
    return $_GET['server'] ?? null;
}

/**
 * Optionally filter data by server (admin version)
 *
 * If $server_id is null, returns all data (admin default behavior)
 * If $server_id is specified, filters to that server only
 *
 * @param array $data Array of records with 'server' field
 * @param string|null $server_id Server to filter by, or null for all
 * @return array Filtered data
 */
function filter_by_server_admin($data, $server_id = null) {
    // If no server specified, return all data
    if ($server_id === null) {
        return $data;
    }

    // Filter by specific server
    return array_values(array_filter($data, function($record) use ($server_id) {
        // Backwards compatible: records without 'server' field always included
        if (!isset($record['server'])) {
            return true;
        }
        return $record['server'] === $server_id;
    }));
}

/**
 * Add server prefix to alliance tag for display
 *
 * Examples:
 * - add_server_prefix("ORCE", "1586") => "[1586] ORCE"
 * - add_server_prefix("BFG", "9999") => "[9999] BFG"
 *
 * @param string $tag Alliance tag
 * @param string $server Server ID
 * @return string Prefixed tag
 */
function add_server_prefix($tag, $server) {
    return "[$server] $tag";
}

/**
 * Add server prefix to alliance object for display
 *
 * Modifies the alliance object to include server-prefixed tag and name
 * for display in admin panel
 *
 * @param array $alliance Alliance object
 * @return array Modified alliance with prefixed display fields
 */
function add_alliance_server_prefix($alliance) {
    $server = $alliance['server'] ?? '1586';

    return array_merge($alliance, [
        'display_tag' => add_server_prefix($alliance['tag'], $server),
        'display_name' => "[{$server}] " . $alliance['name'],
        'server' => $server
    ]);
}

/**
 * Add server prefixes to array of alliances
 *
 * @param array $alliances Array of alliance objects
 * @return array Alliances with server prefixes added
 */
function add_alliances_server_prefixes($alliances) {
    return array_map('add_alliance_server_prefix', $alliances);
}

/**
 * Group alliances by server
 *
 * Returns associative array: ['1586' => [...], '9999' => [...]]
 *
 * @param array $alliances Array of alliance objects
 * @return array Alliances grouped by server
 */
function group_alliances_by_server($alliances) {
    $grouped = [];

    foreach ($alliances as $alliance) {
        $server = $alliance['server'] ?? '1586';

        if (!isset($grouped[$server])) {
            $grouped[$server] = [];
        }

        $grouped[$server][] = $alliance;
    }

    // Sort by server ID
    ksort($grouped);

    return $grouped;
}

/**
 * Unwrap rotation schedule for specific server (admin version)
 *
 * Same as public API version but returns null instead of error
 *
 * @param mixed $schedule_data Schedule data (new or old format)
 * @param string|null $server_id Server to unwrap for, or null for backwards compat
 * @return array|null Schedule data or null if not found
 */
function unwrap_rotation_schedule_admin($schedule_data, $server_id = null) {
    // If no server specified and old format, return as-is
    if ($server_id === null && !isset($schedule_data['server'])) {
        return $schedule_data;
    }

    // New format: { "server": "1586", "data": {...} }
    if (isset($schedule_data['server']) && isset($schedule_data['data'])) {
        if ($server_id === null || $schedule_data['server'] === $server_id) {
            return $schedule_data['data'];
        }
        return null; // Server doesn't match
    }

    // Old format (backwards compatible)
    return $schedule_data;
}

/**
 * Get list of all servers from data
 *
 * Scans data and returns unique list of server IDs
 *
 * @param array $data Array of records with 'server' field
 * @return array Array of unique server IDs (sorted)
 */
function get_all_servers($data) {
    $servers = [];

    foreach ($data as $record) {
        if (isset($record['server'])) {
            $servers[$record['server']] = true;
        }
    }

    $server_list = array_keys($servers);
    sort($server_list);

    return $server_list;
}

/**
 * Check if user has access to server
 *
 * Checks if JWT user has permissions for specified server
 *
 * @param object $user JWT user object
 * @param string $server Server ID to check
 * @return bool True if user has access
 */
function user_has_server_access($user, $server) {
    // Admin has access to all servers
    if ($user->aud === 'admin') {
        return true;
    }

    // Check if user has per-server permissions (v3.8.0+)
    if (isset($user->servers) && is_object($user->servers)) {
        $servers_array = (array)$user->servers;
        return isset($servers_array[$server]);
    }

    // Backwards compatible: default server is 1586
    return $server === '1586';
}

/**
 * Get user's alliances for specific server
 *
 * @param object $user JWT user object
 * @param string $server Server ID
 * @return array Array of alliance tags
 */
function get_user_alliances_for_server($user, $server) {
    // Check new format (v3.8.0+)
    if (isset($user->servers) && is_object($user->servers)) {
        $servers_array = (array)$user->servers;
        if (isset($servers_array[$server])) {
            $server_data = (array)$servers_array[$server];
            return $server_data['alliances'] ?? [];
        }
    }

    // Backwards compatible: old format for server 1586
    if ($server === '1586' && isset($user->alliances)) {
        return is_array($user->alliances) ? $user->alliances : (array)$user->alliances;
    }

    return [];
}
?>
