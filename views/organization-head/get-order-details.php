<?php
session_start();

// Check if user is logged in as Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    echo "Unauthorized access";
    exit();
}

require_once '../../controllers/OrderController.php';

$orderController = new OrderController();

// Get order_id from request
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo "Invalid order ID";
    exit();
}

try {
    // Get order details
    $order = $orderController->getOrderById($order_id);
    
    if (!$order) {
        echo "Order not found";
        exit();
    }
    
    // Standardize status field - ensure we're using the right status field name
    if (!isset($order['status']) && isset($order['order_status'])) {
        $order['status'] = $order['order_status'];
    }
    
    // Get order items
    $orderItems = $orderController->getOrderItems($order_id);
    
    // Status badge color
    $statusClass = '';
    switch(strtolower($order['status'] ?? 'pending')) {
        case 'pending':
            $statusClass = 'warning';
            break;
        case 'completed':
            $statusClass = 'success';
            break;
        case 'canceled': // Database value
            $statusClass = 'danger';
            $displayStatus = 'Canceled'; // Display value
            break;
        default:
            $statusClass = 'secondary';
            $displayStatus = ucfirst($order['status'] ?? 'Pending'); // Default display
    }

    // If not set by the switch case, use capitalized status
    if (!isset($displayStatus)) {
        $displayStatus = ucfirst($order['status'] ?? 'Pending');
    }
} catch (Exception $e) {
    echo "Error retrieving order details: " . $e->getMessage();
    exit();
}
?>

<div class="row">
    <div class="col-md-6">
        <h5>Order Information</h5>
        <table class="table table-bordered">
            <tr>
                <th>Order ID</th>
                <td>#<?= htmlspecialchars($order['order_id']) ?></td>
            </tr>
            <tr>
                <th>Customer</th>
                <td><?= htmlspecialchars($order['customer_name']) ?></td>
            </tr>
            <tr>
                <th>Date</th>
                <td><?= date('F d, Y h:i A', strtotime($order['order_date'])) ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="badge badge-<?= $statusClass ?>"><?= $displayStatus ?></span></td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td>₱<?= number_format($order['total_amount'], 2) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Customer Information</h5>
        <table class="table table-bordered">
            <tr>
                <th>Email</th>
                <td><?= htmlspecialchars($order['email'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Contact</th>
                <td><?= htmlspecialchars($order['contact_number'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?= htmlspecialchars($order['address'] ?? $order['shipping_address'] ?? 'N/A') ?></td>
            </tr>
            <?php if (isset($order['shipping_status'])): ?>
            <tr>
                <th>Shipping Status</th>
                <td>
                    <span class="badge badge-<?= $order['shipping_status'] == 'delivered' ? 'success' : 'info' ?>">
                        <?= ucfirst(htmlspecialchars($order['shipping_status'])) ?>
                    </span>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<h5 class="mt-4">Order Items</h5>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orderItems)): ?>
                <tr>
                    <td colspan="4" class="text-center">No items found in this order.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>₱<?= number_format($item['price'], 2) ?></td>
                        <td>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total:</th>
                <th>₱<?= number_format($order['total_amount'], 2) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<?php if (isset($order['pickup_details']) && !empty($order['pickup_details'])): ?>
<div class="mt-4">
    <h5>Pickup Details</h5>
    <div class="card">
        <div class="card-body">
            <?= nl2br(htmlspecialchars($order['pickup_details'])) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mt-4">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    <?php 
        // Show update button only for pending orders
        $currentStatus = strtolower($order['status'] ?? 'pending');
        if ($currentStatus == 'pending'): 
    ?>
        <button type="button" class="btn btn-primary update-status" 
                data-order-id="<?= $order['order_id'] ?>"
                data-current-status="<?= $currentStatus ?>" 
                data-toggle="modal" 
                data-target="#updateStatusModal" 
                data-dismiss="modal">
            <i class="bi bi-pencil-square"></i> Update Status
        </button>
    <?php endif; ?>
</div>
