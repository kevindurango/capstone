<?php
session_start();

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Product.php';
require_once '../../models/Log.php';
require_once '../../controllers/productcontroller.php';

$productController = new ProductController();
$logClass = new Log();

// Add debug logging
error_log("Starting product management page...");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pagination settings
$productsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $productsPerPage;

// Get product statistics
$products = $productController->getAllProductsWithDetails();
$totalProducts = $productController->getProductCount();
$pendingCount = $productController->getProductCountByStatus('pending');
$approvedCount = $productController->getProductCountByStatus('approved');
$lowStockProducts = $productController->getLowStockProducts(10);

// Handle AJAX load more request
if (isset($_GET['loadMore'])) {
    $offset = (int)$_GET['offset'];
    $filteredProducts = array_slice($products, $offset, $productsPerPage);
    include 'partials/product-rows.php';
    exit;
}

// Get all categories and farmers for filters
$categories = $productController->getCategoriesOptimized();
$farmers = $productController->getAllFarmers();

// Get all barangays for geographic filtering
try {
    // Initialize database connection
    require_once '../../models/Database.php';
    $database = new Database();
    $conn = $database->connect();
    
    $barangayQuery = "SELECT barangay_id, barangay_name FROM barangays ORDER BY barangay_name";
    $barangayStmt = $conn->query($barangayQuery);
    $barangays = $barangayStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching barangays for product management: " . $e->getMessage());
    $barangays = [];
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $productId = $_POST['product_id'];
        $newStatus = $_POST['new_status'];
        $notes = $_POST['notes'] ?? '';
        
        // Add debug logging
        error_log("Attempting to update product #$productId to status: $newStatus");
        
        // Check for valid product ID
        if (!is_numeric($productId) || $productId <= 0) {
            $error = "Invalid product ID";
            error_log("Invalid product ID: $productId");
        } else {
            // Update product status with user ID for activity logging
            $result = $productController->updateProductStatus($productId, $newStatus, $notes, $_SESSION['user_id']);
            
            if ($result) {
                $logClass->logActivity($_SESSION['user_id'], "Updated product #$productId status to $newStatus");
                // Add success message
                $success = "Product #$productId status updated to $newStatus";
                error_log("Successfully updated product #$productId to status: $newStatus");
            } else {
                $error = "Failed to update product status: " . $productController->getLastError();
                error_log("Failed to update product #$productId status. Error: " . $productController->getLastError());
            }
        }
    }
    
    if (isset($_POST['logout'])) {
        $logClass->logActivity($_SESSION['user_id'], "Manager logged out");
        session_destroy();
        header("Location: manager-login.php");
        exit();
    }
    
    // Enhanced export products functionality
    if (isset($_POST['export_products'])) {
        $categoryFilter = $_POST['export_category'] ?? '';
        $statusFilter = $_POST['export_status'] ?? '';
        $farmerFilter = $_POST['export_farmer'] ?? '';
        $dateRangeFilter = $_POST['export_date_range'] ?? '';
        
        // Filter products based on criteria
        $productsToExport = $products;
        
        if (!empty($categoryFilter)) {
            $productsToExport = array_filter($productsToExport, function($product) use ($categoryFilter) {
                return $product['category_id'] == $categoryFilter;
            });
        }
        
        if (!empty($statusFilter)) {
            $productsToExport = array_filter($productsToExport, function($product) use ($statusFilter) {
                return $product['status'] == $statusFilter;
            });
        }
        
        if (!empty($farmerFilter)) {
            $productsToExport = array_filter($productsToExport, function($product) use ($farmerFilter) {
                return $product['farmer_id'] == $farmerFilter;
            });
        }
        
        // Handle date range filtering
        if (!empty($dateRangeFilter)) {
            list($startDate, $endDate) = explode(' - ', $dateRangeFilter);
            $startDate = date('Y-m-d', strtotime($startDate));
            $endDate = date('Y-m-d', strtotime($endDate));
            
            $productsToExport = array_filter($productsToExport, function($product) use ($startDate, $endDate) {
                $productDate = substr($product['created_at'], 0, 10); // Extract just the date part YYYY-MM-DD
                return ($productDate >= $startDate && $productDate <= $endDate);
            });
        }
        
        if (!empty($productsToExport)) {
            $logClass->logActivity($_SESSION['user_id'], "Exported products report with ".count($productsToExport)." items");
            exportProductsToCSV($productsToExport);
            exit();
        } else {
            $error = "No products found with the selected filters";
        }
    }
}

// Handle AJAX status update
if (isset($_POST['ajax_update_status']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $productId = $_POST['product_id'];
    $newStatus = $_POST['new_status'];
    $notes = $_POST['notes'] ?? '';
    
    // Update product status with user ID for activity logging
    $result = $productController->updateProductStatus($productId, $newStatus, $notes, $_SESSION['user_id']);
    
    // Return JSON response
    header('Content-Type: application/json');
    if ($result) {
        $logClass->logActivity($_SESSION['user_id'], "Updated product #$productId status to $newStatus");
        echo json_encode([
            'success' => true,
            'message' => "Product #$productId status updated to $newStatus",
            'new_status' => $newStatus,
            'badge_class' => getStatusBadgeClass($newStatus)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Failed to update product status: " . $productController->getLastError()
        ]);
    }
    exit;
}

/**
 * Generate and force download CSV file with product data
 * @param array $products Product data to export
 */
function exportProductsToCSV($products) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products_report_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, ['Product ID', 'Name', 'Farmer', 'Price', 'Stock', 'Unit Type', 'Category', 'Status', 'Last Updated', 'Created Date']);
    
    // Add product data rows
    foreach ($products as $product) {
        fputcsv($output, [
            $product['product_id'] ?? 'N/A',
            $product['name'] ?? 'N/A',
            $product['farmer_name'] ?? 'N/A',
            $product['price'] ?? '0.00',
            $product['stock'] ?? '0',
            $product['unit_type'] ?? 'piece',
            $product['category'] ?? 'Uncategorized',
            $product['status'] ?? 'pending',
            $product['updated_at'] ?? $product['created_at'] ?? 'Unknown',
            $product['created_at'] ?? 'Unknown'
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/manager-orders.css">
    <style>
        .product-image-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .stock-warning {
            color: #dc3545;
            font-weight: 500;
        }
        .unit-badge {
            font-size: 0.8em;
            vertical-align: middle;
            background-color: #e9ecef;
            color: #495057;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 3px;
        }
        .load-more-container {
            text-align: center;
            padding: 20px 0;
        }
        #loadMoreBtn {
            background: linear-gradient(to right, #4e73df, #36b9cc);
            border: none;
            transition: all 0.3s ease;
        }
        #loadMoreBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .loading-spinner {
            display: none;
            margin-left: 10px;
        }
        .daterangepicker td.active {
            background-color: #4e73df !important;
        }
    </style>
</head>
<body>
    <!-- Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-box-seam"></i> PRODUCT MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>
            
            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white custom-card">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Product Management</li>
                    </ol>
                </nav>
    
                <!-- Success/Error Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 gradient-text">
                            <i class="bi bi-box-seam"></i> Product Management
                        </h1>
                        <p class="text-muted mb-0">Review and approve products from farmers</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success mr-2" data-toggle="modal" data-target="#exportModal">
                            <i class="bi bi-file-earmark-excel"></i> Export Report
                        </button>
                        <form method="POST" class="mb-0">
                            <button type="submit" name="logout" class="btn btn-danger logout-btn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Product Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-primary">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $totalProducts ?></h3>
                                <p>Total Products</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-warning">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $pendingCount ?></h3>
                                <p>Pending Approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= $approvedCount ?></h3>
                                <p>Approved Products</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="order-stat-card">
                            <div class="stat-icon text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?= count($lowStockProducts) ?></h3>
                                <p>Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Filter Section -->
                <div class="filter-section mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="bi bi-search"></i> Search Products</label>
                                <input type="text" id="searchProduct" class="form-control" placeholder="Product name, farmer...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label><i class="bi bi-funnel"></i> Filter Status</label>
                                <select id="statusFilter" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label><i class="bi bi-tag"></i> Filter Category</label>
                                <select id="categoryFilter" class="form-control">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['category_id'] ?>">
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label><i class="bi bi-geo-alt"></i> Barangay</label>
                                <select id="barangayFilter" class="form-control">
                                    <option value="">All Barangays</option>
                                    <?php foreach ($barangays as $barangay): ?>
                                        <option value="<?= $barangay['barangay_id'] ?>">
                                            <?= htmlspecialchars($barangay['barangay_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="bi bi-sort-alpha-down"></i> Sort By</label>
                                <select id="sortFilter" class="form-control">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="name-asc">Name (A-Z)</option>
                                    <option value="name-desc">Name (Z-A)</option>
                                    <option value="price-high">Price (Highest)</option>
                                    <option value="price-low">Price (Lowest)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="orders-table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Farmer</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> No products found.
                                                <?php if (isset($_SESSION['debug'])): ?>
                                                    <br>
                                                    <small>Debug: Check database connection and products table.</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    // Display initial set of products (paginated)
                                    $displayProducts = array_slice($products, 0, $productsPerPage);
                                    foreach ($displayProducts as $product): 
                                    ?>
                                    <tr class="product-row" 
                                        data-status="<?= htmlspecialchars($product['status'] ?? 'pending') ?>"
                                        data-category="<?= htmlspecialchars($product['category_id'] ?? '') ?>">
                                        <td>#<?= $product['product_id'] ?></td>
                                        <td>
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="../../public/<?= htmlspecialchars($product['image']) ?>" class="product-image-thumbnail" alt="<?= htmlspecialchars($product['name']) ?>">
                                            <?php else: ?>
                                                <img src="../../public/assets/default-product.png" class="product-image-thumbnail" alt="Default Image">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold"><?= htmlspecialchars($product['name']) ?></div>
                                            <small class="text-muted">
                                                <?= mb_strimwidth(htmlspecialchars($product['description'] ?? ''), 0, 50, "...") ?>
                                            </small>
                                        </td>
                                        <td><?= htmlspecialchars($product['farmer_name'] ?? 'N/A') ?></td>
                                        <td>₱<?= number_format($product['price'], 2) ?></td>
                                        <td>
                                            <?php if ($product['stock'] <= 10): ?>
                                                <span class="stock-warning"><?= $product['stock'] ?></span>
                                            <?php else: ?>
                                                <?= $product['stock'] ?>
                                            <?php endif; ?>
                                            <span class="unit-badge"><?= htmlspecialchars($product['unit_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></td>
                                        <td>
                                            <span class="badge badge-<?= getStatusBadgeClass($product['status'] ?? 'pending') ?>">
                                                <?= ucfirst(htmlspecialchars($product['status'] ?? 'pending')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info view-product" 
                                                        data-id="<?= $product['product_id'] ?>"
                                                        title="View Product Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-success update-status" 
                                                        data-id="<?= $product['product_id'] ?>"
                                                        data-status="<?= htmlspecialchars($product['status'] ?? 'pending') ?>"
                                                        title="Update Status">
                                                    <i class="bi bi-arrow-up-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary manage-planted-area" 
                                                        data-id="<?= $product['product_id'] ?>"
                                                        data-name="<?= htmlspecialchars($product['name']) ?>"
                                                        title="Manage Planted Area">
                                                    <i class="bi bi-geo-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Load More Button -->
                    <?php if (count($products) > $productsPerPage): ?>
                    <div class="load-more-container">
                        <button id="loadMoreBtn" class="btn btn-primary" data-offset="<?= $productsPerPage ?>">
                            Load More Products
                            <span class="loading-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Export Products Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Export Products Report</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="export_category">Category (Optional)</label>
                            <select id="export_category" name="export_category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>">
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="export_status">Status (Optional)</label>
                            <select id="export_status" name="export_status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="export_farmer">Farmer (Optional)</label>
                            <select id="export_farmer" name="export_farmer" class="form-control">
                                <option value="">All Farmers</option>
                                <?php foreach ($farmers as $farmer): ?>
                                    <option value="<?= $farmer['user_id'] ?>">
                                        <?= htmlspecialchars($farmer['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="export_date_range">Date Range (Optional)</label>
                            <input type="text" id="export_date_range" name="export_date_range" class="form-control" placeholder="Select date range">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="export_products" class="btn btn-success">
                            <i class="bi bi-download"></i> Download Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Product Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="productDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-circle"></i> Update Product Status</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="status_product_id">
                        <div class="form-group">
                            <label>Current Status</label>
                            <input type="text" id="current_status" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>New Status</label>
                            <select name="new_status" id="new_status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add optional notes about this status change"></textarea>
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

    <!-- Status Update Success Modal -->
    <div class="modal fade" id="statusUpdateSuccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Success</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="statusUpdateSuccessMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Planted Area Management Modal -->
    <div class="modal fade" id="plantedAreaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-geo-alt"></i> Manage Planted Area</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="plantedAreaAlert" class="alert" style="display: none;"></div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5 id="productNameHeader"></h5>
                            <p class="text-muted">Update planted area across different barangays</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="plantedAreaTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Barangay</th>
                                    <th>Season</th>
                                    <th>Estimated Production</th>
                                    <th>Unit</th>
                                    <th>Planted Area (hectares)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="plantedAreaTableBody">
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Planted Area Modal -->
    <div class="modal fade" id="editPlantedAreaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Planted Area</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="editPlantedAreaForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_record_id" name="record_id">
                        <input type="hidden" id="edit_product_id" name="product_id">
                        
                        <div class="form-group">
                            <label for="edit_barangay">Barangay</label>
                            <input type="text" id="edit_barangay" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_season">Season</label>
                            <input type="text" id="edit_season" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_production">Estimated Production</label>
                            <div class="input-group">
                                <input type="number" id="edit_production" name="estimated_production" class="form-control" step="0.01" min="0">
                                <div class="input-group-append">
                                    <span class="input-group-text" id="edit_production_unit"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_planted_area">Planted Area</label>
                            <div class="input-group">
                                <input type="number" id="edit_planted_area" name="planted_area" class="form-control" step="0.01" min="0" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">hectares</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize daterangepicker
            $('#export_date_range').daterangepicker({
                opens: 'left',
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });
            
            $('#export_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            });
            
            $('#export_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
            
            // Product search functionality
            $("#searchProduct").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $(".product-row").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                
                // Hide load more button if search is active
                if (value) {
                    $("#loadMoreBtn").hide();
                } else {
                    $("#loadMoreBtn").show();
                }
            });
            
            // Status filter functionality
            $("#statusFilter").on("change", function() {
                var value = $(this).val().toLowerCase();
                if (value === "") {
                    $(".product-row").show();
                } else {
                    $(".product-row").hide();
                    $(".product-row[data-status='" + value + "']").show();
                }
                
                // Hide load more button if filter is active
                if (value) {
                    $("#loadMoreBtn").hide();
                } else {
                    $("#loadMoreBtn").show();
                }
            });
            
            // Category filter functionality
            $("#categoryFilter").on("change", function() {
                var value = $(this).val();
                if (value === "") {
                    $(".product-row").show();
                } else {
                    $(".product-row").hide();
                    $(".product-row[data-category='" + value + "']").show();
                }
                
                // Hide load more button if filter is active
                if (value) {
                    $("#loadMoreBtn").hide();
                } else {
                    $("#loadMoreBtn").show();
                }
            });
            
            // Sort functionality
            $("#sortFilter").on("change", function() {
                var value = $(this).val();
                var tbody = $("table tbody");
                var rows = tbody.find("tr").toArray();
                
                rows.sort(function(a, b) {
                    var A, B;
                    
                    switch(value) {
                        case "newest":
                            A = $(a).find("td:eq(0)").text();
                            B = $(b).find("td:eq(0)").text();
                            return B.localeCompare(A, undefined, {numeric: true});
                        
                        case "oldest":
                            A = $(a).find("td:eq(0)").text();
                            B = $(b).find("td:eq(0)").text();
                            return A.localeCompare(B, undefined, {numeric: true});
                            
                        case "name-asc":
                            A = $(a).find("td:eq(2)").text().trim();
                            B = $(b).find("td:eq(2)").text().trim();
                            return A.localeCompare(B);
                            
                        case "name-desc":
                            A = $(a).find("td:eq(2)").text().trim();
                            B = $(b).find("td:eq(2)").text().trim();
                            return B.localeCompare(A);
                            
                        case "price-high":
                            A = parseFloat($(a).find("td:eq(4)").text().replace('₱', '').replace(',', ''));
                            B = parseFloat($(b).find("td:eq(4)").text().replace('₱', '').replace(',', ''));
                            return B - A;
                            
                        case "price-low":
                            A = parseFloat($(a).find("td:eq(4)").text().replace('₱', '').replace(',', ''));
                            B = parseFloat($(b).find("td:eq(4)").text().replace('₱', '').replace(',', ''));
                            return A - B;
                    }
                });
                
                $.each(rows, function(index, row) {
                    tbody.append(row);
                });
            });

            // View product details
            $(document).on('click', '.view-product', function() {
                const productId = $(this).data('id');
                // Fetch product details via AJAX
                $.ajax({
                    url: '../../ajax/get-product-details.php',
                    type: 'GET',
                    data: { product_id: productId },
                    success: function(response) {
                        $('#productDetailsContent').html(response);
                        $('#productDetailsModal').modal('show');
                    },
                    error: function() {
                        alert('Failed to load product details. Please try again.');
                    }
                });
            });

            // Update status button handler
            $(document).on('click', '.update-status', function() {
                const productId = $(this).data('id');
                const currentStatus = $(this).data('status');
                $('#status_product_id').val(productId);
                $('#current_status').val(currentStatus);
                $('#new_status').val(currentStatus); // Set the dropdown to the current status
                $('#updateStatusModal').modal('show');
            });
            
            // Handle status update form submission
            $('#updateStatusForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                
                $.ajax({
                    url: 'manager-product.php',
                    type: 'POST',
                    data: formData + '&ajax_update_status=true',
                    success: function(response) {
                        if (response.success) {
                            $('#updateStatusModal').modal('hide');
                            $('#statusUpdateSuccessMessage').text(response.message);
                            $('#statusUpdateSuccessModal').modal('show');
                            
                            // Update the status badge in the table
                            const productId = $('#status_product_id').val();
                            const newStatus = response.new_status;
                            const badgeClass = response.badge_class;
                            const row = $('button[data-id="' + productId + '"]').closest('tr');
                            row.find('td:eq(7) .badge').text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1)).attr('class', 'badge badge-' + badgeClass);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('Failed to update product status. Please try again.');
                    }
                });
            });
            
            // Load More Products
            $("#loadMoreBtn").click(function() {
                var offset = $(this).data('offset');
                var button = $(this);
                
                // Show loading spinner
                button.prop('disabled', true);
                button.find('.loading-spinner').show();
                
                $.ajax({
                    url: 'manager-product.php',
                    type: 'GET',
                    data: { 
                        loadMore: true, 
                        offset: offset
                    },
                    success: function(response) {
                        // Append new rows to the table
                        $("#productsTableBody").append(response);
                        
                        // Update offset for next load
                        var newOffset = offset + <?= $productsPerPage ?>;
                        button.data('offset', newOffset);
                        
                        // Hide button if we've loaded all products
                        if (newOffset >= <?= count($products) ?>) {
                            button.hide();
                        }
                        
                        // Reset button state
                        button.prop('disabled', false);
                        button.find('.loading-spinner').hide();
                    },
                    error: function() {
                        alert('Failed to load more products. Please try again.');
                        button.prop('disabled', false);
                        button.find('.loading-spinner').hide();
                    }
                });
            });

            // Manage Planted Area button handler
            $(document).on('click', '.manage-planted-area', function() {
                const productId = $(this).data('id');
                const productName = $(this).data('name');
                
                // Set product name in the modal header
                $('#productNameHeader').text(productName);
                
                // Clear previous data
                $('#plantedAreaTableBody').empty();
                $('#plantedAreaAlert').hide();
                
                // Show loading state
                $('#plantedAreaTableBody').html('<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></td></tr>');
                
                console.log("Fetching planted area data for product ID:", productId);
                
                // Fetch planted area data via AJAX
                $.ajax({
                    url: '../../ajax/get-planted-area.php',
                    type: 'GET',
                    data: { product_id: productId },
                    dataType: 'json',
                    success: function(response) {
                        $('#plantedAreaTableBody').empty();
                        
                        // Debug: Log the response from the server
                        console.log("AJAX Response:", response);

                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                // Debug: Log the first row of data
                                console.log("First row of data:", response.data[0]);

                                // Populate the table with the data
                                $.each(response.data, function(index, item) {
                                    // Parse numeric strings to actual numbers
                                    const estimatedProduction = parseFloat(item.estimated_production);
                                    const plantedArea = parseFloat(item.planted_area);

                                    // Ensure valid numeric values or default to 0.00
                                    const validEstimatedProduction = isNaN(estimatedProduction) ? '0.00' : estimatedProduction.toFixed(2);
                                    const validPlantedArea = isNaN(plantedArea) ? '0.00' : plantedArea.toFixed(2);

                                    console.log(`Row ${index} parsed values:`, {
                                        estimatedProduction: validEstimatedProduction,
                                        plantedArea: validPlantedArea
                                    });

                                    let row = `
                                        <tr>
                                            <td>${item.barangay_name || 'N/A'}</td>
                                            <td>${item.season_name || 'N/A'}</td>
                                            <td>${validEstimatedProduction}</td>
                                            <td>${item.production_unit || 'kilogram'}</td>
                                            <td>${validPlantedArea}</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-planted-area" 
                                                        data-id="${item.id}"
                                                        data-product-id="${productId}"
                                                        data-barangay="${item.barangay_name || ''}"
                                                        data-season="${item.season_name || ''}"
                                                        data-production="${validEstimatedProduction}"
                                                        data-unit="${item.production_unit || 'kilogram'}"
                                                        data-area="${validPlantedArea}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                    $('#plantedAreaTableBody').append(row);
                                });
                            } else {
                                // No data found
                                $('#plantedAreaTableBody').html('<tr><td colspan="6" class="text-center">No planted area data found for this product.</td></tr>');
                                $('#plantedAreaAlert').removeClass('alert-danger').addClass('alert-info')
                                    .html('<strong>Info:</strong> No planted area data exists for this product yet. Click Edit to create new entries.')
                                    .show();
                            }
                        } else {
                            // Error message
                            $('#plantedAreaAlert').removeClass('alert-success').addClass('alert-danger')
                                .html('<strong>Error:</strong> ' + (response.message || 'Failed to load planted area data.'))
                                .show();
                            $('#plantedAreaTableBody').html('<tr><td colspan="6" class="text-center">Failed to fetch planted area data.</td></tr>');
                        }
                        
                        // Show the modal
                        $('#plantedAreaModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.log("Response:", xhr.responseText);
                        
                        $('#plantedAreaAlert').removeClass('alert-success').addClass('alert-danger')
                            .html('<strong>Error:</strong> Failed to load planted area data. Please check the console for details.')
                            .show();
                        $('#plantedAreaTableBody').html('<tr><td colspan="6" class="text-center">Error loading data. Please try again later.</td></tr>');
                        $('#plantedAreaModal').modal('show');
                    }
                });
            });

            // Edit planted area button click handler
            $(document).on('click', '.edit-planted-area', function() {
                const recordId = $(this).data('id');
                const productId = $(this).data('product-id');
                const barangay = $(this).data('barangay');
                const season = $(this).data('season');
                const production = $(this).data('production');
                const unit = $(this).data('unit');
                const area = $(this).data('area');
                
                // Set values in the edit form
                $('#edit_record_id').val(recordId);
                $('#edit_product_id').val(productId);
                $('#edit_barangay').val(barangay);
                $('#edit_season').val(season);
                $('#edit_production').val(production);
                $('#edit_production_unit').text(unit);
                $('#edit_planted_area').val(area);
                
                // Show the edit modal
                $('#plantedAreaModal').modal('hide');
                $('#editPlantedAreaModal').modal('show');
            });

            // Handle edit planted area form submission
            $('#editPlantedAreaForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                
                $.ajax({
                    url: '../../ajax/update-planted-area.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Close the edit modal
                            $('#editPlantedAreaModal').modal('hide');
                            
                            // Show success message
                            $('#plantedAreaAlert').removeClass('alert-danger').addClass('alert-success').text(response.message).show();
                            
                            // Re-fetch the planted area data to refresh the table
                            const productId = $('#edit_product_id').val();
                            
                            // Fetch updated data
                            $.ajax({
                                url: '../../ajax/get-planted-area.php',
                                type: 'GET',
                                data: { product_id: productId },
                                dataType: 'json',
                                success: function(dataResponse) {
                                    $('#plantedAreaTableBody').empty();
                                    
                                    if (dataResponse.success && dataResponse.data.length > 0) {
                                        // Populate the table with the updated data
                                        $.each(dataResponse.data, function(index, item) {
                                            let row = `
                                                <tr>
                                                    <td>${item.barangay_name}</td>
                                                    <td>${item.season_name}</td>
                                                    <td>${item.estimated_production}</td>
                                                    <td>${item.production_unit}</td>
                                                    <td>${item.planted_area}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary edit-planted-area" 
                                                                data-id="${item.id}"
                                                                data-product-id="${productId}"
                                                                data-barangay="${item.barangay_name}"
                                                                data-season="${item.season_name}"
                                                                data-production="${item.estimated_production}"
                                                                data-unit="${item.production_unit}"
                                                                data-area="${item.planted_area}">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            `;
                                            $('#plantedAreaTableBody').append(row);
                                        });
                                    } else {
                                        $('#plantedAreaTableBody').html('<tr><td colspan="6" class="text-center">No planted area data found for this product.</td></tr>');
                                    }
                                    
                                    // Show the planted area modal again
                                    $('#plantedAreaModal').modal('show');
                                }
                            });
                        } else {
                            // Show error message
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('Failed to update planted area. Please try again.');
                    }
                });
            });
            
            // When edit modal is closed, show the main planted area modal again
            $('#editPlantedAreaModal').on('hidden.bs.modal', function () {
                $('#plantedAreaModal').modal('show');
            });
        });
    </script>
</body>
</html>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}
?>