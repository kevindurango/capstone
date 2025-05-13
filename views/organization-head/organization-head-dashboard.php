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
require_once '../../models/Pickup.php';
require_once '../../models/Sales.php'; // Added Sales model for revenue analytics
require_once '../../models/Product.php'; // Added Product model for inventory data

// Initialize classes
$database = new Database();
$conn = $database->connect();
$orderClass = new Order();
$logClass = new Log();
$dashboard = new Dashboard();
$pickupClass = new Pickup();
$salesClass = new Sales(); // Initialize Sales class
$productClass = new Product(); // Initialize Product class

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

// ======= FETCH DATA FOR DASHBOARD =======

// Get essential dashboard data
$orders = $orderClass->getOrdersWithPickupDetails(10); // Limit to 10 recent orders
$stats = [
    'total_products' => $dashboard->getProductCount(),
    'pending_orders' => $dashboard->getOrderCountByStatus('pending'),
    'completed_orders' => $dashboard->getOrderCountByStatus('completed'),
    'canceled_orders' => $dashboard->getOrderCountByStatus('canceled'),
    'total_pickups' => $pickupClass->getPickupCountTotal(),
    'pending_pickups' => $pickupClass->getPickupCountByStatus('pending'),
    'completed_pickups' => $pickupClass->getPickupCountByStatus('completed'),
    'assigned_pickups' => $pickupClass->getPickupCountByStatus('assigned')
];

// Summary statistics
$totalSales = $dashboard->getTotalSalesAmount() ?? 0;
$pendingDeliveries = $pickupClass->getPickupCountByStatus('pending') + $pickupClass->getPickupCountByStatus('assigned');
$recentOrdersCount = count($orders);

// Get Today's Pickups
$todayPickups = $pickupClass->getTodayPickups();

// Get Organization Head Name
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

// Fetch Chart Data
$orderStatusQuery = "SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status";
$orderStatusStmt = $conn->prepare($orderStatusQuery);
$orderStatusStmt->execute();
$orderStatusData = $orderStatusStmt->fetchAll(PDO::FETCH_ASSOC);

$pickupStatusQuery = "SELECT pickup_status, COUNT(*) AS total FROM pickups GROUP BY pickup_status";
$pickupStatusStmt = $conn->prepare($pickupStatusQuery);
$pickupStatusStmt->execute();
$pickupStatusData = $pickupStatusStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data for JavaScript
$orderStatusLabels = [];
$orderStatusValues = [];
foreach ($orderStatusData as $data) {
    $orderStatusLabels[] = ucfirst($data['order_status']);
    $orderStatusValues[] = (int)$data['total'];
}

$pickupStatusLabels = [];
$pickupStatusValues = [];
foreach ($pickupStatusData as $data) {
    $pickupStatusLabels[] = ucfirst($data['pickup_status']);
    $pickupStatusValues[] = (int)$data['total'];
}

// ======= ADDITIONAL DATA FOR NEW DASHBOARD FEATURES =======

// Get Agricultural Analytics Data
$farmersByBarangay = $dashboard->getFarmersPerBarangay();
$cropsByBarangay = $dashboard->getCropsPerBarangay();
$seasonalCrops = $dashboard->getSeasonalCropProduction();

// Transform agricultural data for charts
$barangayLabels = [];
$farmerCounts = [];
$cropCounts = [];

foreach ($farmersByBarangay as $barangay) {
    $barangayLabels[] = $barangay['barangay_name'];
    $farmerCounts[] = (int)$barangay['farmer_count'];
}

// Process crop distribution by barangay data
$barangayCropData = [];
foreach ($cropsByBarangay as $crop) {
    $barangayName = $crop['barangay_name'];
    if (!isset($barangayCropData[$barangayName])) {
        $barangayCropData[$barangayName] = 0;
    }
    $barangayCropData[$barangayName]++;
}

// Convert associative array to indexed arrays for Chart.js
$cropBarangayLabels = array_keys($barangayCropData);
$cropCounts = array_values($barangayCropData);

// Group crops by season for the seasonal crops chart
$seasonLabels = [];
$seasonProduction = [];
$seasonData = [];

foreach ($seasonalCrops as $crop) {
    if (!isset($seasonData[$crop['season_name']])) {
        $seasonData[$crop['season_name']] = 0;
        $seasonLabels[] = $crop['season_name'];
    }
    $seasonData[$crop['season_name']] += (float)$crop['total_production'];
}

foreach ($seasonLabels as $season) {
    $seasonProduction[] = $seasonData[$season];
}

// Get Sales Analytics Data
$totalRevenue = $salesClass->getTotalRevenue() ?? 0;
$weeklyRevenue = $salesClass->getWeeklySales() ?? 0;
$averageOrderValue = $salesClass->getAverageOrderValue() ?? 0;
$salesByCategory = $salesClass->getSalesByCategory() ?? [];
$topProducts = $salesClass->getTopProducts(5) ?? [];
$revenueData = $salesClass->getRevenueData() ?? [];

// Determine top category
$topCategory = !empty($salesByCategory) ? $salesByCategory[0]['category'] : 'N/A';

// Format revenue data for chart
$revenueDates = [];
$revenueAmounts = [];

foreach ($revenueData as $data) {
    $revenueDates[] = date('M j', strtotime($data['date']));
    $revenueAmounts[] = (float)$data['amount'];
}

// Format category data for chart
$categoryLabels = [];
$categoryValues = [];

foreach ($salesByCategory as $category) {
    $categoryLabels[] = $category['category'];
    $categoryValues[] = (float)$category['total'];
}

// Get Farmer Management Data
$allBarangays = $dashboard->getAllBarangaysWithMetrics();

// Transform farmer data for charts
$farmerDistBarangays = [];
$farmerDistCounts = [];
$farmSizeBarangays = [];
$farmSizeValues = [];

foreach ($allBarangays as $barangay) {
    if ((int)$barangay['farmer_count'] > 0) {
        $farmerDistBarangays[] = $barangay['barangay_name'];
        $farmerDistCounts[] = (int)$barangay['farmer_count'];
        
        $farmSizeBarangays[] = $barangay['barangay_name'];
        $farmSizeValues[] = (float)$barangay['total_farm_area'];
    }
}

// Get Low Stock Products
$lowStockProducts = $dashboard->getLowStockProducts(10) ?? [];

// Helper functions
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending': return 'pending';
        case 'completed': return 'completed';
        case 'assigned': return 'scheduled';
        case 'in transit': case 'in-transit': return 'in-transit';
        case 'canceled': case 'cancelled': return 'canceled';
        case 'available': return 'success';
        case 'busy': return 'warning';
        case 'offline': return 'secondary';
        default: return 'info';
    }
}

function getPickupStatusColor($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending': return '#ffc107';
        case 'assigned': return '#17a2b8';
        case 'in transit': case 'in-transit': return '#007bff';
        case 'completed': return '#28a745';
        case 'canceled': return '#dc3545';
        default: return '#6c757d';
    }
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
                        <li class="breadcrumb-item"><a href="#"><i class="bi bi-house-door"></i> Home</a></li>
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
                            <button type="submit" name="logout" class="btn btn-outline-danger">
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
                
                <!-- Statistics Dashboard -->
                <section id="dashboard-summary" class="mb-4">
                    <h5 class="section-title"><i class="bi bi-graph-up text-primary"></i> Dashboard Summary</h5>
                    <div class="row">
                        <!-- Stats Overview Cards -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-card primary-card">
                                <div class="card-icon-container">
                                    <i class="bi bi-box-fill card-icon"></i>
                                </div>
                                <div class="card-content">
                                    <div class="card-title">Total Products</div>
                                    <div class="card-text"><?= $stats['total_products'] ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-card warning-card">
                                <div class="card-icon-container">
                                    <i class="bi bi-clock-fill card-icon"></i>
                                </div>
                                <div class="card-content">
                                    <div class="card-title">Pending Orders</div>
                                    <div class="card-text"><?= $stats['pending_orders'] ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-card success-card">
                                <div class="card-icon-container">
                                    <i class="bi bi-truck card-icon"></i>
                                </div>
                                <div class="card-content">
                                    <div class="card-title">Pending Pickups</div>
                                    <div class="card-text"><?= $stats['pending_pickups'] ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-card info-card">
                                <div class="card-icon-container">
                                    <i class="bi bi-check2-circle card-icon"></i>
                                </div>
                                <div class="card-content">
                                    <div class="card-title">Completed Pickups</div>
                                    <div class="card-text"><?= $stats['completed_pickups'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Data Visualization Section -->
                <section id="data-visualization" class="mb-4">
                    <div class="row">
                        <!-- Order Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-pie-chart-fill text-primary"></i> Order Status Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="orderStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pickup Status Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-bar-chart-fill text-success"></i> Pickup Status Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="pickupStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Agricultural Data Visualization Section (NEW) -->
                <section id="agricultural-analytics" class="mb-4">
                    <h5 class="section-title"><i class="bi bi-geo-alt text-success"></i> Agricultural Analytics</h5>
                    <div class="row">
                        <!-- Crop Distribution by Barangay -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-geo-fill text-primary"></i> Crop Distribution by Barangay</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="cropDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seasonal Crop Production -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-calendar3 text-success"></i> Seasonal Crop Production</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="seasonalCropsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Sales Analytics Section (NEW) -->
                <section id="sales-analytics" class="mb-4">
                    <h5 class="section-title"><i class="bi bi-graph-up text-danger"></i> Sales Analytics</h5>
                    <div class="row">
                        <!-- Revenue Trend -->
                        <div class="col-md-8 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> Revenue Trend</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sales by Category -->
                        <div class="col-md-4 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-pie-chart-fill text-success"></i> Sales by Category</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sales Metrics Cards -->
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-stat-card">
                                <i class="bi bi-cash-coin text-success stat-icon"></i>
                                <div class="stat-title">Total Revenue</div>
                                <div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-stat-card">
                                <i class="bi bi-cart-check text-primary stat-icon"></i>
                                <div class="stat-title">Weekly Revenue</div>
                                <div class="stat-value">₱<?= number_format($weeklyRevenue, 2) ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-stat-card">
                                <i class="bi bi-truck text-info stat-icon"></i>
                                <div class="stat-title">Average Order</div>
                                <div class="stat-value">₱<?= number_format($averageOrderValue, 2) ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="dashboard-stat-card">
                                <i class="bi bi-bag-check text-warning stat-icon"></i>
                                <div class="stat-title">Top Category</div>
                                <div class="stat-value"><?= htmlspecialchars($topCategory) ?></div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Top Products Section (NEW) -->
                <section id="top-products" class="mb-4">
                    <div class="card table-container">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-trophy text-warning"></i> Top Performing Products</h5>
                            <a href="organization-head-sales-report.php" class="btn btn-sm btn-outline-success">View Full Report</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th class="text-right">Units Sold</th>
                                            <th class="text-right">Revenue</th>
                                            <th class="text-center">Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($topProducts)): ?>
                                            <?php foreach ($topProducts as $product): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                                    <td><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></td>
                                                    <td class="text-right"><?= $product['units_sold'] ?></td>
                                                    <td class="text-right">₱<?= number_format($product['revenue'], 2) ?></td>
                                                    <td class="text-center">
                                                        <?php if ($product['trend'] > 0): ?>
                                                            <span class="text-success"><i class="bi bi-arrow-up-circle-fill"></i></span>
                                                        <?php else: ?>
                                                            <span class="text-danger"><i class="bi bi-arrow-down-circle-fill"></i></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No product data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Farmer Management Overview (NEW) -->
                <section id="farmer-overview" class="mb-4">
                    <h5 class="section-title"><i class="bi bi-people text-info"></i> Farmer Management</h5>
                    <div class="row">
                        <!-- Farmer Distribution Map -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-geo-alt text-primary"></i> Farmer Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="farmerDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Farm Size Distribution -->
                        <div class="col-md-6 mb-4">
                            <div class="stat-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-grid text-success"></i> Farm Size Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="farmSizeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Inventory Management (NEW) -->
                <section id="inventory-management" class="mb-4">
                    <div class="card table-container">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-exclamation-circle text-warning"></i> Low Stock Products</h5>
                            <span class="badge badge-pill badge-warning"><?= count($lowStockProducts) ?> items</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Current Stock</th>
                                            <th class="text-center">Reorder Point</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($lowStockProducts)): ?>
                                            <?php foreach ($lowStockProducts as $product): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                                    <td class="text-center"><?= $product['stock'] ?></td>
                                                    <td class="text-center"><?= $product['reorder_point'] ?></td>
                                                    <td class="text-center">
                                                        <?php if ($product['stock'] == 0): ?>
                                                            <span class="badge badge-danger">Out of Stock</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Low Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No low stock products</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Activity Center -->
                <section id="activity-center" class="mb-4">
                    <h5 class="section-title"><i class="bi bi-activity text-danger"></i> Activity Center</h5>
                    <div class="row">
                        <!-- Today's Pickups -->
                        <div class="col-md-6 mb-4">
                            <div class="card activity-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-truck text-success"></i> Today's Pickups</h5>
                                    <span class="badge badge-pill badge-light"><?= count($todayPickups) ?> scheduled</span>
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
                                                    <?php if (!empty($pickup['contact_person'])): ?>
                                                    <small class="text-muted">
                                                        <i class="bi bi-person"></i> Contact: <?= htmlspecialchars($pickup['contact_person']) ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="bi bi-calendar-x"></i>
                                            <p>No pickups scheduled for today</p>
                                            <a href="pickup-scheduling.php" class="btn btn-sm btn-outline-primary mt-2">Schedule New Pickup</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer text-muted text-center">
                                    <a href="pickup-scheduling.php" class="text-decoration-none">View all pickups <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Summary -->
                        <div class="col-md-6 mb-4">
                            <div class="card activity-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-list-check text-warning"></i> Status Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="status-grid">
                                        <div class="status-item">
                                            <div class="status-icon bg-warning text-white">
                                                <i class="bi bi-hourglass-split"></i>
                                            </div>
                                            <div class="status-details">
                                                <div class="status-value"><?= $stats['pending_orders'] ?></div>
                                                <div class="status-label">Pending Orders</div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-item">
                                            <div class="status-icon bg-success text-white">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <div class="status-details">
                                                <div class="status-value"><?= $stats['completed_orders'] ?></div>
                                                <div class="status-label">Completed Orders</div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-item">
                                            <div class="status-icon bg-info text-white">
                                                <i class="bi bi-clock-history"></i>
                                            </div>
                                            <div class="status-details">
                                                <div class="status-value"><?= $stats['assigned_pickups'] ?></div>
                                                <div class="status-label">Assigned Pickups</div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-item">
                                            <div class="status-icon bg-danger text-white">
                                                <i class="bi bi-x-circle"></i>
                                            </div>
                                            <div class="status-details">
                                                <div class="status-value"><?= $stats['canceled_orders'] ?></div>
                                                <div class="status-label">Canceled Orders</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-muted text-center">
                                    <a href="organization-head-order-management.php" class="text-decoration-none">View order details <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
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
                if (window.pickupStatusChart) {
                    window.pickupStatusChart.update();
                }
            });
        });

        // Function to validate chart data before rendering
        function validateChartData(data) {
            if (!data || !data.labels || !data.datasets || data.datasets.length === 0) {
                console.error('Invalid chart data structure:', data);
                return false;
            }
            
            // Check if we have both labels and data
            if (data.labels.length === 0 || data.datasets[0].data.length === 0) {
                console.warn('Chart has no data to display');
                return false;
            }
            
            // Check if data matches labels
            if (data.labels.length !== data.datasets[0].data.length) {
                console.error('Chart labels and data count mismatch');
                return false;
            }
            
            return true;
        }

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
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${context.label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // Display a message when there's no data
                    $(orderStatusCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No order status data available</p></div>');
                }
            }
            
            // Pickup Status Chart
            const pickupStatusCtx = document.getElementById('pickupStatusChart');
            if (pickupStatusCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance to prevent duplicates
                if (window.pickupStatusChart instanceof Chart) {
                    window.pickupStatusChart.destroy();
                }
                
                const pickupChartData = {
                    labels: <?= json_encode($pickupStatusLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($pickupStatusValues) ?>,
                        backgroundColor: ['#ffc107', '#17a2b8', '#007bff', '#28a745', '#dc3545'],
                        borderWidth: 1
                    }]
                };

                if (validateChartData(pickupChartData)) {
                    window.pickupStatusChart = new Chart(pickupStatusCtx, {
                        type: 'pie',
                        data: pickupChartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${context.label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // Display a message when there's no data
                    $(pickupStatusCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No pickup status data available</p></div>');
                }
            }
            
            // Agricultural Distribution Chart
            const cropDistributionCtx = document.getElementById('cropDistributionChart');
            if (cropDistributionCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance
                if (window.cropDistributionChart instanceof Chart) {
                    window.cropDistributionChart.destroy();
                }
                
                const cropDistData = {
                    labels: <?= json_encode($cropBarangayLabels) ?>,
                    datasets: [{
                        label: 'Crop Distribution',
                        data: <?= json_encode($cropCounts) ?>,
                        backgroundColor: [
                            '#4CAF50', '#2196F3', '#9C27B0', '#FF9800', '#F44336',
                            '#3F51B5', '#009688', '#FF5722', '#607D8B', '#E91E63'
                        ],
                        borderWidth: 1
                    }]
                };
                
                if (validateChartData(cropDistData)) {
                    window.cropDistributionChart = new Chart(cropDistributionCtx, {
                        type: 'bar',
                        data: cropDistData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `Crops: ${context.raw}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Crops'
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $(cropDistributionCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No crop distribution data available</p></div>');
                }
            }
            
            // Seasonal Crop Production Chart
            const seasonalCropsCtx = document.getElementById('seasonalCropsChart');
            if (seasonalCropsCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance
                if (window.seasonalCropsChart instanceof Chart) {
                    window.seasonalCropsChart.destroy();
                }
                
                const seasonalCropsData = {
                    labels: <?= json_encode($seasonLabels) ?>,
                    datasets: [{
                        label: 'Seasonal Production',
                        data: <?= json_encode($seasonProduction) ?>,
                        backgroundColor: [
                            '#8BC34A', '#00BCD4', '#FFEB3B', '#9E9E9E', '#FFC107'
                        ],
                        borderWidth: 1
                    }]
                };
                
                if (validateChartData(seasonalCropsData)) {
                    window.seasonalCropsChart = new Chart(seasonalCropsCtx, {
                        type: 'doughnut',
                        data: seasonalCropsData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${context.label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $(seasonalCropsCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No seasonal crop data available</p></div>');
                }
            }
            
            // Revenue Trend Chart
            const revenueTrendCtx = document.getElementById('revenueTrendChart');
            if (revenueTrendCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance
                if (window.revenueTrendChart instanceof Chart) {
                    window.revenueTrendChart.destroy();
                }
                
                const revenueTrendData = {
                    labels: <?= json_encode($revenueDates) ?>,
                    datasets: [{
                        label: 'Revenue',
                        data: <?= json_encode($revenueAmounts) ?>,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                };
                
                if (validateChartData(revenueTrendData)) {
                    window.revenueTrendChart = new Chart(revenueTrendCtx, {
                        type: 'line',
                        data: revenueTrendData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `₱${context.raw.toLocaleString()}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Revenue (PHP)'
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $(revenueTrendCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No revenue trend data available</p></div>');
                }
            }
            
            // Sales by Category Chart
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance
                if (window.categoryChart instanceof Chart) {
                    window.categoryChart.destroy();
                }
                
                const categoryData = {
                    labels: <?= json_encode($categoryLabels) ?>,
                    datasets: [{
                        label: 'Sales by Category',
                        data: <?= json_encode($categoryValues) ?>,
                        backgroundColor: [
                            '#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1',
                            '#fd7e14', '#20c997', '#6c757d', '#007bff', '#6610f2'
                        ],
                        borderWidth: 1
                    }]
                };
                
                if (validateChartData(categoryData)) {
                    window.categoryChart = new Chart(categoryCtx, {
                        type: 'pie',
                        data: categoryData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 10,
                                        usePointStyle: true,
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${context.label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $(categoryCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No category sales data available</p></div>');
                }
            }
            
            // Farmer Distribution Chart
            const farmerDistributionCtx = document.getElementById('farmerDistributionChart');
            if (farmerDistributionCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance
                if (window.farmerDistributionChart instanceof Chart) {
                    window.farmerDistributionChart.destroy();
                }
                
                const farmerDistData = {
                    labels: <?= json_encode($farmerDistBarangays) ?>,
                    datasets: [{
                        data: <?= json_encode($farmerDistCounts) ?>,
                        backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', 
                            '#6c757d', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'
                        ],
                        borderWidth: 1
                    }]
                };
                
                if (validateChartData(farmerDistData)) {
                    window.farmerDistributionChart = new Chart(farmerDistributionCtx, {
                        type: 'bar',
                        data: farmerDistData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            indexAxis: 'y',
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Farmers'
                                    }
                                },
                                y: {
                                    ticks: {
                                        autoSkip: false,
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $(farmerDistributionCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No farmer distribution data available</p></div>');
                }
            }
            
            // Farm Size Distribution Chart
            const farmSizeCtx = document.getElementById('farmSizeChart');
            if (farmSizeCtx && typeof Chart !== 'undefined') {
                // Clear any existing chart instance
                if (window.farmSizeChart instanceof Chart) {
                    window.farmSizeChart.destroy();
                }
                
                const farmSizeData = {
                    labels: <?= json_encode($farmSizeBarangays) ?>,
                    datasets: [{
                        label: 'Farm Size (hectares)',
                        data: <?= json_encode($farmSizeValues) ?>,
                        backgroundColor: 'rgba(40, 167, 69, 0.5)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }]
                };
                
                if (validateChartData(farmSizeData)) {
                    window.farmSizeChart = new Chart(farmSizeCtx, {
                        type: 'polarArea',
                        data: farmSizeData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 10,
                                        usePointStyle: true,
                                        font: {
                                            size: 9
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `${context.label}: ${context.raw.toFixed(2)} hectares`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                r: {
                                    ticks: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                } else {
                    $(farmSizeCtx).parent().html('<div class="empty-chart-message"><i class="bi bi-exclamation-circle"></i><p>No farm size data available</p></div>');
                }
            }
            
            // Update charts on window resize
            $(window).resize(function() {
                if (window.cropDistributionChart) window.cropDistributionChart.update();
                if (window.seasonalCropsChart) window.seasonalCropsChart.update();
                if (window.revenueTrendChart) window.revenueTrendChart.update();
                if (window.categoryChart) window.categoryChart.update();
                if (window.farmerDistributionChart) window.farmerDistributionChart.update();
                if (window.farmSizeChart) window.farmSizeChart.update();
            });
        }
    </script>
</body>
</html>