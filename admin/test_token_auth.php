<?php
/**
 * Test Token Authentication
 *
 * Simple endpoint to test if JWT token authentication is working
 * Returns JSON with authentication status and token details
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [
    'success' => false,
    'timestamp' => date('Y-m-d H:i:s'),
    'cookie_present' => isset($_COOKIE['jwt']),
    'cookie_value' => isset($_COOKIE['jwt']) ? substr($_COOKIE['jwt'], 0, 50) . '...' : null,
    'errors' => []
];

try {
    require_once 'jwt.php';

    // Try to get JWT token
    if (!isset($_COOKIE['jwt'])) {
        $response['errors'][] = 'No JWT cookie present';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    $response['cookie_length'] = strlen($_COOKIE['jwt']);

    // Try to decode the token
    try {
        $token = decode_jwt($_COOKIE['jwt']);

        $response['success'] = true;
        $response['token'] = [
            'sub' => $token->sub ?? null,
            'aud' => $token->aud ?? null,
            'alliances' => $token->alliances ?? null,
            'powereditor' => $token->powereditor ?? false,
            'test_token' => $token->test_token ?? false,
            'jti' => $token->jti ?? null,
            'exp' => $token->exp ?? null,
            'exp_formatted' => isset($token->exp) ? date('Y-m-d H:i:s', $token->exp) : null,
            'iat' => $token->iat ?? null,
            'iat_formatted' => isset($token->iat) ? date('Y-m-d H:i:s', $token->iat) : null,
            'is_expired' => isset($token->exp) ? ($token->exp < time()) : null,
            'time_until_expiry' => isset($token->exp) ? ($token->exp - time()) : null
        ];

        // Check role-based access
        $response['access'] = [
            'is_admin' => ($token->aud === 'admin'),
            'is_r5' => ($token->aud === 'r5'),
            'is_r4' => ($token->aud === 'r4'),
            'is_power_editor' => is_power_editor($token),
            'can_delete_alliances' => can_delete_alliances($token)
        ];

    } catch (Exception $e) {
        $response['errors'][] = 'Token decode error: ' . $e->getMessage();
    }

} catch (Exception $e) {
    $response['errors'][] = 'Fatal error: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
