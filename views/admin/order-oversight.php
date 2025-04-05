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
echo "
<select id='statusFilter' class='form-control'>
    <option value=''>All Statuses</option>
    <option value='pending'>Pending</option>
    <option value='completed'>Completed</option>
    <option value='canceled'>Canceled</option>
</select>";

// Display orders table
if (!empty($allOrders)) {
    foreach ($allOrders as $order) {
        // Format the data correctly
        $orderDate = date('M d, Y H:i', strtotime($order['order_date']));
        $totalAmount = number_format($order['total_amount'] ?? 0, 2);
        $itemCount = $order['item_count'] ?? 0;
        
        echo "<tr data-status='" . htmlspecialchars($order['order_status']) . "'>";
        // ... rest of the table row code ...
    }
} else {
    echo "<tr><td colspan='7' class='text-center'>No orders found</td></tr>";
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

        #driverDetails {
            background-color: #f8f9fa;
            border-color: #dee2e6 !important;
        }
        
        #assigned_to option[data-status="available"] {
            background-color: #d4edda;
        }
        
        #assigned_to option[data-status="busy"] {
            background-color: #fff3cd;
        }
        
        #assigned_to option[data-status="offline"] {
            background-color: #f8f9fa;
        }
        
        .driver-status {
            font-weight: 500;
            padding: 2px 6px;
            border-radius: 3px;
        }

        /* Improve select element styling */
        #assigned_to {
            font-size: 1rem;
            font-weight: 500;
        }

        /* Update driver status styling in select */
        #assigned_to option {
            padding: 8px;
            font-weight: normal;
        }

        #assigned_to option[data-status="available"] {
            background-color: #d4edda;
            color: #155724;
        }
        
        #assigned_to option[data-status="busy"] {
            background-color: #fff3cd;
            color: #856404;
        }
        
        #assigned_to option[data-status="offline"] {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        /* Driver details container styling */
        #driverDetails {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-top: 10px;
        }

        #driverDetails small {
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        #driverStatus.text-success {
            color: #28a745 !important;
            font-weight: 600;
        }

        #driverStatus.text-warning {
            color: #ffc107 !important;
            font-weight: 600;
        }

        #driverStatus.text-secondary {
            color: #6c757d !important;
            font-weight: 600;
        }

        /* Enhanced Modal Select Styling */
        .modal select.form-control {
            height: auto !important;
            max-height: 300px;
            overflow-y: auto;
        }

        .modal select.form-control option {
            padding: 10px 15px;
            margin: 2px 0;
            line-height: 1.5;
            min-height: 40px;
            display: block;
            white-space: normal;
            border-bottom: 1px solid #eee;
        }

        #assigned_to option[data-status="available"] {
            background-color: #d4edda;
            color: #155724;
            font-weight: 500;
        }
        
        #assigned_to option[data-status="busy"] {
            background-color: #fff3cd;
            color: #856404;
            font-weight: 500;
        }
        
        #assigned_to option[data-status="offline"] {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 500;
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
                                <i class="bi bi-person-badge"></i> Assign Driver
                            </label>
                            <select class="form-control" id="assigned_to" name="assigned_to">
                                <option value="">Select Driver</option>
                                <?php
                                try {
                                    $driverQuery = "SELECT u.user_id, u.first_name, u.last_name, u.username,
                                                         d.availability_status, d.current_location, d.vehicle_type,
                                                         (SELECT COUNT(*) FROM pickups WHERE assigned_to = u.user_id 
                                                          AND pickup_status = 'pending') as active_pickups
                                                  FROM users u 
                                                  JOIN driver_details d ON u.user_id = d.user_id
                                                  WHERE u.role_id = 6";
                                    $driverStmt = $conn->query($driverQuery);
                                    while ($driver = $driverStmt->fetch(PDO::FETCH_ASSOC)) {
                                        $statusClass = match($driver['availability_status']) {
                                            'available' => 'text-success',
                                            'busy' => 'text-warning',
                                            default => 'text-secondary'
                                        };
                                        $assignmentInfo = $driver['active_pickups'] > 0 ? 
                                            " (" . $driver['active_pickups'] . " active)" : '';
                                        
                                        echo '<option value="' . $driver['user_id'] . '" ' .
                                             'data-status="' . $driver['availability_status'] . '" ' .
                                             'data-location="' . $driver['current_location'] . '" ' .
                                             'data-vehicle="' . $driver['vehicle_type'] . '">' .
                                             htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) .
                                             ' - ' . ucfirst($driver['availability_status']) . $assignmentInfo .
                                             '</option>';
                                    }
                                } catch (PDOException $e) {
                                    error_log("Error fetching drivers: " . $e->getMessage());
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted">Available drivers are shown in green, busy drivers in yellow</small>
                            <div id="driverDetails" class="mt-2 p-2 border rounded d-none">
                                <small class="d-block"><strong>Status:</strong> <span id="driverStatus"></span></small>
                                <small class="d-block"><strong>Location:</strong> <span id="driverLocation"></span></small>
                                <small class="d-block"><strong>Vehicle:</strong> <span id="driverVehicle"></span></small>
                            </div>
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
                                    <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        
                        // Format pickup date if available
                        let pickupDateFormatted = 'Not scheduled';
                        if (response.pickup && response.pickup.pickup_date) {
                            const pickupDate = new Date(response.pickup.pickup_date);
                            pickupDateFormatted = pickupDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                        
                        // Build the complete order details HTML
                        const orderDetailsHtml = `
                            <div class="order-details-container">
                                <div class="order-header">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6 class="text-muted">ORDER INFORMATION</h6>
                                            <p><strong>Order ID:</strong> ${response.order.order_id}</p>
                                            <p><strong>Date:</strong> ${formattedDate}</p>
                                            <p><strong>Status:</strong> ${statusBadge}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted">CUSTOMER INFORMATION</h6>
                                            <p><strong>Name:</strong> ${response.order.first_name} ${response.order.last_name}</p>
                                            <p><strong>Username:</strong> ${response.order.username}</p>
                                            <p><strong>Email:</strong> ${response.order.email}</p>
                                            <p><strong>Phone:</strong> ${response.order.contact_number || 'Not provided'}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-items mt-4">
                                    <h6 class="text-muted mb-3">ORDER ITEMS</h6>
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${itemsHtml}
                                            <tr>
                                                <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                                <td>₱${parseFloat(response.subtotal).toFixed(2)}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                        `;
                        
                        // Add pickup details section if available
                        let pickupDetailsHtml = '';
                        if (response.pickup) {
                            pickupDetailsHtml = `
                                <div class="pickup-details mt-4">
                                    <h6 class="text-muted mb-3">PICKUP DETAILS</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <p><strong>Pickup Location:</strong> ${response.pickup.pickup_location || 'Not specified'}</p>
                                            <p><strong>Pickup Date:</strong> ${pickupDateFormatted}</p>
                                            <p><strong>Assigned To:</strong> ${response.pickup.assigned_to || 'Not assigned'}</p>
                                            <p><strong>Status:</strong> ${response.pickup.pickup_status || 'Pending'}</p>
                                            <p><strong>Notes:</strong> ${response.pickup.pickup_notes || 'No notes'}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            pickupDetailsHtml = `
                                <div class="pickup-details mt-4">
                                    <h6 class="text-muted mb-3">PICKUP DETAILS</h6>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> No pickup details have been assigned yet.
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Update the modal content
                        $('#orderDetailsContent').html(orderDetailsHtml + pickupDetailsHtml + '</div>');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching order details:', error);
                        $('#orderDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Failed to load order details. Please try again.
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

            // Handle driver selection change
            $('#assigned_to').change(function() {
                const selectedOption = $(this).find('option:selected');
                const driverDetails = $('#driverDetails');
                if (selectedOption.val()) {
                    const status = selectedOption.data('status');
                    const location = selectedOption.data('location');
                    const vehicle = selectedOption.data('vehicle');
                    $('#driverStatus').text(status)
                        .removeClass()
                        .addClass(status === 'available' ? 'text-success' : status === 'busy' ? 'text-warning' : 'text-secondary');
                    $('#driverLocation').text(location || 'Not specified');
                    $('#driverVehicle').text(vehicle || 'Not specified');
                    driverDetails.removeClass('d-none');
                } else {
                    driverDetails.addClass('d-none');
                }
            });
        });
    </script>
</body>
</html>