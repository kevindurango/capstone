<?php
require_once dirname(__DIR__) . '/models/Dashboard.php';
require_once dirname(__DIR__) . '/models/Farmer.php';
require_once dirname(__DIR__) . '/models/Log.php';

/**
 * CropSeasonController handles operations related to crop seasons
 * and their relationships with products and agricultural production
 */
class CropSeasonController {
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
     * Get all crop seasons
     * 
     * @return array List of all crop seasons
     */
    public function getAllCropSeasons() {
        try {
            return $this->dashboard->getAllCropSeasons();
        } catch (Exception $e) {
            $this->log->logError("Error fetching all crop seasons: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get currently active crop seasons based on current month
     * 
     * @return array List of active crop seasons
     */
    public function getCurrentCropSeasons() {
        try {
            return $this->dashboard->getCurrentCropSeasons();
        } catch (Exception $e) {
            $this->log->logError("Error fetching current crop seasons: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get crop production data by season
     * 
     * @param int $season_id Optional season ID to filter by
     * @return array Crop production data organized by season
     */
    public function getCropProductionBySeason($season_id = null) {
        try {
            return $this->dashboard->getCropProductionBySeason($season_id);
        } catch (Exception $e) {
            $this->log->logError("Error fetching crop production by season: " . $e->getMessage());
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
            return $this->dashboard->getSeasonalCropProduction();
        } catch (Exception $e) {
            $this->log->logError("Error fetching seasonal crop production: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Associate a product with a specific crop season
     * 
     * @param int $product_id Product ID
     * @param int $season_id Season ID
     * @param float $yield_estimate Optional yield estimate
     * @param string $notes Optional notes about this product-season association
     * @return bool Success or failure
     */
    public function addProductSeason($product_id, $season_id, $yield_estimate = null, $notes = null) {
        try {
            return $this->farmer->addProductSeason($product_id, $season_id, $yield_estimate, $notes);
        } catch (Exception $e) {
            $this->log->logError("Error adding product season: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all seasons associated with a specific product
     * 
     * @param int $product_id Product ID
     * @return array Seasons associated with the product
     */
    public function getProductSeasons($product_id) {
        try {
            return $this->farmer->getProductSeasons($product_id);
        } catch (Exception $e) {
            $this->log->logError("Error fetching product seasons: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if a given month falls within a specific season
     * 
     * @param int $month Month (1-12)
     * @param int $season_id Season ID
     * @return bool True if the month is in the season, False otherwise
     */
    public function isMonthInSeason($month, $season_id) {
        try {
            $seasons = $this->getAllCropSeasons();
            foreach ($seasons as $season) {
                if ($season['season_id'] == $season_id) {
                    $startMonth = $season['start_month'];
                    $endMonth = $season['end_month'];
                    
                    // Handle seasons that cross year boundaries (e.g., Nov-Feb)
                    if ($startMonth <= $endMonth) {
                        // Regular season (e.g., Jun-Aug)
                        return ($month >= $startMonth && $month <= $endMonth);
                    } else {
                        // Season crosses year boundary (e.g., Nov-Feb)
                        return ($month >= $startMonth || $month <= $endMonth);
                    }
                }
            }
            return false;
        } catch (Exception $e) {
            $this->log->logError("Error checking if month is in season: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get crops that are in season for the current month
     * 
     * @return array Crops currently in season
     */
    public function getCurrentSeasonCrops() {
        try {
            $currentMonth = date('n'); // Current month (1-12)
            $currentSeasons = $this->getCurrentCropSeasons();
            $seasonIds = array_column($currentSeasons, 'season_id');
            
            if (empty($seasonIds)) {
                return [];
            }
            
            // Get all products with their seasons
            $seasonalProduction = $this->getSeasonalCropProduction();
            $currentCrops = [];
            
            foreach ($seasonalProduction as $crop) {
                if (in_array($crop['season_id'], $seasonIds)) {
                    $currentCrops[] = $crop;
                }
            }
            
            return $currentCrops;
        } catch (Exception $e) {
            $this->log->logError("Error fetching current season crops: " . $e->getMessage());
            return [];
        }
    }
}
?>