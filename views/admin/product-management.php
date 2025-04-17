<?php
session_start();

// Security check - Restrict access to authorized users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // Log unauthorized access attempt
    require_once '../../models/Log.php';
    $logClass = new Log();
    $logClass->logActivity($_SESSION['user_id'] ?? 'Unknown', "Unauthorized access attempt to product management");
    
    header("Location: admin-login.php?error=unauthorized");
    exit();
}

require_once '../../controllers/ProductController.php';
require_once '../../models/Log.php';

$productController = new ProductController();
$logClass = new Log();

// Get all products with full details including categories
$products = $productController->getAllProductsWithDetails();

// Get statistics
$totalProducts = $productController->getProductCount();
$pendingApproval = $productController->getProductCountByStatus('pending');
$rejectedProducts = $productController->getProductCountByStatus('rejected');
$lowStockProducts = $productController->getLowStockProducts(5);

// Get all categories for filters and forms
$categories = $productController->getCategoriesOptimized();

// Handle batch operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['batch_action']) && isset($_POST['selected_products'])) {
        $selectedProducts = $_POST['selected_products'];
        $action = $_POST['batch_action'];
        $user_id = $_SESSION['user_id'] ?? null;
        
        foreach ($selectedProducts as $productId) {
            switch($action) {
                case 'approve':
                    $productController->updateProductStatus($productId, 'approved', '', $user_id);
                    break;
                case 'reject':
                    $productController->updateProductStatus($productId, 'rejected', '', $user_id);
                    break;
                case 'delete':
                    $productController->deleteProduct($productId);
                    break;
            }
        }
        
        $logClass->logActivity($user_id, "Admin performed batch $action on " . count($selectedProducts) . " products");
        header("Location: product-management.php?batch_action=" . $action);
        exit();
    }
    
    // Handle individual product status changes
    if (isset($_POST['reject_product']) && !empty($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $user_id = $_SESSION['user_id'];
        $notes = isset($_POST['reject_notes']) ? $_POST['reject_notes'] : '';
        
        if ($productController->updateProductStatus($product_id, 'rejected', $notes, $user_id)) {
            $logClass->logActivity($user_id, 'Rejected product with ID: ' . $product_id);
            
            // Get product and farmer details for notification
            $product = $productController->getProductById($product_id);
            if ($product && isset($product['farmer_id'])) {
                // Add notification for the farmer with type and reference
                require_once '../../models/Notification.php';
                $notificationModel = new Notification();
                $notificationMessage = "Your product \"" . htmlspecialchars($product['name']) . "\" has been rejected. Reason: " . htmlspecialchars($notes);
                $notificationModel->addNotification(
                    $product['farmer_id'], 
                    $notificationMessage,
                    'product_rejected',
                    $product_id
                );
            }
            
            header("Location: product-management.php?action=reject&success=1&message=" . urlencode("Product rejected successfully. Farmer has been notified."));
        } else {
            header("Location: product-management.php?action=reject&error=" . urlencode($productController->getLastError()));
        }
        exit();
    }

    if (isset($_POST['approve_product']) && !empty($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $user_id = $_SESSION['user_id'];
        
        if ($productController->updateProductStatus($product_id, 'approved', '', $user_id)) {
            $logClass->logActivity($user_id, 'Approved product with ID: ' . $product_id);
            
            // Get product and farmer details for notification
            $product = $productController->getProductById($product_id);
            if ($product && isset($product['farmer_id'])) {
                // Add notification for the farmer with type and reference
                require_once '../../models/Notification.php';
                $notificationModel = new Notification();
                $notificationMessage = "Good news! Your product \"" . htmlspecialchars($product['name']) . "\" has been approved and is now available in the marketplace.";
                $notificationModel->addNotification(
                    $product['farmer_id'], 
                    $notificationMessage,
                    'product_approved',
                    $product_id
                );
            }
            
            $productName = htmlspecialchars($product['name']);
            header("Location: product-management.php?action=approve&success=1&product_id=$product_id&product_name=" . urlencode($productName) . "&message=" . urlencode("Product approved successfully. Farmer has been notified."));
        } else {
            header("Location: product-management.php?action=approve&error=" . urlencode($productController->getLastError()));
        }
        exit();
    }

    // Handle logout
    if (isset($_POST['logout'])) {
        $logClass->logActivity($_SESSION['user_id'], 'Admin logged out');
        session_unset();
        session_destroy();
        header("Location: admin-login.php");
        exit();
    }
}

// Handle AJAX requests for inline editing
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    // Update product price
    if ($_POST['ajax_action'] === 'update_price' && isset($_POST['product_id']) && isset($_POST['new_price'])) {
        $productId = (int)$_POST['product_id'];
        $newPrice = (float)$_POST['new_price'];
        
        if ($newPrice <= 0) {
            $response = ['success' => false, 'message' => 'Price must be greater than zero'];
        } else {
            $product = $productController->getProductById($productId);
            if ($product) {
                $result = $productController->editProduct(
                    $productId,
                    null, // name - unchanged
                    null, // description - unchanged
                    $newPrice, // new price
                    null, // stock - unchanged
                    null, // farmer_id - unchanged 
                    null, // image - unchanged
                    $product['image'] ?? null, // current image
                    null  // unit_type - unchanged
                );
                
                if ($result) {
                    $logClass->logActivity($_SESSION['user_id'], "Updated price for product #$productId to ₱$newPrice");
                    $response = [
                        'success' => true, 
                        'message' => 'Price updated successfully',
                        'new_price' => $newPrice
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update price: ' . $productController->getLastError()];
                }
            } else {
                $response = ['success' => false, 'message' => 'Product not found'];
            }
        }
    }
    
    // Update product stock
    if ($_POST['ajax_action'] === 'update_stock' && isset($_POST['product_id']) && isset($_POST['new_stock'])) {
        $productId = (int)$_POST['product_id'];
        $newStock = (int)$_POST['new_stock'];
        
        if ($newStock < 0) {
            $response = ['success' => false, 'message' => 'Stock cannot be negative'];
        } else {
            $product = $productController->getProductById($productId);
            if ($product) {
                $result = $productController->editProduct(
                    $productId,
                    null, // name - unchanged
                    null, // description - unchanged
                    null, // price - unchanged
                    $newStock, // new stock
                    null, // farmer_id - unchanged 
                    null, // image - unchanged
                    $product['image'] ?? null, // current image
                    null  // unit_type - unchanged
                );
                
                if ($result) {
                    $logClass->logActivity($_SESSION['user_id'], "Updated stock for product #$productId to $newStock");
                    $response = [
                        'success' => true, 
                        'message' => 'Stock updated successfully',
                        'new_stock' => $newStock,
                        'is_low_stock' => ($newStock <= 10)
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update stock: ' . $productController->getLastError()];
                }
            } else {
                $response = ['success' => false, 'message' => 'Product not found'];
            }
        }
    }
    
    // Update product name
    if ($_POST['ajax_action'] === 'update_name' && isset($_POST['product_id']) && isset($_POST['new_name'])) {
        $productId = (int)$_POST['product_id'];
        $newName = trim($_POST['new_name']);
        
        if (empty($newName)) {
            $response = ['success' => false, 'message' => 'Product name cannot be empty'];
        } else {
            $product = $productController->getProductById($productId);
            if ($product) {
                $result = $productController->editProduct(
                    $productId,
                    $newName, // new name
                    null, // description - unchanged
                    null, // price - unchanged
                    null, // stock - unchanged
                    null, // farmer_id - unchanged 
                    null, // image - unchanged
                    $product['image'] ?? null, // current image
                    null  // unit_type - unchanged
                );
                
                if ($result) {
                    $logClass->logActivity($_SESSION['user_id'], "Updated name for product #$productId to '$newName'");
                    $response = [
                        'success' => true, 
                        'message' => 'Product name updated successfully',
                        'new_name' => $newName
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update product name: ' . $productController->getLastError()];
                }
            } else {
                $response = ['success' => false, 'message' => 'Product not found'];
            }
        }
    }
    
    // Get product details for edit modal
    if ($_POST['ajax_action'] === 'get_product_details' && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
        $product = $productController->getProductWithDetails($productId);
        
        if ($product) {
            $response = [
                'success' => true,
                'product' => $product
            ];
        } else {
            $response = ['success' => false, 'message' => 'Product not found'];
        }
    }
    
    // Update full product details from modal
    if ($_POST['ajax_action'] === 'update_product' && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
        
        // Create data array for controller
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => (float)$_POST['price'],
            'stock' => (int)$_POST['stock'],
            'farmer_id' => (int)$_POST['farmer_id'],
            'category_id' => (int)$_POST['category_id'],
            'unit_type' => $_POST['unit_type'],
            'current_image' => $_POST['current_image']
        ];
        
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $data['image'] = $_FILES['image'];
        }
        
        $result = $productController->updateProduct($productId, $data);
        
        if ($result) {
            $logClass->logActivity($_SESSION['user_id'], "Updated full details for product #$productId");
            $response = [
                'success' => true,
                'message' => 'Product updated successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to update product: ' . $productController->getLastError()
            ];
        }
    }
    
    echo json_encode($response);
    exit();
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Product Management</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <link rel="stylesheet" href="../../public/style/product-management.css">
  <style>
    /* Admin dashboard styles */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    
    .admin-header {
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      color: white;
      padding: 10px 0;
      margin-bottom: 20px;
    }
    
    .admin-badge {
      background-color: #6a11cb;
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
    }
    
    /* Batch action buttons styling */
    .admin-controls .btn {
      height: 36px; /* Fixed height */
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
      padding: 0.25rem 0.5rem;
      margin: 0 4px; /* Even spacing between buttons */
      min-width: 110px; /* Minimum width to ensure uniformity */
    }
    
    .admin-controls .d-flex {
      gap: 8px; /* Consistent spacing between flex items */
    }
    
    .admin-controls .bi {
      margin-right: 6px;
      font-size: 1rem;
    }
    
    .admin-controls .card {
      border: none;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    }
    
    .admin-controls .card-body {
      padding: 1rem;
    }
    
    .admin-controls .card-title {
      font-size: 1rem;
      margin-bottom: 0.75rem;
    }
    
    /* Product card and table styling */
    .card {
      border-radius: 8px;
      border: none;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 20px;
    }
    
    .card-stats .card-body {
      padding: 15px;
    }
    
    .card-stats .icon-big {
      font-size: 3em;
      color: #6a11cb;
    }
    
    .card-category {
      font-size: 14px;
      color: #888;
      margin-bottom: 0;
    }
    
    .card-title {
      font-weight: 500;
      margin-bottom: 0;
    }
    
    .table-container {
      max-height: 600px;
      overflow-y: auto;
    }
    
    /* Product image styling */
    .product-thumbnail {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 4px;
      cursor: pointer;
      border: 1px solid #eee;
    }
    
    .no-image {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f5f5f5;
      border-radius: 4px;
      font-size: 10px;
      color: #999;
    }
    
    /* Status badge styling */
    .badge-success {
      background-color: #28a745;
    }
    
    .badge-warning {
      background-color: #ffc107;
      color: #212529;
    }
    
    .badge-danger {
      background-color: #dc3545;
    }
    
    .badge-secondary {
      background-color: #6c757d;
    }
    
    /* Action buttons styling */
    .action-btn-group {
      display: flex;
      gap: 5px;
    }
    
    .action-btn {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      color: white;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .action-btn-approve {
      background-color: #28a745;
    }
    
    .action-btn-reject {
      background-color: #ffc107;
      color: #212529;
    }
    
    .action-btn-edit {
      background-color: #17a2b8;
    }
    
    .action-btn-delete {
      background-color: #dc3545;
    }
    
    .action-btn:hover {
      transform: scale(1.1);
    }
    
    /* Editable cells styling */
    .editable {
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .editable:hover {
      background-color: #f8f9fa;
    }
    
    .inline-edit-input {
      padding: 2px 5px;
    }
    
    /* Low stock warning */
    .stock-warning {
      color: #dc3545;
      font-weight: 500;
    }
    
    .unit-badge {
      display: inline-block;
      font-size: 10px;
      background-color: #f0f0f0;
      color: #888;
      padding: 1px 5px;
      border-radius: 3px;
      margin-left: 5px;
    }
    
    /* Toast notifications */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }
    
    .toast {
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      margin-bottom: 10px;
      overflow: hidden;
      width: 300px;
      animation: toast-slide-in 0.3s;
    }
    
    .toast-success .toast-header {
      background-color: #28a745;
      color: white;
    }
    
    .toast-error .toast-header {
      background-color: #dc3545;
      color: white;
    }
    
    .toast-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 15px;
    }
    
    .toast-title {
      margin: 0;
      font-size: 16px;
      font-weight: 500;
    }
    
    .toast-close {
      background: none;
      border: none;
      color: inherit;
      font-size: 18px;
      cursor: pointer;
    }
    
    .toast-body {
      padding: 15px;
      font-size: 14px;
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
    
    /* Modal customizations */
    .modal-content {
      border-radius: 8px;
      border: none;
    }
    
    /* Search and filter area */
    .filters-container {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .search-control {
      border-radius: 6px;
    }
    
    /* Description cell */
    .description-cell {
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  </style>
</head>
<body>
  <!-- Admin Header -->
  <div class="admin-header text-center">
      <h2><i class="bi bi-shield-lock"></i> ADMIN CONTROL PANEL <span class="admin-badge">Restricted Access</span></h2>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../../views/global/admin-sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 product-management-page">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Product Management</li>
          </ol>
        </nav>

        <!-- Page Header -->
        <div class="page-header">
          <h1 class="h2"><i class="bi bi-box-seam"></i> Product Management</h1>
          <div class="d-flex">
            <button class="btn btn-success mr-2" id="exportBtn">
              <i class="bi bi-file-earmark-excel"></i> Export Products
            </button>
            
            <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#createProductModal">
              <i class="bi bi-plus-circle"></i> Add New Product
            </button>

            <!-- Logout Button -->
            <form method="POST" class="ml-3">
              <button type="submit" name="logout" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </form>
          </div>
        </div>

        <!-- Toast Container for Notifications -->
        <div class="toast-container" id="toastContainer"></div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center text-primary">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Total Products</p>
                                    <h4 class="card-title"><?= $totalProducts ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>  
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center text-warning">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Pending Approval</p>
                                    <h4 class="card-title"><?= $pendingApproval ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center text-danger">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Rejected</p>
                                    <h4 class="card-title"><?= $rejectedProducts ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center text-info">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Low Stock</p>
                                    <h4 class="card-title"><?= count($lowStockProducts) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Controls -->
        <div class="admin-controls mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title mb-1"><i class="bi bi-layers"></i> Bulk Actions</h5>
                            <small class="text-muted">Logged in as: <?= $_SESSION['username'] ?> (Administrator)</small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between">
                        <button class="btn btn-outline-primary flex-fill mx-1 mb-0" id="selectAllBtn">
                            <i class="bi bi-check-all"></i> Select All
                        </button>
                        <button class="btn btn-success flex-fill mx-1 mb-0" id="batchApproveBtn" disabled>
                            <i class="bi bi-check2"></i> Approve
                        </button>
                        <button class="btn btn-warning flex-fill mx-1 mb-0" id="batchRejectBtn" disabled>
                            <i class="bi bi-x"></i> Reject
                        </button>
                        <button class="btn btn-danger flex-fill mx-1 mb-0" id="batchDeleteBtn" disabled>
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-container">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group mb-md-0">
                <label for="productSearch"><i class="bi bi-search"></i> Search Products</label>
                <input type="text" id="productSearch" class="form-control search-control" placeholder="Search by name, description...">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-md-0">
                <label for="categoryFilter"><i class="bi bi-funnel"></i> Category</label>
                <select class="form-control search-control" id="categoryFilter">
                  <option value="">All Categories</option>
                  <?php foreach($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['category_id']) ?>">
                      <?= htmlspecialchars($category['category_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-md-0">
                <label for="priceMin">Min Price</label>
                <input type="number" id="priceMin" class="form-control search-control" placeholder="₱0">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-md-0">
                <label for="priceMax">Max Price</label>
                <input type="number" id="priceMax" class="form-control search-control" placeholder="₱1000">
              </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button class="btn btn-primary btn-block" id="filterButton">
                <i class="bi bi-filter"></i> Filter
              </button>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Status Filter</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 mt-3">
                <button class="btn btn-outline-secondary btn-block" id="resetFilterBtn">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                </button>
            </div>
          </div>
        </div>

        <!-- Product Table with Inline Editing -->
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title"><i class="bi bi-grid-3x3"></i> Product List</h5>
                <small class="text-muted">Showing <?= count($products) ?> of <?= $totalProducts ?> products</small>
            </div>
            <div class="table-responsive table-container">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="productTableBody">
                  <?php foreach ($products as $product): ?>
                    <tr class="product-row" 
                        data-id="<?= $product['product_id'] ?>"
                        data-status="<?= htmlspecialchars($product['status'] ?? 'pending') ?>"
                        data-category="<?= htmlspecialchars($product['category_id'] ?? '') ?>">
                        
                      <td><input type="checkbox" class="product-checkbox" value="<?= htmlspecialchars($product['product_id']) ?>"></td>
                      <td>#<?= $product['product_id'] ?></td>
                      <td>
                        <?php if (!empty($product['image'])): ?>
                          <img src="<?= '../../public/' . htmlspecialchars($product['image']) ?>" 
                               alt="Product Image" 
                               class="product-image product-thumbnail" 
                               data-toggle="modal" 
                               data-target="#imageModal" 
                               data-img-src="<?= '../../public/' . htmlspecialchars($product['image']) ?>">
                        <?php else: ?>
                          <div class="no-image">No Image</div>
                        <?php endif; ?>
                      </td>
                      <td class="editable editable-name" data-id="<?= htmlspecialchars($product['product_id']) ?>" title="Double-click to edit">
                        <?= htmlspecialchars($product['name']) ?>
                      </td>
                      <td class="description-cell">
                        <?= mb_strimwidth(htmlspecialchars($product['description'] ?? ''), 0, 50, "...") ?>
                      </td>
                      <td class="editable editable-price" data-id="<?= htmlspecialchars($product['product_id']) ?>" title="Double-click to edit">
                        ₱<?= number_format($product['price'], 2) ?>
                      </td>
                      <td class="editable editable-stock" data-id="<?= htmlspecialchars($product['product_id']) ?>" title="Double-click to edit">
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
                        <div class="action-btn-group">
                            <?php if ($product['status'] !== 'approved'): ?>
                                <button type="button" class="action-btn action-btn-approve" data-id="<?= $product['product_id'] ?>" title="Approve Product">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($product['status'] !== 'rejected'): ?>
                                <button type="button" class="action-btn action-btn-reject" data-id="<?= $product['product_id'] ?>" title="Reject Product">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="action-btn action-btn-edit" data-id="<?= $product['product_id'] ?>" title="Edit Product">
                                <i class="bi bi-pencil"></i>
                            </button>
                            
                            <button type="button" class="action-btn action-btn-delete" data-id="<?= $product['product_id'] ?>" title="Delete Product">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Image Preview Modal -->
  <div class="modal fade image-preview-modal" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <img id="modalImage" src="" alt="Product Image" class="img-fluid">
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel">
            <i class="bi bi-pencil-square"></i> Edit Product
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="editProductForm" enctype="multipart/form-data">
            <input type="hidden" id="edit_product_id" name="product_id">
            <input type="hidden" name="ajax_action" value="update_product">
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="edit_name">Product Name</label>
                  <input type="text" class="form-control" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                  <label for="edit_description">Description</label>
                  <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                  <label for="edit_price">Price (₱)</label>
                  <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                  <label for="edit_stock">Stock</label>
                  <input type="number" class="form-control" id="edit_stock" name="stock" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="edit_unit_type">Unit Type</label>
                  <select class="form-control" id="edit_unit_type" name="unit_type">
                    <?php foreach($productController->getUnitTypes() as $unit): ?>
                      <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars(ucfirst($unit)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="edit_category_id">Category</label>
                  <select class="form-control" id="edit_category_id" name="category_id">
                    <?php foreach($categories as $category): ?>
                      <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="edit_farmer_id">Farmer</label>
                  <select class="form-control" id="edit_farmer_id" name="farmer_id">
                    <?php foreach($productController->getAllFarmers() as $farmer): ?>
                      <option value="<?= htmlspecialchars($farmer['user_id']) ?>"><?= htmlspecialchars($farmer['username']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Current Image</label>
                  <div id="current_image_container" class="mb-2"></div>
                  <input type="hidden" id="edit_current_image" name="current_image">
                  <input type="file" class="form-control-file" id="edit_image" name="image" accept="image/*">
                </div>
              </div>
            </div>
            <div class="text-right mt-3">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Product Modal -->
  <div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createProductModalLabel">
            <i class="bi bi-plus-circle"></i> Add New Product
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="createProductForm" method="POST" action="../../controllers/AjaxController.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="createProduct">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Product Name</label>
                  <input type="text" class="form-control" name="name" required>
                </div>
                <div class="form-group">
                  <label>Description</label>
                  <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                  <label>Price (₱)</label>
                  <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                  <label>Stock</label>
                  <input type="number" class="form-control" name="stock" min="0" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Unit Type</label>
                  <select class="form-control" name="unit_type">
                    <?php foreach($productController->getUnitTypes() as $unit): ?>
                      <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars(ucfirst($unit)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Category</label>
                  <select class="form-control" name="category_id">
                    <?php foreach($categories as $category): ?>
                      <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Farmer</label>
                  <select class="form-control" name="farmer_id">
                    <?php foreach($productController->getAllFarmers() as $farmer): ?>
                      <option value="<?= htmlspecialchars($farmer['user_id']) ?>"><?= htmlspecialchars($farmer['username']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Product Image</label>
                  <input type="file" class="form-control-file" name="image" accept="image/*">
                  <small class="form-text text-muted">Upload an image for the product. Recommended size: 500x500 pixels.</small>
                </div>
              </div>
            </div>
            <div class="text-right mt-3">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Create Product</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p id="confirmMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmAction">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Rejection Notes Modal -->
  <div class="modal fade" id="rejectNotesModal" tabindex="-1" aria-labelledby="rejectNotesModalLabel">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="rejectNotesModalLabel">
            <i class="bi bi-x-circle text-danger"></i> Reject Product
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="rejectNotesForm" method="POST">
            <input type="hidden" id="rejectModalProductId" name="product_id">
            <input type="hidden" name="reject_product" value="1">
            
            <p>You are about to reject: <strong id="rejectModalProductName"></strong></p>
            <p class="text-muted">Please provide a reason for rejection. This will be sent to the farmer.</p>
            
            <div class="form-group">
              <label for="reject_notes">Rejection Reason:</label>
              <textarea class="form-control" id="reject_notes" name="reject_notes" rows="3" required></textarea>
            </div>
            
            <div class="text-right mt-3">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Confirmation Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="successModalLabel">
            <i class="bi bi-check-circle"></i> Success
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body text-center py-4">
          <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          </div>
          <h4 id="successModalMessage">Operation Completed Successfully</h4>
          <p id="successModalDetails" class="text-muted mt-2"></p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-success px-4" data-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {
        // Show toast notification function
        function showToast(title, message, type = 'success') {
            const toast = `
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
            
            $('#toastContainer').append(toast);
            
            // Auto-remove toast after 3 seconds
            const $toast = $('#toastContainer .toast').last();
            setTimeout(function() {
                $toast.css('animation', 'toast-slide-out 0.3s forwards');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
            
            // Close button
            $toast.find('.toast-close').click(function() {
                $toast.css('animation', 'toast-slide-out 0.3s forwards');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            });
        }

        // Image modal
        $('#imageModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const imgSrc = button.data('img-src');
            $(this).find('#modalImage').attr('src', imgSrc);
        });

        // Handle select all checkbox
        $('#selectAll').change(function() {
            $('.product-checkbox').prop('checked', $(this).is(':checked'));
            updateBatchButtons();
        });

        // Handle individual checkboxes
        $('.product-checkbox').change(function() {
            updateBatchButtons();
        });

        // Update batch action buttons state
        function updateBatchButtons() {
            const checkedCount = $('.product-checkbox:checked').length;
            $('#batchApproveBtn, #batchRejectBtn, #batchDeleteBtn').prop('disabled', checkedCount === 0);
        }

        // "Select All" button click
        $('#selectAllBtn').click(function() {
            $('#selectAll').prop('checked', !$('#selectAll').prop('checked')).change();
        });

        // Batch actions
        $('#batchApproveBtn').click(function() { submitBatchAction('approve'); });
        $('#batchRejectBtn').click(function() { submitBatchAction('reject'); });
        $('#batchDeleteBtn').click(function() {
            $('#confirmMessage').text('Are you sure you want to delete the selected products?');
            $('#confirmAction').data('action', 'delete-batch');
            $('#confirmModal').modal('show');
        });

        // Confirm action button
        $('#confirmAction').click(function() {
            const action = $(this).data('action');
            const id = $(this).data('id');
            
            $('#confirmModal').modal('hide');
            
            if (action === 'delete-batch') {
                submitBatchAction('delete');
            } else if (action === 'delete-product') {
                // Implement product deletion
                // AJAX call to delete single product
                // Future implementation
            }
        });

        function submitBatchAction(action) {
            const selectedProducts = $('.product-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            const form = $('<form>', {
                'method': 'POST',
                'action': window.location.href
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'batch_action',
                'value': action
            }));

            selectedProducts.forEach(function(productId) {
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'selected_products[]',
                    'value': productId
                }));
            });

            $(document.body).append(form);
            form.submit();
        }

        // Search and filter functionality
        $("#productSearch").on("keyup", function() {
            applyFilters();
        });
        
        $("#statusFilter, #categoryFilter").on("change", function() {
            applyFilters();
        });
        
        $("#filterButton").click(function() {
            applyFilters();
        });
        
        $("#resetFilterBtn").click(function() {
            $('#productSearch').val('');
            $('#categoryFilter').val('');
            $('#statusFilter').val('');
            $('#priceMin').val('');
            $('#priceMax').val('');
            applyFilters();
        });
        
        function applyFilters() {
            const search = $('#productSearch').val().toLowerCase();
            const category = $('#categoryFilter').val();
            const status = $('#statusFilter').val().toLowerCase();
            const minPrice = parseFloat($('#priceMin').val()) || 0;
            const maxPrice = parseFloat($('#priceMax').val()) || Infinity;

            $('.product-row').each(function() {
                const $row = $(this);
                const name = $row.find('td:eq(3)').text().toLowerCase();
                const description = $row.find('td:eq(4)').text().toLowerCase();
                const price = parseFloat($row.find('td:eq(5)').text().replace('₱', '').replace(',', ''));
                const rowCategory = $row.data('category');
                const rowStatus = $row.data('status');
                
                const matchesSearch = name.includes(search) || description.includes(search);
                const matchesCategory = !category || rowCategory == category;
                const matchesStatus = !status || rowStatus == status;
                const matchesPrice = price >= minPrice && price <= maxPrice;

                $row.toggle(matchesSearch && matchesCategory && matchesStatus && matchesPrice);
            });
        }

        // Handle Export
        $('#exportBtn').click(function() {
            // Build CSV data
            const rows = ['Product ID,Name,Description,Price,Stock,Category,Status'];
            
            $('.product-row:visible').each(function() {
                const $row = $(this);
                const id = $row.find('td:eq(1)').text().replace('#', '');
                const name = '"' + $row.find('td:eq(3)').text().replace(/"/g, '""') + '"';
                const description = '"' + $row.find('td:eq(4)').text().replace(/"/g, '""') + '"';
                const price = $row.find('td:eq(5)').text().replace('₱', '').replace(',', '');
                const stock = $row.find('td:eq(6)').text().trim().split(' ')[0];
                const category = '"' + $row.find('td:eq(7)').text().replace(/"/g, '""') + '"';
                const status = $row.find('td:eq(8) .badge').text();
                
                rows.push(`${id},${name},${description},${price},${stock},${category},${status}`);
            });

            // Create download link
            const csvContent = rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            const date = new Date().toISOString().split('T')[0];
            
            link.href = url;
            link.setAttribute('download', `products_export_${date}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showToast('Success', 'Products exported successfully');
        });

        // Inline editing - Product Name
        $('.editable-name').dblclick(function() {
            const $cell = $(this);
            const productId = $cell.data('id');
            const currentValue = $cell.text().trim();
            
            // Create input field
            $cell.html(`<input type="text" class="form-control form-control-sm inline-edit-input" value="${currentValue}">`);
            const $input = $cell.find('input');
            $input.focus();
            
            // Handle blur event (submit on focus lost)
            $input.blur(function() {
                const newValue = $input.val().trim();
                
                if (newValue !== currentValue && newValue !== '') {
                    // Send AJAX request to update
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            ajax_action: 'update_name',
                            product_id: productId,
                            new_name: newValue
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $cell.html(newValue);
                                showToast('Success', 'Product name updated successfully');
                            } else {
                                $cell.html(currentValue);
                                showToast('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            $cell.html(currentValue);
                            showToast('Error', 'Server error occurred', 'error');
                        }
                    });
                } else {
                    $cell.html(currentValue);
                }
            });
            
            // Submit on enter key
            $input.keypress(function(e) {
                if (e.which === 13) {
                    $input.blur();
                }
            });
        });

        // Inline editing - Price
        $('.editable-price').dblclick(function() {
            const $cell = $(this);
            const productId = $cell.data('id');
            const currentPrice = parseFloat($cell.text().replace('₱', '').replace(',', '').trim());
            
            $cell.html('<input type="number" class="form-control form-control-sm inline-edit-input" ' +
                      'min="0.01" step="0.01" value="'+currentPrice+'">');
            const $input = $cell.find('input');
            $input.focus();
            
            // Handle blur event
            $input.blur(function() {
                const newPrice = parseFloat($input.val());
                
                if (newPrice > 0 && newPrice !== currentPrice) {
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            ajax_action: 'update_price',
                            product_id: productId,
                            new_price: newPrice
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $cell.html('₱' + newPrice.toFixed(2));
                                showToast('Success', 'Price updated successfully');
                            } else {
                                $cell.html('₱' + currentPrice.toFixed(2));
                                showToast('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            $cell.html('₱' + currentPrice.toFixed(2));
                            showToast('Error', 'Failed to update price', 'error');
                        }
                    });
                } else {
                    $cell.html('₱' + currentPrice.toFixed(2));
                }
            });
            
            // Submit on enter key
            $input.keypress(function(e) {
                if (e.which === 13) {
                    $input.blur();
                }
            });
        });

        // Inline editing - Stock
        $('.editable-stock').dblclick(function() {
            const $cell = $(this);
            const productId = $cell.data('id');
            const unitType = $cell.find('.unit-badge').text();
            const currentStock = parseInt($cell.text().replace(unitType, '').trim());
            const hasWarning = $cell.find('.stock-warning').length > 0;
            
            $cell.html('<input type="number" class="form-control form-control-sm inline-edit-input" ' +
                      'min="0" step="1" value="'+currentStock+'">');
            const $input = $cell.find('input');
            $input.focus();
            
            // Handle blur event
            $input.blur(function() {
                const newStock = parseInt($input.val());
                
                if (newStock >= 0 && newStock !== currentStock) {
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            ajax_action: 'update_stock',
                            product_id: productId,
                            new_stock: newStock
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Check if low stock warning needed
                                if (response.is_low_stock) {
                                    $cell.html(`<span class="stock-warning">${newStock}</span> <span class="unit-badge">${unitType}</span>`);
                                } else {
                                    $cell.html(`${newStock} <span class="unit-badge">${unitType}</span>`);
                                }
                                showToast('Success', 'Stock updated successfully');
                            } else {
                                if (hasWarning) {
                                    $cell.html(`<span class="stock-warning">${currentStock}</span> <span class="unit-badge">${unitType}</span>`);
                                } else {
                                    $cell.html(`${currentStock} <span class="unit-badge">${unitType}</span>`);
                                }
                                showToast('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            if (hasWarning) {
                                $cell.html(`<span class="stock-warning">${currentStock}</span> <span class="unit-badge">${unitType}</span>`);
                            } else {
                                $cell.html(`${currentStock} <span class="unit-badge">${unitType}</span>`);
                            }
                            showToast('Error', 'Failed to update stock', 'error');
                        }
                    });
                } else {
                    if (hasWarning) {
                        $cell.html(`<span class="stock-warning">${currentStock}</span> <span class="unit-badge">${unitType}</span>`);
                    } else {
                        $cell.html(`${currentStock} <span class="unit-badge">${unitType}</span>`);
                    }
                }
            });
            
            // Submit on enter key
            $input.keypress(function(e) {
                if (e.which === 13) {
                    $input.blur();
                }
            });
        });

        // Edit button click handler - Open full edit modal
        $('.action-btn-edit').click(function() {
            const productId = $(this).data('id');
            
            // Show loading state
            $(this).html('<i class="bi bi-hourglass"></i>').prop('disabled', true);
            
            // Fetch product details
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax_action: 'get_product_details',
                    product_id: productId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const product = response.product;
                        
                        // Populate form fields
                        $('#edit_product_id').val(product.product_id);
                        $('#edit_name').val(product.name);
                        $('#edit_description').val(product.description);
                        $('#edit_price').val(product.price);
                        $('#edit_stock').val(product.stock);
                        $('#edit_unit_type').val(product.unit_type);
                        $('#edit_category_id').val(product.category_id);
                        $('#edit_farmer_id').val(product.farmer_id);
                        
                        // Handle image
                        if (product.image) {
                            $('#edit_current_image').val(product.image);
                            $('#current_image_container').html(
                                `<img src="../../public/${product.image}" class="img-thumbnail" style="max-height: 100px">`
                            );
                        } else {
                            $('#edit_current_image').val('');
                            $('#current_image_container').html('No image');
                        }
                        
                        // Show modal
                        $('#editProductModal').modal('show');
                    } else {
                        showToast('Error', response.message || 'Failed to load product details', 'error');
                    }
                },
                error: function() {
                    showToast('Error', 'Server connection failed', 'error');
                },
                complete: function() {
                    // Restore button state
                    $('.action-btn-edit[data-id="'+productId+'"]').html('<i class="bi bi-pencil"></i>').prop('disabled', false);
                }
            });
        });

        // Handle edit form submission
        $('#editProductForm').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading in button
            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.html('<i class="bi bi-hourglass"></i> Saving...').prop('disabled', true);
            
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Success', 'Product updated successfully');
                        $('#editProductModal').modal('hide');
                        
                        // Reload page to show updated data
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast('Error', response.message || 'Failed to update product', 'error');
                    }
                },
                error: function() {
                    showToast('Error', 'Server connection failed', 'error');
                },
                complete: function() {
                    // Restore button state
                    $submitBtn.html('Save Changes').prop('disabled', false);
                }
            });
        });

        // Handle Create Product Form Submission
        $('#createProductForm').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Disable submit button
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.html('<i class="bi bi-hourglass"></i> Creating...').prop('disabled', true);
            
            $.ajax({
                url: '../../controllers/AjaxController.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Success', 'Product created successfully');
                        $('#createProductModal').modal('hide');
                        
                        // Reload page to show updated data
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast('Error', response.message || 'Failed to create product', 'error');
                    }
                },
                error: function() {
                    showToast('Error', 'Server connection failed', 'error');
                },
                complete: function() {
                    // Restore button state
                    submitBtn.html('Create Product').prop('disabled', false);
                }
            });
        });
        
        // Quick approve button handler
        $('.action-btn-approve').click(function() {
            const productId = $(this).data('id');
            const $row = $(this).closest('tr');
            
            // Create and submit form
            const form = $('<form>', {
                'method': 'POST',
                'action': window.location.href
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'product_id',
                'value': productId
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'approve_product',
                'value': '1'
            }));
            
            $(document.body).append(form);
            form.submit();
        });
        
        // Replace the Quick reject button handler with this:
        $('.action-btn-reject').click(function() {
            const productId = $(this).data('id');
            const productName = $(this).closest('tr').find('.editable-name').text().trim();
            
            // Show rejection notes modal instead of immediate action
            $('#rejectModalProductId').val(productId);
            $('#rejectModalProductName').text(productName);
            $('#rejectNotesModal').modal('show');
        });
        
        // Delete button handler
        $('.action-btn-delete').click(function() {
            const productId = $(this).data('id');
            $('#confirmMessage').text('Are you sure you want to delete this product? This action cannot be undone.');
            $('#confirmAction').data('action', 'delete-product').data('id', productId);
            $('#confirmModal').modal('show');
        });
    });
  </script>
  
  <script>

    // Handle rejection form submission
    $('#rejectProductForm').submit(function(e) {
        e.preventDefault();
        
        // Validate that notes are provided
        const notes = $('#rejection_notes').val().trim();
        if (!notes) {
            showToast('Error', 'Please provide a reason for rejection', 'error');
            return;
        }
        
        // Show loading state
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = $submitBtn.text();
        $submitBtn.html('<i class="bi bi-hourglass"></i> Rejecting...').prop('disabled', true);
        
        // Submit the form
        const formData = new FormData(this);
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                // Reload the page - PHP will handle the redirect with success message
                window.location.reload();
            },
            error: function() {
                showToast('Error', 'Failed to reject product', 'error');
                $submitBtn.html(originalBtnText).prop('disabled', false);
            }
        });
    });

    // Handle URL parameters for notifications
    $(document).ready(function() {
        // Extract URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        const success = urlParams.get('success');
        const error = urlParams.get('error');
        const message = urlParams.get('message');
        const productId = urlParams.get('product_id');
        const productName = urlParams.get('product_name');
        
        // Show appropriate notifications
        if (action && success === '1') {
            let title = 'Success';
            let displayMessage = message || 'Action completed successfully';
            let details = '';
            
            switch(action) {
                case 'approve':
                    displayMessage = message || 'Product has been approved successfully';
                    details = 'The farmer has been notified about the approval.';
                    
                    if (productName) {
                        details = `Product "${decodeURIComponent(productName)}" (ID: ${productId}) has been approved and the farmer has been notified.`;
                    }
                    
                    // Show modal for product approval
                    $('#successModalMessage').text(displayMessage);
                    $('#successModalDetails').text(details);
                    $('#successModal').modal('show');
                    break;
                    
                case 'reject':
                    if (!message) displayMessage = 'Product has been rejected successfully';
                    showToast(title, displayMessage, 'success');
                    break;
                    
                case 'delete':
                    if (!message) displayMessage = 'Product has been deleted successfully';
                    showToast(title, displayMessage, 'success');
                    break;
                    
                default:
                    showToast(title, displayMessage, 'success');
            }
        } else if (action && error) {
            showToast('Error', decodeURIComponent(error), 'error');
        }
    });
  </script>
</body>
</html>