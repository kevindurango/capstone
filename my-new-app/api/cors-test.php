<?php
// cors-test.php - Test endpoint for CORS configuration

// Set appropriate CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the HTTP method used to access this script
$method = $_SERVER['REQUEST_METHOD'];

// Response data
$response = [
    'success' => true,
    'timestamp' => time(),
    'message' => "CORS test successful with method: $method",
    'cors_headers' => [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
    ],
    'request_headers' => []
];

// Include request headers in response
$headers = getallheaders();
foreach ($headers as $key => $value) {
    $response['request_headers'][strtolower($key)] = $value;
}

// Output as JSON
echo json_encode($response, JSON_PRETTY_PRINT);
