<?php
// Start the session to track login status
session_start();

// Check if the user is logged in as an Organization Head, if not redirect to the login page
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

// Include necessary models
require_once '../../models/Order.php';
require_once '../../models/Log.php';
require_once '../../models/Dashboard.php';
require_once '../../models/Database.php';

$database = new Database();
$conn = $database->connect();

// Instantiate necessary classes
$orderClass = new Order();
$logClass = new Log();
$dashboard = new Dashboard();

// Get Organization Head User ID from Session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;
if (!$organization_head_user_id) {
    error_log("Organization head user ID not found in session. Logging will be incomplete.");
}

// Ensure organization_id is in session
if (!isset($_SESSION['organization_id'])) {
    // Try to retrieve and set the organization_id
    $userQuery = "SELECT organization_id FROM user_organizations WHERE user_id = :user_id LIMIT 1";
    $stmt = $conn->prepare($userQuery);
    $stmt->execute(['user_id' => $organization_head_user_id]);
    $orgData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orgData && isset($orgData['organization_id'])) {
        $_SESSION['organization_id'] = $orgData['organization_id'];
        
        // Get organization name
        $nameQuery = "SELECT name FROM organizations WHERE organization_id = :org_id";
        $nameStmt = $conn->prepare($nameQuery);
        $nameStmt->execute(['org_id' => $orgData['organization_id']]);
        $nameData = $nameStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nameData) {
            $_SESSION['organization_name'] = $nameData['name'];
        }
    } else {
        // Log the issue and set a message to be displayed to the user
        error_log("No organization found for organization head user ID: $organization_head_user_id");
        $_SESSION['message'] = "Organization ID not found. Please contact an administrator.";
        $_SESSION['message_type'] = 'warning';
    }
}

// Log the Organization Head Login activity
if ($_SESSION['organization_head_logged_in'] === true && $_SESSION['role'] === 'Organization Head' && isset($organization_head_user_id)) {
    $logClass->logActivity($organization_head_user_id, "Organization Head logged in.");
}

// Fetch orders with pickup details
$orders = $orderClass->getOrdersWithPickupDetails();

// Fetch dashboard metrics
$productCount = $dashboard->getProductCount();
$orderCountPending = $dashboard->getOrderCountByStatus('pending');
$orderCountCompleted = $dashboard->getOrderCountByStatus('completed');
$orderCountCanceled = $dashboard->getOrderCountByStatus('canceled');
$salesData = $dashboard->getSalesData();
$feedbackCount = $dashboard->getFeedbackCount();

// Handle logout functionality
if (isset($_POST['logout'])) {
    if ($organization_head_user_id) {
        $logClass->logActivity($organization_head_user_id, "Organization Head logged out.");
    }
    session_unset();
    session_destroy();
    header("Location: organization-head-login.php");
    exit();
}

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && 
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    if (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['order_status'];
        
        // Validate status
        $allowed_statuses = ['pending', 'completed', 'canceled'];
        if (!in_array($new_status, $allowed_statuses)) {
            $_SESSION['message'] = "Invalid status update request!";
            $_SESSION['message_type'] = 'danger';
            header("Location: organization-head-dashboard.php");
            exit();
        }
        
        // Log and update status
        if ($organization_head_user_id) {
            $logClass->logActivity($organization_head_user_id, "Attempted to update order ID $order_id to status: $new_status");
        }
        
        if ($orderClass->updateOrderStatus($order_id, $new_status)) {
            if ($organization_head_user_id) {
                $logClass->logActivity($organization_head_user_id, "Updated order status for order ID: $order_id to $new_status.");
            }
            $_SESSION['message'] = "Order status updated successfully.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to update order status.";
            $_SESSION['message_type'] = 'danger';
        }
        
        header("Location: organization-head-dashboard.php");
        exit();
    }
}

// Get Organization Statistics
$statsQuery = "SELECT 
    COUNT(DISTINCT d.user_id) as total_drivers,
    COUNT(DISTINCT CASE WHEN dd.availability_status = 'available' THEN d.user_id END) as available_drivers,
    COUNT(DISTINCT CASE WHEN dd.availability_status = 'busy' THEN d.user_id END) as busy_drivers,
    COUNT(DISTINCT CASE WHEN dd.availability_status = 'offline' THEN d.user_id END) as offline_drivers,
    COUNT(DISTINCT p.pickup_id) as total_pickups,
    COUNT(DISTINCT CASE WHEN p.pickup_status = 'pending' THEN p.pickup_id END) as pending_pickups,
    COUNT(DISTINCT CASE WHEN p.pickup_status = 'completed' THEN p.pickup_id END) as completed_pickups,
    COUNT(DISTINCT CASE WHEN p.pickup_status = 'scheduled' THEN p.pickup_id END) as scheduled_pickups,
    COUNT(DISTINCT CASE WHEN p.pickup_status = 'in transit' THEN p.pickup_id END) as in_transit_pickups,
    COUNT(DISTINCT CASE WHEN p.pickup_status = 'canceled' THEN p.pickup_id END) as canceled_pickups,
    COALESCE(AVG(dd.rating), 0) as avg_driver_rating
FROM users d
JOIN driver_details dd ON d.user_id = dd.user_id 
LEFT JOIN pickups p ON dd.user_id = p.assigned_to AND DATE(p.pickup_date) = CURDATE()
WHERE d.role_id = 6";

$stmt = $conn->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get Today's Pickups
$pickupsQuery = "SELECT 
    p.pickup_id,
    p.pickup_status,
    p.pickup_date,
    p.pickup_location,
    CONCAT(d.first_name, ' ', d.last_name) as driver_name,
    dd.vehicle_type,
    dd.availability_status,
    o.order_id
FROM pickups p
LEFT JOIN users d ON p.assigned_to = d.user_id
LEFT JOIN driver_details dd ON d.user_id = dd.user_id
JOIN orders o ON p.order_id = o.order_id
WHERE DATE(p.pickup_date) = CURDATE()
ORDER BY p.pickup_date ASC";

$stmt = $conn->prepare($pickupsQuery);
$stmt->execute();
$todayPickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Active Drivers
$driversQuery = "SELECT 
    u.user_id,
    CONCAT(u.first_name, ' ', u.last_name) as driver_name,
    dd.vehicle_type,
    dd.vehicle_plate,
    dd.availability_status,
    dd.current_location,
    dd.rating,
    COUNT(p.pickup_id) as active_pickups
FROM users u
JOIN driver_details dd ON u.user_id = dd.user_id
LEFT JOIN pickups p ON u.user_id = p.assigned_to AND p.pickup_status IN ('pending', 'in transit')
WHERE u.role_id = 6
GROUP BY u.user_id
ORDER BY dd.availability_status = 'available' DESC, dd.rating DESC";

$stmt = $conn->prepare($driversQuery);
$stmt->execute();
$activeDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Organization Head User ID and Name from Database
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;
$organization_head_name = 'Organization Head'; // Default value

if ($organization_head_user_id) {
    $userQuery = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($userQuery);
    $stmt->execute(['user_id' => $organization_head_user_id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData) {
        $organization_head_name = $userData['full_name'];
    }
}

// Get time-based greeting
$hour = date('H');
$greeting = '';
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

// Fetch Order Status Distribution
$orderStatusQuery = "SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status";
$orderStatusStmt = $conn->prepare($orderStatusQuery);
$orderStatusStmt->execute();
$orderStatusData = $orderStatusStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Driver Availability
$driverAvailabilityQuery = "SELECT availability_status, COUNT(*) AS total FROM driver_details GROUP BY availability_status";
$driverAvailabilityStmt = $conn->prepare($driverAvailabilityQuery);
$driverAvailabilityStmt->execute();
$driverAvailabilityData = $driverAvailabilityStmt->fetchAll(PDO::FETCH_ASSOC);

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
        case 'available':
            return 'success';
        case 'busy':
            return 'warning';
        case 'offline':
            return 'secondary';
        default:
            return 'info';
    }
}

// Add this to check for chart data issues
?>
<script>
function validateChartData(data) {
    console.log('Chart Data:', data);
    
    // Check if data exists and is in the right format
    if (!data || !Array.isArray(data.datasets) || data.datasets.length === 0) {
        console.error('Invalid chart data structure');
        return false;
    }
    
    // Check if the data array has values
    const dataValues = data.datasets[0].data;
    if (!dataValues || !Array.isArray(dataValues) || dataValues.length === 0) {
        console.error('No data values found');
        return false;
    }
    
    // Check if all values are numbers
    const allNumbers = dataValues.every(value => typeof value === 'number');
    if (!allNumbers) {
        console.error('Data values must be numbers');
        return false;
    }
    
    return true;
}
</script>

<?php 
// Helper function for pickup status colors for chart colors
function getPickupStatusColor($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending': return '#ffc107';
        case 'scheduled': return '#17a2b8';
        case 'in transit': case 'in-transit': return '#007bff';
        case 'completed': return '#28a745';
        case 'canceled': case 'cancelled': return '#dc3545';
        default: return '#6c757d';
    }
}

// Prepare chart data for JavaScript
$orderStatusLabels = [];
$orderStatusValues = [];
foreach ($orderStatusData as $data) {
    $orderStatusLabels[] = ucfirst($data['order_status']);
    $orderStatusValues[] = (int)$data['total'];
}

$driverStatusLabels = [];
$driverStatusValues = [];
foreach ($driverAvailabilityData as $data) {
    $driverStatusLabels[] = ucfirst($data['availability_status']);
    $driverStatusValues[] = (int)$data['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Head Dashboard</title>
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Organization Head Header --> 
    <div class="organization-header text-center">
        <h2><i class="bi bi-building"></i> ORGANIZATION HEAD DASHBOARD
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>
            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main" id="main-content">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </nav>
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard Overview</h1>
                        <p class="text-muted">Monitor and manage your organization's operations</p>
                    </div>
                    <div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Welcome Section -->
                <section class="welcome-section mb-4">
                    <div>
                        <h2 class="greeting-text"><?= $greeting ?>, <?= htmlspecialchars($organization_head_name) ?>!</h2>
                        <?php if (isset($_SESSION['organization_name'])): ?>
                            <p class="text-muted">Managing: <?= htmlspecialchars($_SESSION['organization_name']) ?></p>
                        <?php endif; ?>
                        <p class="date-text"><?= date('l, F j, Y') ?></p>
                    </div>
                    <div class="welcome-actions">
                        <button type="button" class="btn btn-light" data-toggle="modal" data-target="#calendarModal">
                            <i class="bi bi-calendar-check"></i> View Calendar
                        </button>
                    </div>
                </section>
                
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
                
                <!-- Quick Actions Section -->
                <section class="quick-actions mb-4">
                    <h5 class="mb-3"><i class="bi bi-lightning-fill text-warning"></i> Quick Actions</h5>
                    <div class="action-container">
                        <a href="driver-management.php" class="action-card" data-type="drivers">
                            <div class="action-card-icon">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="action-card-content">
                                <h4>Manage Drivers</h4>
                                <p>Assign and monitor delivery drivers</p>
                            </div>
                            <div class="action-card-arrow">
                                <i class="bi bi-arrow-right"></i>
                            </div>
                        </a>
                        <a href="pickup-scheduling.php" class="action-card" data-type="pickups">
                            <div class="action-card-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="action-card-content">
                                <h4>Schedule Pickups</h4>
                                <p>Organize pickup times and locations</p>
                            </div>
                            <div class="action-card-arrow">
                                <i class="bi bi-arrow-right"></i>
                            </div>
                        </a>
                        <a href="organization-head-order-management.php" class="action-card" data-type="orders">
                            <div class="action-card-icon">
                                <i class="bi bi-clipboard-data"></i>
                            </div>
                            <div class="action-card-content">
                                <h4>Monitor Orders</h4>
                                <p>Track order status and delivery times</p>
                            </div>
                            <div class="action-card-arrow">
                                <i class="bi bi-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </section>
                
                <!-- Statistics Cards -->
                <section id="statistics">
                    <div class="row mb-4">
                        <!-- Available Drivers Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-person-check-fill text-success card-icon"></i>
                                <div class="card-title">Available Drivers</div>
                                <div class="card-text"><?= $stats['available_drivers'] ?></div>
                            </div>
                        </div>
                        
                        <!-- Products Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-box-fill text-warning card-icon"></i>
                                <div class="card-title">Products</div>
                                <div class="card-text"><?= $productCount ?></div>
                            </div>
                        </div>
                        
                        <!-- Orders Pending Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-clock-fill text-primary card-icon"></i>
                                <div class="card-title">Orders Pending</div>
                                <div class="card-text"><?= $orderCountPending ?></div>
                            </div>
                        </div>
                        
                        <!-- Pending Pickups Card -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card">
                                <i class="bi bi-hourglass-split text-info card-icon"></i>
                                <div class="card-title">Pending Pickups</div>
                                <div class="card-text"><?= $stats['pending_pickups'] ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Order Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="chart-title">Order Status Distribution</div>
                                <div class="chart-container">
                                    <canvas id="orderStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Driver Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="chart-title">Driver Availability</div>
                                <div class="chart-container">
                                    <canvas id="driverStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Today's Activity Section -->
                <div class="row">
                    <!-- Today's Pickups -->
                    <div class="col-md-6 mb-4">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-truck text-success"></i> Today's Pickups</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($todayPickups)): ?>
                                    <div class="pickup-timeline">
                                        <?php foreach ($todayPickups as $pickup): ?>
                                            <div class="pickup-item">
                                                <div class="d-flex justify-content-between">
                                                    <h6>Pickup #<?= $pickup['pickup_id'] ?></h6>
                                                    <span class="pickup-time"><?= date('g:i A', strtotime($pickup['pickup_date'])) ?></span>
                                                </div>
                                                <p class="mb-1">
                                                    <span class="badge badge-<?= getStatusBadgeClass($pickup['pickup_status']) ?>">
                                                        <?= ucfirst($pickup['pickup_status']) ?>
                                                    </span>
                                                    <span class="ml-2"><?= $pickup['pickup_location'] ?></span>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> Driver: <?= $pickup['driver_name'] ?? 'Unassigned' ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-calendar-x"></i>
                                        <p>No pickups scheduled for today</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Drivers -->
                    <div class="col-md-6 mb-4">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-people-fill text-primary"></i> Active Drivers</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($activeDrivers)): ?>
                                    <?php foreach ($activeDrivers as $driver): ?>
                                        <div class="driver-card <?= $driver['availability_status'] ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($driver['driver_name']) ?></h6>
                                                    <small>
                                                        <i class="bi bi-truck"></i> <?= $driver['vehicle_type'] ?> • <?= $driver['vehicle_plate'] ?>
                                                    </small>
                                                </div>
                                                <div class="text-right">
                                                    <span class="badge badge-<?= getStatusBadgeClass($driver['availability_status']) ?>">
                                                        <?= ucfirst($driver['availability_status']) ?>
                                                    </span>
                                                    <div><small><?= $driver['active_pickups'] ?> active pickups</small></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="bi bi-person-x"></i>
                                        <p>No active drivers at the moment</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders Table -->
                <div class="card table-container mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-check text-warning"></i> Recent Orders</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Consumer</th>
                                        <th>Status</th>
                                        <th>Order Date</th>
                                        <th>Pickup Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders)): ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                                <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $order['order_status'] === 'completed' ? 'success' : 
                                                        ($order['order_status'] === 'canceled' ? 'danger' : 'primary') ?>">
                                                        <?= htmlspecialchars($order['order_status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date("M j, Y", strtotime($order['order_date']))) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $order['pickup_status'] === 'completed' ? 'success' : 
                                                        ($order['pickup_status'] === 'cancelled' ? 'danger' : 'info') ?>">
                                                        <?= htmlspecialchars($order['pickup_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-action btn-info view-order-btn" 
                                                            data-order-id="<?= htmlspecialchars($order['order_id']) ?>" 
                                                            data-toggle="modal" data-target="#orderDetailsModal">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-action btn-warning update-order-status-btn" 
                                                            data-order-id="<?= htmlspecialchars($order['order_id']) ?>" 
                                                            data-order-status="<?= htmlspecialchars($order['order_status']) ?>" 
                                                            data-toggle="modal" data-target="#updateOrderStatusModal">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No orders found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Calendar Modal -->
    <div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-labelledby="calendarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarModalLabel"><i class="bi bi-calendar3"></i> Organization Calendar</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Order Status Modal -->
    <div class="modal fade" id="updateOrderStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateOrderStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="order_id" id="update_order_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateOrderStatusModalLabel">Update Order Status</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="order_status">New Status</label>
                            <select class="form-control" id="order_status" name="order_status">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_order_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel"><i class="bi bi-info-circle"></i> Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Content will be loaded via AJAX -->
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
    <!-- Add FullCalendar CSS and JS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Update Order Status Modal
            $('.update-order-status-btn').click(function() {
                const orderId = $(this).data('order-id');
                const currentStatus = $(this).data('order-status');
                $('#update_order_id').val(orderId);
                $('#order_status').val(currentStatus);
            });

            // View Order Details
            $('.view-order-btn').click(function() {
                const orderId = $(this).data('order-id');
                $('#orderDetailsContent').html('<div class="text-center py-4"><i class="bi bi-hourglass-split fa-spin"></i> Loading order details...</div>');
                
                // Fetch order details via AJAX
                $.ajax({
                    url: '../../controllers/AjaxController.php',
                    method: 'POST',
                    data: {
                        action: 'getOrderDetails',
                        order_id: orderId,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    },
                    success: function(response) {
                        $('#orderDetailsContent').html(response);
                    },
                    error: function() {
                        $('#orderDetailsContent').html('<div class="alert alert-danger">Failed to load order details. Please try again.</div>');
                    }
                });
            });

            // Initialize Calendar
            var calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    height: 'auto',
                    events: [
                        <?php foreach ($todayPickups as $pickup): ?>
                        {
                            title: 'Pickup #<?= $pickup['pickup_id'] ?>',
                            start: '<?= $pickup['pickup_date'] ?>',
                            color: '<?= getPickupStatusColor($pickup['pickup_status']) ?>',
                            extendedProps: {
                                pickupId: '<?= $pickup['pickup_id'] ?>',
                                location: '<?= $pickup['pickup_location'] ?>',
                                driver: '<?= $pickup['driver_name'] ?? "Unassigned" ?>'
                            }
                        },
                        <?php endforeach; ?>
                    ]
                });
                
                $('#calendarModal').on('shown.bs.modal', function () {
                    calendar.render();
                });
            }

            // Initialize Charts
            initializeCharts();
            
            // Add responsiveness to the dashboard
            function adjustLayout() {
                if (window.innerWidth < 768) {
                    $('.dashboard-card').addClass('mb-3');
                    $('.stat-card').addClass('mb-3');
                } else {
                    $('.dashboard-card').removeClass('mb-3');
                    $('.stat-card').removeClass('mb-3');
                }
            }
            
            // Call on page load and window resize
            adjustLayout();
            $(window).resize(function() {
                adjustLayout();
                // Properly update charts on resize
                if (window.orderStatusChart) {
                    window.orderStatusChart.update();
                }
                if (window.driverStatusChart) {
                    window.driverStatusChart.update();
                }
            });
        });

        // Function to initialize charts
        function initializeCharts() {
            // Order Status Chart
            const orderStatusCtx = document.getElementById('orderStatusChart');
            if (orderStatusCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance to prevent duplicates
                if (window.orderStatusChart instanceof Chart) {
                    window.orderStatusChart.destroy();
                }
                
                const orderChartData = {
                    labels: <?= json_encode($orderStatusLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($orderStatusValues) ?>,
                        backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6c757d', '#fd7e14'],
                        borderWidth: 1
                    }]
                };

                if (validateChartData(orderChartData)) {
                    window.orderStatusChart = new Chart(orderStatusCtx, {
                        type: 'doughnut',
                        data: orderChartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true, // Change to true to prevent stretching
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Driver Status Chart
            const driverStatusCtx = document.getElementById('driverStatusChart');
            if (driverStatusCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance to prevent duplicates
                if (window.driverStatusChart instanceof Chart) {
                    window.driverStatusChart.destroy();
                }
                
                const driverChartData = {
                    labels: <?= json_encode($driverStatusLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($driverStatusValues) ?>,
                        backgroundColor: ['#28a745', '#ffc107', '#6c757d'],
                        borderWidth: 1
                    }]
                };

                if (validateChartData(driverChartData)) {
                    window.driverStatusChart = new Chart(driverStatusCtx, {
                        type: 'pie',
                        data: driverChartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true, // Change to true to prevent stretching
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
</body>
</html>