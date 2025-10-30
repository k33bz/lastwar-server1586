<?php
/**
 * Rate Limiter
 *
 * Simple file-based rate limiting to prevent brute force attacks and DDoS
 *
 * Documentation:
 * - Security Issue: https://github.com/k33bz/lastwar-server1586/issues/35
 *
 * @version 1.0.0
 * @date 2025-10-29
 *
 * Usage:
 *   require_once __DIR__ . '/includes/rate_limiter.php';
 *   rate_limit_check('login', 5, 60); // 5 attempts per 60 seconds
 */

// Rate limit storage file
const RATE_LIMIT_FILE = __DIR__ . '/../rate_limits.json';

/**
 * Check if request should be rate limited
 *
 * @param string $action Action identifier (e.g., 'login', 'api')
 * @param int $max_requests Maximum requests allowed
 * @param int $time_window Time window in seconds
 * @return void Exits with 429 if rate limit exceeded
 */
function rate_limit_check(string $action, int $max_requests, int $time_window): void {
    $ip = get_client_ip();
    $key = $action . '_' . $ip;
    $now = time();

    // Load existing rate limits
    $limits = load_rate_limits();

    // Clean up old entries (older than time window)
    $limits = array_filter($limits, function($entry) use ($now, $time_window) {
        return ($now - $entry['first_request']) < $time_window;
    });

    // Check current request count
    if (isset($limits[$key])) {
        $entry = $limits[$key];

        // If within time window, check if limit exceeded
        if (($now - $entry['first_request']) < $time_window) {
            if ($entry['count'] >= $max_requests) {
                // Rate limit exceeded
                $retry_after = $time_window - ($now - $entry['first_request']);
                rate_limit_response($retry_after);
            }

            // Increment count
            $limits[$key]['count']++;
            $limits[$key]['last_request'] = $now;
        } else {
            // Time window expired, reset
            $limits[$key] = [
                'first_request' => $now,
                'last_request' => $now,
                'count' => 1,
                'ip' => $ip,
                'action' => $action
            ];
        }
    } else {
        // First request for this key
        $limits[$key] = [
            'first_request' => $now,
            'last_request' => $now,
            'count' => 1,
            'ip' => $ip,
            'action' => $action
        ];
    }

    // Save updated limits
    save_rate_limits($limits);
}

/**
 * Get client IP address (considers proxies)
 *
 * @return string Client IP address
 */
function get_client_ip(): string {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0'; // Fallback
}

/**
 * Load rate limits from file
 *
 * @return array Rate limits data
 */
function load_rate_limits(): array {
    if (!file_exists(RATE_LIMIT_FILE)) {
        return [];
    }

    $content = file_get_contents(RATE_LIMIT_FILE);
    if ($content === false) {
        return [];
    }

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Save rate limits to file
 *
 * @param array $limits Rate limits data
 * @return void
 */
function save_rate_limits(array $limits): void {
    $json = json_encode($limits, JSON_PRETTY_PRINT);
    file_put_contents(RATE_LIMIT_FILE, $json, LOCK_EX);

    // Set restrictive permissions
    @chmod(RATE_LIMIT_FILE, 0600);
}

/**
 * Send rate limit exceeded response
 *
 * @param int $retry_after Seconds until rate limit resets
 * @return void
 */
function rate_limit_response(int $retry_after): void {
    http_response_code(429);
    header('Retry-After: ' . $retry_after);
    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'error' => 'Rate limit exceeded',
        'message' => 'Too many requests. Please try again in ' . $retry_after . ' seconds.',
        'retry_after' => $retry_after
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

    // Log rate limit violation
    error_log("Rate limit exceeded: " . get_client_ip());

    exit;
}

/**
 * Clean up old rate limit entries (maintenance function)
 *
 * @param int $older_than Remove entries older than this many seconds (default: 1 hour)
 * @return int Number of entries removed
 */
function rate_limit_cleanup(int $older_than = 3600): int {
    $limits = load_rate_limits();
    $now = time();
    $removed = 0;

    $limits = array_filter($limits, function($entry) use ($now, $older_than, &$removed) {
        $keep = ($now - $entry['first_request']) < $older_than;
        if (!$keep) {
            $removed++;
        }
        return $keep;
    });

    save_rate_limits($limits);
    return $removed;
}
