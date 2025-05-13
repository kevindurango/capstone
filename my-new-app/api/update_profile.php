<?php
// API endpoint for updating user profile information
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once './config/database.php';

// Function to log errors
function logError($message) {
    $logFile = 'profile_update_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Function to validate user input
function validateInput($data) {
    $errors = [];
    
    // Basic validation
    if (empty($data->user_id)) {
        $errors[] = "User ID is required";
    }
    
    if (empty($data->first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($data->last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($data->email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Password validation if it's being updated
    if (!empty($data->new_password)) {
        if (empty($data->current_password)) {
            $errors[] = "Current password is required when updating password";
        }
        
        if (strlen($data->new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
    }
    
    return $errors;
}

try {
    // Ensure this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    // Get posted data
    $input = file_get_contents("php://input");
    if (empty($input)) {
        throw new Exception('No data provided');
    }

    $data = json_decode($input);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError("JSON decode failed: " . json_last_error_msg());
        throw new Exception('Invalid JSON data received');
    }

    // Validate input
    $validationErrors = validateInput($data);
    if (!empty($validationErrors)) {
        throw new Exception('Validation failed: ' . implode(', ', $validationErrors));
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $data->user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }

    // Get current user data
    $currentUser = $result->fetch_assoc();
    
    // Check if email is already taken by another user
    if ($currentUser['email'] !== $data->email) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
        $stmt->bind_param("si", $data->email, $data->user_id);
        $stmt->execute();
        $emailCheckResult = $stmt->get_result();
        
        if ($emailCheckResult->num_rows > 0) {
            throw new Exception('Email address is already in use by another account');
        }
    }

    // If updating password, verify current password
    $passwordUpdated = false;
    if (!empty($data->new_password)) {
        // Verify the current password
        $passwordValid = false;
        
        // First try normal password verification
        if (password_verify($data->current_password, $currentUser['password'])) {
            $passwordValid = true;
        } 
        // Special case for development - allow direct matching for testing
        // This should be removed in production
        else if ($data->current_password === $currentUser['password']) {
            logError("[WARNING] Using direct password match - not secure for production!");
            $passwordValid = true;
        }
        
        if (!$passwordValid) {
            throw new Exception('Current password is incorrect');
        }
        
        // Hash the new password
        $newHashedPassword = password_hash($data->new_password, PASSWORD_DEFAULT);
        $passwordUpdated = true;
    }

    // Begin transaction
    $conn->begin_transaction();

    // Update user information
    if ($passwordUpdated) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET 
                first_name = ?,
                last_name = ?,
                username = ?,
                email = ?,
                password = ?,
                contact_number = ?,
                address = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        
        $stmt->bind_param(
            "sssssssi", 
            $data->first_name,
            $data->last_name,
            $data->username,
            $data->email,
            $newHashedPassword,
            $data->contact_number,
            $data->address,
            $data->user_id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE users 
            SET 
                first_name = ?,
                last_name = ?,
                username = ?,
                email = ?,
                contact_number = ?,
                address = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        
        $stmt->bind_param(
            "ssssssi", 
            $data->first_name,
            $data->last_name,
            $data->username,
            $data->email,
            $data->contact_number,
            $data->address,
            $data->user_id
        );
    }
    
    if ($stmt->execute()) {
        // Commit transaction
        $conn->commit();
        
        // Log the profile update activity
        $logStmt = $conn->prepare("INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, NOW())");
        $action = $passwordUpdated ? 
            "Updated profile information and password" : 
            "Updated profile information";
        $logStmt->bind_param("is", $data->user_id, $action);
        $logStmt->execute();
        
        // Get updated user data
        $stmt = $conn->prepare("SELECT user_id, username, email, first_name, last_name, contact_number, address, role_id FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $data->user_id);
        $stmt->execute();
        $updatedUser = $stmt->get_result()->fetch_assoc();
        
        // Return success response with updated user data
        echo json_encode([
            'status' => 'success',
            'message' => $passwordUpdated ? 
                'Profile and password updated successfully' : 
                'Profile updated successfully',
            'user' => $updatedUser,
            'password_updated' => $passwordUpdated
        ]);
    } else {
        // Rollback transaction on failure
        $conn->rollback();
        throw new Exception('Failed to update profile: ' . $stmt->error);
    }

} catch (Exception $e) {
    // Ensure any transaction is rolled back on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    logError("Profile update error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>