<?php
// Start the session to track login status
session_start();

// Check if the user is logged in as a Manager, if not redirect to the login page
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

// Include necessary models
require_once '../../models/Order.php'; // Assuming you have an Order model
require_once '../../models/Log.php'; // Assuming you have a Log model

// Instantiate necessary classes
$orderClass = new Order();
$logClass = new Log();

// Get Manager User ID from Session - Crucial for logging
$manager_user_id = $_SESSION['manager_user_id'] ?? null; // Assuming you store the manager's user_id in the session
if (!$manager_user_id) {
    error_log("Manager user ID not found in session. Logging will be incomplete.");
}

// Log the Manager Login activity
if ($_SESSION['manager_logged_in'] === true && $_SESSION['role'] === 'Manager' && isset($manager_user_id)) {
    $logClass->logActivity($manager_user_id, "Manager logged in.");
}

// Fetch orders using the existing method in your Order model
$orders = $orderClass->getOrdersWithPickupDetails(); // Fetch orders with pickup details

// Handle logout functionality
if (isset($_POST['logout'])) {
    // Log the activity
    if ($manager_user_id) {
        $logClass->logActivity($manager_user_id, "Manager logged out.");
    }

    // Destroy session and log out the user
    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submissions with CSRF validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Example: If you want to update an order status
    if (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['order_status'];

        // Allowed statuses for updating
        $allowed_statuses = ['pending', 'completed', 'canceled'];

        if (!in_array($new_status, $allowed_statuses)) {
            $_SESSION['message'] = "Invalid status update request!";
            $_SESSION['message_type'] = 'danger';
            header("Location: manager-sales-management.php");
            exit();
        }

        // Log the activity of changing the status
        if ($manager_user_id) {
            $logClass->logActivity($manager_user_id, "Attempted to update order ID $order_id to status: $new_status");
        }

        // Update order status if valid
        if ($orderClass->updateOrderStatus($order_id, $new_status)) {
            // Log the successful status update activity
            if ($manager_user_id) {
                $logClass->logActivity($manager_user_id, "Updated order status for order ID: $order_id to $new_status.");
            }
            $_SESSION['message'] = "Order status updated successfully.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to update order status.";
            $_SESSION['message_type'] = 'danger';
        }

        header("Location: manager-sales-management.php"); // Redirect to reload the page with the new status
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <style>
        .table-container {
            position: relative;
            overflow-x: auto;
            max-height: 450px;
        }
        .table-container thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .table-container tbody td {
            vertical-align: middle;
        }
        .btn-primary, .btn-success, .btn-warning, .btn-danger {
            font-weight: bold;
        }
        .modal-header {
            background-color: #f7f7f7;
        }
        .modal-footer button {
            width: 120px;
        }
        .modal-title {
            font-weight: bold;
        }
        .table-bordered td, .table-bordered th {
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Sales Management</h1>
                    <div class="d-flex">
                        <form method="POST" class="ml-3">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Display Message -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['message']); // Clear message after display ?>
                <?php endif; ?>

                <!-- Order Listing -->
                <div class="card custom-card table-container">
                    <div class="card-body">
                        <h5 class="card-title">Order List</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Consumer Name</th>
                                        <th>Order Status</th>
                                        <th>Order Date</th>
                                        <th>Pickup Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                                            <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                            <td><?= htmlspecialchars($order['order_status']) ?></td>
                                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                                            <td><?= htmlspecialchars($order['pickup_status']) ?></td>
                                            <td>
                                                <!-- View Details -->
                                                <button class="btn btn-info btn-sm view-order-btn"
                                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>"
                                                        data-toggle="modal" data-target="#orderDetailsModal">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <!-- Update Order Status -->
                                                <button class="btn btn-warning btn-sm update-order-status-btn"
                                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>"
                                                        data-order-status="<?= htmlspecialchars($order['order_status']) ?>"
                                                        data-toggle="modal" data-target="#updateOrderStatusModal">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Update Order Status Modal -->
    <div class="modal fade" id="updateOrderStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateOrderStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="order_id" id="update_order_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateOrderStatusModalLabel">Update Order Status</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="order_status">New Status</label>
                            <select class="form-control" id="order_status" name="order_status">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_order_status" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // JavaScript to populate modal with order ID and current status
        $(document).ready(function() {
            $('.update-order-status-btn').click(function() {
                var orderId = $(this).data('order-id');
                var orderStatus = $(this).data('order-status');
                $('#update_order_id').val(orderId);
                $('#order_status').val(orderStatus);
            });

            // JavaScript for View Details button
            $('.view-order-btn').click(function() {
                var orderId = $(this).data('order-id');
                // Load order details using AJAX
                $.ajax({
                    url: 'order-details.php?order_id=' + orderId,
                    type: 'GET',
                    success: function(data) {
                        $('#orderDetailsContent').html(data); // Load the response into the modal body
                    },
                    error: function() {
                        $('#orderDetailsContent').html('<p>Error loading order details.</p>');
                    }
                });
            });
        });
    </script>
</body>
</html>