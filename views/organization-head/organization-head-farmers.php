<?php
session_start();

// Check if user is logged in as Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

$logClass = new Log();
$db = new Database();
$conn = $db->connect();

// Get organization_head_user_id from session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle farmer update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_farmer']) && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $farmer_id = $_POST['farmer_id'];
    $farm_name = $_POST['farm_name'];
    $farm_type = $_POST['farm_type'];
    $farm_size = $_POST['farm_size'];
    $farm_location = $_POST['farm_location'];
    $certifications = $_POST['certifications'];
    
    try {
        // First check if farmer details exist
        $checkQuery = "SELECT * FROM farmer_details WHERE user_id = :user_id";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':user_id', $farmer_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update existing record
            $query = "UPDATE farmer_details 
                      SET farm_name = :farm_name, 
                          farm_type = :farm_type, 
                          farm_size = :farm_size, 
                          farm_location = :farm_location,
                          certifications = :certifications
                      WHERE user_id = :user_id";
        } else {
            // Insert new record
            $query = "INSERT INTO farmer_details 
                      (user_id, farm_name, farm_type, farm_size, farm_location, certifications)
                      VALUES 
                      (:user_id, :farm_name, :farm_type, :farm_size, :farm_location, :certifications)";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $farmer_id, PDO::PARAM_INT);
        $stmt->bindParam(':farm_name', $farm_name, PDO::PARAM_STR);
        $stmt->bindParam(':farm_type', $farm_type, PDO::PARAM_STR);
        $stmt->bindParam(':farm_size', $farm_size, PDO::PARAM_STR);
        $stmt->bindParam(':farm_location', $farm_location, PDO::PARAM_STR);
        $stmt->bindParam(':certifications', $certifications, PDO::PARAM_STR);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Update contact information in users table
            $contact_number = $_POST['contact_number'];
            $address = $_POST['address'];
            
            $query = "UPDATE users 
                      SET contact_number = :contact_number,
                          address = :address
                      WHERE user_id = :user_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
            $stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $farmer_id, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            $logClass->logActivity($organization_head_user_id, "Updated farmer details for user ID: $farmer_id");
            $_SESSION['message'] = "Farmer details updated successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to update farmer details.";
            $_SESSION['message_type'] = 'danger';
        }
    } catch (PDOException $e) {
        error_log("Database error updating farmer: " . $e->getMessage());
        $_SESSION['message'] = "Database error occurred: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    // Redirect to refresh the page
    header("Location: organization-head-farmers.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all farmers (role_id = 2)
try {
    // Base query to get farmers with their details
    $query = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.contact_number, u.address,
              fd.farm_name, fd.farm_type, fd.certifications, fd.farm_size, fd.income, fd.farm_location 
              FROM users u 
              LEFT JOIN farmer_details fd ON u.user_id = fd.user_id
              WHERE u.role_id = 2";

    // Add search condition if search parameter is provided
    if (!empty($search)) {
        $query .= " AND (u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search 
                  OR u.email LIKE :search OR fd.farm_name LIKE :search OR fd.farm_location LIKE :search)";
    }

    $query .= " ORDER BY u.last_name, u.first_name";

    // Count total farmers for pagination
    $countQuery = str_replace("SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.contact_number, u.address,
              fd.farm_name, fd.farm_type, fd.certifications, fd.farm_size, fd.income, fd.farm_location", "SELECT COUNT(*)", $query);
    
    $stmt = $conn->prepare($countQuery);
    
    if (!empty($search)) {
        $searchParam = "%" . $search . "%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $totalFarmers = $stmt->fetchColumn();
    
    // Get paginated farmers
    $query .= " LIMIT :offset, :limit";
    $stmt = $conn->prepare($query);
    
    if (!empty($search)) {
        $searchParam = "%" . $search . "%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total pages for pagination
    $totalPages = ceil($totalFarmers / $limit) ?: 1; // Ensure at least 1 page
    
} catch (PDOException $e) {
    error_log("Database error in getting farmers: " . $e->getMessage());
    $_SESSION['message'] = "Error retrieving farmers. Please try again later.";
    $_SESSION['message_type'] = 'danger';
    $farmers = [];
    $totalFarmers = 0;
    $totalPages = 1;
}

// Get farmer statistics
try {
    // Total farmers count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role_id = 2");
    $stmt->execute();
    $totalFarmersCount = $stmt->fetchColumn();
    
    // Average farm size
    $stmt = $conn->prepare("SELECT AVG(farm_size) FROM farmer_details");
    $stmt->execute();
    $avgFarmSize = $stmt->fetchColumn() ?: 0;
    
    // Total farm area
    $stmt = $conn->prepare("SELECT SUM(farm_size) FROM farmer_details");
    $stmt->execute();
    $totalFarmArea = $stmt->fetchColumn() ?: 0;
    
    // Farmers with certifications
    $stmt = $conn->prepare("SELECT COUNT(*) FROM farmer_details WHERE certifications IS NOT NULL AND certifications != ''");
    $stmt->execute();
    $certifiedFarmers = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Database error in getting farmer statistics: " . $e->getMessage());
    $totalFarmersCount = 0;
    $avgFarmSize = 0;
    $totalFarmArea = 0;
    $certifiedFarmers = 0;
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['logout'])) {
        $logClass->logActivity($organization_head_user_id, "Organization Head logged out.");
        session_unset();
        session_destroy();
        header("Location: organization-head-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Management - Organization Head Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .table thead th {
            background-color: #198754; /* Solid green color instead of gradient */
            color: white;
            font-weight: 600;
            border-bottom: none;
            padding: 12px 15px;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .dashboard-card {
            transition: transform 0.3s ease;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            background-color: #fff;
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .dashboard-card h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .dashboard-card p {
            color: #6c757d;
            margin-bottom: 0;
        }
        .stats-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: #198754;
        }
        .stats-number {
            font-size: 1.75rem;
            font-weight: 600;
            color: #198754;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .certification-badge {
            background-color: #28a745;
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            display: inline-block;
        }
        .farmer-card {
            transition: all 0.3s ease;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .farmer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .farmer-card .card-header {
            background-color: #198754;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
        }
        .data-filter-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn-export {
            background: #20c997;
            color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .btn-export:hover {
            background: #198754;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .edit-form .form-group {
            margin-bottom: 1rem;
        }
        .edit-form label {
            font-weight: 500;
        }
        .btn-action {
            margin-right: 5px;
        }
        /* Improved action buttons styling */
        .action-btn-group {
            display: flex;
                flex-direction: row;
            justify-content: center;
            gap: 5px;
            flex-wrap: nowrap;
            width: 100%;
        }
        
        .btn-action {
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            white-space: nowrap;
            margin: 0;
        }
        
        .action-btn-group .btn {
            flex: 1;
            min-width: auto;
            white-space: nowrap;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        
        .btn-action i {
            margin-right: 4px;
            font-size: 0.9rem;
        }
        
        .btn-view {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: white;
        }
        
        .btn-edit {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            color: white;
        }
        
        /* Fix for table button groups */
        .btn-group {
            display: flex;
            flex-direction: row;
            gap: 5px;
        }
        
        .btn-group .btn {
            flex: 0 0 auto;
            white-space: nowrap;
            margin: 0;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-people-fill"></i> FARMER MANAGEMENT SYSTEM
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Add Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white custom-card">
                        <li class="breadcrumb-item"><a href="organization-head-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Farmer Management</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-1">Farmer Management</h1>
                        <p class="text-muted mb-0">View, edit, and manage farmer information</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-success mr-2" id="exportCSV">
                            <i class="bi bi-file-earmark-text"></i> Export Report
                        </button>
                        <form method="POST" class="mb-0" onsubmit="return confirm('Are you sure you want to logout?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
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
                    <?php 
                        unset($_SESSION['message']); 
                        unset($_SESSION['message_type']);
                    ?>
                <?php endif; ?>

                <!-- Search and Export Section -->
                <div class="data-filter-card">
                    <div class="row">
                        <div class="col-md-8">
                            <form method="GET" action="" class="form-inline mb-2">
                                <div class="form-group mr-3">
                                    <label for="search" class="sr-only">Search</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        </div>
                                        <input type="text" id="search" name="search" class="form-control" 
                                               placeholder="Search farmers..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">
                                    Search
                                </button>
                                <a href="organization-head-farmers.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </form>
                            
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="farmTypeFilter"><i class="bi bi-funnel"></i> Farm Type</label>
                                        <select id="farmTypeFilter" class="form-control form-control-sm">
                                            <option value="">All Types</option>
                                            <option value="Vegetable Farm">Vegetable Farm</option>
                                            <option value="Fruit Orchard">Fruit Orchard</option>
                                            <option value="Rice Farm">Rice Farm</option>
                                            <option value="Mixed Crop">Mixed Crop</option>
                                            <option value="Livestock">Livestock</option>
                                            <option value="Poultry">Poultry</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="certificationFilter"><i class="bi bi-patch-check"></i> Certification</label>
                                        <select id="certificationFilter" class="form-control form-control-sm">
                                            <option value="">All</option>
                                            <option value="yes">With Certification</option>
                                            <option value="no">No Certification</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sortBy"><i class="bi bi-sort-alpha-down"></i> Sort By</label>
                                        <select id="sortBy" class="form-control form-control-sm">
                                            <option value="name">Name (A-Z)</option>
                                            <option value="farm_size_desc">Farm Size (Largest)</option>
                                            <option value="farm_size_asc">Farm Size (Smallest)</option>
                                            <option value="farm_name">Farm Name</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <button id="exportCSV" class="btn btn-export mb-2 btn-lg btn-block">
                                <i class="bi bi-download"></i> Export Farmers List
                            </button>
                            <button id="exportDetailedReport" class="btn btn-outline-success btn-block">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Detailed Farm Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Enhanced User Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="card-icon text-success">
                                <i class="bi bi-person-lines-fill"></i>
                            </div>
                            <h3 class="mb-3"><?= $totalFarmersCount ?></h3>
                            <p class="text-muted mb-0">Total Farmers</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="card-icon text-primary">
                                <i class="bi bi-rulers"></i>
                            </div>
                            <h3 class="mb-3"><?= number_format($avgFarmSize, 2) ?> ha</h3>
                            <p class="text-muted mb-0">Average Farm Size</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="card-icon text-info">
                                <i class="bi bi-map"></i>
                            </div>
                            <h3 class="mb-3"><?= number_format($totalFarmArea, 2) ?> ha</h3>
                            <p class="text-muted mb-0">Total Farm Area</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="card-icon text-warning">
                                <i class="bi bi-award"></i>
                            </div>
                            <h3 class="mb-3"><?= $certifiedFarmers ?></h3>
                            <p class="text-muted mb-0">Certified Farmers</p>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced Farmers Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="farmersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Farmer Info</th>
                                        <th>Farm</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($farmers as $farmer): ?>
                                        <tr>
                                            <td><?= $farmer['user_id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar mr-3">
                                                        <i class="bi bi-person-circle"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($farmer['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($farmer['farm_name'] ?? 'Farm Name Not Set') ?></strong>
                                                </div>
                                                <small class="text-muted">Type: <?= htmlspecialchars($farmer['farm_type'] ?? 'Not specified') ?></small>
                                                <?php if (!empty($farmer['farm_size'])): ?>
                                                    <br><small class="text-muted">Size: <?= number_format($farmer['farm_size'], 2) ?> ha</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($farmer['contact_number'] ?? 'N/A') ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($farmer['farm_location'] ?? $farmer['address'] ?? 'Not specified') ?>
                                                </small>
                                            </td>
                                            <td>
                                                    <?php if (!empty($farmer['certifications'])): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-btn-group">
                                                    <button class="btn btn-sm btn-primary edit-farmer" 
                                                            data-toggle="modal"
                                                            data-target="#editFarmerModal"
                                                            data-farmer-id="<?= $farmer['user_id'] ?>"
                                                            data-first-name="<?= htmlspecialchars($farmer['first_name']) ?>"
                                                            data-last-name="<?= htmlspecialchars($farmer['last_name']) ?>"
                                                            data-email="<?= htmlspecialchars($farmer['email']) ?>"
                                                            data-contact="<?= htmlspecialchars($farmer['contact_number'] ?? '') ?>"
                                                            data-address="<?= htmlspecialchars($farmer['address'] ?? '') ?>"
                                                            data-farm-name="<?= htmlspecialchars($farmer['farm_name'] ?? '') ?>"
                                                            data-farm-type="<?= htmlspecialchars($farmer['farm_type'] ?? '') ?>"
                                                            data-farm-size="<?= htmlspecialchars($farmer['farm_size'] ?? '') ?>"
                                                            data-farm-location="<?= htmlspecialchars($farmer['farm_location'] ?? '') ?>"
                                                            data-certifications="<?= htmlspecialchars($farmer['certifications'] ?? '') ?>">
                                                        <i class="bi bi-pencil-square"></i> Edit
                                                    </button>
                                                    <a href="organization-head-farmer-fields.php?farmer_id=<?= $farmer['user_id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-geo-alt"></i> Fields
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (empty($farmers)): ?>
                            <div class="alert alert-info">
                                No farmers found. <?= !empty($search) ? 'Try a different search term.' : '' ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pagination -->
                        <?php if (!empty($farmers)): ?>
                            <nav>
                                <ul class="pagination justify-content-center mt-4">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">First</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>">Last</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <p class="text-center">Page <?= $page ?> of <?= $totalPages ?></p>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Farmer Details Modal -->
    <div class="modal fade" id="farmerDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-person-lines-fill"></i> Farmer Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="farmerDetailsContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading farmer details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="detailsToEditBtn">
                        <i class="bi bi-pencil-square"></i> Edit Details
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Farmer Modal -->
    <div class="modal fade" id="editFarmerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Farmer Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="farmer_id" id="edit_farmer_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Personal Information</h5>
                                
                                <div class="form-group">
                                    <label>Name</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="edit_first_name" readonly>
                                        <input type="text" class="form-control" id="edit_last_name" readonly>
                                    </div>
                                    <small class="text-muted">Name cannot be edited here</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" id="edit_email" readonly>
                                    <small class="text-muted">Email cannot be edited here</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_contact_number">Contact Number</label>
                                    <input type="text" class="form-control" id="edit_contact_number" name="contact_number" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_address">Address</label>
                                    <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Farm Information</h5>
                                
                                <div class="form-group">
                                    <label for="edit_farm_name">Farm Name</label>
                                    <input type="text" class="form-control" id="edit_farm_name" name="farm_name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_farm_type">Farm Type</label>
                                    <select class="form-control" id="edit_farm_type" name="farm_type">
                                        <option value="">Select Farm Type</option>
                                        <option value="Vegetable Farm">Vegetable Farm</option>
                                        <option value="Fruit Orchard">Fruit Orchard</option>
                                        <option value="Rice Farm">Rice Farm</option>
                                        <option value="Mixed Crop">Mixed Crop</option>
                                        <option value="Livestock">Livestock</option>
                                        <option value="Poultry">Poultry</option>
                                        <option value="Aquaculture">Aquaculture</option>
                                        <option value="Agri-Organic">Agri-Organic</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_farm_size">Farm Size (hectares)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_farm_size" name="farm_size">
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_farm_location">Farm Location</label>
                                    <input type="text" class="form-control" id="edit_farm_location" name="farm_location">
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_certifications">Certifications</label>
                                    <textarea class="form-control" id="edit_certifications" name="certifications" rows="2" placeholder="List any certifications separated by commas"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_farmer" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Farmer Details
            $('.view-farmer').click(function() {
                const farmerId = $(this).data('farmer-id');
                
                // For demonstration, we're generating HTML here
                // In a real application, you might want to make an AJAX call to get detailed data
                setTimeout(function() {
                    // Find the farmer data from the table
                    const row = $(`.view-farmer[data-farmer-id="${farmerId}"]`).closest('tr');
                    const name = row.find('td:nth-child(1)').text();
                    const farmName = row.find('td:nth-child(2)').text();
                    const location = row.find('td:nth-child(3)').text();
                    const contact = row.find('td:nth-child(4)').html();
                    const farmSize = row.find('td:nth-child(5)').text();
                    const farmType = row.find('td:nth-child(6)').text();
                    
                    // Generate detailed view
                    const detailsHtml = `
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Personal Information</h5>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label font-weight-bold">Name:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${name}</p>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label font-weight-bold">Contact:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${contact}</p>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label font-weight-bold">Location:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${location}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Farm Information</h5>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label font-weight-bold">Farm Name:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${farmName}</p>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label font-weight-bold">Farm Type:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${farmType}</p>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label font-weight-bold">Farm Size:</label>
                                    <div class="col-sm-8">
                                        <p class="form-control-plaintext">${farmSize}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5 class="border-bottom pb-2">Production Data</h5>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Detailed production data is available in the full farmer profile.
                            </div>
                        </div>
                    `;
                    
                    $('#farmerDetailsContent').html(detailsHtml);
                    
                    // Store the farmer ID for the edit button
                    $('#detailsToEditBtn').data('farmer-id', farmerId);
                }, 500); // Simulate loading time
            });
            
            // Button to go from details view to edit view
            $('#detailsToEditBtn').click(function() {
                const farmerId = $(this).data('farmer-id');
                $('#farmerDetailsModal').modal('hide');
                
                // Find the edit button with the same farmer ID and click it
                $(`.edit-farmer[data-farmer-id="${farmerId}"]`).click();
            });
            
            // Edit Farmer
            $('.edit-farmer').click(function() {
                const farmerId = $(this).data('farmer-id');
                const firstName = $(this).data('first-name');
                const lastName = $(this).data('last-name');
                const email = $(this).data('email');
                const contact = $(this).data('contact');
                const address = $(this).data('address');
                const farmName = $(this).data('farm-name');
                const farmType = $(this).data('farm-type');
                const farmSize = $(this).data('farm-size');
                const farmLocation = $(this).data('farm-location');
                const certifications = $(this).data('certifications');
                
                // Fill the edit form
                $('#edit_farmer_id').val(farmerId);
                $('#edit_first_name').val(firstName);
                $('#edit_last_name').val(lastName);
                $('#edit_email').val(email);
                $('#edit_contact_number').val(contact);
                $('#edit_address').val(address);
                $('#edit_farm_name').val(farmName);
                $('#edit_farm_type').val(farmType);
                $('#edit_farm_size').val(farmSize);
                $('#edit_farm_location').val(farmLocation);
                $('#edit_certifications').val(certifications);
            });
            
            // Client-side filtering functionality
            function filterTable() {
                const farmTypeFilter = $('#farmTypeFilter').val().toLowerCase();
                const certificationFilter = $('#certificationFilter').val();
                const sortBy = $('#sortBy').val();
                
                // Get all rows in the table body
                const rows = $('#farmersTable tbody tr').get();
                
                // Filter rows based on selected criteria
                $(rows).each(function(index, row) {
                    const $row = $(row);
                    const farmType = $row.find('td:nth-child(6)').text().toLowerCase();
                    const certifications = $row.find('.edit-farmer').data('certifications') || '';
                    
                    // Farm Type filter
                    const matchesFarmType = !farmTypeFilter || farmType.includes(farmTypeFilter);
                    
                    // Certification filter
                    let matchesCertification = true;
                    if (certificationFilter === 'yes') {
                        matchesCertification = certifications.trim() !== '';
                    } else if (certificationFilter === 'no') {
                        matchesCertification = certifications.trim() === '';
                    }
                    
                    // Show/hide the row based on filters
                    $row.toggle(matchesFarmType && matchesCertification);
                });
                
                // Sort the visible rows
                rows.sort(function(a, b) {
                    const $a = $(a);
                    const $b = $(b);
                    
                    if (sortBy === 'name') {
                        return $a.find('td:nth-child(1)').text().localeCompare($b.find('td:nth-child(1)').text());
                    } else if (sortBy === 'farm_name') {
                        return $a.find('td:nth-child(2)').text().localeCompare($b.find('td:nth-child(2)').text());
                    } else if (sortBy === 'farm_size_desc') {
                        // Extract numeric value from "X.XX ha" format
                        const aSize = parseFloat($a.find('td:nth-child(5)').text()) || 0;
                        const bSize = parseFloat($b.find('td:nth-child(5)').text()) || 0;
                        return bSize - aSize; // Descending
                    } else if (sortBy === 'farm_size_asc') {
                        const aSize = parseFloat($a.find('td:nth-child(5)').text()) || 0;
                        const bSize = parseFloat($b.find('td:nth-child(5)').text()) || 0;
                        return aSize - bSize; // Ascending
                    }
                    
                    return 0;
                });
                
                // Re-append sorted rows to the table
                const tbody = $('#farmersTable tbody');
                $.each(rows, function(index, row) {
                    tbody.append(row);
                });
            }
            
            // Attach event handlers to filter controls
            $('#farmTypeFilter, #certificationFilter, #sortBy').change(filterTable);
            
            // Export CSV
            document.getElementById('exportCSV').addEventListener('click', function() {
                // Create CSV content from table
                const table = document.getElementById('farmersTable');
                let csv = [];
                const rows = table.querySelectorAll('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        // Skip the actions column
                        if (j === 6 && i > 0) continue;
                        
                        // Clean the cell content (remove HTML, trim)
                        let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                        
                        // Quote fields with commas
                        data = data.replace(/"/g, '""');
                        if (data.includes(',')) {
                            data = `"${data}"`;
                        }
                        
                        row.push(data);
                    }
                    csv.push(row.join(','));
                }
                
                // Add report header
                const reportHeader = [
                    `"Farmers Directory - Generated on ${new Date().toLocaleDateString()}"`,
                    `"Total Farmers: ${<?= $totalFarmersCount ?>}"`,
                    ''  // Empty line before the actual data
                ];
                
                csv = reportHeader.concat(csv);
                const csvFile = csv.join('\n');
                
                // Create download link
                const downloadLink = document.createElement('a');
                downloadLink.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvFile);
                downloadLink.download = `farmers_directory_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            });

            // Export detailed report
            document.getElementById('exportDetailedReport').addEventListener('click', function() {
                // For detailed report, we'll include additional data from data attributes
                const table = document.getElementById('farmersTable');
                let csv = [];
                
                // Create header row with additional columns
                csv.push('"Name","Farm Name","Location","Contact","Email","Farm Size","Farm Type","Certifications"');
                
                // Get all rows that are currently visible
                const rows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
                
                // For each visible row, extract data including from data attributes
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const editBtn = row.querySelector('.edit-farmer');
                    
                    // Extract farmer data from visible cells and data attributes
                    const name = row.cells[0].textContent.trim();
                    const farmName = row.cells[1].textContent.trim();
                    const location = row.cells[2].textContent.trim();
                    const contactInfo = row.cells[3].textContent.trim().replace(/(\r\n|\n|\r)/gm, ' ');
                    // Split contact info into phone and email
                    const contactParts = contactInfo.split(' ');
                    const phone = contactParts[0] || '';
                    const email = editBtn.getAttribute('data-email') || '';
                    const farmSize = row.cells[4].textContent.trim();
                    const farmType = row.cells[5].textContent.trim();
                    const certifications = editBtn.getAttribute('data-certifications') || '';
                    
                    // Format the data for CSV
                    const csvRow = [
                        `"${name.replace(/"/g, '""')}"`,
                        `"${farmName.replace(/"/g, '""')}"`,
                        `"${location.replace(/"/g, '""')}"`,
                        `"${phone.replace(/"/g, '""')}"`,
                        `"${email.replace(/"/g, '""')}"`,
                        `"${farmSize.replace(/"/g, '""')}"`,
                        `"${farmType.replace(/"/g, '""')}"`,
                        `"${certifications.replace(/"/g, '""')}"`
                    ];
                    
                    csv.push(csvRow.join(','));
                }
                
                // Add report header with more details
                const today = new Date();
                const reportHeader = [
                    `"Detailed Farm Report - Generated on ${today.toLocaleDateString()} at ${today.toLocaleTimeString()}"`,
                    `"Organization: ${document.querySelector('.organization-badge').previousSibling.textContent.trim()}"`,
                    `"Total Farmers: ${rows.length} (filtered from ${<?= $totalFarmersCount ?>})"`,
                    `"Total Farm Area: ${<?= number_format($totalFarmArea, 2) ?>} hectares"`,
                    `"Average Farm Size: ${<?= number_format($avgFarmSize, 2) ?>} hectares"`,
                    `"Certified Farmers: ${<?= $certifiedFarmers ?>}"`,
                    `"Report Filters: ${$('#farmTypeFilter').val() ? 'Farm Type: ' + $('#farmTypeFilter').val() : 'All Farm Types'}, ${$('#certificationFilter').val() === 'yes' ? 'With Certification Only' : $('#certificationFilter').val() === 'no' ? 'Without Certification Only' : 'All Certification Status'}"`,
                    ''  // Empty line before the actual data
                ];
                
                csv = reportHeader.concat(csv);
                const csvFile = csv.join('\n');
                
                // Create download link
                const downloadLink = document.createElement('a');
                downloadLink.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvFile);
                downloadLink.download = `detailed_farm_report_${today.toISOString().slice(0,10)}.csv`;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
