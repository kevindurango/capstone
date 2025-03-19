<?php
// Start the session to track login status
session_start();

// Check if the user is logged in as an Organization Head, if not redirect to the login page
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

// Include necessary models
require_once '../../models/Order.php';
require_once '../../models/Log.php';
require_once '../../models/Dashboard.php';

// Instantiate necessary classes
$orderClass = new Order();
$logClass = new Log();
$dashboard = new Dashboard();

// Get Organization Head User ID from Session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;
if (!$organization_head_user_id) {
    error_log("Organization head user ID not found in session. Logging will be incomplete.");
}

// Log the Organization Head Login activity
if ($_SESSION['organization_head_logged_in'] === true && $_SESSION['role'] === 'Organization Head' && isset($organization_head_user_id)) {
    $logClass->logActivity($organization_head_user_id, "Organization Head logged in.");
}

// Fetch orders with pickup details
$orders = $orderClass->getOrdersWithPickupDetails();

// Fetch dashboard metrics
$productCount = $dashboard->getProductCount();
$orderCountPending = $dashboard->getOrderCountByStatus('pending');
$orderCountCompleted = $dashboard->getOrderCountByStatus('completed');
$orderCountCanceled = $dashboard->getOrderCountByStatus('canceled');
$salesData = $dashboard->getSalesData();
$feedbackCount = $dashboard->getFeedbackCount();

// Handle logout functionality
if (isset($_POST['logout'])) {
    if ($organization_head_user_id) {
        $logClass->logActivity($organization_head_user_id, "Organization Head logged out.");
    }
    session_unset();
    session_destroy();
    header("Location: organization-head-login.php");
    exit();
}

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && 
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    if (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['order_status'];
        
        // Validate status
        $allowed_statuses = ['pending', 'completed', 'canceled'];
        if (!in_array($new_status, $allowed_statuses)) {
            $_SESSION['message'] = "Invalid status update request!";
            $_SESSION['message_type'] = 'danger';
            header("Location: organization-head-dashboard.php");
            exit();
        }
        
        // Log and update status
        if ($organization_head_user_id) {
            $logClass->logActivity($organization_head_user_id, "Attempted to update order ID $order_id to status: $new_status");
        }
        
        if ($orderClass->updateOrderStatus($order_id, $new_status)) {
            if ($organization_head_user_id) {
                $logClass->logActivity($organization_head_user_id, "Updated order status for order ID: $order_id to $new_status.");
            }
            $_SESSION['message'] = "Order status updated successfully.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to update order status.";
            $_SESSION['message_type'] = 'danger';
        }
        
        header("Location: organization-head-dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Head Dashboard</title>
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>
            
            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main">
                <!-- Header with Title and Logout Button -->
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Organization Head Dashboard</h1>
                    <form method="POST" class="ml-3">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
                
                <!-- Alert Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <section id="statistics">
                    <div class="row mb-4">
                        <!-- Products Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-box-fill text-warning card-icon"></i>
                                <div class="card-title">Products</div>
                                <div class="card-text"><?= $productCount ?></div>
                            </div>
                        </div>
                        
                        <!-- Orders Pending Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-clock-fill text-primary card-icon"></i>
                                <div class="card-title">Orders Pending</div>
                                <div class="card-text"><?= $orderCountPending ?></div>
                            </div>
                        </div>
                        
                        <!-- Orders Completed Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-check-circle-fill text-success card-icon"></i>
                                <div class="card-title">Orders Completed</div>
                                <div class="card-text"><?= $orderCountCompleted ?></div>
                            </div>
                        </div>
                        
                        <!-- Feedback Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-chat-dots-fill text-info card-icon"></i>
                                <div class="card-title">Feedback</div>
                                <div class="card-text"><?= $feedbackCount ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Order Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="chart-title">Order Status Distribution</div>
                                <div class="chart-container">
                                    <canvas id="orderStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sales Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="chart-title">Monthly Sales</div>
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Order List Table -->
                <div class="card table-container">
                    <h5 class="card-title">Recent Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Consumer</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Pickup Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                                            <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $order['order_status'] === 'completed' ? 'success' : 
                                                    ($order['order_status'] === 'canceled' ? 'danger' : 'primary') ?>">
                                                    <?= htmlspecialchars($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $order['pickup_status'] === 'completed' ? 'success' : 
                                                    ($order['pickup_status'] === 'cancelled' ? 'danger' : 'info') ?>">
                                                    <?= htmlspecialchars($order['pickup_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-info btn-sm view-order-btn" 
                                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>" 
                                                        data-toggle="modal" data-target="#orderDetailsModal">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm update-order-status-btn" 
                                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>" 
                                                        data-order-status="<?= htmlspecialchars($order['order_status']) ?>" 
                                                        data-toggle="modal" data-target="#updateOrderStatusModal">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_order_status" class="btn btn-primary">Update Status</button>
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
                    <!-- Content will be loaded via AJAX -->
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
        $(document).ready(function() {
            // Update Order Status Modal Population
            $('.update-order-status-btn').click(function() {
                const orderId = $(this).data('order-id');
                const orderStatus = $(this).data('order-status');
                $('#update_order_id').val(orderId);
                $('#order_status').val(orderStatus);
            });
            
            // View Order Details AJAX
            $('.view-order-btn').click(function() {
                const orderId = $(this).data('order-id');
                $.ajax({
                    url: 'order-details.php?order_id=' + orderId,
                    type: 'GET',
                    success: function(data) {
                        $('#orderDetailsContent').html(data);
                    },
                    error: function() {
                        $('#orderDetailsContent').html('<div class="alert alert-danger">Error loading order details.</div>');
                    }
                });
            });
            
            // Order Status Chart
            const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
            new Chart(orderStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Completed', 'Canceled'],
                    datasets: [{
                        data: [<?= $orderCountPending ?>, <?= $orderCountCompleted ?>, <?= $orderCountCanceled ?>],
                        backgroundColor: ['#007bff', '#28a745', '#dc3545'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            titleFont: { size: 14 },
                            bodyFont: { size: 14 }
                        }
                    }
                }
            });
            
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_keys($salesData)) ?>,
                    datasets: [{
                        label: 'Monthly Sales',
                        data: <?= json_encode(array_values($salesData)) ?>,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: '#28a745',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#28a745',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            titleFont: { size: 14 },
                            bodyFont: { size: 14 },
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    return 'Sales: ₱' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Amount (₱)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>