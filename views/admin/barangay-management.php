<?php
// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: ../login.php');
    exit();
}

// Include necessary files and controllers
require_once '../../controllers/BarangayController.php';
require_once '../../controllers/GeoAnalyticsController.php';

// Initialize controllers
$barangayController = new BarangayController();
$geoController = new GeoAnalyticsController();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Handle different form actions
        switch ($_POST['action']) {
            case 'updateFarmerBarangay':
                if (isset($_POST['farmer_id']) && isset($_POST['barangay_id'])) {
                    if ($barangayController->updateFarmerBarangay($_POST['farmer_id'], $_POST['barangay_id'])) {
                        $message = "Farmer's barangay updated successfully.";
                        $messageType = "success";
                    } else {
                        $message = "Failed to update farmer's barangay.";
                        $messageType = "danger";
                    }
                }
                break;
                
            case 'addBarangayProduct':
                if (
                    isset($_POST['barangay_id']) && 
                    isset($_POST['product_id']) && 
                    isset($_POST['estimated_production'])
                ) {
                    $production_unit = isset($_POST['production_unit']) ? $_POST['production_unit'] : 'kilogram';
                    $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
                    $season_id = isset($_POST['season_id']) ? $_POST['season_id'] : null;
                    
                    if ($barangayController->addBarangayProduct(
                        $_POST['barangay_id'],
                        $_POST['product_id'],
                        $_POST['estimated_production'],
                        $production_unit,
                        $year,
                        $season_id
                    )) {
                        $message = "Barangay product data added successfully.";
                        $messageType = "success";
                    } else {
                        $message = "Failed to add barangay product data.";
                        $messageType = "danger";
                    }
                }
                break;
        }
    }
}

// Get all barangays
$barangays = $barangayController->getAllBarangays();

// Get dashboard data
$dashboardData = $geoController->getGeographicDashboardData();

// For filtering and analysis
$selectedBarangay = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : null;
$barangayDetails = $selectedBarangay ? $geoController->getBarangayDetails($selectedBarangay) : null;

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
                    <h4 class="mb-0">Barangay Management</h4>
                </div>
                <div class="card-body">
                    <!-- Display messages -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Barangay Overview -->
                    <h5 class="mb-3">Barangay Overview</h5>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    Farmers per Barangay
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Barangay Name</th>
                                                <th>Farmer Count</th>
                                                <th>Total Farm Area (ha)</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dashboardData['farmers_by_barangay'] as $barangay): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($barangay['barangay_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($barangay['farmer_count']); ?></td>
                                                    <td><?php echo htmlspecialchars($barangay['total_farm_area'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <a href="?barangay_id=<?php echo $barangay['barangay_id']; ?>" class="btn btn-sm btn-info">View Details</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($selectedBarangay && $barangayDetails && !isset($barangayDetails['error'])): ?>
                        <!-- Barangay Details -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        Barangay Details: <?php echo htmlspecialchars($barangayDetails['barangay_info']['barangay_name']); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Barangay Information</h6>
                                                <p>
                                                    <strong>Name:</strong> <?php echo htmlspecialchars($barangayDetails['barangay_info']['barangay_name']); ?><br>
                                                    <strong>Municipality:</strong> <?php echo htmlspecialchars($barangayDetails['barangay_info']['municipality'] ?? 'Valencia'); ?><br>
                                                    <strong>Province:</strong> <?php echo htmlspecialchars($barangayDetails['barangay_info']['province'] ?? 'Negros Oriental'); ?><br>
                                                </p>
                                                <h6>Farmers</h6>
                                                <p>
                                                    <strong>Total Farmers:</strong> <?php echo htmlspecialchars($barangayDetails['farmers']['count'] ?? 0); ?><br>
                                                    <strong>Total Farm Area:</strong> <?php echo htmlspecialchars($barangayDetails['farmers']['total_area'] ?? 0); ?> hectares<br>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Top Crops</h6>
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Crop</th>
                                                            <th>Production</th>
                                                            <th>Unit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($barangayDetails['top_crops'] as $crop): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($crop['product_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($crop['total_production']); ?></td>
                                                                <td><?php echo htmlspecialchars($crop['production_unit']); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Barangay Product Form -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        Add Product Production Data
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="addBarangayProduct">
                                            <input type="hidden" name="barangay_id" value="<?php echo $selectedBarangay; ?>">
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="product_id" class="form-label">Product</label>
                                                    <select class="form-control" id="product_id" name="product_id" required>
                                                        <option value="">Select Product</option>
                                                        <?php 
                                                        // You would need to fetch products from a ProductController
                                                        // For now, using sample data
                                                        $sampleProducts = [
                                                            ['product_id' => 1, 'name' => 'Rice'],
                                                            ['product_id' => 2, 'name' => 'Corn'],
                                                            ['product_id' => 3, 'name' => 'Vegetables'],
                                                        ];
                                                        foreach ($sampleProducts as $product): 
                                                        ?>
                                                            <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label for="estimated_production" class="form-label">Estimated Production</label>
                                                    <input type="number" class="form-control" id="estimated_production" name="estimated_production" step="0.01" required>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label for="production_unit" class="form-label">Unit</label>
                                                    <select class="form-control" id="production_unit" name="production_unit">
                                                        <option value="kilogram">Kilogram</option>
                                                        <option value="ton">Ton</option>
                                                        <option value="piece">Piece</option>
                                                        <option value="sack">Sack</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="year" class="form-label">Year</label>
                                                    <select class="form-control" id="year" name="year">
                                                        <?php
                                                        $currentYear = date('Y');
                                                        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                                            echo '<option value="' . $y . '">' . $y . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label for="season_id" class="form-label">Season</label>
                                                    <select class="form-control" id="season_id" name="season_id">
                                                        <option value="">Select Season (Optional)</option>
                                                        <?php 
                                                        // You would need to fetch seasons from a CropSeasonController
                                                        // For now, using sample data
                                                        $sampleSeasons = [
                                                            ['season_id' => 1, 'season_name' => 'Dry Season'],
                                                            ['season_id' => 2, 'season_name' => 'Wet Season'],
                                                        ];
                                                        foreach ($sampleSeasons as $season): 
                                                        ?>
                                                            <option value="<?php echo $season['season_id']; ?>"><?php echo htmlspecialchars($season['season_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">Add Production Data</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Update Farmer's Barangay Form -->
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    Update Farmer Barangay Association
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="updateFarmerBarangay">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="farmer_id" class="form-label">Farmer</label>
                                                <select class="form-control" id="farmer_id" name="farmer_id" required>
                                                    <option value="">Select Farmer</option>
                                                    <?php 
                                                    // You would need to fetch farmers from a FarmerController
                                                    // For now, using sample data
                                                    $sampleFarmers = [
                                                        ['user_id' => 19, 'name' => 'Anna Lee'],
                                                        ['user_id' => 20, 'name' => 'David Moore'],
                                                        ['user_id' => 22, 'name' => 'Dayn Cristofer'],
                                                    ];
                                                    foreach ($sampleFarmers as $farmer): 
                                                    ?>
                                                        <option value="<?php echo $farmer['user_id']; ?>"><?php echo htmlspecialchars($farmer['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="barangay_id" class="form-label">Barangay</label>
                                                <select class="form-control" id="barangay_id" name="barangay_id" required>
                                                    <option value="">Select Barangay</option>
                                                    <?php foreach ($barangays as $barangay): ?>
                                                        <option value="<?php echo $barangay['barangay_id']; ?>"><?php echo htmlspecialchars($barangay['barangay_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Farmer's Barangay</button>
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