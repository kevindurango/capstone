<?php
// Start the session to check if the user is logged in
session_start();

// Check if the user is logged in as a Manager, if not redirect to the login page
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Order.php'; 

$orderClass = new Order();

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $orderDetails = $orderClass->getOrderDetails($order_id); // Assuming getOrderDetails fetches all details including pickup info

    if ($orderDetails) {
        ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Order ID</td>
                        <td><?= htmlspecialchars($orderDetails['order_id']) ?></td>
                    </tr>
                    <tr>
                        <td>Consumer Name</td>
                        <td><?= htmlspecialchars($orderDetails['consumer_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Order Date</td>
                        <td><?= htmlspecialchars($orderDetails['order_date']) ?></td>
                    </tr>
                    <tr>
                        <td>Order Status</td>
                        <td><?= htmlspecialchars($orderDetails['order_status']) ?></td>
                    </tr>
                    <tr>
                        <td>Pickup Status</td>
                        <td><?= htmlspecialchars($orderDetails['pickup_status']) ?></td>
                    </tr>
                    <tr>
                        <td>Pickup Location</td>
                        <td><?= htmlspecialchars($orderDetails['pickup_location']) ?></td>
                    </tr>
                    <tr>
                        <td>Pickup Date</td>
                        <td><?= htmlspecialchars($orderDetails['pickup_date']) ?></td>
                    </tr>
                    <tr>
                        <td>Assigned To</td>
                        <td><?= htmlspecialchars($orderDetails['assigned_to']) ?></td>
                    </tr>
                    <tr>
                        <td>Pickup Notes</td>
                        <td><?= htmlspecialchars($orderDetails['pickup_notes']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    } else {
        echo "<p>Order details not found.</p>";
    }
} else {
    echo "<p>Invalid request.</p>";
}
?>