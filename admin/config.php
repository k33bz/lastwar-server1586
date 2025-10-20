<?php
/**
 * Configuration File for Last War 1586 Admin System
 *
 * Loads environment variables and defines application constants
 * Centralizes configuration for JWT, SMTP, file paths, and security settings
 *
 * Documentation:
 * - Environment Configuration: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ENV-CONFIG.md
 * - Setup Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/setup-local-env.md
 * - Admin Panel Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/README.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial complete implementation with proper error handling
 */

// Prevent direct access
if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}

// Load Composer autoloader
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die('Composer dependencies not installed. Please run: composer install');
}
require_once $autoload_path;

// Load environment variables from .env file
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Validate required environment variables
    $dotenv->required([
        'SECRET_KEY',
        'SMTP_HOST',
        'SMTP_USER',
        'SMTP_PASS',
        'SMTP_FROM',
        'APP_URL'
    ])->notEmpty();
} else {
    // Development fallback warning
    $app_env = $_ENV['APP_ENV'] ?? 'production';
    if ($app_env !== 'development') {
        error_log('SECURITY WARNING: .env file not found. Please create one from .env.example');
        die('Configuration error. Please contact the administrator.');
    }
}

// JWT Configuration
define('SECRET_KEY', $_ENV['SECRET_KEY'] ?? 'INSECURE_DEFAULT_KEY_CHANGE_THIS');

// SMTP Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM', $_ENV['SMTP_FROM'] ?? 'noreply@localhost');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? ($_ENV['APP_NAME'] ?? 'Last War 1586'));

// Application Configuration
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Token Expiry Configuration (in seconds)
define('MAGIC_LINK_EXPIRY', (int)($_ENV['MAGIC_LINK_EXPIRY'] ?? 600));      // 10 minutes default
define('SESSION_TOKEN_EXPIRY', (int)($_ENV['SESSION_TOKEN_EXPIRY'] ?? 3600)); // 1 hour default
define('REFRESH_TOKEN_EXPIRY', (int)($_ENV['REFRESH_TOKEN_EXPIRY'] ?? 604800)); // 7 days default

// Key Rotation Configuration
define('AUTO_KEY_ROTATION_ENABLED', ($_ENV['AUTO_KEY_ROTATION_ENABLED'] ?? 'true') === 'true');
define('KEY_ROTATION_INTERVAL_DAYS', (int)($_ENV['KEY_ROTATION_INTERVAL_DAYS'] ?? 30));
define('KEY_ROTATION_GRACE_PERIOD', (int)($_ENV['KEY_ROTATION_GRACE_PERIOD'] ?? 300));

// File Paths
define('USERS_FILE', __DIR__ . '/users.json');
define('BLACKLIST_FILE', __DIR__ . '/token_blacklist.json');
define('ALLIANCES_FILE', dirname(__DIR__) . '/data/alliances.json');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Enforce HTTPS in production
if (APP_ENV === 'production' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    if (php_sapi_name() !== 'cli') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Set secure session cookie parameters
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', APP_ENV === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Timezone (adjust as needed)
date_default_timezone_set('America/New_York'); // EDT/EST

/**
 * Initialize data files if they don't exist
 */
function initialize_data_files() {
    // Initialize users.json with admin user if it doesn't exist
    if (!file_exists(USERS_FILE)) {
        $initial_users = [
            'users' => [
                [
                    'email' => ADMIN_EMAIL,
                    'alliances' => ['*'],
                    'role' => 'admin'
                ]
            ]
        ];
        file_put_contents(USERS_FILE, json_encode($initial_users, JSON_PRETTY_PRINT));
        if (function_exists('chmod')) {
            @chmod(USERS_FILE, 0600); // Secure permissions
        }
    }

    // Initialize token_blacklist.json if it doesn't exist
    if (!file_exists(BLACKLIST_FILE)) {
        $initial_blacklist = ['jti' => [], 'expires' => []];
        file_put_contents(BLACKLIST_FILE, json_encode($initial_blacklist, JSON_PRETTY_PRINT));
        if (function_exists('chmod')) {
            @chmod(BLACKLIST_FILE, 0600); // Secure permissions
        }
    }
}

// Initialize data files on first load
initialize_data_files();
?>
