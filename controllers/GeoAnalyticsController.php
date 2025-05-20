<?php
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Dashboard.php';
require_once dirname(__DIR__) . '/models/Farmer.php';
require_once dirname(__DIR__) . '/models/Log.php';



/**
 * GeoAnalyticsController handles geographic and agricultural analysis
 * for the farmers market system across different barangays
 */
class GeoAnalyticsController {
    private $dashboard;
    private $farmer;
    private $log;
    private $db;
    private $conn;
    
    /**
     * Initialize the controller with required models
     */
    public function __construct() {
        $this->dashboard = new Dashboard();
        $this->farmer = new Farmer();
        $this->log = new Log();
        $this->db = new Database();
        $this->conn = $this->db->connect();
    }
    
    /**
     * Get farmer distribution and farm area metrics by barangay
     * 
     * @return array Metrics of farmers and area by barangay
     */
    public function getFarmerDistribution() {
        try {
            // Use the existing database view for consistency with reports
            $query = "SELECT * FROM view_farmers_per_barangay ORDER BY farmer_count DESC";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->log->logError("Error in getFarmerDistribution: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get crop production statistics by barangay
     * 
     * @return array Production data by barangay
     */
    public function getCropProductionByBarangay() {
        try {
            // Use the existing database view for consistency with reports
            $query = "SELECT * FROM view_crops_per_barangay ORDER BY barangay_name, total_production DESC";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->log->logError("Error in getCropProductionByBarangay: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get seasonal crop production data
     * 
     * @return array Seasonal crop production data
     */
    public function getSeasonalCropProduction() {
        try {
            // Use the existing database view for consistency with reports
            $query = "SELECT * FROM view_seasonal_crops ORDER BY season_name, total_production DESC";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->log->logError("Error in getSeasonalCropProduction: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active crop seasons for the current month
     * 
     * @return array List of currently active crop seasons
     */
    public function getCurrentCropSeasons() {
        try {
            return $this->dashboard->getCurrentCropSeasons();
        } catch (Exception $e) {
            $this->log->logError("Error in getCurrentCropSeasons: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top crops by production volume for a specific barangay
     * 
     * @param int $barangay_id ID of the barangay
     * @param int $limit Number of top crops to return
     * @return array Top crops in the specified barangay
     */
    public function getTopCropsByBarangay($barangay_id, $limit = 5) {
        try {
            return $this->dashboard->getTopCropsByBarangay($barangay_id, $limit);
        } catch (Exception $e) {
            $this->log->logError("Error in getTopCropsByBarangay: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get agricultural efficiency metrics (yield per area) by barangay
     * 
     * @return array Agricultural efficiency metrics
     */
    public function getBarangayEfficiencyMetrics() {
        try {
            // Calculate yield rates (production per area) for each barangay
            $query = "SELECT 
                        b.barangay_id,
                        b.barangay_name,
                        SUM(bp.estimated_production) AS total_production,
                        SUM(bp.planted_area) AS total_area,
                        CASE 
                            WHEN SUM(bp.planted_area) > 0 
                            THEN SUM(bp.estimated_production) / SUM(bp.planted_area) 
                            ELSE 0 
                        END AS yield_rate,
                        bp.production_unit,
                        bp.area_unit
                    FROM barangays b
                    LEFT JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
                    GROUP BY b.barangay_id, b.barangay_name, bp.production_unit, bp.area_unit
                    ORDER BY yield_rate DESC";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->log->logError("Error in getBarangayEfficiencyMetrics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get detailed agricultural production data with area metrics
     * 
     * @return array Detailed crop production with area metrics
     */
    public function getCropsWithAreaMetrics() {
        try {
            return $this->dashboard->getCropsWithAreaMetrics();
        } catch (Exception $e) {
            $this->log->logError("Error in getCropsWithAreaMetrics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get crop production by specific season
     * 
     * @param int $season_id Optional season ID to filter by
     * @return array Crop production filtered by season
     */
    public function getCropProductionBySeason($season_id = null) {
        try {
            return $this->dashboard->getCropProductionBySeason($season_id);
        } catch (Exception $e) {
            $this->log->logError("Error in getCropProductionBySeason: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get specific farmer production data with area metrics
     * 
     * @param int $farmer_id ID of the farmer
     * @return array Farmer's production and area metrics
     */
    public function getFarmerProductionMetrics($farmer_id) {
        try {
            return $this->farmer->getFarmerProductionMetrics($farmer_id);
        } catch (Exception $e) {
            $this->log->logError("Error in getFarmerProductionMetrics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total planted area for a specific farmer
     * 
     * @param int $farmer_id ID of the farmer
     * @return array Total area data for the farmer
     */
    public function getFarmerTotalPlantedArea($farmer_id) {
        try {
            return $this->farmer->getFarmerTotalPlantedArea($farmer_id);
        } catch (Exception $e) {
            $this->log->logError("Error in getFarmerTotalPlantedArea: " . $e->getMessage());
            return [
                'total_planted_area' => 0,
                'area_unit' => 'hectare'
            ];
        }
    }
    
    /**
     * Get products produced in a specific barangay
     * 
     * @param int $barangay_id ID of the barangay
     * @param int $year Optional year to filter by (defaults to current year)
     * @return array Products in the specified barangay
     */
    public function getBarangayProducts($barangay_id, $year = null) {
        try {
            return $this->farmer->getBarangayProducts($barangay_id, $year);
        } catch (Exception $e) {
            $this->log->logError("Error in getBarangayProducts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add new crop production data for a barangay
     * 
     * @param array $data Production data including barangay_id, product_id, etc.
     * @return bool Success or failure
     */
    public function addBarangayProduction($data) {
        try {
            // Validate required fields
            if (!isset($data['barangay_id']) || !isset($data['product_id']) || 
                !isset($data['estimated_production'])) {
                return false;
            }
            
            $barangay_id = $data['barangay_id'];
            $product_id = $data['product_id'];
            $production = $data['estimated_production'];
            $production_unit = $data['production_unit'] ?? 'kilogram';
            $planted_area = $data['planted_area'] ?? 0;
            $area_unit = $data['area_unit'] ?? 'hectare';
            $year = $data['year'] ?? date('Y');
            $season_id = $data['season_id'] ?? null;
            
            return $this->farmer->addBarangayProduct(
                $barangay_id, 
                $product_id, 
                $production, 
                $production_unit, 
                $planted_area, 
                $area_unit, 
                $year, 
                $season_id
            );
        } catch (Exception $e) {
            $this->log->logError("Error in addBarangayProduction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update existing crop production data for a barangay
     * 
     * @param int $id ID of the barangay_products record
     * @param array $data Updated production data
     * @return bool Success or failure
     */
    public function updateBarangayProduction($id, $data) {
        try {
            // Validate required fields
            if (!isset($data['estimated_production'])) {
                return false;
            }
            
            $production = $data['estimated_production'];
            $production_unit = $data['production_unit'] ?? 'kilogram';
            $planted_area = $data['planted_area'] ?? 0;
            $area_unit = $data['area_unit'] ?? 'hectare';
            $season_id = $data['season_id'] ?? null;
            
            return $this->farmer->updateBarangayProduct(
                $id, 
                $production, 
                $production_unit, 
                $planted_area, 
                $area_unit, 
                $season_id
            );
        } catch (Exception $e) {
            $this->log->logError("Error in updateBarangayProduction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a summary of production and yield efficiency for all barangays
     * 
     * @return array Summary of production efficiency for all barangays
     */
    public function getBarangayProductionSummary() {
        try {
            return $this->dashboard->getProductionStatsByBarangay();
        } catch (Exception $e) {
            $this->log->logError("Error in getBarangayProductionSummary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Handle incoming requests
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getSeasonDetails':
                $this->getSeasonDetails();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    }

    /**
     * Get season details by season name
     */
    private function getSeasonDetails() {
        $seasonName = $_GET['seasonName'] ?? '';
        
        if (empty($seasonName)) {
            echo json_encode(['success' => false, 'message' => 'Season name is required']);
            return;
        }
        
        try {
            // Get season details from database
            $query = "SELECT * FROM crop_seasons WHERE season_name = :seasonName";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':seasonName', $seasonName);
            $stmt->execute();
            
            $seasonDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$seasonDetails) {
                echo json_encode(['success' => false, 'message' => 'Season not found']);
                return;
            }
            
            // Get top crops for this season
            $topCropsQuery = "SELECT 
                p.name AS product_name,
                b.barangay_name,
                SUM(bp.estimated_production) AS total_production,
                bp.production_unit,
                SUM(bp.planted_area) AS total_planted_area,
                bp.area_unit
            FROM barangay_products bp
            JOIN products p ON bp.product_id = p.product_id
            JOIN barangays b ON bp.barangay_id = b.barangay_id
            JOIN crop_seasons cs ON bp.season_id = cs.season_id
            WHERE cs.season_name = :seasonName
            GROUP BY p.name, b.barangay_name, bp.production_unit, bp.area_unit
            ORDER BY total_production DESC
            LIMIT 10";
            
            $topCropsStmt = $this->conn->prepare($topCropsQuery);
            $topCropsStmt->bindParam(':seasonName', $seasonName);
            $topCropsStmt->execute();
            
            $topCrops = $topCropsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format response
            $response = [
                'success' => true,
                'seasonId' => $seasonDetails['season_id'],
                'seasonName' => $seasonDetails['season_name'],
                'startMonth' => $seasonDetails['start_month'],
                'endMonth' => $seasonDetails['end_month'],
                'description' => $seasonDetails['description'] ?? 'No description available',
                'recommendations' => $seasonDetails['planting_recommendations'] ?? 'No specific planting recommendations available for this season.',
                'topCrops' => $topCrops
            ];
            
            echo json_encode($response);
            
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}

// Handle request if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $controller = new GeoAnalyticsController();
    $controller->handleRequest();
}
?>