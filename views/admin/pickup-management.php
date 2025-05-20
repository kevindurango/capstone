<?php
// Start the session to track login status
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

// Filter Setup
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

// Update Pickup Logic
if (isset($_POST['update_pickup'])) {
    $pickup_id = $_POST['pickup_id'];
    $pickup_status = $_POST['pickup_status'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_location = $_POST['pickup_location'];
    $contact_person = $_POST['contact_person'];
    $pickup_notes = $_POST['pickup_notes'];

    // Get the old pickup data for logging purposes
    $oldDataQuery = "SELECT * FROM pickups WHERE pickup_id = :pickup_id";
    $oldDataStmt = $conn->prepare($oldDataQuery);
    $oldDataStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    $oldDataStmt->execute();
    $oldData = $oldDataStmt->fetch(PDO::FETCH_ASSOC);

    // Prepare Update Query
    $updateQuery = "UPDATE pickups
                    SET pickup_status = :pickup_status,
                        pickup_date = :pickup_date,
                        pickup_location = :pickup_location,
                        contact_person = :contact_person,
                        pickup_notes = :pickup_notes
                    WHERE pickup_id = :pickup_id";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    $updateStmt->bindParam(':pickup_status', $pickup_status, PDO::PARAM_STR);
    $updateStmt->bindParam(':pickup_date', $pickup_date, PDO::PARAM_STR);
    $updateStmt->bindParam(':pickup_location', $pickup_location, PDO::PARAM_STR);
    $updateStmt->bindParam(':contact_person', $contact_person, PDO::PARAM_STR);
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
                    'previous_status' => $oldData['pickup_status'],
                    'new_status' => $pickup_status,
                    'pickup_date' => $pickup_date,
                    'pickup_location' => $pickup_location,
                    'contact_person' => $contact_person,
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

    header("Location: pickup-management.php?page=$page&search=" . urlencode($search) . "&status=" . urlencode($statusFilter) . "&date=" . urlencode($dateFilter)); // Redirect to refresh the page
    exit();
}

// Fetch Pickups with Pagination
$query = "SELECT
            p.pickup_id,
            o.order_id,
            c.username AS consumer_name,
            p.pickup_status,
            p.pickup_date,
            p.pickup_location,
            p.contact_person,
            p.pickup_notes
          FROM pickups AS p
          JOIN orders AS o ON p.order_id = o.order_id
          JOIN users AS c ON o.consumer_id = c.user_id";

$whereClauses = [];
$queryParams = [];

if (!empty($search)) {
    $whereClauses[] = "(p.pickup_status LIKE :search
                        OR p.pickup_location LIKE :search
                        OR p.contact_person LIKE :search
                        OR c.username LIKE :search
                        OR o.order_id LIKE :search)"; // Add search by order_id as well.

    $queryParams[':search'] = "%$search%";
}

// Add status filter
if (!empty($statusFilter)) {
    $whereClauses[] = "p.pickup_status = :status";
    $queryParams[':status'] = $statusFilter;
}

// Add date filter
if (!empty($dateFilter)) {
    $whereClauses[] = "DATE(p.pickup_date) = :date";
    $queryParams[':date'] = $dateFilter;
}

// Add WHERE clauses to the query
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY p.pickup_date DESC LIMIT :limit OFFSET :offset";

// Count Query (for pagination)
$countQuery = "SELECT COUNT(*) AS total FROM pickups AS p JOIN orders AS o ON p.order_id = o.order_id JOIN users AS u ON o.consumer_id = u.user_id";

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

// Fetch all distinct statuses for the filter dropdown
$statusQuery = "SELECT DISTINCT pickup_status FROM pickups ORDER BY pickup_status";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->execute();
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

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
    <link rel="stylesheet" href="../../public/style/pickup-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Update admin header styling */
        .admin-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 10px 0;
        }

        .admin-badge {
            background-color: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            font-size: 0.85rem;
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Improve card styling */
        .pickup-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .pickup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .pickup-card .card-header {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px;
            border-radius: 12px 12px 0 0;
        }

        .status-label {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-canceled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Improve search and filter container */
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .search-container .input-group-text {
            background: transparent;
            border-right: none;
        }

        .search-container .form-control {
            border-left: none;
            padding-left: 0;
        }

        .search-container .form-control:focus {
            box-shadow: none;
        }

        /* Enhance pagination */
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: #2193b0;
            padding: 8px 16px;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(33, 147, 176, 0.3);
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        /* Page header styling for positioning logout button */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .page-header .header-content {
            flex-grow: 1;
        }

        .page-header .logout-container {
            flex-shrink: 0;
            margin-left: 20px;
        }

        /* Modal improvements */
        .modal-content {
            border-radius: 12px;
            border: none;
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
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 pickup-management-page">
                <!-- Update breadcrumb styling -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pickup Management</li>
                    </ol>
                </nav>

                <!-- Update header section with page-header class -->
                <div class="page-header">
                    <div class="header-content">
                        <h1 class="h2"><i class="bi bi-truck"></i> Pickup Management</h1>
                        <p class="text-muted">Manage and track all product pickups in one place</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');" class="logout-container">
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

                <!-- Search and Filter Bar -->
                <div class="search-container">
                    <form method="GET" action="" class="row">
                        <div class="col-md-4 mb-2">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                </div>
                                <input type="text" name="search" class="form-control" placeholder="Search pickups..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="bi bi-filter"></i></span>
                                </div>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                            <?= ucfirst(htmlspecialchars($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                </div>
                                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="bi bi-funnel"></i> Apply
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Batch Actions Bar -->
                <div class="batch-actions-bar mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-layers"></i> Bulk Actions</h5>
                            <div class="d-flex flex-wrap justify-content-between">
                                <button type="button" id="selectAllBtn" class="btn btn-outline-primary flex-fill mx-1 mb-0">
                                    <i class="bi bi-check-all"></i> Select All
                                </button>
                                <button type="button" id="approveSelectedBtn" class="btn btn-success flex-fill mx-1 mb-0">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button type="button" id="rejectSelectedBtn" class="btn btn-warning flex-fill mx-1 mb-0">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                                <button type="button" id="deleteSelectedBtn" class="btn btn-danger flex-fill mx-1 mb-0">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                            <div id="batchActionStatus" class="mt-2" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <!-- Pickups Content -->
                <?php if (count($pickups) > 0): ?>
                    <!-- Card View for Pickups -->
                    <div class="row">
                        <?php foreach ($pickups as $pickup): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card pickup-card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="font-weight-bold"><i class="bi bi-box"></i> Pickup #<?= htmlspecialchars($pickup['pickup_id']) ?></span>
                                        <span class="status-label status-<?= strtolower(str_replace(' ', '-', $pickup['pickup_status'])) ?>">
                                            <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p><strong><i class="bi bi-receipt"></i> Order ID:</strong> <?= htmlspecialchars($pickup['order_id']) ?></p>
                                        <p><strong><i class="bi bi-person"></i> Consumer:</strong> <?= htmlspecialchars($pickup['consumer_name']) ?></p>
                                        <p><strong><i class="bi bi-calendar-event"></i> Date:</strong> <?= htmlspecialchars(date("F j, Y, g:i A", strtotime($pickup['pickup_date']))) ?></p>
                                        <p><strong><i class="bi bi-geo-alt"></i> Location:</strong> <?= htmlspecialchars($pickup['pickup_location']) ?></p>
                                        <p>
                                            <strong><i class="bi bi-person-badge"></i> Contact Person:</strong> 
                                            <?= htmlspecialchars($pickup['contact_person']) ?>
                                        </p>
                                        <div class="pickup-notes">
                                            <strong><i class="bi bi-card-text"></i> Notes:</strong> <?= htmlspecialchars($pickup['pickup_notes'] ?: 'No notes available') ?>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="button" class="btn btn-primary btn-block edit-pickup-btn"
                                                data-toggle="modal" 
                                                data-target="#editPickupModal"
                                                data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>"
                                                data-pickup-status="<?= htmlspecialchars($pickup['pickup_status']) ?>"
                                                data-pickup-date="<?= htmlspecialchars($pickup['pickup_date']) ?>"
                                                data-pickup-location="<?= htmlspecialchars($pickup['pickup_location']) ?>"
                                                data-contact-person="<?= htmlspecialchars($pickup['contact_person']) ?>"
                                                data-pickup-notes="<?= htmlspecialchars($pickup['pickup_notes']) ?>">
                                            <i class="bi bi-pencil"></i> Edit Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="bi bi-box-seam"></i>
                        <h4>No Pickups Found</h4>
                        <p>There are no pickups matching your search criteria.</p>
                        <a href="pickup-management.php" class="btn btn-outline-success">Clear Filters</a>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                // Calculate range of page numbers to display
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                // Always show first page
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php 
                                // Always show last page
                                if ($endPage < $totalPages): 
                                    if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>"><?= $totalPages ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                            <i class="bi bi-chevron-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <p class="text-center text-muted">Page <?= $page ?> of <?= $totalPages ?></p>
                        </nav>
                    </div>
                <?php endif; ?>

                <!-- Edit Pickup Modal -->
                <div class="modal fade" id="editPickupModal" tabindex="-1" role="dialog" aria-labelledby="editPickupModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editPickupModalLabel"><i class="bi bi-truck"></i> Edit Pickup Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="" class="pickup-form">
                                    <input type="hidden" name="pickup_id" id="pickup_id">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                    <div class="form-group">
                                        <label for="pickup_status"><i class="bi bi-tag"></i> Pickup Status</label>
                                        <select class="form-control" id="pickup_status" name="pickup_status">                                            <option value="pending">Pending</option>
                                            <option value="ready">Ready</option>
                                            <option value="completed">Completed</option>
                                            <option value="canceled">Canceled</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="pickup_date"><i class="bi bi-calendar"></i> Pickup Date</label>
                                        <input type="datetime-local" class="form-control" id="pickup_date" name="pickup_date">
                                    </div>

                                    <div class="form-group">
                                        <label><i class="bi bi-geo-alt"></i> Pickup Location</label>
                                        <input type="text" class="form-control" value="Municipal Agriculture Office" disabled>
                                        <input type="hidden" name="pickup_location" value="Municipal Agriculture Office">
                                        <small class="text-muted">All pickups are processed at the Municipal Agriculture Office</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="contact_person"><i class="bi bi-person"></i> Contact Person</label>
                                        <input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Person responsible for pickup">
                                    </div>

                                    <div class="form-group">
                                        <label for="pickup_notes"><i class="bi bi-card-text"></i> Pickup Notes</label>
                                        <textarea class="form-control" id="pickup_notes" name="pickup_notes" rows="3"></textarea>
                                    </div>

                                    <div class="pickup-actions">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success" name="update_pickup">
                                            <i class="bi bi-check-circle"></i> Update Pickup
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <script>
        $(document).ready(function () {
            // Format the date for datetime-local input
            function formatDateForInput(dateString) {
                const date = new Date(dateString);
                return date.toISOString().slice(0, 16);
            }
            
            // Handle edit pickup button click
            $('.edit-pickup-btn').click(function () {
                // Get data from button attributes using hyphens for data attributes
                const pickupId = $(this).attr('data-pickup-id');
                const pickupStatus = $(this).attr('data-pickup-status');
                const pickupDate = $(this).attr('data-pickup-date');
                const pickupLocation = $(this).attr('data-pickup-location');
                const contactPerson = $(this).attr('data-contact-person');
                const pickupNotes = $(this).attr('data-pickup-notes');

                console.log('Debug - Pickup ID:', pickupId);
                console.log('Debug - Pickup Status:', pickupStatus);
                console.log('Debug - Contact Person:', contactPerson);

                // Normalize status values to match dropdown options
                let normalizedStatus = pickupStatus;
                if (pickupStatus === 'in transit') {
                    normalizedStatus = 'in_transit';
                }
                
                // Populate the modal form
                $('#pickup_id').val(pickupId);
                $('#pickup_status').val(normalizedStatus);
                $('#pickup_date').val(formatDateForInput(pickupDate));
                $('#pickup_location').val(pickupLocation);
                $('#contact_person').val(contactPerson);
                $('#pickup_notes').val(pickupNotes);
                
                // Update modal title with pickup ID
                $('#editPickupModalLabel').html('<i class="bi bi-truck"></i> Edit Pickup #' + pickupId);
            });

            // Add checkboxes to each pickup card
            $('.pickup-card').each(function() {
                const cardHeader = $(this).find('.card-header');
                const pickupId = $(this).find('.edit-pickup-btn').data('pickup-id');
                
                // Add checkbox before the pickup ID
                const checkbox = $('<div class="custom-control custom-checkbox d-inline-block mr-2">' +
                    '<input type="checkbox" class="custom-control-input pickup-checkbox" id="pickup-' + pickupId + '" data-pickup-id="' + pickupId + '">' +
                    '<label class="custom-control-label" for="pickup-' + pickupId + '"></label>' +
                    '</div>');
                
                cardHeader.prepend(checkbox);
            });

            // Select All Button
            $('#selectAllBtn').click(function() {
                const allChecked = $('.pickup-checkbox:checked').length === $('.pickup-checkbox').length;
                $('.pickup-checkbox').prop('checked', !allChecked);
                updateBatchButtons();
            });

            // Update batch action buttons based on selection
            function updateBatchButtons() {
                const selectedCount = $('.pickup-checkbox:checked').length;
                
                // Only enable batch buttons if pickups are selected
                $('#approveSelectedBtn, #rejectSelectedBtn, #deleteSelectedBtn').prop('disabled', selectedCount === 0);
                
                // Show count of selected items
                if (selectedCount > 0) {
                    $('#batchActionStatus').html('<div class="alert alert-info mb-0">' + selectedCount + ' pickup(s) selected</div>').show();
                } else {
                    $('#batchActionStatus').hide();
                }
            }

            // Listen for checkbox changes
            $(document).on('change', '.pickup-checkbox', function() {
                updateBatchButtons();
            });

            // Initialize batch buttons state
            updateBatchButtons();

            // Batch Action Handlers
            $('#approveSelectedBtn').click(function() {
                processBatchAction('completed', 'approve');
            });

            $('#rejectSelectedBtn').click(function() {
                processBatchAction('canceled', 'reject');
            });

            $('#deleteSelectedBtn').click(function() {
                if (confirm('Are you sure you want to delete the selected pickups? This action cannot be undone.')) {
                    processBatchAction('delete', 'delete');
                }
            });

            // Function to process batch actions via AJAX
            function processBatchAction(status, action) {
                const selectedIds = [];
                $('.pickup-checkbox:checked').each(function() {
                    selectedIds.push($(this).data('pickup-id'));
                });

                if (selectedIds.length === 0) {
                    alert('Please select at least one pickup to ' + action);
                    return;
                }

                // Show processing message
                $('#batchActionStatus').html('<div class="alert alert-warning mb-0"><i class="bi bi-hourglass-split"></i> Processing...</div>').show();

                // AJAX call to process the action
                $.ajax({
                    url: '../../ajax/pickup-actions.php',
                    type: 'POST',
                    data: {
                        action: action,
                        status: status,
                        pickup_ids: selectedIds,
                        csrf_token: $('input[name="csrf_token"]').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#batchActionStatus').html('<div class="alert alert-success mb-0"><i class="bi bi-check-circle"></i> ' + response.message + '</div>');
                            
                            // Reload the page after a short delay
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            $('#batchActionStatus').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-circle"></i> ' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#batchActionStatus').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-circle"></i> An error occurred while processing your request.</div>');
                    }
                });
            }

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>
