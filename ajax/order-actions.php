<?php
session_start();
require_once '../models/Order.php';

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
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
