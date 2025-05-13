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

error_log("[DEBUG] Starting login process");

try {
    // Get database connection
    $conn = getConnection();
    
    // Check connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    // Get and parse input data
    $input = file_get_contents("php://input");
    error_log("[DEBUG] Raw input received: " . $input);
    
    // Check if input is empty
    if (empty($input)) {
        throw new Exception('No data provided. Please send email and password in JSON format');
    }
    
    $data = json_decode($input);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[ERROR] JSON decode failed: " . json_last_error_msg());
        throw new Exception('Invalid JSON data received');
    }
    
    // Validate required fields
    if (!isset($data->email) || !isset($data->password)) {
        throw new Exception('Email and password are required');
    }
    
    // Find user by email (using mysqli for consistency)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    error_log("[DEBUG] User found: " . $user['username']);
    
    // Verify password - with special handling for dev environment
    $passwordMatches = false;
    
    // First try normal password verification
    if (password_verify($data->password, $user['password'])) {
        $passwordMatches = true;
    }
    // Special case for development - allow direct matching for testing
    // This should be removed in production
    else if ($data->password === $user['password']) {
        error_log("[WARNING] Using direct password match - not secure for production!");
        $passwordMatches = true;
    }
    
    if (!$passwordMatches) {
        error_log("[ERROR] Password verification failed");
        throw new Exception('Invalid password');
    }
    
    // Generate a simple token
    $token = bin2hex(random_bytes(32));
    
    // Log user activity
    $userId = $user['user_id'];
    $userRole = $user['role_id'];
    $roleNames = [1 => 'User', 2 => 'Farmer', 3 => 'Admin', 4 => 'Manager', 5 => 'Organization Head', 6 => 'Driver'];
    $roleName = isset($roleNames[$userRole]) ? $roleNames[$userRole] : 'Unknown';
    
    $activityMsg = $roleName . " logged in.";
    $stmt = $conn->prepare("INSERT INTO activitylogs (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $activityMsg);
    $stmt->execute();
    
    // Remove password from response
    unset($user['password']);
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("[ERROR] Login failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close the connection if it exists
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
