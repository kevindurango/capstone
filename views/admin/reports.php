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
$recentOrdersQuery = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, p.pickup_id, p.pickup_date, p.pickup_location, p.assigned_to, p.pickup_notes
                      FROM orders AS o
                      JOIN users AS u ON o.consumer_id = u.user_id
                      LEFT JOIN pickups AS p ON o.order_id = p.order_id
                      ORDER BY o.order_date DESC
                      LIMIT 10";
$recentOrdersStmt = $conn->query($recentOrdersQuery);
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total Orders
$totalOrdersQuery = "SELECT COUNT(*) as total FROM orders";
$totalOrdersStmt = $conn->query($totalOrdersQuery);
$totalOrders = $totalOrdersStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate Total Revenue - Improved to work with schema
// Based on the database schema, there's no direct revenue column in payments
// Let's calculate it from orderitems table instead
try {
    $totalRevenueQuery = "SELECT SUM(oi.price * oi.quantity) as total 
                         FROM orderitems oi
                         JOIN orders o ON oi.order_id = o.order_id
                         WHERE o.order_status = 'completed'";
    $totalRevenueStmt = $conn->query($totalRevenueQuery);
    $totalRevenue = $totalRevenueStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    $totalRevenue = 0;
    error_log("Error in revenue calculation: " . $e->getMessage());
}

// Calculate Total Products
$totalProductsQuery = "SELECT COUNT(*) as total FROM products";
$totalProductsStmt = $conn->query($totalProductsQuery);
$totalProducts = $totalProductsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate Average Order Value
$avgOrderValueQuery = "SELECT AVG(oi.price * oi.quantity) as avg_value 
                      FROM orderitems oi
                      GROUP BY oi.order_id";
try {
    $avgOrderValueStmt = $conn->query($avgOrderValueQuery);
    $avgOrderValue = $avgOrderValueStmt->fetch(PDO::FETCH_ASSOC)['avg_value'] ?? 0;
} catch (PDOException $e) {
    $avgOrderValue = 0;
    error_log("Error in average order calculation: " . $e->getMessage());
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
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/reports.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- PDF Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* Add admin header styling */
        .admin-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 10px 0;
        }
        .admin-badge {
            background-color: #6a11cb;
            color: white;
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 10px;
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
                    <h1 class="h2 text-success">Reports Overview</h1>
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
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start-date">Start Date</label>
                                <input type="date" class="form-control" id="start-date">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end-date">End Date</label>
                                <input type="date" class="form-control" id="end-date">
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
                            <div class="label">Avg. Order Value</div>
                        </div>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="row mb-4">
                    <!-- Order Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-cart me-2"></i> Order Summary
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="orderChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($orderSummary as $order): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($order['order_status']) ?>
                                            <span class="status-badge status-<?= $order['order_status'] ?>"><?= $order['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-credit-card me-2"></i> Payment Summary
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="paymentChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($paymentSummary as $payment): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($payment['payment_status']) ?>
                                            <span class="status-badge status-<?= $payment['payment_status'] ?>"><?= $payment['total'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Product Summary -->
                    <div class="col-md-4 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="bi bi-box me-2"></i> Product Summary
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="productChart"></canvas>
                                </div>
                                <ul class="list-group mt-3">
                                    <?php foreach ($productSummary as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($product['status']) ?>
                                            <span class="status-badge status-<?= $product['status'] ?>"><?= $product['total'] ?></span>
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
                                                            data-assigned-to="<?= htmlspecialchars($order['assigned_to'] ?? 'N/A') ?>"
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
                    <p><strong>Assigned To:</strong> <span id="assigned-to"></span></p>
                    <p><strong>Pickup Notes:</strong> <span id="pickup-notes"></span></p>
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
        // Order Summary Chart
        const orderCtx = document.getElementById('orderChart').getContext('2d');
        const orderChart = new Chart(orderCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($orderSummary, 'order_status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($orderSummary, 'total')) ?>,
                    backgroundColor: ['#007bff', '#28a745', '#dc3545'],
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
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
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
                    backgroundColor: ['#007bff', '#28a745', '#ffc107'],
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
                }
            }
        });

        // View Pickup Details
        $(document).ready(function () {
            $('.view-pickup-details-btn').click(function () {
                var pickupId = $(this).data('pickup-id');
                var pickupDate = $(this).data('pickup-date');
                var pickupLocation = $(this).data('pickup-location');
                var assignedTo = $(this).data('assigned-to');
                var pickupNotes = $(this).data('pickup-notes');

                $('#pickup-id').text(pickupId);
                $('#pickup-date').text(pickupDate);
                $('#pickup-location').text(pickupLocation);
                $('#assigned-to').text(assignedTo);
                $('#pickup-notes').text(pickupNotes);
            });
        });

        // PDF Export function - now functional
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
            
            // Add title
            doc.setFontSize(18);
            doc.setTextColor(40, 167, 69); // Green color
            doc.text('Farmers Market Reports', 105, 15, { align: 'center' });
            
            // Add date
            doc.setFontSize(10);
            doc.setTextColor(100);
            const today = new Date();
            const dateStr = today.toLocaleDateString('en-PH', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            doc.text(`Generated on: ${dateStr}`, 105, 22, { align: 'center' });
            
            // Define the sections to capture
            const sectionsToCapture = [
                { selector: '.row:nth-child(3)', title: 'Summary Statistics', y: 30 },
                { selector: '.row.mb-4', title: 'Detailed Reports', y: 90 },
                { selector: '.card.shadow-sm', title: 'Recent Orders', y: 180 }
            ];
            
            // Function to process each section
            const captureSection = (index) => {
                if (index >= sectionsToCapture.length) {
                    // All sections processed, save the PDF
                    const filename = `FarmersMarketReport_${today.getFullYear()}${(today.getMonth()+1).toString().padStart(2,'0')}${today.getDate().toString().padStart(2,'0')}.pdf`;
                    doc.save(filename);
                    
                    // Remove loading indicator
                    document.body.removeChild(loadingDiv);
                    return;
                }
                
                const section = sectionsToCapture[index];
                const element = document.querySelector(section.selector);
                
                if (element) {
                    // Add section title
                    doc.setFontSize(14);
                    doc.setTextColor(0);
                    doc.text(section.title, 14, section.y - 5);
                    
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

        // Date filter function placeholder
        function applyDateFilter() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be after end date');
                return;
            }
            
            // In a real implementation, you would refresh the page with date parameters
            // or make an AJAX call to get filtered data
            alert(`Filter applied from ${startDate} to ${endDate}`);
        }
    </script>
</body>
</html>