<?php
session_start();

if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';
require_once '../../models/DriverModel.php'; // Include the DriverModel

// Database Connection
$database = new Database();
$conn = $database->connect();
$log = new Log();
$driverModel = new DriverModel(); // Initialize driver model

// Run debug functions
$manager_user_id = $_SESSION['manager_user_id'] ?? null;

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and Filter Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

// Update Pickup Logic
if (isset($_POST['update_pickup'])) {
    $pickup_id = $_POST['pickup_id'];
    $pickup_status = $_POST['pickup_status'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_location = $_POST['pickup_location'];
    $assigned_to = $_POST['assigned_to'];
    $pickup_notes = $_POST['pickup_notes'];

    // Get the old pickup data for logging
    $oldDataQuery = "SELECT * FROM pickups WHERE pickup_id = :pickup_id";
    $oldDataStmt = $conn->prepare($oldDataQuery);
    $oldDataStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    $oldDataStmt->execute();
    $oldData = $oldDataStmt->fetch(PDO::FETCH_ASSOC);

    // Update Query
    $updateQuery = "UPDATE pickups
                   SET pickup_status = :pickup_status,
                       pickup_date = :pickup_date,
                       pickup_location = :pickup_location,
                       assigned_to = :assigned_to,
                       pickup_notes = :pickup_notes
                   WHERE pickup_id = :pickup_id";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':pickup_status', $pickup_status);
    $updateStmt->bindParam(':pickup_date', $pickup_date);
    $updateStmt->bindParam(':pickup_location', $pickup_location);
    $updateStmt->bindParam(':assigned_to', $assigned_to);
    $updateStmt->bindParam(':pickup_notes', $pickup_notes);
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Updated pickup #$pickup_id");
        }
        $_SESSION['message'] = "Pickup details updated successfully!";
        $_SESSION['message_type'] = 'success';
    }
}

// Assign Driver to Pickup
if (isset($_POST['assign_driver'])) {
    $pickup_id = $_POST['pickup_id'];
    $driver_id = $_POST['driver_id'];
    
    $updateQuery = "UPDATE pickups SET assigned_to = :driver_id, pickup_status = 'assigned' WHERE pickup_id = :pickup_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Update driver status to busy
        $driverModel->updateDriverStatus($driver_id, 'busy');
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Assigned driver #$driver_id to pickup #$pickup_id");
        }
        $_SESSION['message'] = "Driver assigned successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error assigning driver.";
        $_SESSION['message_type'] = 'danger';
    }
}

// Count total pickups
$countQuery = "SELECT COUNT(*) FROM pickups";
$countStmt = $conn->query($countQuery);
$totalPickups = $countStmt->fetchColumn();

// Count pickups by status
$pendingQuery = "SELECT COUNT(*) FROM pickups WHERE pickup_status = 'pending'";
$pendingStmt = $conn->query($pendingQuery);
$pendingCount = $pendingStmt->fetchColumn();

// Count today's pickups
$todayQuery = "SELECT COUNT(*) FROM pickups WHERE DATE(pickup_date) = CURDATE()";
$todayStmt = $conn->query($todayQuery);
$todayPickups = $todayStmt->fetchColumn();

// Count assigned pickups - Update the query to get accurate count
$assignedQuery = "SELECT COUNT(*) FROM pickups p 
                 WHERE p.pickup_status = 'assigned' 
                 AND p.assigned_to IS NOT NULL";
$assignedStmt = $conn->query($assignedQuery);
$assignedPickups = $assignedStmt->fetchColumn();

// Fetch Pickups Query with Filtering
$query = "SELECT p.*, o.order_id, o.order_date, 
          c.username AS consumer_name,
          d.username AS driver_name,
          d.first_name AS driver_first_name,
          d.last_name AS driver_last_name
          FROM pickups p
          JOIN orders o ON p.order_id = o.order_id
          JOIN users c ON o.consumer_id = c.user_id
          LEFT JOIN users d ON p.assigned_to = d.user_id
          WHERE 1=1";

if ($search) {
    $query .= " AND (o.order_id LIKE :search OR u.username LIKE :search)";
}
if ($statusFilter) {
    $query .= " AND p.pickup_status = :statusFilter";
}
if ($dateFilter) {
    $query .= " AND DATE(p.pickup_date) = :dateFilter";
}

$query .= " ORDER BY p.pickup_date DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
if ($statusFilter) {
    $stmt->bindValue(':statusFilter', $statusFilter, PDO::PARAM_STR);
}
if ($dateFilter) {
    $stmt->bindValue(':dateFilter', $dateFilter, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct statuses for filter
$statusQuery = "SELECT DISTINCT pickup_status FROM pickups ORDER BY pickup_status";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->execute();
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate total pages for pagination
$totalPages = ceil($totalPickups / $limit);

// Fetch available drivers
$availableDrivers = $driverModel->getAvailableDrivers();
$allDrivers = $driverModel->getAllDriversWithAssignments(); // Use the new method instead of direct query

// Comment out or remove the old query:
/*
$allDriversQuery = "SELECT d.*, u.username, u.first_name, u.last_name, 
                   (SELECT COUNT(*) FROM pickups WHERE assigned_to = d.user_id AND pickup_status IN ('assigned', 'in_transit')) as active_pickups
                   FROM driver_details d
                   JOIN users u ON d.user_id = u.user_id
                   ORDER BY d.availability_status, d.rating DESC";
$allDriversStmt = $conn->prepare($allDriversQuery);
$allDriversStmt->execute();
$allDrivers = $allDriversStmt->fetchAll(PDO::FETCH_ASSOC);
*/

// Handle logout
if (isset($_POST['logout'])) {
    // Log the logout action
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Manager logged out");
    }
    
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
    <link rel="stylesheet" href="../../public/style/pickup-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .manager-header {
            background: linear-gradient(135deg, #1a8754 0%, #34c38f 100%); /* Updated to match manager-orders.css */
            color: white;
            padding: 10px 0;
        }
        .manager-badge {
            background-color: #157347; /* Updated to match manager-orders.css */
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

        .pickup-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .pickup-card:hover {
            transform: translateY(-5px);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
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

        /* Statistics Cards */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-card .icon {
            font-size: 2rem;
            color: #28a745;
        }

        .stats-card .count {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }

        .stats-card .title {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }

        .pickup-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .pickup-card:hover {
            transform: translateY(-5px);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
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

        /* Enhanced Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            position: relative;
        }

        .page-header:after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: linear-gradient(90deg, #28a745, transparent);
        }

        .page-header h1 {
            background: linear-gradient(45deg, #212121, #28a745);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .card-footer {
            display: flex;
            justify-content: flex-start; /* Align buttons to the left */
            gap: 0.5rem; /* Add small spacing between buttons */
        }

        /* Driver card styling */
        .driver-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .driver-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .driver-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .driver-status {
            font-size: 0.85rem;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .driver-status.available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .driver-status.busy {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .driver-status.offline {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .driver-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .driver-avatar {
            width: 60px;
            height: 60px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
        }
        
        .driver-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .driver-stat {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            flex: 1;
            text-align: center;
        }
        
        .driver-stat .value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .driver-stat .label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .driver-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        /* Tab panel styling */
        .tab-container {
            margin-bottom: 20px;
        }
        
        .nav-tabs .nav-item .nav-link {
            color: #6c757d;
            font-weight: 500;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 20px;
        }
        
        .nav-tabs .nav-item .nav-link.active {
            color: #28a745;
            background: transparent;
            border-bottom: 3px solid #28a745;
        }
        
        .nav-tabs .nav-item .nav-link:hover {
            border-color: transparent;
            border-bottom: 3px solid #c3e6cb;
        }
        
        .tab-content {
            padding-top: 20px;
        }
        
        /* Driver assignment form */
        .assignment-form {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .assignments-list {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .assignments-list::-webkit-scrollbar {
            width: 5px;
        }

        .assignments-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .assignments-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .assignment-item {
            border-left: 3px solid #28a745;
            transition: all 0.2s ease;
        }

        .assignment-item:hover {
            transform: translateX(5px);
            background-color: #f8f9fa !important;
        }

        .assignment-details {
            font-size: 0.85rem;
            padding-left: 0.5rem;
            border-left: 2px solid #28a745;
            margin-top: 0.5rem;
        }

        .assignment-details small {
            line-height: 1.6;
        }

        .assignments-list {
            max-height: 250px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .active-assignments {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-truck"></i> PICKUP MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 pickup-management-page">
                <!-- Update breadcrumb styling -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pickup Management</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-truck text-success"></i> Pickup Management</h1>
                        <p class="text-muted">Manage and track all product pickups in one place</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Display Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">  
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <div class="icon text-primary">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div class="count"><?= $totalPickups ?></div>
                                <div class="title">Total Pickups</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <div class="icon text-warning">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="count"><?= $pendingCount ?></div>
                                <div class="title">Pending Pickups</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <div class="icon text-success">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="count"><?= $todayPickups ?></div>
                                <div class="title">Today's Pickups</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <div class="icon text-info">
                                    <i class="bi bi-person-check"></i>
                                </div>
                                <div class="count"><?= $assignedPickups ?></div>
                                <div class="title">Assigned Pickups</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="tab-container">
                    <ul class="nav nav-tabs" id="managementTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pickups-tab" data-toggle="tab" href="#pickups" role="tab">
                                <i class="bi bi-box-seam"></i> Pickups
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="drivers-tab" data-toggle="tab" href="#drivers" role="tab">
                                <i class="bi bi-person-badge"></i> Drivers
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="managementTabsContent">
                        <!-- Pickups Tab -->
                        <div class="tab-pane fade show active" id="pickups" role="tabpanel">
                            <!-- Search and Filter -->
                            <div class="search-container">
                                <form method="GET" action="" class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            </div>
                                            <input type="text" name="search" class="form-control" placeholder="Search pickups..." value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="bi bi-filter"></i></span>
                                            </div>
                                            <select name="status" class="form-control">
                                                <option value="">All Statuses</option>
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                                        <?= ucfirst(htmlspecialchars($status)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                            </div>
                                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="bi bi-funnel"></i> Apply
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Pickups Content -->
                            <?php if (count($pickups) > 0): ?>
                                <div class="row">
                                    <?php foreach ($pickups as $pickup): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card pickup-card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <span class="font-weight-bold">
                                                        <i class="bi bi-box"></i> Pickup #<?= htmlspecialchars($pickup['pickup_id']) ?>
                                                    </span>
                                                    <span class="status-label status-<?= strtolower(str_replace(' ', '-', $pickup['pickup_status'])) ?>">
                                                        <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                                                    </span>
                                                </div>
                                                <div class="card-body">
                                                    <p><strong><i class="bi bi-receipt"></i> Order ID:</strong> <?= htmlspecialchars($pickup['order_id']) ?></p>
                                                    <p><strong><i class="bi bi-person"></i> Consumer:</strong> <?= htmlspecialchars($pickup['consumer_name']) ?></p>
                                                    <p><strong><i class="bi bi-calendar-event"></i> Date:</strong> <?= htmlspecialchars(date("F j, Y, g:i A", strtotime($pickup['pickup_date']))) ?></p>
                                                    <p><strong><i class="bi bi-geo-alt"></i> Location:</strong> <?= htmlspecialchars($pickup['pickup_location']) ?></p>
                                                    <p><strong><i class="bi bi-person-badge"></i> Assigned To:</strong> 
                                                        <?php if (!empty($pickup['driver_name'])): ?>
                                                            <?= htmlspecialchars($pickup['driver_first_name'] . ' ' . $pickup['driver_last_name']) ?>
                                                            <span class="badge badge-info">
                                                                <?= htmlspecialchars($pickup['driver_name']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Unassigned</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <div class="pickup-notes mt-2">
                                                        <strong><i class="bi bi-card-text"></i> Notes:</strong>
                                                        <?= htmlspecialchars($pickup['pickup_notes'] ?: 'No notes available') ?>
                                                    </div>
                                                </div>
                                                <!-- Show driver assignment field if not assigned -->
                                                <?php if (empty($pickup['assigned_to']) && $pickup['pickup_status'] !== 'completed' && $pickup['pickup_status'] !== 'cancelled'): ?>
                                                    <div class="assignment-form">
                                                        <form class="driver-assignment-form form-inline">
                                                            <input type="hidden" name="pickup_id" value="<?= htmlspecialchars($pickup['pickup_id']) ?>">
                                                            <div class="form-group mr-2">
                                                                <select name="driver_id" class="form-control form-control-sm" required>
                                                                    <option value="">Select Driver</option>
                                                                    <?php foreach ($availableDrivers as $driver): ?>
                                                                        <option value="<?= htmlspecialchars($driver['user_id']) ?>">
                                                                            <?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?> 
                                                                            (<?= number_format($driver['rating'], 1) ?>★)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="bi bi-person-check"></i> Assign
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-footer">
                                                    <button class="btn btn-sm btn-info view-order-btn" 
                                                            data-toggle="modal" 
                                                            data-target="#viewOrderModal" 
                                                            data-order-id="<?= htmlspecialchars($pickup['order_id']) ?>">
                                                        <i class="bi bi-eye"></i> View Order
                                                    </button>
                                                    <button class="btn btn-sm btn-primary edit-pickup-btn" 
                                                            data-toggle="modal" 
                                                            data-target="#editPickupModal" 
                                                            data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>"
                                                            data-pickup-status="<?= htmlspecialchars($pickup['pickup_status']) ?>"
                                                            data-pickup-date="<?= htmlspecialchars($pickup['pickup_date']) ?>"
                                                            data-pickup-location="<?= htmlspecialchars($pickup['pickup_location']) ?>"
                                                            data-assigned-to="<?= htmlspecialchars($pickup['assigned_to']) ?>"
                                                            data-pickup-notes="<?= htmlspecialchars($pickup['pickup_notes']) ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <!-- Empty State -->
                                <div class="empty-state">
                                    <i class="bi bi-box-seam"></i>
                                    <h4>No Pickups Found</h4>
                                    <p>There are no pickups matching your search criteria.</p>
                                    <a href="manager-pickup-management.php" class="btn btn-primary">Clear Filters</a>
                                </div>
                            <?php endif; ?>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination-container mt-4">
                                    <nav>
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                                        <i class="bi bi-chevron-double-left"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= ($page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            $startPage = max(1, $page - 2);
                                            $endPage = min($totalPages, $page + 2);
                                            
                                            if ($startPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">1</a>
                                                </li>
                                                <?php if ($startPage > 2): ?>
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($endPage < $totalPages): ?>
                                                <?php if ($endPage < $totalPages - 1): ?>
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                <?php endif; ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>"><?= $totalPages ?></a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= ($page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&date=<?= urlencode($dateFilter) ?>">
                                                        <i class="bi bi-chevron-double-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <p class="text-center text-muted">Showing page <?= $page ?> of <?= $totalPages ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Drivers Tab -->
                        <div class="tab-pane fade" id="drivers" role="tabpanel">
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="bi bi-people"></i> Driver Management</h5>
                                        </div>
                                        <div class="card-body">
                                            <!-- Add Debug Output -->
                                            <?php
                                            error_log("All Drivers: " . print_r($allDrivers, true));
                                            if (empty($allDrivers)) {
                                                error_log("No drivers found in the database");
                                            }
                                            ?>
                                            
                                            <!-- Add Debug Output for Assignments -->
                                            <?php
                                            // Debug output for assignments
                                            error_log("Number of drivers: " . count($allDrivers));
                                            foreach ($allDrivers as $idx => $driver) {
                                                error_log("Driver #{$driver['user_id']} assignments: " . 
                                                    (isset($driver['active_assignments']) ? count($driver['active_assignments']) : "none"));
                                            }
                                            ?>

                                            <?php if (empty($allDrivers)): ?>
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle"></i> No drivers found. Please ensure:
                                                    <ul class="mb-0">
                                                        <li>Users are created with the Driver role (role_id = 6)</li>
                                                        <li>Driver details are properly added in the driver_details table</li>
                                                        <li>The database connection is working correctly</li>
                                                    </ul>
                                                </div>
                                            <?php else: ?>
                                                <div class="row" id="driversContainer">
                                                    <?php foreach ($allDrivers as $driver): ?>
                                                        <div class="col-md-6 col-xl-4 mb-4 driver-item">
                                                            <div class="driver-card">
                                                                <div class="card-header">
                                                                    <h6 class="mb-0"><?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?></h6>
                                                                    <span class="driver-status <?= htmlspecialchars($driver['availability_status']) ?>">
                                                                        <?= ucfirst(htmlspecialchars($driver['availability_status'])) ?>
                                                                    </span>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="driver-info">
                                                                        <div class="driver-avatar">
                                                                            <i class="bi bi-person"></i>
                                                                        </div>
                                                                        <div>
                                                                            <p class="mb-1"><i class="bi bi-truck"></i> <?= htmlspecialchars($driver['vehicle_type']) ?></p>
                                                                            <p class="mb-1"><i class="bi bi-upc-scan"></i> <?= htmlspecialchars($driver['vehicle_plate']) ?></p>
                                                                            <p class="mb-0"><i class="bi bi-telephone"></i> <?= htmlspecialchars($driver['contact_number']) ?></p>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <?php if (isset($driver['active_assignments']) && !empty($driver['active_assignments'])): ?>
                                                                        <div class="active-assignments mt-3">
                                                                            <h6 class="text-muted mb-2">
                                                                                <i class="bi bi-clipboard-check"></i> Active Assignments (<?= count($driver['active_assignments']) ?>)
                                                                            </h6>
                                                                            <div class="assignments-list">
                                                                                <?php foreach ($driver['active_assignments'] as $assignment): ?>
                                                                                    <div class="assignment-item p-2 mb-2 bg-light rounded">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                            <strong><i class="bi bi-box"></i> Pickup #<?= htmlspecialchars($assignment['pickup_id']) ?></strong>
                                                                                            <span class="badge badge-<?= getStatusClass($assignment['pickup_status']) ?>">
                                                                                                <?= ucfirst(htmlspecialchars($assignment['pickup_status'])) ?>
                                                                                            </span>
                                                                                        </div>
                                                                                        <div class="assignment-details">
                                                                                            <small class="d-block text-muted">
                                                                                                <i class="bi bi-person"></i> <?= htmlspecialchars($assignment['customer_first_name'] . ' ' . $assignment['customer_last_name']) ?>
                                                                                            </small>
                                                                                            <small class="d-block text-muted">
                                                                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($assignment['pickup_location']) ?>
                                                                                            </small>
                                                                                            <small class="d-block text-muted">
                                                                                                <i class="bi bi-clock"></i> <?= date("M j, g:i A", strtotime($assignment['pickup_date'])) ?>
                                                                                            </small>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="mt-3 text-center text-muted">
                                                                            <i class="bi bi-inbox"></i> No active assignments
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="driver-actions">
                                                                        <button class="btn btn-sm btn-info view-driver-btn" 
                                                                            data-toggle="modal" 
                                                                            data-target="#viewDriverModal"
                                                                            data-driver-id="<?= htmlspecialchars($driver['user_id']) ?>"
                                                                            data-driver-name="<?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?>">
                                                                            <i class="bi bi-eye"></i> Details
                                                                        </button>
                                                                        
                                                                        <div class="btn-group" role="group">
                                                                            <button class="btn btn-sm btn-outline-success update-status-btn"
                                                                                data-driver-id="<?= htmlspecialchars($driver['user_id']) ?>"
                                                                                data-status="available">
                                                                                Available
                                                                            </button>
                                                                            <button class="btn btn-sm btn-outline-warning update-status-btn"
                                                                                data-driver-id="<?= htmlspecialchars($driver['user_id']) ?>"
                                                                                data-status="busy">
                                                                                Busy
                                                                            </button>
                                                                            <button class="btn btn-sm btn-outline-secondary update-status-btn"
                                                                                data-driver-id="<?= htmlspecialchars($driver['user_id']) ?>"
                                                                                data-status="offline">
                                                                                Offline
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View Order Details Modal -->
    <div class="modal fade" id="viewOrderModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printOrderBtn"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Driver Modal -->
    <div class="modal fade" id="assignDriverModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-check"></i> Assign Driver</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="assignDriverForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="assignOrderId">
                        <div class="form-group">
                            <label><i class="bi bi-person"></i> Driver Name</label>
                            <input type="text" name="assigned_to" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-geo-alt"></i> Pickup Location</label>
                            <input type="text" name="pickup_location" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-calendar"></i> Pickup Date</label>
                            <input type="datetime-local" name="pickup_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-card-text"></i> Notes</label>
                            <textarea name="pickup_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Update Pickup Status</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="updateStatusForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="statusOrderId">
                        <div class="form-group">
                            <label><i class="bi bi-tag"></i> Status</label>
                            <select name="pickup_status" id="pickup_status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_transit">In Transit</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="bi bi-card-text"></i> Status Update Note</label>
                            <textarea name="status_note" class="form-control" rows="3" placeholder="Add notes about this status change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Pickup Modal -->
    <div class="modal fade" id="editPickupModal" tabindex="-1" role="dialog" aria-labelledby="editPickupModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPickupModalLabel"><i class="bi bi-truck"></i> Edit Pickup Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" class="pickup-form">
                        <input type="hidden" name="pickup_id" id="pickup_id">
                        <div class="form-group">
                            <label for="pickup_status"><i class="bi bi-tag"></i> Pickup Status</label>
                            <select class="form-control" id="pickup_status" name="pickup_status">
                                <option value="pending">Pending</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="assigned">Assigned</option>
                                <option value="in transit">In Transit</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_assigned_driver"><i class="bi bi-person-badge"></i> Assign Driver</label>
                            <select class="form-control" id="edit_assigned_driver" name="assigned_to">
                                <option value="">Select Driver</option>
                                <?php foreach ($availableDrivers as $driver): ?>
                                    <option value="<?= htmlspecialchars($driver['user_id']) ?>">
                                        <?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?> 
                                        (<?= number_format($driver['rating'], 1) ?>★)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Only available drivers are shown</small>
                        </div>

                        <div class="form-group">
                            <label for="pickup_date"><i class="bi bi-calendar"></i> Pickup Date</label>
                            <input type="datetime-local" class="form-control" id="pickup_date" name="pickup_date">
                        </div>

                        <div class="form-group">
                            <label for="pickup_location"><i class="bi bi-geo-alt"></i> Pickup Location</label>
                            <input type="text" class="form-control" id="pickup_location" name="pickup_location">
                        </div>

                        <div class="form-group">
                            <label for="pickup_notes"><i class="bi bi-card-text"></i> Pickup Notes</label>
                            <textarea class="form-control" id="pickup_notes" name="pickup_notes" rows="3"></textarea>
                        </div>

                        <div class="pickup-actions">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" name="update_pickup">
                                <i class="bi bi-check-circle"></i> Update Pickup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Driver Modal -->
    <div class="modal fade" id="viewDriverModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge"></i> <span id="driverModalName">Driver Details</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="driverDetails">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading driver details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function () {
            // Format the date for datetime-local input
            function formatDateForInput(dateString) {
                const date = new Date(dateString);
                return date.toISOString().slice(0, 16);
            }

            // Handle edit pickup button click
            $('.edit-pickup-btn').click(function () {
                const pickupId = $(this).data('pickup-id');
                const pickupStatus = $(this).data('pickup-status');
                const pickupDate = $(this).data('pickup-date');
                const pickupLocation = $(this).data('pickup-location');
                const assignedTo = $(this).data('assigned-to');
                const pickupNotes = $(this).data('pickup-notes');

                // Populate the modal form
                $('#pickup_id').val(pickupId);
                $('#pickup_status').val(pickupStatus);
                $('#pickup_date').val(formatDateForInput(pickupDate));
                $('#pickup_location').val(pickupLocation);
                $('#edit_assigned_driver').val(assignedTo);
                $('#pickup_notes').val(pickupNotes);

                // Update modal title
                $('#editPickupModalLabel').text(`Edit Pickup #${pickupId}`);
                
                // Show/hide assigned driver field based on status
                if (pickupStatus === 'completed' || pickupStatus === 'cancelled') {
                    $('#edit_assigned_driver').prop('disabled', true);
                } else {
                    $('#edit_assigned_driver').prop('disabled', false);
                }
            });

            // Handle view order button click
            $('.view-order-btn').click(function () {
                const orderId = $(this).data('order-id');

                // Show loading message in the modal
                $('#orderDetails').html('<p>Loading order details...</p>');

                // Fetch order details via AJAX
                $.ajax({
                    url: '../../controllers/get-order-details.php', // Endpoint to fetch order details
                    method: 'GET',
                    data: { order_id: orderId },
                    success: function (response) {
                        // Populate the modal with the fetched order details
                        $('#orderDetails').html(response);
                    },
                    error: function () {
                        // Show error message if the request fails
                        $('#orderDetails').html('<p class="text-danger">Failed to load order details. Please try again.</p>');
                    }
                });
            });

            // Handle print button click
            $('#printOrderBtn').click(function () {
                const printContent = document.getElementById('orderDetails').innerHTML;
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                printWindow.document.open();
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Print Order Details</title>
                        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                    </head>
                    <body onload="window.print(); window.close();">
                        <div class="container mt-4">
                            ${printContent}
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            });

            // Auto-dismiss alerts
            setTimeout(function () {
                $('.alert').alert('close');
            }, 5000);

            // Search Drivers
            $('#driverSearch').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('.driver-item').each(function() {
                    const driverName = $(this).find('.card-header h6').text().toLowerCase();
                    const vehicleInfo = $(this).find('.driver-info').text().toLowerCase();
                    
                    if (driverName.includes(searchText) || vehicleInfo.includes(searchText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // View driver details
            $('.view-driver-btn').click(function() {
                const driverId = $(this).data('driver-id');
                const driverName = $(this).data('driver-name');
                
                // Update modal title
                $('#driverModalName').text(driverName);
                
                // Show loading indicator
                $('#driverDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="mt-2">Loading driver details...</p></div>');
                
                // Fetch driver details via AJAX
                $.ajax({
                    url: '../../controllers/get-driver-details.php',
                    method: 'GET',
                    data: { driver_id: driverId },
                    success: function(response) {
                        $('#driverDetails').html(response);
                    },
                    error: function() {
                        $('#driverDetails').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load driver details. Please try again.</div>');
                    }
                });
            });

            // Update driver status
            $('.update-status-btn').click(function() {
                const driverId = $(this).data('driver-id');
                const status = $(this).data('status');
                const button = $(this);
                const driverCard = button.closest('.driver-card');
                const statusBadge = driverCard.find('.driver-status');
                
                console.log("Button clicked for driver #" + driverId + ", status: " + status);
                
                // Check if driver ID is valid
                if (!driverId) {
                    console.error("Missing driver ID");
                    showErrorAlert(driverCard, "Missing driver ID");
                    return;
                }
                
                // Prevent updating to the same status
                if (statusBadge.hasClass(status)) {
                    console.log("Driver already has status: " + status);
                    showInfoAlert(driverCard, `Driver is already ${status}.`);
                    return;
                }
                
                // Visual feedback - disable all status buttons during update
                driverCard.find('.update-status-btn').prop('disabled', true);
                
                // Change status badge to indicate updating
                const originalStatusClass = statusBadge.attr('class').replace('driver-status', '').trim();
                const originalStatusText = statusBadge.text();
                
                statusBadge.removeClass().addClass('driver-status updating');
                statusBadge.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
                
                // Call update function with improved error handling
                updateDriverStatus(driverId, status)
                    .done(function(response) {
                        console.log("Update response:", response);
                        if (response && response.success) {
                            // Update status badge
                            statusBadge.removeClass('updating available busy offline')
                                     .addClass(status)
                                     .text(status.charAt(0).toUpperCase() + status.slice(1));
                            
                            showSuccessAlert(driverCard, "Status updated successfully!");
                            
                            // Highlight the active button
                            driverCard.find('.update-status-btn').removeClass('active');
                            button.addClass('active');
                        } else {
                            // Revert status badge
                            statusBadge.removeClass('updating').addClass(originalStatusClass).text(originalStatusText);
                            
                            // Show error message from server
                            const errorMessage = response && response.message ? response.message : 'Unknown error occurred';
                            showErrorAlert(driverCard, errorMessage);
                        }
                    })
                    .fail(function(xhr, textStatus, error) {
                        console.error("AJAX Error:", {xhr, textStatus, error});
                        
                        // Revert status badge
                        statusBadge.removeClass('updating').addClass(originalStatusClass).text(originalStatusText);
                        
                        // Determine error message with more details
                        let errorMessage = 'Network error occurred. Please check console for details.';
                        
                        try {
                            // Try to parse response as JSON
                            if (xhr.responseText && xhr.responseText.trim() !== '') {
                                const response = JSON.parse(xhr.responseText);
                                if (response && response.message) {
                                    errorMessage = response.message;
                                }
                            }
                        } catch (e) {
                            console.error("Error parsing JSON response:", e);
                            
                            // If not JSON, provide status-based messages
                            if (textStatus === 'timeout') {
                                errorMessage = 'Request timed out. Server might be busy.';
                            } else if (xhr.status === 404) {
                                errorMessage = 'Update service not found. Check that update-driver-status.php exists.';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Server error occurred. Check PHP logs.';
                            } else if (xhr.status === 0) {
                                errorMessage = 'Unable to connect to server. Check your network connection.';
                            }
                            
                            // Include raw response for debugging if available
                            if (xhr.responseText) {
                                console.log("Raw server response:", xhr.responseText);
                                if (xhr.responseText.includes("Parse error") || xhr.responseText.includes("syntax error")) {
                                    errorMessage += " PHP syntax error detected.";
                                }
                            }
                        }
                        
                        showErrorAlert(driverCard, errorMessage);
                    })
                    .always(function() {
                        // Re-enable all status buttons
                        driverCard.find('.update-status-btn').prop('disabled', false);
                    });
            });
            
            // Fix for driver status updates - Add a direct update function with improved error handling
            function updateDriverStatus(driverId, status) {
                console.log("Updating driver #" + driverId + " status to: " + status);
                return $.ajax({
                    url: '../../controllers/update-driver-status.php',
                    method: 'POST',
                    data: { driver_id: driverId, status: status },
                    dataType: 'json',
                    timeout: 20000, // 20 second timeout
                    beforeSend: function() {
                        console.log("Sending AJAX request to update driver status");
                    },
                    error: function(xhr, status, error) {
                        // Log detailed error information
                        console.error("AJAX Error Details:");
                        console.error("Status: " + status);
                        console.error("Error: " + error);
                        console.error("Response Text: " + xhr.responseText);
                        console.error("Status Code: " + xhr.status);
                        
                        // Try to determine the exact issue
                        if (xhr.status === 404) {
                            console.error("Controller file not found. Please check path: ../../controllers/update-driver-status.php");
                        } else if (xhr.status === 500) {
                            console.error("Server error. Check PHP logs for details.");
                        } else if (status === 'timeout') {
                            console.error("Request timed out.");
                        } else if (status === 'parsererror') {
                            console.error("JSON parse error. Response content:", xhr.responseText);
                        }
                    }
                });
            }

            // Helper functions for alerts
            function showSuccessAlert(container, message) {
                const alertHtml = `
                    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                        <i class="bi bi-check-circle"></i> ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>`;
                container.find('.card-body').prepend(alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    container.find('.alert-success').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            function showErrorAlert(container, message) {
                const alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>`;
                container.find('.card-body').prepend(alertHtml);
                
                // Auto-dismiss after 8 seconds (longer for errors)
                setTimeout(() => {
                    container.find('.alert-danger').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 8000);
            }
            
            function showInfoAlert(container, message) {
                const alertHtml = `
                    <div class="alert alert-info alert-dismissible fade show mt-2" role="alert">
                        <i class="bi bi-info-circle"></i> ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>`;
                container.find('.card-body').prepend(alertHtml);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    container.find('.alert-info').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 3000);
            }

            // Add status change handler
            $('#pickup_status').change(function() {
                const status = $(this).val();
                const driverSelect = $('#edit_assigned_driver');
                
                if (status === 'completed' || status === 'cancelled') {
                    driverSelect.prop('disabled', true);
                } else {
                    driverSelect.prop('disabled', false);
                }
                
                // If status is 'assigned' but no driver is selected, select first available driver
                if (status === 'assigned' && !driverSelect.val()) {
                    const firstDriver = driverSelect.find('option:not(:first)').first();
                    if (firstDriver.length) {
                        driverSelect.val(firstDriver.val());
                    }
                }
            });
            
            // Add CSS for updating status
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .driver-status.updating {
                        background-color: #e0e0e0;
                        color: #555;
                    }
                    .update-status-btn.active {
                        font-weight: bold;
                        box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
                    }
                    .driver-status.available {
                        background-color: #d4edda;
                        color: #155724;
                    }
                    .driver-status.busy {
                        background-color: #fff3cd;
                        color: #856404;
                    }
                    .driver-status.offline {
                        background-color: #e2e3e5;
                        color: #383d41;
                    }
                `)
                .appendTo('head');

            // Handle AJAX driver assignment form submission
            $('.driver-assignment-form').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const pickup_id = form.find('input[name="pickup_id"]').val();
                const driver_id = form.find('select[name="driver_id"]').val();
                const submitButton = form.find('button[type="submit"]');
                const pickupCard = form.closest('.pickup-card');
                
                // Validation
                if (!driver_id) {
                    alert('Please select a driver');
                    return;
                }
                
                // Disable the button and show loading state
                submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Assigning...');
                
                // Make AJAX request
                $.ajax({
                    url: '../../controllers/assign-driver.php',
                    type: 'POST',
                    data: {
                        pickup_id: pickup_id,
                        driver_id: driver_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Get driver name from the select option text
                            const driverName = form.find('select option:selected').text();
                            
                            // Show success message
                            const successAlert = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                                Driver assigned successfully!
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>`;
                            pickupCard.find('.card-body').prepend(successAlert);
                            
                            // Update the pickup card with the assigned driver and status
                            pickupCard.find('.status-label').removeClass('status-pending').addClass('status-assigned').text('Assigned');
                            
                            // Update "Assigned To" field
                            const assignedToField = pickupCard.find('.card-body p:contains("Assigned To:")');
                            if (assignedToField.length) {
                                assignedToField.html(`<strong><i class="bi bi-person-badge"></i> Assigned To:</strong> 
                                    ${driverName}`);
                            }
                            
                            // Remove the assignment form
                            form.closest('.assignment-form').remove();
                            
                            // Auto-dismiss the alert after 5 seconds
                            setTimeout(() => {
                                pickupCard.find('.alert').alert('close');
                            }, 5000);
                        } else {
                            // Show error message
                            const errorAlert = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                ${response.message || 'Failed to assign driver'}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>`;
                            pickupCard.find('.card-body').prepend(errorAlert);
                            submitButton.prop('disabled', false).html('<i class="bi bi-person-check"></i> Assign');
                        }
                    },
                    error: function() {
                        // Show error message
                        const errorAlert = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            An error occurred. Please try again.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>`;
                        pickupCard.find('.card-body').prepend(errorAlert);
                        submitButton.prop('disabled', false).html('<i class="bi bi-person-check"></i> Assign');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'warning';
        case 'assigned': return 'info';
        case 'in_transit': return 'primary';
        case 'picked_up': return 'success';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>