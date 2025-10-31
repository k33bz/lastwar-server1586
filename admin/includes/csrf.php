<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection
 *
 * Provides token generation, validation, and helper functions for protecting
 * against CSRF attacks on state-changing operations (POST, PUT, DELETE).
 *
 * @version 1.0.0
 * @date 2025-10-28
 * @changelog
 *   1.0.0 (2025-10-28) - Initial implementation for issue #21
 */

/**
 * Generate a CSRF token
 *
 * Creates a cryptographically secure random token and stores it in the session.
 *
 * @return string The generated CSRF token
 */
function generateCsrfToken() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Generate token if one doesn't exist
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get the current CSRF token
 *
 * Returns the existing token from session or generates a new one.
 *
 * @return string The current CSRF token
 */
function getCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        return generateCsrfToken();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 *
 * Checks if the provided token matches the session token.
 * Supports token from:
 * - POST data (_csrf_token)
 * - Header (X-CSRF-Token)
 * - JSON body (csrf_token)
 *
 * @param string|null $provided_token Optional token to validate (if null, checks request)
 * @return bool True if token is valid
 */
function validateCsrfToken($provided_token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get session token
    $session_token = $_SESSION['csrf_token'] ?? null;

    if (!$session_token) {
        return false;
    }

    // If token not provided, try to get it from request
    if ($provided_token === null) {
        // Check POST data (both with and without underscore prefix)
        $provided_token = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? null;

        // Check headers
        if (!$provided_token && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $provided_token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // Check JSON body
        if (!$provided_token) {
            $json = file_get_contents('php://input');
            if ($json) {
                $data = json_decode($json, true);
                if (isset($data['csrf_token'])) {
                    $provided_token = $data['csrf_token'];
                }
            }
        }
    }

    if (!$provided_token) {
        return false;
    }

    // Use hash_equals for timing-attack safe comparison
    return hash_equals($session_token, $provided_token);
}

/**
 * Require valid CSRF token or return error
 *
 * Validates the CSRF token and terminates with 403 error if invalid.
 * Should be called at the beginning of any state-changing API endpoint.
 *
 * @return void Terminates execution if token is invalid
 */
function requireCsrfToken() {
    if (!validateCsrfToken()) {
        // Include API helpers for standardized error response
        if (function_exists('apiForbidden')) {
            apiForbidden('CSRF token validation failed');
        } else {
            // Fallback if API helpers not loaded
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token validation failed'
            ]);
            exit;
        }
    }
}

/**
 * Regenerate CSRF token
 *
 * Creates a new token and updates the session.
 * Should be called after sensitive operations or session changes.
 *
 * @return string The new CSRF token
 */
function regenerateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    unset($_SESSION['csrf_token']);
    return generateCsrfToken();
}

/**
 * Get CSRF token as hidden form field
 *
 * Returns HTML for a hidden input field containing the CSRF token.
 * Use this in forms to automatically include the token.
 *
 * @return string HTML hidden input field
 */
function csrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token meta tag
 *
 * Returns HTML meta tag containing the CSRF token.
 * Use this in the <head> section for JavaScript access.
 *
 * @return string HTML meta tag
 */
function csrfMetaTag() {
    $token = getCsrfToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token for JavaScript
 *
 * Returns JavaScript code to set a global CSRF token variable.
 * Include this in <script> tags or inline scripts.
 *
 * @return string JavaScript code
 */
function csrfScript() {
    $token = getCsrfToken();
    return sprintf(
        "window.csrfToken = '%s';",
        htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Check if request is exempt from CSRF protection
 *
 * Some requests like GET, HEAD, OPTIONS don't need CSRF protection.
 *
 * @return bool True if request method is exempt
 */
function isCsrfExempt() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $exempt_methods = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];
    return in_array($method, $exempt_methods);
}

/**
 * Validate CSRF token for specific operation
 *
 * Enhanced validation with optional operation-specific tokens.
 * Useful for extra protection on critical operations.
 *
 * @param string $operation Operation identifier (e.g., 'delete_user', 'transfer_funds')
 * @return bool True if operation token is valid
 */
function validateOperationToken($operation) {
    if (!validateCsrfToken()) {
        return false;
    }

    // Get operation token from request
    $operation_token = $_POST["_csrf_operation_{$operation}"] ?? null;

    if (!$operation_token && isset($_SERVER['HTTP_X_CSRF_OPERATION'])) {
        $operation_token = $_SERVER['HTTP_X_CSRF_OPERATION'];
    }

    if (!$operation_token) {
        $json = file_get_contents('php://input');
        if ($json) {
            $data = json_decode($json, true);
            if (isset($data['operation_token'])) {
                $operation_token = $data['operation_token'];
            }
        }
    }

    // Generate expected operation token
    $expected_token = hash_hmac('sha256', $operation, getCsrfToken());

    return $operation_token && hash_equals($expected_token, $operation_token);
}

/**
 * Generate operation-specific CSRF token
 *
 * Creates a token tied to a specific operation for extra security.
 *
 * @param string $operation Operation identifier
 * @return string Operation-specific token
 */
function generateOperationToken($operation) {
    return hash_hmac('sha256', $operation, getCsrfToken());
}
?>
