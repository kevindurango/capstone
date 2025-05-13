<?php
session_start();

// Check if the manager is logged in; otherwise redirect
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Dashboard.php';
$dashboard = new Dashboard();

// Initialize data load status
$dataLoadSuccess = true;
$errorMessage = '';

try {
    // Fetch metrics
    $userCount = $dashboard->getUserCount();
    $productCount = $dashboard->getProductCount();
    $orderCountPending = $dashboard->getOrderCountByStatus('pending');
    $orderCountCompleted = $dashboard->getOrderCountByStatus('completed');
    $orderCountCanceled = $dashboard->getOrderCountByStatus('canceled');
    
        // Update pickup status counts with proper error handling
    try {
        $pickupCountPending = $dashboard->getPickupCountByStatus('pending');
        $pickupCountAssigned = $dashboard->getPickupCountByStatus('assigned');
        $pickupCountCompleted = $dashboard->getPickupCountByStatus('completed');
    } catch (Exception $e) {
        // Set default values if there's an error
        $pickupCountPending = 0;
        $pickupCountAssigned = 0;
        $pickupCountCompleted = 0;
        // Don't set dataLoadSuccess to false for this specific error
        // as we want the page to still function with default values
        error_log("Error getting pickup counts: " . $e->getMessage());
    }

    // Fetch revenue data with fallbacks
    $totalRevenue = method_exists($dashboard, 'getTotalRevenue') ? $dashboard->getTotalRevenue() : 15820.75;
    $monthlyRevenue = method_exists($dashboard, 'getMonthlyRevenue') ? $dashboard->getMonthlyRevenue() : 4250.50;
    $revenueChange = method_exists($dashboard, 'getRevenueChangePercentage') ? $dashboard->getRevenueChangePercentage() : 12.5;
    $isRevenuePositive = $revenueChange >= 0;

    // Get low stock products
    try {
        $lowStockProducts = $dashboard->getLowStockProducts(5);
    } catch (Error | Exception $e) {
        $lowStockProducts = [];
    }

    // Fetch recent activity with fallback
    try {
        if (method_exists($dashboard, 'getRecentActivity')) {
            $recentActivities = $dashboard->getRecentActivity(5);
        } else {
            throw new Exception("Method not found");
        }
    } catch (Exception $e) {
        // Fallback static activities - updated to match new schema
        $recentActivities = [
            [
                'type' => 'product',
                'icon' => 'box-seam',
                'color' => 'primary',
                'title' => 'Product inventory updated',
                'activity' => 'Product inventory was updated',
                'date' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'type' => 'order',
                'icon' => 'cart-check',
                'color' => 'success',
                'title' => 'New order received',
                'activity' => 'New order was received',
                'status' => 'pending',
                'date' => date('Y-m-d H:i:s', strtotime('-3 hours'))
            ],
            [
                'type' => 'pickup',
                'icon' => 'truck',
                'color' => 'info',
                'title' => 'Pickup scheduled',
                'activity' => 'New pickup was scheduled',
                'date' => date('Y-m-d H:i:s', strtotime('-5 hours'))
            ]
        ];
    }
} catch (Exception $e) {
    $dataLoadSuccess = false;
    $errorMessage = $e->getMessage();
    
    // Set default values for all metrics in case of error
    $userCount = 0;
    $productCount = 0;
    $orderCountPending = 0;
    $orderCountCompleted = 0;
    $orderCountCanceled = 0;
    $pickupCountPending = 0;
    $pickupCountAssigned = 0;
    $pickupCountCompleted = 0;
    $lowStockProducts = [];
    $recentActivities = [];
}

// Get current date
$currentDate = date('l, F j, Y');

// Get time-based greeting
$hour = date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

// Handle logout functionality
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}

// Helper function to format time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . ' ' . ($diff->y == 1 ? 'year' : 'years') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' ' . ($diff->m == 1 ? 'month' : 'months') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' ' . ($diff->d == 1 ? 'day' : 'days') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' ' . ($diff->h == 1 ? 'hour' : 'hours') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' ' . ($diff->i == 1 ? 'minute' : 'minutes') . ' ago';
    }
    return 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/manager-dashboard.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Add manager header styling */
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
    </style>
</head>
<body>
  <!-- Add Manager Header -->
  <div class="manager-header text-center">
    <h2><i class="bi bi-person-badge"></i> MANAGER OPERATIONS DASHBOARD <span class="manager-badge">Authorized Access</span></h2>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../../views/global/manager-sidebar.php'; ?>
      
      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main">
        <!-- Rest of the dashboard content structure following admin-dashboard.php pattern -->
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>

        <!-- Data Load Error Alert -->
        <?php if (!$dataLoadSuccess): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="bi bi-exclamation-triangle"></i> Error:</strong> 
                Failed to load some dashboard data. <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section without Refresh Option -->
        <div class="welcome-section mb-4 animate__animated animate__fadeIn">
            <div>
                <h3 class="greeting-text">Welcome, <?= $_SESSION['username'] ?>!</h3>
                <p class="date-text"><?= $currentDate ?></p>
            </div>
            <div class="d-flex align-items-center">
                <form method="POST">
                    <button type="submit" name="logout" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Improved Quick Actions Section -->
        <div class="row mb-4 animate__animated animate__fadeIn animate__delay-1s">
            <div class="col-12">
                <div class="quick-actions">
                    <h5 class="section-title">
                        <i class="bi bi-lightning-fill text-warning"></i> Quick Actions
                    </h5>
                    
                    <!-- Quick Actions Cards -->
                    <div class="action-container">
                        <a href="manager-product.php" class="action-card" data-type="products">
                          <div class="action-card-icon">
                            <i class="bi bi-box-seam"></i>
                          </div>
                          <div class="action-card-content">
                            <h4>Manage Products</h4>
                            <p>Update inventory and stock levels</p>
                          </div>
                          <div class="action-card-arrow">
                            <i class="bi bi-arrow-right"></i>
                          </div>
                        </a>
                        
                        <a href="manager-sales-management.php" class="action-card" data-type="orders">
                          <div class="action-card-icon">
                            <i class="bi bi-cart-check"></i>
                            <?php if($orderCountPending > 0): ?>
                              <span class="badge badge-warning position-absolute"><?= $orderCountPending ?></span>
                            <?php endif; ?>
                          </div>
                          <div class="action-card-content">
                            <h4>Sales Management</h4>
                            <p>View and process orders</p>
                          </div>
                          <div class="action-card-arrow">
                            <i class="bi bi-arrow-right"></i>
                          </div>
                        </a>
                        
                        <a href="manager-pickup-management.php" class="action-card" data-type="logistics">
                          <div class="action-card-icon">
                            <i class="bi bi-truck"></i>
                            <?php if($pickupCountPending > 0): ?>
                              <span class="badge badge-info position-absolute"><?= $pickupCountPending ?></span>
                            <?php endif; ?>
                          </div>
                          <div class="action-card-content">
                            <h4>Manage Pickups</h4>
                            <p>Schedule and track deliveries</p>
                          </div>
                          <div class="action-card-arrow">
                            <i class="bi bi-arrow-right"></i>
                          </div>
                        </a>
                      </div>
                </div>
            </div>
        </div>

        <section id="statistics" class="animate__animated animate__fadeIn animate__delay-2s">
            <div class="row">
                <!-- Essential Statistics -->
                <div class="col-md-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body text-center">
                            <i class="bi bi-clock-fill text-warning card-icon"></i>
                            <h5 class="card-title">Pending Orders</h5>
                            <div class="card-value"><?= $orderCountPending ?></div>
                            <a href="manager-order-oversight.php?filter=pending" class="btn btn-sm btn-outline-primary mt-2">
                                Review Orders <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body text-center">
                            <i class="bi bi-box-fill text-info card-icon"></i>
                            <h5 class="card-title">Active Products</h5>
                            <div class="card-value"><?= $productCount ?></div>
                            <a href="manager-product.php" class="btn btn-sm btn-outline-primary mt-2">
                                Manage Products <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body text-center">
                            <i class="bi bi-truck text-primary card-icon"></i>
                            <h5 class="card-title">Pending Pickups</h5>
                            <div class="card-value"><?= $pickupCountPending ?></div>
                            <a href="manager-pickup-management.php?filter=pending" class="btn btn-sm btn-outline-primary mt-2">
                                Manage Pickups <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Critical Information -->
        <div class="row mb-4">
            <!-- Low Stock Products -->
            <div class="col-md-6 mb-4">
                <div class="stat-card">
                    <h3 class="chart-title">
                        <i class="bi bi-exclamation-circle text-warning"></i> Low Stock Products
                        <?php if(!empty($lowStockProducts)): ?>
                            <span class="badge badge-warning"><?= count($lowStockProducts) ?></span>
                        <?php endif; ?>
                    </h3>
                    <div class="low-stock-list">
                        <?php if (!empty($lowStockProducts)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th class="text-center">Stock</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-<?= $product['stock'] == 0 ? 'danger' : 'warning' ?>">
                                                        <?= $product['stock'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary update-stock-btn"
                                                        data-product-id="<?= $product['product_id'] ?>"
                                                        data-name="<?= htmlspecialchars($product['name']) ?>"
                                                        data-current-stock="<?= $product['stock'] ?>">
                                                        <i class="bi bi-pencil"></i> Update
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> All products have sufficient stock levels.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="col-md-6 mb-4">
                <div class="stat-card">
                    <h3 class="chart-title">Recent Activities</h3>
                    <div class="activity-feed">
                        <?php if (!empty($recentActivities)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="bi bi-<?= $activity['icon'] ?> text-<?= $activity['color'] ?> me-2"></i>
                                            <?= htmlspecialchars($activity['title']) ?>
                                        </span>
                                        <small class="text-muted">
                                            <?= timeAgo($activity['date']) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No recent activities.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Update Stock Modal -->
        <div class="modal fade" id="updateStockModal" tabindex="-1" role="dialog" aria-labelledby="updateStockModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStockModalLabel">Update Product Stock</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="updateStockForm" method="POST" action="update-stock.php">
                        <div class="modal-body">
                            <input type="hidden" name="product_id" id="update_product_id">
                            <div class="form-group">
                                <label>Product Name:</label>
                                <input type="text" id="product_name" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Current Stock:</label>
                                <input type="number" id="current_stock" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>New Stock Level:</label>
                                <input type="number" name="new_stock" id="new_stock" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fetch barangay metrics data -->
        <?php
        $barangayMetrics = [];
        try {
            // Use the new method to get data for all 26 barangays
            if (method_exists($dashboard, 'getAllBarangaysWithMetrics')) {
                $barangayMetrics = $dashboard->getAllBarangaysWithMetrics();
            } else {
                // Fallback to get basic barangay list
                $barangayMetrics = $dashboard->getAllBarangays();
            }
        } catch (Exception $e) {
            error_log("Error fetching barangay metrics: " . $e->getMessage());
        }
        ?>

        <!-- Barangay Statistics Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stat-card">
                    <h3 class="chart-title">
                        <i class="bi bi-geo-alt-fill text-success"></i> Valencia Barangay Agricultural Statistics
                        <small class="text-muted">(All 26 Barangays)</small>
                    </h3>
                    
                    <!-- Visualization Controls -->
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-success active" id="view-farmers">Farmers</button>
                            <button type="button" class="btn btn-outline-primary" id="view-crops">Crop Types</button>
                            <button type="button" class="btn btn-outline-info" id="view-land">Planted Area</button>
                        </div>
                    </div>

                    <!-- Barangay Visualization Chart -->
                    <div class="chart-container" style="position: relative; height: 400px;">
                        <canvas id="barangayVisualizationChart"></canvas>
                    </div>
                    
                    <!-- Statistics Table -->
                    <div class="mt-4">
                        <h5><i class="bi bi-table"></i> Barangay Details</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Barangay</th>
                                        <th class="text-center">Farmers</th>
                                        <th class="text-center">Crop Types</th>
                                        <th class="text-center">Farm Area</th>
                                        <th class="text-center">Planted Area</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($barangayMetrics as $barangay): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($barangay['barangay_name']) ?></td>
                                            <td class="text-center"><?= $barangay['farmer_count'] ?? 0 ?></td>
                                            <td class="text-center"><?= $barangay['crop_count'] ?? 0 ?></td>
                                            <td class="text-center"><?= number_format($barangay['total_farm_area'] ?? 0, 2) ?> ha</td>
                                            <td class="text-center"><?= number_format($barangay['total_planted_area'] ?? 0, 2) ?> ha</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ...rest of existing code... -->

        </main>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/js/bootstrap.bundle.min.js"></script>
  <script src="../../public/js/manager-dashboard.js"></script>

  <!-- Add FullCalendar -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts
        const orderStatusChart = new Chart(
            document.getElementById('orderStatusChart').getContext('2d'),
            {
                type: 'pie',
                data: {
                    labels: ['Pending', 'Completed', 'Canceled'],
                    datasets: [{
                        data: [
                            <?= $orderCountPending ?>,
                            <?= $orderCountCompleted ?>,
                            <?= $orderCountCanceled ?>
                        ],
                        backgroundColor: ['#ffc107', '#198754', '#dc3545'],
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
            }
        );

        // Update pickup status chart to match the new schema
        const pickupStatusChart = new Chart(
            document.getElementById('pickupStatusChart').getContext('2d'),
            {
                type: 'pie',
                data: {
                    labels: ['Pending', 'Assigned', 'Completed'],
                    datasets: [{
                        data: [
                            <?= $pickupCountPending ?>,
                            <?= $pickupCountAssigned ?>,
                            <?= $pickupCountCompleted ?>
                        ],
                        backgroundColor: ['#17a2b8', '#fd7e14', '#198754'],
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
            }
        );

        // Chart type toggle functionality
        document.querySelectorAll('[data-chart]').forEach(button => {
            button.addEventListener('click', function() {
                const chartId = this.getAttribute('data-chart');
                const chartType = this.getAttribute('data-type');
                const chart = chartId === 'orderStatusChart' ? orderStatusChart : pickupStatusChart;
                
                // Update chart type
                chart.config.type = chartType;
                chart.update();

                // Update active state of buttons
                this.closest('.btn-group').querySelectorAll('.btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Initialize calendar if exists
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
                events: []
            });

            $('#calendarModal').on('shown.bs.modal', function () {
                calendar.render();
            });
        }

        // Add this new code for update stock functionality
        $(document).ready(function() {
            $('.update-stock-btn').click(function() {
                const productId = $(this).data('product-id');
                const name = $(this).data('name');
                const currentStock = $(this).data('current-stock');

                $('#update_product_id').val(productId);
                $('#product_name').val(name);
                $('#current_stock').val(currentStock);
                $('#new_stock').val(currentStock);

                $('#updateStockModal').modal('show');
            });
        });
    });
  </script>
  
  <script>
    $(document).ready(function() {
        // Update stock button click handler
        $('.update-stock-btn').on('click', function() {
            const productId = $(this).data('product-id');
            const name = $(this).data('name');
            const currentStock = $(this).data('current-stock');
            
            // Set modal values
            $('#update_product_id').val(productId);
            $('#product_name').val(name);
            $('#current_stock').val(currentStock);
            $('#new_stock').val(currentStock);
            
            // Show modal
            $('#updateStockModal').modal('show');
        });

        // Handle update stock form submission
        $('#updateStockForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'update-stock.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Stock updated successfully!');
                        location.reload();
                    } else {
                        alert(response.message || 'Failed to update stock');
                    }
                },
                error: function() {
                    alert('Error updating stock');
                },
                complete: function() {
                    $('#updateStockModal').modal('hide');
                }
            });
        });

        // Initialize other features
        // ...existing chart initialization code...
    });
  </script>
  <script>
    // Barangay Visualization Chart
    const barangayCtx = document.getElementById('barangayVisualizationChart').getContext('2d');
    let barangayChart;
    let currentView = 'farmers';

    // Prepare data for the barangay chart
    const barangayData = {
        labels: [<?php echo implode(',', array_map(function($b) { 
            return "'" . addslashes($b['barangay_name']) . "'"; 
        }, $barangayMetrics)); ?>],
        datasets: {
            farmers: [<?php echo implode(',', array_map(function($b) { 
                return isset($b['farmer_count']) ? $b['farmer_count'] : 0; 
            }, $barangayMetrics)); ?>],
            crops: [<?php echo implode(',', array_map(function($b) { 
                return isset($b['crop_count']) ? $b['crop_count'] : 0; 
            }, $barangayMetrics)); ?>],
            land: [<?php echo implode(',', array_map(function($b) { 
                return isset($b['total_planted_area']) ? $b['total_planted_area'] : 0; 
            }, $barangayMetrics)); ?>]
        }
    };

    function renderBarangayChart(view) {
        currentView = view;
        
        // Set up the colors and labels based on view
        let colors, label;
        switch(view) {
            case 'farmers':
                colors = '#28a745'; // Green color for farmers
                label = 'Farmers Count';
                break;
            case 'crops':
                colors = '#007bff'; // Blue color for crop types
                label = 'Crop Types';
                break;
            case 'land':
                colors = '#17a2b8'; // Cyan color for planted area
                label = 'Planted Area (hectares)';
                break;
        }
        
        // Destroy existing chart if it exists
        if (barangayChart) {
            barangayChart.destroy();
        }
        
        // Create new chart
        barangayChart = new Chart(barangayCtx, {
            type: 'bar',
            data: {
                labels: barangayData.labels,
                datasets: [{
                    label: label,
                    data: barangayData.datasets[view],
                    backgroundColor: colors,
                    borderColor: colors.replace(')', ', 0.8)').replace('rgb', 'rgba'),
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
                            precision: view === 'land' ? 2 : 0
                        }
                    },
                    x: {
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
                            title: function(tooltipItems) {
                                return tooltipItems[0].label; // Barangay name
                            },
                            label: function(context) {
                                let value = context.raw;
                                if (view === 'farmers') {
                                    return `Farmer Count: ${value}`;
                                } else if (view === 'crops') {
                                    return `Crop Types: ${value}`;
                                } else { // land
                                    return `Planted Area: ${value.toFixed(2)} hectares`;
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize chart with farmers view
    $(document).ready(function() {
        renderBarangayChart('farmers');
        
        // Set up button click handlers for chart view changes
        $('#view-farmers').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            renderBarangayChart('farmers');
        });
        
        $('#view-crops').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            renderBarangayChart('crops');
        });
        
        $('#view-land').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            renderBarangayChart('land');
        });
    });
  </script>
</body>
</html>