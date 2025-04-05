<?php
session_start();

// Check if user is logged in as Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

require_once '../../controllers/OrderController.php';
require_once '../../models/Log.php';

$orderController = new OrderController();
$logClass = new Log();

// Get organization_head_user_id from session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Date filters
$defaultStartDate = date('Y-m-01'); // First day of current month
$defaultEndDate = date('Y-m-d'); // Today

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

// Get all orders for this date range
try {
    $orders = $orderController->getOrders();
    
    // Filter orders by date range and completed status
    $salesData = [];
    $totalSales = 0;
    $totalOrders = 0;
    $averageOrderValue = 0;
    
    foreach ($orders as $order) {
        // Add orders within date range and only completed ones
        $orderDate = substr($order['order_date'], 0, 10); // YYYY-MM-DD format
        if ($orderDate >= $startDate && $orderDate <= $endDate && 
            (strtolower($order['status']) == 'completed')) {
            $salesData[] = $order;
            $totalSales += $order['total_amount'];
            $totalOrders++;
        }
    }
    
    // Calculate average order value
    $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
    
    // Group sales by date for the chart
    $salesByDate = [];
    foreach ($salesData as $order) {
        $date = substr($order['order_date'], 0, 10);
        if (!isset($salesByDate[$date])) {
            $salesByDate[$date] = 0;
        }
        $salesByDate[$date] += $order['total_amount'];
    }
    // Sort by date
    ksort($salesByDate);
    
} catch (Exception $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
    $_SESSION['message'] = "Error retrieving sales data. Please try again later.";
    $_SESSION['message_type'] = 'danger';
    $salesData = [];
    $salesByDate = [];
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
        body { font-family: 'Poppins', sans-serif; }
        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .table thead th {
            background-color: #198754;
            color: white;
            border-bottom: 0;
            white-space: nowrap;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-completed { background-color: #28a745; color: #fff; }
        .card-stats {
            transition: transform 0.3s ease;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 100%;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .card-stats .card-header {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            font-weight: 600;
        }
        .card-stats .card-body {
            padding: 1.5rem;
        }
        .stats-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: #198754;
        }
        .stats-number {
            font-size: 1.75rem;
            font-weight: 600;
            color: #198754;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .chart-container {
            height: 400px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        .data-filter-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn-export {
            background: #20c997;
            color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .btn-export:hover {
            background: #198754;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-building"></i> ORGANIZATION SALES MANAGEMENT
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success"><i class="bi bi-graph-up"></i> Sales Report</h1>
                    <form method="POST" class="ml-3" onsubmit="return confirm('Are you sure you want to logout?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
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
                <div class="data-filter-card">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="start_date" class="mr-2">From:</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" 
                                           value="<?= htmlspecialchars($startDate) ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="end_date" class="mr-2">To:</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" 
                                           value="<?= htmlspecialchars($endDate) ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4 text-right">
                            <button id="exportCSV" class="btn btn-export">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                            <button id="printReport" class="btn btn-secondary ml-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-cash-stack stats-icon"></i> Total Sales</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number">₱<?= number_format($totalSales, 2) ?></div>
                                <div class="stats-label">Revenue from completed orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-cart-check stats-icon"></i> Completed Orders</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= $totalOrders ?></div>
                                <div class="stats-label">Total successful orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-calculator stats-icon"></i> Average Order</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number">₱<?= number_format($averageOrderValue, 2) ?></div>
                                <div class="stats-label">Average value per order</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Sales Data Table -->
                <div class="table-responsive table-container">
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
                                    <td colspan="5" class="text-center">No sales data found for the selected period.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($salesData as $sale): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($sale['order_id']) ?></td>
                                        <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($sale['order_date'])) ?></td>
                                        <td>₱<?= number_format($sale['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-completed">
                                                Completed
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgba(40, 167, 69, 1)',
                        pointRadius: 4,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Sales',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
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
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sales (₱)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            
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
                        let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').trim();
                        
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
