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

// Database Connection
$database = new Database();
$conn = $database->connect();

// Log instance
$log = new Log();  // Instantiate the Log class

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
                            assigned_to = :assigned_to,
                            pickup_notes = :pickup_notes
                        WHERE pickup_id = :pickup_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
        $stmt->bindParam(':pickup_date', $pickup_date, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_location', $pickup_location, PDO::PARAM_STR);
        $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_notes', $pickup_notes, PDO::PARAM_STR);

        $action_description = "Updated pickup details for order ID: $order_id, Pickup ID: $pickup_id"; // Detailed log

    } else {
        // Insert new record
        $insertQuery = "INSERT INTO pickups (order_id, pickup_date, pickup_location, assigned_to, pickup_notes)
                        VALUES (:order_id, :pickup_date, :pickup_location, :assigned_to, :pickup_notes)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':pickup_date', $pickup_date, PDO::PARAM_STR);
        $stmt->bindParam(':pickup_location', $pickup_location, PDO::PARAM_STR);
        $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_STR);
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
                p.pickup_id, p.pickup_date, p.pickup_location, p.assigned_to, p.pickup_notes
          FROM orders AS o
          JOIN users AS u ON o.consumer_id = u.user_id
          LEFT JOIN pickups AS p ON o.order_id = p.order_id";

$whereClauses = [];
$queryParams = [];

if (!empty($search)) {
    $whereClauses[] = "(u.username LIKE :search OR o.order_status LIKE :search)";
    $queryParams[':search'] = "%$search%";
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY o.order_date DESC LIMIT :limit OFFSET :offset";

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
                                                        <!-- Manage Pickup Details Button (launches Modal) -->
                                                        <button type="button" class="btn btn-info btn-sm manage-pickup-btn"
                                                                data-toggle="modal" data-target="#managePickupModal"
                                                                data-order-id="<?= htmlspecialchars($order['order_id']) ?>"
                                                                data-pickup-id="<?= htmlspecialchars($order['pickup_id'] ?? '') ?>"
                                                                data-pickup-date="<?= htmlspecialchars($order['pickup_date'] ?? '') ?>"
                                                                data-pickup-location="<?= htmlspecialchars($order['pickup_location'] ?? '') ?>"
                                                                data-assigned-to="<?= htmlspecialchars($order['assigned_to'] ?? '') ?>"
                                                                data-pickup-notes="<?= htmlspecialchars($order['pickup_notes'] ?? '') ?>">
                                                            <i class="bi bi-truck"></i> Manage Pickup
                                                        </button>
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
                            <input type="text" class="form-control" id="pickup_location" name="pickup_location" placeholder="Enter pickup location" required>
                        </div>

                        <div class="form-group">
                            <label for="assigned_to">
                                <i class="bi bi-person"></i> Assigned To
                            </label>
                            <input type="text" class="form-control" id="assigned_to" name="assigned_to" placeholder="Enter staff name">
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
                    url: 'get-order-details.php',
                    type: 'GET',
                    data: { order_id: orderId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            $('#orderDetailsContent').html(`
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Error: ${response.error}
                                </div>
                            `);
                            return;
                        }
                        
                        // Format the order date
                        const orderDate = new Date(response.order.order_date);
                        const formattedDate = orderDate.toLocaleDateString('en-US', {
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        // Get status badge based on order status
                        let statusBadge = '';
                        if (response.order.order_status === 'pending') {
                            statusBadge = '<span class="badge badge-warning">Pending</span>';
                        } else if (response.order.order_status === 'completed') {
                            statusBadge = '<span class="badge badge-success">Completed</span>';
                        } else {
                            statusBadge = '<span class="badge badge-danger">Canceled</span>';
                        }
                        
                        // Build the order items HTML
                        let itemsHtml = '';
                        response.items.forEach(item => {
                            itemsHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>₱${parseFloat(item.item_price).toFixed(2)}</td>
                                    <td>${item.quantity}</td>
                        }
                        
                        // Build the complete order details HTML
                        const orderDetailsHtml = `
                            <div class="order-details-container">
                                <div class="order-header">
                                    <div class="row mb-4">
                                    <td>₱${parseFloat(item.total_price).toFixed(2)}</td>               <div class="col-md-6">
                                </tr>                    <h6 class="text-muted">ORDER INFORMATION</h6>
                            `;</strong> ${response.order.order_id}</p>
                        });rong>Date:</strong> ${formattedDate}</p>
                        trong> ${statusBadge}</p>
                        // Format pickup date if available
                        let pickupDateFormatted = 'Not scheduled';-6">
                        if (response.pickup && response.pickup.pickup_date) {ted">CUSTOMER INFORMATION</h6>
                            const pickupDate = new Date(response.pickup.pickup_date);rst_name} ${response.order.last_name}</p>
                            pickupDateFormatted = pickupDate.toLocaleDateString('en-US', {
                                year: 'numeric', ail}</p>
                                month: 'long', ntact_number || 'Not provided'}</p>
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                        
                        // Build the complete order details HTML
                        const orderDetailsHtml = ` class="thead-light">
                            <div class="order-details-container">  <tr>
                                <div class="order-header">          <th>Product</th>
                                    <div class="row mb-4">                <th>Price</th>
                                        <div class="col-md-6">th>
                                            <h6 class="text-muted">ORDER INFORMATION</h6>
                                            <p><strong>Order ID:</strong> ${response.order.order_id}</p>
                                            <p><strong>Date:</strong> ${formattedDate}</p>
                                            <p><strong>Status:</strong> ${statusBadge}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted">CUSTOMER INFORMATION</h6>lass="text-right"><strong>Subtotal:</strong></td>
                                            <p><strong>Name:</strong> ${response.order.first_name} ${response.order.last_name}</p>oat(response.subtotal).toFixed(2)}</td>
                                            <p><strong>Username:</strong> ${response.order.username}</p>
                                            <p><strong>Email:</strong> ${response.order.email}</p>
                                            <p><strong>Phone:</strong> ${response.order.contact_number || 'Not provided'}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-items mt-4">';
                                    <h6 class="text-muted mb-3">ORDER ITEMS</h6>
                                    <table class="table table-bordered">l = `
                                        <thead class="thead-light">lass="pickup-details mt-4">
                                            <tr>          <h6 class="text-muted mb-3">PICKUP DETAILS</h6>
                                                <th>Product</th>            <div class="card">
                                                <th>Price</th>
                                                <th>Quantity</th>ong>Pickup Location:</strong> ${response.pickup.pickup_location || 'Not specified'}</p>
                                                <th>Total</th>><strong>Pickup Date:</strong> ${pickupDateFormatted}</p>
                                            </tr>trong>Assigned To:</strong> ${response.pickup.assigned_to || 'Not assigned'}</p>
                                        </thead>trong> ${response.pickup.pickup_status || 'Pending'}</p>
                                        <tbody>ickup.pickup_notes || 'No notes'}</p>
                                            ${itemsHtml}
                                            <tr>
                                                <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                                <td>₱${parseFloat(response.subtotal).toFixed(2)}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>"text-muted mb-3">PICKUP DETAILS</h6>
                        `;lass="alert alert-info">
                          <i class="bi bi-info-circle"></i> No pickup details have been assigned yet.
                        // Add pickup details section if available      </div>
                        let pickupDetailsHtml = '';</div>
                        if (response.pickup) {
                            pickupDetailsHtml = `
                                <div class="pickup-details mt-4">
                                    <h6 class="text-muted mb-3">PICKUP DETAILS</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <p><strong>Pickup Location:</strong> ${response.pickup.pickup_location || 'Not specified'}</p>r, status, error) {
                                            <p><strong>Pickup Date:</strong> ${pickupDateFormatted}</p>e.error('Error fetching order details:', error);
                                            <p><strong>Assigned To:</strong> ${response.pickup.assigned_to || 'Not assigned'}</p>('#orderDetailsContent').html(`
                                            <p><strong>Status:</strong> ${response.pickup.pickup_status || 'Pending'}</p>    <div class="alert alert-danger">
                                            <p><strong>Notes:</strong> ${response.pickup.pickup_notes || 'No notes'}</p>lamation-triangle"></i> Failed to load order details. Please try again.
                                        </div>
                                    </div>  `);
                                </div>
                            `;
                        } else {
                            pickupDetailsHtml = `
                                <div class="pickup-details mt-4">
                                    <h6 class="text-muted mb-3">PICKUP DETAILS</h6>).click(function() {
                                    <div class="alert alert-info">Contents = document.getElementById('orderDetailsContent').innerHTML;
                                        <i class="bi bi-info-circle"></i> No pickup details have been assigned yet. originalContents = document.body.innerHTML;
                                    </div>
                                </div> document.body.innerHTML = `
                            `;        <div style="padding: 30px;">
                        }"text-align: center; margin-bottom: 20px;">Order Details</h1>
                        
                        // Update the modal content
                        $('#orderDetailsContent').html(orderDetailsHtml + pickupDetailsHtml + '</div>');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching order details:', error);alContents;
                        $('#orderDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Failed to load order details. Please try again.
                            </div>tus Filter Functionality
                        `);filter-btn').click(function() {
                    }$(this).data('status');
                });
            });utton
             $('.filter-btn').removeClass('active');
            // Print functionality    $(this).addClass('active');
            $('#printOrderDetails').click(function() {
                const printContents = document.getElementById('orderDetailsContent').innerHTML;
                const originalContents = document.body.innerHTML;
                    $('#orders-table tr').show();
                document.body.innerHTML = `
                    <div style="padding: 30px;">
                        <h1 style="text-align: center; margin-bottom: 20px;">Order Details</h1>a-status="' + status + '"]').show();
                        ${printContents}}
                    </div>
                `;age if needed
                .length === 0) {
                window.print();orders-table').append(`
                document.body.innerHTML = originalContents;
                location.reload();e">
            });               <i class="bi bi-cart-x"></i>
                            No ${status} orders found.
            // Status Filter Functionality
            $('.filter-btn').click(function() {
                const status = $(this).data('status');
                
                // Update active button
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                
                // Filter rowsn
                if (status === 'all') {orm').submit(function() {
                    $('#orders-table tr').show();#pickup_date').val() || !$('#pickup_location').val()) {
                } else {n are required.');
                    $('#orders-table tr').hide();   return false;
                    $('#orders-table tr[data-status="' + status + '"]').show(); }
                }    return true;
                
                // Show no results message if needed
                if ($('#orders-table tr:visible').length === 0) {
                    $('#orders-table').append(`
                        <tr id="no-results-row">                            <td colspan="5" class="text-center no-orders-message">                                <i class="bi bi-cart-x"></i>                                No ${status} orders found.                            </td>                        </tr>                    `);                } else {
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