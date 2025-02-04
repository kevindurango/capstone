<?php
require_once '../../controllers/ProductController.php';

$productController = new ProductController();

// Fetch products
$products = $productController->getAllProducts();

// Fetch farmers (for dropdown selection in Add Product modal)
$farmers = $productController->getAllFarmers();

// Handle POST requests for editing and deleting products
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle product deletion
    if (isset($_POST['delete_product']) && !empty($_POST['product_id'])) {
        $productController->deleteProduct($_POST['product_id']);
        header("Location: product-management.php");
        exit();
    }

    // Handle product editing
    if (isset($_POST['edit_product'])) {
        $productId = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $farmerId = $_POST['farmer_id'];
        $image = $_FILES['image'] ?? null;
        $current_image = $_POST['current_image'];

        // Call the edit product function
        $productController->editProduct($productId, $name, $description, $price, $image, $current_image);
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
  <link rel="stylesheet" href="../../public/style/sidebar.css">
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
      <?php include '../../views/global/sidebar.php'; ?>

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
        <div class="table-container">
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
                    <img src="uploads/<?= $product['image'] ?: 'placeholder.jpg' ?>" alt="Product Image" class="img-thumbnail" style="width: 50px; height: 50px;">
                  </td>
                  <td><?= htmlspecialchars($product['farmer_name']) ?></td>
                  <td>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editProductModal<?= $product['product_id'] ?>">
                      <i class="bi bi-pencil"></i> Edit
                    </button>
                    <!-- Delete Form -->
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                      <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">
                        <i class="bi bi-trash"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Edit Product Modal -->
        <?php foreach ($products as $product): ?>
          <div class="modal fade" id="editProductModal<?= $product['product_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel<?= $product['product_id'] ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel<?= $product['product_id'] ?>">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <!-- Hidden input for product_id -->
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <div class="form-group">
                      <label for="editProductName">Product Name</label>
                      <input type="text" class="form-control" id="editProductName" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>
                    <div class="form-group">
                      <label for="editProductDescription">Description</label>
                      <textarea class="form-control" id="editProductDescription" name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="editProductPrice">Price</label>
                      <input type="number" class="form-control" id="editProductPrice" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
                    </div>
                    <div class="form-group">
                      <label for="editProductFarmer">Farmer</label>
                      <select class="form-control" id="editProductFarmer" name="farmer_id" required>
                        <?php foreach ($farmers as $farmer): ?>
                          <option value="<?= $farmer['user_id'] ?>" <?= $product['farmer_id'] == $farmer['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($farmer['username']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="editProductImage">Product Image</label>
                      <input type="file" class="form-control-file" id="editProductImage" name="image">
                      <input type="hidden" name="current_image" value="<?= htmlspecialchars($product['image']) ?>"> <!-- Keep current image -->
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="edit_product" class="btn btn-primary">Save Changes</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

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
