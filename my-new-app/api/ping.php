<?php
// Set headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Return success response with server details
    $response = array(
        "status" => "success",
        "message" => "API server is running",
        "server" => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP ' . phpversion(),
        "timestamp" => time(),
        "database" => "connected"
    );

    // Check database connection if database.php exists
    if (file_exists(__DIR__ . '/config/database.php')) {
        try {
            require_once __DIR__ . '/config/database.php';
            // If we got here without errors, database is connected
        } catch (Exception $e) {
            $response["database"] = "error: " . $e->getMessage();
        }
    } else {
        $response["database"] = "config not found";
    }

    // Output the response
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Internal server error",
        "error" => $e->getMessage()
    ]);
}
?>
