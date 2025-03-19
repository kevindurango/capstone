<?php
session_start();

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Sales.php';
require_once '../../models/Log.php';

$salesClass = new Sales();
$logClass = new Log();

// Fetch sales metrics
$dailySales = $salesClass->getDailySales();
$weeklySales = $salesClass->getWeeklySales();
$monthlySales = $salesClass->getMonthlySales();
$totalRevenue = $salesClass->getTotalRevenue();
$averageOrderValue = $salesClass->getAverageOrderValue();
$salesByCategory = $salesClass->getSalesByCategory();
$topProducts = $salesClass->getTopProducts(5); // Get top 5 products
$revenueData = $salesClass->getRevenueData(); // For chart

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
    $logClass->logActivity($_SESSION['user_id'], "Manager logged out");
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
    <title>Sales Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/admin-dashboard.css">
    <link rel="stylesheet" href="../../public/style/manager-sales.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Add Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-graph-up"></i> SALES MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sales Management</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 gradient-text">
                            <i class="bi bi-graph-up-arrow"></i> Sales Overview
                        </h1>
                        <p class="text-muted mb-0">Track and analyze your sales performance</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success mr-2" onclick="exportSalesReport()">
                            <i class="bi bi-file-earmark-excel"></i> Export Report
                        </button>
                        <form method="POST" class="mb-0">
                            <button type="submit" name="logout" class="btn btn-danger logout-btn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Date Filter Section -->
                <form id="dateFilterForm" class="date-filter-container">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <div class="date-input-group">
                                <label for="startDate"><i class="bi bi-calendar3"></i> Start Date:</label>
                                <input type="date" id="startDate" name="startDate" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="date-input-group">
                                <label for="endDate"><i class="bi bi-calendar3"></i> End Date:</label>
                                <input type="date" id="endDate" name="endDate" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Sales Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="sales-dashboard-card">
                            <div class="sales-card-icon text-primary">
                                <i class="bi bi-calendar-day"></i>
                            </div>
                            <div class="sales-card-title">Today's Sales</div>
                            <div class="sales-card-value">₱<?= number_format($dailySales, 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="sales-dashboard-card">
                            <div class="sales-card-icon text-success">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <div class="sales-card-title">Weekly Sales</div>
                            <div class="sales-card-value">₱<?= number_format($weeklySales, 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="sales-dashboard-card">
                            <div class="sales-card-icon text-info">
                                <i class="bi bi-calendar-month"></i>
                            </div>
                            <div class="sales-card-title">Monthly Sales</div>
                            <div class="sales-card-value">₱<?= number_format($monthlySales, 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="sales-dashboard-card">
                            <div class="sales-card-icon text-warning">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="sales-card-title">Average Order</div>
                            <div class="sales-card-value">₱<?= number_format($averageOrderValue, 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Sales Charts -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-panel">
                            <h3 class="chart-panel-title"><i class="bi bi-graph-up"></i> Revenue Trend</h3>
                            <div class="chart-container">
                                <canvas id="revenueTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-panel">
                            <h3 class="chart-panel-title"><i class="bi bi-pie-chart"></i> Sales by Category</h3>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products Table -->
                <div class="products-table-container">
                    <div class="products-table-header">
                        <h5><i class="bi bi-trophy"></i> Top Performing Products</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td><?= number_format($product['units_sold']) ?></td>
                                    <td>₱<?= number_format($product['revenue'], 2) ?></td>
                                    <td>
                                        <?php if ($product['trend'] > 0): ?>
                                            <i class="bi bi-arrow-up-circle-fill trend-up"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-circle-fill trend-down"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revenueData, 'date')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($revenueData, 'amount')) ?>,
                    borderColor: '#28a745',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($salesByCategory, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($salesByCategory, 'total')) ?>,
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1']
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

        // Export functionality
        function exportSalesReport() {
            const startDate = document.getElementById('startDate')?.value || '';
            const endDate = document.getElementById('endDate')?.value || '';
            
            // Redirect to export endpoint with date parameters
            window.location.href = `export-sales.php?start=${startDate}&end=${endDate}`;
        }

        // Handle date filter form submission
        document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be after end date');
                return;
            }
            
            // Here you would implement date filtering logic
            // For now just show an alert
            alert(`Filtering data from ${startDate} to ${endDate}`);
            
            // In a real implementation, you would make an AJAX request to refresh the data
            // window.location.href = `?start=${startDate}&end=${endDate}`;
        });
    </script>
</body>
</html>