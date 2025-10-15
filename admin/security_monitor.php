<?php
/**
 * Advanced Security Monitoring System
 *
 * Provides rate limiting, IP blocking, suspicious activity detection,
 * and real-time security monitoring
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';
require_once __DIR__ . '/audit_logger.php';

// Security monitoring configuration
define('SECURITY_LOG_FILE', __DIR__ . '/security_events.json');
define('IP_BLACKLIST_FILE', __DIR__ . '/ip_blacklist.json');
define('RATE_LIMIT_FILE', __DIR__ . '/rate_limits.json');

// Rate limiting thresholds
define('MAGIC_LINK_RATE_LIMIT', 3);     // 3 requests per 15 minutes
define('MAGIC_LINK_RATE_WINDOW', 900);  // 15 minutes
define('LOGIN_ATTEMPT_LIMIT', 5);       // 5 failed attempts per hour
define('LOGIN_ATTEMPT_WINDOW', 3600);   // 1 hour
define('API_RATE_LIMIT', 60);           // 60 requests per minute
define('API_RATE_WINDOW', 60);          // 1 minute

/**
 * Initialize security files
 */
function initialize_security_files() {
    $files = [
        SECURITY_LOG_FILE => ['events' => []],
        IP_BLACKLIST_FILE => ['ips' => [], 'auto_blocks' => []],
        RATE_LIMIT_FILE => ['limits' => []]
    ];
    
    foreach ($files as $file => $initial_data) {
        if (!file_exists($file)) {
            write_json_file($file, $initial_data);
            if (function_exists('chmod')) {
                @chmod($file, 0600);
            }
        }
    }
}

/**
 * Get client IP address with proxy support
 *
 * @return string Client IP address
 */
function get_real_client_ip() {
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_CLIENT_IP',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Check if IP is blacklisted
 *
 * @param string $ip IP address to check
 * @return bool True if IP is blacklisted
 */
function is_ip_blacklisted($ip) {
    try {
        initialize_security_files();
        $blacklist = read_json_file(IP_BLACKLIST_FILE);
        
        // Check permanent blacklist
        if (in_array($ip, $blacklist['ips'])) {
            return true;
        }
        
        // Check auto-blocks (temporary)
        foreach ($blacklist['auto_blocks'] as $block) {
            if ($block['ip'] === $ip && $block['expires'] > time()) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Failed to check IP blacklist: " . $e->getMessage());
        return false;
    }
}

/**
 * Add IP to blacklist
 *
 * @param string $ip IP address
 * @param string $reason Reason for blocking
 * @param int $duration Duration in seconds (0 = permanent)
 * @return bool Success status
 */
function blacklist_ip($ip, $reason, $duration = 0) {
    try {
        initialize_security_files();
        
        return update_json_file(IP_BLACKLIST_FILE, function(&$data) use ($ip, $reason, $duration) {
            if ($duration === 0) {
                // Permanent block
                if (!in_array($ip, $data['ips'])) {
                    $data['ips'][] = $ip;
                }
            } else {
                // Temporary block
                $data['auto_blocks'][] = [
                    'ip' => $ip,
                    'reason' => $reason,
                    'blocked_at' => time(),
                    'expires' => time() + $duration
                ];
            }
            
            return true;
        });
    } catch (Exception $e) {
        error_log("Failed to blacklist IP $ip: " . $e->getMessage());
        return false;
    }
}

/**
 * Check rate limit for IP and action
 *
 * @param string $ip IP address
 * @param string $action Action type (magic_link, login_attempt, api_call)
 * @param int $limit Request limit
 * @param int $window Time window in seconds
 * @return bool True if within rate limit
 */
function check_rate_limit($ip, $action, $limit, $window) {
    try {
        initialize_security_files();
        
        $key = $ip . '_' . $action;
        $now = time();
        
        return update_json_file(RATE_LIMIT_FILE, function(&$data) use ($key, $now, $limit, $window) {
            // Clean up expired entries
            if (isset($data['limits'][$key])) {
                $data['limits'][$key] = array_filter($data['limits'][$key], function($timestamp) use ($now, $window) {
                    return $timestamp > ($now - $window);
                });
            } else {
                $data['limits'][$key] = [];
            }
            
            // Check if limit exceeded
            if (count($data['limits'][$key]) >= $limit) {
                return false; // Rate limit exceeded
            }
            
            // Add current request
            $data['limits'][$key][] = $now;
            return true; // Within rate limit
        });
    } catch (Exception $e) {
        error_log("Rate limit check failed for $ip/$action: " . $e->getMessage());
        return true; // Allow on error
    }
}

/**
 * Log security event
 *
 * @param string $event_type Type of security event
 * @param string $ip IP address
 * @param array $details Event details
 * @return bool Success status
 */
function log_security_event($event_type, $ip, $details = []) {
    try {
        initialize_security_files();
        
        $event = [
            'id' => uniqid('sec_', true),
            'timestamp' => time(),
            'event_type' => $event_type,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'details' => $details
        ];
        
        return update_json_file(SECURITY_LOG_FILE, function(&$data) use ($event) {
            array_unshift($data['events'], $event);
            
            // Keep only last 10,000 events
            if (count($data['events']) > 10000) {
                $data['events'] = array_slice($data['events'], 0, 10000);
            }
            
            return true;
        });
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
        return false;
    }
}

/**
 * Detect suspicious activity patterns
 *
 * @param string $ip IP address
 * @return array Detected threats
 */
function detect_suspicious_activity($ip) {
    try {
        initialize_security_files();
        $security_log = read_json_file(SECURITY_LOG_FILE);
        $threats = [];
        $now = time();
        
        // Get recent events for this IP (last hour)
        $recent_events = array_filter($security_log['events'], function($event) use ($ip, $now) {
            return $event['ip'] === $ip && ($now - $event['timestamp']) < 3600;
        });
        
        // Count event types
        $event_counts = [];
        foreach ($recent_events as $event) {
            $event_counts[$event['event_type']] = ($event_counts[$event['event_type']] ?? 0) + 1;
        }
        
        // Detect patterns
        if (($event_counts['failed_login'] ?? 0) >= 10) {
            $threats[] = 'brute_force_login';
        }
        
        if (($event_counts['rate_limit_exceeded'] ?? 0) >= 5) {
            $threats[] = 'rate_limit_abuse';
        }
        
        if (($event_counts['invalid_token'] ?? 0) >= 20) {
            $threats[] = 'token_enumeration';
        }
        
        if (count($recent_events) >= 100) {
            $threats[] = 'high_volume_requests';
        }
        
        return $threats;
    } catch (Exception $e) {
        error_log("Threat detection failed for $ip: " . $e->getMessage());
        return [];
    }
}

/**
 * Auto-block IP based on threat detection
 *
 * @param string $ip IP address
 * @param array $threats Detected threats
 * @return bool True if IP was blocked
 */
function auto_block_threats($ip, $threats) {
    if (empty($threats)) {
        return false;
    }
    
    $block_durations = [
        'brute_force_login' => 3600,      // 1 hour
        'rate_limit_abuse' => 1800,       // 30 minutes
        'token_enumeration' => 7200,      // 2 hours
        'high_volume_requests' => 900     // 15 minutes
    ];
    
    $max_duration = 0;
    $reasons = [];
    
    foreach ($threats as $threat) {
        if (isset($block_durations[$threat])) {
            $max_duration = max($max_duration, $block_durations[$threat]);
            $reasons[] = $threat;
        }
    }
    
    if ($max_duration > 0) {
        $reason = 'Auto-blocked: ' . implode(', ', $reasons);
        blacklist_ip($ip, $reason, $max_duration);
        
        // Log the auto-block
        log_security_event('auto_ip_block', $ip, [
            'threats' => $threats,
            'duration' => $max_duration,
            'reason' => $reason
        ]);
        
        return true;
    }
    
    return false;
}

/**
 * Security middleware for protecting endpoints
 *
 * @param string $action Action type for rate limiting
 * @return bool True if request should be allowed
 */
function security_check($action = 'general') {
    $ip = get_real_client_ip();
    
    // Check IP blacklist
    if (is_ip_blacklisted($ip)) {
        log_security_event('blocked_ip_access', $ip, ['action' => $action]);
        http_response_code(403);
        die('Access denied');
    }
    
    // Rate limiting based on action
    $rate_limits = [
        'magic_link' => [MAGIC_LINK_RATE_LIMIT, MAGIC_LINK_RATE_WINDOW],
        'login_attempt' => [LOGIN_ATTEMPT_LIMIT, LOGIN_ATTEMPT_WINDOW],
        'api_call' => [API_RATE_LIMIT, API_RATE_WINDOW],
        'general' => [100, 300] // 100 requests per 5 minutes
    ];
    
    $limits = $rate_limits[$action] ?? $rate_limits['general'];
    
    if (!check_rate_limit($ip, $action, $limits[0], $limits[1])) {
        log_security_event('rate_limit_exceeded', $ip, [
            'action' => $action,
            'limit' => $limits[0],
            'window' => $limits[1]
        ]);
        
        // Check for auto-block
        $threats = detect_suspicious_activity($ip);
        if (auto_block_threats($ip, $threats)) {
            http_response_code(403);
            die('IP temporarily blocked due to suspicious activity');
        }
        
        http_response_code(429);
        die('Rate limit exceeded. Please try again later.');
    }
    
    return true;
}

/**
 * Get security statistics
 *
 * @return array Security metrics
 */
function get_security_stats() {
    try {
        initialize_security_files();
        
        $security_log = read_json_file(SECURITY_LOG_FILE);
        $blacklist = read_json_file(IP_BLACKLIST_FILE);
        $rate_limits = read_json_file(RATE_LIMIT_FILE);
        
        $now = time();
        $hour_ago = $now - 3600;
        $day_ago = $now - 86400;
        
        // Count recent events
        $recent_events = array_filter($security_log['events'], function($event) use ($hour_ago) {
            return $event['timestamp'] > $hour_ago;
        });
        
        $daily_events = array_filter($security_log['events'], function($event) use ($day_ago) {
            return $event['timestamp'] > $day_ago;
        });
        
        // Count active blocks
        $active_blocks = array_filter($blacklist['auto_blocks'], function($block) use ($now) {
            return $block['expires'] > $now;
        });
        
        return [
            'events_last_hour' => count($recent_events),
            'events_last_24h' => count($daily_events),
            'permanent_blocks' => count($blacklist['ips']),
            'active_temp_blocks' => count($active_blocks),
            'total_rate_limit_keys' => count($rate_limits['limits']),
            'last_updated' => $now
        ];
    } catch (Exception $e) {
        error_log("Failed to get security stats: " . $e->getMessage());
        return [
            'events_last_hour' => 0,
            'events_last_24h' => 0,
            'permanent_blocks' => 0,
            'active_temp_blocks' => 0,
            'total_rate_limit_keys' => 0,
            'last_updated' => time(),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Clean up expired security data
 *
 * @return array Cleanup statistics
 */
function cleanup_security_data() {
    $cleaned = ['rate_limits' => 0, 'temp_blocks' => 0, 'old_events' => 0];
    $now = time();
    
    try {
        // Clean up expired rate limits
        update_json_file(RATE_LIMIT_FILE, function(&$data) use (&$cleaned, $now) {
            foreach ($data['limits'] as $key => &$timestamps) {
                $original_count = count($timestamps);
                $timestamps = array_filter($timestamps, function($timestamp) use ($now) {
                    return $timestamp > ($now - 3600); // Keep last hour
                });
                $cleaned['rate_limits'] += $original_count - count($timestamps);
                
                // Remove empty keys
                if (empty($timestamps)) {
                    unset($data['limits'][$key]);
                }
            }
            return true;
        });
        
        // Clean up expired IP blocks
        update_json_file(IP_BLACKLIST_FILE, function(&$data) use (&$cleaned, $now) {
            $original_count = count($data['auto_blocks']);
            $data['auto_blocks'] = array_filter($data['auto_blocks'], function($block) use ($now) {
                return $block['expires'] > $now;
            });
            $cleaned['temp_blocks'] = $original_count - count($data['auto_blocks']);
            return true;
        });
        
        // Clean up old security events (keep last 30 days)
        update_json_file(SECURITY_LOG_FILE, function(&$data) use (&$cleaned, $now) {
            $original_count = count($data['events']);
            $cutoff = $now - (30 * 86400); // 30 days
            $data['events'] = array_filter($data['events'], function($event) use ($cutoff) {
                return $event['timestamp'] > $cutoff;
            });
            $cleaned['old_events'] = $original_count - count($data['events']);
            return true;
        });
        
    } catch (Exception $e) {
        error_log("Security cleanup failed: " . $e->getMessage());
    }
    
    return $cleaned;
}
?>