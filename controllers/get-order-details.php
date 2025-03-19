<?php
require_once '../models/Database.php';

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo '<p class="text-danger">Invalid order ID.</p>';
    exit();
}

$order_id = htmlspecialchars($_GET['order_id']);

// Database connection
$database = new Database();
$conn = $database->connect();

// Fetch order details
$query = "SELECT o.order_id, o.order_date, u.username AS consumer_name
          FROM orders o
          JOIN users u ON o.consumer_id = u.user_id
          WHERE o.order_id = :order_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);

if ($stmt->execute() && $stmt->rowCount() > 0) {
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Display order details
    echo '<h5>Order Details</h5>';
    echo '<p><strong>Order ID:</strong> ' . htmlspecialchars($order['order_id']) . '</p>';
    echo '<p><strong>Order Date:</strong> ' . htmlspecialchars($order['order_date']) . '</p>';
    echo '<p><strong>Consumer Name:</strong> ' . htmlspecialchars($order['consumer_name']) . '</p>';
} else {
    echo '<p class="text-danger">Order not found.</p>';
}
?>
