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

// Fetch geographic and seasonal data for this product
$barangayProductData = [];
try {
    require_once '../models/Database.php';
    $database = new Database();
    $conn = $database->connect();
    
    // Get barangay production data
    $barangayQuery = "SELECT bp.*, b.barangay_name, cs.season_name, cs.start_month, cs.end_month, 
                      cs.planting_recommendations
                      FROM barangay_products bp
                      JOIN barangays b ON bp.barangay_id = b.barangay_id
                      JOIN crop_seasons cs ON bp.season_id = cs.season_id
                      WHERE bp.product_id = :product_id
                      ORDER BY bp.estimated_production DESC";
    $stmt = $conn->prepare($barangayQuery);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    $barangayProductData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching geographic data for product $productId: " . $e->getMessage());
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
    
    <?php if (!empty($barangayProductData)): ?>
    <!-- Geographic and Seasonal Information -->
    <div class="mt-4">
        <h5 class="border-bottom pb-2"><i class="bi bi-geo-alt"></i> Geographic Information</h5>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Barangay</th>
                        <th>Season</th>
                        <th>Production</th>
                        <th>Planted Area</th>
                        <th>Yield Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($barangayProductData as $data): ?>
                        <?php 
                        // Calculate yield rate if planted area is available
                        $yieldRate = (!empty($data['planted_area']) && $data['planted_area'] > 0) 
                            ? $data['estimated_production'] / $data['planted_area']
                            : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($data['barangay_name']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($data['season_name']) ?>
                                </span>
                                <br>
                                <small class="text-muted">
                                    Month <?= htmlspecialchars($data['start_month']) ?>-<?= htmlspecialchars($data['end_month']) ?>
                                </small>
                            </td>
                            <td>
                                <?= number_format($data['estimated_production'], 2) ?>
                                <span class="unit-badge"><?= htmlspecialchars($data['production_unit']) ?></span>
                            </td>
                            <td>
                                <?= $data['planted_area'] ? number_format($data['planted_area'], 2) : 'N/A' ?>
                                <span class="unit-badge"><?= htmlspecialchars($data['area_unit'] ?? 'hectare') ?></span>
                            </td>
                            <td>
                                <?php if ($yieldRate > 0): ?>
                                    <?= number_format($yieldRate, 2) ?>
                                    <span class="unit-badge"><?= htmlspecialchars($data['production_unit']) ?>/<?= htmlspecialchars($data['area_unit'] ?? 'hectare') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Planting Recommendations -->
        <?php if (!empty($barangayProductData[0]['planting_recommendations'])): ?>
        <div class="alert alert-info mt-3">
            <h6><i class="bi bi-info-circle"></i> Planting Recommendations:</h6>
            <p><?= nl2br(htmlspecialchars($barangayProductData[0]['planting_recommendations'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
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