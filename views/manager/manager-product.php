<?php
session_start();

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../controllers/ProductController.php';
require_once '../../models/Log.php';

$productController = new ProductController();
$logClass = new Log();

// Fetch products
$products = $productController->getAllProducts(); // Use original method to avoid errors

// Get product statistics
$totalProducts = $productController->getProductCount();
$activeProducts = $productController->getProductCountByStatus('active');
$pendingProducts = $productController->getProductCountByStatus('pending');
$lowStockProducts = $productController->getLowStockProducts();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['logout'])) {
        $logClass->logActivity($_SESSION['user_id'], "Manager logged out");
        session_unset();
        session_destroy();
        header("Location: manager-login.php");
        exit();
    }
    // ... handle other POST actions
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
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/product-management.css">
    <style>
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
        .product-thumbnail {
            max-width: 50px;
            height: auto;
        }
        /* Add manager header styling */
        .manager-header {
            background: linear-gradient(135deg, #1a8754 0%, #34c38f 100%); /* Updated to match other manager pages */
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

        /* Enhanced product card styling */
        .custom-card {
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .custom-card:hover {
            transform: translateY(-5px);
        }

        /* Enhanced status badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Product thumbnail improvements */
        .product-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Search and filter section */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Action buttons styling */
        .action-btn {
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <!-- Add Manager Header -->
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
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <!-- Enhanced Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb bg-white custom-card">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Product Management</li>
                    </ol>
                </nav>

                <!-- Enhanced Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-box-seam text-success"></i> Product Management</h1>
                        <p class="text-muted">Manage and monitor product inventory</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-success mr-2 action-btn" onclick="exportProductReport()">
                            <i class="bi bi-file-earmark-excel"></i> Export Report
                        </button>
                        <button class="btn btn-primary mr-2 action-btn" data-toggle="modal" data-target="#addProductModal">
                            <i class="bi bi-plus-lg"></i> Add Product
                        </button>
                        <form method="POST">
                            <button type="submit" name="logout" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Product Statistics Cards -->
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
                            <div class="card-title">Active Products</div>
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

                <!-- Enhanced Search and Filter Section -->
                <div class="filter-section custom-card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="bi bi-search"></i> Search Products</label>
                                    <input type="text" id="searchProduct" class="form-control" placeholder="Search by name, category...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="bi bi-funnel"></i> Filter by Category</label>
                                    <select id="categoryFilter" class="form-control">
                                        <option value="">All Categories</option>
                                        <!-- Add categories dynamically -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="bi bi-sort-alpha-down"></i> Sort By</label>
                                    <select id="sortFilter" class="form-control">
                                        <option value="name">Name A-Z</option>
                                        <option value="price">Price</option>
                                        <option value="stock">Stock Level</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="custom-card">
                    <div class="card-body">
                        <?php
                        // Debug output
                        if (empty($products)) {
                            echo '<div class="alert alert-warning">No products found in database</div>';
                        }
                        ?>
                        <div class="table-responsive" id="productsTableContainer">
                            <table class="table table-hover" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($products)): ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['product_id']) ?></td>
                                                <td>
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="<?= '../../public/' . htmlspecialchars($product['image']) ?>" 
                                                             alt="Product Image" 
                                                             class="img-thumbnail" 
                                                             style="width: 80px; height: 80px; object-fit: cover;"
                                                             onerror="this.src='../../public/assets/default-product.jpg'">
                                                    <?php else: ?>
                                                        <img src="../../public/assets/default-product.jpg" 
                                                             alt="No Image" 
                                                             class="img-thumbnail" 
                                                             style="width: 80px; height: 80px; object-fit: cover;">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= htmlspecialchars($product['category'] ?? 'N/A') ?></td>
                                                <td>₱<?= number_format($product['price'], 2) ?></td>
                                                <td>
                                                    <span class="<?= $product['stock'] <= 10 ? 'text-danger' : '' ?>">
                                                        <?= htmlspecialchars($product['stock']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $product['status'] === 'active' ? 'success' : 
                                                        ($product['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst(htmlspecialchars($product['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-product" 
                                                            data-id="<?= $product['product_id'] ?>"
                                                            data-toggle="modal" 
                                                            data-target="#editProductModal">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-product"
                                                            data-id="<?= $product['product_id'] ?>"
                                                            onclick="confirmDelete(<?= $product['product_id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Add pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                            <div>
                                <span>Showing <span id="itemsCount">0</span> of <span id="totalItems">0</span> products</span>
                            </div>
                            <div>
                                <button id="loadMoreBtn" class="btn btn-primary">
                                    <i class="bi bi-arrow-down-circle"></i> Load More
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Product Modal -->
                <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addProductModalLabel"><i class="bi bi-plus-circle"></i> Add New Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="addProductForm" enctype="multipart/form-data" onsubmit="return false;">
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
                                            <div class="form-group">
                                                <label for="category_id">Category</label>
                                                <select class="form-control" id="category_id" name="category_id">
                                                    <option value="">Select Category</option>
                                                    <option value="1">Fruit</option>
                                                    <option value="2">Vegetable</option>
                                                    <option value="3">Grain</option>
                                                    <option value="4">Herb</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="farmer_id">Farmer</label>
                                                <select class="form-control" id="farmer_id" name="farmer_id">
                                                    <option value="">Select Farmer</option>
                                                    <?php
                                                    $farmers = $productController->getAllFarmers();
                                                    foreach ($farmers as $farmer) {
                                                        echo "<option value=\"{$farmer['user_id']}\">{$farmer['username']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                            </div>
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
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveProductBtn">Save Product</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Product Modal -->
                <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editProductModalLabel"><i class="bi bi-pencil-square"></i> Edit Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="editProductForm" enctype="multipart/form-data" onsubmit="return false;">
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
                                            <div class="form-group">
                                                <label for="edit_category_id">Category</label>
                                                <select class="form-control" id="edit_category_id" name="category_id">
                                                    <option value="">Select Category</option>
                                                    <option value="1">Fruit</option>
                                                    <option value="2">Vegetable</option>
                                                    <option value="3">Grain</option>
                                                    <option value="4">Herb</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_farmer_id">Farmer</label>
                                                <select class="form-control" id="edit_farmer_id" name="farmer_id">
                                                    <option value="">Select Farmer</option>
                                                    <?php
                                                    foreach ($farmers as $farmer) {
                                                        echo "<option value=\"{$farmer['user_id']}\">{$farmer['username']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_description">Description</label>
                                                <textarea class="form-control" id="edit_description" name="description" rows="4"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_image">Product Image</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="edit_image" name="image" accept="image/*">
                                                    <label class="custom-file-label" for="edit_image">Choose file...</label>
                                                </div>
                                                <input type="hidden" id="current_image" name="current_image">
                                                <div class="mt-2">
                                                    <img id="editImagePreview" src="#" alt="Preview" style="max-width: 100%; max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="updateProductBtn">Update Product</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                                <input type="hidden" id="delete_product_id">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Product</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store all products and working copy
        const allProducts = <?= json_encode($products) ?>;
        let filteredProducts = [...allProducts];
        let displayCount = 10;
        let isLoading = false;

        // Standardize image path handling between PHP and JS
        function getImagePath(imagePath) {
            // Simple, consistent logic for image paths
            if (!imagePath) return '../../public/assets/default-product.jpg';
            
            // Make sure path starts with uploads/
            if (imagePath.includes('uploads/')) {
                return '../../public/' + imagePath;
            }
  
            return '../../public/uploads/' + imagePath;
        }
        
        // Only update what changed instead of whole table
        function renderProducts() {
            if (isLoading) return;
            isLoading = true;
            
            document.getElementById('loadingIndicator').style.display = 'flex';
            const displayedProducts = filteredProducts.slice(0, displayCount);
            const tbody = document.querySelector('#productsTable tbody');
            
            // Prevent layout thrashing by building fragment first
            const fragment = document.createDocumentFragment();
            
            if (displayedProducts.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="8" class="text-center">No products match your search</td>';
                fragment.appendChild(emptyRow);
            } else {
                displayedProducts.forEach(product => {
                    const row = document.createElement('tr');
                    row.dataset.id = product.product_id; // For easier lookup
                    
                    // Pre-load images before adding to DOM
                    const image = new Image();
                    image.src = getImagePath(product.image);
                    image.className = 'img-thumbnail';
                    image.alt = product.name || 'Product';
                    image.style = 'width: 80px; height: 80px; object-fit: cover;';
                    image.onerror = function() {
                        this.src = '../../public/assets/default-product.jpg';
                        this.onerror = null; // Prevent infinite recursion
                    };
                    
                    // Construct cells separately to avoid reflow
                    const cells = [
                        `<td>${product.product_id}</td>`,
                        `<td>${image.outerHTML}</td>`,
                        `<td>${product.name || ''}</td>`,
                        `<td>${product.category || 'N/A'}</td>`,
                        `<td>₱${parseFloat(product.price || 0).toFixed(2)}</td>`,
                        `<td class="${(product.stock <= 10) ? 'text-danger' : ''}">${product.stock || 0}</td>`,
                        `<td>
                            <span class="badge badge-${product.status === 'active' ? 'success' : 
                                (product.status === 'pending' ? 'warning' : 'danger')}">
                                ${product.status ? product.status.charAt(0).toUpperCase() + product.status.slice(1) : 'N/A'}
                            </span>
                        </td>`,
                        `<td>
                            <button class="btn btn-sm btn-warning edit-product-btn" data-id="${product.product_id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-product-btn" data-id="${product.product_id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>`
                    ];
                    
                    row.innerHTML = cells.join('');
                    fragment.appendChild(row);
                });
            }
            
            // Single DOM update
            tbody.innerHTML = '';
            tbody.appendChild(fragment);
            
            // Update UI elements
            document.getElementById('itemsCount').textContent = displayedProducts.length;
            document.getElementById('totalItems').textContent = filteredProducts.length;
            document.getElementById('loadMoreBtn').style.display = 
                displayedProducts.length < filteredProducts.length ? 'block' : 'none';
            
            // Add event listeners after DOM update is complete
            setTimeout(() => {
                addButtonEventListeners();
                document.getElementById('loadingIndicator').style.display = 'none';
                isLoading = false;
            }, 50);
        }
        
        // Debounce function for searches and filters
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }
        
        // Filter products based on search and sort
        const applyFilters = debounce(function() {
            const searchTerm = document.getElementById('searchProduct').value.toLowerCase();
            const categoryValue = document.getElementById('categoryFilter').value.toLowerCase();
            const sortBy = document.getElementById('sortFilter').value;
            
            // Reset to original products then filter
            filteredProducts = [...allProducts].filter(product => {
                const nameMatch = (product.name || '').toLowerCase().includes(searchTerm);
                const categoryMatch = !categoryValue || (product.category || '').toLowerCase().includes(categoryValue);
                return nameMatch && categoryMatch;
            });
            
            // Apply sorting
            if (sortBy) {
                filteredProducts.sort((a, b) => {
                    if (sortBy === 'name') return a.name.localeCompare(b.name);
                    if (sortBy === 'price') return parseFloat(a.price || 0) - parseFloat(b.price || 0);
                    if (sortBy === 'stock') return parseInt(a.stock || 0) - parseInt(b.stock || 0);
                    return 0;
                });
            }
            
            // Reset to first page
            displayCount = 10;
            renderProducts();
        }, 300);
        
        // Use event delegation for better performance
        function addButtonEventListeners() {
            const tbody = document.querySelector('#productsTable tbody');
            
            tbody.addEventListener('click', function(event) {
                let target = event.target;
                
                // Navigate up to find the button if clicked on icon
                while (target !== this && !target.classList.contains('btn')) {
                    target = target.parentNode;
                }
                
                if (target.classList.contains('edit-product-btn')) {
                    editProduct(target.getAttribute('data-id'));
                    event.stopPropagation();
                } else if (target.classList.contains('delete-product-btn')) {
                    showDeleteConfirmation(target.getAttribute('data-id'));
                    event.stopPropagation();
                }
            });
        }
        
        // CRUD Operations
        function editProduct(productId) {
            const product = allProducts.find(p => p.product_id == productId);
            if (!product) return;
            
            // Fill edit form
            document.getElementById('edit_product_id').value = product.product_id;
            document.getElementById('edit_name').value = product.name || '';
            document.getElementById('edit_price').value = product.price || '';
            document.getElementById('edit_stock').value = product.stock || '';
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_farmer_id').value = product.farmer_id || '';
            document.getElementById('current_image').value = product.image || '';
            
            // Preview image
            const imagePreview = document.getElementById('editImagePreview');
            imagePreview.src = getImagePath(product.image);
            imagePreview.style.display = product.image ? 'block' : 'none';
            
            // Show modal
            $('#editProductModal').modal('show');
        }
        
        function showDeleteConfirmation(productId) {
            document.getElementById('delete_product_id').value = productId;
            $('#deleteConfirmModal').modal('show');
        }
        
        function deleteProduct(productId) {
            isLoading = true;
            document.getElementById('loadingIndicator').style.display = 'flex';
            
            $.ajax({
                url: '../../ajax/product-actions.php',
                type: 'POST',
                data: { action: 'delete_product', product_id: productId },
                success: function(response) {
                    isLoading = false;
                    document.getElementById('loadingIndicator').style.display = 'none';
                    
                    if (response.success) {
                        // Update local data
                        const index = allProducts.findIndex(p => p.product_id == productId);
                        if (index !== -1) allProducts.splice(index, 1);
                        
                        // Update display
                        applyFilters();
                        $('#deleteConfirmModal').modal('hide');
                    } else {
                        alert('Failed to delete product: ' + (response.message || 'Unknown error'));
                        $('#deleteConfirmModal').modal('hide');
                    }
                },
                error: function() {
                    isLoading = false;
                    document.getElementById('loadingIndicator').style.display = 'none';
                    alert('Network error occurred. Please try again.');
                    $('#deleteConfirmModal').modal('hide');
                }
            });
        }
        
        // Export products to CSV
        function exportProductReport() {
            const headers = ['ID', 'Name', 'Price', 'Stock', 'Status'];
            let csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n";
            
            filteredProducts.forEach(product => {
                csvContent += [
                    product.product_id,
                    '"' + (product.name || '').replace(/"/g, '""') + '"',
                    product.price || 0,
                    product.stock || 0,
                    product.status || ''
                ].join(",") + "\n";
            });
            
            const link = document.createElement("a");
            link.href = encodeURI(csvContent);
            link.download = "product_report.csv";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initial render
            renderProducts();
            
            // Setup event listeners
            document.getElementById('searchProduct').addEventListener('input', applyFilters);
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('sortFilter').addEventListener('change', applyFilters);
            document.getElementById('loadMoreBtn').addEventListener('click', function() {
                displayCount += 10;
                renderProducts();
            });
            
            // Image preview for add product
            document.getElementById('image').addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        document.getElementById('imagePreview').src = e.target.result;
                        document.getElementById('imagePreview').style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                    this.nextElementSibling.textContent = this.files[0].name;
                }
            });
            
            // Image preview for edit product
            document.getElementById('edit_image').addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        document.getElementById('editImagePreview').src = e.target.result;
                        document.getElementById('editImagePreview').style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                    this.nextElementSibling.textContent = this.files[0].name;
                }
            });
            
            // Form submissions
            document.getElementById('addProductForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent form submission
                return false;
            });
            
            document.getElementById('editProductForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent form submission
                return false;
            });
            
            document.getElementById('saveProductBtn').addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default action
                const form = document.getElementById('addProductForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                const formData = new FormData(form);
                formData.append('action', 'add_product');
                
                isLoading = true;
                document.getElementById('loadingIndicator').style.display = 'flex';
                
                $.ajax({
                    url: '../../ajax/product-actions.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        isLoading = false;
                        document.getElementById('loadingIndicator').style.display = 'none';
                        
                        if (response.success) {
                            if (response.product) {
                                allProducts.unshift(response.product);
                                applyFilters();
                            }
                            
                            form.reset();
                            document.getElementById('imagePreview').style.display = 'none';
                            $('#addProductModal').modal('hide');
                        } else {
                            alert('Failed to add product: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        isLoading = false;
                        document.getElementById('loadingIndicator').style.display = 'none';
                        alert('Network error occurred. Please try again.');
                    }
                });
            });
            
            document.getElementById('updateProductBtn').addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default action
                const form = document.getElementById('editProductForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                const formData = new FormData(form);
                formData.append('action', 'update_product');
                
                isLoading = true;
                document.getElementById('loadingIndicator').style.display = 'flex';
                
                $.ajax({
                    url: '../../ajax/product-actions.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        isLoading = false;
                        document.getElementById('loadingIndicator').style.display = 'none';
                        
                        if (response.success) {
                            if (response.product) {
                                const index = allProducts.findIndex(p => p.product_id == response.product.product_id);
                                if (index !== -1) allProducts[index] = response.product;
                                applyFilters();
                            }
                            
                            $('#editProductModal').modal('hide');
                        } else {
                            alert('Failed to update product: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        isLoading = false;
                        document.getElementById('loadingIndicator').style.display = 'none';
                        alert('Network error occurred. Please try again.');
                    }
                });
            });
            
            document.getElementById('confirmDeleteBtn').addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default action
                const productId = document.getElementById('delete_product_id').value;
                if (productId) {
                    deleteProduct(productId);
                }
            });
        });
    </script>
</body>
</html>
