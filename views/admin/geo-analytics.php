<?php
// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: ../login.php');
    exit();
}

// Include necessary files and controllers
require_once '../../controllers/GeoAnalyticsController.php';

// Initialize controllers
$geoAnalyticsController = new GeoAnalyticsController();

// Get dashboard data
$dashboardData = $geoAnalyticsController->getGeographicDashboardData();

// Selected barangay for detailed view
$selectedBarangay = isset($_GET['barangay_id']) ? $_GET['barangay_id'] : null;
$barangayDetails = $selectedBarangay ? $geoAnalyticsController->getBarangayDetails($selectedBarangay) : null;

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
                    <h4 class="mb-0">Geographic Analytics Dashboard</h4>
                </div>
                <div class="card-body">
                    <!-- Overview Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">Total Barangays</h5>
                                    <p class="card-text display-4">
                                        <?php echo count($dashboardData['farmers_by_barangay']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Total Farmers</h5>
                                    <p class="card-text display-4">
                                        <?php 
                                        $totalFarmers = 0;
                                        foreach ($dashboardData['farmers_by_barangay'] as $barangay) {
                                            $totalFarmers += $barangay['farmer_count'];
                                        }
                                        echo $totalFarmers;
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h5 class="card-title">Active Seasons</h5>
                                    <p class="card-text display-4">
                                        <?php echo count($dashboardData['active_seasons']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Farmers per Barangay -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Farmers per Barangay</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="farmersBarangayChart" width="100%" height="40"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Farming Summary Table -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Barangay Farming Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Barangay</th>
                                                    <th>Farmer Count</th>
                                                    <th>Top Crop</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dashboardData['farming_summary'] as $summary): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($summary['barangay_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($summary['farmer_count']); ?></td>
                                                        <td><?php echo htmlspecialchars($summary['top_crop'] ?? 'None'); ?></td>
                                                        <td>
                                                            <a href="?barangay_id=<?php echo $summary['barangay_id']; ?>" class="btn btn-sm btn-info">View Details</a>
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
                    
                    <!-- Production Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Production Statistics by Barangay</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Barangay</th>
                                                    <th>Unique Crops</th>
                                                    <th>Total Production</th>
                                                    <th>Unit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dashboardData['production_stats'] as $stats): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($stats['barangay_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($stats['unique_crops']); ?></td>
                                                        <td><?php echo htmlspecialchars($stats['total_production'] ?? '0.00'); ?></td>
                                                        <td><?php echo htmlspecialchars($stats['unit'] ?? 'kilogram'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($selectedBarangay && $barangayDetails && !isset($barangayDetails['error'])): ?>
                        <!-- Barangay Details -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Barangay Details: <?php echo htmlspecialchars($barangayDetails['barangay_info']['barangay_name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>General Information</h6>
                                                <p>
                                                    <strong>Name:</strong> <?php echo htmlspecialchars($barangayDetails['barangay_info']['barangay_name']); ?><br>
                                                    <strong>Municipality:</strong> <?php echo htmlspecialchars($barangayDetails['barangay_info']['municipality'] ?? 'Valencia'); ?><br>
                                                    <strong>Province:</strong> <?php echo htmlspecialchars($barangayDetails['barangay_info']['province'] ?? 'Negros Oriental'); ?><br>
                                                </p>
                                                <h6>Agricultural Statistics</h6>
                                                <p>
                                                    <strong>Total Farmers:</strong> <?php echo htmlspecialchars($barangayDetails['farmers']['count'] ?? '0'); ?><br>
                                                    <strong>Total Farm Area:</strong> <?php echo htmlspecialchars($barangayDetails['farmers']['total_area'] ?? '0'); ?> hectares<br>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Top Crops</h6>
                                                <canvas id="topCropsChart" width="100%" height="150"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seasonal Data for Barangay -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Seasonal Crop Production in <?php echo htmlspecialchars($barangayDetails['barangay_info']['barangay_name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($barangayDetails['seasonal_data'])): ?>
                                            <div class="alert alert-info">
                                                No seasonal crop data available for this barangay.
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Season</th>
                                                            <th>Crop</th>
                                                            <th>Production</th>
                                                            <th>Unit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($barangayDetails['seasonal_data'] as $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['season_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['product_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['total_production']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['unit'] ?? 'kilogram'); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Include Chart.js for data visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Farmers per Barangay Chart
const farmersBarangayCtx = document.getElementById('farmersBarangayChart').getContext('2d');
const farmersBarangayData = {
    labels: [
        <?php 
        $labels = [];
        $farmerCounts = [];
        foreach ($dashboardData['farmers_by_barangay'] as $barangay) {
            $labels[] = "'" . htmlspecialchars($barangay['barangay_name']) . "'";
            $farmerCounts[] = $barangay['farmer_count'];
        }
        echo implode(', ', $labels);
        ?>
    ],
    datasets: [{
        label: 'Number of Farmers',
        data: [<?php echo implode(', ', $farmerCounts); ?>],
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
    }]
};

const farmersBarangayConfig = {
    type: 'bar',
    data: farmersBarangayData,
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
};

const farmersBarangayChart = new Chart(farmersBarangayCtx, farmersBarangayConfig);

<?php if ($selectedBarangay && $barangayDetails && !isset($barangayDetails['error'])): ?>
// Top Crops Chart for Selected Barangay
const topCropsCtx = document.getElementById('topCropsChart').getContext('2d');
const topCropsData = {
    labels: [
        <?php 
        $cropLabels = [];
        $cropValues = [];
        foreach ($barangayDetails['top_crops'] as $crop) {
            $cropLabels[] = "'" . htmlspecialchars($crop['product_name']) . "'";
            $cropValues[] = $crop['total_production'];
        }
        echo implode(', ', $cropLabels);
        ?>
    ],
    datasets: [{
        label: 'Production Amount',
        data: [<?php echo implode(', ', $cropValues); ?>],
        backgroundColor: [
            'rgba(255, 99, 132, 0.5)',
            'rgba(54, 162, 235, 0.5)',
            'rgba(255, 206, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)',
            'rgba(153, 102, 255, 0.5)'
        ],
        borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
        ],
        borderWidth: 1
    }]
};

const topCropsConfig = {
    type: 'doughnut',
    data: topCropsData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            title: {
                display: true,
                text: 'Top Crops by Production'
            }
        }
    }
};

const topCropsChart = new Chart(topCropsCtx, topCropsConfig);
<?php endif; ?>
</script>

<?php include '../templates/footer.php'; ?>