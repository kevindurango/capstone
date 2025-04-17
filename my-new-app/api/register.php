<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers to allow cross-origin requests and specify content type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    exit();
}

// Ensure we catch all errors
function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit();
}
set_error_handler('handleError');

// Include database connection file
require_once __DIR__ . '/../api/config/database.php'; // Ensure this file contains the correct credentials

// Create a new database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit();
}

error_log("[DEBUG] Starting registration process");

// Get raw POST data and log it
$rawData = file_get_contents("php://input");
error_log("Received raw data: " . $rawData);

// Decode JSON data
$data = json_decode($rawData, true);

// Check if JSON is valid
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON data provided'
    ]);
    exit();
}

// Initialize response array
$response = array();

try {
    // Validate required fields
    if (
        empty($data['fullName']) ||
        empty($data['email']) ||
        empty($data['password']) ||
        empty($data['userType'])
    ) {
        throw new Exception("Required fields are missing");
    }
    
    // Extract data
    $fullName = $data['fullName'];
    $email = $data['email'];
    $password = $data['password'];
    $userType = $data['userType'];
    $contactNumber = $data['contact_number'] ?? '';
    $address = $data['address'] ?? '';
    
    // Split full name into first and last name
    $name_parts = explode(' ', $fullName, 2);
    $firstName = $name_parts[0];
    $lastName = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Generate username from email (before @ symbol)
    $username = explode('@', $email)[0];
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email already exists. Please use a different email.");
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If username exists, append numbers until unique
    if ($result->num_rows > 0) {
        $i = 1;
        $newUsername = $username . $i;
        
        while (true) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $newUsername);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $username = $newUsername;
                break;
            }
            
            $i++;
            $newUsername = $username . $i;
        }
    }
    
    // Determine role_id based on userType
    $roleId = 1; // Default to User (Consumer)
    if ($userType === 'farmer') {
        $roleId = 2; // Farmer role
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Insert user data
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role_id, first_name, last_name, contact_number, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissss", $username, $hashedPassword, $email, $roleId, $firstName, $lastName, $contactNumber, $address);
    
    if (!$stmt->execute()) {
        throw new Exception("Error creating user account: " . $stmt->error);
    }
    
    $userId = $conn->insert_id;
    
    // If user is a farmer, also create farmer_details entry
    if ($userType === 'farmer') {
        $stmt = $conn->prepare("INSERT INTO farmer_details (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating farmer details: " . $stmt->error);
        }
    }
    
    // Log the activity
    $action = "User registered: $username";
    $stmt = $conn->prepare("INSERT INTO activitylogs (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Prepare success response
    $response = array(
        "status" => "success",
        "message" => "Registration successful",
        "user" => array(
            "id" => $userId,
            "username" => $username,
            "email" => $email,
            "fullName" => $fullName,
            "userType" => $userType
        )
    );
    
    // Ensure proper JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    
    // Rollback transaction on error
    if ($conn && $conn->connect_errno == 0) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
} finally {
    // Always ensure we send a valid JSON response
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    if ($conn) {
        $conn->close();
    }
}
?>
