<?php
session_start();

// Check if user is logged in as Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

require_once '../../controllers/OrderController.php';
require_once '../../models/Log.php';

$orderController = new OrderController();
$logClass = new Log();

// Get organization_head_user_id from session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Get all consumer orders
try {
    // Check if the method exists to avoid fatal errors
    if (method_exists($orderController, 'getOrders')) {
        $orders = $orderController->getOrders();
        
        // Ensure status field is properly mapped from order_status
        foreach ($orders as &$order) {
            // Make sure we're using the correct status field name
            if (!isset($order['status']) && isset($order['order_status'])) {
                $order['status'] = $order['order_status'];
            }
        }
    } else {
        // Fallback if method doesn't exist
        $orders = [];
        error_log("OrderController::getOrders() method does not exist");
        $_SESSION['message'] = "Error: Order functionality is not fully implemented. Please contact the administrator.";
        $_SESSION['message_type'] = 'danger';
    }
    
    // Apply search filter if provided
    if (!empty($search) && !empty($orders)) {
        $filteredOrders = [];
        foreach ($orders as $order) {
            // Search by order ID, customer name, or other relevant fields
            if (
                stripos($order['order_id'], $search) !== false || 
                stripos($order['customer_name'], $search) !== false ||
                stripos($order['status'] ?? '', $search) !== false
            ) {
                $filteredOrders[] = $order;
            }
        }
        $orders = $filteredOrders;
    }
    
    // Apply status filter if provided
    if (!empty($status_filter) && !empty($orders)) {
        $filteredOrders = [];
        foreach ($orders as $order) {
            if (strtolower($order['status'] ?? '') === strtolower($status_filter)) {
                $filteredOrders[] = $order;
            }
        }
        $orders = $filteredOrders;
    }
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $_SESSION['message'] = "Error retrieving orders. Please try again later.";
    $_SESSION['message_type'] = 'danger';
    $orders = [];
}

// Pagination
$totalOrders = count($orders);
$totalPages = ceil($totalOrders / $limit) ?: 1; // Ensure at least 1 page
$orders = array_slice($orders, $offset, $limit);

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        try {
            if ($orderController->updateOrderStatus($order_id, $new_status)) {
                $logClass->logActivity($organization_head_user_id, "Updated order #$order_id status to $new_status");
                $_SESSION['message'] = "Order status updated successfully!";
                $_SESSION['message_type'] = 'success';
                
                // Force page refresh to show updated status
                header("Location: organization-head-order-management.php");
                exit();
            } else {
                $_SESSION['message'] = "Failed to update order status. Please check database logs.";
                $_SESSION['message_type'] = 'danger';
            }
        } catch (Exception $e) {
            error_log("Error updating order status: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred while updating the order status: " . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
        
        header("Location: organization-head-order-management.php");
        exit();
    }

    // Handle logout
    if (isset($_POST['logout'])) {
        // Use organization_head_user_id
        if (isset($_SESSION['organization_head_user_id'])) {
            $logClass->logActivity($_SESSION['organization_head_user_id'], "Organization Head logged out.");
        }
        session_unset();
        session_destroy();
        header("Location: organization-head-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Organization Head Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f5f8fa;
        }
        
        .content-wrapper {
            padding: 1.5rem;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .page-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0;
        }
        
        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(to right, #198754, #20c997);
            color: white;
            border-bottom: 0;
            white-space: nowrap;
            font-weight: 600;
            padding: 1rem 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 120px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        
        .status-pending { 
            background-color: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba;
        }
        
        .status-processing { 
            background-color: #cce5ff; 
            color: #004085; 
            border: 1px solid #b8daff;
        }
        
        .status-completed { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        
        .status-cancelled { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        
        .action-btn-group {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-action {
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-action i {
            margin-right: 6px;
            font-size: 0.9rem;
        }
        
        .btn-view {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        
        .btn-update {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        
        .btn-update:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        
        .search-container {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-label {
            font-weight: 500;
            margin-right: 5px;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .filter-control {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out;
        }
        
        .filter-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .order-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            flex: 1;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card .text {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .pending-stat .icon { color: #ffc107; }
        .completed-stat .icon { color: #28a745; }
        .cancelled-stat .icon { color: #dc3545; }
        .total-stat .icon { color: #17a2b8; }
        
        .pagination .page-link {
            color: #198754;
            border-color: #e9ecef;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
        }
        
        .modal-header {
            background: linear-gradient(to right, #198754, #20c997);
            color: white;
            border-radius: calc(0.3rem - 1px) calc(0.3rem - 1px) 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-header .close {
            color: white;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .order-detail-row {
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-detail-label {
            font-weight: 600;
            color: #495057;
        }

        .order-products {
            margin-top: 1.5rem;
        }
        
        .order-product-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        .close:focus {
            outline: none;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center py-3">
        <h2><i class="bi bi-building"></i> ORGANIZATION ORDER MANAGEMENT
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 pb-5">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white shadow-sm">
                        <li class="breadcrumb-item"><a href="organization-head-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Order Management</li>
                    </ol>
                </nav>
                
                <div class="content-wrapper">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Order Management</h1>
                            <p class="page-subtitle">Track and manage customer orders</p>
                        </div>
                        <form method="POST" class="ml-3" onsubmit="return confirm('Are you sure you want to logout?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <?= $_SESSION['message'] ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php 
                            unset($_SESSION['message']); 
                            unset($_SESSION['message_type']);
                        ?>
                    <?php endif; ?>
                    
                    <!-- Order Statistics -->
                    <div class="order-stats">
                        <div class="stat-card pending-stat">
                            <div class="icon"><i class="bi bi-clock"></i></div>
                            <div class="number"><?= count(array_filter($orderController->getOrders(), function($o) { return strtolower($o['status'] ?? $o['order_status']) === 'pending'; })) ?></div>
                            <div class="text">Pending Orders</div>
                        </div>
                        <div class="stat-card completed-stat">
                            <div class="icon"><i class="bi bi-check-circle"></i></div>
                            <div class="number"><?= count(array_filter($orderController->getOrders(), function($o) { return strtolower($o['status'] ?? $o['order_status']) === 'completed'; })) ?></div>
                            <div class="text">Completed Orders</div>
                        </div>
                        <div class="stat-card cancelled-stat">
                            <div class="icon"><i class="bi bi-x-circle"></i></div>
                            <div class="number"><?= count(array_filter($orderController->getOrders(), function($o) { return strtolower($o['status'] ?? $o['order_status']) === 'canceled'; })) ?></div>
                            <div class="text">Cancelled Orders</div>
                        </div>
                        <div class="stat-card total-stat">
                            <div class="icon"><i class="bi bi-cart3"></i></div>
                            <div class="number"><?= count($orderController->getOrders()) ?></div>
                            <div class="text">Total Orders</div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-container">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <form method="GET" action="" class="d-flex align-items-center">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-search"></i>
                                            </span>
                                        </div>
                                        <input type="text" name="search" class="form-control border-left-0" 
                                               placeholder="Search by order ID, customer name..." value="<?= htmlspecialchars($search) ?>">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-success">
                                                Search
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group d-flex justify-content-md-end">
                                    <div class="me-2 mr-3">
                                        <span class="filter-label"><i class="bi bi-funnel"></i> Status:</span>
                                        <select id="statusFilter" class="filter-control form-control-sm">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div>
                                        <span class="filter-label"><i class="bi bi-calendar3"></i> Date:</span>
                                        <input type="date" id="dateFilter" class="filter-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="table-responsive table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                                <p class="mt-3 mb-0">No orders found.</p>
                                                <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php 
                                            // Standardize status 
                                            $status = isset($order['status']) ? $order['status'] : 
                                                    (isset($order['order_status']) ? $order['order_status'] : 'pending');
                                            
                                            // Determine style class based on status
                                            $statusClass = '';
                                            switch(strtolower($status)) {
                                                case 'pending':
                                                    $statusClass = 'pending';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'processing';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'completed';
                                                    break;
                                                case 'canceled': // Handle DB enum value
                                                    $statusClass = 'cancelled'; // Use UI class
                                                    $status = 'Canceled'; // Use consistent display text
                                                    break;
                                                default:
                                                    $statusClass = 'pending';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= htmlspecialchars($order['order_id']) ?></strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person-circle text-muted mr-2"></i>
                                                    <?= htmlspecialchars($order['customer_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="bi bi-calendar2 text-muted mr-1"></i> 
                                                <?= date('M d, Y', strtotime($order['order_date'])) ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock text-muted mr-1"></i>
                                                    <?= date('h:i A', strtotime($order['order_date'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong>₱<?= number_format($order['total_amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= $statusClass ?>">
                                                    <?php 
                                                        $icon = '';
                                                        switch($statusClass) {
                                                            case 'pending': $icon = 'hourglass-split'; break;
                                                            case 'processing': $icon = 'arrow-repeat'; break;
                                                            case 'completed': $icon = 'check-circle'; break;
                                                            case 'cancelled': $icon = 'x-circle'; break;
                                                        }
                                                    ?>
                                                    <i class="bi bi-<?= $icon ?> mr-1"></i>
                                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-btn-group">
                                                    <button class="btn btn-sm btn-primary view-order" 
                                                            data-order-id="<?= $order['order_id'] ?>"
                                                            data-toggle="modal" 
                                                            data-target="#orderDetailsModal">
                                                        <i class="bi bi-eye-fill"></i> View
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-update update-status" 
                                                            data-order-id="<?= $order['order_id'] ?>"
                                                            data-current-status="<?= $order['status'] ?>"
                                                            data-toggle="modal" 
                                                            data-target="#updateStatusModal">
                                                        <i class="bi bi-pencil"></i> Update
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center mt-5">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">First</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">Last</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <p class="text-center text-muted">
                            Showing page <?= $page ?> of <?= $totalPages ?>
                            <span class="ml-2">
                                (<?= count($orders) ?> of <?= $totalOrders ?> orders)
                            </span>
                        </p>
                    </nav>
                </div>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-3">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary" id="printOrderBtn">
                        <i class="bi bi-printer"></i> Print Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Update Order Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="order_id" id="updateOrderId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_status" class="font-weight-bold">New Status</label>
                            <select name="new_status" id="new_status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <small><i class="bi bi-info-circle"></i> Note: Status updates will be immediately visible to customers.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Order Details
            $('.view-order').click(function() {
                const orderId = $(this).data('order-id');
                $.get(`../../ajax/get-order-details.php?order_id=${orderId}`, function(data) {
                    $('#orderDetailsContent').html(data);
                }).fail(function() {
                    $('#orderDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Failed to load order details. Please try again.
                        </div>
                    `);
                });
            });

            // Update Status
            $('.update-status').click(function() {
                const orderId = $(this).data('order-id');
                let currentStatus = $(this).data('current-status');
                
                // Make sure we map cancelled to canceled for the form
                if (currentStatus === 'cancelled') {
                    currentStatus = 'canceled';
                }
                
                $('#updateOrderId').val(orderId);
                $('#new_status').val(currentStatus);
            });

            // Filter functionality
            const filterOrders = () => {
                const statusFilter = $('#statusFilter').val().toLowerCase();
                const dateFilter = $('#dateFilter').val();

                $('tbody tr').each(function() {
                    const $row = $(this);
                    const status = $row.find('.status-badge').text().toLowerCase();
                    const date = $row.find('td:nth-child(3)').text();

                    const matchesStatus = !statusFilter || status.includes(statusFilter);
                    const matchesDate = !dateFilter || date.includes(dateFilter);

                    $row.toggle(matchesStatus && matchesDate);
                });
                
                // Update the "No orders found" message visibility
                const visibleRows = $('tbody tr:visible').length;
                if(visibleRows === 0 && $('tbody tr').length > 0) {
                    if($('#no-results-message').length === 0) {
                        $('tbody').append(`
                            <tr id="no-results-message">
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-search mr-2"></i>
                                    No orders match your current filters.
                                    <button id="clear-filters" class="btn btn-sm btn-outline-secondary ml-3">
                                        <i class="bi bi-x-circle"></i> Clear filters
                                    </button>
                                </td>
                            </tr>
                        `);
                        
                        $('#clear-filters').click(function() {
                            $('#statusFilter').val('');
                            $('#dateFilter').val('');
                            filterOrders();
                        });
                    }
                } else {
                    $('#no-results-message').remove();
                }
            };

            $('#statusFilter, #dateFilter').on('change', filterOrders);
            
            // Print Order
            $('#printOrderBtn').click(function() {
                const content = document.getElementById('orderDetailsContent').innerHTML;
                const printWindow = window.open('', '_blank');
                
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Order Details</title>
                            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                body { font-family: Arial, sans-serif; padding: 20px; }
                                .print-header { text-align: center; margin-bottom: 20px; }
                                table { width: 100%; border-collapse: collapse; }
                                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                                @media print {
                                    .no-print { display: none; }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="print-header">
                                <h2>Order Details</h2>
                                <p>Printed on ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                            </div>
                            <div>${content}</div>
                            <div class="mt-4 no-print">
                                <button onclick="window.print()" class="btn btn-primary">Print</button>
                                <button onclick="window.close()" class="btn btn-secondary ml-2">Close</button>
                            </div>
                        </body>
                    </html>
                `);
                
                printWindow.document.close();
            });
        });
    </script>
</body>
</html>