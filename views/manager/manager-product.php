<?php
// Start the session to track login status
session_start();

// Check if the user is logged in as a Manager, if not redirect to the login page
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    // Log unauthorized access attempt
    require_once '../../models/Log.php';
    $logClass = new Log();
    $userId = $_SESSION['user_id'] ?? null;
    $logClass->logActivity($userId, "Unauthorized access attempt to manager product management");
    
    header("Location: manager-login.php");
    exit();
}

// Get Manager User ID from Session
$manager_user_id = $_SESSION['user_id'] ?? null;
if (!$manager_user_id) {
    error_log("Manager user ID not found in session. Logging will be incomplete.");
}

// Get manager username for display
$manager_username = $_SESSION['username'] ?? 'Manager User';

require_once '../../controllers/ProductController.php';
require_once '../../models/Log.php';

$productController = new ProductController();
$logClass = new Log();

// Fetch products with additional info
$products = $productController->getAllProducts();

// Get product statistics
$totalProducts = $productController->getProductCount();
$activeProducts = $productController->getProductCountByStatus('approved');
$pendingProducts = $productController->getProductCountByStatus('pending');
$lowStockProducts = $productController->getLowStockProducts() ?: [];

// Fetch categories from database directly
$categories = [];
try {
    require_once '../../models/Database.php';
    $db = new Database();
    $pdo = $db->connect();
    $stmt = $pdo->query("SELECT * FROM productcategories ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = []; // Empty array as fallback
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle logout
    if (isset($_POST['logout'])) {
        $logClass->logActivity($manager_user_id, "Manager logged out");
        session_unset();
        session_destroy();
        header("Location: manager-login.php");
        exit();
    }
    
    // Handle add product
    if (isset($_POST['add_product'])) {
        // Process form data
        $productData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock' => $_POST['stock'] ?? 0,
            'category_id' => $_POST['category_id'] ?? null,
            'farmer_id' => $_POST['farmer_id'] ?? null,
            'status' => 'pending' // Default status for new products
        ];
        
        // Validate required fields
        if (empty($productData['name']) || empty($productData['price']) || empty($productData['farmer_id'])) {
            $actionMessage = '<div class="alert alert-danger alert-dismissible fade show">
                Failed to add product. Name, price and farmer are required fields.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            // Continue rendering the page instead of redirecting
        } else {
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_dir = '../../public/uploads/';
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $upload_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                    $productData['image'] = 'uploads/' . $filename;
                }
            }
            
            $result = $productController->addProduct($productData);
            if ($result) {
                $logClass->logActivity($manager_user_id, "Added new product: {$productData['name']}");
                header("Location: manager-product.php?action=added");
                exit();
            } else {
                header("Location: manager-product.php?error=add_failed");
                exit();
            }
        }
    }
    
    // Handle update product
    if (isset($_POST['update_product'])) {
        $product_id = $_POST['product_id'] ?? 0;
        
        $productData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock' => $_POST['stock'] ?? 0,
            'category_id' => $_POST['category_id'] ?? null,
            'farmer_id' => $_POST['farmer_id'] ?? null,
            'current_image' => $_POST['current_image'] ?? ''
        ];
        
        // Handle image upload if present
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $productData['image'] = $_FILES['image'];
        }
        
        $result = $productController->updateProduct($product_id, $productData);
        if ($result) {
            $logClass->logActivity($manager_user_id, "Updated product ID: {$product_id}");
            header("Location: manager-product.php?action=updated");
            exit();
        } else {
            header("Location: manager-product.php?error=update_failed");
            exit();
        }
    }
    
    // Handle delete product
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'] ?? 0;
        $result = $productController->deleteProduct($product_id);
        
        if ($result) {
            $logClass->logActivity($manager_user_id, "Deleted product ID: {$product_id}");
            header("Location: manager-product.php?action=deleted");
            exit();
        } else {
            header("Location: manager-product.php?error=delete_failed");
            exit();
        }
    }
    
    // Handle product status change
    if (isset($_POST['change_status']) && isset($_POST['product_id']) && isset($_POST['new_status'])) {
        $product_id = $_POST['product_id'];
        $new_status = $_POST['new_status'];
        $notes = $_POST['notes'] ?? '';
        
        $result = $productController->updateProductStatus($product_id, $new_status);
        
        if ($result) {
            $logClass->logActivity($manager_user_id, "Changed product ID: {$product_id} status to {$new_status}. Notes: {$notes}");
            header("Location: manager-product.php?action=status_changed");
            exit();
        } else {
            header("Location: manager-product.php?error=status_change_failed");
            exit();
        }
    }
}

// Get action messages
$actionMessage = '';
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'added':
            $actionMessage = '<div class="alert alert-success alert-dismissible fade show">
                Product has been successfully added.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
        case 'updated':
            $actionMessage = '<div class="alert alert-success alert-dismissible fade show">
                Product has been successfully updated.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
        case 'deleted':
            $actionMessage = '<div class="alert alert-success alert-dismissible fade show">
                Product has been successfully deleted.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
        case 'status_changed':
            $actionMessage = '<div class="alert alert-success alert-dismissible fade show">
                Product status has been successfully updated.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
    }
}

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'add_failed':
            $actionMessage = '<div class="alert alert-danger alert-dismissible fade show">
                Failed to add product. Please try again.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
        case 'update_failed':
            $actionMessage = '<div class="alert alert-danger alert-dismissible fade show">
                Failed to update product. Please try again.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
        case 'delete_failed':
            $actionMessage = '<div class="alert alert-danger alert-dismissible fade show">
                Failed to delete product. Please try again.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
        case 'status_change_failed':
            $actionMessage = '<div class="alert alert-danger alert-dismissible fade show">
                Failed to change product status. Please try again.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
            break;
    }
}

// Set current page for navigation highlighting
$currentPage = 'manager-product.php';
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
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <link rel="stylesheet" href="../../public/style/product-management.css">
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
    /* Card styling */
    .dashboard-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 20px;
        background: white;
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 20px;
    }
    
    .card-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .card-title {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .card-value {
        font-size: 1.8rem;
        font-weight: 600;
        margin-top: 5px;
        margin-bottom: 0;
        color: #212529;
    }
    
    /* Product thumbnail */
    .product-thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        cursor: pointer;
        background-color: #f8f9fa; /* Add background while loading */
        transition: opacity 0.2s ease; /* Smooth transition when loading */
    }
    
    /* Add a loading indicator */
    .img-loading {
        position: relative;
    }
    
    .img-loading::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.7) url('../../public/assets/loading.gif') center no-repeat;
        background-size: 24px;
        z-index: 1;
    }
    
    /* Force image dimensions to prevent layout shifts */
    .product-image-container {
        width: 80px;
        height: 80px;
        position: relative;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        border-radius: 8px;
        background-color: #f8f9fa;
    }
    
    .custom-card {
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }

    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    /* Page header styling */
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
        font-weight: 600;
        letter-spacing: -0.5px;
    }
    
    /* Status badges */
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    /* Toast styling */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
    }
    
    .toast {
        margin-bottom: 10px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: toast-slide-in 0.3s forwards;
        width: 350px;
        background: white;
        overflow: hidden;
    }
    
    .toast-success {
        border-left: 4px solid #28a745;
    }
    
    .toast-error {
        border-left: 4px solid #dc3545;
    }
    
    .toast-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .toast-title {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
    }
    
    .toast-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    
    .toast-body {
        padding: 15px;
    }
    
    @keyframes toast-slide-in {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes toast-slide-out {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    /* Modal styling for notes */
    .notes-form .form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    /* Loading indicator */
    .loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
  </style>
</head>
<body>
  <!-- Manager Header -->
  <div class="manager-header text-center">
      <h2><i class="bi bi-box"></i> PRODUCT MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
  </div>

  <!-- Loading indicator -->
  <div class="loading" id="loadingIndicator">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../global/manager-sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 product-management-page">
        <!-- Add Breadcrumb -->
        <nav aria-label="breadcrumb" class="mt-3">
          <ol class="breadcrumb bg-white custom-card">
            <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Product Management</li>
          </ol>
        </nav>

        <!-- Display action messages if any -->
        <?php echo $actionMessage; ?>

        <!-- Header Section with updated gradient styling -->
        <div class="page-header">
          <div>
            <h1 class="h2"><i class="bi bi-box-seam text-success"></i> Product Management</h1>
            <p class="text-muted">Manage and monitor product inventory</p>
          </div>
          <div class="d-flex">
            <button class="btn btn-success mr-2" id="exportBtn">
              <i class="bi bi-file-earmark-excel"></i> Export Report
            </button>
            <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#addProductModal">
              <i class="bi bi-plus-lg"></i> Add Product
            </button>
            <!-- Logout Button -->
            <form method="POST">
              <button type="submit" name="logout" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </form>
          </div>
        </div>

        <!-- Display notification toast for updates -->
        <div class="toast-container" id="toastContainer"></div>
        
        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="card-icon text-primary">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="card-title">Total Products</div>
                    <div class="card-value"><?= $totalProducts ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="card-icon text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="card-title">Approved Products</div>
                    <div class="card-value"><?= $activeProducts ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="card-icon text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="card-title">Pending Review</div>
                    <div class="card-value"><?= $pendingProducts ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card">
                    <div class="card-icon text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="card-title">Low Stock</div>
                    <div class="card-value"><?= count($lowStockProducts) ?></div>
                </div>
            </div>
        </div>

        <!-- Enhanced Manager Controls -->
        <div class="admin-controls d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Product Management Console</h4>
                <small class="text-muted">Logged in as: <?= $manager_username ?> (Manager)</small>
            </div>
        </div>

        <!-- Search Bar & Filters -->
        <div class="filter-section custom-card mb-4">
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label><i class="bi bi-search"></i> Search Products</label>
                  <input type="text" id="searchProduct" class="form-control" placeholder="Search by name, description...">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label><i class="bi bi-funnel"></i> Filter by Category</label>
                  <select id="categoryFilter" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= strtolower($category['category_name']) ?>">
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label><i class="bi bi-filter"></i> Status Filter</label>
                  <select class="form-control" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label><i class="bi bi-sort-alpha-down"></i> Sort By</label>
                  <select id="sortFilter" class="form-control">
                    <option value="name">Name A-Z</option>
                    <option value="price">Price (Low to High)</option>
                    <option value="price_desc">Price (High to Low)</option>
                    <option value="stock">Stock Level</option>
                    <option value="newest">Newest First</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary btn-block" id="resetFilterBtn">
                  <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Table -->
        <div class="card custom-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title"><i class="bi bi-grid-3x3"></i> Product List</h5>
                <small class="text-muted">Showing <span id="itemsCount"><?= count($products) ?></span> of <span id="totalItems"><?= $totalProducts ?></span> products</small>
            </div>
            <div class="table-responsive">
              <table class="table table-hover" id="productsTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Farmer</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="productTableBody">
                  <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                      <tr>
                        <td><?= htmlspecialchars($product['product_id']) ?></td>
                        <td>
                          <div class="product-image-container">
                            <?php 
                            $imagePath = !empty($product['image']) ? '../../public/' . htmlspecialchars($product['image']) : '../../public/assets/default-product.png';
                            $imageAlt = htmlspecialchars($product['name'] ?? 'Product');
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 alt="<?= $imageAlt ?>" 
                                 class="product-thumbnail" 
                                 data-toggle="modal" 
                                 data-target="#imageModal" 
                                 data-img-src="<?= $imagePath ?>"
                                 onerror="this.onerror=null; this.src='../../public/assets/default-product.png';">
                          </div>
                        </td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td>
                          <?php
                          $desc = htmlspecialchars($product['description'] ?? '');
                          echo (strlen($desc) > 50) ? substr($desc, 0, 50) . '...' : $desc;
                          ?>
                        </td>
                        <td>₱<?= number_format($product['price'], 2) ?></td>
                        <td>
                          <span class="<?= ($product['stock'] <= 10) ? 'text-danger font-weight-bold' : '' ?>">
                            <?= htmlspecialchars($product['stock'] ?? 0) ?>
                          </span>
                        </td>
                        <td><?= htmlspecialchars($product['farmer_name'] ?? 'N/A') ?></td>
                        <td>
                          <span class="badge <?= $product['status'] === 'approved' ? 'badge-success' : 
                            ($product['status'] === 'pending' ? 'badge-warning' : 'badge-danger') ?> status-badge">
                            <?= ucfirst(htmlspecialchars($product['status'])) ?>
                          </span>
                        </td>
                        <td>
                          <div class="action-btn-container">
                            <!-- View Button -->
                            <button type="button" class="btn btn-info btn-sm view-product"
                                    data-id="<?= $product['product_id'] ?>"
                                    data-toggle="modal" 
                                    data-target="#viewProductModal">
                              <i class="bi bi-eye"></i>
                            </button>
                            
                            <!-- Edit Button (Manager-specific) -->
                            <button type="button" class="btn btn-warning btn-sm edit-product"
                                    data-id="<?= $product['product_id'] ?>"
                                    data-toggle="modal"
                                    data-target="#editProductModal">
                              <i class="bi bi-pencil"></i>
                            </button>
                            
                            <!-- Delete Button (Manager-specific) -->
                            <button type="button" class="btn btn-danger btn-sm delete-product"
                                    data-id="<?= $product['product_id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-toggle="modal"
                                    data-target="#deleteProductModal">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9" class="text-center">No products found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <button id="loadMoreBtn" class="btn btn-primary <?= count($products) >= $totalProducts ? 'd-none' : '' ?>">
                        <i class="bi bi-arrow-down-circle"></i> Load More
                    </button>
                </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Image Modal -->
  <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body text-center">
          <img id="modalImage" src="" alt="Product Image" class="img-fluid rounded">
        </div>
      </div>
    </div>
  </div>
  
  <!-- View Product Modal -->
  <div class="modal fade" id="viewProductModal" tabindex="-1" role="dialog" aria-labelledby="viewProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewProductModalLabel"><i class="bi bi-info-circle"></i> Product Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4 text-center mb-3">
              <img id="view_image" src="" alt="Product Image" class="img-fluid rounded shadow" style="max-height: 200px;">
            </div>
            <div class="col-md-8">
              <h3 id="view_name" class="font-weight-bold"></h3>
              <p id="view_description" class="text-muted my-3"></p>
              
              <div class="row mb-2">
                <div class="col-md-6">
                  <small class="text-muted">Price</small>
                  <h4 id="view_price" class="font-weight-bold text-success"></h4>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Stock</small>
                  <h4 id="view_stock" class="font-weight-bold"></h4>
                </div>
              </div>
              
              <div class="row mb-2">
                <div class="col-md-6">
                  <small class="text-muted">Farmer</small>
                  <p id="view_farmer"></p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Category</small>
                  <p id="view_category"></p>
                </div>
              </div>
              
              <div class="row mb-2">
                <div class="col-md-6">
                  <small class="text-muted">Status</small>
                  <p id="view_status"></p>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">Added Date</small>
                  <p id="view_date"></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-warning" id="viewEditBtn">
            <i class="bi bi-pencil"></i> Edit Product
          </button>
          <button type="button" class="btn btn-success" id="viewChangeStatusBtn" data-toggle="modal" data-target="#changeStatusModal">
            <i class="bi bi-arrow-repeat"></i> Change Status
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Add Product Modal (Manager-specific) -->
  <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel"><i class="bi bi-plus-circle"></i> Add New Product</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="POST" action="manager-product.php" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" name="add_product" value="1">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="name">Product Name*</label>
                  <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                  <label for="price">Price (₱)*</label>
                  <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                  <label for="stock">Stock Quantity*</label>
                  <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="category_id">Category</label>
                  <select class="form-control" id="category_id" name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?= $category['category_id'] ?>">
                        <?= htmlspecialchars($category['category_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="farmer_id">Farmer</label>
                  <select class="form-control" id="farmer_id" name="farmer_id">
                    <option value="">Select Farmer</option>
                    <?php
                    $farmers = $productController->getAllFarmers();
                    foreach ($farmers as $farmer):
                    ?>
                      <option value="<?= $farmer['user_id'] ?>">
                        <?= htmlspecialchars($farmer['username']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="description">Description</label>
                  <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="form-group">
                  <label for="image">Product Image</label>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                    <label class="custom-file-label" for="image">Choose file...</label>
                  </div>
                  <div class="mt-2">
                    <img id="imagePreview" src="#" alt="Preview" style="max-width: 100%; max-height: 150px; display: none;">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Edit Product Modal (Manager-specific) -->
  <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel"><i class="bi bi-pencil-square"></i> Edit Product</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="POST" action="manager-product.php" enctype="multipart/form-data">
          <div class="modal-body">
            <input type="hidden" name="update_product" value="1">
            <input type="hidden" id="edit_product_id" name="product_id">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="edit_name">Product Name*</label>
                  <input type="text" class="form-control" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                  <label for="edit_price">Price (₱)*</label>
                  <input type="number" class="form-control" id="edit_price" name="price" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                  <label for="edit_stock">Stock Quantity*</label>
                  <input type="number" class="form-control" id="edit_stock" name="stock" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="edit_category_id">Category</label>
                  <select class="form-control" id="edit_category_id" name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?= $category['category_id'] ?>">
                        <?= htmlspecialchars($category['category_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="edit_farmer_id">Farmer</label>
                  <select class="form-control" id="edit_farmer_id" name="farmer_id">
                    <option value="">Select Farmer</option>
                    <?php foreach ($farmers as $farmer): ?>
                      <option value="<?= $farmer['user_id'] ?>">
                        <?= htmlspecialchars($farmer['username']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="edit_description">Description</label>
                  <textarea class="form-control" id="edit_description" name="description" rows="4"></textarea>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="form-group">
                  <label for="edit_image">Product Image</label>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="edit_image" name="image" accept="image/*">
                    <label class="custom-file-label" for="edit_image">Choose file...</label>
                  </div>
                  <input type="hidden" id="current_image" name="current_image">
                  <div class="mt-2">
                    <img id="editImagePreview" src="#" alt="Preview" style="max-width: 100%; max-height: 150px; display: none;">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Delete Product Modal (Manager-specific) -->
  <div class="modal fade" id="deleteProductModal" tabindex="-1" role="dialog" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteProductModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="POST" action="manager-product.php">
          <div class="modal-body">
            <p>Are you sure you want to delete the product: <strong id="delete_product_name"></strong>?</p>
            <p class="text-danger">This action cannot be undone.</p>
            <input type="hidden" id="delete_product_id" name="product_id">
            <input type="hidden" name="delete_product" value="1">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Change Status Modal -->
  <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="changeStatusModalLabel"><i class="bi bi-arrow-repeat"></i> Change Product Status</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="POST" action="manager-product.php">
          <div class="modal-body">
            <input type="hidden" id="status_product_id" name="product_id">
            <input type="hidden" name="change_status" value="1">
            
            <div class="form-group">
              <label for="new_status">New Status</label>
              <select class="form-control" id="new_status" name="new_status" required>
                <option value="">Select New Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="status_notes">Notes</label>
              <textarea class="form-control" id="status_notes" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
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

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {
        // Global variables
        let allProducts = <?= json_encode($products) ?>;
        let filteredProducts = [...allProducts];
        let displayCount = 10;
        
        // Initialize page
        updateProductsTable();
        
        // Image preview for add product
        $('#image').change(function() {
            previewImage(this, '#imagePreview');
        });
        
        // Image preview for edit product
        $('#edit_image').change(function() {
            previewImage(this, '#editImagePreview');
        });
        
        // Image preview function
        function previewImage(input, previewElement) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $(previewElement).attr('src', e.target.result);
                    $(previewElement).css('display', 'block');
                }
                reader.readAsDataURL(input.files[0]);
                
                // Update file input label
                $(input).next('.custom-file-label').text(input.files[0].name);
            }
        }
        
        // Reset filters button
        $('#resetFilterBtn').click(function() {
            $('#searchProduct').val('');
            $('#categoryFilter').val('');
            $('#statusFilter').val('');
            $('#sortFilter').val('name');
            filteredProducts = [...allProducts];
            displayCount = 10;
            updateProductsTable();
        });
        
        // Load more products
        $('#loadMoreBtn').click(function() {
            displayCount += 10;
            updateProductsTable();
        });
        
        // Handle export
        $('#exportBtn').click(function() {
            const headers = ['ID', 'Name', 'Description', 'Price', 'Stock', 'Status', 'Farmer', 'Category'];
            let csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n";
            
            filteredProducts.forEach(product => {
                const row = [
                    product.product_id,
                    '"' + (product.name || '').replace(/"/g, '""') + '"',
                    '"' + (product.description || '').replace(/"/g, '""') + '"',
                    parseFloat(product.price || 0).toFixed(2),
                    product.stock || 0,
                    product.status || '',
                    '"' + (product.farmer_name || 'N/A').replace(/"/g, '""') + '"',
                    '"' + (product.category || 'N/A').replace(/"/g, '""') + '"'
                ];
                csvContent += row.join(",") + "\n";
            });
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "product_report.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Apply filters on input/change
        $('#searchProduct, #categoryFilter, #statusFilter, #sortFilter').on('input change', function() {
            applyFilters();
        });
        
        // Apply filters function
        function applyFilters() {
            const searchTerm = $('#searchProduct').val().toLowerCase();
            const category = $('#categoryFilter').val().toLowerCase();
            const status = $('#statusFilter').val().toLowerCase();
            const sort = $('#sortFilter').val();
            
            // Filter products
            filteredProducts = allProducts.filter(product => {
                const nameMatch = (product.name || '').toLowerCase().includes(searchTerm);
                const descMatch = (product.description || '').toLowerCase().includes(searchTerm);
                const categoryMatch = !category || (product.category || '').toLowerCase().includes(category);
                const statusMatch = !status || (product.status || '').toLowerCase() === status;
                
                return (nameMatch || descMatch) && categoryMatch && statusMatch;
            });
            
            // Sort products
            if (sort === 'name') {
                filteredProducts.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            } else if (sort === 'price') {
                filteredProducts.sort((a, b) => parseFloat(a.price || 0) - parseFloat(b.price || 0));
            } else if (sort === 'price_desc') {
                filteredProducts.sort((a, b) => parseFloat(b.price || 0) - parseFloat(a.price || 0));
            } else if (sort === 'stock') {
                filteredProducts.sort((a, b) => parseInt(a.stock || 0) - parseInt(b.stock || 0));
            } else if (sort === 'newest') {
                filteredProducts.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
            }
            
            // Reset display count and update table
            displayCount = 10;
            updateProductsTable();
        }
        
        // Update products table
        function updateProductsTable() {
            const displayedProducts = filteredProducts.slice(0, displayCount);
            const tableBody = $('#productTableBody');
            tableBody.empty();
            
            if (displayedProducts.length === 0) {
                tableBody.append(`
                    <tr>
                        <td colspan="9" class="text-center">No products found matching your criteria</td>
                    </tr>
                `);
            } else {
                displayedProducts.forEach(product => {
                    const imagePath = getImagePath(product.image);
                    const row = `
                        <tr>
                            <td>${product.product_id}</td>
                            <td>
                                <div class="product-image-container">
                                    <img src="${imagePath}"
                                         alt="${product.name || 'Product'}"
                                         class="product-thumbnail img-loading"
                                         data-toggle="modal"
                                         data-target="#imageModal"
                                         data-img-src="${imagePath}"
                                         onerror="this.onerror=null; this.src='../../public/assets/default-product.png'; $(this).removeClass('img-loading');">
                                </div>
                            </td>
                            <td>${product.name || ''}</td>
                            <td>${(product.description && product.description.length > 50) ? 
                                  product.description.substring(0, 50) + '...' : 
                                  (product.description || '')}</td>
                            <td>₱${parseFloat(product.price || 0).toFixed(2)}</td>
                            <td>
                                <span class="${(product.stock <= 10) ? 'text-danger font-weight-bold' : ''}">
                                    ${product.stock || 0}
                                </span>
                            </td>
                            <td>${product.farmer_name || 'N/A'}</td>
                            <td>
                                <span class="badge ${product.status === 'approved' ? 'badge-success' : 
                                        (product.status === 'pending' ? 'badge-warning' : 'badge-danger')} status-badge">
                                    ${product.status ? product.status.charAt(0).toUpperCase() + product.status.slice(1) : 'N/A'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info view-product"
                                        data-id="${product.product_id}"
                                        data-toggle="modal"
                                        data-target="#viewProductModal">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning edit-product"
                                        data-id="${product.product_id}"
                                        data-toggle="modal"
                                        data-target="#editProductModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-product"
                                        data-id="${product.product_id}"
                                        data-name="${product.name || ''}"
                                        data-toggle="modal"
                                        data-target="#deleteProductModal">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            }
            
            // Update counts and load more button
            $('#itemsCount').text(displayedProducts.length);
            $('#totalItems').text(filteredProducts.length);
            
            if (displayedProducts.length >= filteredProducts.length) {
                $('#loadMoreBtn').addClass('d-none');
            } else {
                $('#loadMoreBtn').removeClass('d-none');
            }
            
            // Add event listeners to the new buttons
            addButtonEventListeners();
        }
        
        // Add event listeners to all buttons
        function addButtonEventListeners() {
            // Image modal
            $('.product-thumbnail').click(function() {
                const imgSrc = $(this).data('img-src');
                $('#modalImage').attr('src', imgSrc);
            });
            
            // View product button
            $('.view-product').click(function() {
                const productId = $(this).data('id');
                const product = allProducts.find(p => p.product_id == productId);
                if (product) {
                    populateViewModal(product);
                }
            });
            
            // Edit product button
            $('.edit-product').click(function() {
                const productId = $(this).data('id');
                const product = allProducts.find(p => p.product_id == productId);
                if (product) {
                    populateEditModal(product);
                }
            });
            
            // Delete product button
            $('.delete-product').click(function() {
                const productId = $(this).data('id');
                const productName = $(this).data('name');
                $('#delete_product_id').val(productId);
                $('#delete_product_name').text(productName);
            });
        }
        
        // View - Edit button click
        $('#viewEditBtn').click(function() {
            const productId = $('#status_product_id').val();
            $('#viewProductModal').modal('hide');
            
            // Find product and populate edit modal
            const product = allProducts.find(p => p.product_id == productId);
            if (product) {
                populateEditModal(product);
                $('#editProductModal').modal('show');
            }
        });
        
        // Populate view modal
        function populateViewModal(product) {
            $('#view_name').text(product.name || '');
            $('#view_description').text(product.description || 'No description available');
            $('#view_price').text('₱' + parseFloat(product.price || 0).toFixed(2));
            $('#view_stock').text(product.stock || '0');
            $('#view_farmer').text(product.farmer_name || 'N/A');
            $('#view_category').text(product.category || 'N/A');
            $('#view_date').text(formatDate(product.created_at));
            
            // Set status with badge
            const statusBadge = `
                <span class="badge ${product.status === 'approved' ? 'badge-success' : 
                    (product.status === 'pending' ? 'badge-warning' : 'badge-danger')} status-badge">
                    ${product.status ? product.status.charAt(0).toUpperCase() + product.status.slice(1) : 'N/A'}
                </span>
            `;
            $('#view_status').html(statusBadge);
            
            // Set image
            $('#view_image').attr('src', product.image ? 
                '../../public/' + product.image : 
                '../../public/assets/default-product.png');
            
            // Set product id for change status
            $('#status_product_id').val(product.product_id);
            $('#new_status').val(product.status);
        }
        
        // Populate edit modal
        function populateEditModal(product) {
            $('#edit_product_id').val(product.product_id);
            $('#edit_name').val(product.name || '');
            $('#edit_price').val(product.price || '');
            $('#edit_stock').val(product.stock || '');
            $('#edit_description').val(product.description || '');
            $('#edit_category_id').val(product.category_id || '');
            $('#edit_farmer_id').val(product.farmer_id || '');
            $('#current_image').val(product.image || '');
            
            // Show image preview if available
            if (product.image) {
                $('#editImagePreview').attr('src', '../../public/' + product.image);
                $('#editImagePreview').css('display', 'block');
                $('.custom-file-label[for="edit_image"]').text(product.image.split('/').pop());
            } else {
                $('#editImagePreview').css('display', 'none');
                $('.custom-file-label[for="edit_image"]').text('Choose file...');
            }
        }
        
        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Show toast notification function
        function showToast(title, message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const toastHTML = `
                <div class="toast toast-${type}">
                    <div class="toast-header">
                        <h5 class="toast-title">${title}</h5>
                        <button type="button" class="toast-close">&times;</button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            $('#toastContainer').append(toastHTML);
            $(`#${toastId}`).toast('show');
            
            // Auto remove toast when hidden
            setTimeout(function() {
                $(`#toastContainer .toast`).last().css('animation', 'toast-slide-out 0.3s forwards');
                setTimeout(function() {
                    $(`#toastContainer .toast`).last().remove();
                }, 300);
            }, 3000);
            
            // Close button
            $('.toast-close').click(function() {
                $(this).closest('.toast').css('animation', 'toast-slide-out 0.3s forwards');
                setTimeout(() => {
                    $(this).closest('.toast').remove();
                }, 300);
            });
        }
        
        // Show action messages as toasts if present
        <?php if (isset($_GET['action'])): ?>
            <?php switch($_GET['action']): 
                case 'added': ?>
                    showToast('Success', 'Product has been successfully added.', 'success');
                <?php break; ?>
                
                <?php case 'updated': ?>
                    showToast('Success', 'Product has been successfully updated.', 'success');
                <?php break; ?>
                
                <?php case 'deleted': ?>
                    showToast('Success', 'Product has been successfully deleted.', 'success');
                <?php break; ?>
                
                <?php case 'status_changed': ?>
                    showToast('Success', 'Product status has been successfully updated.', 'success');
                <?php break; ?>
            <?php endswitch; ?>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <?php switch($_GET['error']): 
                case 'add_failed': ?>
                    showToast('Error', 'Failed to add product. Please try again.', 'danger');
                <?php break; ?>
                
                <?php case 'update_failed': ?>
                    showToast('Error', 'Failed to update product. Please try again.', 'danger');
                <?php break; ?>
                
                <?php case 'delete_failed': ?>
                    showToast('Error', 'Failed to delete product. Please try again.', 'danger');
                <?php break; ?>
                
                <?php case 'status_change_failed': ?>
                    showToast('Error', 'Failed to change product status. Please try again.', 'danger');
                <?php break; ?>
            <?php endswitch; ?>
        <?php endif; ?>
        
        // Standardize image path handling between PHP and JS
        function getImagePath(imagePath) {
            if (!imagePath) return '../../public/assets/default-product.png';
            
            // If the path already includes public/ don't add it again
            if (imagePath.includes('public/')) return imagePath;
            
            // Handle relative paths
            if (imagePath.startsWith('uploads/')) {
                return '../../public/' + imagePath;
            }
            
            return '../../public/assets/default-product.png';
        }
        
        // Preload images for smoother experience
        function preloadImages() {
            allProducts.forEach(product => {
                if (product.image) {
                    const img = new Image();
                    img.src = getImagePath(product.image);
                }
            });
        }
        
        // Call preload function
        preloadImages();
        
        // Improved image error handling
        $(document).on('error', '.product-thumbnail', function() {
            $(this).attr('src', '../../public/assets/default-product.png');
            $(this).removeClass('img-loading');
        });
        
        // Make images start loading with a loading class
        $(document).on('load', '.product-thumbnail', function() {
            $(this).removeClass('img-loading');
        });
        
        // Update products table with improved image handling
        function updateProductsTable() {
            const displayedProducts = filteredProducts.slice(0, displayCount);
            const tableBody = $('#productTableBody');
            tableBody.empty();
            
            if (displayedProducts.length === 0) {
                tableBody.append(`
                    <tr>
                        <td colspan="9" class="text-center">No products found matching your criteria</td>
                    </tr>
                `);
            } else {
                displayedProducts.forEach(product => {
                    const imagePath = getImagePath(product.image);
                    const row = `
                        <tr>
                            <td>${product.product_id}</td>
                            <td>
                                <div class="product-image-container">
                                    <img src="${imagePath}"
                                         alt="${product.name || 'Product'}"
                                         class="product-thumbnail img-loading"
                                         data-toggle="modal"
                                         data-target="#imageModal"
                                         data-img-src="${imagePath}"
                                         onerror="this.onerror=null; this.src='../../public/assets/default-product.png'; $(this).removeClass('img-loading');">
                                </div>
                            </td>
                            <td>${product.name || ''}</td>
                            <td>${(product.description && product.description.length > 50) ? 
                                  product.description.substring(0, 50) + '...' : 
                                  (product.description || '')}</td>
                            <td>₱${parseFloat(product.price || 0).toFixed(2)}</td>
                            <td>
                                <span class="${(product.stock <= 10) ? 'text-danger font-weight-bold' : ''}">
                                    ${product.stock || 0}
                                </span>
                            </td>
                            <td>${product.farmer_name || 'N/A'}</td>
                            <td>
                                <span class="badge ${product.status === 'approved' ? 'badge-success' : 
                                        (product.status === 'pending' ? 'badge-warning' : 'badge-danger')} status-badge">
                                    ${product.status ? product.status.charAt(0).toUpperCase() + product.status.slice(1) : 'N/A'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info view-product"
                                        data-id="${product.product_id}"
                                        data-toggle="modal"
                                        data-target="#viewProductModal">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning edit-product"
                                        data-id="${product.product_id}"
                                        data-toggle="modal"
                                        data-target="#editProductModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-product"
                                        data-id="${product.product_id}"
                                        data-name="${product.name || ''}"
                                        data-toggle="modal"
                                        data-target="#deleteProductModal">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            }
            
            // Update counts and load more button
            $('#itemsCount').text(displayedProducts.length);
            $('#totalItems').text(filteredProducts.length);
            
            if (displayedProducts.length >= filteredProducts.length) {
                $('#loadMoreBtn').addClass('d-none');
            } else {
                $('#loadMoreBtn').removeClass('d-none');
            }
            
            // Add event listeners to the new buttons
            addButtonEventListeners();
        }
        
        // Add event listeners to all buttons
        function addButtonEventListeners() {
            // Image modal
            $('.product-thumbnail').click(function() {
                const imgSrc = $(this).data('img-src');
                $('#modalImage').attr('src', imgSrc);
            });
            
            // View product button
            $('.view-product').click(function() {
                const productId = $(this).data('id');
                const product = allProducts.find(p => p.product_id == productId);
                if (product) {
                    populateViewModal(product);
                }
            });
            
            // Edit product button
            $('.edit-product').click(function() {
                const productId = $(this).data('id');
                const product = allProducts.find(p => p.product_id == productId);
                if (product) {
                    populateEditModal(product);
                }
            });
            
            // Delete product button
            $('.delete-product').click(function() {
                const productId = $(this).data('id');
                const productName = $(this).data('name');
                $('#delete_product_id').val(productId);
                $('#delete_product_name').text(productName);
            });
        }
        
        // View - Edit button click
        $('#viewEditBtn').click(function() {
            const productId = $('#status_product_id').val();
            $('#viewProductModal').modal('hide');
            
            // Find product and populate edit modal
            const product = allProducts.find(p => p.product_id == productId);
            if (product) {
                populateEditModal(product);
                $('#editProductModal').modal('show');
            }
        });
        
        // Populate view modal
        function populateViewModal(product) {
            $('#view_name').text(product.name || '');
            $('#view_description').text(product.description || 'No description available');
            $('#view_price').text('₱' + parseFloat(product.price || 0).toFixed(2));
            $('#view_stock').text(product.stock || '0');
            $('#view_farmer').text(product.farmer_name || 'N/A');
            $('#view_category').text(product.category || 'N/A');
            $('#view_date').text(formatDate(product.created_at));
            
            // Set status with badge
            const statusBadge = `
                <span class="badge ${product.status === 'approved' ? 'badge-success' : 
                    (product.status === 'pending' ? 'badge-warning' : 'badge-danger')} status-badge">
                    ${product.status ? product.status.charAt(0).toUpperCase() + product.status.slice(1) : 'N/A'}
                </span>
            `;
            $('#view_status').html(statusBadge);
            
            // Set image
            $('#view_image').attr('src', product.image ? 
                '../../public/' + product.image : 
                '../../public/assets/default-product.png');
            
            // Set product id for change status
            $('#status_product_id').val(product.product_id);
            $('#new_status').val(product.status);
        }
        
        // Populate edit modal
        function populateEditModal(product) {
            $('#edit_product_id').val(product.product_id);
            $('#edit_name').val(product.name || '');
            $('#edit_price').val(product.price || '');
            $('#edit_stock').val(product.stock || '');
            $('#edit_description').val(product.description || '');
            $('#edit_category_id').val(product.category_id || '');
            $('#edit_farmer_id').val(product.farmer_id || '');
            $('#current_image').val(product.image || '');
            
            // Show image preview if available
            if (product.image) {
                $('#editImagePreview').attr('src', '../../public/' + product.image);
                $('#editImagePreview').css('display', 'block');
                $('.custom-file-label[for="edit_image"]').text(product.image.split('/').pop());
            } else {
                $('#editImagePreview').css('display', 'none');
                $('.custom-file-label[for="edit_image"]').text('Choose file...');
            }
        }
        
        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Show toast notification function
        function showToast(title, message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const toastHTML = `
                <div class="toast toast-${type}">
                    <div class="toast-header">
                        <h5 class="toast-title">${title}</h5>
                        <button type="button" class="toast-close">&times;</button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            $('#toastContainer').append(toastHTML);
            $(`#${toastId}`).toast('show');
            
            // Auto remove toast when hidden
            setTimeout(function() {
                $(`#toastContainer .toast`).last().css('animation', 'toast-slide-out 0.3s forwards');
                setTimeout(function() {
                    $(`#toastContainer .toast`).last().remove();
                }, 300);
            }, 3000);
            
            // Close button
            $('.toast-close').click(function() {
                $(this).closest('.toast').css('animation', 'toast-slide-out 0.3s forwards');
                setTimeout(() => {
                    $(this).closest('.toast').remove();
                }, 300);
            });
        }
        
        // Show action messages as toasts if present
        <?php if (isset($_GET['action'])): ?>
            <?php switch($_GET['action']): 
                case 'added': ?>
                    showToast('Success', 'Product has been successfully added.', 'success');
                <?php break; ?>
                
                <?php case 'updated': ?>
                    showToast('Success', 'Product has been successfully updated.', 'success');
                <?php break; ?>
                
                <?php case 'deleted': ?>
                    showToast('Success', 'Product has been successfully deleted.', 'success');
                <?php break; ?>
                
                <?php case 'status_changed': ?>
                    showToast('Success', 'Product status has been successfully updated.', 'success');
                <?php break; ?>
            <?php endswitch; ?>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <?php switch($_GET['error']): 
                case 'add_failed': ?>
                    showToast('Error', 'Failed to add product. Please try again.', 'danger');
                <?php break; ?>
                
                <?php case 'update_failed': ?>
                    showToast('Error', 'Failed to update product. Please try again.', 'danger');
                <?php break; ?>
                
                <?php case 'delete_failed': ?>
                    showToast('Error', 'Failed to delete product. Please try again.', 'danger');
                <?php break; ?>
                
                <?php case 'status_change_failed': ?>
                    showToast('Error', 'Failed to change product status. Please try again.', 'danger');
                <?php break; ?>
            <?php endswitch; ?>
        <?php endif; ?>
    });
  </script>
</body>
</html>
