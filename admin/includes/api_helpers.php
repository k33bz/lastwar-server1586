<?php
/**
 * API Response Helpers
 *
 * Standardized API response functions for consistent JSON responses
 *
 * @version 1.0.0
 * @date 2025-10-16
 * @changelog
 *   1.0.0 (2025-10-16) - Initial implementation
 */

/**
 * Send a successful JSON response
 *
 * @param mixed $data Data to return
 * @param string $message Optional success message
 * @param int $http_code HTTP status code (default: 200)
 * @return void
 */
function apiSuccess($data = null, $message = null, $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json');

    $response = ['success' => true];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send an error JSON response
 *
 * @param string $error Error message
 * @param int $http_code HTTP status code (default: 400)
 * @param mixed $details Optional error details
 * @return void
 */
function apiError($error, $http_code = 400, $details = null) {
    http_response_code($http_code);
    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'error' => $error
    ];

    if ($details !== null) {
        $response['details'] = $details;
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send a validation error response with field-specific errors
 *
 * @param array $errors Array of field => error message
 * @param string $message Optional general validation message
 * @return void
 */
function apiValidationError($errors, $message = 'Validation failed') {
    apiError($message, 422, ['validation_errors' => $errors]);
}

/**
 * Send an unauthorized error response
 *
 * @param string $message Optional custom message
 * @return void
 */
function apiUnauthorized($message = 'Unauthorized') {
    apiError($message, 401);
}

/**
 * Send a forbidden error response
 *
 * @param string $message Optional custom message
 * @return void
 */
function apiForbidden($message = 'Forbidden') {
    apiError($message, 403);
}

/**
 * Send a not found error response
 *
 * @param string $message Optional custom message
 * @return void
 */
function apiNotFound($message = 'Not found') {
    apiError($message, 404);
}

/**
 * Send a server error response
 *
 * @param string $message Optional custom message
 * @param Exception $exception Optional exception for logging
 * @return void
 */
function apiServerError($message = 'Internal server error', $exception = null) {
    if ($exception) {
        error_log("API Server Error: " . $exception->getMessage());
        error_log("Stack trace: " . $exception->getTraceAsString());
    }

    // In production, don't reveal exception details
    if (($_ENV['APP_ENV'] ?? 'production') === 'development' && $exception) {
        apiError($message, 500, [
            'exception_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    } else {
        apiError($message, 500);
    }
}

/**
 * Require a specific HTTP method
 *
 * @param string|array $method Required HTTP method(s)
 * @return void
 */
function requireMethod($method) {
    $methods = is_array($method) ? $method : [$method];
    $current_method = $_SERVER['REQUEST_METHOD'];

    if (!in_array($current_method, $methods)) {
        apiError('Method not allowed. Expected: ' . implode(', ', $methods), 405);
    }
}

/**
 * Require POST method
 *
 * @return void
 */
function requirePost() {
    requireMethod('POST');
}

/**
 * Require GET method
 *
 * @return void
 */
function requireGet() {
    requireMethod('GET');
}

/**
 * Get JSON input from request body
 *
 * @param bool $associative Return as associative array (default: true)
 * @return array|object|null
 */
function getJsonInput($associative = true) {
    $input = file_get_contents('php://input');

    if (empty($input)) {
        return null;
    }

    $data = json_decode($input, $associative);

    if (json_last_error() !== JSON_ERROR_NONE) {
        apiError('Invalid JSON: ' . json_last_error_msg(), 400);
    }

    return $data;
}

/**
 * Validate required fields in request data
 *
 * @param array $data Request data
 * @param array $required_fields Required field names
 * @return bool
 */
function validateRequired($data, $required_fields) {
    $errors = [];

    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[$field] = "The {$field} field is required";
        }
    }

    if (!empty($errors)) {
        apiValidationError($errors);
    }

    return true;
}

/**
 * Sanitize input string
 *
 * @param string $input Input to sanitize
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 *
 * @param string $email Email to validate
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get request parameter with default value
 *
 * @param string $key Parameter name
 * @param mixed $default Default value if not set
 * @param string $method Request method ('GET', 'POST', or 'REQUEST')
 * @return mixed
 */
function getParam($key, $default = null, $method = 'REQUEST') {
    switch (strtoupper($method)) {
        case 'GET':
            return $_GET[$key] ?? $default;
        case 'POST':
            return $_POST[$key] ?? $default;
        case 'REQUEST':
        default:
            return $_REQUEST[$key] ?? $default;
    }
}

/**
 * Get integer parameter with validation
 *
 * @param string $key Parameter name
 * @param int $default Default value
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return int
 */
function getIntParam($key, $default = 0, $min = null, $max = null) {
    $value = getParam($key, $default);
    $int_value = filter_var($value, FILTER_VALIDATE_INT);

    if ($int_value === false) {
        return $default;
    }

    if ($min !== null && $int_value < $min) {
        return $min;
    }

    if ($max !== null && $int_value > $max) {
        return $max;
    }

    return $int_value;
}

/**
 * Set CORS headers for API
 *
 * @param array $allowed_origins Allowed origins (default: current host only)
 * @param array $allowed_methods Allowed HTTP methods
 * @return void
 */
function setCorsHeaders($allowed_origins = null, $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']) {
    if ($allowed_origins === null) {
        $allowed_origins = [$_SERVER['HTTP_HOST'] ?? '*'];
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array('*', $allowed_origins) || in_array($origin, $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowed_methods));
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
    }

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Rate limit API requests
 *
 * @param string $identifier Unique identifier (e.g., IP, user ID)
 * @param int $max_requests Maximum requests allowed
 * @param int $time_window Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit($identifier, $max_requests = 60, $time_window = 60) {
    $cache_file = __DIR__ . '/../cache/rate_limit_' . md5($identifier) . '.json';
    $cache_dir = dirname($cache_file);

    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }

    $now = time();
    $data = ['requests' => [], 'window_start' => $now];

    if (file_exists($cache_file)) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if ($cached && ($now - $cached['window_start']) < $time_window) {
            $data = $cached;
        }
    }

    // Clean old requests
    $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });

    // Check limit
    if (count($data['requests']) >= $max_requests) {
        $retry_after = $time_window - ($now - min($data['requests']));
        header('X-RateLimit-Limit: ' . $max_requests);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . ($now + $retry_after));
        header('Retry-After: ' . $retry_after);
        apiError('Rate limit exceeded. Try again in ' . $retry_after . ' seconds.', 429);
        return false;
    }

    // Add current request
    $data['requests'][] = $now;
    file_put_contents($cache_file, json_encode($data));

    // Set rate limit headers
    header('X-RateLimit-Limit: ' . $max_requests);
    header('X-RateLimit-Remaining: ' . ($max_requests - count($data['requests'])));
    header('X-RateLimit-Reset: ' . ($data['window_start'] + $time_window));

    return true;
}

/**
 * Paginate array data
 *
 * @param array $data Data to paginate
 * @param int $page Current page (1-indexed)
 * @param int $per_page Items per page
 * @return array Paginated result with metadata
 */
function paginate($data, $page = 1, $per_page = 20) {
    $total = count($data);
    $total_pages = ceil($total / $per_page);
    $page = max(1, min($page, $total_pages ?: 1));

    $offset = ($page - 1) * $per_page;
    $items = array_slice($data, $offset, $per_page);

    return [
        'items' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total_items' => $total,
            'total_pages' => $total_pages,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        ]
    ];
}

/**
 * Log API request for debugging
 *
 * @param string $endpoint Endpoint name
 * @param array $data Request data
 * @return void
 */
function logApiRequest($endpoint, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    ];

    $log_file = __DIR__ . '/../logs/api_requests.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    file_put_contents(
        $log_file,
        json_encode($log_entry) . PHP_EOL,
        FILE_APPEND
    );
}
?>
