<?php
// Start session for CSRF protection
session_start();

// Include necessary files
require_once '../models/Database.php';
require_once '../models/Log.php';
require_once '../models/Order.php';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh the page and try again.'
    ]);
    exit();
}

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please log in.'
    ]);
    exit();
}

// Connect to the database
$database = new Database();
$conn = $database->connect();

// Log instance
$log = new Log();
$orderClass = new Order();

// Get admin user ID from session
$admin_user_id = $_SESSION['admin_user_id'] ?? null;

// Handle different actions
$action = $_POST['action'] ?? '';

// Batch actions for multiple pickups
if (isset($_POST['pickup_ids']) && is_array($_POST['pickup_ids'])) {
    $pickup_ids = $_POST['pickup_ids'];
    $status = $_POST['status'] ?? '';
    
    // Validate pickup IDs
    if (empty($pickup_ids)) {
        echo json_encode([
            'success' => false,
            'message' => 'No pickups selected.'
        ]);
        exit();
    }
    
    // Sanitize pickup IDs
    $pickup_ids = array_map('intval', $pickup_ids);
    $id_list = implode(',', $pickup_ids);
    
    // Process based on action
    try {
        $conn->beginTransaction();
        
        $affectedRows = 0;
        $message = '';
        
        switch ($action) {
            case 'approve':
                // Update pickup status to "completed"
                $stmt = $conn->prepare("UPDATE pickups SET pickup_status = :status WHERE pickup_id IN ($id_list)");
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
                $message = "Successfully approved $affectedRows pickups";
                
                // Log the activity
                if ($admin_user_id) {
                    $log->logActivity($admin_user_id, "Admin approved $affectedRows pickups in bulk", [
                        'pickup_ids' => $pickup_ids,
                        'new_status' => $status
                    ]);
                }
                break;
                
            case 'reject':
                // Update pickup status to "canceled"
                $stmt = $conn->prepare("UPDATE pickups SET pickup_status = :status WHERE pickup_id IN ($id_list)");
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
                $message = "Successfully rejected $affectedRows pickups";
                
                // Log the activity
                if ($admin_user_id) {
                    $log->logActivity($admin_user_id, "Admin rejected $affectedRows pickups in bulk", [
                        'pickup_ids' => $pickup_ids,
                        'new_status' => $status
                    ]);
                }
                break;
                
            case 'delete':
                // Delete the pickups
                $stmt = $conn->prepare("DELETE FROM pickups WHERE pickup_id IN ($id_list)");
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
                $message = "Successfully deleted $affectedRows pickups";
                
                // Log the activity
                if ($admin_user_id) {
                    $log->logActivity($admin_user_id, "Admin deleted $affectedRows pickups in bulk", [
                        'pickup_ids' => $pickup_ids
                    ]);
                }
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid batch action specified.'
                ]);
                $conn->rollBack();
                exit();
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'affected_rows' => $affectedRows
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        
        // Log the error
        if ($admin_user_id) {
            $log->logActivity($admin_user_id, "Error processing bulk action: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
} 
// Individual pickup actions
else if (isset($_POST['pickup_id']) || isset($_GET['pickup_id'])) {
    $pickupId = $_POST['pickup_id'] ?? $_GET['pickup_id'] ?? 0;
    
    try {
        switch ($action) {
            case 'get_pickup_details':
                $pickup = $orderClass->getPickupDetails($pickupId);
                echo json_encode(['success' => true, 'pickup' => $pickup]);
                break;
                
            case 'update_status':
                $status = $_POST['status'];
                $result = $orderClass->updatePickupStatus($pickupId, $status);
                if ($result) {
                    $log->logActivity($admin_user_id, "Admin updated pickup #$pickupId status to $status");
                }
                echo json_encode(['success' => $result]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid individual action specified.'
                ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters.'
    ]);
}
?>
