<?php
/**
 * Signature History API
 *
 * Returns the signature history for server rules
 * Serves data from data/signature-history.json with PII stripped
 *
 * @version 1.0.0
 * @created 2025-11-12
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    $data_file = __DIR__ . '/../data/signature-history.json';

    if (!file_exists($data_file)) {
        throw new Exception('Signature history data not found');
    }

    $content = file_get_contents($data_file);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in signature history data');
    }

    // Return data in expected API format
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
