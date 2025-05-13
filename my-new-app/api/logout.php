<?php
// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Origin');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST requests are allowed'
    ]);
    exit();
}

// Include database connection file
require_once 'config/database.php';

error_log("[DEBUG] Starting logout process");

try {
    // Get user ID from the request if provided (optional)
    // This can be used to log which user logged out
    $input = file_get_contents("php://input");
    $userId = null;
    
    if (!empty($input)) {
        $data = json_decode($input);
        if (isset($data->user_id)) {
            $userId = $data->user_id;
            error_log("[DEBUG] Logging out user ID: " . $userId);
            
            // Log user activity if ID is provided
            if ($userId) {
                $stmt = $conn->prepare("INSERT INTO activitylogs (user_id, action) VALUES (?, 'User logged out')");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            }
        }
    }
    
    // In a more complex system, you might invalidate tokens here
    // For this implementation, the client side will remove tokens from local storage
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Logout successful'
    ]);
    
} catch (Exception $e) {
    error_log("[ERROR] Logout failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>