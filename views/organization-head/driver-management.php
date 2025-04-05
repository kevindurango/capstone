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

// Handle filtering
$filterStatus = $_GET['filter_status'] ?? 'all';
$searchTerm = $_GET['search_term'] ?? '';

// Fetch drivers with filtering
$driversQuery = "SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.contact_number,
    u.address,
    dd.vehicle_type,
    dd.vehicle_plate,
    dd.availability_status,
    dd.current_location,
    dd.rating,
    dd.license_number,
    dd.completed_pickups
FROM users u
JOIN driver_details dd ON u.user_id = dd.user_id
WHERE u.role_id = 6";

// Add filtering conditions
if ($filterStatus !== 'all') {
    $driversQuery .= " AND dd.availability_status = :status";
}

if (!empty($searchTerm)) {
    $driversQuery .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search 
                        OR u.email LIKE :search OR dd.vehicle_plate LIKE :search
                        OR dd.vehicle_type LIKE :search)";
}

$driversQuery .= " ORDER BY u.last_name ASC";

$stmt = $conn->prepare($driversQuery);

// Bind parameters if needed
if ($filterStatus !== 'all') {
    $stmt->bindParam(':status', $filterStatus);
}

if (!empty($searchTerm)) {
    $search = "%$searchTerm%";
    $stmt->bindParam(':search', $search);
}

$stmt->execute();
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get driver statistics
$statsQuery = "SELECT 
    COUNT(*) as total_drivers,
    COUNT(CASE WHEN dd.availability_status = 'available' THEN 1 END) as available_drivers,
    COUNT(CASE WHEN dd.availability_status = 'busy' THEN 1 END) as busy_drivers,
    COUNT(CASE WHEN dd.availability_status = 'offline' THEN 1 END) as offline_drivers,
    IFNULL(AVG(dd.rating), 0) as average_rating,
    SUM(dd.completed_pickups) as total_completed
FROM users u
JOIN driver_details dd ON u.user_id = dd.user_id
WHERE u.role_id = 6";

$stmt = $conn->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch active pickups for each driver
$activePickupsQuery = "SELECT 
    p.pickup_id, 
    p.order_id,
    p.assigned_to,
    p.pickup_status,
    p.pickup_date,
    p.pickup_location
FROM pickups p
WHERE p.assigned_to = :driver_id
AND p.pickup_status IN ('scheduled', 'in transit')
ORDER BY p.pickup_date ASC";

$activePickupsStmt = $conn->prepare($activePickupsQuery);

// Helper function for status badges
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'available':
            return 'success';
        case 'busy':
            return 'warning';
        case 'offline':
            return 'secondary';
        case 'pending':
            return 'pending';
        case 'scheduled':
            return 'scheduled';
        case 'in transit':
        case 'in-transit':
            return 'in-transit';
        case 'completed':
            return 'completed';
        case 'canceled':
        case 'cancelled':
            return 'canceled';
        default:
            return 'info';
    }
}

// Handle status updates through AJAX - Process POST request to update driver status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $driver_id = $_POST['driver_id'] ?? 0;
    $new_status = $_POST['availability_status'] ?? '';
    
    // Validate inputs
    if (!$driver_id || empty($new_status)) {
        $_SESSION['message'] = "Invalid input data for status update";
        $_SESSION['message_type'] = 'danger';
        header("Location: driver-management.php");
        exit();
    }
    
    // Validate status value
    $valid_statuses = ['available', 'busy', 'offline'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['message'] = "Invalid status value";
        $_SESSION['message_type'] = 'danger';
        header("Location: driver-management.php");
        exit();
    }
    
    try {
        // Update the driver's status
        $updateQuery = "UPDATE driver_details SET availability_status = :status 
                        WHERE user_id = :driver_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':status', $new_status);
        $updateStmt->bindParam(':driver_id', $driver_id);
        $result = $updateStmt->execute();
        
        if ($result) {
            // Log activity
            if ($organization_head_user_id) {
                $logClass->logActivity($organization_head_user_id, "Updated driver ID $driver_id status to $new_status");
            }
            
            $_SESSION['message'] = "Driver status updated successfully";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to update driver status";
            $_SESSION['message_type'] = 'danger';
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        error_log("Driver status update error: " . $e->getMessage());
    }
    
    header("Location: driver-management.php");
    exit();
}

// Include the sidebar
include '../global/organization-head-sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <style>
        .driver-profile-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #1a8754;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .driver-avatar {
            width: 80px;
            height: 80px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 2rem;
            color: #1a8754;
        }
        
        .driver-info {
            margin-bottom: 15px;
        }
        
        .driver-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #212529;
        }
        
        .driver-status {
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            margin-bottom: 15px;
        }
        
        .driver-detail {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .driver-detail i {
            width: 20px;
            margin-right: 10px;
            color: #6c757d;
        }
        
        .driver-detail-text {
            flex: 1;
            color: #212529;
        }
        
        .driver-stats {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f1f1f1;
        }
        
        .driver-stat {
            text-align: center;
        }
        
        .driver-stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a8754;
        }
        
        .driver-stat-label {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .driver-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .filter-bar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #1a8754;
        }
        
        .pickup-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 3px solid #1a8754;
        }

        .pickup-time {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .no-pickups-message {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
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
                        <li class="breadcrumb-item active" aria-current="page">Driver Management</li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-truck"></i> Driver Management</h1>
                        <p class="text-muted">Manage and monitor your driver fleet</p>
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

                <!-- Driver Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-people-fill text-primary stat-icon"></i>
                            <div class="stat-title">Total Drivers</div>
                            <div class="stat-value"><?= $stats['total_drivers'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-person-check-fill text-success stat-icon"></i>
                            <div class="stat-title">Available</div>
                            <div class="stat-value"><?= $stats['available_drivers'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-person-x-fill text-warning stat-icon"></i>
                            <div class="stat-title">Busy</div>
                            <div class="stat-value"><?= $stats['busy_drivers'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-stat-card">
                            <i class="bi bi-star-fill text-info stat-icon"></i>
                            <div class="stat-title">Avg. Rating</div>
                            <div class="stat-value"><?= number_format($stats['average_rating'] ?? 0, 1) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="filter-bar mb-4">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter Drivers</h5>
                    <form method="GET" class="mb-0">
                        <div class="form-row">
                            <div class="col-md-5">
                                <label for="filter_status">Status</label>
                                <select class="form-control" id="filter_status" name="filter_status">
                                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                    <option value="available" <?= $filterStatus === 'available' ? 'selected' : '' ?>>Available</option>
                                    <option value="busy" <?= $filterStatus === 'busy' ? 'selected' : '' ?>>Busy</option>
                                    <option value="offline" <?= $filterStatus === 'offline' ? 'selected' : '' ?>>Offline</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label for="search_term">Search</label>
                                <input type="text" class="form-control" id="search_term" name="search_term" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Name, email, vehicle...">
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary btn-block mt-2">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Drivers Grid -->
                <div class="row">
                    <?php if (!empty($drivers)): ?>
                        <?php foreach ($drivers as $driver): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="driver-profile-card">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div class="d-flex">
                                            <div class="driver-avatar">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h5 class="driver-name"><?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?></h5>
                                                <span class="badge badge-<?= getStatusBadgeClass($driver['availability_status']) ?> badge-status">
                                                    <?= ucfirst($driver['availability_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary view-driver-btn" 
                                                    data-driver-id="<?= $driver['user_id'] ?>"
                                                    data-toggle="modal" data-target="#driverDetailsModal">
                                                <i class="bi bi-info-circle"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="driver-info">
                                        <div class="driver-detail">
                                            <i class="bi bi-truck"></i>
                                            <div class="driver-detail-text">
                                                <?= htmlspecialchars($driver['vehicle_type']) ?> (<?= htmlspecialchars($driver['vehicle_plate']) ?>)
                                            </div>
                                        </div>
                                        <div class="driver-detail">
                                            <i class="bi bi-geo-alt"></i>
                                            <div class="driver-detail-text">
                                                <?= !empty($driver['current_location']) ? htmlspecialchars($driver['current_location']) : 'Location not available' ?>
                                            </div>
                                        </div>
                                        <div class="driver-detail">
                                            <i class="bi bi-telephone"></i>
                                            <div class="driver-detail-text">
                                                <?= htmlspecialchars($driver['contact_number']) ?>
                                            </div>
                                        </div>
                                        <div class="driver-detail">
                                            <i class="bi bi-envelope"></i>
                                            <div class="driver-detail-text">
                                                <?= htmlspecialchars($driver['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="driver-stats">
                                        <div class="driver-stat">
                                            <div class="driver-stat-value"><?= $driver['completed_pickups'] ?? 0 ?></div>
                                            <div class="driver-stat-label">Completed</div>
                                        </div>
                                        <div class="driver-stat">
                                            <div class="driver-stat-value rating-stars">
                                                <?= !empty($driver['rating']) ? number_format($driver['rating'], 1) : 'N/A' ?>
                                            </div>
                                            <div class="driver-stat-label">Rating</div>
                                        </div>
                                    </div>
                                    
                                    <div class="driver-actions">
                                        <button class="btn btn-sm btn-success flex-fill change-status-btn" 
                                                data-driver-id="<?= $driver['user_id'] ?>"
                                                data-current-status="<?= $driver['availability_status'] ?>"
                                                data-toggle="modal" data-target="#changeStatusModal">
                                            <i class="bi bi-arrow-clockwise"></i> Change Status
                                        </button>
                                        <button class="btn btn-sm btn-primary flex-fill active-pickups-btn"
                                                data-driver-id="<?= $driver['user_id'] ?>"
                                                data-driver-name="<?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?>"
                                                data-toggle="modal" data-target="#activePickupsModal">
                                            <i class="bi bi-list-check"></i> View Pickups
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No drivers found with the current filters. 
                                <a href="driver-management.php" class="alert-link">Clear filters</a> to see all drivers.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Driver Details Modal -->
    <div class="modal fade" id="driverDetailsModal" tabindex="-1" role="dialog" aria-labelledby="driverDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="driverDetailsModalLabel"><i class="bi bi-person-badge"></i> Driver Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="driverDetailsContent">
                    <div class="text-center py-4">
                        <i class="bi bi-hourglass-split"></i> Loading driver details...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel"><i class="bi bi-arrow-clockwise"></i> Change Driver Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="driver_id" id="status_driver_id">
                        <div class="form-group">
                            <label for="availability_status">New Status</label>
                            <select class="form-control" id="availability_status" name="availability_status" required>
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="offline">Offline</option>
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

    <!-- Active Pickups Modal -->
    <div class="modal fade" id="activePickupsModal" tabindex="-1" role="dialog" aria-labelledby="activePickupsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activePickupsModalLabel"><i class="bi bi-list-check"></i> Active Pickups</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="activePickupsContent">
                    <div class="text-center py-4">
                        <i class="bi bi-hourglass-split"></i> Loading pickups...
                    </div>
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
            // Change Status 
            $('.change-status-btn').click(function() {
                const driverId = $(this).data('driver-id');
                const currentStatus = $(this).data('current-status');
                
                $('#status_driver_id').val(driverId);
                $('#availability_status').val(currentStatus);
            });
            
            // View Active Pickups
            $('.active-pickups-btn').click(function() {
                const driverId = $(this).data('driver-id');
                const driverName = $(this).data('driver-name');
                
                $('#activePickupsModalLabel').html(`<i class="bi bi-list-check"></i> ${driverName}'s Active Pickups`);
                
                $.ajax({
                    url: '../../controllers/AjaxController.php',
                    method: 'POST',
                    data: {
                        action: 'getDriverActivePickups',
                        driver_id: driverId,
                        csrf_token: '<?= isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : "" ?>'
                    },
                    success: function(response) {
                        $('#activePickupsContent').html(response);
                    },
                    error: function() {
                        $('#activePickupsContent').html('<div class="alert alert-danger">Failed to load pickups. Please try again.</div>');
                    }
                });
            });
            
            // View Driver Details
            $('.view-driver-btn').click(function() {
                const driverId = $(this).data('driver-id');
                
                $.ajax({
                    url: '../../controllers/AjaxController.php',
                    method: 'POST',
                    data: {
                        action: 'getDriverDetails',
                        driver_id: driverId,
                        csrf_token: '<?= isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : "" ?>'
                    },
                    success: function(response) {
                        $('#driverDetailsContent').html(response);
                    },
                    error: function() {
                        $('#driverDetailsContent').html('<div class="alert alert-danger">Failed to load driver details. Please try again.</div>');
                    }
                });
            });
            
            // Initialize rating stars
            $('.rating-stars').each(function() {
                const rating = parseFloat($(this).text());
                if (!isNaN(rating)) {
                    let starsHtml = '';
                    const fullStars = Math.floor(rating);
                    const halfStar = rating - fullStars >= 0.5;
                    
                    for (let i = 0; i < fullStars; i++) {
                        starsHtml += '<i class="bi bi-star-fill"></i>';
                    }
                    
                    if (halfStar) {
                        starsHtml += '<i class="bi bi-star-half"></i>';
                    }
                    
                    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
                    for (let i = 0; i < emptyStars; i++) {
                        starsHtml += '<i class="bi bi-star"></i>';
                    }
                    
                    $(this).html(starsHtml);
                }
            });
        });
    </script>
</body>
</html>
