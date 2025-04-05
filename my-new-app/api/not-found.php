<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Return a JSON error for all not-found endpoints
http_response_code(404);
echo json_encode([
    "status" => "error",
    "message" => "API endpoint not found",
    "requested_uri" => $_SERVER['REQUEST_URI']
]);
?>
