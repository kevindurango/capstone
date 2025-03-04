<?php
session_start();

// Check if the user is logged in as a Manager
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    //Instead of redirecting, send a 403 Forbidden response for AJAX requests
    header("HTTP/1.1 403 Forbidden");
    echo "You are not authorized to view this content.";
    exit();
}

require_once '../../models/Order.php';
$orderClass = new Order();

// Get the order ID from the query string
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0; // Get and sanitize

// Validate the order ID
if ($order_id <= 0) {
    echo "Invalid order ID.";
    exit;
}

// Fetch the order details
$order = $orderClass->getOrderById($order_id);

// Authorize:  Check if the manager is authorized to view this order!!!
//  This is a CRITICAL security check.  Adapt this to your application's logic.
// Example: Check if the order belongs to the manager's department.
function isOrderAccessibleByManager($order, $manager_id) {
    // Implement your authorization logic here.  Replace this placeholder.
    // For example, check if the order's 'department_id' matches the manager's 'department_id'.
    //  Return true if authorized, false otherwise.
    //  This is just a PLACEHOLDER.  You *must* implement your own logic.
    return true; //PLACEHOLDER
}


if (!isOrderAccessibleByManager($order, $_SESSION['user_id'])) {
    //Instead of redirecting, send a 403 Forbidden response for AJAX requests
    header("HTTP/1.1 403 Forbidden");
    echo "You are not authorized to view this content.";
    exit();
}

if (!$order) {
    echo "Order not found.";
    exit;
}

//Now display the order details (after the code that follows.)

?>

<div class="container">
    <h1>Order Details</h1>

    <div class="order-details-container">
        <h2>Order Information</h2>
        <p>Order ID: <?= htmlspecialchars($order['order_id']) ?></p>
        <p>Consumer ID: <?= htmlspecialchars($order['consumer_id']) ?></p>
        <p>Order Status: <?= htmlspecialchars($order['order_status']) ?></p>
        <p>Order Date: <?= htmlspecialchars($order['order_date']) ?></p>
        <p>Pickup Details: <?= htmlspecialchars($order['pickup_details']) ?></p>
    </div>

    <div class="order-details-container">
        <h2>Order Items</h2>
        <?php if (isset($order['items']) && !empty($order['items'])): ?>
            <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <p>Product ID: <?= htmlspecialchars($item['product_id']) ?></p>
                    <p>Quantity: <?= htmlspecialchars($item['quantity']) ?></p>
                    <p>Price: <?= htmlspecialchars($item['price']) ?></p>
                    <!-- Display other item details as needed -->
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No items found for this order.</p>
        <?php endif; ?>
    </div>

</div>
