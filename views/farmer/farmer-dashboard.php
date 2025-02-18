<?php
session_start();
require_once '../../models/Farmer.php';
require_once '../../controllers/UserController.php'; // Include the UserController

// Initialize UserController
$userController = new UserController();

// Handle farmer logout functionality
if (isset($_POST['logout'])) {
    $userController->farmerLogout(); // Call farmerLogout method
}

// Check if the user is logged in as a farmer
if (!isset($_SESSION['farmer_logged_in']) || $_SESSION['farmer_logged_in'] !== true) {
    header("Location: farmer-login.php");
    exit();
}

// Fetch the farmer ID from the session
$farmer_id = $_SESSION['farmer_id'] ?? null;

// Debugging session variable
if (!$farmer_id) {
    echo 'Farmer ID not set in session';
    exit();
}

// Initialize Farmer object
$farmer = new Farmer();

// Fetch farmer details and dashboard data
try {
    $farmerDetails = $farmer->getFarmerDetails($farmer_id);
    $productCount = $farmer->getProductCount($farmer_id);
    $orderPending = $farmer->getOrderCountByStatus('pending', $farmer_id);
    $orderCompleted = $farmer->getOrderCountByStatus('completed', $farmer_id);
    $feedbackCount = $farmer->getFeedbackCount($farmer_id);
    $orderCountPerDay = $farmer->getOrderCountPerDay($farmer_id);
    $feedbackRatingAvg = $farmer->getAverageFeedbackRating($farmer_id);
} catch (Exception $e) {
    // Handle database errors gracefully
    die("Error fetching data: " . $e->getMessage());
}
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../../public/assets/logo.png" alt="Logo" class="rounded-circle mr-2" style="width: 40px; height: 40px;">
            Farmer Dashboard
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="farmer-dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-products.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-profile.php">Profile</a></li>
                <li class="nav-item">
                <form method="POST" class="nav-link p-0 ml-4">
                    <button type="submit" name="logout" class="btn btn-danger btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>  
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Dashboard Cards -->
        <h1>Welcome, <?= htmlspecialchars($farmerDetails['first_name'] ?? 'Farmer') ?>!</h1>
        <div class="row">
            <!-- Products Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Products</div>
                    <div class="card-body">
                        <i class="bi bi-box-seam card-icon"></i>
                        <p class="card-text"><?= htmlspecialchars($productCount) ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Pending -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Orders Pending</div>
                    <div class="card-body">
                        <i class="bi bi-clock-history card-icon"></i>
                        <p class="card-text"><?= htmlspecialchars($orderPending) ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Completed -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Orders Completed</div>
                    <div class="card-body">
                        <i class="bi bi-check2-circle card-icon"></i>
                        <p class="card-text"><?= htmlspecialchars($orderCompleted) ?></p>
                    </div>
                </div>
            </div>

            <!-- Feedback -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">Customer Feedback</div>
                    <div class="card-body">
                        <i class="bi bi-chat-square-text card-icon"></i>
                        <p class="card-text"><?= htmlspecialchars($feedbackCount) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Orders Per Day</div>
                    <div class="card-body">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer text-center mt-5 py-3 bg-dark">
        <p>&copy; <?= date('Y'); ?> Farmer Dashboard. All Rights Reserved.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Orders per Day Chart
        var ordersCtx = document.getElementById('ordersChart').getContext('2d');
        var ordersChart = new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],  // Replace with dynamic data if available
                datasets: [{
                    label: 'Orders per Day',
                    data: [<?= implode(',', $orderCountPerDay) ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    fill: true,
                }]
            }
        });
    </script>
</body>
</html>
