<?php
// File: C:\xampp\htdocs\capstone\admin\index.php

// Start session to check if the user is logged in
session_start();

// Check if the user is logged in, if not redirect to the login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../../models/Dashboard.php';

$dashboard = new Dashboard();

$userCount = $dashboard->getUserCount();
$productCount = $dashboard->getProductCount();
$orderCountPending = $dashboard->getOrderCountByStatus('pending');
$orderCountCompleted = $dashboard->getOrderCountByStatus('completed');
$orderCountCanceled = $dashboard->getOrderCountByStatus('canceled');
$pickupCountPending = $dashboard->getPickupCountByStatus('pending');
$pickupCountShipped = $dashboard->getPickupCountByStatus('shipped');
$pickupCountDelivered = $dashboard->getPickupCountByStatus('delivered');

// Handle logout functionality
if (isset($_POST['logout'])) {
    // Destroy the session to log the user out
    session_start();
    session_unset();
    session_destroy();
    header("Location: admin-login.php"); // Redirect to the login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    .dashboard-card {
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .card-icon {
      font-size: 30px;
      margin-bottom: 10px;
    }
    .dashboard-header {
      font-size: 32px;
      font-weight: bold;
    }
    .stat-card {
      text-align: center;
      padding: 30px;
      border-radius: 10px;
      background-color: #f9f9f9;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .chart-container {
      height: 400px;
      width: 100%;
      margin-top: 30px;
    }
    .row .col-md-4 {
      padding: 15px;
    }
    .chart-title {
      font-size: 20px;
      margin-top: 20px;
      font-weight: bold;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../../views/global/sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="dashboard-header">Dashboard</h1>

          <!-- Logout Button -->
          <form method="POST" action="" class="form-inline">
            <button type="submit" name="logout" class="btn btn-danger">Logout</button>
          </form>
        </div>

        <!-- Dashboard Statistics -->
        <section id="statistics">
          <div class="row">
            <!-- Users Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-person-fill text-success card-icon"></i>
                <div class="card-title">Users</div>
                <div class="card-text"><?= $userCount ?></div>
              </div>
            </div>

            <!-- Products Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-box-fill text-warning card-icon"></i>
                <div class="card-title">Products</div>
                <div class="card-text"><?= $productCount ?></div>
              </div>
            </div>

            <!-- Orders Pending Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-clock-fill text-primary card-icon"></i>
                <div class="card-title">Orders Pending</div>
                <div class="card-text"><?= $orderCountPending ?></div>
              </div>
            </div>

            <!-- Orders Completed Card -->
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-check-circle-fill text-danger card-icon"></i>
                <div class="card-title">Orders Completed</div>
                <div class="card-text"><?= $orderCountCompleted ?></div>
              </div>
            </div>
          </div>

          <!-- Orders Status Chart -->
          <div class="row">
            <div class="col-md-6 mb-4">
              <div class="stat-card">
                <div class="chart-title">Order Status</div>
                <canvas id="orderStatusChart"></canvas>
              </div>
            </div>

            <!-- Pickup Status Chart -->
            <div class="col-md-6 mb-4">
              <div class="stat-card">
                <div class="chart-title">Pickup Status</div>
                <canvas id="pickupStatusChart"></canvas>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <!-- Chart.js Configuration -->
  <script>
    // Orders Status Chart
    var orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    var orderStatusChart = new Chart(orderStatusCtx, {
      type: 'pie',
      data: {
        labels: ['Pending', 'Completed', 'Canceled'],
        datasets: [{
          label: 'Order Status',
          data: [<?= $orderCountPending ?>, <?= $orderCountCompleted ?>, <?= $orderCountCanceled ?>],
          backgroundColor: ['#007bff', '#28a745', '#dc3545'],
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          }
        }
      }
    });

    // Pickup Status Chart
    var pickupStatusCtx = document.getElementById('pickupStatusChart').getContext('2d');
    var pickupStatusChart = new Chart(pickupStatusCtx, {
      type: 'pie',
      data: {
        labels: ['Pending', 'Shipped', 'Delivered'],
        datasets: [{
          label: 'Pickup Status',
          data: [<?= $pickupCountPending ?>, <?= $pickupCountShipped ?>, <?= $pickupCountDelivered ?>],
          backgroundColor: ['#17a2b8', '#ffc107', '#28a745'],
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          }
        }
      }
    });
  </script>
</body>
</html>
