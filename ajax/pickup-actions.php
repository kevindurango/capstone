<?php
session_start();
require_once '../models/Order.php';
require_once '../models/Log.php';

if (!isset($_SESSION['manager_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderClass = new Order();
$logClass = new Log();
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_pickup_details':
            $pickupId = $_GET['pickup_id'];
            $pickup = $orderClass->getPickupDetails($pickupId);
            echo json_encode(['success' => true, 'pickup' => $pickup]);
            break;

        case 'get_tracking_details':
            $pickupId = $_GET['pickup_id'];
            $tracking = $orderClass->getPickupTracking($pickupId);
            echo json_encode(['success' => true, 'tracking' => $tracking]);
            break;

        case 'get_drivers':
            $drivers = $orderClass->getAvailableDrivers();
            echo json_encode(['success' => true, 'drivers' => $drivers]);
            break;

        case 'assign_pickup':
            $pickupId = $_POST['pickup_id'];
            $driverName = $_POST['driver_id']; // This will be the driver's name
            $notes = $_POST['notes'] ?? '';
            
            $result = $orderClass->assignPickup($pickupId, $driverName, $notes);
            if ($result) {
                $logClass->logActivity($_SESSION['user_id'], "Assigned pickup #$pickupId to driver: $driverName");
            }
            echo json_encode(['success' => $result]);
            break;

        case 'update_status':
            $pickupId = $_POST['pickup_id'];
            $status = $_POST['status'];
            
            $result = $orderClass->updatePickupStatus($pickupId, $status);
            if ($result) {
                $logClass->logActivity($_SESSION['user_id'], "Updated pickup #$pickupId status to $status");
            }
            echo json_encode(['success' => $result]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
