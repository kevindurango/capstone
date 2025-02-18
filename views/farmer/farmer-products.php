<?php
session_start();
require_once '../../models/Farmer.php';
require_once '../../controllers/FarmerController.php';

// Initialize FarmerController
$farmerController = new FarmerController();

// Handle farmer logout functionality
if (isset($_POST['logout'])) {
    $farmerController->farmerLogout(); // Call farmerLogout method
}

// Check if the user is logged in as a farmer
if (!isset($_SESSION['farmer_logged_in']) || $_SESSION['farmer_logged_in'] !== true) {
    header("Location: farmer-login.php");
    exit();
}

// Sample farmer ID for now
$farmer_id = $_SESSION['farmer_id']; // Assuming the farmer's ID is stored in the session

// Fetch products for the farmer
$farmer = new Farmer();
$products = $farmer->getProducts($farmer_id);

// Handle the form submission for adding a new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name'], $_POST['description'], $_POST['price'], $_FILES['image'])) {
    $productName = htmlspecialchars(trim($_POST['product_name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = floatval($_POST['price']);
    $status = 'pending'; // Set the status as pending since the admin will approve
    $image = $_FILES['image'];

    // Validate image upload
    if ($image['error'] === 0) {
        $imageFileType = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'png', 'jpeg'];
        if (!in_array($imageFileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG files are allowed.']);
            exit();
        }

        // Check for file size (max 2MB)
        if ($image['size'] > 2097152) {
            echo json_encode(['success' => false, 'message' => 'File size should not exceed 2MB.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Image upload failed.']);
        exit();
    }

    // Call the controller's method to add the product
    $response = $farmerController->addProduct($farmer_id, $productName, $description, $price, $status, $image);

    if (isset($response['error'])) {
        echo json_encode(['success' => false, 'message' => $response['error']]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
    }
    exit(); // Stop further execution
}

// Handle the form submission for updating a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    $productName = htmlspecialchars(trim($_POST['edit_product_name']));
    $description = htmlspecialchars(trim($_POST['edit_description']));
    $price = floatval($_POST['edit_price']);
    $status = 'pending'; // Don't allow the farmer to edit status, it remains "pending"
    $image = $_FILES['edit_image']; // If an image is uploaded

    // Update product logic
    $response = $farmerController->updateProduct($productId, $productName, $description, $price, $status, $image);

    if (isset($response['error'])) {
        echo json_encode(['success' => false, 'message' => $response['error']]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully!', 'id' => $productId, 'name' => $productName, 'description' => $description, 'price' => $price, 'status' => $status]);
    }
    exit(); // Stop further execution
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $productId = intval($_POST['delete_product_id']);

    // Call the delete method from FarmerController
    $response = $farmerController->deleteProduct($productId);

    if ($response) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting the product.']);
    }
    exit(); // Stop further execution
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/farmer.css">
    <style>
        .table-responsive {
            max-height: 400px; /* Set a max height for the scrollable area */
            overflow-y: auto; /* Enable vertical scrolling */
        }

        .table thead th {
            position: sticky; /* Make the header sticky */
            top: 0; /* Stick to the top */
            background-color: #fff; /* Background color for the header */
            z-index: 10; /* Ensure the header is above the content */
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../../public/assets/logo.png" alt="Logo" class="rounded-circle mr-2" style="width: 40px; height: 40px;">
            Farmer Dashboard
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="farmer-dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-products.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="farmer-profile.php">Profile</a></li>
                <li class="nav-item">
                    <form method="POST" class="nav-link p-0 ml-4">
                        <button type="submit" name="logout" class="btn btn-danger btn-logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </li>  
            </ul>
        </div>
    </nav>

    <!-- Page Title -->
    <div class="container mt-5">
        <h1>Manage Your Products</h1>
        <div id="statusAlert" class="alert alert-success d-none" role="alert"></div>
    </div>

    <!-- Product Table -->
    <div class="container">
        <div class="d-flex justify-content-between mb-3">
            <h3>Product List</h3>
            <button class="btn btn-success" data-toggle="modal" data-target="#addProductModal">
                <i class="bi bi-plus-circle"></i> Add Product
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if (!empty($products)) : ?>
                        <?php foreach ($products as $index => $product) : ?>
                            <tr id="product-<?= $product['product_id'] ?>">
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= '../../public/' . htmlspecialchars($product['image']) ?>" alt="Product Image" class="img-thumbnail" style="width: 80px; height: 80px;">
                                    <?php else: ?>
                                        <img src="../../public/assets/placeholder.png" alt="No Image" class="img-thumbnail" style="width: 80px; height: 80px;">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td>₱<?= number_format($product['price'], 2) ?></td> <!-- Peso symbol here -->
                                <td><?= ucfirst($product['status']) ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editProductModal" 
                                        data-id="<?= htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-description="<?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-price="<?= htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-status="<?= htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-image="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    <!-- Delete Button -->
                                    <button class="btn btn-danger btn-sm delete-product" data-id="<?= htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">No products available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addProductForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="productName">Product Name</label>
                            <input type="text" class="form-control" name="product_name" id="productName" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="price" required placeholder="₱0.00"> <!-- Peso symbol in placeholder -->
                        </div>
                        <!-- Status is hidden and set to "Pending" by default -->
                        <input type="hidden" name="status" id="status" value="pending">
                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <input type="file" class="form-control-file" name="image" id="image" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editProductForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="editProductId">
                        <div class="form-group">
                            <label for="editProductName">Product Name</label>
                            <input type="text" class="form-control" name="edit_product_name" id="editProductName" required>
                        </div>
                        <div class="form-group">
                            <label for="editDescription">Description</label>
                            <textarea class="form-control" name="edit_description" id="editDescription" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="editPrice">Price</label>
                            <input type="number" step="0.01" class="form-control" name="edit_price" id="editPrice" required placeholder="₱0.00">
                        </div>
                        <!-- Display status as plain text -->
                        <div class="form-group">
                            <label>Status</label>
                            <p id="editStatusDisplay" class="form-control-plaintext" style="margin-bottom: 0;"></p>
                        </div>
                        <div class="form-group">
                            <label for="editImage">Product Image</label>
                            <input type="file" class="form-control-file" name="edit_image" id="editImage" accept="image/*">
                            <img id="editImageDisplay" class="img-thumbnail mt-3" style="width: 100px; height: 100px; display: none;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y'); ?> Farmer Dashboard. All Rights Reserved.</p>
    </div>

    <!-- Bootstrap JS and Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $('#addProductForm').on('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        var formData = new FormData(this); // Create a FormData object

        $.ajax({
            url: 'farmer-products.php', // The URL to send the request to
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                response = JSON.parse(response); // Parse the JSON response
                if (response.success) {
                    // Show success notification
                    $('#statusAlert').removeClass('d-none alert-danger').addClass('alert-success')
                        .text('Product added successfully!')
                        .fadeIn().delay(3000).fadeOut();
                    
                    // Close the "Add Product" modal
                    $('#addProductModal').modal('hide');
                    
                    // Reload the page to reflect the changes
                    setTimeout(function() {
                        location.reload(); // This will refresh the page and show the updated product list
                    }, 3000); // Wait for the success message to show for 3 seconds before reloading
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred while adding the product.');
            }
        });
    });

    // Handle Update Product Form Submission
    $('#editProductForm').on('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        var formData = new FormData(this); // Create a FormData object

        $.ajax({
            url: 'farmer-products.php', // The URL to send the request to
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                response = JSON.parse(response); // Parse the JSON response
                if (response.success) {
                    // Update the product row in the table
                    var row = $(`#product-${response.id}`);
                    row.find('td:nth-child(3)').text(response.name);
                    row.find('td:nth-child(4)').text(response.description);
                    row.find('td:nth-child(5)').text(`₱${parseFloat(response.price).toFixed(2)}`);
                    row.find('td:nth-child(6)').text(response.status.charAt(0).toUpperCase() + response.status.slice(1));

                    // Hide the modal and reset the form
                    $('#editProductModal').modal('hide');
                    $('#editProductForm')[0].reset();

                    // Success notification
                    $('#statusAlert').removeClass('d-none').text('Product updated successfully!').fadeIn().delay(3000).fadeOut();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred while updating the product.');
            }
        });
    });

    // Handle Delete Product
    $(document).on('click', '.delete-product', function(event) {
        event.preventDefault(); // Prevent the default form submission
        var productId = $(this).data('id'); // Get the product ID

        $.ajax({
            url: 'farmer-products.php', // The URL to send the request to
            type: 'POST',
            data: { delete_product_id: productId },
            success: function(response) {
                response = JSON.parse(response); // Parse the JSON response
                if (response.success) {
                    // Remove the product row from the table
                    $(`#product-${productId}`).remove();
                    $('#statusAlert').removeClass('d-none').text('Product deleted successfully!').fadeIn().delay(3000).fadeOut(); // Success notification
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred while deleting the product.');
            }
        });
    });

    // Populate Edit Product Modal (Remove "status" from edit form)
    $('#editProductModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Get the button that triggered the modal
        var productId = button.data('id'); // Get product id from data-id attribute
        var productName = button.data('name'); // Get product name from data-name attribute
        var description = button.data('description'); // Get description from data-description
        var price = button.data('price'); // Get price from data-price
        var status = button.data('status'); // Get status from data-status
        var image = button.data('image'); // Get image from data-image

        var modal = $(this);
        modal.find('#editProductId').val(productId); // Set product ID in the hidden input
        modal.find('#editProductName').val(productName); // Set product name
        modal.find('#editDescription').val(description); // Set product description
        modal.find('#editPrice').val(price); // Set product price
        modal.find('#editStatusDisplay').text(status.charAt(0).toUpperCase() + status.slice(1)); // Display status as plain text
        if (image) {
            modal.find('#editImageDisplay').attr('src', '../../public/' + image).show(); // Show image if available
        } else {
            modal.find('#editImageDisplay').hide(); // Hide image display if no image exists
        }
    });
    </script>
</body>
</html>


