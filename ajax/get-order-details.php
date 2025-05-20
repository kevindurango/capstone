<?php
header('Content-Type: application/json');
session_start();
require_once '../models/Database.php';

// Check if user is logged in as admin, manager, or organization head
if ((!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) && 
    (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) &&
    (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true)) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Validate order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

$order_id = (int)$_GET['order_id'];

try {
    require_once '../models/Order.php';
    $orderModel = new Order();
    
    // Get complete order details using the Order model
    $orderDetails = $orderModel->getOrderDetails($order_id);
    
    if (!$orderDetails) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    // Prepare response
    $response = [
        'success' => true,
        'order' => $orderDetails,
        'items' => $orderDetails['items'],
        'payment' => [
            'status' => $orderDetails['payment_status'] ?? 'not_processed',
            'amount' => $orderDetails['amount'] ?? 0,
            'date' => $orderDetails['payment_date'] ?? null,
            'method' => $orderDetails['payment_method'] ?? null
        ],
        'pickup' => [
            'status' => $orderDetails['pickup_status'] ?? 'pending',
            'date' => $orderDetails['pickup_date'] ?? null,
            'location' => $orderDetails['pickup_location'] ?? null,
            'notes' => $orderDetails['pickup_notes'] ?? null,
            'contact_person' => $orderDetails['contact_person'] ?? null
        ],
        'subtotal' => $orderDetails['total'] ?? 0
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug' => $_ENV['ENVIRONMENT'] === 'development' ? $e->getMessage() : null
    ]);
    exit();
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred'
    ]);
    exit();
}
?>