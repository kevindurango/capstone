<?php
// Start session
session_start();

// Check if the user is logged in as a Manager
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';

// Database Connection
$database = new Database();
$conn = $database->connect();

// Set error mode to exception
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Include the Pickup model
require_once '../../models/Pickup.php';  
$pickupModel = new Pickup();

// Fetch Pickup details using the Pickup model
$pickups = $pickupModel->getPickupsWithOrderDetails();

// Handle Form Submissions (Update Pickup Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the necessary data is set and not empty
    if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['pickup_status'])) {
        // Sanitize the inputs
        $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
        $new_status = filter_var($_POST['pickup_status'], FILTER_SANITIZE_STRING);

        // Ensure the inputs are valid
        if ($order_id === false || empty($new_status)) {
            echo "<div class='alert alert-danger'>Invalid input! Please check the data.</div>";
        } else {
            // Update the pickup status in the 'pickups' table
            $updateSuccess = $pickupModel->updatePickupStatus($order_id, $new_status);

            if ($updateSuccess) {
                echo "<div class='alert alert-success'>Pickup status updated successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Failed to update pickup status. Please try again.</div>";
            }

            // Redirect to avoid multi-submit
            header("Location: manager-pickup-management.php");
            exit();
        }
    } else {
        echo "<div class='alert alert-warning'>Missing data. Please make sure all fields are filled out correctly.</div>";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include '../../views/global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-1">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success">Pickup Management</h1>
                    <form method="POST" class="ml-3">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Pickups Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Consumer</th>
                                <th>Order Date</th>
                                <th>Pickup Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pickups) > 0): ?>
                                <?php foreach ($pickups as $pickup): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pickup['order_id']) ?></td>
                                        <td><?= htmlspecialchars($pickup['consumer_name']) ?></td>
                                        <td><?= htmlspecialchars(date("F j, Y, g:i A", strtotime($pickup['order_date']))) ?></td>
                                        <td><?= htmlspecialchars($pickup['pickup_location']) ?></ <td><?= htmlspecialchars($pickup['pickup_status']) ?></td>
                                        <td>
                                            <!-- Update Status Form -->
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($pickup['order_id']) ?>">
                                                <div class="select-wrapper">
                                                    <select name="pickup_status" class="custom-select">
                                                        <option value="pending" <?= ($pickup['pickup_status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                                        <option value="scheduled" <?= ($pickup['pickup_status'] == 'scheduled') ? 'selected' : '' ?>>Scheduled</option>
                                                        <option value="in_transit" <?= ($pickup['pickup_status'] == 'in_transit') ? 'selected' : '' ?>>In Transit</option>
                                                        <option value="picked_up" <?= ($pickup['pickup_status'] == 'picked_up') ? 'selected' : '' ?>>Picked Up</option>
                                                        <option value="completed" <?= ($pickup['pickup_status'] == 'completed') ? 'selected' : '' ?>>Completed</option>
                                                        <option value="cancelled" <?= ($pickup['pickup_status'] == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary mt-2">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No pickups found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>