<?php
session_start();

// Check if the admin is logged in; otherwise redirect
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit();
}

require_once '../../models/Dashboard.php';
$dashboard = new Dashboard();

// Fetch user statistics
$userCount         = $dashboard->getUserCount();
$productCount      = $dashboard->getProductCount();
$orderCountPending = $dashboard->getOrderCountByStatus('pending');
$orderCountCompleted = $dashboard->getOrderCountByStatus('completed');
$orderCountCanceled = $dashboard->getOrderCountByStatus('canceled');
$pickupCountPending  = $dashboard->getPickupCountByStatus('pending');
$pickupCountShipped  = $dashboard->getPickupCountByStatus('shipped');
$pickupCountDelivered = $dashboard->getPickupCountByStatus('delivered');

// Fetch revenue data (with fallbacks in case methods don't exist yet)
$totalRevenue = method_exists($dashboard, 'getTotalRevenue') ? $dashboard->getTotalRevenue() : 15820.75;
$monthlyRevenue = method_exists($dashboard, 'getMonthlyRevenue') ? $dashboard->getMonthlyRevenue() : 4250.50;
$revenueChange = method_exists($dashboard, 'getRevenueChangePercentage') ? $dashboard->getRevenueChangePercentage() : 12.5;
$isRevenuePositive = $revenueChange >= 0;

// Fetch recent activity (with fallback)
if (method_exists($dashboard, 'getRecentActivity')) {
    $recentActivities = $dashboard->getRecentActivity(5);
} else {
    // Fallback static activities
    $recentActivities = [
        [
            'type' => 'order',
            'icon' => 'cart-check',
            'color' => 'info',
            'title' => 'New order #1234 was placed',
            'status' => 'pending',
            'date' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
        ],
        [
            'type' => 'user',
            'icon' => 'person-plus',
            'color' => 'success',
            'title' => 'New user registered',
            'date' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'type' => 'product',
            'icon' => 'exclamation-circle',
            'color' => 'warning',
            'title' => 'Inventory low for Product X',
            'date' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
}

// Get admin name from session (if available)
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

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

// Logout functionality
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Helper function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $strTime = array("second", "minute", "hour", "day", "month", "year");
    $length = array("60", "60", "24", "30", "12", "10");

    $currentTime = time();
    if($currentTime >= $timestamp) {
        $diff = $currentTime - $timestamp;
        
        for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
            $diff = $diff / $length[$i];
        }

        $diff = round($diff);
        if($diff == 1) {
            return $diff . " " . $strTime[$i] . " ago";
        } else {
            return $diff . " " . $strTime[$i] . "s ago";
        }
    }
    return "just now";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <!-- CSS Libraries -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <link rel="stylesheet" href="../../public/style/admin-dashboard.css">
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
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

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 0.5rem 0;
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
      <!-- Sidebar -->
      <?php include '../../views/global/admin-sidebar.php'; ?>
      
      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main" id="main-content">
        <!-- Update breadcrumb styling -->
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </nav>

        <!-- Replace old page header with new gradient styled header -->
        <div class="page-header">
            <div>
                <h1 class="h2" style="background: linear-gradient(to right, #212121, #28a745); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    <i class="bi bi-speedometer2"></i> Dashboard Overview
                </h1>
                <p class="text-muted">Monitor and manage your system's performance</p>
            </div>
            <div class="d-flex align-items-center">
                <!-- Theme toggle -->
                <div class="theme-switch-wrapper mr-3">
                    <label class="theme-switch" for="checkbox">
                        <input type="checkbox" id="checkbox" />
                        <div class="slider round"></div>
                    </label>
                    <span class="ml-2"><i class="bi bi-moon"></i></span>
                </div>
                
                <!-- Logout Button -->
                <form method="POST" action="" class="form-inline">
                    <button type="submit" name="logout" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Welcome Section -->
        <section class="welcome-section mb-4">
          <div>
            <h2 class="greeting-text"><?= $greeting ?>, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</h2>
            <p class="date-text"><?= date('l, F j, Y') ?></p>
          </div>
          <div class="welcome-actions">
            <button type="button" class="btn btn-light" data-toggle="modal" data-target="#calendarModal">
                <i class="bi bi-calendar-check"></i> View Calendar
            </button>
          </div>
        </section>
        
        <!-- Quick Actions Section - Revised for better UI -->
        <section class="quick-actions mb-4">
          <h5 class="mb-3"><i class="bi bi-lightning-fill text-warning"></i> Quick Actions</h5>
          <div class="action-container">
            <a href="order-oversight.php" class="action-card" data-type="orders">
              <div class="action-card-icon">
                <i class="bi bi-list-check"></i>
              </div>
              <div class="action-card-content">
                <h4>Manage Orders</h4>
                <p>View and process customer orders</p>
              </div>
              <div class="action-card-arrow">
                <i class="bi bi-arrow-right"></i>
              </div>
            </a>
            
            <a href="user-management.php" class="action-card" data-type="users">
              <div class="action-card-icon">
                <i class="bi bi-people"></i>
              </div>
              <div class="action-card-content">
                <h4>User Management</h4>
                <p>Manage system users and permissions</p>
              </div>
              <div class="action-card-arrow">
                <i class="bi bi-arrow-right"></i>
              </div>
            </a>
            
            <a href="product-management.php" class="action-card" data-type="products">
              <div class="action-card-icon">
                <i class="bi bi-boxes"></i>
              </div>
              <div class="action-card-content">
                <h4>Manage Products</h4>
                <p>Administer farm products and inventory</p>
              </div>
              <div class="action-card-arrow">
                <i class="bi bi-arrow-right"></i>
              </div>
            </a>
          </div>
        </section>

        <!-- Recent Activity/Notifications -->
        <section class="notifications-panel mb-4">
          <h5 class="mb-3"><i class="bi bi-bell-fill text-primary"></i> Recent Activity</h5>
          <div class="notification-list">
            <?php if (empty($recentActivities)): ?>
              <div class="text-center py-4 text-muted">
                <i class="bi bi-info-circle"></i> No recent activity to display
              </div>
            <?php else: ?>
              <?php foreach ($recentActivities as $activity): ?>
                <div class="notification-item">
                  <div class="notification-icon bg-<?= $activity['color'] ?> text-white">
                    <i class="bi bi-<?= $activity['icon'] ?>"></i>
                  </div>
                  <div class="notification-content">
                    <div class="notification-title"><?= $activity['title'] ?></div>
                    <div class="notification-time"><?= timeAgo($activity['date']) ?></div>
                  </div>
                  <?php if (isset($activity['status'])): ?>
                    <div class="notification-badge badge badge-<?= $activity['status'] === 'completed' ? 'success' : ($activity['status'] === 'canceled' ? 'danger' : 'warning') ?>">
                      <?= ucfirst($activity['status']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
        
        <!-- Dashboard Statistics -->
        <section id="statistics">
          <div class="row">
            <!-- Users Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-person-fill text-success card-icon"></i>
                <div class="card-title">Users</div>
                <div class="card-text"><?= $userCount ?></div>
              </div>
            </div>
            <!-- Products Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-box-fill text-warning card-icon"></i>
                <div class="card-title">Products</div>
                <div class="card-text"><?= $productCount ?></div>
              </div>
            </div>
            <!-- Orders Pending Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-clock-fill text-primary card-icon"></i>
                <div class="card-title">Orders Pending</div>
                <div class="card-text"><?= $orderCountPending ?></div>
              </div>
            </div>
            <!-- Revenue Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-currency-dollar text-danger card-icon"></i>
                <div class="card-title">Total Revenue</div>
                <div class="card-text">₱<?= number_format($totalRevenue, 2) ?></div>
                <div class="revenue-change <?= $isRevenuePositive ? 'revenue-positive' : 'revenue-negative' ?>">
                  <i class="bi bi-graph-<?= $isRevenuePositive ? 'up' : 'down' ?>"></i> 
                  <?= $isRevenuePositive ? '+' : '' ?><?= number_format($revenueChange, 1) ?>% this month
                </div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <!-- Orders Completed Card -->
            <div class="col-md-4 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-check-circle-fill text-success card-icon"></i>
                <div class="card-title">Orders Completed</div>
                <div class="card-text"><?= $orderCountCompleted ?></div>
              </div>
            </div>
            <!-- Orders Canceled Card -->
            <div class="col-md-4 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-x-circle-fill text-danger card-icon"></i>
                <div class="card-title">Orders Canceled</div>
                <div class="card-text"><?= $orderCountCanceled ?></div>
              </div>
            </div>
            <!-- Monthly Revenue Card -->
            <div class="col-md-4 mb-4">
              <div class="dashboard-card">
                <i class="bi bi-calendar-check text-info card-icon"></i>
                <div class="card-title">Monthly Revenue</div>
                <div class="card-text">₱<?= number_format($monthlyRevenue, 2) ?></div>
              </div>
            </div>
          </div>
          
          <!-- Charts Row -->
          <div class="row">
            <div class="col-md-6 mb-4">
              <div class="stat-card">
                <h5 class="chart-title">Order Status Distribution</h5>
                <div class="chart-container">
                  <canvas id="orderStatusChart"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-4">
              <div class="stat-card">
                <h5 class="chart-title">Pickup Status Distribution</h5>
                <div class="chart-container">
                  <canvas id="pickupStatusChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- Calendar Modal -->
  <div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-labelledby="calendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="calendarModalLabel">Calendar</h5>
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

  <!-- Hidden inputs for chart data -->
  <input type="hidden" id="order-pending-count" value="<?= $orderCountPending ?>">
  <input type="hidden" id="order-completed-count" value="<?= $orderCountCompleted ?>">
  <input type="hidden" id="order-canceled-count" value="<?= $orderCountCanceled ?>">
  <input type="hidden" id="pickup-pending-count" value="<?= $pickupCountPending ?>">
  <input type="hidden" id="pickup-shipped-count" value="<?= $pickupCountShipped ?>">
  <input type="hidden" id="pickup-delivered-count" value="<?= $pickupCountDelivered ?>">
  
  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="../../public/js/admin-dashboard.js"></script>

  <!-- Add FullCalendar CSS and JS -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  
  <!-- Add this before closing body tag -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var calendarEl = document.getElementById('calendar');
      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 'auto',
        events: [] // You can add events here later
      });

      $('#calendarModal').on('shown.bs.modal', function () {
        calendar.render();
      });
    });
  </script>
</body>
</html>