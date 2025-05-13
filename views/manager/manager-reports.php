<?php
// Start the session to track login status
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Database Connection
$database = new Database();
$conn = $database->connect();
$log = new Log();

$manager_user_id = $_SESSION['manager_user_id'] ?? null;

// Create required database views if they don't exist
try {
    // Check if view_crops_per_barangay exists
    $viewCheckQuery = "SELECT COUNT(*) FROM information_schema.views 
                      WHERE table_schema = DATABASE() 
                      AND table_name = 'view_crops_per_barangay'";
    $viewExists = $conn->query($viewCheckQuery)->fetchColumn();
    
    if (!$viewExists) {
        // Create view_crops_per_barangay
        $createViewQuery = "CREATE VIEW view_crops_per_barangay AS
                           SELECT b.barangay_id, b.barangay_name, 
                                  p.product_id, p.name as product_name,
                                  pc.category_name,
                                  COUNT(distinct bp.id) AS production_instances,
                                  SUM(bp.estimated_production) AS total_production,
                                  bp.production_unit,
                                  SUM(bp.planted_area) AS total_planted_area,
                                  bp.area_unit
                           FROM barangays b
                           LEFT JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
                           LEFT JOIN products p ON bp.product_id = p.product_id
                           LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                           LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id
                           GROUP BY b.barangay_id, b.barangay_name, p.product_id, p.name, pc.category_name, bp.production_unit, bp.area_unit";
        $conn->exec($createViewQuery);
        error_log("Manager created view_crops_per_barangay");
    }
    
    // Check if view_farmers_per_barangay exists
    $viewCheckQuery = "SELECT COUNT(*) FROM information_schema.views 
                      WHERE table_schema = DATABASE() 
                      AND table_name = 'view_farmers_per_barangay'";
    $viewExists = $conn->query($viewCheckQuery)->fetchColumn();
    
    if (!$viewExists) {
        // Create view_farmers_per_barangay
        $createViewQuery = "CREATE VIEW view_farmers_per_barangay AS
                           SELECT b.barangay_id, b.barangay_name,
                                  COUNT(fd.user_id) AS farmer_count,
                                  SUM(fd.farm_size) AS total_farm_area
                           FROM barangays b
                           LEFT JOIN farmer_details fd ON b.barangay_id = fd.barangay_id
                           LEFT JOIN users u ON fd.user_id = u.user_id AND u.role_id = 2
                           GROUP BY b.barangay_id, b.barangay_name";
        $conn->exec($createViewQuery);
        error_log("Manager created view_farmers_per_barangay");
    }
    
    // Check if view_seasonal_crops exists
    $viewCheckQuery = "SELECT COUNT(*) FROM information_schema.views 
                      WHERE table_schema = DATABASE() 
                      AND table_name = 'view_seasonal_crops'";
    $viewExists = $conn->query($viewCheckQuery)->fetchColumn();
    
    if (!$viewExists) {
        // Create view_seasonal_crops
        $createViewQuery = "CREATE VIEW view_seasonal_crops AS
                           SELECT b.barangay_name, 
                                  p.name AS product_name,
                                  cs.season_name,
                                  cs.start_month,
                                  cs.end_month,
                                  SUM(bp.estimated_production) AS total_production,
                                  bp.production_unit,
                                  SUM(bp.planted_area) AS total_planted_area,
                                  bp.area_unit
                           FROM barangay_products bp
                           JOIN barangays b ON bp.barangay_id = b.barangay_id
                           JOIN products p ON bp.product_id = p.product_id
                           JOIN crop_seasons cs ON bp.season_id = cs.season_id
                           GROUP BY b.barangay_name, p.name, cs.season_name, cs.start_month, cs.end_month, bp.production_unit, bp.area_unit
                           ORDER BY b.barangay_name ASC, cs.start_month ASC";
        $conn->exec($createViewQuery);
        error_log("Manager created view_seasonal_crops");
    }
} catch (PDOException $e) {
    error_log("Error creating database views in manager reports: " . $e->getMessage());
}

// Handle date range filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Add WHERE clause for date filtering
$dateFilterClause = " WHERE o.order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";

// Handle Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="market_report_' . $startDate . '_to_' . $endDate . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Report Type', 'Category', 'Count', 'Date Range']);
    
    // Export Order Summary
    $orderSummaryQuery = "SELECT order_status, COUNT(*) AS total 
                          FROM orders 
                          WHERE order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' 
                          GROUP BY order_status";
    $orderSummaryStmt = $conn->query($orderSummaryQuery);
    while ($row = $orderSummaryStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, ['Order', ucfirst($row['order_status']), $row['total'], "$startDate to $endDate"]);
    }
    
    // Export Pickup Summary
    $pickupSummaryQuery = "SELECT p.pickup_status, COUNT(*) AS total 
                           FROM pickups p 
                           JOIN orders o ON p.order_id = o.order_id 
                           WHERE o.order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' 
                           GROUP BY p.pickup_status";
    $pickupSummaryStmt = $conn->query($pickupSummaryQuery);
    while ($row = $pickupSummaryStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, ['Pickup', ucfirst($row['pickup_status']), $row['total'], "$startDate to $endDate"]);
    }
    
    // Export Product Summary
    $productSummaryQuery = "SELECT status, COUNT(*) AS total 
                            FROM products 
                            WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' 
                            GROUP BY status";
    $productSummaryStmt = $conn->query($productSummaryQuery);
    while ($row = $productSummaryStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, ['Product', ucfirst($row['status']), $row['total'], "$startDate to $endDate"]);
    }
    
    // Export Order Details
    $orderDetailsQuery = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date,
                         p.pickup_status, p.pickup_date, SUM(oi.price * oi.quantity) AS order_total 
                         FROM orders o
                         JOIN users u ON o.consumer_id = u.user_id
                         LEFT JOIN pickups p ON o.order_id = p.order_id
                         LEFT JOIN orderitems oi ON o.order_id = oi.order_id
                         WHERE o.order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                         GROUP BY o.order_id
                         ORDER BY o.order_date DESC";
    $orderDetailsStmt = $conn->query($orderDetailsQuery);
    
    // Add a separator line
    fputcsv($output, []);
    fputcsv($output, ['Detailed Order Report', $startDate, 'to', $endDate]);
    fputcsv($output, ['Order ID', 'Customer', 'Order Status', 'Order Date', 'Pickup Status', 'Pickup Date', 'Order Total']);
    
    while ($row = $orderDetailsStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['order_id'],
            $row['consumer_name'],
            ucfirst($row['order_status']),
            date("Y-m-d H:i", strtotime($row['order_date'])),
            ucfirst($row['pickup_status'] ?? 'Not Set'),
            $row['pickup_date'] ? date("Y-m-d H:i", strtotime($row['pickup_date'])) : 'Not Scheduled',
            '₱' . number_format($row['order_total'] ?? 0, 2)
        ]);
    }
    
    // Close the output stream
    fclose($output);
    exit;
}

// Fetch Order Summary - updated to match actual schema
$orderSummaryQuery = "SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status";
$orderSummaryStmt = $conn->query($orderSummaryQuery);
$orderSummary = $orderSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Pickup Summary - Updated to use actual pickup_status values
$pickupSummaryQuery = "SELECT pickup_status, COUNT(*) AS total FROM pickups GROUP BY pickup_status";
$pickupSummaryStmt = $conn->query($pickupSummaryQuery);
$pickupSummary = $pickupSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Product Summary - using actual status values in the products table
$productSummaryQuery = "SELECT status, COUNT(*) AS total FROM products GROUP BY status";
$productSummaryStmt = $conn->query($productSummaryQuery);
$productSummary = $productSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Orders with customer and pickup information
$recentOrdersQuery = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, 
                      p.pickup_id, p.pickup_date, p.office_location AS pickup_location, p.pickup_status,
                      p.contact_person, p.pickup_notes
                      FROM orders AS o
                      JOIN users AS u ON o.consumer_id = u.user_id
                      LEFT JOIN pickups AS p ON o.order_id = p.order_id
                      ORDER BY o.order_date DESC
                      LIMIT 10";
$recentOrdersStmt = $conn->query($recentOrdersQuery);
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch agricultural data for reports
try {
    // Get crops per barangay data
    $cropsPerBarangayQuery = "SELECT * FROM view_crops_per_barangay ORDER BY barangay_name, total_production DESC";
    $cropsPerBarangayStmt = $conn->query($cropsPerBarangayQuery);
    $cropsPerBarangay = $cropsPerBarangayStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get farmers per barangay data
    $farmersPerBarangayQuery = "SELECT * FROM view_farmers_per_barangay ORDER BY farmer_count DESC";
    $farmersPerBarangayStmt = $conn->query($farmersPerBarangayQuery);
    $farmersPerBarangay = $farmersPerBarangayStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get seasonal crops data
    $seasonalCropsQuery = "SELECT * FROM view_seasonal_crops ORDER BY season_name, total_production DESC";
    $seasonalCropsStmt = $conn->query($seasonalCropsQuery);
    $seasonalCrops = $seasonalCropsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all barangays to ensure we display all 26 barangays in Valencia
    $allBarangaysQuery = "SELECT * FROM barangays WHERE municipality = 'Valencia' ORDER BY barangay_name";
    $allBarangaysStmt = $conn->query($allBarangaysQuery);
    $allBarangays = $allBarangaysStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get complete barangay data including those without crop production
    $completeBarangayDataQuery = "
        SELECT 
            b.barangay_id,
            b.barangay_name,
            COALESCE(COUNT(DISTINCT bp.product_id), 0) as crop_count,
            COALESCE(COUNT(DISTINCT fd.user_id), 0) as farmer_count,
            COALESCE(SUM(fd.farm_size), 0) as total_farm_area,
            COALESCE(SUM(bp.planted_area), 0) as total_planted_area
        FROM barangays b
        LEFT JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
        LEFT JOIN farmer_details fd ON b.barangay_id = fd.barangay_id
        GROUP BY b.barangay_id, b.barangay_name
        ORDER BY b.barangay_name";
    $completeBarangayDataStmt = $conn->query($completeBarangayDataQuery);
    $completeBarangayData = $completeBarangayDataStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching agricultural data for manager reports: " . $e->getMessage());
    $cropsPerBarangay = [];
    $farmersPerBarangay = [];
    $seasonalCrops = [];
    $allBarangays = [];
    $completeBarangayData = [];
}

// Count pending orders for badge
$pendingOrdersQuery = "SELECT COUNT(*) AS pending_count FROM orders WHERE order_status = 'pending'";
$pendingOrdersStmt = $conn->query($pendingOrdersQuery);
$pendingOrders = $pendingOrdersStmt->fetch(PDO::FETCH_ASSOC);
$pendingOrderCount = $pendingOrders['pending_count'];

// Count today's pickups
$todayPickupsQuery = "SELECT COUNT(*) AS today_count FROM pickups 
                      WHERE DATE(pickup_date) = CURDATE()";
$todayPickupsStmt = $conn->query($todayPickupsQuery);
$todayPickups = $todayPickupsStmt->fetch(PDO::FETCH_ASSOC);
$todayPickupCount = $todayPickups['today_count'];

// Handle logout
if (isset($_POST['logout'])) {
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Manager logged out");
    }
    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 1px solid #f8f9fa;
            padding-bottom: 5px;
        }
        .chart-container {
            position: relative;
            height: 120px;
            margin-bottom: 0;
        }
        .badge-pill {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 0.25rem 0.75rem;
            font-size: 0.9rem;
        }
        #pendingOrdersBadge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.7rem;
        }
        .manager-header {
            background: linear-gradient(135deg, #1a8754 0%, #34c38f 100%);
            color: white;
            padding: 10px 0;
        }
        .manager-badge {
            background-color: #157347;
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
            padding-bottom: 1rem;
            position: relative;
        }
        .page-header:after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: linear-gradient(90deg, #28a745, transparent);
        }
        .page-header h1 {
            background: linear-gradient(45deg, #212121, #28a745);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .stats-card {
            border-radius: 8px;
            padding: 12px;
            transition: transform 0.3s ease;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .stats-card .icon {
            font-size: 1.75rem;
        }
        .stats-card .count {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
        .stats-card .title {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }
        .card-body {
            padding: 0.75rem;
        }
        .list-group {
            margin-top: 0.5rem !important;
        }
        /* New styles for export section */
        .export-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid #28a745;
        }
        
        .export-section h5 {
            font-weight: 600;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .date-picker-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .date-picker-container .form-group {
            margin-bottom: 0;
            flex-grow: 1;
            min-width: 140px;
        }
        
        .export-btn {
            background-color: #28a745;
            border-color: #28a745;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background-color: #218838;
            border-color: #1e7e34;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .date-picker-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-picker-container .form-group {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-bar-chart-line"></i> REPORTS MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4">
                <!-- Update breadcrumb styling -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reports</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-bar-chart-line-fill text-success"></i> Reports Overview</h1>
                        <p class="text-muted">View and analyze system performance metrics</p>
                        <div>
                            <?php if($pendingOrderCount > 0): ?>
                                <span class="badge badge-warning"><?= $pendingOrderCount ?> pending orders</span>
                            <?php endif; ?>
                            <?php if($todayPickupCount > 0): ?>
                                <span class="badge badge-info ml-2"><?= $todayPickupCount ?> pickups today</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- New Export Section -->
                <div class="export-section">
                    <h5><i class="bi bi-file-earmark-spreadsheet"></i> Generate Report</h5>
                    <form id="exportForm" method="GET" action="manager-reports.php">
                        <div class="date-picker-container">
                            <div class="form-group">
                                <label for="start_date">Start Date:</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?= htmlspecialchars($startDate) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date:</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?= htmlspecialchars($endDate) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="report_type">Report Format:</label>
                                <select class="form-control" id="report_type" name="report_type">
                                    <option value="csv">CSV (Excel Compatible)</option>
                                </select>
                            </div>
                            <div class="form-group d-flex align-items-end">
                                <input type="hidden" name="export" value="csv">
                                <button type="submit" class="btn btn-success export-btn">
                                    <i class="bi bi-download"></i> Export Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Quick Statistics -->
                <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                        <div class="stats-card bg-white">
                            <div class="d-flex align-items-center">
                                <div class="icon text-primary mr-3">
                                    <i class="bi bi-cart-fill"></i>
                                </div>
                                <div>
                                    <p class="title">Total Orders</p>
                                    <p class="count"><?= array_sum(array_column($orderSummary, 'total')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card bg-white">
                            <div class="d-flex align-items-center">
                                <div class="icon text-success mr-3">
                                    <i class="bi bi-truck"></i>
                                </div>
                                <div>
                                    <p class="title">Total Pickups</p>
                                    <p class="count"><?= array_sum(array_column($pickupSummary, 'total')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card bg-white">
                            <div class="d-flex align-items-center">
                                <div class="icon text-warning mr-3">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div>
                                    <p class="title">Total Products</p>
                                    <p class="count"><?= array_sum(array_column($productSummary, 'total')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="row mb-4">
                    <!-- Order Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-cart-fill me-2 text-primary"></i> Order Summary
                                </h5>
                                <div class="chart-container">
                                    <canvas id="orderChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($orderSummary as $order): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($order['order_status']) ?>
                                            <?php
                                            $badgeClass = 'primary';
                                            switch($order['order_status']) {
                                                case 'pending': $badgeClass = 'warning'; break;
                                                case 'completed': $badgeClass = 'success'; break;
                                                case 'canceled': $badgeClass = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?> badge-pill"><?= $order['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Pickup Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-truck me-2 text-success"></i> Pickup Summary
                                </h5>
                                <div class="chart-container">
                                    <canvas id="pickupChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($pickupSummary as $pickup): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($pickup['pickup_status']) ?>
                                            <?php
                                            $badgeClass = 'primary';
                                            switch($pickup['pickup_status']) {
                                                case 'pending': $badgeClass = 'warning'; break;
                                                case 'assigned': $badgeClass = 'info'; break;
                                                case 'completed': $badgeClass = 'success'; break;
                                                case 'cancelled': $badgeClass = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?> badge-pill"><?= $pickup['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Product Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-box-seam-fill me-2 text-warning"></i> Product Summary
                                </h5>
                                <div class="chart-container">
                                    <canvas id="productChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($productSummary as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($product['status']) ?>
                                            <?php
                                            $badgeClass = 'primary';
                                            switch($product['status']) {
                                                case 'pending': $badgeClass = 'warning'; break;
                                                case 'approved': $badgeClass = 'success'; break;
                                                case 'rejected': $badgeClass = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?> badge-pill"><?= $product['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="bi bi-clock-history me-2 text-info"></i> Recent Orders
                            <?php if($pendingOrderCount > 0): ?>
                                <span class="position-relative ml-2">
                                    <i class="bi bi-bell-fill text-secondary"></i>
                                    <span id="pendingOrdersBadge" class="badge badge-danger badge-pill"><?= $pendingOrderCount ?></span>
                                </span>
                            <?php endif; ?>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Order Status</th>
                                        <th>Pickup Status</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentOrders) > 0): ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                                <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $orderStatusClass = 'secondary';
                                                    switch($order['order_status']) {
                                                        case 'pending': $orderStatusClass = 'warning'; break;
                                                        case 'completed': $orderStatusClass = 'success'; break;
                                                        case 'canceled': $orderStatusClass = 'danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?= $orderStatusClass ?>">
                                                        <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $pickupStatusClass = 'secondary';
                                                    switch($order['pickup_status'] ?? '') {
                                                        case 'pending': $pickupStatusClass = 'warning'; break;
                                                        case 'scheduled': $pickupStatusClass = 'primary'; break;
                                                        case 'assigned': $pickupStatusClass = 'info'; break;
                                                        case 'completed': $pickupStatusClass = 'success'; break;
                                                        case 'cancelled': $pickupStatusClass = 'danger'; break;
                                                        default: $pickupStatusClass = 'secondary';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?= $pickupStatusClass ?>">
                                                        <?= htmlspecialchars(ucfirst($order['pickup_status'] ?? 'Not Set')) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-info btn-sm view-pickup-details-btn"
                                                            data-pickup-id="<?= htmlspecialchars($order['pickup_id'] ?? '') ?>"
                                                            data-pickup-date="<?= htmlspecialchars($order['pickup_date'] ?? '') ?>"
                                                            data-pickup-location="<?= htmlspecialchars($order['pickup_location'] ?? 'Municipal Agriculture Office') ?>"
                                                            data-contact-person="<?= htmlspecialchars($order['contact_person'] ?? '') ?>"
                                                            data-pickup-notes="<?= htmlspecialchars($order['pickup_notes'] ?? '') ?>"
                                                            data-toggle="modal" data-target="#pickupDetailsModal">
                                                        <i class="bi bi-info-circle"></i> View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No recent orders found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Agricultural Analytics Section -->
                <div class="row mb-4">
                    <!-- Crops per Barangay -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-bar-chart me-2 text-success"></i> Crops per Barangay
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Barangay</th>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Production</th>
                                                <th>Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($cropsPerBarangay) > 0): ?>
                                                <?php foreach ($cropsPerBarangay as $crop): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($crop['barangay_name']) ?></td>
                                                        <td><?= htmlspecialchars($crop['product_name']) ?></td>
                                                        <td><?= htmlspecialchars($crop['category_name']) ?></td>
                                                        <td><?= htmlspecialchars($crop['total_production']) ?></td>
                                                        <td><?= htmlspecialchars($crop['production_unit']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No crop data found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Farmers per Barangay -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-people me-2 text-primary"></i> Farmers per Barangay
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Barangay</th>
                                                <th>Farmer Count</th>
                                                <th>Total Farm Area</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($farmersPerBarangay) > 0): ?>
                                                <?php foreach ($farmersPerBarangay as $farmer): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($farmer['barangay_name']) ?></td>
                                                        <td><?= htmlspecialchars($farmer['farmer_count']) ?></td>
                                                        <td><?= htmlspecialchars($farmer['total_farm_area']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No farmer data found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seasonal Crops -->
                <div class="row mb-4">
                    <div class="col-md-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-calendar4-week me-2 text-warning"></i> Seasonal Crops
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Barangay</th>
                                                <th>Product</th>
                                                <th>Season</th>
                                                <th>Start Month</th>
                                                <th>End Month</th>
                                                <th>Production</th>
                                                <th>Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($seasonalCrops) > 0): ?>
                                                <?php foreach ($seasonalCrops as $crop): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($crop['barangay_name']) ?></td>
                                                        <td><?= htmlspecialchars($crop['product_name']) ?></td>
                                                        <td><?= htmlspecialchars($crop['season_name']) ?></td>
                                                        <td><?= htmlspecialchars($crop['start_month']) ?></td>
                                                        <td><?= htmlspecialchars($crop['end_month']) ?></td>
                                                        <td><?= htmlspecialchars($crop['total_production']) ?></td>
                                                        <td><?= htmlspecialchars($crop['production_unit']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No seasonal crop data found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Crop Production Visualization -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center mb-4">
                                    <i class="bi bi-graph-up text-success me-2"></i> Crop Production Visualization
                                </h5>
                                
                                <!-- Visualization type selector -->
                                <div class="btn-group mb-3" role="group" aria-label="Data view options">
                                    <button type="button" class="btn btn-outline-success active" data-chart-view="production">
                                        <i class="bi bi-basket"></i> Production Volume
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" data-chart-view="area">
                                        <i class="bi bi-grid"></i> Planted Area
                                    </button>
                                    <button type="button" class="btn btn-outline-info" data-chart-view="yieldRate">
                                        <i class="bi bi-arrow-up-right"></i> Yield Rate
                                    </button>
                                </div>
                                
                                <!-- Chart canvas -->
                                <div style="height: 400px;">
                                    <canvas id="cropProductionChart"></canvas>
                                </div>
                                
                                <!-- Legend and explanation -->
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="alert alert-light border">
                                            <h6><i class="bi bi-info-circle"></i> About this visualization:</h6>
                                            <p class="small mb-1">This chart displays agricultural data by barangay. Toggle between views:</p>
                                            <ul class="small">
                                                <li><strong>Production Volume:</strong> Total crop production per barangay</li>
                                                <li><strong>Planted Area:</strong> Land area used for cultivation (in hectares)</li>
                                                <li><strong>Yield Rate:</strong> Production efficiency (volume per hectare)</li>
                                            </ul>
                                            <p class="small mb-0">Hover over chart elements to see detailed information about crops grown in each barangay.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Pickup Details Modal -->
    <div class="modal fade" id="pickupDetailsModal" tabindex="-1" role="dialog" aria-labelledby="pickupDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="pickupDetailsModalLabel">
                        <i class="bi bi-truck"></i> Pickup Details
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 bg-light">
                        <div class="card-body">
                            <p><strong><i class="bi bi-tag"></i> Pickup ID:</strong> <span id="pickup-id" class="text-primary"></span></p>
                            <p><strong><i class="bi bi-calendar-event"></i> Pickup Date:</strong> <span id="pickup-date"></span></p>
                            <p><strong><i class="bi bi-geo-alt"></i> Pickup Location:</strong> <span id="pickup-location"></span></p>
                            <p><strong><i class="bi bi-person"></i> Contact Person:</strong> <span id="contact-person"></span></p>
                            <hr>
                            <p><strong><i class="bi bi-sticky"></i> Pickup Notes:</strong></p>
                            <div id="pickup-notes" class="p-2 border rounded bg-white"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <a href="manager-pickup-management.php" class="btn btn-primary">
                        <i class="bi bi-truck"></i> Manage Pickups
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Chart.js Script -->
    <script>
        // Chart.js global options
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.plugins.legend.position = 'bottom';
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.7)';
        
        // Order Summary Chart
        const orderChartColors = ['#ffc107', '#28a745', '#dc3545', '#17a2b8', '#6610f2'];
        const orderCtx = document.getElementById('orderChart').getContext('2d');
        const orderChart = new Chart(orderCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($orderSummary, 'order_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($orderSummary, 'total')) ?>,
                    backgroundColor: orderChartColors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Pickup Summary Chart
        const pickupChartColors = ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2'];
        const pickupCtx = document.getElementById('pickupChart').getContext('2d');
        const pickupChart = new Chart(pickupCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($pickupSummary, 'pickup_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($pickupSummary, 'total')) ?>,
                    backgroundColor: pickupChartColors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Product Summary Chart
        const productChartColors = ['#ffc107', '#28a745', '#dc3545', '#17a2b8', '#6610f2'];
        const productCtx = document.getElementById('productChart').getContext('2d');
        const productChart = new Chart(productCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($productSummary, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($productSummary, 'total')) ?>,
                    backgroundColor: productChartColors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Add validation for date range form
        document.addEventListener('DOMContentLoaded', function() {
            const exportForm = document.getElementById('exportForm');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            exportForm.addEventListener('submit', function(e) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                const today = new Date();
                
                // Clear any previous error messages
                document.querySelectorAll('.date-error').forEach(el => el.remove());
                
                // Validate date range
                if (startDate > endDate) {
                    e.preventDefault();
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'alert alert-danger date-error mt-2';
                    errorMsg.textContent = 'Start date cannot be after end date';
                    exportForm.appendChild(errorMsg);
                    return false;
                }
                
                // Validate against future dates
                if (endDate > today) {
                    e.preventDefault();
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'alert alert-danger date-error mt-2';
                    errorMsg.textContent = 'End date cannot be in the future';
                    exportForm.appendChild(errorMsg);
                    return false;
                }
                
                // Validate range isn't too large (example: limit to 1 year)
                const oneYearInMs = 365 * 24 * 60 * 60 * 1000;
                if (endDate - startDate > oneYearInMs) {
                    e.preventDefault();
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'alert alert-warning date-error mt-2';
                    errorMsg.textContent = 'Date range exceeds 1 year. Please select a shorter period for best performance.';
                    exportForm.appendChild(errorMsg);
                    return false;
                }
            });
        });
        
        // Fix the HTML comment in JavaScript context
        // Modal event handlers for pickup details
        document.addEventListener('DOMContentLoaded', function() {
            // Get all buttons that open the pickup details modal
            const viewDetailsButtons = document.querySelectorAll('.view-pickup-details-btn');
            
            // Add click event listener to each button
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Get data from button attributes
                    const pickupId = this.getAttribute('data-pickup-id') || 'Not assigned';
                    const pickupDate = this.getAttribute('data-pickup-date') || 'Not scheduled';
                    const pickupLocation = this.getAttribute('data-pickup-location') || 'Not specified';
                    const contactPerson = this.getAttribute('data-contact-person') || 'Not specified';
                    const pickupNotes = this.getAttribute('data-pickup-notes') || 'No notes available';
                    
                    // Set modal content
                    document.getElementById('pickup-id').textContent = pickupId;
                    document.getElementById('pickup-date').textContent = pickupDate ? new Date(pickupDate).toLocaleString() : 'Not scheduled';
                    document.getElementById('pickup-location').textContent = pickupLocation;
                    document.getElementById('contact-person').textContent = contactPerson;
                    document.getElementById('pickup-notes').textContent = pickupNotes;
                });
            });
        });

        // Crop Production Visualization Chart
        const cropProductionCtx = document.getElementById('cropProductionChart').getContext('2d');
        // Use complete barangay data to ensure all 26 barangays are displayed
        let cropProductionData = <?= json_encode($cropsPerBarangay) ?>;
        let completeBarangayData = <?= json_encode($completeBarangayData) ?>;
        let allBarangays = <?= json_encode($allBarangays) ?>;
        let cropProductionChart;
        
        function initCropProductionChart(viewType = 'production') {
            // Process data for the chart
            const barangayData = {};
            
            // Initialize all 26 barangays first (even those without crop data)
            allBarangays.forEach(barangay => {
                barangayData[barangay.barangay_name] = {
                    barangay_id: barangay.barangay_id,
                    production: 0,
                    area: 0,
                    yieldRate: 0,
                    crops: []
                };
            });
            
            // Add complete barangay metrics
            completeBarangayData.forEach(item => {
                if (barangayData[item.barangay_name]) {
                    barangayData[item.barangay_name].farmer_count = parseInt(item.farmer_count || 0);
                    barangayData[item.barangay_name].crop_count = parseInt(item.crop_count || 0);
                    barangayData[item.barangay_name].area = parseFloat(item.total_planted_area || 0);
                    barangayData[item.barangay_name].farm_area = parseFloat(item.total_farm_area || 0);
                }
            });
            
            // Now add detailed crop production data
            cropProductionData.forEach(item => {
                if (barangayData[item.barangay_name]) {
                    // Add production values
                    barangayData[item.barangay_name].production += parseFloat(item.total_production || 0);
                    
                    // Store crop info for tooltip
                    barangayData[item.barangay_name].crops.push({
                        name: item.product_name,
                        production: item.total_production,
                        area: item.total_planted_area,
                        unit: item.production_unit,
                        areaUnit: item.area_unit
                    });
                }
            });
            
            // Calculate yield rates
            Object.keys(barangayData).forEach(barangay => {
                if (barangayData[barangay].area > 0) {
                    barangayData[barangay].yieldRate = 
                        barangayData[barangay].production / barangayData[barangay].area;
                }
            });
            
            // Sort barangays by the selected view type, but ensure all barangays are included
            const sortedBarangays = Object.keys(barangayData).sort((a, b) => {
                return barangayData[b][viewType] - barangayData[a][viewType];
            });
            
            // Prepare chart data based on view type
            const chartData = {
                labels: sortedBarangays,
                datasets: [{
                    label: viewType === 'production' ? 'Production Volume' : 
                           viewType === 'area' ? 'Planted Area' : 'Yield Rate',
                    data: sortedBarangays.map(b => barangayData[b][viewType]),
                    backgroundColor: viewType === 'production' ? '#28a745' : 
                                    viewType === 'area' ? '#007bff' : '#17a2b8',
                    borderColor: viewType === 'production' ? '#1e7e34' : 
                                viewType === 'area' ? '#0056b3' : '#117a8b',
                    borderWidth: 1
                }]
            };
            
            // Destroy previous chart instance if it exists
            if (cropProductionChart) {
                cropProductionChart.destroy();
            }
            
            // Create new chart
            cropProductionChart = new Chart(cropProductionCtx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: viewType === 'production' ? 'Production Volume' : 
                                    viewType === 'area' ? 'Planted Area (hectares)' : 'Yield Rate (production/area)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Barangays of Valencia, Negros Oriental (All 26)'
                            },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 90,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                afterTitle: function(tooltipItems) {
                                    const barangay = tooltipItems[0].label;
                                    const barangayInfo = barangayData[barangay];
                                    
                                    return [
                                        `Farmers: ${barangayInfo.farmer_count || 0}`,
                                        `Crop Types: ${barangayInfo.crop_count || 0}`
                                    ];
                                },
                                afterBody: function(tooltipItems) {
                                    const barangay = tooltipItems[0].label;
                                    
                                    if (!barangayData[barangay].crops || barangayData[barangay].crops.length === 0) {
                                        return ['No crop data available'];
                                    }
                                    
                                    // Get top 3 crops by production for this barangay
                                    const topCrops = barangayData[barangay].crops
                                        .sort((a, b) => b.production - a.production)
                                        .slice(0, 3);
                                    
                                    if (topCrops.length === 0) return ['No crops recorded'];
                                    
                                    return ['Top Crops:'].concat(topCrops.map(crop => 
                                        `${crop.name}: ${crop.production} ${crop.unit}`));
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: viewType === 'production' ? 'Crop Production by Barangay' : 
                                viewType === 'area' ? 'Planted Area by Barangay' : 'Agricultural Yield Rate by Barangay',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize chart with production view by default
        document.addEventListener('DOMContentLoaded', function() {
            initCropProductionChart('production');
            
            // Handle view toggle buttons
            document.querySelectorAll('[data-chart-view]').forEach(button => {
                button.addEventListener('click', function() {
                    const viewType = this.getAttribute('data-chart-view');
                    
                    // Update active button
                    document.querySelectorAll('[data-chart-view]').forEach(btn => 
                        btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update chart
                    initCropProductionChart(viewType);
                });
            });
        });
    </script>
</body>
</html>