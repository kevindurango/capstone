<?php
session_start();

// Check if user is logged in as Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

require_once '../../models/Order.php';
require_once '../../models/Log.php';
require_once '../../models/Dashboard.php';
require_once '../../models/Sales.php';

$orderModel = new Order();
$logClass = new Log();
$dashboard = new Dashboard();
$salesModel = new Sales();

// Get organization_head_user_id from session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Date filters
$defaultStartDate = date('Y-m-01'); // First day of current month
$defaultEndDate = date('Y-m-d'); // Today

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Get all orders for this date range and sales analytics
try {
    // Use both Order and Sales models to get comprehensive data
    $orders = $orderModel->getOrdersByDateRange($startDate, $endDate);
    
    // Get additional sales metrics from Sales model
    $revenueData = $salesModel->getRevenueData($startDate, $endDate);
    $topProducts = $salesModel->getTopProducts(5);
    $categorySales = $salesModel->getSalesByCategory();
    $avgOrderValue = $salesModel->getAverageOrderValue();
    
    // Check if the methods are returning data correctly
    if ($orders === false) {
        throw new Exception("Failed to retrieve orders from the database.");
    }
    
    // Debug info to check what we're getting from the models
    error_log("Retrieved " . count($orders) . " orders from date range $startDate to $endDate");
    error_log("Retrieved " . count($revenueData) . " revenue data points from Sales model");
    
    // Filter orders - include both pending and completed for testing/demonstration
    $salesData = [];
    $totalSales = 0;
    $totalOrders = 0;
    $averageOrderValue = $avgOrderValue;  // Use the value from the Sales model
    
    foreach ($orders as $order) {
        // Include all orders for now, or filter by status if needed
        if (strtolower($order['order_status']) == 'pending' || strtolower($order['order_status']) == 'completed') {
            $salesData[] = $order;
            $totalSales += floatval($order['total_amount'] ?? 0);
            $totalOrders++;
        }
    }
    
    // Group sales by date for the chart - using Sales model revenue data if available
    $salesByDate = [];
    if (!empty($revenueData)) {
        foreach ($revenueData as $data) {
            $salesByDate[$data['date']] = floatval($data['amount']);
        }
    } else {
        // Fallback to the original method if Sales model doesn't return data
        foreach ($salesData as $order) {
            $date = substr($order['order_date'], 0, 10);
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += floatval($order['total_amount'] ?? 0);
        }
    }
    // Sort by date
    ksort($salesByDate);
    
    // If no data is found, show a message
    if (empty($salesData)) {
        $_SESSION['message'] = "No sales data found for the selected period. Try adjusting your date range.";
        $_SESSION['message_type'] = 'info';
    }
    
} catch (Exception $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
    $_SESSION['message'] = "Error retrieving sales data: " . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    $salesData = [];
    $salesByDate = [];
    $totalSales = 0;
    $totalOrders = 0;
    $averageOrderValue = 0;
    $topProducts = [];
    $categorySales = [];
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['logout'])) {
        $logClass->logActivity($organization_head_user_id, "Organization Head logged out.");
        session_unset();
        session_destroy();
        header("Location: organization-head-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Organization Head Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            font-family: 'Poppins', sans-serif;
            background-color: #f5f8fa;
        }
        
        .content-wrapper {
            padding: 1.5rem;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .page-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(to right, #198754, #20c997);
            color: white;
            border-bottom: 0;
            white-space: nowrap;
            font-weight: 600;
            padding: 1rem 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        
        .status-completed { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        
        .status-pending { 
            background-color: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba;
        }
        
        .status-canceled { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        
        .stats-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #198754, #20c997);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            color: #20c997;
            margin-bottom: 0.75rem;
            opacity: 0.8;
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 0.25rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .chart-container {
            height: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .data-filter-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .date-filter-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .date-filter-label {
            font-weight: 500;
            margin-right: 0.5rem;
            color: #495057;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .date-filter-label i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .date-input {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out;
            max-width: 180px;
        }
        
        .date-input:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .btn-export {
            background: linear-gradient(to right, #198754, #20c997);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-export i {
            margin-right: 0.5rem;
        }
        
        .btn-export:hover {
            background: linear-gradient(to right, #146c43, #15a37f);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }
        
        .btn-print {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-print i {
            margin-right: 0.5rem;
        }
        
        .btn-print:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }
        
        .btn-group-actions {
            display: flex;
            gap: 10px;
        }
        
        .quick-filter {
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
        }
        
        .quick-filter label {
            margin-right: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        @media print {
            .sidebar, .organization-header, .btn, form button, 
            .data-filter-card, .no-print {
                display: none !important;
            }
            
            .ml-sm-auto, .col-lg-10 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                margin-left: 0 !important;
            }
            
            .chart-container {
                height: 300px !important;
                page-break-inside: avoid;
            }
            
            .table {
                width: 100% !important;
                page-break-inside: auto;
            }
            
            .status-badge {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: none !important;
            }
            
            body {
                padding: 0;
                margin: 0;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 1rem;
                display: block !important;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center py-3">
        <h2><i class="bi bi-building"></i> ORGANIZATION SALES REPORT
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 pb-5">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white shadow-sm">
                        <li class="breadcrumb-item"><a href="organization-head-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sales Report</li>
                    </ol>
                </nav>
                
                <!-- Print Header - Only visible when printing -->
                <div class="print-header d-none">
                    <h2>Sales Report</h2>
                    <p>Period: <?= date('M d, Y', strtotime($startDate)) ?> to <?= date('M d, Y', strtotime($endDate)) ?></p>
                    <p>Generated on: <?= date('M d, Y H:i') ?></p>
                </div>
                
                <div class="content-wrapper">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Sales Report</h1>
                            <p class="page-subtitle">View and analyze your sales data</p>
                        </div>
                        <form method="POST" class="ml-3 no-print" onsubmit="return confirm('Are you sure you want to logout?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-info-circle-fill mr-2"></i>
                            <?= $_SESSION['message'] ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php 
                            unset($_SESSION['message']); 
                            unset($_SESSION['message_type']);
                        ?>
                    <?php endif; ?>

                    <!-- Date Range Filter -->
                    <div class="data-filter-card no-print">
                        <div class="row align-items-center">
                            <div class="col-md-8 mb-3 mb-md-0">
                                <h5 class="mb-3"><i class="bi bi-calendar3"></i> Select Date Range</h5>
                                <form method="GET" action="" class="date-filter-group">
                                    <div class="form-group mr-3 mb-0">
                                        <label for="start_date" class="date-filter-label">
                                            <i class="bi bi-calendar-event"></i> From:
                                        </label>
                                        <input type="date" id="start_date" name="start_date" class="form-control date-input" 
                                               value="<?= htmlspecialchars($startDate) ?>">
                                    </div>
                                    <div class="form-group mr-3 mb-0">
                                        <label for="end_date" class="date-filter-label">
                                            <i class="bi bi-calendar-event"></i> To:
                                        </label>
                                        <input type="date" id="end_date" name="end_date" class="form-control date-input" 
                                               value="<?= htmlspecialchars($endDate) ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-filter"></i> Apply Filter
                                    </button>
                                </form>
                                
                                <div class="quick-filter">
                                    <label>Quick Filter:</label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary quick-period" data-period="today">Today</button>
                                        <button type="button" class="btn btn-outline-secondary quick-period" data-period="week">This Week</button>
                                        <button type="button" class="btn btn-outline-secondary quick-period" data-period="month">This Month</button>
                                        <button type="button" class="btn btn-outline-secondary quick-period" data-period="year">This Year</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="btn-group-actions d-flex justify-content-md-end">
                                    <button id="exportCSV" class="btn btn-export">
                                        <i class="bi bi-file-earmark-excel"></i> Export Report
                                    </button>
                                    <button id="printReport" class="btn btn-print">
                                        <i class="bi bi-printer"></i> Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="stats-card">
                                <div class="stats-icon">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div class="stats-number">₱<?= number_format($totalSales, 2) ?></div>
                                <div class="stats-label">Total Revenue</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="stats-card">
                                <div class="stats-icon">
                                    <i class="bi bi-bag-check"></i>
                                </div>
                                <div class="stats-number"><?= $totalOrders ?></div>
                                <div class="stats-label">Total Orders</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-icon">
                                    <i class="bi bi-calculator"></i>
                                </div>
                                <div class="stats-number">₱<?= number_format($averageOrderValue, 2) ?></div>
                                <div class="stats-label">Average Order Value</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Chart -->
                    <div class="chart-container mb-4">
                        <h5 class="mb-3"><i class="bi bi-graph-up"></i> Sales Trend</h5>
                        <canvas id="salesChart"></canvas>
                    </div>

                    <!-- Top Products Section - Using Sales Model Data -->
                    <?php if (!empty($topProducts)): ?>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="table-responsive table-container">
                                <h5 class="mb-3"><i class="bi bi-award"></i> Top Selling Products</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                            <th>Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-box-seam text-muted mr-2"></i>
                                                        <?= htmlspecialchars($product['name']) ?>
                                                    </div>
                                                    <small class="text-muted"><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></small>
                                                </td>
                                                <td><?= number_format($product['units_sold']) ?></td>
                                                <td>₱<?= number_format($product['revenue'], 2) ?></td>
                                                <td>
                                                    <?php if ($product['trend'] > 0): ?>
                                                        <i class="bi bi-arrow-up-circle-fill text-success"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-arrow-down-circle-fill text-danger"></i>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="mb-3"><i class="bi bi-pie-chart"></i> Sales by Category</h5>
                                <canvas id="categorySalesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sales Data Table -->
                    <div class="table-responsive table-container">
                        <h5 class="mb-3"><i class="bi bi-table"></i> Sales Details</h5>
                        <table class="table table-hover" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesData)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                                                <p class="mt-3 mb-0">No sales data found for the selected period.</p>
                                                <p class="text-muted">Try adjusting the date range.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salesData as $sale): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars($sale['order_id']) ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person-circle text-muted mr-2"></i>
                                                    <?= htmlspecialchars($sale['customer_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="bi bi-calendar2 text-muted mr-1"></i>
                                                <?= date('M d, Y', strtotime($sale['order_date'])) ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock text-muted mr-1"></i>
                                                    <?= date('h:i A', strtotime($sale['order_date'])) ?>
                                                </small>
                                            </td>
                                            <td><strong>₱<?= number_format($sale['total_amount'], 2) ?></strong></td>
                                            <td>
                                                <?php
                                                    $statusClass = '';
                                                    $statusIcon = '';
                                                    switch(strtolower($sale['order_status'])) {
                                                        case 'completed':
                                                            $statusClass = 'completed';
                                                            $statusIcon = 'check-circle';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'pending';
                                                            $statusIcon = 'hourglass-split';
                                                            break;
                                                        case 'canceled':
                                                        case 'cancelled':
                                                            $statusClass = 'canceled';
                                                            $statusIcon = 'x-circle';
                                                            break;
                                                        default:
                                                            $statusClass = 'pending';
                                                            $statusIcon = 'circle';
                                                    }
                                                ?>
                                                <span class="status-badge status-<?= $statusClass ?>">
                                                    <i class="bi bi-<?= $statusIcon ?> mr-1"></i>
                                                    <?= ucfirst(htmlspecialchars($sale['order_status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Prepare chart data
            const salesDates = <?= json_encode(array_keys($salesByDate)) ?>;
            const salesValues = <?= json_encode(array_values($salesByDate)) ?>;
            
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: salesDates,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: salesValues,
                        backgroundColor: 'rgba(32, 201, 151, 0.2)',
                        borderColor: '#20c997',
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#20c997',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Sales Trend',
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#495057',
                            bodyColor: '#343a40',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            borderColor: '#e9ecef',
                            borderWidth: 1,
                            displayColors: true,
                            caretPadding: 10,
                            callbacks: {
                                label: function(context) {
                                    return '₱ ' + context.parsed.y.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 10
                            },
                            title: {
                                display: true,
                                text: 'Date',
                                font: {
                                    weight: 'bold'
                                },
                                padding: 10
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 10,
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            },
                            title: {
                                display: true,
                                text: 'Sales Amount (₱)',
                                font: {
                                    weight: 'bold'
                                },
                                padding: 10
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        line: {
                            tension: 0.4
                        }
                    }
                }
            });
            
            // Category Sales Chart
            <?php if (!empty($categorySales)): ?>
            const categoryCtx = document.getElementById('categorySalesChart').getContext('2d');
            
            // Prepare category chart data
            const categoryLabels = <?= json_encode(array_column($categorySales, 'category')) ?>;
            const categoryData = <?= json_encode(array_column($categorySales, 'total')) ?>;
            
            // Generate colors for each category
            const categoryColors = generateColors(categoryLabels.length);
            
            const categorySalesChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: categoryColors,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Revenue Distribution by Category',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        },
                        legend: {
                            position: 'right',
                            align: 'center',
                            labels: {
                                boxWidth: 15,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#495057',
                            bodyColor: '#343a40',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            borderColor: '#e9ecef',
                            borderWidth: 1,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    }
                }
            });
            <?php endif; ?>
            
            // Generate random colors for chart
            function generateColors(count) {
                const baseColors = [
                    '#20c997', '#0d6efd', '#ffc107', '#dc3545', '#6f42c1', 
                    '#fd7e14', '#0dcaf0', '#198754', '#6610f2', '#d63384'
                ];
                
                const colors = [];
                for (let i = 0; i < count; i++) {
                    if (i < baseColors.length) {
                        colors.push(baseColors[i]);
                    } else {
                        // Generate random colors if we need more than the base colors
                        const r = Math.floor(Math.random() * 200) + 55;
                        const g = Math.floor(Math.random() * 200) + 55;
                        const b = Math.floor(Math.random() * 200) + 55;
                        colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
                    }
                }
                return colors;
            }
            
            // Quick date filters
            $('.quick-period').click(function() {
                const today = new Date();
                let startDate = new Date();
                const period = $(this).data('period');
                
                switch(period) {
                    case 'today':
                        // Set to today
                        break;
                    case 'week':
                        // Set to start of current week (Sunday)
                        startDate.setDate(today.getDate() - today.getDay());
                        break;
                    case 'month':
                        // Set to start of current month
                        startDate.setDate(1);
                        break;
                    case 'year':
                        // Set to start of current year
                        startDate = new Date(today.getFullYear(), 0, 1);
                        break;
                }
                
                // Format dates for input fields (YYYY-MM-DD)
                $('#start_date').val(formatDate(startDate));
                $('#end_date').val(formatDate(today));
                
                // Submit the form
                $(this).closest('form').submit();
            });
            
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            
            // Export CSV
            document.getElementById('exportCSV').addEventListener('click', function() {
                // Create CSV content from table
                const table = document.getElementById('salesTable');
                let csv = [];
                const rows = table.querySelectorAll('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        // Clean the cell content (remove HTML, trim)
                        let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                        
                        // Quote fields with commas
                        data = data.replace(/"/g, '""');
                        if (data.includes(',')) {
                            data = `"${data}"`;
                        }
                        
                        row.push(data);
                    }
                    csv.push(row.join(','));
                }
                
                // Add report metadata
                const reportInfo = [
                    `"Sales Report: ${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}"`,
                    `"Generated on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}"`,
                    `"Total Sales: ₱<?= number_format($totalSales, 2) ?>"`,
                    `"Total Orders: ${<?= $totalOrders ?>}"`,
                    `"Average Order Value: ₱<?= number_format($averageOrderValue, 2) ?>"`,
                    ''  // Empty line before the actual data
                ];
                
                csv = reportInfo.concat(csv);
                const csvFile = csv.join('\n');
                
                // Create download link
                const downloadLink = document.createElement('a');
                downloadLink.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvFile);
                downloadLink.download = `sales_report_${document.getElementById('start_date').value}_to_${document.getElementById('end_date').value}.csv`;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            });
            
            // Print Report
            document.getElementById('printReport').addEventListener('click', function() {
                window.print();
            });
        });
    </script>
</body>
</html>
