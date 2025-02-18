<?php
session_start();
require_once '../../controllers/ProductController.php';

$productController = new ProductController();

// Fetch products
$products = $productController->getAllProducts();

// Handle POST requests for approving and rejecting products
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle product rejection
    if (isset($_POST['reject_product']) && !empty($_POST['product_id'])) {
        $productController->updateProductStatus($_POST['product_id'], 'rejected');
        header("Location: product-management.php");
        exit();
    }

    // Handle product approval
    if (isset($_POST['approve_product']) && !empty($_POST['product_id'])) {
        $productController->updateProductStatus($_POST['product_id'], 'approved');
        header("Location: product-management.php");
        exit();
    }

    // Handle logout
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header("Location: admin-login.php");
        exit();
    }
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
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <style>
    .table-container {
      position: relative;
      overflow-x: auto;
      max-height: 450px;
    }
    .table-container thead th {
      position: sticky;
      top: 0;
      z-index: 1;
    }
    .table-container tbody td {
      vertical-align: middle;
    }
    .btn-primary, .btn-success, .btn-warning, .btn-danger {
      font-weight: bold;
    }
    .modal-header {
      background-color: #f7f7f7;
    }
    .modal-footer button {
      width: 120px;
    }
    .modal-title {
      font-weight: bold;
    }
    .table-bordered td, .table-bordered th {
      border: 1px solid #dee2e6;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../../views/global/admin-sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2 text-success">Product Management</h1>
          <div class="d-flex">

            <!-- Logout Button -->
            <form method="POST" class="ml-3">
              <button type="submit" name="logout" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </form>
          </div>
        </div>

        <!-- Search Bar & Filters -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
          <input type="text" id="productSearch" class="form-control w-25" placeholder="Search products...">
          <div class="form-inline">
            <select class="form-control mr-2" id="categoryFilter">
              <option value="">Filter by Category</option>
              <!-- Dynamically fill categories here -->
            </select>
            <input type="number" id="priceMin" class="form-control mr-2" placeholder="Min Price">
            <input type="number" id="priceMax" class="form-control" placeholder="Max Price">
            <button class="btn btn-info" id="filterButton">Filter</button>
          </div>
        </div>

<!-- Product Table -->
<div class="table-container mt-5">
  <table class="table table-bordered text-center">
    <thead class="table-header">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Image</th>
        <th>Farmer</th>
        <th>Actions</th>
        <th>Status</th> <!-- Moved Status to the last column -->
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
          <?php if (!empty($product['image'])): ?>
              <img src="<?= '../../public/' . htmlspecialchars($product['image']) ?>" alt="Product Image" class="img-thumbnail" style="width: 80px; height: 80px;">
          <?php else: ?>
              <img src="../../public/assets/placeholder.png" alt="No Image" class="img-thumbnail" style="width: 80px; height: 80px;">
          <?php endif; ?>
      </td>
          <td><?= htmlspecialchars($product['farmer_name']) ?></td>
          <td>
            <!-- Approve Button -->
            <?php if ($product['status'] !== 'approved'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <button type="submit" name="approve_product" class="btn btn-success btn-sm mb-2">
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
          </td>
          <td>
            <!-- Approved Status Indicator -->
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
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
      </main>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <script>
    // Product Search and Filtering Logic
    document.addEventListener("DOMContentLoaded", function() {
        const productSearch = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const priceMin = document.getElementById('priceMin');
        const priceMax = document.getElementById('priceMax');
        const filterButton = document.getElementById('filterButton');
        const productTableBody = document.getElementById('productTableBody');
        
        // Function to filter the products
        function filterProducts() {
            const searchQuery = productSearch.value.toLowerCase();
            const categoryValue = categoryFilter.value;
            const minPrice = parseFloat(priceMin.value) || 0;
            const maxPrice = parseFloat(priceMax.value) || Infinity;

            // Get all rows in the table
            const rows = productTableBody.getElementsByTagName('tr');

            // Loop through each row and check if it matches the filter conditions
            Array.from(rows).forEach(function(row) {
                const productName = row.cells[1].innerText.toLowerCase();
                const productCategory = row.cells[5].innerText.toLowerCase(); // Assuming the category is in the 6th column (index 5)
                const productPrice = parseFloat(row.cells[3].innerText.replace('₱', '').trim());

                // Check if row matches all filter conditions
                const matchesSearch = productName.includes(searchQuery);
                const matchesCategory = categoryValue ? productCategory.includes(categoryValue.toLowerCase()) : true;
                const matchesPrice = (productPrice >= minPrice && productPrice <= maxPrice);

                // If all conditions match, show the row, otherwise hide it
                if (matchesSearch && matchesCategory && matchesPrice) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Listen for input changes and filter products
        productSearch.addEventListener('keyup', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);
        priceMin.addEventListener('input', filterProducts);
        priceMax.addEventListener('input', filterProducts);
        
        // Trigger the filter immediately when the page loads
        filterProducts();
    });
  </script>
</body>
</html>
