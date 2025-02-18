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
$orders = $farmer->getOrdersByFarmer($farmer_id); // Fetch farmer's orders

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $farmer->updateOrderStatus($order_id, $status);
    header('Location: orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Orders</title>
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

    <!-- Page Title -->
    <div class="container mt-5">
        <h1 class="text-center">Manage Your Orders</h1>
    </div>

    <!-- Orders Table -->
    <div class="container table-container">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $index => $order): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($order['order_id']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td><span class="badge badge-<?= $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'shipped' ? 'primary' : 'warning') ?>"><?= ucfirst($order['status']) ?></span></td>
                                <td>
                                    <form method="POST" action="orders.php" class="d-flex justify-content-center">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <select name="status" class="status-select mr-2">
                                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-update">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No orders available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y'); ?> Farmer Dashboard. All Rights Reserved.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
