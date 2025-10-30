<?php
/**
 * API Rate Limit Middleware
 *
 * Include this at the top of admin API endpoints for automatic rate limiting
 *
 * Documentation:
 * - Security Issue: https://github.com/k33bz/lastwar-server1586/issues/35
 *
 * @version 1.0.0
 * @date 2025-10-29
 *
 * Usage:
 *   require_once __DIR__ . '/includes/api_rate_limit.php';
 */

require_once __DIR__ . '/rate_limiter.php';

// Apply rate limiting: 20 requests per minute for API endpoints
rate_limit_check('api', 20, 60);
