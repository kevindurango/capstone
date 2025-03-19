<?php
// Start session to check if user is logged in
session_start();

// Check if the user is logged in as an Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Database Connection
$database = new Database();
$conn = $database->connect();

// Log instance
$log = new Log();

// Get Admin User ID from Session
$admin_user_id = $_SESSION['admin_user_id'] ?? null;

// Check if order_id was provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

$order_id = intval($_GET['order_id']);

try {
    // Log the activity
    if ($admin_user_id) {
        $log->logActivity($admin_user_id, "Viewed details for order #$order_id");
    }

    // Query to get order details with customer information
    $orderQuery = "SELECT o.order_id, o.order_status, o.order_date, 
                    u.username, u.first_name, u.last_name, u.email, u.contact_number
                  FROM orders AS o
                  JOIN users AS u ON o.consumer_id = u.user_id
                  WHERE o.order_id = :order_id";
    
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $orderStmt->execute();
    $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderDetails) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }
    
    // Query to get order items with product details
    $itemsQuery = "SELECT oi.quantity, oi.price as item_price, p.name as product_name,
                      (oi.quantity * oi.price) as total_price
                   FROM orderitems AS oi
                   JOIN products AS p ON oi.product_id = p.product_id
                   WHERE oi.order_id = :order_id";
    
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $itemsStmt->execute();
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate order subtotal
    $subtotal = 0;
    foreach ($orderItems as $item) {
        $subtotal += $item['total_price'];
    }
    
    // Query to get pickup details
    $pickupQuery = "SELECT pickup_id, pickup_date, pickup_location, assigned_to, pickup_notes, pickup_status
                    FROM pickups 
                    WHERE order_id = :order_id";
    
    $pickupStmt = $conn->prepare($pickupQuery);
    $pickupStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $pickupStmt->execute();
    $pickupDetails = $pickupStmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare the response
    $response = [
        'order' => $orderDetails,
        'items' => $orderItems,
        'subtotal' => $subtotal,
        'pickup' => $pickupDetails
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Log error
    error_log("Error fetching order details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>
