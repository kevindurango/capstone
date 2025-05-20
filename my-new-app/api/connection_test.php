<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple test response
echo json_encode([
    'success' => true,
    'message' => 'Connection successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'client_ip' => $_SERVER['REMOTE_ADDR'],
    'server_ip' => $_SERVER['SERVER_ADDR'],
    'http_host' => $_SERVER['HTTP_HOST']
]);
?> 