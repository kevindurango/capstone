<?php
require_once '../../models/Farmer.php';
session_start();

// Sample farmer ID for now
$farmer_id = 1;

$farmer = new Farmer();
$productCount = $farmer->getProductCount($farmer_id);
$orderPending = $farmer->getOrderCountByStatus('pending', $farmer_id);
$orderCompleted = $farmer->getOrderCountByStatus('completed', $farmer_id);
$feedbackCount = $farmer->getFeedbackCount($farmer_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/farmer.css"> 
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
    <a class="navbar-brand d-flex align-items-center" href="#">
        <!-- Placeholder Image -->
        <img src="../../public/assets/logo.png" alt="Logo" class="rounded-circle mr-2" style="width: 40px; height: 40px;">
        Farmer Dashboard
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="farmer-dashboard.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Logout</a></li>
        </ul>
    </div>
</nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <h1>Welcome, Farmer!</h1>
        <div class="row">
            <!-- Products Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Products</div>
                    <div class="card-body">
                        <i class="bi bi-box-seam card-icon"></i>
                        <p class="card-text"><?= $productCount ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Pending -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Orders Pending</div>
                    <div class="card-body">
                        <i class="bi bi-clock-history card-icon"></i>
                        <p class="card-text"><?= $orderPending ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Completed -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Orders Completed</div>
                    <div class="card-body">
                        <i class="bi bi-check2-circle card-icon"></i>
                        <p class="card-text"><?= $orderCompleted ?></p>
                    </div>
                </div>
            </div>

            <!-- Feedback -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Customer Feedback</div>
                    <div class="card-body">
                        <i class="bi bi-chat-square-text card-icon"></i>
                        <p class="card-text"><?= $feedbackCount ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y'); ?> Farmer Dashboard. All Rights Reserved.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
