<?php
// Start the session to track login status
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';  // Include the Log model

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

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Update Pickup Logic
if (isset($_POST['update_pickup'])) {
    $pickup_id = $_POST['pickup_id'];
    $pickup_status = $_POST['pickup_status'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_location = $_POST['pickup_location'];
    $assigned_to = $_POST['assigned_to'];
    $pickup_notes = $_POST['pickup_notes'];

    // Prepare Update Query
    $updateQuery = "UPDATE pickups
                    SET pickup_status = :pickup_status,
                        pickup_date = :pickup_date,
                        pickup_location = :pickup_location,
                        assigned_to = :assigned_to,
                        pickup_notes = :pickup_notes
                    WHERE pickup_id = :pickup_id";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    $updateStmt->bindParam(':pickup_status', $pickup_status, PDO::PARAM_STR);
    $updateStmt->bindParam(':pickup_date', $pickup_date, PDO::PARAM_STR);
    $updateStmt->bindParam(':pickup_location', $pickup_location, PDO::PARAM_STR);
    $updateStmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_STR);
    $updateStmt->bindParam(':pickup_notes', $pickup_notes, PDO::PARAM_STR);

    if ($updateStmt->execute()) {
        $_SESSION['message'] = "Pickup details updated successfully!";
        $_SESSION['message_type'] = 'success';

        // Log the successful pickup update
        if ($admin_user_id) {
            $log->logActivity(
                $admin_user_id,
                "Pickup ID: $pickup_id updated successfully. New status: $pickup_status",
                [
                    'pickup_date' => $pickup_date,
                    'pickup_location' => $pickup_location,
                    'assigned_to' => $assigned_to,
                    'pickup_notes' => $pickup_notes
                ]
            );
        }
    } else {
        $_SESSION['message'] = "Error updating pickup details.";
        $_SESSION['message_type'] = 'danger';

        // Log the failed pickup update attempt
        if ($admin_user_id) {
            $log->logActivity($admin_user_id, "Error updating Pickup ID: $pickup_id. Details: " . print_r($updateStmt->errorInfo(), true));
        }
    }

    header("Location: pickup-management.php?page=$page&search=" . urlencode($search)); // Redirect to refresh the page
    exit();
}

// Fetch Pickups with Pagination
$query = "SELECT
            p.pickup_id,
            o.order_id,
            u.username AS consumer_name,
            p.pickup_status,
            p.pickup_date,
            p.pickup_location,
            p.assigned_to,
            p.pickup_notes
          FROM pickups AS p
          JOIN orders AS o ON p.order_id = o.order_id
          JOIN users AS u ON o.consumer_id = u.user_id";

$whereClauses = [];
$queryParams = [];

if (!empty($search)) {
    $whereClauses[] = "(p.pickup_status LIKE :search
                        OR p.pickup_location LIKE :search
                        OR p.assigned_to LIKE :search
                        OR u.username LIKE :search
                        OR o.order_id LIKE :search)"; // Add search by order_id as well.

    $queryParams[':search'] = "%$search%";
}

// Add WHERE clauses to the query
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY p.pickup_date DESC LIMIT :limit OFFSET :offset";

// Count Query (for pagination)
$countQuery = "SELECT COUNT(*) AS total FROM pickups AS p JOIN orders AS o ON p.order_id = o.order_id  JOIN users AS u ON o.consumer_id = u.user_id";

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
$totalPickups = $countStmt->fetch(PDO::FETCH_ASSOC)['total']; // Corrected variable name
$totalPages = ceil($totalPickups / $limit);

// Prepare and Execute Main Query
$stmt = $conn->prepare($query);

// Bind parameters for the main query
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if (!empty($queryParams)) {
    foreach ($queryParams as $param => $value) {
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }
}
$stmt->execute();
$pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle logout
if (isset($_POST['logout'])) {
    // Log the logout
    if ($admin_user_id) {
        $log->logActivity($admin_user_id, "Admin logged out.");
    }

    session_unset();
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Log Search Activity
if (!empty($search)) {
    // Log the search activity
    if ($admin_user_id) {
        $log->logActivity($admin_user_id, "Admin searched pickups with term: '$search'. Results: $totalPickups");
    }
}

// Log page view
if ($admin_user_id) {
    $log->logActivity($admin_user_id, "Admin viewed pickup management page.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Management - Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Datepicker Styling */
        .datepicker {
            z-index: 1100 !important; /* Make sure datepicker appears above other elements */
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Pickup Management</h1>
                    <!-- Logout Button -->
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
                    <input type="text" name="search" class="form-control mr-2" placeholder="Search pickups..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-success">Search</button>
                </form>

                <!-- Pickups Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Pickup ID</th>
                                <th>Order ID</th>
                                <th>Consumer</th>
                                <th>Status</th>
                                <th>Pickup Date</th>
                                <th>Pickup Location</th>
                                <th>Assigned To</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pickups) > 0): ?>
                                <?php foreach ($pickups as $pickup): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pickup['pickup_id']) ?></td>
                                        <td><?= htmlspecialchars($pickup['order_id']) ?></td>
                                        <td><?= htmlspecialchars($pickup['consumer_name']) ?></td>
                                        <td><?= htmlspecialchars($pickup['pickup_status']) ?></td>
                                        <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($pickup['pickup_date']))) ?></td>
                                        <td><?= htmlspecialchars($pickup['pickup_location']) ?></td>
                                        <td><?= htmlspecialchars($pickup['assigned_to']) ?></td>
                                        <td><?= htmlspecialchars($pickup['pickup_notes']) ?></td>
                                        <td>
                                            <!-- Edit Button (launches Modal) -->
                                            <button type="button" class="btn btn-sm btn-primary edit-pickup-btn"
                                                    data-toggle="modal" data-target="#editPickupModal"
                                                    data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>"
                                                    data-pickup-status="<?= htmlspecialchars($pickup['pickup_status']) ?>"
                                                    data-pickup-date="<?= htmlspecialchars($pickup['pickup_date']) ?>"
                                                    data-pickup-location="<?= htmlspecialchars($pickup['pickup_location']) ?>"
                                                    data-assigned-to="<?= htmlspecialchars($pickup['assigned_to']) ?>"
                                                    data-pickup-notes="<?= htmlspecialchars($pickup['pickup_notes']) ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No pickups found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center mt-5">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>

                <!-- Edit Pickup Modal -->
                <div class="modal fade" id="editPickupModal" tabindex="-1" role="dialog" aria-labelledby="editPickupModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editPickupModalLabel">Edit Pickup Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="pickup_id" id="pickup_id">

                                    <div class="form-group">
                                        <label for="pickup_status">Pickup Status</label>
                                        <select class="form-control" id="pickup_status" name="pickup_status">
                                            <option value="pending">Pending</option>
                                            <option value="scheduled">Scheduled</option>
                                            <option value="ready">Ready</option>
                                            <option value="in transit">In Transit</option>
                                            <option value="completed">Completed</option>
                                            <option value="canceled">Canceled</option>
                                        </select>
                                    </div>

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

                                    <button type="submit" class="btn btn-primary" name="update_pickup">Update Pickup</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and Datepicker -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <script>
        $(document).ready(function () {
            $('.edit-pickup-btn').click(function () {
                // Get data from button attributes
                var pickupId = $(this).data('pickup-id');
                var pickupStatus = $(this).data('pickup-status');
                var pickupDate = $(this).data('pickup-date');
                var pickupLocation = $(this).data('pickup-location');
                var assignedTo = $(this).data('assigned-to');
                var pickupNotes = $(this).data('pickup-notes');

                // Populate the modal form
                $('#pickup_id').val(pickupId);
                $('#pickup_status').val(pickupStatus);
                $('#pickup_date').val(pickupDate);
                $('#pickup_location').val(pickupLocation);
                $('#assigned_to').val(assignedTo);
                $('#pickup_notes').val(pickupNotes);
            });

            // Initialize Datepicker
            $('#pickup_date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
        });
    </script>
</body>
</html>
