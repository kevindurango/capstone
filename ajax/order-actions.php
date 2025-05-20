<?php
session_start();
require_once '../models/Order.php';

// Check authentication
if (
    (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) && 
    (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true)
) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$orderClass = new Order();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_order_details':
            if (isset($_GET['order_id'])) {
                $orderId = (int)$_GET['order_id'];
                $order = $orderClass->getOrderWithItems($orderId);
                
                if ($order) {
                    echo json_encode([
                        'success' => true,
                        'order' => $order
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Order not found'
                    ]);
                }
            }
            break;
        
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
                $orderId = (int)$_POST['order_id'];
                $newStatus = $_POST['new_status'];
                
                // Validate status
                $validStatuses = ['pending', 'processing', 'ready', 'completed', 'canceled'];
                if (!in_array($newStatus, $validStatuses)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid status value'
                    ]);
                    exit();
                }
                
                // Update the order status
                if ($orderClass->updateStatus($orderId, $newStatus)) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Order #$orderId status updated to $newStatus",
                        'newStatus' => $newStatus
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update order status'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
}
