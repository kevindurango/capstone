<?php
session_start();

// Check if the user is logged in as an Organization Head, if not redirect to the login page
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

require_once '../../controllers/ProductController.php';
require_once '../../models/Log.php';

$productController = new ProductController();
$logClass = new Log();

// Fetch products
// For organization head, we should filter products by their organization
// If organization_id is stored in session, use it
// Otherwise, get all products (fallback option)
if (isset($_SESSION['organization_id'])) {
    $products = $productController->getProductsByOrganization($_SESSION['organization_id']);
} else {
    // Fallback - get all products
    $products = $productController->getAllProducts();
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Add Product
    if (isset($_POST['add_product']) && !empty($_POST['product_name']) && !empty($_POST['price'])) {
        // Handle file upload if present
        $image = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../public/assets/products/';
            $fileName = time() . '_' . basename($_FILES['product_image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadFile)) {
                $image = 'assets/products/' . $fileName;
            }
        }

        // Get organization_id from session
        $organization_id = $_SESSION['organization_id'] ?? 0;

        $productController->addProduct(
            $_POST['product_name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $image,
            $organization_id,
            $_SESSION['user_id'] // Assuming the organization head is also a user
        );
        
        $logClass->logActivity($_SESSION['user_id'], 'Added new product: ' . $_POST['product_name']);
        $_SESSION['message'] = 'Product added successfully!';
        $_SESSION['message_type'] = 'success';
        
        header("Location: organization-head-product.php");
        exit();
    }

    // Edit Product
    if (isset($_POST['edit_product'])) {
        // Handle file upload for edit
        $image = $_POST['current_image'];
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../public/assets/products/';
            $fileName = time() . '_' . basename($_FILES['product_image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadFile)) {
                $image = 'assets/products/' . $fileName;
            }
        }

        $productController->updateProduct(
            $_POST['product_id'],
            $_POST['product_name'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock'],
            $image
        );
        
        $logClass->logActivity($_SESSION['user_id'], 'Edited product: ' . $_POST['product_name']);
        $_SESSION['message'] = 'Product updated successfully!';
        $_SESSION['message_type'] = 'success';
        
        header("Location: organization-head-product.php");
        exit();
    }

    // Delete Product
    if (isset($_POST['delete_product'])) {
        $productController->deleteProduct($_POST['product_id']);
        $logClass->logActivity($_SESSION['user_id'], 'Deleted product with ID: ' . $_POST['product_id']);
        $_SESSION['message'] = 'Product deleted successfully!';
        $_SESSION['message_type'] = 'success';
        
        header("Location: organization-head-product.php");
        exit();
    }

    // Logout
    if (isset($_POST['logout'])) {
        $logClass->logActivity($_SESSION['user_id'], 'Organization Head logged out');
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
  <title>Product Management - Organization Head Dashboard</title>
  <!-- Bootstrap and Icon Libraries -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Custom Styles -->
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <style>
    .table-container {
      position: relative;
      overflow-x: auto;
      max-height: 450px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    
    .table-container thead th {
      position: sticky;
      top: 0;
      z-index: 1;
      background-color: #198754; /* Updated to match the green header color */
      color: white;
    }
    
    .table-container tbody td {
      vertical-align: middle;
    }
    
    .btn-action {
      border-radius: 50px;
      width: 32px;
      height: 32px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin: 0 3px;
      transition: all 0.3s ease;
    }
    
    .btn-action:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    
    .modal-content {
      border-radius: 15px;
      border: none;
    }
    
    .modal-header {
      background-color: #f8f9fa;
      border-radius: 15px 15px 0 0;
    }
    
    .form-control:focus {
      border-color: #22c55e;
      box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.25);
    }
    
    .img-thumbnail {
      object-fit: cover;
      width: 80px;
      height: 80px;
      transition: transform 0.3s ease;
    }
    
    .img-thumbnail:hover {
      transform: scale(1.5);
      z-index: 10;
    }
    
    .badge {
      font-size: 0.85rem;
      padding: 0.4rem 0.6rem;
    }
    
    #productSearch {
      border-radius: 50px;
      padding-left: 2.5rem;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>');
      background-repeat: no-repeat;
      background-position: 1rem center;
      background-size: 1rem;
    }
    
    /* Status Badge Colors */
    .badge-success { background-color: #22c55e; }
    .badge-danger { background-color: #ef4444; }
    .badge-warning { background-color: #f59e0b; }

    /* Improved Action Buttons */
    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    .btn-action {
      width: 38px;
      height: 38px;
      border-radius: 8px; /* Changed to rounded rectangle */
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      transition: all 0.3s ease;
      box-shadow: 0 3px 6px rgba(0,0,0,0.12);
      border: none;
    }

    .btn-action:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* View Button Style */
    .btn-view {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
    }

    .btn-view:hover, .btn-view:focus {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
    }

    /* Edit Button Style */
    .btn-edit {
      background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
      color: white;
    }

    .btn-edit:hover, .btn-edit:focus {
      background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(249, 115, 22, 0.4);
    }

    /* Delete Button Style */
    .btn-delete {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
    }

    .btn-delete:hover, .btn-delete:focus {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(220, 38, 38, 0.4);
    }

    /* Button icons */
    .btn-action i {
      font-size: 1rem;
      line-height: 1;
    }

    /* Ripple effect */
    .btn-action {
      position: relative;
      overflow: hidden;
    }

    .btn-action:after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 5px;
      height: 5px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 0;
      border-radius: 100%;
      transform: scale(1, 1) translate(-50%);
      transform-origin: 50% 50%;
    }

    .btn-action:focus:not(:active)::after {
      animation: ripple 1s ease-out;
    }

    @keyframes ripple {
      0% {
        transform: scale(0, 0);
        opacity: 1;
      }
      20% {
        transform: scale(25, 25);
        opacity: 1;
      }
      100% {
        opacity: 0;
        transform: scale(40, 40);
      }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../global/organization-head-sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2 text-success">Product Management</h1>
          <div class="d-flex">
            <!-- Add Product Button -->
            <button class="btn btn-primary mr-3" data-toggle="modal" data-target="#addProductModal">
              <i class="bi bi-plus-lg"></i> Add New Product
            </button>
            
            <!-- Logout Button -->
            <form method="POST" class="ml-2">
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

        <!-- Search Bar & Filters -->
        <div class="mb-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="row align-items-end">
                <div class="col-md-4">
                  <label for="productSearch">Search</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="bi bi-search"></i></span>
                    </div>
                    <input type="text" id="productSearch" class="form-control" placeholder="Product name or description...">
                  </div>
                </div>
                
                <div class="col-md-2">
                  <label for="priceRange">Price Range</label>
                  <div class="d-flex">
                    <input type="number" id="priceMin" class="form-control" placeholder="Min">
                    <div class="mx-1 d-flex align-items-center">-</div>
                    <input type="number" id="priceMax" class="form-control" placeholder="Max">
                  </div>
                </div>
                
                <div class="col-md-2">
                  <label for="stockRange">Stock Range</label>
                  <div class="d-flex">
                    <input type="number" id="stockMin" class="form-control" placeholder="Min">
                    <div class="mx-1 d-flex align-items-center">-</div>
                    <input type="number" id="stockMax" class="form-control" placeholder="Max">
                  </div>
                </div>
                
                <div class="col-md-2">
                  <label for="statusFilter">Status</label>
                  <select id="statusFilter" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
                
                <div class="col-md-2">
                  <button class="btn btn-primary btn-block" id="filterButton">
                    <i class="bi bi-funnel-fill"></i> Apply Filters
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Table -->
        <div class="table-container mt-4">
          <table class="table table-striped table-bordered text-center mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Image</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="productTableBody">
              <?php foreach ($products as $product): ?>
                <tr>
                  <td><?= htmlspecialchars($product['product_id']) ?></td>
                  <td><?= htmlspecialchars($product['name']) ?></td>
                  <td><?= htmlspecialchars($product['description']) ?></td>
                  <td>₱<?= number_format($product['price'], 2) ?></td>
                  <td>
                    <?= htmlspecialchars($product['stock']) ?>
                    <?php if ($product['stock'] <= 5): ?>
                      <span class="badge badge-danger">Low</span>
                    <?php elseif ($product['stock'] <= 20): ?>
                      <span class="badge badge-warning">Medium</span>
                    <?php else: ?>
                      <span class="badge badge-success">High</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($product['image'])): ?>
                      <img src="<?= '../../public/' . htmlspecialchars($product['image']) ?>" 
                           alt="<?= htmlspecialchars($product['name']) ?>" 
                           class="img-thumbnail">
                    <?php else: ?>
                      <img src="../../public/assets/placeholder.png" 
                           alt="No Image" 
                           class="img-thumbnail">
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($product['status'] === 'approved'): ?>
                      <span class="badge badge-success">
                        <i class="bi bi-check2-circle"></i> Approved
                      </span>
                    <?php elseif ($product['status'] === 'rejected'): ?>
                      <span class="badge badge-danger">
                        <i class="bi bi-x-circle"></i> Rejected
                      </span>
                    <?php else: ?>
                      <span class="badge badge-warning">
                        <i class="bi bi-hourglass-split"></i> Pending
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <!-- View Button -->
                      <button class="btn btn-action btn-view" 
                              data-product-id="<?= $product['product_id'] ?>" 
                              data-toggle="tooltip" 
                              title="View Details">
                        <i class="bi bi-eye-fill"></i>
                      </button>
                      
                      <!-- Edit Button -->
                      <button class="btn btn-action btn-edit" 
                              data-product-id="<?= $product['product_id'] ?>" 
                              data-product-name="<?= htmlspecialchars($product['name']) ?>" 
                              data-description="<?= htmlspecialchars($product['description']) ?>" 
                              data-price="<?= htmlspecialchars($product['price']) ?>"
                              data-stock="<?= htmlspecialchars($product['stock']) ?>"
                              data-image="<?= htmlspecialchars($product['image'] ?? '') ?>"
                              data-toggle="tooltip" 
                              title="Edit Product">
                        <i class="bi bi-pencil-fill"></i>
                      </button>
                      
                      <!-- Delete Button -->
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <button type="submit" name="delete_product" 
                                class="btn btn-action btn-delete" 
                                onclick="return confirm('Are you sure you want to delete this product?');"
                                data-toggle="tooltip" 
                                title="Delete Product">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              
              <?php if (empty($products)): ?>
                <tr>
                  <td colspan="8" class="text-center py-4">No products found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </main>
    </div>
  </div>

  <!-- Add Product Modal -->
  <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="product_name">Product Name</label>
                  <input type="text" name="product_name" id="product_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                  <label for="price">Price (₱)</label>
                  <input type="number" step="0.01" name="price" id="price" class="form-control" min="0" required>
                </div>
                
                <div class="form-group">
                  <label for="stock">Stock</label>
                  <input type="number" name="stock" id="stock" class="form-control" min="0" required>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label for="description">Description</label>
                  <textarea name="description" id="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                  <label for="product_image">Product Image</label>
                  <input type="file" name="product_image" id="product_image" class="form-control-file" accept="image/*">
                  <small class="form-text text-muted">Recommended image size: 500x500 pixels</small>
                </div>
                
                <div class="mt-3">
                  <img id="image_preview" src="../../public/assets/placeholder.png" 
                       alt="Preview" class="img-fluid" style="max-height: 150px; display: none;">
                </div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="product_id" id="edit_product_id">
          <input type="hidden" name="current_image" id="current_image">
          
          <div class="modal-header">
            <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="edit_product_name">Product Name</label>
                  <input type="text" name="product_name" id="edit_product_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                  <label for="edit_price">Price (₱)</label>
                  <input type="number" step="0.01" name="price" id="edit_price" class="form-control" min="0" required>
                </div>
                
                <div class="form-group">
                  <label for="edit_stock">Stock</label>
                  <input type="number" name="stock" id="edit_stock" class="form-control" min="0" required>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label for="edit_description">Description</label>
                  <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                  <label for="edit_product_image">Product Image</label>
                  <input type="file" name="product_image" id="edit_product_image" class="form-control-file" accept="image/*">
                  <small class="form-text text-muted">Leave empty to keep current image</small>
                </div>
                
                <div class="mt-3">
                  <img id="edit_image_preview" src="" alt="Preview" class="img-fluid" style="max-height: 150px;">
                </div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_product" class="btn btn-warning">Update Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Product Modal -->
  <div class="modal fade" id="viewProductModal" tabindex="-1" role="dialog" aria-labelledby="viewProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Product Name:</label>
                <p id="view_product_name" class="lead mb-3"></p>
              </div>
              
              <div class="form-group">
                <label class="font-weight-bold">Description:</label>
                <p id="view_description" class="text-muted mb-3"></p>
              </div>
              
              <div class="form-group">
                <label class="font-weight-bold">Price:</label>
                <p id="view_price" class="text-success font-weight-bold mb-3"></p>
              </div>
              
              <div class="form-group">
                <label class="font-weight-bold">Stock:</label>
                <p id="view_stock" class="mb-3"></p>
              </div>
              
              <div class="form-group">
                <label class="font-weight-bold">Status:</label>
                <p id="view_status" class="mb-3"></p>
              </div>
            </div>
            
            <div class="col-md-6 text-center">
              <label class="font-weight-bold">Product Image:</label>
              <div class="mt-2">
                <img id="view_image" src="" alt="Product Image" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
              </div>
            </div>
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
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Initialize tooltips
      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
      });
      
      // Image preview for add product
      document.getElementById('product_image').addEventListener('change', function(e) {
        const preview = document.getElementById('image_preview');
        const file = e.target.files[0];
        
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
          }
          reader.readAsDataURL(file);
        } else {
          preview.style.display = 'none';
        }
      });
      
      // Image preview for edit product
      document.getElementById('edit_product_image').addEventListener('change', function(e) {
        const preview = document.getElementById('edit_image_preview');
        const file = e.target.files[0];
        
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
          }
          reader.readAsDataURL(file);
        }
      });
      
      // Edit product modal data handling
      document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', () => {
          document.getElementById('edit_product_id').value = button.dataset.productId;
          document.getElementById('edit_product_name').value = button.dataset.productName;
          document.getElementById('edit_description').value = button.dataset.description;
          document.getElementById('edit_price').value = button.dataset.price;
          document.getElementById('edit_stock').value = button.dataset.stock;
          document.getElementById('current_image').value = button.dataset.image;
          
          const editImagePreview = document.getElementById('edit_image_preview');
          if (button.dataset.image) {
            editImagePreview.src = '../../public/' + button.dataset.image;
          } else {
            editImagePreview.src = '../../public/assets/placeholder.png';
          }
          
          $('#editProductModal').modal('show');
        });
      });
      
      // View product modal data handling
      document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('click', () => {
            // Get product ID from data attribute
            const productId = button.getAttribute('data-product-id');
            
            // Fetch product details from the server
            fetch(`get-product-details.php?product_id=${productId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(product => {
                    // Update modal content with product details
                    document.getElementById('view_product_name').textContent = product.name;
                    document.getElementById('view_description').textContent = product.description || 'No description available';
                    document.getElementById('view_price').textContent = '₱' + parseFloat(product.price).toFixed(2);
                    document.getElementById('view_stock').textContent = product.stock;
                    
                    // Status badge
                    let statusHtml = '';
                    if (product.status === 'approved') {
                        statusHtml = '<span class="badge badge-success"><i class="bi bi-check2-circle"></i> Approved</span>';
                    } else if (product.status === 'rejected') {
                        statusHtml = '<span class="badge badge-danger"><i class="bi bi-x-circle"></i> Rejected</span>';
                    } else {
                        statusHtml = '<span class="badge badge-warning"><i class="bi bi-hourglass-split"></i> Pending</span>';
                    }
                    document.getElementById('view_status').innerHTML = statusHtml;
                    
                    // Image
                    const viewImage = document.getElementById('view_image');
                    if (product.image) {
                        viewImage.src = '../../public/' + product.image;
                    } else {
                        viewImage.src = '../../public/assets/placeholder.png';
                    }
                    
                    // Show the modal
                    $('#viewProductModal').modal('show');
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    alert('Failed to load product details. Please try again.');
                });
        });
    });
      
      // Product Search and Filtering
      const productSearch = document.getElementById('productSearch');
      const stockMin = document.getElementById('stockMin');
      const stockMax = document.getElementById('stockMax');
      const priceMin = document.getElementById('priceMin');
      const priceMax = document.getElementById('priceMax');
      const statusFilter = document.getElementById('statusFilter');
      const filterButton = document.getElementById('filterButton');
      const productTableBody = document.getElementById('productTableBody');
      
      // Function to filter the products
      function filterProducts() {
        const searchQuery = productSearch.value.toLowerCase();
        const minStock = parseInt(stockMin.value) || 0;
        const maxStock = parseInt(stockMax.value) || Infinity;
        const minPrice = parseFloat(priceMin.value) || 0;
        const maxPrice = parseFloat(priceMax.value) || Infinity;
        const statusFilterValue = statusFilter.value.toLowerCase();

        // Get all rows in the table
        const rows = productTableBody.getElementsByTagName('tr');

        // Loop through each row and check if it matches the filter conditions
        Array.from(rows).forEach(function(row) {
          if (row.cells.length <= 1) return; // Skip empty state row
          
          const productName = row.cells[1].innerText.toLowerCase();
          const productDescription = row.cells[2].innerText.toLowerCase();
          const productPrice = parseFloat(row.cells[3].innerText.replace('₱', '').replace(',', ''));
          const productStock = parseInt(row.cells[4].innerText);
          const productStatus = row.cells[6].innerText.toLowerCase();

          // Check if row matches all filter conditions
          const matchesSearch = productName.includes(searchQuery) || productDescription.includes(searchQuery);
          const matchesStock = (productStock >= minStock && productStock <= maxStock);
          const matchesPrice = (productPrice >= minPrice && productPrice <= maxPrice);
          const matchesStatus = statusFilterValue === '' || productStatus.includes(statusFilterValue);

          // If all conditions match, show the row, otherwise hide it
          if (matchesSearch && matchesStock && matchesPrice && matchesStatus) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }

      // Listen for input changes and filter products
      productSearch.addEventListener('keyup', filterProducts);
      filterButton.addEventListener('click', filterProducts);
    });
  </script>
</body>
</html>

<?php
// filepath: /c:/xampp/htdocs/capstone/views/organization-head/get-product-details.php
require_once '../../controllers/ProductController.php';

if (isset($_GET['product_id'])) {
    $productController = new ProductController();
    $product = $productController->getProductById($_GET['product_id']);
    
    if ($product) {
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
}