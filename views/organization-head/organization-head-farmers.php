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
            background-color: #198754;
            color: white;
            border-bottom: 0;
            white-space: nowrap;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .card-stats {
            transition: transform 0.3s ease;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 100%;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .card-stats .card-header {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            font-weight: 600;
        }
        .card-stats .card-body {
            padding: 1.5rem;
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
            justify-content: center;
            gap: 8px;
        }
        
        .btn-action {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 80px;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        
        .btn-action i {
            margin-right: 4px;
            font-size: 1rem;
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
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-building"></i> ORGANIZATION MANAGEMENT SYSTEM
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success"><i class="bi bi-people-fill"></i> Farmers Management</h1>
                    <form method="POST" class="ml-3" onsubmit="return confirm('Are you sure you want to logout?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
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
                            <form method="GET" action="" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="search" class="sr-only">Search</label>
                                    <input type="text" id="search" name="search" class="form-control" 
                                           placeholder="Search farmers..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="organization-head-farmers.php" class="btn btn-outline-secondary ml-2">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </form>
                        </div>
                        <div class="col-md-4 text-right">
                            <button id="exportCSV" class="btn btn-export">
                                <i class="bi bi-download"></i> Export Farmers List
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-person-badge stats-icon"></i> Total Farmers</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= $totalFarmersCount ?></div>
                                <div class="stats-label">Registered farmers</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-rulers stats-icon"></i> Avg. Farm Size</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= number_format($avgFarmSize, 2) ?> ha</div>
                                <div class="stats-label">Average farm area</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-map stats-icon"></i> Total Area</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= number_format($totalFarmArea, 2) ?> ha</div>
                                <div class="stats-label">Total farm area</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md=3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-award stats-icon"></i> Certified</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= $certifiedFarmers ?></div>
                                <div class="stats-label">Farmers with certifications</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Farmers List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Farmers Directory</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($farmers)): ?>
                                    <div class="alert alert-info">
                                        No farmers found. <?= !empty($search) ? 'Try a different search term.' : '' ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="farmersTable">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Farm Name</th>
                                                    <th>Location</th>
                                                    <th>Contact</th>
                                                    <th>Farm Size</th>
                                                    <th>Farm Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($farmers as $farmer): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($farmer['first_name'] . ' ' . $farmer['last_name']) ?></td>
                                                        <td><?= htmlspecialchars($farmer['farm_name'] ?? 'Not specified') ?></td>
                                                        <td><?= htmlspecialchars($farmer['farm_location'] ?? $farmer['address'] ?? 'Not specified') ?></td>
                                                        <td>
                                                            <?= htmlspecialchars($farmer['contact_number'] ?? 'N/A') ?><br>
                                                            <small><?= htmlspecialchars($farmer['email']) ?></small>
                                                        </td>
                                                        <td><?= ($farmer['farm_size'] ?? 0) > 0 ? number_format($farmer['farm_size'], 2) . ' ha' : 'N/A' ?></td>
                                                        <td><?= htmlspecialchars($farmer['farm_type'] ?? 'Not specified') ?></td>
                                                        <td>
                                                            <div class="action-btn-group">
                                                                <button class="btn btn-action btn-view view-farmer" 
                                                                        data-farmer-id="<?= $farmer['user_id'] ?>"
                                                                        data-toggle="modal" 
                                                                        data-target="#farmerDetailsModal"
                                                                        data-toggle="tooltip"
                                                                        title="View farmer details">
                                                                    <i class="bi bi-eye-fill"></i> View
                                                                </button>
                                                                
                                                                <button class="btn btn-action btn-edit edit-farmer" 
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
                                                                        data-certifications="<?= htmlspecialchars($farmer['certifications'] ?? '') ?>"
                                                                        data-toggle="modal" 
                                                                        data-target="#editFarmerModal"
                                                                        data-toggle="tooltip"
                                                                        title="Edit farmer information">
                                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
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

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
