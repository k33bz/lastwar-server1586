<?php
/**
 * Public API Helper Functions
 *
 * Shared utilities for read-only public API endpoints
 * Provides caching, CORS, error handling, and JSON response formatting
 *
 * Documentation:
 * - API Design: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/PUBLIC_API.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-29
 */

/**
 * Set standard API response headers
 *
 * @param int $cache_seconds Cache duration in seconds (default: 60)
 * @param bool $enable_cors Enable CORS headers (default: true)
 */
function set_api_headers($cache_seconds = 60, $enable_cors = true) {
    // Content type
    header('Content-Type: application/json; charset=utf-8');

    // CORS headers (allow public access)
    if ($enable_cors) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }

    // Cache control (public data, cacheable)
    header('Cache-Control: public, max-age=' . $cache_seconds);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_seconds) . ' GMT');

    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
}

/**
 * Read JSON file safely with error handling
 *
 * @param string $file_path Path to JSON file
 * @return array|null Decoded JSON data or null on error
 */
function read_json_safe($file_path) {
    if (!file_exists($file_path)) {
        return null;
    }

    $content = file_get_contents($file_path);
    if ($content === false) {
        return null;
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in $file_path: " . json_last_error_msg());
        return null;
    }

    return $data;
}

/**
 * Send JSON success response
 *
 * @param mixed $data Data to return
 * @param int $cache_seconds Cache duration
 */
function api_success($data, $cache_seconds = 60) {
    set_api_headers($cache_seconds);

    // Add metadata
    $response = [
        'success' => true,
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'data' => $data
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send JSON error response
 *
 * @param string $message Error message
 * @param int $http_code HTTP status code
 */
function api_error($message, $http_code = 500) {
    http_response_code($http_code);
    set_api_headers(0); // Don't cache errors

    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Generate ETag for content-based caching
 *
 * @param mixed $data Data to hash
 * @return string ETag value
 */
function generate_etag($data) {
    return '"' . md5(json_encode($data)) . '"';
}

/**
 * Check if client has valid cached version (ETag matching)
 *
 * @param string $etag Current ETag
 * @return bool True if client cache is valid
 */
function check_client_cache($etag) {
    $client_etag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

    if ($client_etag === $etag) {
        header('HTTP/1.1 304 Not Modified');
        header('ETag: ' . $etag);
        exit;
    }

    return false;
}

/**
 * Send response with ETag support
 *
 * @param mixed $data Data to return
 * @param int $cache_seconds Cache duration
 */
function api_success_with_etag($data, $cache_seconds = 60) {
    $etag = generate_etag($data);

    // Check if client has cached version
    check_client_cache($etag);

    // Set ETag header
    header('ETag: ' . $etag);

    // Send response
    api_success($data, $cache_seconds);
}

/**
 * Handle OPTIONS preflight request for CORS
 */
function handle_preflight() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        set_api_headers(3600); // Cache preflight for 1 hour
        exit;
    }
}

/**
 * Validate request method
 *
 * @param string $expected_method Expected HTTP method (GET, POST, etc.)
 */
function validate_method($expected_method = 'GET') {
    if ($_SERVER['REQUEST_METHOD'] !== $expected_method) {
        api_error('Method not allowed. Expected ' . $expected_method, 405);
    }
}

/**
 * Strip PII (Personally Identifiable Information) from alliance data
 *
 * Removes sensitive information before sending to public API:
 * - Discord IDs (user IDs, channel IDs, server IDs)
 * - Email addresses
 * - Discord usernames (except public display names)
 * - Private Discord invite URLs
 * - Recruitment contact information
 *
 * @param array $alliances Alliance data array
 * @return array Sanitized alliance data
 */
function strip_alliance_pii($alliances) {
    $sanitized = [];

    foreach ($alliances as $alliance) {
        $sanitized_alliance = [
            'tag' => $alliance['tag'] ?? '',
            'name' => $alliance['name'] ?? '',
            'power' => $alliance['power'] ?? 0,
            'signed' => $alliance['signed'] ?? false,
            'rank' => $alliance['rank'] ?? null,
        ];

        // R5 info - only include public display name, strip Discord IDs
        if (isset($alliance['r5'])) {
            $sanitized_alliance['r5'] = [
                'name' => $alliance['r5']['name'] ?? null,
                // discordId REMOVED - PII
                // gameId REMOVED - may contain PII
            ];
        }

        // Discord info - keep public invite URLs, strip server IDs
        if (isset($alliance['discord'])) {
            $sanitized_alliance['discord'] = [
                'serverName' => $alliance['discord']['serverName'] ?? null,
                'logoUrl' => $alliance['discord']['logoUrl'] ?? null,
                'inviteUrl' => $alliance['discord']['inviteUrl'] ?? null, // Public invite link - OK to share
                'memberCount' => $alliance['discord']['memberCount'] ?? null,
            ];
        }

        // Cross-server info - tag names only, no server IDs
        if (isset($alliance['crossServer'])) {
            $sanitized_alliance['crossServer'] = [
                'hasPartner' => $alliance['crossServer']['hasPartner'] ?? false,
                'partnerTags' => $alliance['crossServer']['partnerTags'] ?? [],
                // servers REMOVED - contains server IDs
                // network REMOVED - may contain Discord channel IDs
            ];
        }

        // Alliance info - recruitment requirements, languages, etc.
        if (isset($alliance['info'])) {
            $sanitized_alliance['info'] = $alliance['info'];
        }

        // Contact info - COMPLETELY REMOVED (contains emails, Discord usernames, channel IDs)
        // $alliance['contact'] NOT included

        // Achievements - safe to include
        if (isset($alliance['achievements'])) {
            $sanitized_alliance['achievements'] = $alliance['achievements'];
        }

        $sanitized[] = $sanitized_alliance;
    }

    return $sanitized;
}

/**
 * Strip PII from server info data
 *
 * @param array $server_info Server info data
 * @return array Sanitized server info
 */
function strip_server_info_pii($server_info) {
    $sanitized = $server_info;

    // Remove Discord server ID and channel IDs, but KEEP public invite URL
    if (isset($sanitized['discord'])) {
        unset($sanitized['discord']['serverId']); // Discord server ID is PII
        // inviteUrl is PUBLIC and intentionally shared - keep it
        unset($sanitized['discord']['channelIds']); // Channel IDs are PII
    }

    // Remove admin contact information
    if (isset($sanitized['contact'])) {
        unset($sanitized['contact']); // May contain emails, Discord usernames
    }

    return $sanitized;
}
