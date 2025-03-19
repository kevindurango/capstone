<?php
session_start();

// Stricter admin authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // Log unauthorized access attempt
    require_once '../../models/Log.php';
    $logClass = new Log();
    $logClass->logActivity($_SESSION['user_id'] ?? 'Unknown', "Unauthorized access attempt to admin product management");
    
    header("Location: admin-login.php?error=unauthorized");
    exit();
}

require_once '../../controllers/ProductController.php';
require_once '../../models/Log.php'; // Include the Log class

$productController = new ProductController();
$logClass = new Log(); // Create an instance of the Log class

// Fetch products
$products = $productController->getAllProducts();

// Enhanced statistics for admin
$totalProducts = $productController->getProductCount();
$pendingApproval = $productController->getProductCountByStatus('pending');
$rejectedProducts = $productController->getProductCountByStatus('rejected');
$lowStockProducts = $productController->getLowStockProducts(5); // Lower threshold for admin alerts

// Add admin-specific batch operations handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['batch_action']) && isset($_POST['selected_products'])) {
        $selectedProducts = $_POST['selected_products'];
        $action = $_POST['batch_action'];
        
        foreach ($selectedProducts as $productId) {
            switch($action) {
                case 'approve':
                    $productController->updateProductStatus($productId, 'approved');
                    break;
                case 'reject':
                    $productController->updateProductStatus($productId, 'rejected');
                    break;
                case 'delete':
                    $productController->deleteProduct($productId);
                    break;
            }
        }
        
        $logClass->logActivity($_SESSION['user_id'], "Admin performed batch $action on " . count($selectedProducts) . " products");
        header("Location: product-management.php?batch_action=" . $action);
        exit();
    }
    
    // Handle product rejection
    if (isset($_POST['reject_product']) && !empty($_POST['product_id'])) {
        $productController->updateProductStatus($_POST['product_id'], 'rejected');
        
        // Log the action
        $logClass->logActivity($_SESSION['user_id'], 'Rejected product with ID: ' . $_POST['product_id']);
        
        header("Location: product-management.php");
        exit();
    }

    // Handle product approval
    if (isset($_POST['approve_product']) && !empty($_POST['product_id'])) {
        $productController->updateProductStatus($_POST['product_id'], 'approved');
        
        // Log the action
        $logClass->logActivity($_SESSION['user_id'], 'Approved product with ID: ' . $_POST['product_id']);
        
        header("Location: product-management.php");
        exit();
    }

    // Handle logout
    if (isset($_POST['logout'])) {
        // Log the logout action
        $logClass->logActivity($_SESSION['user_id'], 'Admin logged out');
        
        session_unset();
        session_destroy();
        header("Location: admin-login.php");
        exit();
    }
}

// Get action messages
$actionMessage = '';
if (isset($_GET['action'])) {
    // ... existing action message code ...
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Management - Admin Dashboard</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <link rel="stylesheet" href="../../public/style/product-management.css">
  <style>
    .admin-header {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        padding: 10px 0;
    }
    .admin-badge {
        background-color: #6a11cb;
        color: white;
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 4px;
        margin-left: 10px;
    }
    .btn-group-sm .btn {
        padding: 0.1rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    /* Fix for uniform button size */
    .btn-group .btn {
        min-width: 140px;
    }
    /* Specific styling for the reject button */
    #batchRejectBtn {
        min-height: 38px; /* Match the height of other buttons */
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* Fix for uniform card height */
    .card-stats {
        height: 140px;
        margin-bottom: 15px;
    }
    .card-stats .card-body {
        display: flex;
        align-items: center;
        height: 100%;
    }
    /* Updated button styling for consistency */
    .btn-group .btn {
        min-width: 150px; /* Increased width */
        height: 38px;     /* Fixed height */
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .btn-group {
        display: flex;
        align-items: stretch;
    }

    /* Remove previous button group small styling */
    .btn-group-sm .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Ensure icon and text alignment */
    .btn i {
        margin-right: 5px;
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
        <!-- Add Breadcrumb -->
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Product Management</li>
          </ol>
        </nav>

        <!-- Header Section with updated gradient styling -->
        <div class="page-header">
          <h1 class="h2"><i class="bi bi-box-seam"></i> Product Management</h1>
          <div class="d-flex">
            <button class="btn btn-success mr-2" id="exportBtn">
              <i class="bi bi-file-earmark-excel"></i> Export Products
            </button>

            <!-- Logout Button -->
            <form method="POST" class="ml-3">
              <button type="submit" name="logout" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </form>
          </div>
        </div>

        <!-- Display notification toast for price updates -->
        <div class="toast-container" id="toastContainer"></div>
        
        <!-- Stats Overview -->
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

        <!-- Enhanced Admin Controls -->
        <div class="admin-controls d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Product Administration</h4>
                <small class="text-muted">Logged in as: <?= $_SESSION['username'] ?> (Administrator)</small>
            </div>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-primary" id="selectAllBtn">
                    <i class="bi bi-check-all"></i> Select All
                </button>
                <button class="btn btn-success" id="batchApproveBtn" disabled>
                    <i class="bi bi-check2"></i> Approve Selected
                </button>
                <button class="btn btn-warning reject-btn" id="batchRejectBtn" disabled>
                    <i class="bi bi-x"></i> Reject Selected
                </button>
                <button class="btn btn-danger" id="batchDeleteBtn" disabled>
                    <i class="bi bi-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <!-- Search Bar & Filters -->
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
                  <option value="fruit">Fruit</option>
                  <option value="vegetable">Vegetable</option>
                  <option value="grain">Grain</option>
                  <option value="herb">Herb</option>
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

        <!-- Product Table -->
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title"><i class="bi bi-grid-3x3"></i> Product List</h5>
                <small class="text-muted">Showing <?= count($products) ?> of <?= $totalProducts ?> products</small>
            </div>
            <div class="table-container">
              <table class="table table-bordered text-center">
                <thead>
                  <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Farmer</th>
                    <th>Actions</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="productTableBody">
                  <?php foreach ($products as $product): ?>
                    <tr>
                      <td><input type="checkbox" class="product-checkbox" value="<?= htmlspecialchars($product['product_id']) ?>"></td>
                      <td><?= htmlspecialchars($product['product_id']) ?></td>
                      <td><?= htmlspecialchars($product['name']) ?></td>
                      <td><?= htmlspecialchars($product['description']) ?></td>
                      <!-- Changed price cell to be inline editable -->
                      <td class="editable-price" data-id="<?= htmlspecialchars($product['product_id']) ?>">
                        ₱<?= number_format($product['price'], 2) ?>
                      </td>
                      <td>
                        <?php if (!empty($product['image'])): ?>
                          <img src="<?= '../../public/' . htmlspecialchars($product['image']) ?>" 
                               alt="Product Image" 
                               class="img-thumbnail product-image" 
                               data-toggle="modal" 
                               data-target="#imageModal" 
                               data-img-src="<?= '../../public/' . htmlspecialchars($product['image']) ?>">
                        <?php else: ?>
                          <div class="no-image">No Image</div>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($product['farmer_name']) ?></td>
                      <td>
                        <div class="action-btn-container">
                          <!-- Approve Button -->
                          <?php if ($product['status'] !== 'approved'): ?>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                              <button type="submit" name="approve_product" class="btn btn-success btn-sm">
                                <i class="bi bi-check2-circle"></i> Approve
                              </button>
                            </form>
                          <?php endif; ?>
                          <!-- Reject Button -->
                          <?php if ($product['status'] !== 'rejected'): ?>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                              <button type="submit" name="reject_product" class="btn btn-danger btn-sm">
                                <i class="bi bi-x-circle"></i> Reject
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <?php if ($product['status'] === 'approved'): ?>
                          <span class="badge badge-success"><i class="bi bi-check2-circle"></i> Approved</span>
                        <?php elseif ($product['status'] === 'rejected'): ?>
                          <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                        <?php else: ?>
                          <span class="badge badge-warning"><i class="bi bi-clock"></i> Pending</span>
                        <?php endif; ?>
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

  <!-- Image Modal -->
  <div class="modal fade image-preview-modal" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
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

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {
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

        // Handle batch actions
        $('#batchApproveBtn').click(function() { submitBatchAction('approve'); });
        $('#batchRejectBtn').click(function() { submitBatchAction('reject'); });
        $('#batchDeleteBtn').click(function() {
            if (confirm('Are you sure you want to delete the selected products?')) {
                submitBatchAction('delete');
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

        // Handle "Select All" button click
        $('#selectAllBtn').click(function() {
            $('#selectAll').prop('checked', !$('#selectAll').prop('checked')).change();
        });

        // Reset filters button
        $('#resetFilterBtn').click(function() {
            $('#productSearch').val('');
            $('#categoryFilter').val('');
            $('#statusFilter').val('');
            $('#priceMin').val('');
            $('#priceMax').val('');
            applyFilters();
        });

        // Handle export
        $('#exportBtn').click(function() {
            const rows = ['Product ID,Name,Description,Price,Status,Farmer'];
            
            $('#productTableBody tr').each(function() {
                const cells = $(this).find('td');
                const row = [
                    cells.eq(1).text(), // ID
                    '"' + cells.eq(2).text().replace(/"/g, '""') + '"', // Name
                    '"' + cells.eq(3).text().replace(/"/g, '""') + '"', // Description
                    cells.eq(4).text().replace('₱', ''), // Price
                    cells.eq(8).find('span').text().trim(), // Status
                    '"' + cells.eq(6).text().trim() + '"' // Farmer
                ];
                rows.push(row.join(','));
            });

            const csvContent = rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'products_report.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Initialize filters
        $('#filterButton').click(function() {
            applyFilters();
        });

        function applyFilters() {
            const search = $('#productSearch').val().toLowerCase();
            const category = $('#categoryFilter').val().toLowerCase();
            const status = $('#statusFilter').val().toLowerCase();
            const minPrice = parseFloat($('#priceMin').val()) || 0;
            const maxPrice = parseFloat($('#priceMax').val()) || Infinity;

            $('#productTableBody tr').each(function() {
                const $row = $(this);
                const name = $row.find('td').eq(2).text().toLowerCase();
                const description = $row.find('td').eq(3).text().toLowerCase();
                const price = parseFloat($row.find('td').eq(4).text().replace('₱', ''));
                const rowStatus = $row.find('td').eq(8).text().toLowerCase();
                
                const matchesSearch = name.includes(search) || description.includes(search);
                const matchesCategory = !category || $row.find('td').eq(3).text().toLowerCase().includes(category);
                const matchesStatus = !status || rowStatus.includes(status);
                const matchesPrice = price >= minPrice && price <= maxPrice;

                $row.toggle(matchesSearch && matchesCategory && matchesStatus && matchesPrice);
            });
        }

        // Image modal
        $('#imageModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const imgSrc = button.data('img-src');
            const modal = $(this);
            modal.find('#modalImage').attr('src', imgSrc);
        });

        // Inline editing: On double-click of price cell, show an input for editing
        $('.editable-price').dblclick(function() {
            const $cell = $(this);
            const productId = $cell.data('id');
            // Capture the current price (remove the currency symbol)
            const currentPrice = parseFloat($cell.text().replace('₱','').trim().replace(',', ''));
            // Replace cell content with an input field
            $cell.data('oldPrice', currentPrice);
            $cell.html('<input type="number" class="form-control form-control-sm inline-price-input" min="0.01" step="0.01" value="'+currentPrice+'">');
            // Focus the input field
            $cell.find('input').focus();
        });
        
        // On leaving the price field, send an AJAX update if the value changed
        $(document).on('blur', '.inline-price-input', function() {
            const $input = $(this);
            const newPrice = parseFloat($input.val());
            const $cell = $input.closest('.editable-price');
            const productId = $cell.data('id');
            const oldPrice = $cell.data('oldPrice');

            // If the new price is valid and changed, perform AJAX update
            if (newPrice > 0 && newPrice !== oldPrice) {
                $.ajax({
                    url: '../../controllers/AjaxController.php',
                    method: 'POST',
                    data: { action: 'updateProductPrice', product_id: productId, new_price: newPrice },
                    success: function(response) {
                        $cell.html('₱' + parseFloat(newPrice).toFixed(2));
                        
                        // Show success toast
                        showToast('Price Updated', 'Product price has been successfully updated to ₱' + parseFloat(newPrice).toFixed(2), 'success');
                    },
                    error: function() {
                        // On error revert to the old price and show toast
                        $cell.html('₱' + parseFloat(oldPrice).toFixed(2));
                        showToast('Update Failed', 'There was an error updating the product price.', 'error');
                    }
                });
            } else {
                $cell.html('₱' + parseFloat(oldPrice).toFixed(2));
            }
        });
        
        // On pressing Enter key in price input, trigger blur to save
        $(document).on('keydown', '.inline-price-input', function(e) {
            if (e.which === 13) {
                $(this).blur();
            }
        });
        
        // Toast notification function
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
    });
  </script>
</body>
</html>
