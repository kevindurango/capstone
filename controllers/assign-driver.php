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
if (!isset($_POST['pickup_id']) || !isset($_POST['driver_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit();
}

$pickup_id = $_POST['pickup_id'];
$driver_id = $_POST['driver_id'];

require_once '../models/Database.php';
require_once '../models/Log.php';
require_once '../models/DriverModel.php';

try {
    // Database connection
    $database = new Database();
    $conn = $database->connect();
    $log = new Log();
    $driverModel = new DriverModel();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Update pickup
    $updateQuery = "UPDATE pickups SET assigned_to = :driver_id, pickup_status = 'assigned' WHERE pickup_id = :pickup_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_STR); // Use PARAM_STR for compatibility
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Update driver status to busy
        $driverModel->updateDriverStatus($driver_id, 'busy');
        
        // Log activity
        $manager_user_id = $_SESSION['manager_user_id'] ?? null;
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Assigned driver #$driver_id to pickup #$pickup_id");
        }
        
        // Get driver details for response
        $driverQuery = "SELECT u.first_name, u.last_name, u.username FROM users u WHERE u.user_id = :driver_id";
        $driverStmt = $conn->prepare($driverQuery);
        $driverStmt->bindParam(':driver_id', $driver_id);
        $driverStmt->execute();
        $driverDetails = $driverStmt->fetch(PDO::FETCH_ASSOC);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Driver assigned successfully',
            'driver' => $driverDetails
        ]);
    } else {
        // Rollback transaction
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to assign driver'
        ]);
    }
} catch (PDOException $e) {
    // Rollback transaction
    if ($conn) {
        $conn->rollback();
    }
    
    error_log("Error assigning driver: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>
