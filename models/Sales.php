<?php
// Check if the Database class is already defined before including
require_once 'Database.php';

class Sales {
    private $conn;

    public function __construct() {
        // Check if we're using the new Database class or need to connect directly
        if (class_exists('Database')) {
            $database = new Database();
            $this->conn = $database->connect();
        } else {
            // Fallback to direct connection if needed
            $this->conn = $this->getConnection();
        }
    }

    // Fallback connection method if needed
    private function getConnection() {
        // Define database parameters (should match those in Database class)
        $host = "localhost";
        $db_name = "farmersmarketdb";
        $username = "root";
        $password = "";
        
        try {
            // Create PDO connection
            $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getDailySales() {
        $today = date('Y-m-d');
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE DATE(o.order_date) = :today";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ? $result['total'] : 0;
        } catch(PDOException $e) {
            error_log("Error getting daily sales: " . $e->getMessage());
            return 0;
        }
    }

    public function getWeeklySales() {
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE o.order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ? $result['total'] : 0;
        } catch(PDOException $e) {
            error_log("Error getting weekly sales: " . $e->getMessage());
            return 0;
        }
    }

    public function getMonthlySales() {
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE o.order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ? $result['total'] : 0;
        } catch(PDOException $e) {
            error_log("Error getting monthly sales: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRevenue() {
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ? $result['total'] : 0;
        } catch(PDOException $e) {
            error_log("Error getting total revenue: " . $e->getMessage());
            return 0;
        }
    }

    public function getAverageOrderValue() {
        $query = "SELECT AVG(order_total) as avg_order
                 FROM (
                     SELECT o.order_id, SUM(oi.price * oi.quantity) as order_total
                     FROM orders o
                     JOIN orderitems oi ON o.order_id = oi.order_id
                     GROUP BY o.order_id
                 ) as order_totals";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['avg_order'] ? $result['avg_order'] : 0;
        } catch(PDOException $e) {
            error_log("Error getting average order value: " . $e->getMessage());
            return 0;
        }
    }

    public function getSalesByCategory() {
        $query = "SELECT pc.category_name as category, SUM(oi.price * oi.quantity) as total
                 FROM orderitems oi
                 JOIN products p ON oi.product_id = p.product_id
                 JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                 JOIN productcategories pc ON pcm.category_id = pc.category_id
                 GROUP BY pc.category_name
                 ORDER BY total DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $categories;
        } catch(PDOException $e) {
            error_log("Error getting sales by category: " . $e->getMessage());
            return [];
        }
    }

    public function getTopProducts($limit = 5) {
        $query = "SELECT p.product_id, p.name, pc.category_name as category,
                 SUM(oi.quantity) as units_sold,
                 SUM(oi.price * oi.quantity) as revenue,
                 CASE 
                     WHEN SUM(oi.quantity) > (
                         SELECT AVG(total_sold) FROM (
                             SELECT SUM(quantity) as total_sold 
                             FROM orderitems 
                             GROUP BY product_id
                         ) as avg_sales
                     ) THEN 1 ELSE -1 
                 END as trend
                 FROM orderitems oi
                 JOIN products p ON oi.product_id = p.product_id
                 LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                 LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id
                 GROUP BY p.product_id, p.name, pc.category_name
                 ORDER BY revenue DESC
                 LIMIT :limit";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $products;
        } catch(PDOException $e) {
            error_log("Error getting top products: " . $e->getMessage());
            return [];
        }
    }

    public function getRevenueData($startDate = null, $endDate = null) {
        // Default to last 30 days if no dates provided
        $startDate = $startDate ? $startDate : date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ? $endDate : date('Y-m-d');
        
        $query = "SELECT DATE(o.order_date) as date, 
                 SUM(oi.price * oi.quantity) as amount
                 FROM orders o
                 JOIN orderitems oi ON o.order_id = oi.order_id
                 WHERE o.order_date BETWEEN :start_date AND :end_date
                 GROUP BY DATE(o.order_date)
                 ORDER BY date ASC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $revenue;
        } catch(PDOException $e) {
            error_log("Error getting revenue data: " . $e->getMessage());
            return [];
        }
    }

    public function getFilteredSalesData($startDate, $endDate) {
        // Similar to getRevenueData but can include additional metrics
        return $this->getRevenueData($startDate, $endDate);
    }

    public function exportSalesReport($startDate = null, $endDate = null) {
        // Get filtered data for export
        $salesData = $this->getFilteredSalesData($startDate, $endDate);
        $topProducts = $this->getTopProducts(10);
        $categorySales = $this->getSalesByCategory();
        
        // Build report data structure
        $report = [
            'salesData' => $salesData,
            'topProducts' => $topProducts,
            'categorySales' => $categorySales,
            'period' => [
                'start' => $startDate ? $startDate : date('Y-m-d', strtotime('-30 days')),
                'end' => $endDate ? $endDate : date('Y-m-d')
            ],
            'summary' => [
                'totalRevenue' => $this->getTotalRevenue(),
                'averageOrderValue' => $this->getAverageOrderValue()
            ]
        ];
        
        return $report;
    }
}
?>
