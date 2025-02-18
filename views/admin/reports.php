<?php
// Start the session to track login status
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
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

// Fetch Recent Orders
$recentOrdersQuery = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, o.pickup_details
                      FROM orders AS o
                      JOIN users AS u ON o.consumer_id = u.user_id
                      ORDER BY o.order_date DESC
                      LIMIT 10";
$recentOrdersStmt = $conn->query($recentOrdersQuery);
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Reports Overview</h1>
                    <!-- Logout Button -->
                    <form method="POST" class="ml-3">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Report Summary -->
                <div class="row mb-4">
                    <!-- Order Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-cart me-2"></i>Order Summary
                                </h5>
                                <canvas id="orderChart" width="100" height="100"></canvas>
                                <ul class="list-group mt-3">
                                    <?php foreach ($orderSummary as $order): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($order['order_status']) ?>
                                            <span class="badge badge-primary badge-pill"><?= $order['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-credit-card me-2"></i>Payment Summary
                                </h5>
                                <canvas id="paymentChart" width="100" height="100"></canvas>
                                <ul class="list-group mt-3">
                                    <?php foreach ($paymentSummary as $payment): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($payment['payment_status']) ?>
                                            <span class="badge badge-success badge-pill"><?= $payment['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Product Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title d-flex align-items-center">
                                    <i class="bi bi-box me-2"></i>Product Summary
                                </h5>
                                <canvas id="productChart" width="100" height="100"></canvas>
                                <ul class="list-group mt-3">
                                    <?php foreach ($productSummary as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($product['status']) ?>
                                            <span class="badge badge-warning badge-pill"><?= $product['total'] ?></span>
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
                            <i class="bi bi-clock-history me-2"></i>Recent Orders
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Consumer</th>
                                        <th>Status</th>
                                        <th>Order Date</th>
                                        <th>Pickup Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentOrders) > 0): ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                                <td><?= htmlspecialchars($order['consumer_name']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $order['order_status'] === 'completed' ? 'success' : 'warning' ?>">
                                                        <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($order['order_date']))) ?></td>
                                                <td><?= htmlspecialchars($order['pickup_details'] ?? 'N/A') ?></td>
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

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

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
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545'],
                }]
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
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                }]
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
                    backgroundColor: '#ffc107',
                }]
            }
        });
    </script>
</body>
</html>
