<?php
// Start session to check if user is logged in
session_start();

// Generate CSRF token if not exists - add this near the top of the file
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in as an Admin, if not redirect to the login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: admin-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php'; 
require_once '../../models/Order.php'; // Add Order model include

// Database Connection
$database = new Database();
$conn = $database->connect();

// Log instance
$log = new Log();  // Instantiate the Log class
$orderClass = new Order(); // Initialize Order class

// Get Admin User ID from Session - Crucial for logging
$admin_user_id = $_SESSION['admin_user_id'] ?? null; // Assuming you store the admin's user_id in the session
if (!$admin_user_id) {
    error_log("Admin user ID not found in session. Logging will be incomplete.");
    // Handle the error appropriately - maybe redirect to login or display an error message
}

// Manage Pickup Details Logic (Handles both Add and Update)
if (isset($_POST['manage_pickup_details'])) {
    $order_id = $_POST['order_id'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_location = $_POST['pickup_location'];
    $assigned_to = $_POST['assigned_to'];
    $pickup_notes = $_POST['pickup_notes'];
    $pickup_id = $_POST['pickup_id'] ?? null;  // Get pickup_id from the form, handle if it's not set (new record)

    // Check if a pickup record already exists for this order
    $checkQuery = "SELECT pickup_id FROM pickups WHERE order_id = :order_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $existingPickup = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingPickup) {
        // Update existing record
        $pickup_id = $_POST['pickup_id'];  // Get pickup_id from the form
        $updateQuery = "UPDATE pickups
                        SET pickup_date = :pickup_date,
                            pickup_location = :pickup_location,
                            contact_person = :contact_person,
                            pickup_notes = :pickup_notes
                        WHERE pickup_id = :pickup_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
        $stmt->bindParam(':pickup_date', $pickup_date, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_location', $pickup_location, PDO::PARAM_STR);
        $stmt->bindParam(':contact_person', $assigned_to, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_notes', $pickup_notes, PDO::PARAM_STR);

        $action_description = "Updated pickup details for order ID: $order_id, Pickup ID: $pickup_id"; // Detailed log

    } else {
        // Insert new record
        $insertQuery = "INSERT INTO pickups (order_id, pickup_date, pickup_location, contact_person, pickup_notes)
                        VALUES (:order_id, :pickup_date, :pickup_location, :contact_person, :pickup_notes)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':pickup_date', $pickup_date, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_location', $pickup_location, PDO::PARAM_STR);
        $stmt->bindParam(':contact_person', $assigned_to, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_notes', $pickup_notes, PDO::PARAM_STR);

        $action_description = "Created new pickup details for order ID: $order_id";  // Detailed log
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Pickup details saved successfully!";
        $_SESSION['message_type'] = 'success';

        // Log the activity
        if ($admin_user_id) {
            $log->logActivity($admin_user_id, $action_description);
        }

    } else {
        $_SESSION['message'] = "Error saving pickup details.";
        $_SESSION['message_type'] = 'danger';

        // Log the activity (even on error)
        if ($admin_user_id) {
            $log->logActivity($admin_user_id, "Failed to save pickup details for order ID: $order_id. Error: " . print_r($stmt->errorInfo(), true));
            error_log("PDO Error: " . print_r($stmt->errorInfo(), true));  // Log PDO errors
        }
    }

    header("Location: order-oversight.php"); // Redirect to refresh the page
    exit();
}

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$totalOrders = 0;

// Fetch Orders with Pagination and Search
$query = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date,
                p.pickup_id, p.pickup_date, p.pickup_location, p.pickup_notes,
                pm.payment_id, pm.payment_status, pm.payment_date, pm.amount
          FROM orders AS o
          JOIN users AS u ON o.consumer_id = u.user_id
          LEFT JOIN pickups AS p ON o.order_id = p.order_id
          LEFT JOIN payments AS pm ON o.order_id = pm.order_id";

$whereClauses = [];
$queryParams = [];

if (!empty($search)) {
    $whereClauses[] = "(u.username LIKE :search OR o.order_status LIKE :search)";
    $queryParams[':search'] = "%$search%";
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query = $query . " ORDER BY o.order_date DESC LIMIT :limit OFFSET :offset";

// Count Query
$countQuery = "SELECT COUNT(o.order_id) AS total FROM orders AS o JOIN users AS u ON o.consumer_id = u.user_id";

if (!empty($whereClauses)) {
    $countQuery .= " WHERE " . implode(" AND ", $whereClauses);
}

// Prepare and Execute Count Query
$countStmt = $conn->prepare($countQuery);
if (!empty($queryParams)) {
    foreach ($queryParams as $param => $value) {
        $countStmt->bindValue($param, $value, PDO::PARAM_STR);
    }
}
$countStmt->execute();
$totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $limit);

// Prepare and Execute Main Query
$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if (!empty($queryParams)) {
    foreach ($queryParams as $param => $value) {
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle logout
if (isset($_POST['logout']) && isset($_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Log the activity
        if ($admin_user_id) {
            $log->logActivity($admin_user_id, "Admin logged out.");
        }

        session_unset();
        session_destroy();
        header("Location: admin-login.php");
        exit();
    }
}

// Log Search Activity
if (!empty($search)) {
    // Log the activity
    if ($admin_user_id) {
        $log->logActivity($admin_user_id, "Admin searched orders with term: '$search'. Results: $totalOrders");
    }
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get order statistics
$pendingOrders = $orderClass->getOrdersByStatus('pending');
$completedOrders = $orderClass->getOrdersByStatus('completed');
$allOrders = $orderClass->getOrdersByStatus('all');

// Calculate statistics
$totalOrders = count($allOrders);
$pendingCount = count($pendingOrders);
$totalRevenue = array_reduce($completedOrders, function($carry, $order) {
    return $carry + ($order['total_amount'] ?? 0);
}, 0);

$todayOrders = array_filter($allOrders, function($order) {
    return date('Y-m-d', strtotime($order['order_date'])) === date('Y-m-d');
});
$todayCount = count($todayOrders);

// Update the status filter options
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Oversight - Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/order-oversight.css">
    <style>
        /* Add admin header styling */
        .admin-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 10px 0;
        }
        .admin-badge {
            background-color: #6a11cb;
            color: white;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
        }

        /* Modal Select Container */
        .modal .form-group select {
            width: 100%;
            font-size: 14px;
        }

        /* Ensure text doesn't get cut off */
        .modal select option {
            word-wrap: break-word;
            white-space: normal !important;
        }
    </style>
</head>
<body>
    <!-- Add Admin Header -->
    <div class="admin-header text-center">
        <h2><i class="bi bi-shield-lock"></i> ADMIN CONTROL PANEL <span class="admin-badge">Restricted Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 order-oversight-page">
                <!-- Add Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Order Oversight</li>
                    </ol>
                </nav>

                <div class="page-header">
                    <h1 class="h2"><i class="bi bi-cart-check"></i> Order Oversight</h1>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
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

                <!-- Search and Filter Section -->
                <div class="filters-container">
                    <div class="row">
                        <div class="col-md-6">
                            <form method="GET" action="" class="form-inline">
                                <div class="input-group w-100">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                    </div>
                                    <input type="text" name="search" class="form-control border-left-0 search-control" 
                                           placeholder="Search orders by customer or status..." 
                                           value="<?= htmlspecialchars($search) ?>" aria-label="Search orders">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <div class="status-filter">
                                    <button class="btn btn-outline-secondary mr-2 filter-btn active" data-status="all">
                                        All Orders
                                    </button>
                                    <button class="btn btn-outline-warning mr-2 filter-btn" data-status="pending">
                                        Pending
                                    </button>
                                    <button class="btn btn-outline-success filter-btn" data-status="completed">
                                        Completed
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table Card -->
                <div class="card custom-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-list-check"></i> Order List</h5>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Consumer</th>
                                            <th>Status</th>
                                            <th>Order Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orders-table">
                                        <?php if (count($orders) > 0): ?>
                                            <?php foreach ($orders as $order): ?>
                                                <tr data-status="<?= strtolower($order['order_status']) ?>">
                                                    <td><span class="order-id"><?= htmlspecialchars($order['order_id']) ?></span></td>
                                                    <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                                    <td>
                                                        <?php if ($order['order_status'] === 'pending'): ?>
                                                            <span class="badge badge-warning status-badge">
                                                                <i class="bi bi-hourglass-split"></i> Pending
                                                            </span>
                                                        <?php elseif ($order['order_status'] === 'completed'): ?>
                                                            <span class="badge badge-success status-badge">
                                                                <i class="bi bi-check-circle"></i> Completed
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger status-badge">
                                                                <i class="bi bi-x-circle"></i> Canceled
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="order-date" title="<?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?>">
                                                            <i class="bi bi-calendar"></i>
                                                            <?= htmlspecialchars(date("M j, Y", strtotime($order['order_date']))) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm view-details-btn" 
                                                                data-order-id="<?= htmlspecialchars($order['order_id']) ?>">
                                                            <i class="bi bi-eye"></i> Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center no-orders-message">
                                                    <i class="bi bi-cart-x"></i>
                                                    No orders found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-container">
                            <nav>
                                <ul class="pagination justify-content-center mt-4">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php 
                                    // Show limited page numbers with ellipsis
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    
                                    if ($start > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                                        if ($start > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    for ($i = $start; $i <= $end; $i++): 
                                    ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                        </li>
                                    <?php 
                                    endfor;
                                    
                                    if ($end < $totalPages) {
                                        if ($end < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '">' . $totalPages . '</a></li>';
                                    }
                                    ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <p class="text-center text-muted">Page <?= $page ?> of <?= $totalPages ?></p>
                            </nav>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Manage Pickup Details Modal -->
    <div class="modal fade" id="managePickupModal" tabindex="-1" role="dialog" aria-labelledby="managePickupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="managePickupModalLabel">
                        <i class="bi bi-truck"></i> Manage Pickup Details
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="pickupForm">
                        <input type="hidden" name="order_id" id="order_id">
                        <input type="hidden" name="pickup_id" id="pickup_id">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="form-group">
                            <label for="pickup_date"> 
                                <i class="bi bi-calendar-event"></i> Pickup Date
                            </label>
                            <input type="datetime-local" class="form-control" id="pickup_date" name="pickup_date" required>
                        </div>

                        <div class="form-group">
                            <label for="pickup_location">
                                <i class="bi bi-geo-alt"></i> Pickup Location
                            </label>
                            <input type="text" class="form-control" value="Municipal Agriculture Office" disabled>
                            <input type="hidden" name="pickup_location" value="Municipal Agriculture Office">
                            <small class="text-muted">All pickups are processed at the Municipal Agriculture Office</small>
                        </div>

                        <div class="form-group">
                            <label for="assigned_to">
                                <i class="bi bi-person-badge"></i> Contact Person
                            </label>
                            <input type="text" class="form-control" id="assigned_to" name="assigned_to" placeholder="Person responsible for pickup">
                        </div>

                        <div class="form-group">
                            <label for="pickup_notes">
                                <i class="bi bi-card-text"></i> Pickup Notes
                            </label>
                            <textarea class="form-control" id="pickup_notes" name="pickup_notes" rows="3" placeholder="Additional instructions or notes"></textarea>
                        </div>

                        <div class="form-actions text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" name="manage_pickup_details">
                                <i class="bi bi-save"></i> Save Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Details Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" role="dialog" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel">
                        <i class="bi bi-bag-check"></i> Order Details
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-3">Loading order details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printOrderDetails">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
            // Manage Pickup Modal Functionality
            $('.manage-pickup-btn').click(function () {
                // Get data from button attributes
                var orderId = $(this).data('order-id');
                var pickupId = $(this).data('pickup-id');
                var pickupDate = $(this).data('pickup-date');
                var pickupLocation = $(this).data('pickup-location');
                var assignedTo = $(this).data('assigned-to');
                var pickupNotes = $(this).data('pickup-notes');

                // Populate the modal form
                $('#order_id').val(orderId);
                $('#pickup_id').val(pickupId);  // Add pickup_id to the form
                $('#pickup_date').val(pickupDate);
                $('#pickup_location').val(pickupLocation);
                $('#assigned_to').val(assignedTo);
                $('#pickup_notes').val(pickupNotes);

                // Update modal title with order ID
                $('#managePickupModalLabel').html('<i class="bi bi-truck"></i> Manage Pickup Details - Order #' + orderId);
            });

            // Order Details Modal Functionality
            $('.view-details-btn').click(function() {
                const orderId = $(this).data('order-id');
                console.log("Loading details for order ID:", orderId); // Add debug logging
                
                $('#viewOrderModalLabel').html('<i class="bi bi-bag-check"></i> Order Details - #' + orderId);
                $('#viewOrderModal').modal('show');

                // Show loading spinner
                $('#orderDetailsContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-3">Loading order details...</p>
                    </div>
                `);
                
                // Fetch order details via AJAX
                $.ajax({
                    url: '../../ajax/get-order-details.php',
                    type: 'GET',
                    data: { order_id: orderId },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Response received:", response); // Debug log response
                        
                        if (response.error) {
                            $('#orderDetailsContent').html(`
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Error: ${response.error}
                                </div>
                            `);
                            return;
                        }

                        // Format the order details
                        let html = `
                            <div class="order-details-container">
                                <div class="row">
                                    <!-- Customer Information -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-left-primary">
                                            <div class="card-header bg-gradient-primary text-white">
                                                <i class="bi bi-person-circle mr-2"></i> Customer Information
                                            </div>
                                            <div class="card-body">
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-person mr-2"></i>Name:</span>
                                                    <span class="info-value font-weight-bold">${response.order.username}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-envelope mr-2"></i>Email:</span>
                                                    <span class="info-value">${response.order.email}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-telephone mr-2"></i>Phone:</span>
                                                    <span class="info-value">${response.order.contact_number || 'N/A'}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Information -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-left-info">
                                            <div class="card-header bg-gradient-info text-white">
                                                <i class="bi bi-bag-check mr-2"></i> Order Information
                                            </div>
                                            <div class="card-body">
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-hash mr-2"></i>Order ID:</span>
                                                    <span class="info-value font-weight-bold">#${response.order.order_id}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-calendar-date mr-2"></i>Date:</span>
                                                    <span class="info-value">${new Date(response.order.order_date).toLocaleString()}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-flag mr-2"></i>Status:</span>
                                                    <span class="badge badge-${response.order.order_status === 'completed' ? 'success' : 
                                                                            (response.order.order_status === 'pending' ? 'warning' : 'danger')} badge-pill px-3 py-2">
                                                        <i class="bi bi-${response.order.order_status === 'completed' ? 'check-circle' : 
                                                                        (response.order.order_status === 'pending' ? 'hourglass-split' : 'x-circle')} mr-1"></i>
                                                        ${response.order.order_status.toUpperCase()}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment Information -->
                                <div class="card mb-4 border-left-purple shadow-sm">
                                    <div class="card-header bg-gradient-purple text-white">
                                        <i class="bi bi-credit-card mr-2"></i> Payment Information
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-check-circle mr-2"></i>Status:</span>
                                                    <span class="badge badge-${
                                                        response.payment.status === 'completed' ? 'success' : 
                                                        (response.payment.status === 'pending' ? 'warning' : 
                                                        (response.payment.status === 'failed' ? 'danger' : 'secondary'))
                                                    } badge-pill px-3 py-2">
                                                        ${response.payment.status === 'not_processed' ? 'NOT PROCESSED' : response.payment.status.toUpperCase()}
                                                    </span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-wallet2 mr-2"></i>Method:</span>
                                                    <span class="info-value font-weight-bold">${
                                                        response.payment.method === 'credit_card' ? '<i class="bi bi-credit-card mr-1"></i> Credit Card' :
                                                        response.payment.method === 'paypal' ? '<i class="bi bi-paypal mr-1"></i> PayPal' :
                                                        response.payment.method === 'bank_transfer' ? '<i class="bi bi-bank mr-1"></i> Bank Transfer' :
                                                        response.payment.method === 'cash_on_pickup' ? '<i class="bi bi-cash mr-1"></i> Cash on Pickup' :
                                                        response.payment.method
                                                    }</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                ${response.payment.date ? `
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-calendar mr-2"></i>Date:</span>
                                                    <span class="info-value">${new Date(response.payment.date).toLocaleString()}</span>
                                                </div>` : ''}
                                                ${response.payment.amount ? `
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-cash-stack mr-2"></i>Amount:</span>
                                                    <span class="info-value">₱${parseFloat(response.payment.amount).toFixed(2)}</span>
                                                </div>` : ''}
                                                ${response.payment.transaction_reference ? `
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-hash mr-2"></i>Ref:</span>
                                                    <span class="info-value font-monospace">${response.payment.transaction_reference}</span>
                                                </div>` : ''}
                                            </div>
                                        </div>
                                        ${response.payment.payment_notes ? `
                                        <div class="mt-3 p-2 bg-light rounded">
                                            <small class="text-muted"><i class="bi bi-info-circle mr-1"></i> Notes: ${response.payment.payment_notes}</small>
                                        </div>` : ''}
                                    </div>
                                </div>`;
                                
                        // Pickup Information if available
                        if (response.pickup) {
                            html += `
                                <div class="card mb-4 border-left-success shadow-sm">
                                    <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi bi-truck mr-2"></i> Pickup Details
                                        </div>
                                        <span class="badge badge-light pickup-id-badge">ID: #${response.pickup.pickup_id}</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-calendar-event mr-2"></i>Pickup Date:</span>
                                                    <span class="info-value font-weight-bold">${response.pickup.pickup_date ? new Date(response.pickup.pickup_date).toLocaleString() : 'Not scheduled'}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-geo-alt mr-2"></i>Location:</span>
                                                    <span class="info-value">${response.pickup.pickup_location || 'Not set'}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-person-badge mr-2"></i>Contact Person:</span>
                                                    <span class="info-value">${response.pickup.contact_person || 'Not assigned'}</span>
                                                </div>
                                                ${response.pickup.pickup_notes ? `
                                                <div class="info-item">
                                                    <span class="info-label"><i class="bi bi-card-text mr-2"></i>Notes:</span>
                                                    <span class="info-value notes-text">${response.pickup.pickup_notes}</span>
                                                </div>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        }
                        
                        // Order Items
                        html += `
                            <div class="card mb-4 border-left-warning shadow-sm">
                                <div class="card-header bg-gradient-warning text-dark">
                                    <i class="bi bi-cart mr-2"></i> Order Items
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="pl-3">Product</th>
                                                    <th class="text-center">Price</th>
                                                    <th class="text-center">Quantity</th>
                                                    <th class="text-right pr-3">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;
                        
                        let totalItems = 0;
                        response.items.forEach(item => {
                            totalItems += parseInt(item.quantity);
                            html += `
                                <tr>
                                    <td class="pl-3 font-weight-bold">${item.product_name || 'Unknown Product'}</td>
                                    <td class="text-center">₱${parseFloat(item.price).toFixed(2)}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td class="text-right pr-3">₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</td>
                                </tr>`;
                        });
                        
                        html += `
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <td class="pl-3 font-weight-bold">Order Summary</td>
                                                    <td class="text-center">${response.items.length} product(s)</td>
                                                    <td class="text-center">${totalItems} item(s)</td>
                                                    <td class="text-right pr-3 font-weight-bold text-success">₱${response.subtotal.toFixed(2)}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <div class="order-total-box p-3 bg-light rounded shadow-sm">
                                    <span class="total-label">Total Amount:</span>
                                    <span class="total-value">₱${response.subtotal.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>`;
                        
                        $('#orderDetailsContent').html(html);
                        
                        // Add custom styling for the order details
                        $('<style>')
                            .text(`
                                .order-details-container {
                                    font-family: 'Poppins', sans-serif;
                                }
                                .border-left-primary {
                                    border-left: 4px solid #4e73df !important;
                                }
                                .border-left-info {
                                    border-left: 4px solid #36b9cc !important;
                                }
                                .border-left-success {
                                    border-left: 4px solid #1cc88a !important;
                                }
                                .border-left-warning {
                                    border-left: 4px solid #f6c23e !important;
                                }
                                .border-left-purple {
                                    border-left: 4px solid #7b4ea8 !important;
                                }
                                .bg-gradient-primary {
                                    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
                                }
                                .bg-gradient-info {
                                    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
                                }
                                .bg-gradient-success {
                                    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
                                }
                                .bg-gradient-warning {
                                    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
                                }
                                .bg-gradient-purple {
                                    background: linear-gradient(135deg, #7b4ea8 0%, #5b3b7e 100%);
                                    color: #fff;
                                }
                                .info-item {
                                    margin-bottom: 12px;
                                    display: flex;
                                    align-items: center;
                                }
                                .info-label {
                                    width: 90px;
                                    color: #6c757d;
                                    font-size: 14px;
                                }
                                .info-value {
                                    flex: 1;
                                    color: #212529;
                                    font-weight: 500;
                                }
                                .notes-text {
                                    font-style: italic;
                                    color: #6c757d;
                                }
                                .pickup-id-badge {
                                    font-family: monospace;
                                    letter-spacing: 1px;
                                }
                                .order-total-box {
                                    display: inline-block;
                                    min-width: 200px;
                                    text-align: right;
                                    background-color: #f8f9fa;
                                    border-left: 4px solid #28a745;
                                }
                                .total-label {
                                    font-weight: 500;
                                    font-size: 18px;
                                    color: #495057;
                                    margin-right: 10px;
                                }
                                .total-value {
                                    font-weight: 700;
                                    font-size: 22px;
                                    color: #28a745;
                                }
                                .card {
                                    border-radius: 0.5rem;
                                    overflow: hidden;
                                    transition: transform 0.2s, box-shadow 0.2s;
                                    margin-bottom: 1.5rem;
                                }
                                .card:hover {
                                    transform: translateY(-3px);
                                    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                                }
                                .card-header {
                                    padding: 0.75rem 1.25rem;
                                    font-weight: 600;
                                }
                                table.table td, table.table th {
                                    vertical-align: middle;
                                }
                                .badge {
                                    padding: 0.5em 0.85em;
                                    font-size: 0.85em;
                                    font-weight: 600;
                                }
                                .badge-pill {
                                    border-radius: 50rem;
                                }
                                .badge-success {
                                    background-color: #2ecc71;
                                    color: white;
                                }
                                .badge-warning {
                                    background-color: #f39c12;
                                    color: #212529;
                                }
                                .badge-danger {
                                    background-color: #e74c3c;
                                    color: white;
                                }
                                .badge-secondary {
                                    background-color: #6c757d;
                                    color: white;
                                }
                                /* Improved styles for the payment information section */
                                .bg-light {
                                    background-color: #f1f3f5 !important;
                                }
                                .font-monospace {
                                    font-family: monospace;
                                    letter-spacing: 0.5px;
                                    color: #212529 !important;
                                    background-color: #e9ecef;
                                    padding: 2px 4px;
                                    border-radius: 3px;
                                }
                                .text-muted {
                                    color: #495057 !important;
                                }
                                /* Add a light background to the payment card body for better contrast */
                                .border-left-purple .card-body {
                                    background-color: #f8f5ff;
                                }
                            `)
                            .appendTo('head');
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        
                        $('#orderDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Failed to load order details. Please try again.
                                <br>Error: ${error || 'Unknown error'} 
                            </div>
                        `);
                    }
                });
            });
            
            // Print functionality
            $('#printOrderDetails').click(function() {
                const printContents = document.getElementById('orderDetailsContent').innerHTML;
                const originalContents = document.body.innerHTML;
                document.body.innerHTML = `
                    <div style="padding: 30px;">
                        <h1 style="text-align: center; margin-bottom: 20px;">Order Details</h1>
                        ${printContents}
                    </div>
                `;
                window.print();
                document.body.innerHTML = originalContents;
                location.reload();
            });

            // Status Filter Functionality
            $('.filter-btn').click(function() {
                const status = $(this).data('status');
                // Update active button
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');

                // Filter rows
                if (status === 'all') {
                    $('#orders-table tr').show();
                } else {
                    $('#orders-table tr').hide();
                    $('#orders-table tr[data-status="' + status + '"]').show();
                }

                // Show no results message if needed
                if ($('#orders-table tr:visible').length === 0) {
                    $('#orders-table').append(`
                        <tr id="no-results-row">
                            <td colspan="5" class="text-center no-orders-message">
                                <i class="bi bi-cart-x"></i>
                                No ${status} orders found.
                            </td>
                        </tr>
                    `);
                } else {
                    $('#no-results-row').remove();
                }
            });
            
            // Form validation
            $('#pickupForm').submit(function() {
                if (!$('#pickup_date').val() || !$('#pickup_location').val()) {
                    alert('Pickup date and location are required.');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>