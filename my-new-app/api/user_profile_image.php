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
    'image_url' => null
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
        
        // Look up user in the database - users table doesn't have profile_image column
        // so we'll generate a placeholder image URL instead
        $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Create a placeholder image URL or use a default image
            // In a real system, you would either have a profile_image field in your users table
            // or a separate table to store user profile images
            
            // For now, let's just return a default image path and indicate success
            $response['success'] = true;
            $response['message'] = 'Default profile image URL provided';
            
            // You can replace this with an actual default image path on your server
            // or generate a URL for a service like Gravatar based on the email
            $default_image = "assets/images/default-profile.png";
            $response['image_url'] = $default_image;
            
            // Include user name to allow initialing in the frontend if needed
            $response['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
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