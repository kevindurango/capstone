<?php
/**
 * AJAX handler for fetching product details
 * Used in the manager-product.php page
 */

// Initialize session if needed for authentication
session_start();

// Check if the user is a manager
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    echo '<div class="alert alert-danger">Access denied. Managers only.</div>';
    exit;
}

// Include required files
require_once '../models/Product.php';
require_once '../controllers/productcontroller.php';

// Check if product ID is provided
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    echo '<div class="alert alert-danger">Invalid product ID.</div>';
    exit;
}

$productId = (int)$_GET['product_id'];
$productController = new ProductController();

// Get detailed product information
$product = $productController->getProductWithDetails($productId);

if (!$product) {
    echo '<div class="alert alert-danger">Product not found.</div>';
    exit;
}

// Create the correct relative path for images
$imagePath = !empty($product['image']) 
    ? "../../public/" . htmlspecialchars($product['image']) 
    : "../../public/assets/default-product.png";

?>

<div class="product-details">
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-4 text-center">
            <img src="<?= $imagePath ?>" 
                 class="img-fluid rounded" style="max-height: 250px;" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        
        <!-- Product Info -->
        <div class="col-md-8">
            <h4><?= htmlspecialchars($product['name']) ?></h4>
            
            <div class="product-meta mb-3">
                <span class="badge badge-<?= getStatusBadgeClass($product['status']) ?> mr-2">
                    <?= ucfirst(htmlspecialchars($product['status'])) ?>
                </span>
                
                <?php if (!empty($product['category'])): ?>
                <span class="badge badge-info mr-2">
                    <?= htmlspecialchars($product['category']) ?>
                </span>
                <?php endif; ?>
                
                <span class="text-muted">ID: #<?= $product['product_id'] ?></span>
            </div>
            
            <table class="table table-bordered table-sm">
                <tr>
                    <th style="width: 120px;">Price</th>
                    <td>₱<?= number_format($product['price'], 2) ?></td>
                </tr>
                <tr>
                    <th>Stock</th>
                    <td>
                        <?php if ($product['stock'] <= 10): ?>
                            <span class="text-danger font-weight-bold"><?= $product['stock'] ?></span>
                        <?php else: ?>
                            <?= $product['stock'] ?>
                        <?php endif; ?>
                        <span class="unit-badge"><?= htmlspecialchars($product['unit_type']) ?></span>
                        
                        <?php if ($product['stock'] <= 10): ?>
                            <span class="badge badge-danger ml-2">Low Stock</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Farmer</th>
                    <td><?= htmlspecialchars($product['farmer_name'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Added on</th>
                    <td><?= date('F d, Y', strtotime($product['created_at'])) ?></td>
                </tr>
                <tr>
                    <th>Last Updated</th>
                    <td><?= date('F d, Y h:i A', strtotime($product['updated_at'] ?? $product['created_at'])) ?></td>
                </tr>
            </table>
            
            <?php if (!empty($product['description'])): ?>
            <div class="mt-3">
                <h5>Description</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-3 text-right">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success quick-update-status" data-id="<?= $product['product_id'] ?>">
            Update Status
        </button>
    </div>
</div>

<script>
$(document).ready(function() {
    // Hook up the quick update status button
    $('.quick-update-status').click(function() {
        let productId = $(this).data('id');
        $('#productDetailsModal').modal('hide');
        
        // Set the product ID and show the status update modal
        $('#status_product_id').val(productId);
        setTimeout(function() {
            $('#updateStatusModal').modal('show');
        }, 500);
    });
});
</script>

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