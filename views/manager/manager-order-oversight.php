<?php
// Start session to check if user is logged in
session_start();

// Check if the user is logged in as a Manager, if not redirect to the login page
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';  // Include the Log model

// Database Connection
$database = new Database();
$conn = $database->connect();

// Log instance
$log = new Log();  // Instantiate the Log class

// Get Manager User ID from Session - Crucial for logging
$manager_user_id = $_SESSION['manager_user_id'] ?? null; // Assuming you store the manager's user_id in the session
if (!$manager_user_id) {
    error_log("Manager user ID not found in session. Logging will be incomplete.");
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
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, $action_description);
        }

    } else {
        $_SESSION['message'] = "Error saving pickup details.";
        $_SESSION['message_type'] = 'danger';

        // Log the activity (even on error)
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Failed to save pickup details for order ID: $order_id. Error: " . print_r($stmt->errorInfo(), true));
            error_log("PDO Error: " . print_r($stmt->errorInfo(), true));  // Log PDO errors
        }
    }

    header("Location: manager-order-oversight.php"); // Redirect to refresh the page
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
if (isset($_POST['logout'])) {
    // Log the activity
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Manager logged out.");
    }

    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}

// Log Search Activity
if (!empty($search)) {
    // Log the activity
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Manager searched orders with term: '$search'. Results: $totalOrders");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Oversight - Manager Dashboard</title>
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
            <!-- Include Sidebar -->
            <?php include '../../views/global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Order Oversight</h1>
                    <form method="POST" class="ml-3">
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

                <!-- Search Bar -->
                <form method="GET" action="" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>" aria-label="Search orders">
                    <button type="submit" class="btn btn-outline-success">Search</button>
                </form>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Consumer</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orders) > 0): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($order['order_status'])) ?></td>
                                        <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?></td>
                                        <td>
                                            <!-- Manage Pickup Details Button (launches Modal) -->
                                            <button type="button" class="btn btn-sm btn-info manage-pickup-btn"
                                                    data-toggle="modal" data-target="#managePickupModal"
                                                    data-order-id="<?= htmlspecialchars($order['order_id']) ?>"
                                                    data-pickup-id="<?= htmlspecialchars($order['pickup_id'] ?? '') ?>"
                                                    data-pickup-date="<?= htmlspecialchars($order['pickup_date'] ?? '') ?>"
                                                    data-pickup-location="<?= htmlspecialchars($order['pickup_location'] ?? '') ?>"
                                                    data-assigned-to="<?= htmlspecialchars($order['assigned_to'] ?? '') ?>"
                                                    data-pickup-notes="<?= htmlspecialchars($order['pickup_notes'] ?? '') ?>">
                                                <i class="bi bi-truck"></i> Manage Pickup
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center mt-3">
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

                <!-- Manage Pickup Details Modal -->
                <div class="modal fade" id="managePickupModal" tabindex="-1" role="dialog" aria-labelledby="managePickupModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="managePickupModalLabel">Manage Pickup Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="order_id" id="order_id">
                                    <input type="hidden" name="pickup_id" id="pickup_id">

                                    <div class="form-group">
                                        <label for="pickup_date">Pickup Date</label>
                                        <input type="datetime-local" class="form-control" id="pickup_date" name="pickup_date">
                                    </div>

                                    <div class="form-group">
                                        <label for="pickup_location">Pickup Location</label>
                                        <input type="text" class="form-control" id="pickup_location" name="pickup_location">
                                    </div>

                                    <div class="form-group">
                                        <label for="assigned_to">Assigned To</label>
                                        <input type="text" class="form-control" id="assigned_to" name="assigned_to">
                                    </div>

                                    <div class="form-group">
                                        <label for="pickup_notes">Pickup Notes</label>
                                        <textarea class="form-control" id="pickup_notes" name="pickup_notes" rows="3"></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success" name="manage_pickup_details">Save Details</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
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
            });
        });
    </script>
</body>
</html>