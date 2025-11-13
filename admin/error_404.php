<?php
/**
 * Custom 404 Error Page
 *
 * Provides a user-friendly error page when accessing non-existent admin pages
 *
 * @version 1.0.0
 * @created 2025-11-12
 */

// Don't require authentication for error pages
define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit_logger.php';

// Log 404 error
$requested_url = $_SERVER['REQUEST_URI'] ?? 'unknown';
$referer = $_SERVER['HTTP_REFERER'] ?? 'direct';

log_audit('page_not_found', [
    'requested_url' => $requested_url,
    'referer' => $referer,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Set 404 status
http_response_code(404);

$page_title = "Page Not Found";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Server 1586 Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 3rem;
            text-align: center;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .error-message {
            font-size: 1.125rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .requested-url {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 2rem 0;
            text-align: left;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: #495057;
            word-break: break-all;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        .help-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: left;
        }

        .help-section h3 {
            font-size: 1.125rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .help-links {
            list-style: none;
            display: grid;
            gap: 0.5rem;
        }

        .help-links li a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .help-links li a:hover {
            background: white;
        }

        .help-links li a::before {
            content: "→";
            font-weight: bold;
        }

        @media (max-width: 640px) {
            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-container {
                padding: 2rem;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            The page you're looking for doesn't exist or may have been moved.
        </p>

        <div class="requested-url">
            <strong>Requested:</strong> <?php echo htmlspecialchars($requested_url); ?>
        </div>

        <div class="actions">
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <div class="help-section">
            <h3>📌 Common Pages</h3>
            <ul class="help-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="alliances_power.php">Alliance Power Management</a></li>
                <li><a href="council_rotation.php">Council Rotation</a></li>
                <li><a href="discord_vote_proposals.php">Vote Proposals</a></li>
                <li><a href="discord_templates.php">Discord Templates</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
