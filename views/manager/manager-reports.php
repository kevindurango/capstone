<?php
// Start the session to track login status
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';

// Database Connection
$database = new Database();
$conn = $database->connect();

// Fetch Order Summary
$orderSummaryQuery = "SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status";
$orderSummaryStmt = $conn->query($orderSummaryQuery);
$orderSummary = $orderSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Payment Summary
$paymentSummaryQuery = "SELECT payment_status, COUNT(*) AS total FROM payments GROUP BY payment_status";
$paymentSummaryStmt = $conn->query($paymentSummaryQuery);
$paymentSummary = $paymentSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Product Summary
$productSummaryQuery = "SELECT status, COUNT(*) AS total FROM products GROUP BY status";
$productSummaryStmt = $conn->query($productSummaryQuery);
$productSummary = $productSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Orders with driver information based on the actual database schema
$recentOrdersQuery = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, 
                      p.pickup_id, p.pickup_date, p.pickup_location, p.assigned_to,
                      u2.first_name AS driver_first_name, u2.last_name AS driver_last_name,
                      dd.vehicle_type, dd.license_number, dd.vehicle_plate,
                      p.pickup_notes
                      FROM orders AS o
                      JOIN users AS u ON o.consumer_id = u.user_id
                      LEFT JOIN pickups AS p ON o.order_id = p.order_id
                      LEFT JOIN users AS u2 ON p.assigned_to = u2.user_id
                      LEFT JOIN driver_details AS dd ON u2.user_id = dd.user_id
                      ORDER BY o.order_date DESC
                      LIMIT 10";
$recentOrdersStmt = $conn->query($recentOrdersQuery);
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

// Count pending orders for badge
$pendingOrdersQuery = "SELECT COUNT(*) AS pending_count FROM orders WHERE order_status = 'pending'";
$pendingOrdersStmt = $conn->query($pendingOrdersQuery);
$pendingOrders = $pendingOrdersStmt->fetch(PDO::FETCH_ASSOC);
$pendingOrderCount = $pendingOrders['pending_count'];

// Handle logout
if (isset($_POST['logout'])) {
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
            margin-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        .chart-container {
            position: relative;
            height: 220px;
            margin-bottom: 15px;
        }
        .badge-pill {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .list-group-item {
            border-left: none;
            border-right: none;
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
            <?php include '../../views/global/manager-sidebar.php'; ?>

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
                        <?php if($pendingOrderCount > 0): ?>
                            <span class="badge badge-danger"><?= $pendingOrderCount ?> pending orders</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
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
                                                case 'cancelled': $badgeClass = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?> badge-pill"><?= $order['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-credit-card-fill me-2 text-success"></i> Payment Summary
                                </h5>
                                <div class="chart-container">
                                    <canvas id="paymentChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($paymentSummary as $payment): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($payment['payment_status']) ?>
                                            <?php
                                            $badgeClass = 'primary';
                                            switch($payment['payment_status']) {
                                                case 'paid': $badgeClass = 'success'; break;
                                                case 'pending': $badgeClass = 'warning'; break;
                                                case 'failed': $badgeClass = 'danger'; break;
                                                case 'refunded': $badgeClass = 'info'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $badgeClass ?> badge-pill"><?= $payment['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Product Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body"> <!-- Fix: Changed from 'card->body' to 'card-body' -->
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
                                                case 'active': $badgeClass = 'success'; break;
                                                case 'inactive': $badgeClass = 'secondary'; break;
                                                case 'out_of_stock': $badgeClass = 'danger'; break;
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
                                        <th>Consumer</th>
                                        <th>Status</th>
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
                                                    $statusClass = 'secondary';
                                                    switch($order['order_status']) {
                                                        case 'pending': $statusClass = 'warning'; break;
                                                        case 'completed': $statusClass = 'success'; break;
                                                        case 'cancelled': $statusClass = 'danger'; break;
                                                        case 'processing': $statusClass = 'info'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?= $statusClass ?>">
                                                        <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-info btn-sm view-pickup-details-btn"
                                                            data-pickup-id="<?= htmlspecialchars($order['pickup_id']) ?>"
                                                            data-pickup-date="<?= htmlspecialchars($order['pickup_date']) ?>"
                                                            data-pickup-location="<?= htmlspecialchars($order['pickup_location']) ?>"
                                                            data-assigned-to="<?= htmlspecialchars($order['assigned_to']) ?>"
                                                            data-driver-name="<?= htmlspecialchars(($order['driver_first_name'] && $order['driver_last_name']) ? $order['driver_first_name'].' '.$order['driver_last_name'] : '') ?>"
                                                            data-vehicle-type="<?= htmlspecialchars($order['vehicle_type'] ?? '') ?>"
                                                            data-vehicle-plate="<?= htmlspecialchars($order['vehicle_plate'] ?? '') ?>"
                                                            data-pickup-notes="<?= htmlspecialchars($order['pickup_notes']) ?>"
                                                            data-toggle="modal" data-target="#pickupDetailsModal">
                                                        <i class="bi bi-info-circle"></i> View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No recent orders found.</td>
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
                            <div class="driver-info mt-3 mb-3 p-2 border rounded bg-white">
                                <h6><i class="bi bi-person-badge"></i> Driver Information</h6>
                                <p><strong>Name:</strong> <span id="driver-name"></span></p>
                                <p><strong>Vehicle Type:</strong> <span id="vehicle-type"></span></p>
                                <p><strong>Vehicle Plate:</strong> <span id="vehicle-plate"></span></p>
                            </div>
                            <hr>
                            <p><strong><i class="bi bi-sticky"></i> Pickup Notes:</strong></p>
                            <div id="pickup-notes" class="p-2 border rounded bg-white"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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

        // Payment Summary Chart
        const paymentChartColors = ['#28a745', '#ffc107', '#dc3545', '#17a2b8'];
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($paymentSummary, 'payment_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($paymentSummary, 'total')) ?>,
                    backgroundColor: paymentChartColors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
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
        const productChartColors = ['#20c997', '#6f42c1', '#fd7e14', '#17a2b8'];
        const productCtx = document.getElementById('productChart').getContext('2d');
        const productChart = new Chart(productCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($productSummary, 'status')) ?>,
                datasets: [{
                    label: 'Products',
                    data: <?= json_encode(array_column($productSummary, 'total')) ?>,
                    backgroundColor: productChartColors,
                    borderRadius: 5,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                return `${label}: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // View Pickup Details
        $(document).ready(function () {
            $('.view-pickup-details-btn').click(function () {
                var pickupId = $(this).data('pickup-id');
                var pickupDate = $(this).data('pickup-date');
                var pickupLocation = $(this).data('pickup-location');
                var driverName = $(this).data('driver-name');
                var vehicleType = $(this).data('vehicle-type');
                var vehiclePlate = $(this).data('vehicle-plate');
                var pickupNotes = $(this).data('pickup-notes');

                $('#pickup-id').text(pickupId || 'Not assigned');
                $('#pickup-date').text(pickupDate ? new Date(pickupDate).toLocaleString() : 'Not scheduled');
                $('#pickup-location').text(pickupLocation || 'Not specified');
                
                // Driver information
                if (driverName) {
                    $('#driver-name').text(driverName);
                    $('#vehicle-type').text(vehicleType || 'Not specified');
                    $('#vehicle-plate').text(vehiclePlate || 'Not specified');
                    $('.driver-info').show();
                } else {
                    $('#driver-name').text('Not assigned');
                    $('#vehicle-type').text('N/A');
                    $('#vehicle-plate').text('N/A');
                    $('.driver-info').show(); // Still show the section but with N/A values
                }
                
                $('#pickup-notes').text(pickupNotes || 'No notes available');
            });
        });
    </script>
</body>
</html>