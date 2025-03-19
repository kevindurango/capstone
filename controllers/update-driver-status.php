<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as manager
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['driver_id']) || !isset($_POST['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit();
}

// Validate the status
$driver_id = $_POST['driver_id'];
$status = $_POST['status'];

// Debug logging
error_log("Updating driver #$driver_id status to: $status");

// Validate status value
if (!in_array($status, ['available', 'busy', 'offline'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit();
}

require_once '../models/Database.php';
require_once '../models/Log.php';

try {
    // Connect to the database
    $db = new Database();
    $conn = $db->connect();
    $log = new Log();
    
    // Update driver status
    $query = "UPDATE driver_details 
              SET availability_status = :status 
              WHERE user_id = :driver_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':driver_id', $driver_id);
    
    if ($stmt->execute()) {
        // Log the status update
        $manager_user_id = $_SESSION['manager_user_id'] ?? null;
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Updated driver #$driver_id status to $status");
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $status
        ]);
    } else {
        error_log("Failed to update driver #$driver_id status. PDO error info: " . print_r($stmt->errorInfo(), true));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update driver status: ' . $stmt->errorInfo()[2]
        ]);
    }
} catch (PDOException $e) {
    // Log error and return error response
    error_log("Database error updating driver #$driver_id status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'debug' => $e->getMessage()
    ]);
}
?>
