<?php
// Start the session to track login status
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';  // Include Log model for activity logging
require_once '../../controllers/GeoAnalyticsController.php';  // Include GeoAnalyticsController

// Database Connection
$database = new Database();
$conn = $database->connect();

// Initialize GeoAnalyticsController
$geoAnalytics = new GeoAnalyticsController();

// Fetch Geographical Analytics data using the controller
$cropsPerBarangay = $geoAnalytics->getCropProductionByBarangay();
$farmersPerBarangay = $geoAnalytics->getFarmerDistribution();
$seasonalCrops = $geoAnalytics->getSeasonalCropProduction();
$baranguayEfficiencyMetrics = $geoAnalytics->getBarangayEfficiencyMetrics();

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
                           JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
                           JOIN products p ON bp.product_id = p.product_id
                           LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                           LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id
                           GROUP BY b.barangay_id, b.barangay_name, p.product_id, p.name, pc.category_name, bp.production_unit, bp.area_unit";
        $conn->exec($createViewQuery);
        error_log("Created view_crops_per_barangay");
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
        error_log("Created view_farmers_per_barangay");
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
        error_log("Created view_seasonal_crops");
    }
} catch (PDOException $e) {
    error_log("Error creating database views: " . $e->getMessage());
}

// Get Admin User ID from Session - For logging
$admin_user_id = $_SESSION['admin_user_id'] ?? null;

// Process date range filter
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Add date filter condition if dates are provided
$dateFilterCondition = '';
if ($startDate && $endDate) {
    $dateFilterCondition = " WHERE o.order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
    
    // Log the report generation activity
    if ($admin_user_id) {
        $log = new Log();
        $log->logActivity($admin_user_id, "Generated date-range report from $startDate to $endDate");
    }
}

// Fetch Order Summary with date filter
$orderSummaryQuery = "SELECT order_status, COUNT(*) AS total FROM orders o";
$orderSummaryQuery .= $dateFilterCondition;
$orderSummaryQuery .= " GROUP BY order_status";
$orderSummaryStmt = $conn->query($orderSummaryQuery);
$orderSummary = $orderSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Payment Summary with date filter
$paymentSummaryQuery = "SELECT payment_status, COUNT(*) AS total FROM payments p";
if ($startDate && $endDate) {
    $paymentSummaryQuery .= " WHERE p.payment_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
}
$paymentSummaryQuery .= " GROUP BY payment_status";
$paymentSummaryStmt = $conn->query($paymentSummaryQuery);
$paymentSummary = $paymentSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Product Summary with improved status handling
$productSummaryQuery = "SELECT COALESCE(status, 'pending') as status, COUNT(*) AS total 
                       FROM products 
                       GROUP BY status 
                       ORDER BY FIELD(status, 'active', 'pending', 'out_of_stock', 'discontinued')";
$productSummaryStmt = $conn->query($productSummaryQuery);
$productSummary = $productSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Orders with date filter
$recentOrdersQuery = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, 
                      p.pickup_id, p.pickup_date, p.pickup_location, p.pickup_notes, p.contact_person
                      FROM orders AS o
                      JOIN users AS u ON o.consumer_id = u.user_id
                      LEFT JOIN pickups AS p ON o.order_id = p.order_id";
if ($startDate && $endDate) {
    $recentOrdersQuery .= $dateFilterCondition;
    $recentOrdersQuery .= " ORDER BY o.order_date DESC";
} else {
    $recentOrdersQuery .= " ORDER BY o.order_date DESC LIMIT 10";
}
$recentOrdersStmt = $conn->query($recentOrdersQuery);
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total Orders with date filter
$totalOrdersQuery = "SELECT COUNT(*) as total FROM orders o";
$totalOrdersQuery .= $dateFilterCondition;
$totalOrdersStmt = $conn->query($totalOrdersQuery);
$totalOrders = $totalOrdersStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate Total Revenue with date filter
try {
    $totalRevenueQuery = "SELECT SUM(oi.price * oi.quantity) as total 
                         FROM orderitems oi
                         JOIN orders o ON oi.order_id = o.order_id
                         WHERE o.order_status = 'completed'";
    if ($startDate && $endDate) {
        $totalRevenueQuery .= " AND o.order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
    }
    $totalRevenueStmt = $conn->query($totalRevenueQuery);
    $totalRevenue = $totalRevenueStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    $totalRevenue = 0;
    error_log("Error in revenue calculation: " . $e->getMessage());
}

// Calculate Total Products (keeping as is)
$totalProductsQuery = "SELECT COUNT(*) as total FROM products";
$totalProductsStmt = $conn->query($totalProductsQuery);
$totalProducts = $totalProductsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate Average Order Value with date filter
try {
    $avgOrderValueQuery = "SELECT AVG(subquery.order_total) as avg_value FROM (
                          SELECT o.order_id, SUM(oi.price * oi.quantity) as order_total
                          FROM orders o
                          JOIN orderitems oi ON o.order_id = oi.order_id";
    if ($startDate && $endDate) {
        $avgOrderValueQuery .= " WHERE o.order_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
    }
    $avgOrderValueQuery .= " GROUP BY o.order_id) as subquery";
    
    $avgOrderValueStmt = $conn->query($avgOrderValueQuery);
    $avgOrderValue = $avgOrderValueStmt->fetch(PDO::FETCH_ASSOC)['avg_value'] ?? 0;
} catch (PDOException $e) {
    $avgOrderValue = 0;
    error_log("Error in average order calculation: " . $e->getMessage());
}

// Get date range for display
$reportTitle = "Reports Overview";
if ($startDate && $endDate) {
    $formattedStartDate = date('F j, Y', strtotime($startDate));
    $formattedEndDate = date('F j, Y', strtotime($endDate));
    $reportTitle = "Reports: $formattedStartDate to $formattedEndDate";
}

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/reports.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- PDF Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* Print Styles */
        @media print {
            /* Hide non-printable elements */
            .no-print, .admin-header, .admin-sidebar, .btn-toolbar, .btn-export, .btn-print, 
            .date-filter, nav, .breadcrumb, .section-header h3 i {
                display: none !important;
            }
            
            /* Make sure the main content takes full width when printing */
            main, .container-fluid, .row, .col-md-9, .col-lg-10 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Add page header and improve layout */
            body::before {
                content: "FARMERS MARKET MANAGEMENT SYSTEM";
                display: block;
                text-align: center;
                font-size: 24pt;
                font-weight: bold;
                color: #28a745;
                margin-bottom: 20px;
            }
            
            /* Adjust card and table styling for print */
            .report-card {
                break-inside: avoid;
                border: 1px solid #ddd !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid;
            }
            
            .card-header {
                background-color: #f0f0f0 !important;
                border-bottom: 1px solid #ddd !important;
                padding: 10px 15px !important;
            }
            
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            
            table th, table td {
                border: 1px solid #dee2e6 !important;
            }
            
            /* Add page numbers */
            @page {
                margin: 0.5in;
                counter-increment: page;
                @bottom-center {
                    content: "Page " counter(page);
                }
            }
            
            /* Improve chart visibility on print */
            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
            
            /* Summary stats styling */
            .summary-stats {
                padding: 15px 10px !important;
            }
            
            /* More spacing between sections */
            .row {
                margin-bottom: 20px !important;
            }
            
            /* Remove animations */
            .animate-card {
                animation: none !important;
            }
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
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <!-- Add breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reports</li>
                    </ol>
                </nav>

                <div class="report-header d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2 text-success"><?= $reportTitle ?></h1>
                    <div class="btn-toolbar">
                        <button class="btn btn-export mr-2" onclick="exportToPDF()">
                            <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                        </button>
                        <button class="btn btn-print mr-2" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                        <form method="POST" class="ml-2">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Date Filter Section (No-print) -->
                <div class="date-filter no-print">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap preset-date-ranges">
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('today')">Today</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('yesterday')">Yesterday</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('this-week')">This Week</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('last-week')">Last Week</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('this-month')">This Month</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('last-month')">Last Month</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('last-30-days')">Last 30 Days</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('last-90-days')">Last 90 Days</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('this-year')">This Year</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('last-year')">Last Year</button>
                                <button type="button" class="btn btn-outline-success mr-2 mb-2" onclick="setDateRange('all-time')">All Time</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start-date">Start Date</label>
                                <input type="date" class="form-control" id="start-date" value="<?= htmlspecialchars($startDate) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end-date">End Date</label>
                                <input type="date" class="form-control" id="end-date" value="<?= htmlspecialchars($endDate) ?>">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="filter-btn" onclick="applyDateFilter()">
                                <i class="bi bi-funnel"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="summary-stats animate-card">
                            <div class="number"><?= $totalOrders ?></div>
                            <div class="label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-stats animate-card" style="animation-delay: 0.1s;">
                            <div class="number">₱<?= number_format($totalRevenue, 2) ?></div>
                            <div class="label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-stats animate-card" style="animation-delay: 0.2s;">
                            <div class="number"><?= $totalProducts ?></div>
                            <div class="label">Total Products</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-stats animate-card" style="animation-delay: 0.3s;">
                            <div class="number">₱<?= number_format($avgOrderValue, 2) ?></div>
                            <div class="label">Average Order Value</div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Summary -->
                <div class="row mb-5">
                    <div class="col-md-4 mt-5">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-credit-card me-2"></i> Payment Summary
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="paymentChart"></canvas>
                                </div>
                                <div class="list-group">
                                    <?php foreach ($paymentSummary as $payment): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-circle-fill me-2 text-<?= $payment['payment_status'] === 'paid' ? 'success' : ($payment['payment_status'] === 'pending' ? 'warning' : 'danger') ?>"></i> <?= ucfirst($payment['payment_status']) ?></span>
                                            <span class="status-badge status-<?= $payment['payment_status'] ?>"><?= $payment['total'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Product Summary -->
                    <div class="col-md-4 mt-5">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-box me-2"></i> Product Summary
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="productChart"></canvas>
                                </div>
                                <div class="list-group">
                                    <?php 
                                    $statusColors = [
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'out_of_stock' => 'danger',
                                        'discontinued' => 'secondary'
                                    ];
                                    ?>
                                    <?php foreach ($productSummary as $product): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-circle-fill me-2 text-<?= $statusColors[$product['status']] ?? 'secondary' ?>"></i> <?= ucfirst($product['status']) ?></span>
                                            <span class="status-badge status-<?= $product['status'] ?>"><?= $product['total'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="col-md-4 mt-5">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-cart me-2"></i> Order Summary
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="orderChart"></canvas>
                                </div>
                                <div class="list-group">
                                    <?php foreach ($orderSummary as $order): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-circle-fill me-2 text-<?= $order['order_status'] === 'completed' ? 'success' : ($order['order_status'] === 'pending' ? 'warning' : 'danger') ?>"></i> <?= ucfirst($order['order_status']) ?></span>
                                            <span class="status-badge status-<?= $order['order_status'] ?>"><?= $order['total'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geographical Analytics Section Header -->
                <div class="row mb-4 mt-4">
                    <div class="col-12">
                        <div class="section-header">
                            <h3 class="text-success"><i class="bi bi-geo-alt"></i> Geographical Analytics</h3>
                            <hr class="separator">
                        </div>
                    </div>
                </div>

                <!-- Geographical Analytics -->
                <div class="row mb-4">
                    <!-- Crops Per Barangay -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card h-100">
                            <div class="card-header">
                                <i class="bi bi-geo-alt me-2"></i> Crops Per Barangay
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="cropsTable" class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Barangay</th>
                                                <th>Crop</th>
                                                <th>Production</th>
                                                <th>Planted Area</th>
                                                <th>Category</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cropsPerBarangay as $crop): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($crop['barangay_name']) ?></td>
                                                    <td><?= htmlspecialchars($crop['product_name']) ?></td>
                                                    <td><?= number_format($crop['total_production']) . ' ' . htmlspecialchars($crop['production_unit']) ?></td>
                                                    <td><?= number_format($crop['total_planted_area']) . ' ' . htmlspecialchars($crop['area_unit']) ?></td>
                                                    <td><?= htmlspecialchars($crop['category_name'] ?? 'Uncategorized') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Farmers Per Barangay -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card h-100">
                            <div class="card-header">
                                <i class="bi bi-people me-2"></i> Farmers Per Barangay
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="farmersTable" class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Barangay</th>
                                                <th>Number of Farmers</th>
                                                <th>Total Farm Area</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($farmersPerBarangay as $farmer): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($farmer['barangay_name']) ?></td>
                                                    <td><?= $farmer['farmer_count'] ?></td>
                                                    <td><?= number_format($farmer['total_farm_area'], 2) ?> hectares</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Production Insights -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card h-100">
                            <div class="card-header">
                                <i class="bi bi-graph-up me-2"></i> Production Insights
                            </div>
                            <div class="card-body">
                                <div id="production-insights">
                                    <!-- Content will be populated by JavaScript -->
                                    <div class="loading">
                                        <div class="spinner-border text-success" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Crop Production Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-bar-chart me-2"></i> Crop Production Chart
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="cropProductionChart"></canvas>
                                </div>
                                <div class="mt-3 text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-success active" data-chart-view="production">By Production</button>
                                        <button type="button" class="btn btn-outline-primary" data-chart-view="area">By Area</button>
                                        <button type="button" class="btn btn-outline-info" data-chart-view="yield">By Yield Rate</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seasonal Crops -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-calendar4 me-2"></i> Seasonal Crops
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="seasonalTable" class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Season</th>
                                                <th>Crop</th>
                                                <th>Production</th>
                                                <th>Area</th>
                                                <th>Period</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($seasonalCrops as $crop): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($crop['season_name']) ?></td>
                                                    <td><?= htmlspecialchars($crop['product_name']) ?></td>
                                                    <td><?= number_format($crop['total_production']) . ' ' . htmlspecialchars($crop['production_unit']) ?></td>
                                                    <td><?= number_format($crop['total_planted_area']) . ' ' . htmlspecialchars($crop['area_unit']) ?></td>
                                                    <td><?= htmlspecialchars($crop['start_month']) . ' - ' . htmlspecialchars($crop['end_month']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="section-header">
                            <h3 class="text-success"><i class="bi bi-cart-check"></i> Order Management</h3>
                            <hr class="separator">
                        </div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center mb-4">
                                    <i class="bi bi-clock-history me-2"></i>Recent Orders
                                </h5>
                                <div class="table-responsive">
                                    <table id="ordersDataTable" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Consumer</th>
                                                <th>Status</th>
                                                <th>Order Date</th>
                                                <th>Pickup Details</th>
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
                                                            <span class="badge badge-<?= $order['order_status'] === 'completed' ? 'success' : ($order['order_status'] === 'canceled' ? 'danger' : 'warning') ?>">
                                                                <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?></td>
                                                        <td><?= htmlspecialchars($order['pickup_location'] ?? 'N/A') ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-info btn-sm view-pickup-details-btn"
                                                                    data-pickup-id="<?= htmlspecialchars($order['pickup_id'] ?? 'N/A') ?>"
                                                                    data-pickup-date="<?= htmlspecialchars($order['pickup_date'] ?? 'N/A') ?>"
                                                                    data-pickup-location="<?= htmlspecialchars($order['pickup_location'] ?? 'N/A') ?>"
                                                                    data-contact-person="<?= htmlspecialchars($order['contact_person'] ?? 'N/A') ?>"
                                                                    data-pickup-notes="<?= htmlspecialchars($order['pickup_notes'] ?? 'N/A') ?>"
                                                                    data-toggle="modal" data-target="#pickupDetailsModal">
                                                                View Pickup Details
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
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Pickup Details Modal -->
    <div class="modal fade" id="pickupDetailsModal" tabindex="-1" role="dialog" aria-labelledby="pickupDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pickupDetailsModalLabel">Pickup Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Pickup ID:</strong> <span id="pickup-id"></span></p>
                    <p><strong>Pickup Date:</strong> <span id="pickup-date"></span></p>
                    <p><strong>Pickup Location:</strong> <span id="pickup-location"></span></p>
                    <p><strong>Contact Person:</strong> <span id="assigned-to"></span></p>
                    <p><strong>Pickup Notes:</strong> <span id="pickup-notes"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Season Details Modal -->
    <div class="modal fade" id="seasonDetailsModal" tabindex="-1" role="dialog" aria-labelledby="seasonDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="seasonDetailsModalLabel"><i class="bi bi-calendar3"></i> Season Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong><i class="bi bi-info-circle"></i> Season Information</strong>
                                </div>
                                <div class="card-body">
                                    <h4 class="text-info" id="season-name"></h4>
                                    <p><strong>Period:</strong> <span id="season-period"></span></p>
                                    <p><strong>Description:</strong> <span id="season-description"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><i class="bi bi-plant"></i> Planting Recommendations</strong>
                                </div>
                                <div class="card-body">
                                    <p id="season-recommendations"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><i class="bi bi-bar-chart"></i> Top Crops for this Season</strong>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover" id="season-crops-table">
                                            <thead>
                                                <tr>
                                                    <th>Crop</th>
                                                    <th>Barangay</th>
                                                    <th>Production</th>
                                                    <th>Area</th>
                                                </tr>
                                            </thead>
                                            <tbody id="season-crops-data">
                                                <!-- Data will be loaded dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" id="print-season-details">
                        <i class="bi bi-printer"></i> Print Season Information
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.colVis.min.js"></script>

    <!-- Chart.js Script -->
    <script>
        // Order Summary Chart
        const orderCtx = document.getElementById('orderChart').getContext('2d');
        const orderChart = new Chart(orderCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($orderSummary, 'order_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($orderSummary, 'total')) ?>,
                    backgroundColor: [
                        '#28a745', // completed
                        '#ffc107', // pending  
                        '#dc3545', // canceled
                        '#17a2b8'  // other statuses
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Payment Summary Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($paymentSummary, 'payment_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($paymentSummary, 'total')) ?>,
                    backgroundColor: [
                        '#28a745', // paid
                        '#ffc107', // pending
                        '#dc3545', // failed
                        '#17a2b8'  // other statuses
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Product Summary Chart
        const productCtx = document.getElementById('productChart').getContext('2d');
        const productChart = new Chart(productCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($productSummary, 'status')) ?>,
                datasets: [{
                    label: 'Products',
                    data: <?= json_encode(array_column($productSummary, 'total')) ?>,
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // DataTables Initialization
        $(document).ready(function() {
            // Main orders table initialization
            $('#ordersDataTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-file-earmark-arrow-down"></i> Export',
                        buttons: [
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Skip actions column
                                },
                                title: 'Farmers Market Orders Report'
                            },
                            {
                                extend: 'csv',
                                text: '<i class="bi bi-file-earmark-text"></i> CSV',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Skip actions column
                                },
                                title: 'Farmers Market Orders Report'
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Skip actions column
                                },
                                orientation: 'landscape',
                                title: 'Farmers Market Orders Report'
                            },
                            {
                                extend: 'print',
                                text: '<i class="bi bi-printer"></i> Print',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Skip actions column
                                },
                                title: 'Farmers Market Orders Report'
                            }
                        ]
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="bi bi-eye"></i> Columns'
                    }
                ],
                language: {
                    search: "<i class='bi bi-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ orders",
                    infoEmpty: "Showing 0 to 0 of 0 orders",
                    infoFiltered: "(filtered from _MAX_ total orders)"
                },
                columnDefs: [
                    { 
                        targets: 2, // Status column
                        render: function(data, type, row) {
                            // Return data for sorting and type operations
                            if(type === 'sort' || type === 'type') {
                                return $(data).text();
                            }
                            // Return HTML for display
                            return data;
                        }
                    },
                    {
                        targets: 5, // Actions column
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [[3, 'desc']] // Sort by order date by default (newest first)
            });
            
            // Handle status filtering
            $('#ordersDataTable_wrapper').prepend(
                '<div class="mb-3 d-flex justify-content-start align-items-center status-filter-container">' +
                '<label class="mr-2"><i class="bi bi-filter"></i> Filter by Status:</label>' +
                '<div class="btn-group btn-group-sm" role="group">' +
                '<button type="button" class="btn btn-outline-secondary active" data-status="all">All</button>' +
                '<button type="button" class="btn btn-outline-warning" data-status="pending">Pending</button>' +
                '<button type="button" class="btn btn-outline-success" data-status="completed">Completed</button>' +
                '<button type="button" class="btn btn-outline-danger" data-status="canceled">Canceled</button>' +
                '</div>' +
                '</div>'
            );
            
            // Add custom status filtering
            $('.status-filter-container .btn').on('click', function() {
                let status = $(this).data('status');
                let table = $('#ordersDataTable').DataTable();
                
                // Update active button
                $('.status-filter-container .btn').removeClass('active');
                $(this).addClass('active');
                
                // Apply filter
                if (status === 'all') {
                    table.column(2).search('').draw();
                } else {
                    table.column(2).search(status, true, false).draw();
                }
            });
              // Initialize geographical analytics tables with pagination and collapsible sections
            $('#cropsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                dom: '<"top"<"float-left"l><"float-right"f>><"clear">rt<"bottom"<"float-left"i><"float-right"p>>',
                language: {
                    search: "<i class='bi bi-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ crops",
                    infoEmpty: "No crops available",
                    infoFiltered: "(filtered from _MAX_ total crops)",
                    zeroRecords: "No matching crops found",
                },
                order: [[1, 'desc']],
                drawCallback: function() {
                    updateProductionInsights();
                }
            });
            
            // Initialize farmers per barangay table
            $('#farmersTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                dom: '<"top"<"float-left"l><"float-right"f>><"clear">rt<"bottom"<"float-left"i><"float-right"p>>',
                language: {
                    search: "<i class='bi bi-search'></i>",
                    lengthMenu: "Show _MENU_",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                order: [[1, 'desc']] // Sort by farmer count by default
            });
            
            // Initialize seasonal crops table with clickable rows for more information
            $('#seasonalTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                dom: '<"top"<"float-left"l><"float-right"f>><"clear">rt<"bottom"<"float-left"i><"float-right"p>>',
                language: {
                    search: "<i class='bi bi-search'></i>",
                    lengthMenu: "Show _MENU_",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                drawCallback: function() {
                    // Group by Season with collapsible sections
                    let api = this.api();
                    let rows = api.rows({ page: 'current' }).nodes();
                    let last = null;
                    
                    api.column(0, { page: 'current' }).data().each(function(group, i) {
                        if (last !== group) {
                            $(rows).eq(i).before(
                                '<tr class="group"><td colspan="5"><button class="btn btn-sm btn-outline-info toggle-group" data-group="' + 
                                group + '"><i class="bi bi-caret-down-fill"></i> ' + group + '</button></td></tr>'
                            );
                            last = group;
                        }
                    });
                    
                    // Make season name cells clickable to show detailed information
                    $('#seasonalTable tbody tr:not(.group)').css('cursor', 'pointer').on('click', function() {
                        const seasonName = $(this).find('td:first').text().trim();
                        const seasonPeriod = $(this).find('td:last').text().trim();
                        showSeasonDetails(seasonName, seasonPeriod);
                    });
                },
                // Add export buttons
                buttons: [
                    {
                        extend: 'excel',
                        text: 'Excel',
                        title: 'Seasonal Crop Production',
                        className: 'btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        title: 'Seasonal Crop Production',
                        className: 'btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: 'PDF',
                        title: 'Seasonal Crop Production',
                        className: 'btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    }
                ]
            });
            
            // Function to show detailed season information in modal
            function showSeasonDetails(seasonName, seasonPeriod) {
                // Set basic information in modal
                $('#season-name').text(seasonName);
                $('#season-period').text(seasonPeriod);
                
                // Load season description and recommendations via AJAX
                $.ajax({
                    url: '../../controllers/GeoAnalyticsController.php',
                    type: 'GET',
                    data: {
                        action: 'getSeasonDetails',
                        seasonName: seasonName
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#season-description').text(response.description || 'No description available');
                            $('#season-recommendations').text(response.recommendations || 'No recommendations available');
                            
                            // Load top crops for this season
                            let cropsHtml = '';
                            if (response.topCrops && response.topCrops.length > 0) {
                                response.topCrops.forEach(function(crop) {
                                    cropsHtml += `<tr>
                                        <td>${crop.product_name}</td>
                                        <td>${crop.barangay_name}</td>
                                        <td>${crop.total_production} ${crop.production_unit}</td>
                                        <td>${crop.total_planted_area} ${crop.area_unit}</td>
                                    </tr>`;
                                });
                            } else {
                                cropsHtml = '<tr><td colspan="4" class="text-center">No crop data available for this season</td></tr>';
                            }
                            $('#season-crops-data').html(cropsHtml);
                            
                            // Show the modal
                            $('#seasonDetailsModal').modal('show');
                        } else {
                            alert('Error loading season details: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error connecting to server. Please try again later.');
                    }
                });
            }
            
            // Handle print season details button
            $('#print-season-details').click(function() {
                const seasonName = $('#season-name').text();
                const seasonContent = document.querySelector('.modal-body').innerHTML;
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Season Details: ${seasonName}</title>
                        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            h1 { color: #17a2b8; margin-bottom: 20px; }
                            .section { margin-bottom: 30px; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { padding: 8px; border: 1px solid #ddd; }
                            th { background-color: #f8f9fa; }
                            @media print {
                                body { padding: 0; }
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h1>Season Details: ${seasonName}</h1>
                            <div class="no-print mb-3">
                                <button onclick="window.print()" class="btn btn-info">Print</button>
                                <button onclick="window.close()" class="btn btn-secondary ml-2">Close</button>
                            </div>
                            ${seasonContent}
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                setTimeout(function() {
                    printWindow.focus();
                }, 500);
            });
            
            // Handle collapsible group toggling
            $(document).on('click', '.toggle-group', function() {
                const group = $(this).data('group');
                const icon = $(this).find('i');
                const table = $(this).closest('table').DataTable();
                
                // Toggle icon
                if (icon.hasClass('bi-caret-down-fill')) {
                    icon.removeClass('bi-caret-down-fill').addClass('bi-caret-right-fill');
                } else {
                    icon.removeClass('bi-caret-right-fill').addClass('bi-caret-down-fill');
                }
                
                // Toggle visibility of group rows
                const tableId = $(this).closest('table').attr('id');
                if (tableId === 'cropsTable') {
                    $('tr.child-row-' + group.replace(/\s+/g, '-')).toggle();
                    
                    // Toggle visibility in the DataTable
                    table.rows().every(function() {
                        const rowData = this.data();
                        if (rowData[0] === group) {
                            const tr = $(this.node());
                            tr.toggle();
                            if (tr.is(':hidden') && tr.hasClass('parent')) {
                                this.child.hide();
                            }
                        }
                    });
                } else if (tableId === 'seasonalTable') {
                    $('tr.child-row-' + group.replace(/\s+/g, '-')).toggle();
                    
                    // Toggle visibility in the DataTable
                    table.rows().every(function() {
                        const rowData = this.data();
                        if (rowData[0] === group) {
                            const tr = $(this.node());
                            tr.toggle();
                            if (tr.is(':hidden') && tr.hasClass('parent')) {
                                this.child.hide();
                            }
                        }
                    });
                }
            });
        });

        // View Pickup Details
        $(document).ready(function () {
            $('.view-pickup-details-btn').click(function () {
                var pickupId = $(this).data('pickup-id');
                var pickupDate = $(this).data('pickup-date');
                var pickupLocation = $(this).data('pickup-location');
                var contactPerson = $(this).data('contact-person');
                var pickupNotes = $(this).data('pickup-notes');

                $('#pickup-id').text(pickupId);
                $('#pickup-date').text(pickupDate);
                $('#pickup-location').text(pickupLocation);
                $('#assigned-to').text(contactPerson);
                $('#pickup-notes').text(pickupNotes);
            });
        });

        // PDF Export function with date range
        function exportToPDF() {
            // Show loading indicator
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'export-loading';
            loadingDiv.innerHTML = '<div class="spinner-border text-success" role="status"><span class="sr-only">Loading...</span></div><p>Generating PDF...</p>';
            document.body.appendChild(loadingDiv);
            
            // Access the jsPDF library
            const { jsPDF } = window.jspdf;
            
            // Create a new PDF document
            const doc = new jsPDF('p', 'mm', 'a4');
            
            // Get date range information
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const dateRangeText = startDate && endDate 
                ? `Report Period: ${formatDateForDisplay(startDate)} to ${formatDateForDisplay(endDate)}`
                : 'Report Period: All Time';
            
            // Add logo placeholder (would be better with an actual logo)
            doc.setFillColor(40, 167, 69); // Green color
            doc.rect(14, 10, 15, 15, 'F');
            doc.setTextColor(255);
            doc.setFontSize(12);
            doc.text('FM', 21.5, 19.5, { align: 'center' });
            
            // Add title with professional formatting
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(40, 167, 69); // Green color
            doc.text('FARMERS MARKET MANAGEMENT SYSTEM', 105, 16, { align: 'center' });
            
            // Add subtitle
            doc.setFontSize(16);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(40, 167, 69);
            doc.text('Agricultural Production Report', 105, 24, { align: 'center' });
            
            // Add divider line
            doc.setDrawColor(40, 167, 69);
            doc.setLineWidth(0.5);
            doc.line(14, 28, 196, 28);
            
            // Add date range and generation date with better formatting
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.setFont(undefined, 'italic');
            const today = new Date();
            const dateStr = today.toLocaleDateString('en-PH', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            doc.text(`Generated: ${dateStr}`, 14, 35);
            doc.setFont(undefined, 'bold');
            doc.text(dateRangeText, 14, 40);
            
            // Add page numbers to all pages
            const totalPages = 3; // Estimate pages
            const addPageNumber = function(page) {
                doc.setFont(undefined, 'italic');
                doc.setFontSize(8);
                doc.setTextColor(100);
                doc.text(`Page ${page} of ${totalPages}`, 196, 285, { align: 'right' });
            };
            
            // Define the sections to capture with improved layout
            const sectionsToCapture = [
                { selector: '.row:nth-child(3)', title: 'Summary Statistics', y: 50, addHeader: true },
                { selector: '.row.mb-5:first-of-type', title: 'Sales & Inventory Analytics', y: 110, addHeader: true, pageBreak: true },
                { selector: '.row.mb-4:nth-of-type(3)', title: 'Geographical Analytics', y: 50, addHeader: true },
                { selector: '.row.mb-5:nth-of-type(2)', title: 'Crop Production Data', y: 110, addHeader: true, pageBreak: true },
                { selector: '.row.mb-4:nth-of-type(5)', title: 'Order Management', y: 50, addHeader: true },
                { selector: '.card.shadow-sm', title: 'Recent Orders', y: 85, addHeader: true }
            ];
            
            // Function to add section header
            const addSectionHeader = function(title, y) {
                // Add section header background
                doc.setFillColor(240, 250, 240);
                doc.rect(14, y - 8, 182, 10, 'F');
                
                // Add section icon placeholder
                doc.setFillColor(40, 167, 69);
                doc.circle(20, y - 3, 3, 'F');
                
                // Add section title
                doc.setFont(undefined, 'bold');
                doc.setFontSize(12);
                doc.setTextColor(40, 100, 40);
                doc.text(title, 26, y - 3);
            };
            
            // Track current page
            let currentPage = 1;
            
            // Function to process each section
            const captureSection = (index) => {
                if (index >= sectionsToCapture.length) {
                    // All sections processed, add final page number and save the PDF
                    addPageNumber(currentPage);
                    
                    let filename = `FarmersMarketReport_`;
                    
                    // Include date range in filename if available
                    if (startDate && endDate) {
                        const startPart = startDate.replace(/-/g, '');
                        const endPart = endDate.replace(/-/g, '');
                        filename += `${startPart}_to_${endPart}`;
                    } else {
                        filename += `${today.getFullYear()}${(today.getMonth()+1).toString().padStart(2,'0')}${today.getDate().toString().padStart(2,'0')}`;
                    }
                    
                    filename += '.pdf';
                    doc.save(filename);
                    
                    // Remove loading indicator
                    document.body.removeChild(loadingDiv);
                    return;
                }
                
                const section = sectionsToCapture[index];
                const element = document.querySelector(section.selector);
                
                // Check for page break
                if (section.pageBreak) {
                    addPageNumber(currentPage);
                    doc.addPage();
                    currentPage++;
                    
                    // Add header to new page
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(100);
                    doc.text('FARMERS MARKET MANAGEMENT SYSTEM', 105, 10, { align: 'center' });
                    doc.setDrawColor(200, 200, 200);
                    doc.setLineWidth(0.1);
                    doc.line(14, 12, 196, 12);
                }
                
                if (element) {
                    // Add section header if needed
                    if (section.addHeader) {
                        addSectionHeader(section.title, section.y);
                    }
                    
                    // Capture the section
                    html2canvas(element, {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        allowTaint: true,
                        backgroundColor: '#ffffff'
                    }).then(canvas => {
                        // Add the canvas as an image to the PDF
                        const imgData = canvas.toDataURL('image/png');
                        const imgWidth = 180;
                        const imgHeight = canvas.height * imgWidth / canvas.width;
                        
                        doc.addImage(imgData, 'PNG', 15, section.y, imgWidth, imgHeight);
                        
                        // Process next section
                        captureSection(index + 1);
                    });
                } else {
                    // Skip this section if not found
                    captureSection(index + 1);
                }
            };
            
            // Start capturing sections
            captureSection(0);
        }
        
        // Helper function to format dates for display in PDFs
        function formatDateForDisplay(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }        // Enhanced date filter function with validation
        function applyDateFilter() {
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            // Clear previous error styling
            startDateInput.classList.remove('is-invalid');
            endDateInput.classList.remove('is-invalid');
            document.getElementById('date-error').innerHTML = '';
            
            // Validate date inputs
            if (!startDate || !endDate) {
                document.getElementById('date-error').innerHTML = 
                    '<div class="alert alert-danger">Please select both start and end dates</div>';
                if (!startDate) startDateInput.classList.add('is-invalid');
                if (!endDate) endDateInput.classList.add('is-invalid');
                return;
            }
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            const today = new Date();
            
            // Validate date range
            if (start > end) {
                document.getElementById('date-error').innerHTML = 
                    '<div class="alert alert-danger">Start date cannot be after end date</div>';
                startDateInput.classList.add('is-invalid');
                endDateInput.classList.add('is-invalid');
                return;
            }
            
            // Validate against future dates
            if (start > today || end > today) {
                document.getElementById('date-error').innerHTML = 
                    '<div class="alert alert-danger">Cannot select future dates</div>';
                if (start > today) startDateInput.classList.add('is-invalid');
                if (end > today) endDateInput.classList.add('is-invalid');
                return;
            }
            
            // Validate maximum date range (e.g., 1 year)
            const oneYear = 365 * 24 * 60 * 60 * 1000; // milliseconds in a year
            if (end - start > oneYear) {
                document.getElementById('date-error').innerHTML = 
                    '<div class="alert alert-warning">Date range cannot exceed 1 year. Adjusting to last 365 days.</div>';
                startDateInput.value = new Date(end.getTime() - oneYear).toISOString().split('T')[0];
            }
            
            // Show loading indicator
            document.getElementById('date-error').innerHTML = 
                '<div class="alert alert-info">Loading data...</div>';
            
            // Redirect to the same page with date parameters
            window.location.href = `?start_date=${startDateInput.value}&end_date=${endDateInput.value}`;
        }
        
        // Preset date range function
        function setDateRange(range) {
            const today = new Date();
            let startDate = new Date();
            let endDate = new Date();
            
            // Calculate start and end dates based on selected preset
            switch(range) {
                case 'today':
                    // Start and end are both today
                    break;
                    
                case 'yesterday':
                    startDate.setDate(today.getDate() - 1);
                    endDate.setDate(today.getDate() - 1);
                    break;
                    
                case 'this-week':
                    // Start of week (Sunday)
                    const dayOfWeek = today.getDay(); // 0 = Sunday, 6 = Saturday
                    startDate.setDate(today.getDate() - dayOfWeek);
                    break;
                    
                case 'last-week':
                    // Last week (Sunday to Saturday)
                    const lastWeekDay = today.getDay();
                    startDate.setDate(today.getDate() - lastWeekDay - 7);
                    endDate.setDate(today.getDate() - lastWeekDay - 1);
                    break;
                    
                case 'this-month':
                    // Start of current month
                    startDate.setDate(1);
                    break;
                    
                case 'last-month':
                    // Last month
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                    
                case 'last-30-days':
                    // Last 30 days
                    startDate.setDate(today.getDate() - 30);
                    break;
                    
                case 'last-90-days':
                    // Last 90 days
                    startDate.setDate(today.getDate() - 90);
                    break;
                    
                case 'this-year':
                    // Start of current year
                    startDate = new Date(today.getFullYear(), 0, 1);
                    break;
                    
                case 'last-year':
                    // Last year
                    startDate = new Date(today.getFullYear() - 1, 0, 1);
                    endDate = new Date(today.getFullYear() - 1, 11, 31);
                    break;
                    
                case 'all-time':
                    // Set to a far past date for "all time"
                    startDate = new Date(2000, 0, 1);
                    break;
            }
            
            // Format dates for input fields (YYYY-MM-DD)
            document.getElementById('start-date').value = formatDateForInput(startDate);
            document.getElementById('end-date').value = formatDateForInput(endDate);
            
            // Auto-apply the filter
            applyDateFilter();
        }
        
        // Helper function to format dates for input fields
        function formatDateForInput(date) {
            return date.toISOString().split('T')[0];
        }

        // Initialize Crop Production Chart
        const cropProductionCtx = document.getElementById('cropProductionChart').getContext('2d');
        let cropProductionData = <?= json_encode($cropsPerBarangay) ?>;
        let cropProductionChart;
        
        function initCropProductionChart(viewType = 'production') {
            // Process data for the chart
            const barangayData = {};
            
            // Group data by barangay
            cropProductionData.forEach(item => {
                if (!barangayData[item.barangay_name]) {
                    barangayData[item.barangay_name] = {
                        production: 0,
                        area: 0,
                        yieldRate: 0,
                        crops: []
                    };
                }
                
                // Add production values - ensure we're using numbers, not strings
                const production = parseFloat(item.total_production) || 0;
                const area = parseFloat(item.total_planted_area) || 0;
                
                barangayData[item.barangay_name].production += production;
                barangayData[item.barangay_name].area += area;
                
                // Store crop info for tooltip
                barangayData[item.barangay_name].crops.push({
                    name: item.product_name,
                    production: production,
                    area: area,
                    unit: item.production_unit,
                    areaUnit: item.area_unit
                });
            });
            
            // Calculate yield rates
            Object.keys(barangayData).forEach(barangay => {
                if (barangayData[barangay].area > 0) {
                    barangayData[barangay].yieldRate = 
                        barangayData[barangay].production / barangayData[barangay].area;
                }
            });
            
            // Sort barangays by the selected view type
            const sortedBarangays = Object.keys(barangayData).sort((a, b) => {
                if (viewType === 'yield') {
                    return barangayData[b].yieldRate - barangayData[a].yieldRate;
                } else {
                    return barangayData[b][viewType] - barangayData[a][viewType];
                }
            });
            
            // Prepare chart data based on view type
            const chartData = {
                labels: sortedBarangays,
                datasets: [{
                    label: viewType === 'production' ? 'Production Volume' : 
                           viewType === 'area' ? 'Planted Area' : 'Yield Rate',
                    data: sortedBarangays.map(b => viewType === 'yield' ? 
                           barangayData[b].yieldRate : barangayData[b][viewType]),
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
            
            // Get unit for display
            const unitType = viewType === 'yield' ? 'Yield Rate (production/area)' :
                             viewType === 'area' ? 'Planted Area (hectares)' : 
                             'Production Volume';
            
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
                                text: unitType
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Barangays'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                afterTitle: function(tooltipItems) {
                                    const barangay = tooltipItems[0].label;
                                    return 'Top Crops:';
                                },
                                afterBody: function(tooltipItems) {
                                    const barangay = tooltipItems[0].label;
                                    // Get top 3 crops by production for this barangay
                                    const topCrops = barangayData[barangay].crops
                                        .sort((a, b) => b.production - a.production)
                                        .slice(0, 3);
                                    
                                    if (viewType === 'yield') {
                                        return [`Yield Rate: ${barangayData[barangay].yieldRate.toFixed(2)}`,
                                               `Production: ${barangayData[barangay].production.toLocaleString()}`,
                                               `Area: ${barangayData[barangay].area.toLocaleString()}`]
                                               .concat(topCrops.map(crop => `${crop.name}: ${crop.production} ${crop.unit}`));
                                    }
                                    
                                    return topCrops.map(crop => 
                                        `${crop.name}: ${crop.production} ${crop.unit}`);
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
        $(document).ready(function() {
            initCropProductionChart('production');
            
            // Handle view toggle buttons
            $('[data-chart-view]').click(function() {
                const viewType = $(this).data('chart-view');
                
                // Update active button
                $('[data-chart-view]').removeClass('active');
                $(this).addClass('active');
                
                // Update chart
                initCropProductionChart(viewType);
            });
        });

        // Make sure this executes after all other script loads
        $(document).ready(function() {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error("Chart.js is not loaded!");
                return;
            }
            
            // Define cropProductionChart globally if it doesn't exist
            if (typeof cropProductionChart === 'undefined') {
                window.cropProductionChart = null;
            }
            
            // Initialize crop production data from PHP
            const cropProductionData = <?= json_encode($cropsPerBarangay) ?>;
            
            // Custom function to properly initialize the crop chart
            function initializeCropProductionChart(viewType = 'production') {
                console.log("Initializing crop production chart with view type:", viewType);
                
                // Get the canvas context
                const cropProductionCtx = document.getElementById('cropProductionChart');
                
                if (!cropProductionCtx) {
                    console.error("Cannot find crop production chart canvas element");
                    return;
                }
                
                // Process data for the chart
                const barangayData = {};
                
                // Group data by barangay
                cropProductionData.forEach(item => {
                    if (!barangayData[item.barangay_name]) {
                        barangayData[item.barangay_name] = {
                            production: 0,
                            area: 0,
                            yieldRate: 0,
                            crops: [],
                            units: new Set(),
                            areaUnits: new Set()
                        };
                    }
                    
                    // Add production values
                    const production = parseFloat(item.total_production || 0);
                    const area = parseFloat(item.total_planted_area || 0);
                    
                    barangayData[item.barangay_name].production += production;
                    barangayData[item.barangay_name].area += area;
                    barangayData[item.barangay_name].units.add(item.production_unit);
                    barangayData[item.barangay_name].areaUnits.add(item.area_unit);
                    
                    // Store crop info for tooltip
                    barangayData[item.barangay_name].crops.push({
                        name: item.product_name,
                        production: production,
                        area: area,
                        unit: item.production_unit,
                        areaUnit: item.area_unit
                    });
                });
                
                // Calculate yield rates
                Object.keys(barangayData).forEach(barangay => {
                    if (barangayData[barangay].area > 0) {
                        barangayData[barangay].yieldRate = 
                            barangayData[barangay].production / barangayData[barangay].area;
                    }
                });
                
                // Sort barangays by the selected view type
                const sortedBarangays = Object.keys(barangayData).sort((a, b) => {
                    return barangayData[b][viewType === 'yield' ? 'yieldRate' : viewType] - 
                           barangayData[a][viewType === 'yield' ? 'yieldRate' : viewType];
                });
                
                // Get unit labels based on the first item for each barangay
                const unitLabels = {
                    production: Array.from(new Set(cropProductionData.map(item => item.production_unit)))[0] || '',
                    area: Array.from(new Set(cropProductionData.map(item => item.area_unit)))[0] || ''
                };
                
                // Prepare chart data based on view type
                const chartData = {
                    labels: sortedBarangays,
                    datasets: [{
                        label: viewType === 'production' ? 'Production Volume' : 
                               viewType === 'area' ? 'Planted Area' : 'Yield Rate',
                        data: sortedBarangays.map(b => viewType === 'yield' ? 
                            barangayData[b].yieldRate : barangayData[b][viewType]),
                        backgroundColor: viewType === 'production' ? '#28a745' : 
                                         viewType === 'area' ? '#007bff' : '#17a2b8',
                        borderColor: viewType === 'production' ? '#1e7e34' : 
                                    viewType === 'area' ? '#0056b3' : '#117a8b',
                        borderWidth: 1
                    }]
                };
                
                // Destroy previous chart instance if it exists
                if (window.cropProductionChart instanceof Chart) {
                    window.cropProductionChart.destroy();
                }
                
                // Create new chart
                window.cropProductionChart = new Chart(cropProductionCtx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        onClick: function(event, elements) {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const barangay = this.data.labels[index];
                                highlightBarangay(barangay);
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: viewType === 'production' ? `Production Volume (${unitLabels.production})` : 
                                          viewType === 'area' ? `Planted Area (${unitLabels.area})` : 
                                          `Yield Rate (${unitLabels.production}/${unitLabels.area})`
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Barangays'
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    afterTitle: function(tooltipItems) {
                                        return 'Top Crops:';
                                    },
                                    afterBody: function(tooltipItems) {
                                        const barangay = tooltipItems[0].label;
                                        // Get top 3 crops by production for this barangay
                                        if (barangayData[barangay]) {
                                            const topCrops = barangayData[barangay].crops
                                                .sort((a, b) => b.production - a.production)
                                                .slice(0, 3);
                                            
                                            return topCrops.map(crop => 
                                                `${crop.name}: ${crop.production} ${crop.unit}`);
                                        }
                                        return ['No crop data available'];
                                    }
                                }
                            },
                            legend: {
                                display: false
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
                
                // Update the production insights based on current data
                updateProductionInsights();
                
                console.log("Crop production chart initialized successfully");
            }
            
            function highlightBarangay(barangayName) {
                // Find the barangay in the table and highlight it
                const cropsTable = document.getElementById('cropsTable');
                if (cropsTable) {
                    // If DataTable is initialized
                    if ($.fn.DataTable.isDataTable('#cropsTable')) {
                        const dataTable = $('#cropsTable').DataTable();
                        
                        // Clear any existing search
                        dataTable.search('').draw();
                        
                        // Apply search for this barangay
                        dataTable.search(barangayName).draw();
                    }
                    
                    // Scroll to the table
                    cropsTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    
                    // Visual feedback - add highlight class to matching rows
                    $(`#cropsTable tbody tr:contains('${barangayName}')`).addClass('highlight-row');
                    setTimeout(() => {
                        $(`#cropsTable tbody tr`).removeClass('highlight-row');
                    }, 3000);
                }
            }
            
            // Function to update production insights based on crop data
            function updateProductionInsights() {
                try {
                    // Get crop data from the table
                    let cropData = [];
                    if ($.fn.DataTable.isDataTable('#cropsTable')) {
                        const table = $('#cropsTable').DataTable();
                        table.rows().every(function() {
                            const rowData = this.data();
                            if (rowData && rowData.length >= 5) {
                                const productionParts = typeof rowData[2] === 'string' ? 
                                    rowData[2].split(' ') : ['0', ''];
                                
                                cropData.push({
                                    barangay: typeof rowData[0] === 'string' ? rowData[0] : '',
                                    crop: typeof rowData[1] === 'string' ? rowData[1] : '',
                                    production: parseFloat(productionParts[0].replace(/,/g, '')) || 0,
                                    unit: productionParts[1] || '',
                                    category: typeof rowData[4] === 'string' ? rowData[4] : 'Uncategorized'
                                });
                            }
                        });
                    } else {
                        $('#cropsTable tbody tr').each(function() {
                            const cells = $(this).find('td');
                            if (cells.length >= 5) {
                                const productionText = $(cells[2]).text();
                                const productionParts = productionText.trim().split(' ');
                                
                                cropData.push({
                                    barangay: $(cells[0]).text().trim(),
                                    crop: $(cells[1]).text().trim(),
                                    production: parseFloat(productionParts[0].replace(/,/g, '')) || 0,
                                    unit: productionParts[1] || '',
                                    category: $(cells[4]).text().trim()
                                });
                            }
                        });
                    }
                    
                    if (cropData.length === 0) {
                        $('#production-insights').html('<p class="text-center">No crop data available</p>');
                        return;
                    }
                    
                    // Calculate insights
                    const insights = calculateCropInsights(cropData);
                    
                    // Build insights HTML
                    let insightsHtml = `
                        <div class="list-group">
                            <div class="list-group-item bg-light font-weight-bold">
                                <i class="bi bi-award"></i> Top Producing Barangay
                            </div>
                            <div class="list-group-item">
                                <h5 class="mb-1">${insights.topBarangay.name}</h5>
                                <p class="mb-1">Total Production: ${insights.topBarangay.production.toLocaleString()} ${insights.topBarangay.unit}</p>
                                <p class="mb-1">Top Crop: ${insights.topBarangay.topCrop}</p>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div class="list-group-item bg-light font-weight-bold mt-3">
                                <i class="bi bi-star"></i> Top Crop
                            </div>
                            <div class="list-group-item">
                                <h5 class="mb-1">${insights.topCrop.name}</h5>
                                <p class="mb-1">Total Production: ${insights.topCrop.production.toLocaleString()} ${insights.topCrop.unit}</p>
                                <p class="mb-1">Category: ${insights.topCrop.category}</p>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div class="list-group-item bg-light font-weight-bold mt-3">
                                <i class="bi bi-pie-chart"></i> Production by Category
                            </div>
                            <div class="list-group-item">
                                <ul class="list-unstyled">
                    `;
                    
                    insights.categories.forEach(category => {
                        const percentage = (category.production / insights.totalProduction * 100).toFixed(1);
                        insightsHtml += `
                            <li class="mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>${category.name}: ${category.production.toLocaleString()} ${category.unit}</span>
                                    <span>${percentage}%</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" 
                                        style="width: ${percentage}%; background-color: ${getColorForCategory(category.name)}" 
                                        aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </li>
                        `;
                    });
                    
                    insightsHtml += `
                                </ul>
                            </div>
                        </div>
                    `;
                    
                    // Update the insights container
                    $('#production-insights').html(insightsHtml);
                    
                } catch (error) {
                    console.error("Error updating production insights:", error);
                    $('#production-insights').html(`
                        <div class="alert alert-danger">
                            <p>Error generating production insights. Please try again.</p>
                        </div>
                    `);
                }
            }
            
            // Helper function to calculate crop insights
            function calculateCropInsights(cropData) {
                // Calculate top barangay by production
                const barangayProduction = {};
                const barangayCrops = {};
                
                // Calculate top crop
                const cropProduction = {};
                const cropCategories = {};
                
                // Calculate category totals
                const categoryProduction = {};
                
                // Get common unit
                const units = cropData.map(item => item.unit);
                const commonUnit = units.length > 0 ? 
                    units.reduce((acc, curr, i, arr) => 
                        arr.filter(v => v === acc).length >= arr.filter(v => v === curr).length ? acc : curr
                    ) : '';
                
                let totalProduction = 0;
                
                // Process crop data
                cropData.forEach(item => {
                    // Barangay calculations
                    if (!barangayProduction[item.barangay]) {
                        barangayProduction[item.barangay] = 0;
                        barangayCrops[item.barangay] = {};
                    }
                    barangayProduction[item.barangay] += item.production;
                    
                    if (!barangayCrops[item.barangay][item.crop]) {
                        barangayCrops[item.barangay][item.crop] = 0;
                    }
                    barangayCrops[item.barangay][item.crop] += item.production;
                    
                    // Crop calculations
                    if (!cropProduction[item.crop]) {
                        cropProduction[item.crop] = 0;
                        cropCategories[item.crop] = item.category;
                    }
                    cropProduction[item.crop] += item.production;
                    
                    // Category calculations
                    if (!categoryProduction[item.category]) {
                        categoryProduction[item.category] = 0;
                    }
                    categoryProduction[item.category] += item.production;
                    
                    totalProduction += item.production;
                });
                
                // Find top barangay
                let topBarangayName = '';
                let topBarangayProduction = 0;
                
                for (const barangay in barangayProduction) {
                    if (barangayProduction[barangay] > topBarangayProduction) {
                        topBarangayName = barangay;
                        topBarangayProduction = barangayProduction[barangay];
                    }
                }
                
                // Find top crop for top barangay
                let topBarangayCrop = '';
                let topBarangayCropProduction = 0;
                
                for (const crop in barangayCrops[topBarangayName]) {
                    if (barangayCrops[topBarangayName][crop] > topBarangayCropProduction) {
                        topBarangayCrop = crop;
                        topBarangayCropProduction = barangayCrops[topBarangayName][crop];
                    }
                }
                
                // Find top crop overall
                let topCropName = '';
                let topCropProduction = 0;
                
                for (const crop in cropProduction) {
                    if (cropProduction[crop] > topCropProduction) {
                        topCropName = crop;
                        topCropProduction = cropProduction[crop];
                    }
                }
                
                // Prepare category data
                const categories = Object.keys(categoryProduction).map(name => ({
                    name,
                    production: categoryProduction[name],
                    unit: commonUnit
                })).sort((a, b) => b.production - a.production);
                
                return {
                    topBarangay: {
                        name: topBarangayName,
                        production: topBarangayProduction,
                        unit: commonUnit,
                        topCrop: topBarangayCrop
                    },
                    topCrop: {
                        name: topCropName,
                        production: topCropProduction,
                        unit: commonUnit,
                        category: cropCategories[topCropName] || 'Uncategorized'
                    },
                    categories,
                    totalProduction
                };
            }
            
            // Helper function to get colors for categories
            function getColorForCategory(category) {
                const categoryColors = {
                    'Vegetables': '#28a745',
                    'Fruits': '#fd7e14',
                    'Grains': '#ffc107',
                    'Root Crops': '#6f42c1',
                    'Legumes': '#20c997',
                    'Herbs': '#17a2b8',
                    'Uncategorized': '#6c757d'
                };
                
                return categoryColors[category] || '#007bff';
            }
            
            // Call updateProductionInsights when page loads
            updateProductionInsights();
            
            // Update insights when crops table changes
            $('#cropsTable').on('draw.dt', function() {
                updateProductionInsights();
            });
        });
    </script>

    <!-- Add this JavaScript code after the existing script sections in views/admin/reports.php -->

    <script>
        // Initialize crop production chart with controller data
        $(document).ready(function() {
            // Get crop data from PHP
            const cropProductionData = <?= json_encode($cropsPerBarangay) ?>;
            
            // Initialize chart when page loads
            initCropProductionChart('production', cropProductionData);
            
            // Listen for chart view changes
            $('[data-chart-view]').click(function() {
                const viewType = $(this).data('chart-view');
                
                // Update active button
                $('[data-chart-view]').removeClass('active');
                $(this).addClass('active');
                
                // Update chart with new view
                initCropProductionChart(viewType, cropProductionData);
            });
            
            // Initialize crop production chart
            function initCropProductionChart(viewType, cropData) {
                if (!cropData || cropData.length === 0) {
                    console.log("No crop production data available");
                    return;
                }
                
                // Process data for the chart
                const barangayData = processBarangayData(cropData);
                
                // Sort barangays by the selected view type
                const sortedBarangays = Object.keys(barangayData).sort((a, b) => {
                    if (viewType === 'yield') {
                        return barangayData[b].yieldRate - barangayData[a].yieldRate;
                    } else if (viewType === 'area') {
                        return barangayData[b].area - barangayData[a].area;
                    } else {
                        return barangayData[b].production - barangayData[a].production;
                    }
                });
                
                // Limit to top 10 barangays for better visualization
                const topBarangays = sortedBarangays.slice(0, 10);
                
                // Set chart colors based on view type
                let primaryColor = '#28a745'; // Default - green for production
                if (viewType === 'area') {
                    primaryColor = '#007bff'; // Blue for area
                } else if (viewType === 'yield') {
                    primaryColor = '#17a2b8'; // Cyan for yield rate
                }
                
                // Get the canvas context
                const cropProductionCtx = document.getElementById('cropProductionChart');
                if (!cropProductionCtx) {
                    console.error("Cannot find cropProductionChart canvas");
                    return;
                }
                
                // Destroy previous chart if it exists
                if (window.cropProductionChart) {
                    window.cropProductionChart.destroy();
                }
                
                // Determine label based on view type
                let yAxisLabel = 'Production Volume';
                let dataValues = topBarangays.map(b => barangayData[b].production);
                
                if (viewType === 'area') {
                    yAxisLabel = 'Planted Area (hectares)';
                    dataValues = topBarangays.map(b => barangayData[b].area);
                } else if (viewType === 'yield') {
                    yAxisLabel = 'Yield Rate (production/area)';
                    dataValues = topBarangays.map(b => barangayData[b].yieldRate);
                }
                
                // Create new chart
                window.cropProductionChart = new Chart(cropProductionCtx, {
                    type: 'bar',
                    data: {
                        labels: topBarangays,
                        datasets: [{
                            label: yAxisLabel,
                            data: dataValues,
                            backgroundColor: primaryColor,
                            borderColor: primaryColor,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: yAxisLabel
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Barangays'
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    afterTitle: function(tooltipItems) {
                                        return 'Top Crops:';
                                    },
                                    afterBody: function(tooltipItems) {
                                        const barangay = tooltipItems[0].label;
                                        const topCrops = barangayData[barangay].topCrops;
                                        return topCrops.map(crop => 
                                            `${crop.name}: ${crop.production.toLocaleString()} ${crop.unit}`);
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Update production insights based on the processed data
                updateProductionInsights(barangayData);
            }
            
            // Process barangay data for chart visualization
            function processBarangayData(cropData) {
                const barangayData = {};
                
                // Group data by barangay
                cropData.forEach(item => {
                    const barangayName = item.barangay_name;
                    
                    if (!barangayData[barangayName]) {
                        barangayData[barangayName] = {
                            production: 0,
                            area: 0,
                            yieldRate: 0,
                            crops: {},
                            topCrops: [],
                            productionUnit: item.production_unit || '',
                            areaUnit: item.area_unit || ''
                        };
                    }
                    
                    // Add production values
                    const production = parseFloat(item.total_production) || 0;
                    const area = parseFloat(item.total_planted_area) || 0;
                    
                    barangayData[barangayName].production += production;
                    barangayData[barangayName].area += area;
                    
                    // Track individual crops
                    if (!barangayData[barangayName].crops[item.product_name]) {
                        barangayData[barangayName].crops[item.product_name] = {
                            name: item.product_name,
                            production: 0,
                            area: 0,
                            unit: item.production_unit || '',
                            category: item.category_name || 'Uncategorized'
                        };
                    }
                    
                    barangayData[barangayName].crops[item.product_name].production += production;
                    barangayData[barangayName].crops[item.product_name].area += area;
                });
                
                // Calculate yield rates and get top crops for each barangay
                Object.keys(barangayData).forEach(barangay => {
                    // Calculate yield rate
                    if (barangayData[barangay].area > 0) {
                        barangayData[barangay].yieldRate = 
                            barangayData[barangay].production / barangayData[barangay].area;
                    }
                    
                    // Get top 3 crops by production
                    const cropArray = Object.values(barangayData[barangay].crops);
                    barangayData[barangay].topCrops = cropArray
                        .sort((a, b) => b.production - a.production)
                        .slice(0, 3);
                });
                
                return barangayData;
            }
            
            // Update production insights based on the provided data
            function updateProductionInsights(barangayData) {
                try {
                    // Find top producing barangay
                    let topBarangayName = '';
                    let topBarangayProduction = 0;
                    
                    Object.keys(barangayData).forEach(barangay => {
                        if (barangayData[barangay].production > topBarangayProduction) {
                            topBarangayName = barangay;
                            topBarangayProduction = barangayData[barangay].production;
                        }
                    });
                    
                    // Find top crop overall
                    const allCrops = {};
                    Object.keys(barangayData).forEach(barangay => {
                        Object.keys(barangayData[barangay].crops).forEach(crop => {
                            if (!allCrops[crop]) {
                                allCrops[crop] = {
                                    name: crop,
                                    production: 0,
                                    category: barangayData[barangay].crops[crop].category,
                                    unit: barangayData[barangay].crops[crop].unit
                                };
                            }
                            allCrops[crop].production += barangayData[barangay].crops[crop].production;
                        });
                    });
                    
                    let topCropName = '';
                    let topCropProduction = 0;
                    let topCropCategory = '';
                    let topCropUnit = '';
                    
                    Object.keys(allCrops).forEach(crop => {
                        if (allCrops[crop].production > topCropProduction) {
                            topCropName = crop;
                            topCropProduction = allCrops[crop].production;
                            topCropCategory = allCrops[crop].category;
                            topCropUnit = allCrops[crop].unit;
                        }
                    });
                    
                    // Calculate production by category
                    const categories = {};
                    let totalProduction = 0;
                    
                    Object.keys(allCrops).forEach(crop => {
                        const category = allCrops[crop].category;
                        if (!categories[category]) {
                            categories[category] = {
                                name: category,
                                production: 0,
                                unit: allCrops[crop].unit
                            };
                        }
                        categories[category].production += allCrops[crop].production;
                        totalProduction += allCrops[crop].production;
                    });
                    
                    // Convert to array and sort
                    const categoryArray = Object.values(categories).sort((a, b) => 
                        b.production - a.production);
                    
                    // Build insights HTML
                    let insightsHtml = '';
                    
                    if (topBarangayName) {
                        const topBarangay = barangayData[topBarangayName];
                        const topCrop = topBarangay.topCrops.length > 0 ? 
                            topBarangay.topCrops[0].name : 'None';
                        
                        insightsHtml = `
                            <div class="list-group">
                                <div class="list-group-item bg-light font-weight-bold">
                                    <i class="bi bi-award"></i> Top Producing Barangay
                                </div>
                                <div class="list-group-item">
                                    <h5 class="mb-1">${topBarangayName}</h5>
                                    <p class="mb-1">Total Production: ${topBarangay.production.toLocaleString()} ${topBarangay.productionUnit}</p>
                                    <p class="mb-1">Top Crop: ${topCrop}</p>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                    </div>
                                </div>
                                
                                <div class="list-group-item bg-light font-weight-bold mt-3">
                                    <i class="bi bi-star"></i> Top Crop
                                </div>
                                <div class="list-group-item">
                                    <h5 class="mb-1">${topCropName}</h5>
                                    <p class="mb-1">Total Production: ${topCropProduction.toLocaleString()} ${topCropUnit}</p>
                                    <p class="mb-1">Category: ${topCropCategory}</p>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                                    </div>
                                </div>
                                
                                <div class="list-group-item bg-light font-weight-bold mt-3">
                                    <i class="bi bi-pie-chart"></i> Production by Category
                                </div>
                                <div class="list-group-item">
                                    <ul class="list-unstyled">`;
                    
                        // Add category breakdown
                        categoryArray.forEach(category => {
                            const percentage = totalProduction > 0 ? 
                                (category.production / totalProduction * 100).toFixed(1) : 0;
                                
                            insightsHtml += `
                                <li class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>${category.name}: ${category.production.toLocaleString()} ${category.unit}</span>
                                        <span>${percentage}%</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                            style="width: ${percentage}%; background-color: ${getColorForCategory(category.name)}" 
                                            aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </li>`;
                        });
                        
                        insightsHtml += `
                                    </ul>
                                </div>
                            </div>`;
                    } else {
                        insightsHtml = '<p class="text-center">No crop data available</p>';
                    }
                    
                    // Update the insights container
                    $('#production-insights').html(insightsHtml);
                } catch (error) {
                    console.error("Error updating production insights:", error);
                    $('#production-insights').html(`
                        <div class="alert alert-danger">
                            <p>Error generating production insights. Please try again.</p>
                        </div>
                    `);
                }
            }
            
            // Helper function to get colors for categories
            function getColorForCategory(category) {
                const categoryColors = {
                    'Vegetables': '#28a745',
                    'Fruits': '#fd7e14',
                    'Grains': '#ffc107',
                    'Root Crops': '#6f42c1',
                    'Legumes': '#20c997',
                    'Herbs': '#17a2b8',
                    'Uncategorized': '#6c757d'
                };
                
                return categoryColors[category] || '#007bff';
            }
        });
    </script>

    <!-- Enhanced season details handler -->
    <script>
        // Enhanced season details handler
        $(document).ready(function() {
            // Handle clicking on season rows
            $('#seasonalTable').on('click', 'tbody tr', function() {
                const seasonName = $(this).find('td:first').text().trim();
                const seasonPeriod = $(this).find('td:last').text().trim();
                
                // Show loading indicator in modal
                $('#seasonDetailsModal .modal-body').html(
                    '<div class="d-flex justify-content-center">' +
                        '<div class="spinner-border text-primary" role="status">' +
                            '<span class="sr-only">Loading...</span>' +
                        '</div>' +
                    '</div>' +
                    '<p class="text-center mt-2">Loading season details...</p>'
                );
                
                // Show the modal
                $('#seasonDetailsModal').modal('show');
                
                // Fetch season details from GeoAnalyticsController
                $.ajax({
                    url: '../../controllers/GeoAnalyticsController.php',
                    type: 'GET',
                    data: {
                        action: 'getSeasonDetails',
                        seasonName: seasonName
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update modal content with season details
                            updateSeasonModalContent(response);
                        } else {
                            // Show error message
                            $('#seasonDetailsModal .modal-body').html(
                                '<div class="alert alert-danger">' +
                                    '<i class="bi bi-exclamation-triangle"></i> ' +
                                    'Error: ' + response.message +
                                '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        $('#seasonDetailsModal .modal-body').html(
                            '<div class="alert alert-danger">' +
                                '<i class="bi bi-exclamation-triangle"></i> ' +
                                'Error connecting to server. Please try again later.' +
                            '</div>'
                        );
                        console.error('AJAX Error:', error);
                    }
                });
            });
            
            // Handle print button in season details modal
            $('#print-season-details').click(function() {
                const seasonName = $('#season-name').text();
                const seasonContent = $('#seasonDetailsModal .modal-body').html();
                
                // Open print window
                const printWindow = window.open('', '_blank');
                
                // Generate print content
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Season Details: ${seasonName}</title>
                        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
                        <style>
                            body { 
                                font-family: 'Poppins', Arial, sans-serif;
                                padding: 20px; 
                            }
                            h1 { 
                                color: #28a745; 
                                margin-bottom: 20px; 
                                border-bottom: 2px solid #28a745;
                                padding-bottom: 10px;
                            }
                            .header {
                                text-align: center;
                                margin-bottom: 30px;
                            }
                            .logo {
                                background-color: #28a745;
                                color: white;
                                width: 60px;
                                height: 60px;
                                line-height: 60px;
                                border-radius: 50%;
                                font-size: 24px;
                                font-weight: bold;
                                display: inline-block;
                                margin-bottom: 15px;
                            }
                            .section { 
                                margin-bottom: 30px;
                                break-inside: avoid;
                            }
                            .section-title {
                                background-color: #f8f9fa;
                                padding: 10px;
                                border-left: 4px solid #28a745;
                                margin-bottom: 15px;
                            }
                            table { 
                                width: 100%; 
                                border-collapse: collapse; 
                                margin-bottom: 20px;
                            }
                            th, td { 
                                padding: 8px; 
                                border: 1px solid #dee2e6; 
                            }
                            th { 
                                background-color: #f8f9fa; 
                            }
                            .footer {
                                margin-top: 30px;
                                border-top: 1px solid #dee2e6;
                                padding-top: 10px;
                                font-size: 12px;
                                color: #6c757d;
                                text-align: center;
                            }
                            @media print {
                                body { padding: 0; }
                                .no-print { display: none; }
                                @page {
                                    margin: 0.5in;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <div class="logo">FM</div>
                                <h1>Season Details: ${seasonName}</h1>
                                <p class="text-muted">Farmers Market Management System - Agricultural Report</p>
                            </div>
                            
                            <div class="no-print mb-3">
                                <button onclick="window.print()" class="btn btn-success">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                                <button onclick="window.close()" class="btn btn-secondary ml-2">
                                    <i class="bi bi-x"></i> Close
                                </button>
                            </div>
                            
                            ${seasonContent}
                            
                            <div class="footer">
                                Report generated on ${new Date().toLocaleString()}
                                <br>
                                &copy; Farmers Market Management System
                            </div>
                        </div>
                    </body>
                    </html>
                `);
                
                // Finalize the print window
                printWindow.document.close();
                setTimeout(function() {
                    printWindow.focus();
                }, 500);
            });
            
            // Function to update season modal content
            function updateSeasonModalContent(data) {
                // Build the modal content
                let modalContent = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong><i class="bi bi-info-circle"></i> Season Information</strong>
                                </div>
                                <div class="card-body">
                                    <h4 class="text-info" id="season-name">${data.seasonName}</h4>
                                    <p><strong>Period:</strong> <span id="season-period">${data.startMonth} - ${data.endMonth}</span></p>
                                    <p><strong>Description:</strong> <span id="season-description">${data.description || 'No description available'}</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><i class="bi bi-plant"></i> Planting Recommendations</strong>
                                </div>
                                <div class="card-body">
                                    <p id="season-recommendations">${data.recommendations || 'No recommendations available'}</p>
                                </div>
                            </div>
                        </div>
                    </div>`;
                    
                // Add top crops section if available
                if (data.topCrops && data.topCrops.length > 0) {
                    modalContent += `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong><i class="bi bi-bar-chart"></i> Top Crops for this Season</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover" id="season-crops-table">
                                                <thead>
                                                    <tr>
                                                        <th>Crop</th>
                                                        <th>Barangay</th>
                                                        <th>Production</th>
                                                        <th>Area</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="season-crops-data">`;
                
                    // Add rows for each crop
                    data.topCrops.forEach(crop => {
                        modalContent += `
                            <tr>
                                <td>${crop.product_name}</td>
                                <td>${crop.barangay_name}</td>
                                <td>${parseFloat(crop.total_production).toLocaleString()} ${crop.production_unit}</td>
                                <td>${parseFloat(crop.total_planted_area).toLocaleString()} ${crop.area_unit}</td>
                            </tr>`;
                    });
                    
                    modalContent += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                } else {
                    modalContent += `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> No crop data available for this season.
                                </div>
                            </div>
                        </div>`;
                }
                
                // Update the modal content
                $('#seasonDetailsModal .modal-body').html(modalContent);
            }
        });
    </script>
</body>
</html>