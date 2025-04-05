<?php
// Start session and check login
session_start();
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

// Include necessary models
require_once '../../models/Database.php';
require_once '../../models/Log.php';

$database = new Database();
$conn = $database->connect();
$logClass = new Log();

// Get Organization Head User ID
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Handle filtering by status
$filterStatus = $_GET['filter_status'] ?? 'pending';
$searchLocation = $_GET['search_location'] ?? '';

// Modify the query to include filtering and searching
$pendingPickupsQuery = "SELECT 
    p.pickup_id, 
    p.order_id, 
    p.pickup_status, 
    p.pickup_date, 
    p.pickup_location, 
    CONCAT(u.first_name, ' ', u.last_name) AS driver_name, 
    dd.vehicle_type 
FROM pickups p
LEFT JOIN users u ON p.assigned_to = u.user_id
LEFT JOIN driver_details dd ON u.user_id = dd.user_id
WHERE p.pickup_status LIKE :filter_status
AND p.pickup_location LIKE :search_location
ORDER BY p.pickup_date ASC";

$stmt = $conn->prepare($pendingPickupsQuery);
$stmt->execute([
    'filter_status' => "%$filterStatus%",
    'search_location' => "%$searchLocation%"
]);
$pendingPickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available drivers
$availableDriversQuery = "SELECT 
    u.user_id, 
    CONCAT(u.first_name, ' ', u.last_name) AS driver_name, 
    dd.vehicle_type, 
    dd.vehicle_plate 
FROM users u
JOIN driver_details dd ON u.user_id = dd.user_id
WHERE dd.availability_status = 'available'
ORDER BY u.first_name ASC";

$stmt = $conn->prepare($availableDriversQuery);
$stmt->execute();
$availableDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pickup statistics
$statsQuery = "SELECT 
    COUNT(CASE WHEN pickup_status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN pickup_status = 'scheduled' AND DATE(pickup_date) = CURDATE() THEN 1 END) as scheduled_today,
    COUNT(CASE WHEN pickup_status = 'completed' AND DATE(pickup_date) = CURDATE() THEN 1 END) as completed_today
FROM pickups";

$stmt = $conn->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$pendingCount = $stats['pending_count'];
$scheduledToday = $stats['scheduled_today'];
$completedToday = $stats['completed_today'];

// Handle pickup assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_pickup'])) {
    $pickup_id = $_POST['pickup_id'];
    $driver_id = $_POST['driver_id'];

    // Update pickup assignment
    $assignPickupQuery = "UPDATE pickups SET assigned_to = :driver_id, pickup_status = 'scheduled' WHERE pickup_id = :pickup_id";
    $stmt = $conn->prepare($assignPickupQuery);
    $stmt->execute(['driver_id' => $driver_id, 'pickup_id' => $pickup_id]);

    // Log the activity
    if ($organization_head_user_id) {
        $logClass->logActivity($organization_head_user_id, "Assigned driver ID $driver_id to pickup ID $pickup_id.");
    }

    $_SESSION['message'] = "Pickup successfully assigned to driver.";
    $_SESSION['message_type'] = 'success';
    header("Location: pickup-scheduling.php");
    exit();
}

// Helper function for pickup status colors
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':
            return 'pending';
        case 'completed':
            return 'completed';
        case 'scheduled':
            return 'scheduled';
        case 'in transit':
        case 'in-transit':
            return 'in-transit';
        case 'canceled':
        case 'cancelled':
            return 'canceled';
        default:
            return 'info';
    }
}

// Include the sidebar
include '../global/organization-head-sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Scheduling</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <style>
        /* Custom styles for pickup scheduling page */
        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
        }
        
        .filter-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #1a8754;
        }
        
        .action-btn {
            border-radius: 50px;
            padding: 6px 15px;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .pickup-empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .pickup-empty-state i {
            font-size: 3.5rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-building"></i> ORGANIZATION MANAGEMENT SYSTEM
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <?php include '../global/organization-head-sidebar.php'; ?>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main" id="main-content">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="organization-head-dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pickup Scheduling</li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-calendar-check"></i> Pickup Scheduling</h1>
                        <p class="text-muted">Schedule and manage pickup operations</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#schedulingHelpModal">
                            <i class="bi bi-question-circle"></i> Help
                        </button>
                    </div>
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

                <!-- Filter and Search -->
                <div class="filter-container mb-4">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Pickups</h5>
                    <form method="GET" class="mb-0">
                        <div class="form-row">
                            <div class="col-md-4">
                                <label for="filter_status">Status</label>
                                <select class="form-control" id="filter_status" name="filter_status">
                                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="scheduled" <?= $filterStatus === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="canceled" <?= $filterStatus === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search_location">Location</label>
                                <input type="text" class="form-control" id="search_location" name="search_location" value="<?= htmlspecialchars($searchLocation) ?>" placeholder="Enter location">
                            </div>
                            <div class="col-md-4 align-self-end">
                                <button type="submit" class="btn btn-primary btn-block mt-2">
                                    <i class="bi bi-search"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Quick Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-clock text-primary stat-icon"></i>
                            <div class="stat-title">Pending Pickups</div>
                            <div class="stat-value"><?= $pendingCount ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-calendar-check text-success stat-icon"></i>
                            <div class="stat-title">Scheduled Today</div>
                            <div class="stat-value"><?= $scheduledToday ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-truck text-info stat-icon"></i>
                            <div class="stat-title">Available Drivers</div>
                            <div class="stat-value"><?= count($availableDrivers) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-check-circle text-warning stat-icon"></i>
                            <div class="stat-title">Completed Today</div>
                            <div class="stat-value"><?= $completedToday ?? 0 ?></div>
                        </div>
                    </div>
                </div>

                <!-- Pickups Table -->
                <div class="table-container">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list"></i> Pickup Management</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pendingPickups)): ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pickup ID</th>
                                        <th>Order ID</th>
                                        <th>Status</th>
                                        <th>Pickup Date</th>
                                        <th>Location</th>
                                        <th>Assigned Driver</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingPickups as $pickup): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pickup['pickup_id']) ?></td>
                                            <td><?= htmlspecialchars($pickup['order_id']) ?></td>
                                            <td>
                                                <span class="badge badge-status badge-<?= getStatusBadgeClass($pickup['pickup_status']) ?>">
                                                    <?= ucfirst($pickup['pickup_status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(date("M j, Y g:i A", strtotime($pickup['pickup_date']))) ?></td>
                                            <td><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($pickup['pickup_location']) ?></td>
                                            <td>
                                                <?php if ($pickup['driver_name']): ?>
                                                    <i class="bi bi-person-badge"></i> <?= htmlspecialchars($pickup['driver_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="bi bi-person-x"></i> Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info action-btn view-pickup-btn" 
                                                        data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>" 
                                                        data-toggle="modal" data-target="#viewPickupModal">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm btn-primary action-btn assign-pickup-btn" 
                                                        data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>" 
                                                        data-toggle="modal" data-target="#assignPickupModal">
                                                    <i class="bi bi-person-plus"></i> Assign
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="pickup-empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h5>No pickups found</h5>
                                <p class="text-muted">No pickups match your current filter criteria.</p>
                                <a href="pickup-scheduling.php" class="btn btn-outline-primary mt-2">
                                    <i class="bi bi-arrow-repeat"></i> Reset Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Pickup Modal -->
    <div class="modal fade" id="viewPickupModal" tabindex="-1" role="dialog" aria-labelledby="viewPickupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPickupModalLabel"><i class="bi bi-info-circle"></i> Pickup Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="pickupDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Pickup Modal -->
    <div class="modal fade" id="assignPickupModal" tabindex="-1" role="dialog" aria-labelledby="assignPickupModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignPickupModalLabel"><i class="bi bi-person-plus"></i> Assign Pickup</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="pickup_id" id="assign_pickup_id">
                        <div class="form-group">
                            <label for="driver_id">Select Driver</label>
                            <select class="form-control" id="driver_id" name="driver_id" required>
                                <option value="" disabled selected>Select a driver</option>
                                <?php foreach ($availableDrivers as $driver): ?>
                                    <option value="<?= htmlspecialchars($driver['user_id']) ?>">
                                        <?= htmlspecialchars($driver['driver_name']) ?> 
                                        (<?= htmlspecialchars($driver['vehicle_type']) ?> - <?= htmlspecialchars($driver['vehicle_plate']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_pickup" class="btn btn-primary">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Help Modal -->
    <div class="modal fade" id="schedulingHelpModal" tabindex="-1" role="dialog" aria-labelledby="schedulingHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="schedulingHelpModalLabel"><i class="bi bi-question-circle"></i> Pickup Scheduling Help</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6><i class="bi bi-info-circle text-primary"></i> About Pickup Scheduling</h6>
                    <p>This page allows you to manage and assign drivers to pending pickups.</p>
                    
                    <h6 class="mt-4"><i class="bi bi-card-checklist text-success"></i> Status Guide</h6>
                    <ul class="list-unstyled">
                        <li><span class="badge badge-pending">Pending</span> - Awaiting driver assignment</li>
                        <li><span class="badge badge-scheduled">Scheduled</span> - Driver assigned and scheduled</li>
                        <li><span class="badge badge-completed">Completed</span> - Pickup has been completed</li>
                        <li><span class="badge badge-canceled">Canceled</span> - Pickup was canceled</li>
                    </ul>
                    
                    <h6 class="mt-4"><i class="bi bi-tools text-info"></i> Tips</h6>
                    <ul>
                        <li>Use filters to find specific pickups</li>
                        <li>Click <i class="bi bi-eye"></i> View to see all pickup details</li>
                        <li>Click <i class="bi bi-person-plus"></i> Assign to assign a driver</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Assign Pickup
            $('.assign-pickup-btn').click(function() {
                const pickupId = $(this).data('pickup-id');
                $('#assign_pickup_id').val(pickupId);
            });

            // View Pickup Details with enhanced error handling
            $('.view-pickup-btn').click(function() {
                const pickupId = $(this).data('pickup-id');
                $('#pickupDetailsContent').html('<div class="text-center py-4"><i class="bi bi-hourglass-split fa-spin"></i> Loading pickup details...</div>');
                
                // Add CSRF token to the request if it's required
                const csrfToken = '<?= isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : "" ?>';
                
                $.ajax({
                    url: '../../controllers/AjaxController.php',
                    method: 'POST',
                    data: {
                        action: 'getPickupDetails',
                        pickup_id: pickupId,
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        if (response.trim() === '') {
                            $('#pickupDetailsContent').html('<div class="alert alert-danger">Received empty response from server.</div>');
                            return;
                        }
                        
                        try {
                            // Check if the response is JSON (error message)
                            const jsonResponse = JSON.parse(response);
                            if (jsonResponse.success === false) {
                                $('#pickupDetailsContent').html('<div class="alert alert-danger">' + jsonResponse.message + '</div>');
                            } else {
                                $('#pickupDetailsContent').html(response);
                            }
                        } catch(e) {
                            // Not JSON, treat as HTML content
                            $('#pickupDetailsContent').html(response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.log("Response:", xhr.responseText);
                        $('#pickupDetailsContent').html('<div class="alert alert-danger">Failed to load pickup details: ' + error + '</div>');
                    }
                });
            });

            // Add refresh functionality
            window.refreshTable = function() {
                // Show loading indicator
                const currentFilter = $('#filter_status').val() || 'pending';
                const currentLocation = $('#search_location').val() || '';
                
                // Construct URL with current filters
                let refreshUrl = 'pickup-scheduling.php';
                
                // Add parameters if they exist
                const params = [];
                if (currentFilter) params.push(`filter_status=${currentFilter}`);
                if (currentLocation) params.push(`search_location=${encodeURIComponent(currentLocation)}`);
                
                if (params.length > 0) {
                    refreshUrl += '?' + params.join('&');
                }
                
                // Reload the page with the current filters
                window.location.href = refreshUrl;
            };
        });
    </script>
</body>
</html>
