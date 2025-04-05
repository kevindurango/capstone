<?php
session_start();

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Order.php';
require_once '../../models/Log.php';

$orderClass = new Order();
$logClass = new Log();

// Add debug logging
error_log("Starting order fetching process...");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get order statistics with debug info
$pendingOrders = $orderClass->getOrdersByStatus('pending'); // Note: case matters - use 'pending' not 'PENDING'
error_log("Pending orders count: " . count($pendingOrders));

$totalOrders = $orderClass->getTotalOrderCount();
error_log("Total orders: " . $totalOrders);

$todayOrders = $orderClass->getTodayOrderCount();
error_log("Today's orders: " . $todayOrders);

$totalRevenue = $orderClass->getTotalRevenue();
error_log("Total revenue: " . $totalRevenue);

// Add error checking for empty results
if (empty($pendingOrders)) {
    error_log("No pending orders found - verify database connection and data");
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['new_status'];
        $orderClass->updateOrderStatus($orderId, $newStatus);
        $logClass->logActivity($_SESSION['user_id'], "Updated order #$orderId status to $newStatus");
        header("Location: manager-order-oversight.php");
        exit();
    }
    
    if (isset($_POST['logout'])) {
        $logClass->logActivity($_SESSION['user_id'], "Manager logged out");
        session_destroy();
        header("Location: manager-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/manager-orders.css">
</head>
<body>
    <!-- Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-cart-check"></i> ORDER MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white custom-card">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Order Management</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 gradient-text">
                            <i class="bi bi-cart-check"></i> Order Management
                        </h1>
                        <p class="text-muted mb-0">Monitor and process customer orders</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success mr-2" onclick="exportOrderReport()">
                            <i class="bi bi-file-earmark-excel"></i> Export Report
                        </button>
                        <form method="POST" class="mb-0">
                            <button type="submit" name="logout" class="btn btn-danger logout-btn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-primary">
                                <i class="bi bi-cart"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $totalOrders ?></h3>
                                <p>Total Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-warning">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= count($pendingOrders) ?></h3>
                                <p>Pending Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-success">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $todayOrders ?></h3>
                                <p>Today's Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-info">
                                <i class="bi bi-cash"></i>
                            </div>
                            <div class="stat-details">
                                <h3>₱<?= number_format($totalRevenue, 2) ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Filter Section -->
                <div class="filter-section mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="bi bi-search"></i> Search Orders</label>
                                <input type="text" id="searchOrder" class="form-control" placeholder="Order ID, Customer...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="bi bi-funnel"></i> Filter Status</label>
                                <select id="statusFilter" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="canceled">Canceled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="bi bi-calendar3"></i> Date Range</label>
                                <input type="date" id="dateFilter" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="bi bi-sort-alpha-down"></i> Sort By</label>
                                <select id="sortFilter" class="form-control">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="highest">Highest Amount</option>
                                    <option value="lowest">Lowest Amount</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="orders-table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingOrders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> No orders found.
                                                <?php if (isset($_SESSION['debug'])): ?>
                                                    <br>
                                                    <small>Debug: Check database connection and orders table.</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle mr-2"></i>
                                                <div>
                                                    <div class="font-weight-bold">
                                                        <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($order['email'] ?? 'No email') ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= intval($order['item_count']) ?> items</td>
                                        <td>₱<?= number_format(floatval($order['total_amount']), 2) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= getStatusBadgeClass($order['status'] ?? 'pending') ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'] ?? 'pending')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info view-order" 
                                                        data-id="<?= $order['order_id'] ?>"
                                                        title="View Order Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-success update-status" 
                                                        data-id="<?= $order['order_id'] ?>"
                                                        title="Update Status">
                                                    <i class="bi bi-arrow-up-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-circle"></i> Update Order Status</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="status_order_id">
                        <div class="form-group">
                            <label>New Status</label>
                            <select name="new_status" class="form-control" required>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/js/manager-orders.js"></script>
</body>
</html>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>