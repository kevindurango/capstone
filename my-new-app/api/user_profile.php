<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once('config/database.php');

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'user' => null
];

// Process based on request method
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check if user_id is provided
        if (!isset($_GET['user_id'])) {
            $response['message'] = 'Missing user_id parameter';
            echo json_encode($response);
            exit;
        }
        
        $user_id = intval($_GET['user_id']);
        
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT user_id, username, email, first_name, last_name, contact_number, address, role_id, created_at, updated_at FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Convert numeric fields to proper types
            $user['user_id'] = (int)$user['user_id'];
            $user['role_id'] = (int)$user['role_id'];
            
            $response['success'] = true;
            $response['message'] = 'User profile retrieved successfully';
            $response['user'] = $user;
        } else {
            $response['message'] = 'User not found';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Method not allowed';
}

// Return the response
echo json_encode($response);
?>