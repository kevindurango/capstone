<?php
require_once dirname(__DIR__) . '/models/Dashboard.php';
require_once dirname(__DIR__) . '/models/Farmer.php';
require_once dirname(__DIR__) . '/models/Log.php';

/**
 * BarangayController handles operations related to barangays
 * including their geographic data and associations with farmers
 */
class BarangayController {
    private $dashboard;
    private $farmer;
    private $log;
    
    /**
     * Initialize the controller with required models
     */
    public function __construct() {
        $this->dashboard = new Dashboard();
        $this->farmer = new Farmer();
        $this->log = new Log();
    }
    
    /**
     * Get all barangays
     * 
     * @return array List of all barangays
     */
    public function getAllBarangays() {
        try {
            return $this->dashboard->getAllBarangays();
        } catch (Exception $e) {
            $this->log->logError("Error fetching all barangays: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a specific barangay by ID
     * 
     * @param int $barangay_id The ID of the barangay
     * @return array|bool Barangay details or false if not found
     */
    public function getBarangayById($barangay_id) {
        try {
            $barangays = $this->dashboard->getAllBarangays();
            foreach ($barangays as $barangay) {
                if ($barangay['barangay_id'] == $barangay_id) {
                    return $barangay;
                }
            }
            return false;
        } catch (Exception $e) {
            $this->log->logError("Error fetching barangay by ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get farmers in a specific barangay
     * 
     * @param int $barangay_id The ID of the barangay
     * @return array List of farmers in the barangay
     */
    public function getFarmersByBarangay($barangay_id) {
        try {
            return $this->farmer->getFarmersByBarangay($barangay_id);
        } catch (Exception $e) {
            $this->log->logError("Error fetching farmers by barangay: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update a farmer's barangay association
     * 
     * @param int $farmer_id The ID of the farmer
     * @param int $barangay_id The ID of the barangay
     * @return bool Success or failure
     */
    public function updateFarmerBarangay($farmer_id, $barangay_id) {
        try {
            return $this->farmer->updateFarmerBarangay($farmer_id, $barangay_id);
        } catch (Exception $e) {
            $this->log->logError("Error updating farmer barangay: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get top crops for a specific barangay
     * 
     * @param int $barangay_id The ID of the barangay
     * @param int $limit Number of top crops to return
     * @return array List of top crops in the barangay
     */
    public function getTopCropsByBarangay($barangay_id, $limit = 5) {
        try {
            return $this->dashboard->getTopCropsByBarangay($barangay_id, $limit);
        } catch (Exception $e) {
            $this->log->logError("Error fetching top crops by barangay: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get products produced in a specific barangay
     * 
     * @param int $barangay_id The ID of the barangay
     * @param int $year Optional year to filter by
     * @return array List of products in the barangay
     */
    public function getBarangayProducts($barangay_id, $year = null) {
        try {
            return $this->farmer->getBarangayProducts($barangay_id, $year);
        } catch (Exception $e) {
            $this->log->logError("Error fetching barangay products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add a new agricultural production record for a barangay
     * 
     * @param array $data Production data
     * @return bool Success or failure
     */
    public function addBarangayProduction($data) {
        try {
            // Ensure required fields are present
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
            $this->log->logError("Error adding barangay production: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing agricultural production record for a barangay
     * 
     * @param int $id The ID of the barangay_products record
     * @param array $data Updated production data
     * @return bool Success or failure
     */
    public function updateBarangayProduction($id, $data) {
        try {
            // Ensure required fields are present
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
            $this->log->logError("Error updating barangay production: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get summary of farmer distribution and farm size by barangay
     * 
     * @return array Summary of farmers by barangay
     */
    public function getFarmerDistributionSummary() {
        try {
            return $this->dashboard->getFarmersPerBarangay();
        } catch (Exception $e) {
            $this->log->logError("Error fetching farmer distribution summary: " . $e->getMessage());
            return [];
        }
    }
}
?>