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

error_log("[DEBUG] Starting forgot password process");

// Get raw POST data and log it
$rawData = file_get_contents("php://input");
error_log("Received raw data: " . $rawData);

// Check if input is empty
if (empty($rawData)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'No data provided. Please send email in JSON format'
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

// Initialize response array
$response = array();

try {
    // Validate required fields
    if (empty($data['email'])) {
        throw new Exception("Email is required");
    }
    
    $email = $data['email'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    // Check if the user with given email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userId = $row['user_id'];
        
        // Generate token
        $token = bin2hex(random_bytes(32)); // 64 character hex string
        
        // Set expiry time (24 hours from now)
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store token in the database
        // First, check if a token already exists for this user and invalidate it
        $stmtUpdate = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
        $stmtUpdate->bind_param("i", $userId);
        $stmtUpdate->execute();
        
        // Insert new token
        $stmtInsert = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmtInsert->bind_param("iss", $userId, $token, $expires);
        
        if ($stmtInsert->execute()) {
            // Reset link for mobile app
            $reset_link = "farmersmarket://reset-password?token=" . $token;
            
            // Log token for development purposes
            error_log("Password Reset Token for {$email}: {$token}");
            error_log("Reset Link: {$reset_link}");
            
            // For local development, we'll skip actual email sending
            // and provide the token directly in the response
            $emailContent = generatePasswordResetEmailContent($email, $reset_link, $token);
            
            // In development mode, always return success and the token
            // Don't try to send emails in development to avoid errors
            $emailSent = false;
            
            // Only attempt to send email if not in development mode
            // For local development, we'll set a flag to bypass actual email sending
            $isDevelopment = true; // Set to false in production
            
            if (!$isDevelopment) {
                try {
                    $emailSent = sendPasswordResetEmail($email, $reset_link, $token);
                } catch (Exception $emailEx) {
                    error_log("Email sending failed, but continuing with token generation: " . $emailEx->getMessage());
                    // Don't rethrow - we'll continue with the process
                }
            }
            
            // Return success response with token for development
            $response = array(
                "status" => "success",
                "message" => "Password reset instructions generated.",
                "dev_mode" => true,
                "reset_info" => array(
                    "token" => $token,
                    "link" => $reset_link,
                    "expires" => $expires,
                    "email_sent" => $emailSent ? "true" : "false"
                )
            );
        } else {
            throw new Exception("Error generating password reset token");
        }
    } else {
        // Even if the email doesn't exist, return success to prevent email enumeration
        $response = array(
            "status" => "success",
            "message" => "Password reset instructions sent."
        );
    }
    
    // Send response
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    
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

/**
 * Generate password reset email content (without sending)
 * 
 * @param string $email The recipient's email address
 * @param string $resetLink The reset link for mobile app
 * @param string $token The token for password reset
 * @return string The email content that would be sent
 */
function generatePasswordResetEmailContent($email, $resetLink, $token) {
    // Get the app domain and protocol (can be modified based on environment)
    $appDomain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    
    // Create web reset link as a backup in case deep link doesn't work
    $webResetLink = "{$protocol}://{$appDomain}/reset-password?token={$token}";
    
    // Email content
    $message = "
    <html>
    <head>
        <title>Password Reset Instructions</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .button { display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; 
                    text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { font-size: 12px; color: #777; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>FarmersMarket Password Reset</h2>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>We received a request to reset your password for your FarmersMarket account.</p>
                <p>If you didn't make this request, you can ignore this email.</p>
                <p>To reset your password, open the FarmersMarket app and use the following verification code:</p>
                <h2 style='text-align: center; letter-spacing: 5px; color: #4CAF50;'>{$token}</h2>
                <p>Or open the app using this link:</p>
                <p style='text-align: center;'><a href='{$resetLink}' class='button'>Reset Password</a></p>
                <p>This password reset link is valid for 24 hours.</p>
                <p>Thank you,<br>The FarmersMarket Team</p>
            </div>
            <div class='footer'>
                <p>If you're having trouble with the button above, copy and paste the URL below into your web browser:</p>
                <p>{$webResetLink}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return $message;
}

/**
 * Send password reset email to the user
 * 
 * @param string $email The recipient's email address
 * @param string $resetLink The reset link for mobile app
 * @param string $token The token for password reset
 * @return bool Whether the email was sent successfully
 */
function sendPasswordResetEmail($email, $resetLink, $token) {
    try {
        // Email subject
        $subject = "Password Reset Instructions - FarmersMarket App";
        
        // Generate email content
        $message = generatePasswordResetEmailContent($email, $resetLink, $token);
        
        // Additional headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: FarmersMarket <noreply@farmersmarket.com>" . "\r\n";
        
        // Log that we're about to send an email
        error_log("Attempting to send password reset email to {$email}");
        
        // Use @ to suppress errors from mail() function
        $emailSent = @mail($email, $subject, $message, $headers);
        
        // Log the result
        if ($emailSent) {
            error_log("Password reset email sent successfully to {$email}");
        } else {
            error_log("Failed to send password reset email to {$email}, but continuing with process");
        }
        
        return $emailSent;
    } catch (Exception $e) {
        error_log("Error sending password reset email: " . $e->getMessage());
        // Return false instead of throwing an exception
        return false;
    }
}
?>
