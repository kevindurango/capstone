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
        </div>

        <!-- Search Bar -->
        <div class="mb-3">
          <input type="text" id="productSearch" class="form-control w-25" placeholder="Search products...">
        </div>

        <!-- Product Table -->
        <div class="table-container">
          <table class="table table-bordered text-center">
            <thead>
              <tr>
                <th style="width: 10%;">ID</th>
                <th style="width: 20%;">Name</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 10%;">Price</th>
                <th style="width: 10%;">Image</th>
                <th style="width: 10%;">Farmer</th>
                <th style="width: 10%;">Actions</th>
              </tr>
            </thead>
            <tbody id="productTableBody">
              <?php foreach ($products as $product): ?>
                <tr>
                  <td><?= htmlspecialchars($product['product_id']) ?></td>
                  <td><?= htmlspecialchars($product['name']) ?></td>
                  <td><?= htmlspecialchars($product['description']) ?></td>
                  <td>$<?= number_format($product['price'], 2) ?></td>
                  <td>
                    <img src="uploads/<?= $product['image'] ?: 'placeholder.jpg' ?>" 
                         alt="Product Image" class="img-thumbnail" style="width: 50px; height: 50px;">
                  </td>
                  <td><?= htmlspecialchars($product['farmer_name']) ?></td>
                  <td>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-sm" data-toggle="modal" 
                            data-target="#editProductModal<?= $product['product_id'] ?>">
                      <i class="bi bi-pencil"></i> Edit
                    </button>
                    <!-- Delete Form -->
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                      <button type="submit" name="delete_product" class="btn btn-danger btn-sm" 
                              onclick="return confirm('Are you sure you want to delete this product?');">
                        <i class="bi bi-trash"></i> Delete
                      </button>
                    </form>
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
    // Product Search
    document.getElementById('productSearch').addEventListener('keyup', function () {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('#productTableBody tr');

      rows.forEach(row => {
        const name = row.cells[1].innerText.toLowerCase();
        row.style.display = name.includes(filter) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
