<?php
require_once '../models/Order.php';
require_once '../models/Log.php';

// Check if the request has an order_id parameter
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo '<div class="alert alert-danger">Missing order ID</div>';
    exit;
}

$orderId = intval($_GET['order_id']);
$orderClass = new Order();
$logClass = new Log();

// Get the order details
$orderDetails = $orderClass->getOrderWithItems($orderId);

// If order isn't found
if (!$orderDetails) {
    echo '<div class="alert alert-warning">Order not found</div>';
    exit;
}

// Log this view for audit purposes
$logClass->logActivity('view_order', "Viewed order #$orderId details");

// Get pickup info if it exists
$pickupInfo = $orderClass->getPickupDetails($orderId);
?>

<div class="order-details">
    <h5 class="border-bottom pb-2">Order #<?= htmlspecialchars($orderId) ?></h5>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <h6><strong>Order Information</strong></h6>
            <p><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($orderDetails['order_date'])) ?></p>
            <p><strong>Order Status:</strong> 
                <span class="badge badge-<?= getStatusBadge($orderDetails['order_status']) ?>">
                    <?= ucfirst(htmlspecialchars($orderDetails['order_status'])) ?>
                </span>
            </p>
            <p><strong>Customer:</strong> <?= htmlspecialchars($orderDetails['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($orderDetails['email']) ?></p>
        </div>
        
        <div class="col-md-6">
            <h6><strong>Pickup Information</strong></h6>
            <?php if ($pickupInfo): ?>
                <p><strong>Pickup Status:</strong> 
                    <span class="badge badge-<?= getStatusBadge($pickupInfo['pickup_status']) ?>">
                        <?= ucfirst(htmlspecialchars($pickupInfo['pickup_status'])) ?>
                    </span>
                </p>
                <p><strong>Assigned To:</strong> <?= htmlspecialchars($pickupInfo['assigned_to'] ?: 'Not assigned') ?></p>
                <p><strong>Pickup Location:</strong> <?= htmlspecialchars($pickupInfo['pickup_location'] ?: 'Not set') ?></p>
                <p><strong>Scheduled for:</strong> 
                    <?= $pickupInfo['pickup_date'] ? date('F j, Y, g:i a', strtotime($pickupInfo['pickup_date'])) : 'Not scheduled' ?>
                </p>
            <?php else: ?>
                <p>No pickup details available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($orderDetails['items'])): ?>
        <h6 class="mt-4"><strong>Order Items</strong></h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderDetails['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td>₱<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total Amount:</th>
                        <th>₱<?= number_format($orderDetails['total_amount'], 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info mt-4">No order items found.</div>
    <?php endif; ?>
    
    <?php if (!empty($pickupInfo['pickup_notes'])): ?>
        <div class="mt-3">
            <h6><strong>Pickup Notes:</strong></h6>
            <p><?= nl2br(htmlspecialchars($pickupInfo['pickup_notes'])) ?></p>
        </div>
    <?php endif; ?>
</div>

<?php
function getStatusBadge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending': return 'warning';
        case 'assigned': return 'info';
        case 'in_transit': return 'primary';
        case 'completed': return 'success';
        case 'cancelled': 
        case 'canceled': return 'danger';
        default: return 'secondary';
    }
}
?>
