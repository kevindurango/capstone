<?php
// File: C:\xampp\htdocs\capstone\models\Dashboard.php
require_once 'Database.php';

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Get count of users
    public function getUserCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }

    // Get count of products
    public function getProductCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM products");
        return $stmt->fetchColumn();
    }

    // Get count of orders by status
    public function getOrderCountByStatus($status) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE order_status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchColumn();
    }

    // Get count of shipping info by status - Fixed to use pickups table instead of shippinginfo
    public function getPickupCountByStatus($status) {
        try {
            // Updated to use consistent status values across the application
            $validStatuses = ['pending', 'scheduled', 'in_transit', 'picked_up', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                error_log("Warning: Invalid pickup status requested: $status");
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM pickups WHERE pickup_status = ?");
            $stmt->execute([$status]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting pickup count by status: " . $e->getMessage());
            return 0;
        }
    }

    // Get sales data
    public function getSalesData() {
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(o.order_date, '%Y-%m') AS month, SUM(oi.price * oi.quantity) AS sales
            FROM orders o
            JOIN orderitems oi ON o.order_id = oi.order_id
            GROUP BY month
            ORDER BY month
        ");
        $salesData = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $salesData[$row['month']] = $row['sales'];
        }
        return $salesData;
    }

    // Get count of feedback
    public function getFeedbackCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM feedback");
        return $stmt->fetchColumn();
    }
    
    /**
     * Get total revenue from all completed orders
     * @return float Total revenue amount
     */
    public function getTotalRevenue() {
        $stmt = $this->db->prepare("
            SELECT SUM(oi.price * oi.quantity) as total_revenue 
            FROM orderitems oi 
            INNER JOIN orders o ON oi.order_id = o.order_id 
            WHERE o.order_status = ?
        ");
        $stmt->execute(['completed']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total_revenue'] ? floatval($result['total_revenue']) : 0;
    }
    
    /**
     * Get monthly revenue from orders in the current month
     * @return float Monthly revenue amount
     */
    public function getMonthlyRevenue() {
        $currentMonth = date('Y-m-01');
        
        $stmt = $this->db->prepare("
            SELECT SUM(oi.price * oi.quantity) as monthly_revenue 
            FROM orderitems oi 
            INNER JOIN orders o ON oi.order_id = o.order_id 
            WHERE o.order_status = ? 
            AND o.order_date >= ?
        ");
        $stmt->execute(['completed', $currentMonth]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['monthly_revenue'] ? floatval($result['monthly_revenue']) : 0;
    }
    
    /**
     * Calculate the revenue change percentage compared to the previous month
     * @return float Percentage change (positive or negative)
     */
    public function getRevenueChangePercentage() {
        $currentMonth = date('Y-m-01');
        $previousMonth = date('Y-m-01', strtotime('-1 month'));
        
        // Current month revenue
        $stmt1 = $this->db->prepare("
            SELECT SUM(oi.price * oi.quantity) as current_revenue 
            FROM orderitems oi 
            INNER JOIN orders o ON oi.order_id = o.order_id 
            WHERE o.order_status = ? 
            AND o.order_date >= ?
        ");
        $stmt1->execute(['completed', $currentMonth]);
        $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $currentRevenue = $result1['current_revenue'] ? floatval($result1['current_revenue']) : 0;
        
        // Previous month revenue
        $stmt2 = $this->db->prepare("
            SELECT SUM(oi.price * oi.quantity) as previous_revenue 
            FROM orderitems oi 
            INNER JOIN orders o ON oi.order_id = o.order_id 
            WHERE o.order_status = ? 
            AND o.order_date >= ? 
            AND o.order_date < ?
        ");
        $stmt2->execute(['completed', $previousMonth, $currentMonth]);
        $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $previousRevenue = $result2['previous_revenue'] ? floatval($result2['previous_revenue']) : 0;
        
        // Calculate percentage change
        if ($previousRevenue > 0) {
            return (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
        } elseif ($currentRevenue > 0) {
            return 100; // If previous month was 0 but current month has revenue, that's a 100% increase
        } else {
            return 0; // If both months are 0, then no change
        }
    }
    
    /**
     * Get recent activity (orders, user registrations, etc.)
     * @param int $limit Number of activities to return
     * @return array Activities
     */
    public function getRecentActivity($limit = 5) {
        $activities = [];
        
        // Recent orders - Fix for LIMIT clause
        $stmt1 = $this->db->prepare("
            SELECT o.order_id, o.order_date, u.first_name, u.last_name, o.order_status 
            FROM orders o 
            INNER JOIN users u ON o.consumer_id = u.user_id 
            ORDER BY o.order_date DESC 
            LIMIT :limit
        ");
        $stmt1->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt1->execute();
        
        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'type' => 'order',
                'icon' => 'cart-check',
                'color' => 'info',
                'title' => "New order #{$row['order_id']} by {$row['first_name']} {$row['last_name']}",
                'status' => $row['order_status'],
                'date' => $row['order_date']
            ];
        }
        
        // Recent user registrations - Fix for LIMIT clause
        $stmt2 = $this->db->prepare("
            SELECT user_id, first_name, last_name, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt2->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt2->execute();
        
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'type' => 'user',
                'icon' => 'person-plus',
                'color' => 'success',
                'title' => "New user registered: {$row['first_name']} {$row['last_name']}",
                'date' => $row['created_at']
            ];
        }
        
        // Recent product additions - Fix for LIMIT clause
        $stmt3 = $this->db->prepare("
            SELECT p.product_id, p.name, p.created_at, u.first_name, u.last_name 
            FROM products p 
            LEFT JOIN users u ON p.farmer_id = u.user_id 
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt3->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt3->execute();
        
        while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
            $farmerName = !empty($row['first_name']) ? "{$row['first_name']} {$row['last_name']}" : "Unknown";
            $activities[] = [
                'type' => 'product',
                'icon' => 'box',
                'color' => 'warning',
                'title' => "New product added: {$row['name']} by {$farmerName}",
                'date' => $row['created_at']
            ];
        }
        
        // Low stock products - Fix for LIMIT clause
        $stmt4 = $this->db->prepare("
            SELECT product_id, name, stock 
            FROM products 
            WHERE stock < 5 AND stock > 0 
            ORDER BY stock ASC 
            LIMIT :limit
        ");
        $stmt4->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt4->execute();
        
        while ($row = $stmt4->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'type' => 'stock',
                'icon' => 'exclamation-circle',
                'color' => 'danger',
                'title' => "Low stock alert: {$row['name']} (only {$row['stock']} left)",
                'date' => date('Y-m-d H:i:s') // Current date as these aren't event-based
            ];
        }
        
        // Sort all activities by date (most recent first)
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Return only the requested number
        return array_slice($activities, 0, $limit);
    }

    /**
     * Get recent system activities
     *
     * @param int $limit Number of activities to return
     * @return array Array of recent activities
     */
    public function getRecentActivities($limit = 5) {
        try {
            // Connect to the database
            $db = new Database();
            $conn = $db->getConnection();
            
            // Query to fetch recent activities from your activity log table
            $query = "SELECT activity, timestamp FROM activity_log 
                     ORDER BY timestamp DESC LIMIT :limit";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Return empty array on error
            return [];
        }
    }
    
    /**
     * Get products with low stock
     *
     * @param int $limit Number of products to return
     * @param int $threshold Stock threshold to consider low
     * @return array Array of products with low stock
     */
    public function getLowStockProducts($limit = 5) {
        try {
            // First check if reorder_point exists in the products table
            $checkColumn = "SHOW COLUMNS FROM products LIKE 'reorder_point'";
            $columnExists = $this->db->query($checkColumn)->rowCount() > 0;
            
            if ($columnExists) {
                // Use reorder_point if it exists
                $query = "SELECT 
                            product_id,
                            name,
                            stock,
                            COALESCE(reorder_point, 10) as reorder_point
                         FROM products 
                         WHERE stock <= COALESCE(reorder_point, 10)
                         AND status = 'approved'
                         ORDER BY stock ASC 
                         LIMIT :limit";
            } else {
                // Use default threshold of 10 if reorder_point doesn't exist
                $query = "SELECT 
                            product_id,
                            name,
                            stock,
                            10 as reorder_point
                         FROM products 
                         WHERE stock <= 10
                         AND status = 'approved'
                         ORDER BY stock ASC 
                         LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting low stock products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get pending pickups with details
     * @param int $limit Optional number of pickups to retrieve
     * @return array Array of pending pickups with order details
     */
    public function getPendingPickups($limit = 5) {
        try {
            $query = "SELECT p.*, o.order_date, u.username as customer_name 
                     FROM pickups p 
                     LEFT JOIN orders o ON p.order_id = o.order_id 
                     LEFT JOIN users u ON o.consumer_id = u.user_id 
                     WHERE p.pickup_status = 'pending' 
                     ORDER BY p.pickup_date ASC";
            
            if ($limit > 0) {
                $query .= " LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($query);
            
            if ($limit > 0) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPendingPickups: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get assigned pickups count
     * @return int Number of assigned pickups
     */
    public function getAssignedPickupsCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM pickups WHERE pickup_status = 'assigned'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting assigned pickups count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get completed pickups count
     * @return int Number of completed pickups
     */
    public function getCompletedPickupsCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM pickups WHERE pickup_status = 'completed'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting completed pickups count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total sales amount from all orders
     * @return float Total sales amount
     */
    public function getTotalSalesAmount() {
        try {
            $stmt = $this->db->query("
                SELECT SUM(oi.price * oi.quantity) as total_sales 
                FROM orderitems oi 
                INNER JOIN orders o ON oi.order_id = o.order_id
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total_sales'] ? floatval($result['total_sales']) : 0;
        } catch (PDOException $e) {
            error_log("Error getting total sales amount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get farmers per barangay using view_farmers_per_barangay view
     * @return array Array of barangays with farmer counts
     */
    public function getFarmersPerBarangay() {
        try {
            $stmt = $this->db->query("SELECT * FROM view_farmers_per_barangay ORDER BY farmer_count DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting farmers per barangay: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get crops per barangay using view_crops_per_barangay view
     * @return array Array of crops by barangay
     */
    public function getCropsPerBarangay() {
        try {
            $stmt = $this->db->query("SELECT * FROM view_crops_per_barangay ORDER BY barangay_name, product_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting crops per barangay: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get seasonal crop production data using view_seasonal_crops view
     * @return array Array of seasonal crop data
     */
    public function getSeasonalCropProduction() {
        try {
            $stmt = $this->db->query("SELECT * FROM view_seasonal_crops ORDER BY season_name, product_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting seasonal crop production: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top crops in a specific barangay
     * @param int $barangay_id Barangay ID to analyze
     * @param int $limit Number of top crops to return
     * @return array Top crops by production volume
     */
    public function getTopCropsByBarangay($barangay_id, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.name as product_name, 
                       SUM(bp.estimated_production) as total_production,
                       bp.production_unit
                FROM barangay_products bp
                JOIN products p ON bp.product_id = p.product_id
                WHERE bp.barangay_id = :barangay_id
                GROUP BY p.name, bp.production_unit
                ORDER BY total_production DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting top crops by barangay: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get crop production by season
     * @param int $season_id Optional season ID to filter by
     * @return array Crop production data by season
     */
    public function getCropProductionBySeason($season_id = null) {
        try {
            $query = "
                SELECT cs.season_name, 
                       p.name as product_name,
                       SUM(bp.estimated_production) as total_production,
                       bp.production_unit
                FROM barangay_products bp
                JOIN products p ON bp.product_id = p.product_id
                JOIN crop_seasons cs ON bp.season_id = cs.season_id
                WHERE 1=1
            ";
            
            if ($season_id) {
                $query .= " AND bp.season_id = :season_id";
            }
            
            $query .= " GROUP BY cs.season_name, p.name, bp.production_unit
                        ORDER BY cs.season_name, total_production DESC";
            
            $stmt = $this->db->prepare($query);
            
            if ($season_id) {
                $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting crop production by season: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all barangays with their details
     * @return array All barangays in the system
     */
    public function getAllBarangays() {
        try {
            $stmt = $this->db->query("SELECT * FROM barangays ORDER BY barangay_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all barangays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all crop seasons
     * @return array All defined crop seasons
     */
    public function getAllCropSeasons() {
        try {
            $stmt = $this->db->query("SELECT * FROM crop_seasons ORDER BY start_month");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all crop seasons: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get current active crop season(s) based on current month
     * @return array Active crop seasons
     */
    public function getCurrentCropSeasons() {
        try {
            $currentMonth = date('n'); // Current month number (1-12)
            
            $stmt = $this->db->prepare("
                SELECT * FROM crop_seasons
                WHERE (:current_month BETWEEN start_month AND end_month)
                   OR (start_month > end_month AND 
                      (:current_month >= start_month OR :current_month <= end_month))
                ORDER BY season_name
            ");
            $stmt->bindParam(':current_month', $currentMonth, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting current crop seasons: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get production statistics by barangay
     * @return array Production statistics grouped by barangay
     */
    public function getProductionStatsByBarangay() {
        try {
            $stmt = $this->db->query("
                SELECT b.barangay_id,
                       b.barangay_name,
                       COUNT(DISTINCT bp.product_id) as unique_crops,
                       SUM(bp.estimated_production) as total_production,
                       MAX(bp.production_unit) as unit
                FROM barangays b
                LEFT JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
                GROUP BY b.barangay_id, b.barangay_name
                ORDER BY unique_crops DESC, total_production DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting production stats by barangay: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get summary of farmers and farm area by barangay
     * @return array Summary data of farmers and area by barangay
     */
    public function getFarmingSummaryByBarangay() {
        try {
            // Call the get_farmer_count_by_barangay function for each barangay
            $stmt = $this->db->query("
                SELECT b.barangay_id, 
                       b.barangay_name,
                       get_farmer_count_by_barangay(b.barangay_id) as farmer_count,
                       get_top_crop_by_barangay(b.barangay_id) as top_crop
                FROM barangays b
                ORDER BY farmer_count DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting farming summary by barangay: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get crops with area metrics per barangay - new method for area analysis
     * @return array Array of crops with production and area metrics by barangay
     */
    public function getCropsWithAreaMetrics() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    b.barangay_name,
                    p.name as product_name,
                    SUM(bp.estimated_production) as total_production,
                    MAX(bp.production_unit) as production_unit,
                    SUM(bp.planted_area) as total_planted_area,
                    MAX(bp.area_unit) as area_unit,
                    CASE 
                        WHEN SUM(bp.planted_area) > 0 
                        THEN SUM(bp.estimated_production) / SUM(bp.planted_area) 
                        ELSE 0 
                    END as yield_per_area
                FROM barangay_products bp
                JOIN barangays b ON bp.barangay_id = b.barangay_id
                JOIN products p ON bp.product_id = p.product_id
                GROUP BY b.barangay_name, p.name
                ORDER BY b.barangay_name, total_production DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting crop metrics with area: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get barangay agricultural efficiency metrics
     * @return array Array of barangays with yield efficiency metrics
     */
    public function getBarangayEfficiencyMetrics() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    b.barangay_id,
                    b.barangay_name,
                    SUM(bp.planted_area) as total_area_planted,
                    MAX(bp.area_unit) as area_unit,
                    SUM(bp.estimated_production) as total_production,
                    MAX(bp.production_unit) as production_unit,
                    CASE 
                        WHEN SUM(bp.planted_area) > 0 
                        THEN SUM(bp.estimated_production) / SUM(bp.planted_area) 
                        ELSE 0 
                    END as average_yield
                FROM barangays b
                LEFT JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
                GROUP BY b.barangay_id, b.barangay_name
                ORDER BY average_yield DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting barangay efficiency metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get barangay overview with agricultural metrics (crops & farmers)
     * Shows all barangays including those without crop data
     * @return array All barangays with available metrics
     */
    public function getAllBarangaysWithMetrics() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    b.barangay_id,
                    b.barangay_name,
                    b.municipality,
                    b.province,
                    COALESCE(COUNT(DISTINCT bp.product_id), 0) as crop_count,
                    COALESCE(COUNT(DISTINCT fd.user_id), 0) as farmer_count,
                    COALESCE(SUM(fd.farm_size), 0) as total_farm_area,
                    COALESCE(SUM(bp.planted_area), 0) as total_planted_area
                FROM barangays b
                LEFT JOIN barangay_products bp ON b.barangay_id = bp.barangay_id
                LEFT JOIN farmer_details fd ON b.barangay_id = fd.barangay_id
                GROUP BY b.barangay_id, b.barangay_name, b.municipality, b.province
                ORDER BY b.barangay_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all barangay metrics: " . $e->getMessage());
            return [];
        }
    }
}
?>
