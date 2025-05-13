<?php
// This endpoint validates if a user is actually a farmer (role_id = 2)
// This helps prevent unauthorized operations on farmer fields by non-farmer users

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Include database connection
require_once('../../config/database.php');

// Initialize response array
$response = [
    'success' => false,
    'is_farmer' => false,
    'message' => ''
];

try {
    // Check if user_id is provided
    if (!isset($_GET['user_id'])) {
        throw new Exception('Missing required parameter: user_id');
    }

    $user_id = intval($_GET['user_id']);

    // Check if the user exists and is a farmer (role_id = 2)
    $user_check_query = "SELECT u.user_id, u.role_id, u.username, fd.detail_id 
                         FROM users u 
                         LEFT JOIN farmer_details fd ON u.user_id = fd.user_id 
                         WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($user_check_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // User doesn't exist
        $response['message'] = 'User not found';
    } else {
        $user_data = $result->fetch_assoc();
        
        // Check if user's role is farmer (role_id = 2)
        if ($user_data['role_id'] == 2) {
            $response['success'] = true;
            $response['is_farmer'] = true;
            $response['message'] = 'Valid farmer account';
            $response['has_details'] = !empty($user_data['detail_id']);
        } else {
            $response['success'] = true;
            $response['is_farmer'] = false;
            $response['message'] = 'User exists but is not a farmer';
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    // Return response
    echo json_encode($response);
    
    // Close connection
    $conn->close();
}
?>