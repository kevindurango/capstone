<?php
// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: ../login.php');
    exit();
}

// Include necessary files and controllers
require_once '../../controllers/CropSeasonController.php';
require_once '../../controllers/productcontroller.php';

// Initialize controllers
$cropSeasonController = new CropSeasonController();
$productController = new ProductController();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Handle different form actions
        switch ($_POST['action']) {
            case 'addProductSeason':
                if (
                    isset($_POST['product_id']) && 
                    isset($_POST['season_id'])
                ) {
                    $yield_estimate = !empty($_POST['yield_estimate']) ? $_POST['yield_estimate'] : null;
                    $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
                    
                    if ($cropSeasonController->addProductSeason(
                        $_POST['product_id'],
                        $_POST['season_id'],
                        $yield_estimate,
                        $notes
                    )) {
                        $message = "Product season association added successfully.";
                        $messageType = "success";
                    } else {
                        $message = "Failed to add product season association.";
                        $messageType = "danger";
                    }
                }
                break;
        }
    }
}

// Get all crop seasons
$cropSeasons = $cropSeasonController->getAllCropSeasons();

// Get current active seasons
$currentSeasons = $cropSeasonController->getCurrentCropSeasons();

// Get seasonal production data
$seasonalProduction = $cropSeasonController->getSeasonalCropProduction();

// Get product list
$products = $productController->getAllProducts();

// Selected season for filtering
$selectedSeason = isset($_GET['season_id']) ? $_GET['season_id'] : null;

// Include header
include '../templates/admin-header.php';
?>

<main class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Include admin sidebar -->
            <?php include '../templates/admin-sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Crop Season Management</h4>
                </div>
                <div class="card-body">
                    <!-- Display messages -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Current Seasons -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Current Active Seasons</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php if (empty($currentSeasons)): ?>
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    No active seasons for the current month.
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($currentSeasons as $season): ?>
                                                <div class="col-md-4">
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($season['season_name']); ?></h5>
                                                            <p class="card-text">
                                                                <strong>Months:</strong> 
                                                                <?php 
                                                                $months = [
                                                                    1 => 'January', 2 => 'February', 3 => 'March', 
                                                                    4 => 'April', 5 => 'May', 6 => 'June',
                                                                    7 => 'July', 8 => 'August', 9 => 'September',
                                                                    10 => 'October', 11 => 'November', 12 => 'December'
                                                                ];
                                                                
                                                                $start = $months[$season['start_month']];
                                                                $end = $months[$season['end_month']];
                                                                echo "$start to $end";
                                                                ?>
                                                            </p>
                                                            <p class="card-text">
                                                                <?php echo htmlspecialchars($season['description'] ?? 'No description available.'); ?>
                                                            </p>
                                                            <a href="?season_id=<?php echo $season['season_id']; ?>" class="btn btn-sm btn-primary">View Associated Crops</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- All Crop Seasons -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">All Crop Seasons</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Season Name</th>
                                                    <th>Start Month</th>
                                                    <th>End Month</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cropSeasons as $season): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($season['season_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($months[$season['start_month']]); ?></td>
                                                        <td><?php echo htmlspecialchars($months[$season['end_month']]); ?></td>
                                                        <td><?php echo htmlspecialchars($season['description'] ?? 'No description available.'); ?></td>
                                                        <td>
                                                            <a href="?season_id=<?php echo $season['season_id']; ?>" class="btn btn-sm btn-info">View Crops</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($selectedSeason): ?>
                        <!-- Crops for Selected Season -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <?php
                                        $seasonName = '';
                                        foreach ($cropSeasons as $season) {
                                            if ($season['season_id'] == $selectedSeason) {
                                                $seasonName = $season['season_name'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <h5 class="mb-0">Crops for Season: <?php echo htmlspecialchars($seasonName); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Product Name</th>
                                                        <th>Estimated Yield</th>
                                                        <th>Notes</th>
                                                        <th>Barangays</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $seasonalData = $cropSeasonController->getCropProductionBySeason($selectedSeason);
                                                    if (empty($seasonalData)): 
                                                    ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">No crops associated with this season yet.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($seasonalData as $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['product_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['total_production'] . ' ' . $data['production_unit']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['notes'] ?? 'No notes'); ?></td>
                                                                <td><?php echo htmlspecialchars($data['barangay_name'] ?? 'N/A'); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add Product Season Association Form -->
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    Associate Product with Season
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="addProductSeason">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="product_id" class="form-label">Product</label>
                                                <select class="form-control" id="product_id" name="product_id" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="season_id" class="form-label">Season</label>
                                                <select class="form-control" id="season_id" name="season_id" required>
                                                    <option value="">Select Season</option>
                                                    <?php foreach ($cropSeasons as $season): ?>
                                                        <option value="<?php echo $season['season_id']; ?>"><?php echo htmlspecialchars($season['season_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="yield_estimate" class="form-label">Estimated Yield</label>
                                                <input type="number" class="form-control" id="yield_estimate" name="yield_estimate" step="0.01">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="notes" class="form-label">Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Associate Product with Season</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../templates/footer.php'; ?>