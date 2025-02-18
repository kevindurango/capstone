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

// Sample farmer ID (for now, this can be replaced with the farmer's session ID)
$farmer_id = $_SESSION['farmer_id']; // Assuming the farmer's ID is stored in the session

$farmer = new Farmer();
$feedbacks = $farmer->getFeedbackByFarmer($farmer_id); // Fetch farmer's feedback
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Farmer Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/farmer.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
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

    <!-- Page Content -->
    <div class="container mt-5 table-container">
        <h1 class="text-center">Customer Feedback</h1>
        <p class="text-center text-light">View customer reviews and ratings for your products.</p>

        <!-- Feedback Table -->
        <div class="card custom-card">
            <div class="card-body">
                <h5 class="card-title">Feedback List</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Rating</th>
                                <th>Feedback</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($feedbacks)): ?>
                                <?php foreach ($feedbacks as $index => $feedback): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($feedback['product_name']) ?></td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?= htmlspecialchars($feedback['rating']) ?> / 5
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($feedback['feedback_text']) ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($feedback['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No feedback available for your products.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
