<?php
require_once '../models/Database.php';
require_once '../models/DriverModel.php';

// Check if driver_id is provided
if (!isset($_GET['driver_id']) || empty($_GET['driver_id'])) {
    echo '<div class="alert alert-danger">Invalid driver ID.</div>';
    exit;
}

$driver_id = intval($_GET['driver_id']);
$driverModel = new DriverModel();
$driverDetails = $driverModel->getDriverDetailsByUserId($driver_id);

if (!$driverDetails) {
    echo '<div class="alert alert-danger">Driver not found.</div>';
    exit;
}

// Get current assigned pickups for this driver
$database = new Database();
$conn = $database->connect();

$query = "SELECT p.*, o.order_id, u.username AS consumer_name
          FROM pickups p
          JOIN orders o ON p.order_id = o.order_id
          JOIN users u ON o.consumer_id = u.user_id
          WHERE p.assigned_to = :driver_id AND p.pickup_status != 'completed' AND p.pickup_status != 'cancelled'
          ORDER BY p.pickup_date ASC";
          
$stmt = $conn->prepare($query);
$stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
$stmt->execute();
$currentPickups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="driver-profile">
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="bi bi-person-badge"></i> Driver Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($driverDetails['first_name'] . ' ' . $driverDetails['last_name']) ?></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($driverDetails['username']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($driverDetails['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($driverDetails['contact_number'] ?: 'Not provided') ?></p>
                    <p><strong>Current Location:</strong> <?= htmlspecialchars($driverDetails['current_location'] ?: 'Unknown') ?></p>
                    <p><strong>Status:</strong>
                        <span class="badge badge-<?= $driverDetails['availability_status'] === 'available' ? 'success' : 
                            ($driverDetails['availability_status'] === 'busy' ? 'warning' : 'secondary') ?>">
                            <?= ucfirst(htmlspecialchars($driverDetails['availability_status'])) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5><i class="bi bi-truck"></i> Vehicle Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Vehicle Type:</strong> <?= htmlspecialchars($driverDetails['vehicle_type'] ?: 'Not specified') ?></p>
                    <p><strong>License Number:</strong> <?= htmlspecialchars($driverDetails['license_number'] ?: 'Not provided') ?></p>
                    <p><strong>Plate Number:</strong> <?= htmlspecialchars($driverDetails['vehicle_plate'] ?: 'Not provided') ?></p>
                    <p><strong>Max Load:</strong> <?= $driverDetails['max_load_capacity'] ? htmlspecialchars($driverDetails['max_load_capacity']) . ' kg' : 'Not specified' ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list-check"></i> Current Assignments (<?= count($currentPickups) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($currentPickups) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Pickup ID</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Pickup Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentPickups as $pickup): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pickup['pickup_id']) ?></td>
                                            <td><?= htmlspecialchars($pickup['order_id']) ?></td>
                                            <td><?= htmlspecialchars($pickup['consumer_name']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $pickup['pickup_status'] === 'assigned' ? 'info' : 
                                                    ($pickup['pickup_status'] === 'in_transit' ? 'primary' : 'secondary') ?>">
                                                    <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date("M j, g:i A", strtotime($pickup['pickup_date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No current assignments</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-graph-up"></i> Driver Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3><?= number_format($driverDetails['rating'], 1) ?> <i class="bi bi-star-fill text-warning"></i></h3>
                            <p class="text-muted">Average Rating</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?= $driverDetails['completed_pickups'] ?></h3>
                            <p class="text-muted">Completed Pickups</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3><?= count($currentPickups) ?></h3>
                            <p class="text-muted">Current Assignments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
