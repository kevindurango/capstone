<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Get order_id from request
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

// Database connection
$database = new Database();
$conn = $database->connect();

try {
    // Log the activity
    $logClass = new Log();
    if (isset($_SESSION['admin_user_id'])) {
        $logClass->logActivity($_SESSION['admin_user_id'], "Viewed details for Order #{$order_id}");
    }

    // Fetch order details
    $orderQuery = "SELECT o.*, u.username, u.first_name, u.last_name, u.contact_number, u.email, u.address 
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

    // Fetch order items
    $itemsQuery = "SELECT oi.*, p.name as product_name 
                  FROM orderitems oi 
                  LEFT JOIN products p ON oi.product_id = p.product_id 
                  WHERE oi.order_id = :order_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate subtotal
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    // Fetch pickup details if available
    $pickupQuery = "SELECT * FROM pickups WHERE order_id = :order_id";
    $pickupStmt = $conn->prepare($pickupQuery);
    $pickupStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $pickupStmt->execute();
    $pickup = $pickupStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch payment details if available
    $paymentQuery = "SELECT p.*, pm.method_name 
                    FROM payments p
                    JOIN payment_methods pm ON p.method_id = pm.method_id
                    WHERE p.order_id = :order_id";
    $paymentStmt = $conn->prepare($paymentQuery);
    $paymentStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $paymentStmt->execute();
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    // Prepare response
    $response = [
        'order' => $order,
        'items' => $items,
        'subtotal' => $subtotal,
        'pickup' => $pickup
    ];

    // Add payment details if available
    if ($payment) {
        $response['payment'] = [
            'status' => $payment['payment_status'],
            'method' => $payment['method_name'],
            'date' => $payment['payment_date'],
            'amount' => $payment['amount'],
            'transaction_reference' => $payment['transaction_reference'],
            'payment_notes' => $payment['payment_notes']
        ];
    } else {
        // No payment record found
        $response['payment'] = [
            'status' => 'not_processed',
            'method' => 'Not specified'
        ];
    }

    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
