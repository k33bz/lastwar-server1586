<?php
// Simple test to verify PHP is working
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'PHP is working',
    'php_version' => phpversion(),
    'time' => date('Y-m-d H:i:s')
]);
?>
