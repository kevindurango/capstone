<?php
session_start();

if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Database Connection
$database = new Database();
$conn = $database->connect();
$log = new Log();

// Run debug functions
$manager_user_id = $_SESSION['manager_user_id'] ?? null;

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and Filter Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Update Pickup Logic
if (isset($_POST['update_pickup'])) {
    $pickup_id = $_POST['pickup_id'];
    $pickup_status = $_POST['pickup_status'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_notes = $_POST['pickup_notes'];
    $contact_person = $_POST['contact_person'] ?? null;

    // Get the old pickup data for logging
    $oldDataQuery = "SELECT * FROM pickups WHERE pickup_id = :pickup_id";
    $oldDataStmt = $conn->prepare($oldDataQuery);
    $oldDataStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    $oldDataStmt->execute();
    $oldData = $oldDataStmt->fetch(PDO::FETCH_ASSOC);

    // Update Query - Modified for office pickup
    $updateQuery = "UPDATE pickups
                   SET pickup_status = :pickup_status,
                       pickup_date = :pickup_date,
                       pickup_notes = :pickup_notes,
                       contact_person = :contact_person,
                       office_location = 'Municipal Agriculture Office'
                   WHERE pickup_id = :pickup_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':pickup_status', $pickup_status);
    $updateStmt->bindParam(':pickup_date', $pickup_date);
    $updateStmt->bindParam(':pickup_notes', $pickup_notes);
    $updateStmt->bindParam(':contact_person', $contact_person);
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Updated pickup #$pickup_id");
        }
        $_SESSION['message'] = "Pickup details updated successfully!";
        $_SESSION['message_type'] = 'success';
    }
}

// Export to CSV functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set default dates if not provided
    $exportStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $exportEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $exportStatus = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pickup_report_' . $exportStartDate . '_to_' . $exportEndDate . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Pickup ID', 'Order ID', 'Customer', 'Status', 'Pickup Date', 'Location', 'Contact Person', 'Notes']);
    
    // Prepare the query with filters
    $exportQuery = "SELECT p.*, o.order_id, o.order_date, c.username AS consumer_name
                    FROM pickups p
                    JOIN orders o ON p.order_id = o.order_id
                    JOIN users c ON o.consumer_id = c.user_id
                    WHERE 1=1";
    
    if ($exportStatus) {
        $exportQuery .= " AND p.pickup_status = :status";
    }
    
    $exportQuery .= " AND DATE(p.pickup_date) BETWEEN :startDate AND :endDate
                      ORDER BY p.pickup_date DESC";
    
    $exportStmt = $conn->prepare($exportQuery);
    if ($exportStatus) {
        $exportStmt->bindValue(':status', $exportStatus, PDO::PARAM_STR);
    }
    $exportStmt->bindValue(':startDate', $exportStartDate, PDO::PARAM_STR);
    $exportStmt->bindValue(':endDate', $exportEndDate, PDO::PARAM_STR);
    $exportStmt->execute();
    
    // Write data rows
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['pickup_id'],
            $row['order_id'],
            $row['consumer_name'],
            ucfirst($row['pickup_status']),
            date("Y-m-d H:i", strtotime($row['pickup_date'])),
            $row['office_location'] ?: 'Municipal Agriculture Office',
            $row['contact_person'] ?: 'Not specified',
            $row['pickup_notes'] ?: ''
        ]);
    }
    
    // Log the export action
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Exported pickup report from $exportStartDate to $exportEndDate");
    }
    
    // Close the output stream
    fclose($output);
    exit;
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
$assignedQuery = "SELECT COUNT(*) FROM pickups WHERE pickup_status = 'assigned'";
$assignedStmt = $conn->query($assignedQuery);
$assignedPickups = $assignedStmt->fetchColumn();

// Fetch Pickups Query with Filtering
$query = "SELECT p.*, o.order_id, o.order_date, c.username AS consumer_name
          FROM pickups p
          JOIN orders o ON p.order_id = o.order_id
          JOIN users c ON o.consumer_id = c.user_id
          WHERE 1=1";
if ($search) {
    $query .= " AND (o.order_id LIKE :search OR c.username LIKE :search)";
}
if ($statusFilter) {
    $query .= " AND p.pickup_status = :statusFilter";
}
if ($startDateFilter && $endDateFilter) {
    $query .= " AND DATE(p.pickup_date) BETWEEN :startDate AND :endDate";
} else if ($startDateFilter) {
    $query .= " AND DATE(p.pickup_date) >= :startDate";
} else if ($endDateFilter) {
    $query .= " AND DATE(p.pickup_date) <= :endDate";
}
$query .= " ORDER BY p.pickup_date DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
if ($statusFilter) {
    $stmt->bindValue(':statusFilter', $statusFilter, PDO::PARAM_STR);
}
if ($startDateFilter && $endDateFilter) {
    $stmt->bindValue(':startDate', $startDateFilter, PDO::PARAM_STR);
    $stmt->bindValue(':endDate', $endDateFilter, PDO::PARAM_STR);
} else if ($startDateFilter) {
    $stmt->bindValue(':startDate', $startDateFilter, PDO::PARAM_STR);
} else if ($endDateFilter) {
    $stmt->bindValue(':endDate', $endDateFilter, PDO::PARAM_STR);
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
        }
        
        /* Card styling fixes */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s ease;
            height: 100%;
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
        
        /* Status label styling */
        .status-label {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-scheduled {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Pickup card improvements */
        .pickup-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .pickup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .pickup-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .pickup-card .card-body {
            padding: 15px;
        }
        
        .pickup-card .card-body p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .pickup-card .card-body p i {
            width: 20px;
            margin-right: 8px;
            color: #6c757d;
        }
        
        .pickup-notes {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        /* Filter card styling */
        .filter-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .filter-card .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .filter-card label {
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .filter-card .form-control {
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        
        .filter-card .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .active-filters {
            border-top: 1px solid #e9ecef;
        }
        
        .badge-info {
            background-color: #17a2b8;
        }
        
        /* Export section styling */
        .export-section {
            border: 1px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .export-btn {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        /* Improved Export section styling */
        .export-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px;
            background-color: #f8fff9;
            border-left: 4px solid #28a745;
        }
        
        .export-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 12px 15px;
        }
        
        .export-btn {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            padding: 8px 16px;
        }
        
        .export-btn:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
        }
        
        /* Improved section headings */
        .section-heading {
            margin-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
        }
        
        .section-heading i {
            color: #28a745;
        }
        
        .filter-card {
            /* ...existing filter-card styles... */
            margin-bottom: 20px;
        }
        
        /* Improved statistics card design */
        .stats-row {
            margin-bottom: 25px;
        }
        
        /* ...existing styles... */
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
                <div class="row stats-row">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Total Pickups</p>
                                <p class="count"><?= $totalPickups ?></p>
                            </div>
                            <div class="icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Pending Pickups</p>
                                <p class="count"><?= $pendingCount ?></p>
                            </div>
                            <div class="icon text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Today's Pickups</p>
                                <p class="count"><?= $todayPickups ?></p>
                            </div>
                            <div class="icon text-success">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Scheduled Pickups</p>
                                <p class="count"><?= $assignedPickups ?></p>
                            </div>
                            <div class="icon text-info">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Management Tools Section -->
                <h4 class="section-heading"><i class="bi bi-gear"></i> Management Tools</h4>
                
                <!-- Export Functionality - Moved Above -->
                <div class="card export-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-arrow-down text-success"></i> Export Pickup Data
                        </h5>
                    </div>
                    <div class="card-body py-3">
                        <form method="GET" action="" id="exportForm">
                            <input type="hidden" name="export" value="csv">
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label for="export_start_date" class="small font-weight-bold">Start Date:</label>
                                    <input type="date" class="form-control" id="export_start_date" name="start_date" 
                                           value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="export_end_date" class="small font-weight-bold">End Date:</label>
                                    <input type="date" class="form-control" id="export_end_date" name="end_date" 
                                           value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="export_status" class="small font-weight-bold">Status Filter:</label>
                                    <select class="form-control" id="export_status" name="status">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= htmlspecialchars($status) ?>">
                                                <?= ucfirst(htmlspecialchars($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button type="submit" class="btn export-btn btn-block">
                                        <i class="bi bi-file-earmark-arrow-down"></i> Export to CSV
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Download pickup data as a CSV file based on date range and status.</small>
                        </form>
                    </div>
                </div>
                
                <!-- Search and Filter Controls -->
                <div class="card filter-card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel"></i> Search & Filter Pickups
                            <?php if($search || $statusFilter || $startDateFilter || $endDateFilter): ?>
                                <span class="badge badge-primary ml-2">Active</span>
                            <?php endif; ?>
                        </h5>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                            <i class="bi bi-chevron-up" id="filterToggleIcon"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body py-3">
                            <form method="GET" action="">
                                <div class="row">
                                    <!-- Search Field -->
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">Search</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            </div>
                                            <input type="text" class="form-control" placeholder="Order/Customer" 
                                                   name="search" value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    <!-- Status Filter -->
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= htmlspecialchars($status) ?>" 
                                                        <?= $statusFilter === $status ? 'selected' : '' ?>>
                                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Date Range Filters -->
                                    <div class="col-md-2 mb-2">
                                        <label class="small font-weight-bold">From Date</label>
                                        <input type="date" class="form-control" placeholder="From Date"
                                               name="start_date" value="<?= htmlspecialchars($startDateFilter) ?>">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="small font-weight-bold">To Date</label>
                                        <input type="date" class="form-control" placeholder="To Date"
                                               name="end_date" value="<?= htmlspecialchars($endDateFilter) ?>">
                                    </div>
                                    <!-- Action Buttons -->
                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                        <div class="btn-group w-100">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-filter"></i> Apply
                                            </button>
                                            <a href="manager-pickup-management.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Active Filters Display - Compact -->
                                <?php if($search || $statusFilter || $startDateFilter || $endDateFilter): ?>
                                <div class="mt-2 pt-2 active-filters">
                                    <small class="text-muted font-weight-bold">Active Filters:</small>
                                    <?php if($search): ?>
                                        <span class="badge badge-info mr-1"><?= htmlspecialchars($search) ?></span>
                                    <?php endif; ?>
                                    <?php if($statusFilter): ?>
                                        <span class="badge badge-info mr-1"><?= ucfirst(htmlspecialchars($statusFilter)) ?></span>
                                    <?php endif; ?>
                                    <?php if($startDateFilter): ?>
                                        <span class="badge badge-info mr-1">From: <?= htmlspecialchars($startDateFilter) ?></span>
                                    <?php endif; ?>
                                    <?php if($endDateFilter): ?>
                                        <span class="badge badge-info mr-1">To: <?= htmlspecialchars($endDateFilter) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Pickups List Section -->
                <h4 class="section-heading"><i class="bi bi-box-seam"></i> Pickups</h4>
                
                <!-- Pickups Content -->
                <?php if (count($pickups) > 0): ?>
                    <div class="row">
                        <?php foreach ($pickups as $pickup): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card pickup-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="font-weight-bold">
                                            <i class="bi bi-box"></i> Pickup #<?= htmlspecialchars($pickup['pickup_id']) ?>
                                        </span>
                                        <span class="status-label status-<?= strtolower($pickup['pickup_status']) ?>">
                                            <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p><i class="bi bi-receipt"></i> <strong>Order:</strong> <?= htmlspecialchars($pickup['order_id']) ?></p>
                                        <p><i class="bi bi-person"></i> <strong>Customer:</strong> <?= htmlspecialchars($pickup['consumer_name']) ?></p>
                                        <p><i class="bi bi-calendar-event"></i> <strong>Date:</strong> <?= htmlspecialchars(date("F j, Y, g:i A", strtotime($pickup['pickup_date']))) ?></p>
                                        <p><i class="bi bi-geo-alt"></i> <strong>Location:</strong> Municipal Agriculture Office</p>
                                        <p><i class="bi bi-person-badge"></i> <strong>Contact:</strong> <?= htmlspecialchars($pickup['contact_person'] ?? 'Not specified') ?></p>
                                        <div class="pickup-notes">
                                            <i class="bi bi-card-text"></i> <strong>Notes:</strong><br>
                                            <?= nl2br(htmlspecialchars($pickup['pickup_notes'] ?: 'No notes available')) ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 d-flex justify-content-start">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary edit-pickup-btn" 
                                                    data-toggle="modal" 
                                                    data-target="#editPickupModal" 
                                                    data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>"
                                                    data-pickup-status="<?= htmlspecialchars($pickup['pickup_status']) ?>"
                                                    data-pickup-date="<?= htmlspecialchars($pickup['pickup_date']) ?>"
                                                    data-pickup-notes="<?= htmlspecialchars($pickup['pickup_notes']) ?>"
                                                    data-contact-person="<?= htmlspecialchars($pickup['contact_person'] ?? '') ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-info view-order-btn"
                                                    data-toggle="modal"
                                                    data-target="#viewOrderModal"
                                                    data-order-id="<?= htmlspecialchars($pickup['order_id']) ?>">
                                                <i class="bi bi-eye"></i> View Order
                                            </button>
                                        </div>
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
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>"><?= $totalPages ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <p class="text-center text-muted">Showing page <?= $page ?> of <?= $totalPages ?></p>
                    </div>
                <?php endif; ?>
            </main>
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
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pickup_date"><i class="bi bi-calendar"></i> Pickup Date</label>
                            <input type="datetime-local" class="form-control" id="pickup_date" name="pickup_date">
                        </div>
                        <div class="form-group">
                            <label for="pickup_notes"><i class="bi bi-card-text"></i> Pickup Notes</label>
                            <textarea class="form-control" id="pickup_notes" name="pickup_notes" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="contact_person"><i class="bi bi-person"></i> Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
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

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" role="dialog" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel"><i class="bi bi-receipt"></i> Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printOrderBtn">
                        <i class="bi bi-printer"></i> Print
                    </button>
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
                const pickupNotes = $(this).data('pickup-notes');
                const contactPerson = $(this).data('contact-person');

                // Populate the modal form
                $('#pickup_id').val(pickupId);
                $('#pickup_status').val(pickupStatus);
                $('#pickup_date').val(formatDateForInput(pickupDate));
                $('#pickup_notes').val(pickupNotes);
                $('#contact_person').val(contactPerson);
            });

            // Handle view order button click
            $('.view-order-btn').click(function () {
                const orderId = $(this).data('order-id');
                
                // Show loading message in the modal
                $('#orderDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="mt-2">Loading order details...</p></div>');
                
                // Fetch order details via AJAX
                $.ajax({
                    url: 'fetch-order-details.php',
                    type: 'GET',
                    data: {
                        order_id: orderId
                    },
                    success: function(response) {
                        $('#orderDetails').html(response);
                    },
                    error: function() {
                        $('#orderDetails').html('<div class="alert alert-danger">Failed to load order details. Please try again.</div>');
                    }
                });
            });
            
            // Handle print button click
            $('#printOrderBtn').click(function() {
                const printContent = document.getElementById('orderDetails').innerHTML;
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                
                printWindow.document.open();
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Order Details</title>
                        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            .print-header { text-align: center; margin-bottom: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="print-header">
                            <h2>Municipal Agriculture Office</h2>
                            <h4>Order Details</h4>
                        </div>
                        ${printContent}
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            });

            // Auto-dismiss alerts
            setTimeout(function () {
                $('.alert').alert('close');
            }, 5000);

            // Manage the collapse toggle icon
            $('#filterCollapse').on('show.bs.collapse', function () {
                $('#filterToggleIcon').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            });
            
            $('#filterCollapse').on('hide.bs.collapse', function () {
                $('#filterToggleIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
            });
        });
    </script>
</body>
</html>