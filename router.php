<?php
/**
 * Router Script for PHP Built-in Development Server
 *
 * Handles routing and custom error pages for php -S server
 *
 * Usage: php -S localhost:8080 router.php
 *
 * @version 1.0.0
 * @created 2025-11-12
 */

$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'] ?? '/';

// Remove query string for file checks
$file_path = $_SERVER['DOCUMENT_ROOT'] . $path;

// Serve static files directly (assets, images, etc.)
if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|ico|json|woff|woff2|ttf|eot)$/i', $path)) {
    if (file_exists($file_path)) {
        return false; // Let PHP serve the static file
    } else {
        // 404 for missing static files
        if (strpos($path, '/admin/') === 0) {
            require_once __DIR__ . '/admin/error_404.php';
            exit;
        }
        http_response_code(404);
        exit;
    }
}

// Handle admin directory requests
if (strpos($path, '/admin/') === 0) {
    // Check if the requested PHP file exists
    if (preg_match('/\.php$/i', $path)) {
        if (file_exists($file_path)) {
            return false; // Let PHP execute the file
        } else {
            // Custom 404 for admin panel
            require_once __DIR__ . '/admin/error_404.php';
            exit;
        }
    } else {
        // Directory request in admin - check for index.php
        $index_path = rtrim($file_path, '/') . '/index.php';
        if (file_exists($index_path)) {
            require_once $index_path;
            exit;
        } else {
            require_once __DIR__ . '/admin/error_404.php';
            exit;
        }
    }
}

// Handle root directory and other paths
if ($path === '/' || $path === '/index.html' || $path === '') {
    // Serve the React app
    if (file_exists(__DIR__ . '/index.html')) {
        require_once __DIR__ . '/index.html';
        exit;
    }
}

// Check if the requested file exists
if (file_exists($file_path)) {
    return false; // Let PHP handle it
}

// Default 404 - serve React app (for client-side routing)
if (file_exists(__DIR__ . '/index.html')) {
    require_once __DIR__ . '/index.html';
    exit;
}

// Fallback 404
http_response_code(404);
echo "404 - Not Found";
exit;
