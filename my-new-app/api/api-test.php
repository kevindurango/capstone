<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// API endpoints to test
$endpoints = [
    'connectivity-test.php',
    'login.php',
    'market.php',
    'order.php',
    'payment.php?action=ping',
    'register.php',
    'pickup.php'
];

$results = [];

// Test each endpoint
foreach ($endpoints as $endpoint) {
    $url = "http://$_SERVER[HTTP_HOST]/capstone/my-new-app/api/$endpoint";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    $results[$endpoint] = [
        'url' => $url,
        'status' => $httpCode,
        'response' => $response ? json_decode($response, true) : null,
        'error' => $error ?: null,
        'accessible' => ($httpCode >= 200 && $httpCode < 300) ? true : false
    ];
}

// Server information
$serverInfo = [
    'server_ip' => $_SERVER['SERVER_ADDR'],
    'server_name' => $_SERVER['SERVER_NAME'],
    'host' => $_SERVER['HTTP_HOST'],
    'php_version' => phpversion(),
    'timestamp' => time()
];

// Return results
echo json_encode([
    'status' => 'success',
    'message' => 'API test completed',
    'server_info' => $serverInfo,
    'endpoint_tests' => $results
], JSON_PRETTY_PRINT);
?>