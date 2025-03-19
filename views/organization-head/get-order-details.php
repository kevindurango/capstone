<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true) {
    http_response_code(403);
    echo "Unauthorized access";
    exit();
}

require_once '../../controllers/OrderController.php';

if (!isset($_GET['order_id'])) {
    echo "Order ID is required";
    exit();
}

$orderController = new OrderController();
$order = $orderController->getOrderDetails($_GET['order_id']);

if (!$order) {
    echo "Order not found";
    exit();
}
?>

<div class="order-details">
    <div class="row mb-3">
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <p>Name: <?= htmlspecialchars($order['customer_name']) ?></p>
            <p>Email: <?= htmlspecialchars($order['customer_email']) ?></p>
            <p>Phone: <?= htmlspecialchars($order['customer_phone']) ?></p>
        </div>
        <div class="col-md-6">
            <h6>Order Information</h6>
            <p>Order ID: #<?= htmlspecialchars($order['order_id']) ?></p>
            <p>Date: <?= date('M d, Y H:i', strtotime($order['order_date'])) ?></p>
            <p>Status: <span class="status-badge status-<?= strtolower($order['status']) ?>"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></p>
        </div>
    </div>

    <h6>Order Items</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td>₱<?= number_format($item['price'], 2) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total:</th>
                    <th>₱<?= number_format($order['total_amount'], 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($order['notes'])): ?>
        <div class="mt-3">
            <h6>Order Notes</h6>
            <p class="text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
        </div>
    <?php endif; ?>
</div>