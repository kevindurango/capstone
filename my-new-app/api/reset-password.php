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

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST requests are allowed'
    ]);
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
require_once __DIR__ . '/../api/config/database.php';

// Get database connection
$conn = getConnection();

// Check connection
if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit();
}

error_log("[DEBUG] Starting password reset process");

// Get raw POST data and log it
$rawData = file_get_contents("php://input");
error_log("Received raw data: " . $rawData);

// Check if input is empty
if (empty($rawData)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'No data provided. Please send token and new password in JSON format'
    ]);
    exit();
}

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

try {
    // Validate required fields
    if (empty($data['token']) || empty($data['password'])) {
        throw new Exception("Token and new password are required");
    }
    
    $token = $data['token'];
    $password = $data['password'];
    
    // Validate password strength
    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }
    
    // Validate token and get the associated user
    $stmt = $conn->prepare("SELECT user_id, expires_at, used FROM password_reset_tokens WHERE token = ? AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userId = $row['user_id'];
        $expiresAt = $row['expires_at'];
        
        // Check if token has expired
        if (strtotime($expiresAt) < time()) {
            throw new Exception("Password reset token has expired");
        }
        
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Update user's password
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Error updating password: " . $updateStmt->error);
        }
        
        // Mark token as used
        $tokenStmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $tokenStmt->bind_param("s", $token);
        
        if (!$tokenStmt->execute()) {
            throw new Exception("Error updating token status: " . $tokenStmt->error);
        }
        
        // Log the activity
        $action = "Password reset completed for user ID: $userId";
        $logStmt = $conn->prepare("INSERT INTO activitylogs (user_id, action) VALUES (?, ?)");
        $logStmt->bind_param("is", $userId, $action);
        $logStmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Return success response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Password has been reset successfully'
        ]);
        
    } else {
        throw new Exception("Invalid or expired password reset token");
    }
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    
    // Rollback transaction on error
    if ($conn && $conn->connect_errno == 0) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Always ensure we close the connection
    if ($conn) {
        $conn->close();
    }
}
?>
