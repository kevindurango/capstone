<?php
// Start session for authentication
session_start();

// Check if user is logged in as Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include necessary models
require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

$order_id = (int)$_GET['order_id'];

// Create database connection
$database = new Database();
$conn = $database->connect();

// Log instance
$log = new Log();

// Get Admin User ID from Session
$admin_user_id = $_SESSION['admin_user_id'] ?? null;

try {
    // Log the activity
    if ($admin_user_id) {
        $log->logActivity($admin_user_id, "Viewed details for order #$order_id");
    }

    // Get order details
    $orderQuery = "SELECT o.*, u.username, u.first_name, u.last_name, u.email, u.contact_number 
                  FROM orders o
                  JOIN users u ON o.consumer_id = u.user_id
                  WHERE o.order_id = :order_id";
    
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $orderStmt->execute();
    
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['error' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $itemsQuery = "SELECT oi.*, p.name as product_name 
                  FROM orderitems oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = :order_id";
    
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $itemsStmt->execute();
    
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate subtotal
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }
    
    // Get pickup details if available
    $pickupQuery = "SELECT * FROM pickups WHERE order_id = :order_id";
    $pickupStmt = $conn->prepare($pickupQuery);
    $pickupStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $pickupStmt->execute();
    
    $pickup = $pickupStmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'order' => $order,
        'items' => $items,
        'subtotal' => $subtotal,
        'pickup' => $pickup ?: null
    ];
    
    // Return response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
