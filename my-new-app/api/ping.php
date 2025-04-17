<?php
// Simple endpoint to check if the API is accessible
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'API is reachable',
    'timestamp' => time()
]);
?>
