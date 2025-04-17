<?php
// Check if the Database class is already defined before including
if (!class_exists('Database')) {
    require_once __DIR__ . '/../config/Database.php';
}

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
        
        // Create connection
        $conn = new mysqli($host, $username, $password, $db_name);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    }

    public function getDailySales() {
        $today = date('Y-m-d');
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE DATE(o.order_date) = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ? $row['total'] : 0;
    }

    public function getWeeklySales() {
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE o.order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ? $row['total'] : 0;
    }

    public function getMonthlySales() {
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi 
                 JOIN orders o ON oi.order_id = o.order_id 
                 WHERE o.order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ? $row['total'] : 0;
    }

    public function getTotalRevenue() {
        $query = "SELECT SUM(oi.price * oi.quantity) AS total 
                 FROM orderitems oi";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ? $row['total'] : 0;
    }

    public function getAverageOrderValue() {
        $query = "SELECT AVG(order_total) as avg_order
                 FROM (
                     SELECT o.order_id, SUM(oi.price * oi.quantity) as order_total
                     FROM orders o
                     JOIN orderitems oi ON o.order_id = oi.order_id
                     GROUP BY o.order_id
                 ) as order_totals";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['avg_order'] ? $row['avg_order'] : 0;
    }

    public function getSalesByCategory() {
        $query = "SELECT pc.category_name as category, SUM(oi.price * oi.quantity) as total
                 FROM orderitems oi
                 JOIN products p ON oi.product_id = p.product_id
                 JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                 JOIN productcategories pc ON pcm.category_id = pc.category_id
                 GROUP BY pc.category_name
                 ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
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
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }

    public function getRevenueData($startDate = null, $endDate = null) {
        // Default to last 30 days if no dates provided
        $startDate = $startDate ? $startDate : date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ? $endDate : date('Y-m-d');
        
        $query = "SELECT DATE(o.order_date) as date, 
                 SUM(oi.price * oi.quantity) as amount
                 FROM orders o
                 JOIN orderitems oi ON o.order_id = oi.order_id
                 WHERE o.order_date BETWEEN ? AND ?
                 GROUP BY DATE(o.order_date)
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $revenue = [];
        while ($row = $result->fetch_assoc()) {
            $revenue[] = $row;
        }
        
        return $revenue;
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
