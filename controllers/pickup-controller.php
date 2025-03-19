<?php
session_start();
require_once '../models/Order.php';
require_once '../models/Log.php';

if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    die('Unauthorized access');
}

$orderClass = new Order();
$logClass = new Log();

// Handle different actions
$action = $_REQUEST['action'] ?? '';

switch($action) {
    case 'view':
        $pickupId = $_GET['pickup_id'];
        $pickup = $orderClass->getPickupDetails($pickupId);
        include '../views/manager/partials/pickup-details.php';
        break;

    case 'track':
        $pickupId = $_GET['pickup_id'];
        $tracking = $orderClass->getPickupTracking($pickupId);
        include '../views/manager/partials/pickup-tracking.php';
        break;

    case 'getDrivers':
        $drivers = $orderClass->getAvailableDrivers();
        foreach ($drivers as $driver) {
            echo "<option value='{$driver['user_id']}'>{$driver['first_name']} {$driver['last_name']}</option>";
        }
        break;

    case 'assign':
        $pickupId = $_POST['pickup_id'];
        $driverId = $_POST['driver_id'];
        $notes = $_POST['notes'];
        
        $result = $orderClass->assignPickup($pickupId, $driverId, $notes);
        if ($result) {
            $logClass->logActivity($_SESSION['user_id'], "Assigned pickup #$pickupId to driver #$driverId");
        }
        echo json_encode(['success' => $result]);
        break;

    case 'updateStatus':
        $pickupId = $_POST['pickup_id'];
        $status = $_POST['status'];
        
        $result = $orderClass->updatePickupStatus($pickupId, $status);
        if ($result) {
            $logClass->logActivity($_SESSION['user_id'], "Updated pickup #$pickupId status to $status");
        }
        echo json_encode(['success' => $result]);
        break;

    case 'export':
        $pickups = $orderClass->getOrdersWithPickupDetails();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pickup_report.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Pickup ID', 'Order ID', 'Status', 'Date', 'Location', 'Driver', 'Notes']);
        
        foreach ($pickups as $pickup) {
            fputcsv($output, [
                $pickup['pickup_id'],
                $pickup['order_id'],
                $pickup['pickup_status'],
                $pickup['pickup_date'],
                $pickup['pickup_location'],
                $pickup['assigned_to'],
                $pickup['pickup_notes']
            ]);
        }
        fclose($output);
        break;
}
