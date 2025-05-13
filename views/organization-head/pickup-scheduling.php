<?php
// Start session and check login
session_start();
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

// Include necessary models
require_once '../../models/Database.php';
require_once '../../models/Log.php';

$database = new Database();
$conn = $database->connect();
$logClass = new Log();

// Get Organization Head User ID
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and Filter Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'pending';
$searchLocation = isset($_GET['search_location']) ? $_GET['search_location'] : '';
$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Export to CSV functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set default dates if not provided
    $exportStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $exportEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $exportStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
    
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
            $row['pickup_location'] ?: 'Municipal Agriculture Office',
            $row['contact_person'] ?: 'Not specified',
            $row['pickup_notes'] ?: ''
        ]);
    }
    
    // Log the export action
    if ($organization_head_user_id) {
        $logClass->logActivity($organization_head_user_id, "Exported pickup report from $exportStartDate to $exportEndDate");
    }
    
    // Close the output stream
    fclose($output);
    exit;
}

// Build the query with filters
$pickupQuery = "SELECT p.*, o.order_date, c.username AS consumer_name
                FROM pickups p
                JOIN orders o ON p.order_id = o.order_id
                JOIN users c ON o.consumer_id = c.user_id
                WHERE 1=1";

// Apply filters
if ($search) {
    $pickupQuery .= " AND (o.order_id LIKE :search OR c.username LIKE :search)";
}
if ($statusFilter) {
    $pickupQuery .= " AND p.pickup_status = :statusFilter";
}
if ($searchLocation) {
    $pickupQuery .= " AND p.pickup_location LIKE :searchLocation";
}
if ($startDateFilter && $endDateFilter) {
    $pickupQuery .= " AND DATE(p.pickup_date) BETWEEN :startDate AND :endDate";
} else if ($startDateFilter) {
    $pickupQuery .= " AND DATE(p.pickup_date) >= :startDate";
} else if ($endDateFilter) {
    $pickupQuery .= " AND DATE(p.pickup_date) <= :endDate";
}

// Count total pickups for pagination
$countQuery = $pickupQuery;
$countQuery = "SELECT COUNT(*) FROM ($countQuery) as count_table";
$countStmt = $conn->prepare($countQuery);

// Add search parameters
if ($search) {
    $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
if ($statusFilter) {
    $countStmt->bindValue(':statusFilter', $statusFilter, PDO::PARAM_STR);
}
if ($searchLocation) {
    $countStmt->bindValue(':searchLocation', "%$searchLocation%", PDO::PARAM_STR);
}
if ($startDateFilter && $endDateFilter) {
    $countStmt->bindValue(':startDate', $startDateFilter, PDO::PARAM_STR);
    $countStmt->bindValue(':endDate', $endDateFilter, PDO::PARAM_STR);
} else if ($startDateFilter) {
    $countStmt->bindValue(':startDate', $startDateFilter, PDO::PARAM_STR);
} else if ($endDateFilter) {
    $countStmt->bindValue(':endDate', $endDateFilter, PDO::PARAM_STR);
}

$countStmt->execute();
$totalPickups = $countStmt->fetchColumn();

// Add pagination to the main query
$pickupQuery .= " ORDER BY p.pickup_date DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($pickupQuery);

// Add search parameters
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
if ($statusFilter) {
    $stmt->bindValue(':statusFilter', $statusFilter, PDO::PARAM_STR);
}
if ($searchLocation) {
    $stmt->bindValue(':searchLocation', "%$searchLocation%", PDO::PARAM_STR);
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

// Calculate total pages for pagination
$totalPages = ceil($totalPickups / $limit);

// Get pickup statistics
$statsQuery = "SELECT 
    COUNT(CASE WHEN pickup_status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN pickup_status = 'scheduled' AND DATE(pickup_date) = CURDATE() THEN 1 END) as scheduled_today,
    COUNT(CASE WHEN pickup_status = 'completed' AND DATE(pickup_date) = CURDATE() THEN 1 END) as completed_today,
    COUNT(*) as total_pickups
FROM pickups";

$stmt = $conn->prepare($statsQuery);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$pendingCount = $stats['pending_count'];
$scheduledToday = $stats['scheduled_today'];
$completedToday = $stats['completed_today'];
$totalPickupCount = $stats['total_pickups'];

// Get distinct statuses for filter
$statusQuery = "SELECT DISTINCT pickup_status FROM pickups ORDER BY pickup_status";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->execute();
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

// Helper function for pickup status colors
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':
            return 'pending';
        case 'completed':
            return 'completed';
        case 'scheduled':
            return 'scheduled';
        case 'in transit':
        case 'in-transit':
            return 'in-transit';
        case 'canceled':
        case 'cancelled':
            return 'canceled';
        default:
            return 'info';
    }
}

// Get card header colors for different pickup statuses with gradients
$statusColors = [
    'pending' => 'linear-gradient(135deg, #fff3cd 0%, #ffffff 100%)',    // Light yellow to white gradient
    'scheduled' => 'linear-gradient(135deg, #d1ecf1 0%, #ffffff 100%)',  // Light blue to white gradient
    'completed' => 'linear-gradient(135deg, #d4edda 0%, #ffffff 100%)',  // Light green to white gradient
    'cancelled' => 'linear-gradient(135deg, #f8d7da 0%, #ffffff 100%)',  // Light red to white gradient
    'assigned' => 'linear-gradient(135deg, #e2e3e5 0%, #ffffff 100%)',   // Light gray to white gradient
    'default' => 'linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%)'     // Default light gradient
];

// Include the sidebar
include '../global/organization-head-sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Scheduling</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }
        
        /* Custom styles for pickup scheduling page */
        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
        }
        
        .filter-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #1a8754;
        }
        
        .action-btn {
            border-radius: 50px;
            padding: 6px 15px;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .pickup-empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .pickup-empty-state i {
            font-size: 3.5rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        /* Status label styling */
        .status-label {
            padding: 5px 12px;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
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
        
        .status-cancelled, .status-canceled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-in-transit {
            background-color: #cce5ff;
            color: #004085;
        }
        
        /* Pickup card improvements */
        .pickup-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .pickup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 18px rgba(0,0,0,0.12);
        }
        
        .pickup-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 18px;
            font-weight: 600;
        }
        
        .pickup-card .card-body {
            padding: 18px;
        }
        
        .pickup-card .card-body p {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        .pickup-card .card-body p i {
            width: 24px;
            margin-right: 10px;
            color: #28a745;
        }
        
        .pickup-card .card-footer {
            background-color: transparent;
            padding: 12px 18px 18px;
            border-top: none;
        }
        
        /* Export card styling */
        .export-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 25px;
            background-color: #f8fff9;
            border-left: 5px solid #28a745;
        }
        
        .export-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        
        .export-btn {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .export-btn:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Avatar circle for customer display */
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Pickup date badge */
        .pickup-date-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .pickup-date-badge i {
            margin-right: 6px;
            color: #28a745;
        }
        
        /* Table styling */
        .table-container {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table thead th {
            background: #28a745;
            border-bottom: 2px solid #219a3a;
            color: white;
            font-weight: 600;
            padding: 12px 15px;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
        }
        
        /* Center align action buttons in table - more specific selector for higher priority */
        .table td:last-child {
            text-align: center !important;
        }
        
        /* Ensure the action button is centered and properly sized within its cell */
        .table td .action-btn {
            display: inline-block;
            min-width: 80px;
            margin: 0 auto;
        }
        
        /* Center align action buttons in table */
        .table td:last-child {
            text-align: center;
        }
        
        /* Pagination */
        .pagination-container {
            margin-top: 30px;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: #343a40;
            transition: all 0.2s;
        }
        
        .pagination .page-link:hover {
            background-color: #e9ecef;
            color: #28a745;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Badge status */
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-scheduled {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-canceled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-in-transit {
            background-color: #cce5ff;
            color: #004085;
        }
        
        /* Action row with gradient */
        .action-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            position: relative;
        }

        .action-row:after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: linear-gradient(90deg, #28a745, transparent);
        }
        
        /* Tab navigation */
        .pickup-tabs {
            margin-bottom: 20px;
        }
        
        .pickup-tabs .nav-link {
            font-weight: 500;
            color: #495057;
            border: none;
            padding: 10px 20px;
            border-radius: 0;
            position: relative;
        }
        
        .pickup-tabs .nav-link.active {
            color: #28a745;
            background-color: transparent;
            font-weight: 600;
        }
        
        .pickup-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #28a745;
            border-radius: 3px;
        }
        
        .pickup-tabs .nav-link:hover {
            color: #28a745;
        }
        
        .pickup-tabs .badge {
            margin-left: 5px;
            font-size: 0.7rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-building"></i> ORGANIZATION MANAGEMENT SYSTEM
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <?php include '../global/organization-head-sidebar.php'; ?>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 dashboard-main" id="main-content">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="organization-head-dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pickup Scheduling</li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2"><i class="bi bi-calendar-check"></i> Pickup Scheduling</h1>
                        <p class="text-muted">Schedule and manage pickup operations</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-success mr-2" data-toggle="modal" data-target="#exportModal">
                            <i class="bi bi-file-earmark-excel"></i> Export Data
                        </button>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#schedulingHelpModal">
                            <i class="bi bi-question-circle"></i> Help
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <!-- Advanced Filter and Search -->
                <div class="card filter-card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel"></i> Search & Filter Pickups
                            <?php if($search || $statusFilter || $startDateFilter || $endDateFilter || $searchLocation): ?>
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
                                        <select class="form-control" name="filter_status">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Location Search -->
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">Location</label>
                                        <input type="text" class="form-control" placeholder="Enter location"
                                               name="search_location" value="<?= htmlspecialchars($searchLocation) ?>">
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="col-md-3 mb-2 d-flex align-items-end">
                                        <div class="btn-group w-100">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-filter"></i> Apply
                                            </button>
                                            <a href="pickup-scheduling.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Date Range Section -->
                                <div class="row mt-3">
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">From Date</label>
                                        <input type="date" class="form-control" placeholder="From Date"
                                               name="start_date" value="<?= htmlspecialchars($startDateFilter) ?>">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">To Date</label>
                                        <input type="date" class="form-control" placeholder="To Date"
                                               name="end_date" value="<?= htmlspecialchars($endDateFilter) ?>">
                                    </div>
                                </div>
                                
                                <!-- Active Filters Display -->
                                <?php if($search || $statusFilter || $startDateFilter || $endDateFilter || $searchLocation): ?>
                                <div class="mt-2 pt-2 active-filters">
                                    <small class="text-muted font-weight-bold">Active Filters:</small>
                                    <?php if($search): ?>
                                        <span class="badge badge-info mr-1"><?= htmlspecialchars($search) ?></span>
                                    <?php endif; ?>
                                    <?php if($statusFilter): ?>
                                        <span class="badge badge-info mr-1"><?= ucfirst(htmlspecialchars($statusFilter)) ?></span>
                                    <?php endif; ?>
                                    <?php if($searchLocation): ?>
                                        <span class="badge badge-info mr-1">Location: <?= htmlspecialchars($searchLocation) ?></span>
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

                <!-- Quick Statistics -->
                <div class="row stats-row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Total Pickups</p>
                                <p class="count"><?= $totalPickupCount ?? 0 ?></p>
                            </div>
                            <div class="icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Pending Pickups</p>
                                <p class="count"><?= $pendingCount ?? 0 ?></p>
                            </div>
                            <div class="icon text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Scheduled Today</p>
                                <p class="count"><?= $scheduledToday ?? 0 ?></p>
                            </div>
                            <div class="icon text-info">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div>
                                <p class="title">Completed Today</p>
                                <p class="count"><?= $completedToday ?? 0 ?></p>
                            </div>
                            <div class="icon text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickups Display Section with Tabs -->
                <div class="action-row">
                    <h4 class="mb-0"><i class="bi bi-box-seam text-success"></i> Pickups Management</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-success" onclick="refreshTable()">
                            <i class="bi bi-arrow-repeat"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="toggleViewBtn">
                            <i class="bi bi-grid"></i> <span id="viewToggleText">Card View</span>
                        </button>
                    </div>
                </div>

                <!-- Pickup Tabs -->
                <ul class="nav nav-tabs pickup-tabs" id="pickupTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= empty($statusFilter) || $statusFilter === 'pending' ? 'active' : '' ?>" 
                           href="?filter_status=pending">
                           <i class="bi bi-hourglass-split"></i> Pending 
                           <span class="badge badge-warning"><?= $pendingCount ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter === 'scheduled' ? 'active' : '' ?>" 
                           href="?filter_status=scheduled">
                           <i class="bi bi-calendar-check"></i> Scheduled
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter === 'completed' ? 'active' : '' ?>" 
                           href="?filter_status=completed">
                           <i class="bi bi-check-circle"></i> Completed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter === 'canceled' ? 'active' : '' ?>" 
                           href="?filter_status=canceled">
                           <i class="bi bi-x-circle"></i> Canceled
                        </a>
                    </li>
                </ul>

                <!-- Table View -->
                <div id="tableView" class="view-container">
                    <div class="table-container">
                        <?php if (!empty($pickups)): ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pickup ID</th>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Pickup Date</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pickups as $pickup): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pickup['pickup_id']) ?></td>
                                            <td><?= htmlspecialchars($pickup['order_id']) ?></td>
                                            <td><?= htmlspecialchars($pickup['consumer_name']) ?></td>
                                            <td>
                                                <span class="badge badge-status badge-<?= getStatusBadgeClass($pickup['pickup_status']) ?>">
                                                    <?= ucfirst($pickup['pickup_status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(date("M j, Y g:i A", strtotime($pickup['pickup_date']))) ?></td>
                                            <td><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($pickup['pickup_location']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary action-btn view-pickup-btn" 
                                                        data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>" 
                                                        data-toggle="modal" data-target="#viewPickupModal">
                                                    <i class="bi bi-eye-fill"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="pickup-empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h5>No pickups found</h5>
                                <p class="text-muted">No pickups match your current filter criteria.</p>
                                <a href="pickup-scheduling.php" class="btn btn-outline-primary mt-2">
                                    <i class="bi bi-arrow-repeat"></i> Reset Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card View -->
                <div id="cardView" class="view-container" style="display: none;">
                    <?php if (!empty($pickups)): ?>
                        <div class="row">
                            <?php foreach ($pickups as $pickup): 
                                // Determine appropriate status colors and icons
                                $statusClass = 'secondary';
                                $statusIcon = 'question-circle';
                                
                                switch(strtolower($pickup['pickup_status'])) {
                                    case 'pending':
                                        $statusClass = 'warning';
                                        $statusIcon = 'hourglass-split';
                                        break;
                                    case 'scheduled':
                                        $statusClass = 'info';
                                        $statusIcon = 'calendar-check';
                                        break;
                                    case 'completed':
                                        $statusClass = 'success';
                                        $statusIcon = 'check-circle';
                                        break;
                                    case 'cancelled':
                                    case 'canceled':
                                        $statusClass = 'danger';
                                        $statusIcon = 'x-circle';
                                        break;
                                }
                                
                                // Format dates
                                $pickupDate = new DateTime($pickup['pickup_date']);
                                $now = new DateTime();
                                $isToday = $pickupDate->format('Y-m-d') === $now->format('Y-m-d');
                                $isPast = $pickupDate < $now && !$isToday;
                                $dateClass = $isToday ? 'text-success font-weight-bold' : ($isPast ? 'text-danger' : '');
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card pickup-card">
                                        <div class="card-header d-flex justify-content-between align-items-center" style="background-color: <?= $statusColors[$pickup['pickup_status']] ?? $statusColors['default'] ?>;">
                                            <span>
                                                <span class="badge badge-pill badge-light mr-2">#<?= htmlspecialchars($pickup['pickup_id']) ?></span>
                                                Order #<?= htmlspecialchars($pickup['order_id']) ?>
                                            </span>
                                            <span class="badge badge-pill badge-<?= $statusClass ?>">
                                                <i class="bi bi-<?= $statusIcon ?>"></i> <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="media mb-3">
                                                <div class="mr-3">
                                                    <div class="avatar-circle">
                                                        <?= strtoupper(substr($pickup['consumer_name'] ?? 'U', 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div class="media-body">
                                                    <h6 class="mt-0 mb-1"><?= htmlspecialchars($pickup['consumer_name']) ?></h6>
                                                    <small class="text-muted">
                                                        Customer
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="pickup-info">
                                                <div class="pickup-date-badge <?= $dateClass ?>">
                                                    <i class="bi bi-calendar-event"></i>
                                                    <?= $isToday ? 'Today' : htmlspecialchars($pickupDate->format("M j, Y")) ?>
                                                    at <?= htmlspecialchars($pickupDate->format("g:i A")) ?>
                                                    <?= $isPast ? '<span class="badge badge-danger ml-1">Overdue</span>' : '' ?>
                                                </div>
                                                
                                                <div class="pickup-location mt-2">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <span class="text-muted"><?= htmlspecialchars($pickup['pickup_location']) ?></span>
                                                </div>
                                            </div>

                                            <?php if(!empty($pickup['pickup_notes'])): ?>
                                            <div class="pickup-notes mt-3">
                                                <i class="bi bi-journal-text"></i> <strong>Notes:</strong>
                                                <p class="mb-0 text-truncate notes-text" title="<?= htmlspecialchars($pickup['pickup_notes']) ?>">
                                                    <?= htmlspecialchars($pickup['pickup_notes']) ?>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <button class="btn btn-sm btn-info btn-block view-pickup-btn" 
                                                    data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>" 
                                                    data-toggle="modal" data-target="#viewPickupModal">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pickup-empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5>No pickups found</h5>
                            <p class="text-muted">No pickups match your current filter criteria.</p>
                            <a href="pickup-scheduling.php" class="btn btn-outline-primary mt-2">
                                <i class="bi bi-arrow-repeat"></i> Reset Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&filter_status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&search_location=<?= urlencode($searchLocation) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&filter_status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&search_location=<?= urlencode($searchLocation) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&filter_status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&search_location=<?= urlencode($searchLocation) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
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

    <!-- View Pickup Modal -->
    <div class="modal fade" id="viewPickupModal" tabindex="-1" role="dialog" aria-labelledby="viewPickupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPickupModalLabel"><i class="bi bi-info-circle"></i> Pickup Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="pickupDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printPickupBtn">
                        <i class="bi bi-printer"></i> Print Details
                    </button>
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
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel"><i class="bi bi-file-earmark-excel"></i> Export Pickup Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="" id="exportForm">
                        <input type="hidden" name="export" value="csv">
                        
                        <div class="form-group">
                            <label for="export_status">Status Filter:</label>
                            <select class="form-control" id="export_status" name="filter_status">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>">
                                        <?= ucfirst(htmlspecialchars($status)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Select a status to filter the exported data.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="export_start_date">Start Date:</label>
                            <input type="date" class="form-control" id="export_start_date" name="start_date" 
                                   value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="export_end_date">End Date:</label>
                            <input type="date" class="form-control" id="export_end_date" name="end_date" 
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <p class="mb-2">Export Format: <strong>CSV</strong> <span class="badge badge-info">Excel Compatible</span></p>
                            <small class="form-text text-muted">Data will be exported in CSV format which can be opened with Excel or other spreadsheet applications.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="document.getElementById('exportForm').submit();">
                        <i class="bi bi-download"></i> Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Modal -->
    <div class="modal fade" id="schedulingHelpModal" tabindex="-1" role="dialog" aria-labelledby="schedulingHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="schedulingHelpModalLabel"><i class="bi bi-question-circle"></i> Pickup Scheduling Help</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6><i class="bi bi-info-circle text-primary"></i> About Pickup Scheduling</h6>
                    <p>This page allows you to manage and monitor pickup operations.</p>
                    
                    <h6 class="mt-4"><i class="bi bi-card-checklist text-success"></i> Status Guide</h6>
                    <ul class="list-unstyled">
                        <li><span class="badge badge-pending">Pending</span> - Awaiting processing</li>
                        <li><span class="badge badge-scheduled">Scheduled</span> - Scheduled for pickup</li>
                        <li><span class="badge badge-completed">Completed</span> - Pickup has been completed</li>
                        <li><span class="badge badge-canceled">Canceled</span> - Pickup was canceled</li>
                    </ul>
                    
                    <h6 class="mt-4"><i class="bi bi-tools text-info"></i> Tips</h6>
                    <ul>
                        <li>Use filters to find specific pickups by status, date, or location</li>
                        <li>Click <i class="bi bi-eye"></i> View to see all pickup details</li>
                        <li>Toggle between table and card views using the view toggle button</li>
                        <li>Export data to CSV for reporting and analysis</li>
                        <li>Click on the tab navigation to quickly filter by status</li>
                    </ul>
                    
                    <h6 class="mt-4"><i class="bi bi-file-earmark-excel text-success"></i> Exporting Data</h6>
                    <p>To export pickup data:</p>
                    <ol>
                        <li>Click the "Export Data" button in the top right corner</li>
                        <li>Select optional filters such as status and date range</li>
                        <li>Click "Export Data" to download the CSV file</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            // View Pickup Details with enhanced error handling
            $('.view-pickup-btn').click(function() {
                const pickupId = $(this).data('pickup-id');
                $('#pickupDetailsContent').html('<div class="text-center py-4"><i class="bi bi-hourglass-split fa-spin"></i> Loading pickup details...</div>');
                
                // Add CSRF token to the request if it's required
                const csrfToken = '<?= isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : "" ?>';
                
                $.ajax({
                    url: '../../controllers/AjaxController.php',
                    method: 'POST',
                    data: {
                        action: 'getPickupDetails',
                        pickup_id: pickupId,
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        if (response.trim() === '') {
                            $('#pickupDetailsContent').html('<div class="alert alert-danger">Received empty response from server.</div>');
                            return;
                        }
                        
                        try {
                            // Check if the response is JSON (error message)
                            const jsonResponse = JSON.parse(response);
                            if (jsonResponse.success === false) {
                                $('#pickupDetailsContent').html('<div class="alert alert-danger">' + jsonResponse.message + '</div>');
                            } else {
                                $('#pickupDetailsContent').html(response);
                            }
                        } catch(e) {
                            // Not JSON, treat as HTML content
                            $('#pickupDetailsContent').html(response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.log("Response:", xhr.responseText);
                        $('#pickupDetailsContent').html('<div class="alert alert-danger">Failed to load pickup details: ' + error + '</div>');
                    }
                });
            });

            // Toggle between table and card views
            $('#toggleViewBtn').click(function() {
                const tableView = $('#tableView');
                const cardView = $('#cardView');
                const viewToggleText = $('#viewToggleText');
                const toggleIcon = $(this).find('i');
                
                if (tableView.is(':visible')) {
                    tableView.hide();
                    cardView.show();
                    viewToggleText.text('Table View');
                    toggleIcon.removeClass('bi-grid').addClass('bi-table');
                    localStorage.setItem('pickupViewPreference', 'card');
                } else {
                    cardView.hide();
                    tableView.show();
                    viewToggleText.text('Card View');
                    toggleIcon.removeClass('bi-table').addClass('bi-grid');
                    localStorage.setItem('pickupViewPreference', 'table');
                }
            });
            
            // Load previously saved view preference
            const savedViewPreference = localStorage.getItem('pickupViewPreference');
            if (savedViewPreference === 'card') {
                $('#tableView').hide();
                $('#cardView').show();
                $('#viewToggleText').text('Table View');
                $('#toggleViewBtn i').removeClass('bi-grid').addClass('bi-table');
            }

            // Print functionality for pickup details
            $('#printPickupBtn').click(function() {
                const printContent = document.getElementById('pickupDetailsContent').innerHTML;
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                
                printWindow.document.open();
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Pickup Details</title>
                        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            .print-header { text-align: center; margin-bottom: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="print-header">
                            <h2>Organization Management System</h2>
                            <h4>Pickup Details</h4>
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
            
            // Datepicker initialization
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
            
            // Auto-dismiss alerts
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Filter collapse toggle
            $('#filterCollapse').on('show.bs.collapse', function () {
                $('#filterToggleIcon').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            });
            
            $('#filterCollapse').on('hide.bs.collapse', function () {
                $('#filterToggleIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
            });
            
            // Add refresh functionality
            window.refreshTable = function() {
                // Get current filters
                const currentPage = <?= $page ?>;
                const currentFilter = $('#filter_status').val() || '<?= $statusFilter ?>';
                const currentSearch = $('#search').val() || '<?= $search ?>';
                const currentLocation = $('#search_location').val() || '<?= $searchLocation ?>';
                const startDate = $('#start_date').val() || '<?= $startDateFilter ?>';
                const endDate = $('#end_date').val() || '<?= $endDateFilter ?>';
                
                // Construct URL with current filters
                let refreshUrl = 'pickup-scheduling.php';
                
                // Add parameters if they exist
                const params = [];
                if (currentPage > 1) params.push(`page=${currentPage}`);
                if (currentFilter) params.push(`filter_status=${currentFilter}`);
                if (currentSearch) params.push(`search=${encodeURIComponent(currentSearch)}`);
                if (currentLocation) params.push(`search_location=${encodeURIComponent(currentLocation)}`);
                if (startDate) params.push(`start_date=${startDate}`);
                if (endDate) params.push(`end_date=${endDate}`);
                
                if (params.length > 0) {
                    refreshUrl += '?' + params.join('&');
                }
                
                // Reload the page with the current filters
                window.location.href = refreshUrl;
            };
        });
    </script>
</body>
</html>
