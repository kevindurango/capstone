<?php
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    echo "Unauthorized access";
    exit();
}

require_once '../../models/Database.php';

// Check if order ID is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo '<div class="alert alert-danger">Invalid order ID</div>';
    exit();
}

$order_id = (int)$_GET['order_id'];

// Database connection
$database = new Database();
$conn = $database->connect();

try {
    // Fetch order details
    $orderQuery = "SELECT o.*, u.username, u.first_name, u.last_name, u.contact_number, u.address 
                  FROM orders o
                  JOIN users u ON o.consumer_id = u.user_id
                  WHERE o.order_id = :order_id";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $orderStmt->execute();
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo '<div class="alert alert-danger">Order not found</div>';
        exit();
    }
    
    // Fetch order items
    $itemsQuery = "SELECT oi.*, p.name, p.image 
                  FROM orderitems oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = :order_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch pickup details
    $pickupQuery = "SELECT * FROM pickups WHERE order_id = :order_id";
    $pickupStmt = $conn->prepare($pickupQuery);
    $pickupStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $pickupStmt->execute();
    $pickup = $pickupStmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate order total
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Format the output HTML
    ?>
    <div class="order-details">
        <div class="row">
            <div class="col-md-6">
                <h5>Order Information</h5>
                <table class="table table-bordered table-sm">
                    <tr>
                        <th>Order ID</th>
                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td><?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge badge-<?= $order['order_status'] === 'completed' ? 'success' : 
                                ($order['order_status'] === 'pending' ? 'warning' : 'danger') ?>">
                                <?= ucfirst(htmlspecialchars($order['order_status'])) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Customer Information</h5>
                <table class="table table-bordered table-sm">
                    <tr>
                        <th>Name</th>
                        <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td><?= htmlspecialchars($order['contact_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?= htmlspecialchars($order['address']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <h5>Pickup Details</h5>
            <?php if ($pickup): ?>
                <table class="table table-bordered table-sm">
                    <tr>
                        <th>Pickup Date</th>
                        <td><?= date('F j, Y, g:i a', strtotime($pickup['pickup_date'])) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge badge-<?= $pickup['pickup_status'] === 'completed' ? 'success' : 
                                ($pickup['pickup_status'] === 'pending' ? 'warning' : 
                                ($pickup['pickup_status'] === 'cancelled' ? 'danger' : 'info')) ?>">
                                <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td>Municipal Agriculture Office</td>
                    </tr>
                    <?php if (!empty($pickup['contact_person'])): ?>
                        <tr>
                            <th>Contact Person</th>
                            <td><?= htmlspecialchars($pickup['contact_person']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($pickup['pickup_notes'])): ?>
                        <tr>
                            <th>Notes</th>
                            <td><?= nl2br(htmlspecialchars($pickup['pickup_notes'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No pickup information available</div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4">
            <h5>Order Items</h5>
            <?php if (count($items) > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($item['name']) ?>
                                </td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Total:</th>
                            <th>₱<?= number_format($total, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No items found for this order</div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching order details: ' . $e->getMessage() . '</div>';
}
?>
