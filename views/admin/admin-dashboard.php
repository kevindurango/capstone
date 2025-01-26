<?php
// File: C:\xampp\htdocs\capstone\admin\index.php
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
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
        <div class="logo-container">
          <img src="../../public/assets/logo.png" alt="Farmers Market Logo" class="logo">
        </div>
        <h4 class="text-white text-center mb-4">Admin Dashboard</h4>
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link active" href="#"><i class="bi bi-house-fill"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="user-management.php"><i class="bi bi-people-fill"></i> User Management</a></li>
          <li class="nav-item"><a class="nav-link" href="activity-logs.php"><i class="bi bi-clock-fill"></i> Activity Logs</a></li>
          <li class="nav-item"><a class="nav-link" href="product-management.php"><i class="bi bi-box-fill"></i> Product Management</a></li>
          <li class="nav-item"><a class="nav-link" href="order-oversight.php"><i class="bi bi-list-check"></i> Order Oversight</a></li>
          <li class="nav-item"><a class="nav-link" href="pickup-management.php"><i class="bi bi-truck"></i> Pick-Up Management</a></li>
          <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-bar-chart-fill"></i> Reports</a></li>
        </ul>
      </nav>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="dashboard-header">Dashboard</h1>
        </div>

        <!-- Dashboard Statistics -->
        <section id="statistics">
          <div class="row">
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-person-fill text-success card-icon"></i>
                <div class="card-title">Users</div>
                <div class="card-text"><?= $userCount ?></div>
              </div>
            </div>
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-box-fill text-warning card-icon"></i>
                <div class="card-title">Products</div>
                <div class="card-text"><?= $productCount ?></div>
              </div>
            </div>
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-clock-fill text-primary card-icon"></i>
                <div class="card-title">Orders Pending</div>
                <div class="card-text"><?= $orderCountPending ?></div>
              </div>
            </div>
            <div class="col-md-3 mb-4">
              <div class="dashboard-card p-3 text-center">
                <i class="bi bi-check-circle-fill text-danger card-icon"></i>
                <div class="card-title">Orders Completed</div>
                <div class="card-text"><?= $orderCountCompleted ?></div>
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
</body>
</html>
