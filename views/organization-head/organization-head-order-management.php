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

// Get organization_id from session
$organization_id = $_SESSION['organization_id'] ?? null;

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Fetch orders with pagination and search
$orders = $organization_id ? $orderController->getOrdersByOrganizationPaginated($organization_id, $search, $status_filter, $limit, $offset) : [];
$totalOrders = $organization_id ? $orderController->getTotalOrders($organization_id, $search, $status_filter) : 0;
$totalPages = ceil($totalOrders / $limit);

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        if ($orderController->updateOrderStatus($order_id, $new_status)) {
            $logClass->logActivity($_SESSION['user_id'], "Updated order #$order_id status to $new_status");
            $_SESSION['message'] = "Order status updated successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to update order status.";
            $_SESSION['message_type'] = 'danger';
        }
        
        header("Location: organization-head-order-management.php");
        exit();
    }

    // Handle logout
    if (isset($_POST['logout'])) {
        if ($admin_user_id) {
            $logClass->logActivity($_SESSION['user_id'], "Organization Head logged out.");
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
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .table thead th {
            background-color: #198754;
            color: white;
            border-bottom: 0;
            white-space: nowrap;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .btn-action {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        .search-container {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Order Management</h1>
                    <form method="POST" class="ml-3" onsubmit="return confirm('Are you sure you want to logout?');">
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
                    <?php 
                        unset($_SESSION['message']); 
                        unset($_SESSION['message_type']);
                    ?>
                <?php endif; ?>

                <!-- Search and Filter Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" action="" class="form-inline">
                            <input type="text" name="search" class="form-control mr-2" 
                                   placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            <select id="statusFilter" class="form-control mr-2" style="width: auto;">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <input type="date" id="dateFilter" class="form-control">
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
                                    <td colspan="6" class="text-center">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-info btn-sm view-order" 
                                                    data-order-id="<?= $order['order_id'] ?>"
                                                    data-toggle="modal" 
                                                    data-target="#orderDetailsModal">
                                                <i class="bi bi-eye-fill"></i> View
                                            </button>
                                            
                                            <button class="btn btn-warning btn-sm update-status" 
                                                    data-order-id="<?= $order['order_id'] ?>"
                                                    data-current-status="<?= $order['status'] ?>"
                                                    data-toggle="modal" 
                                                    data-target="#updateStatusModal">
                                                <i class="bi bi-pencil-fill"></i> Update
                                            </button>
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
                                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <p class="text-center">Page <?= $page ?> of <?= $totalPages ?></p>
                </nav>
            </main>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="order_id" id="updateOrderId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_status">New Status</label>
                            <select name="new_status" id="new_status" class="form-control" required>
                                <option value="pending">Pending</option>
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Order Details
            $('.view-order').click(function() {
                const orderId = $(this).data('order-id');
                $.get(`get-order-details.php?order_id=${orderId}`, function(data) {
                    $('#orderDetailsContent').html(data);
                });
            });

            // Update Status
            $('.update-status').click(function() {
                const orderId = $(this).data('order-id');
                const currentStatus = $(this).data('current-status');
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
            };

            $('#statusFilter, #dateFilter').on('change', filterOrders);
        });
    </script>
</body>
</html>