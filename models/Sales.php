<?php
require_once 'Database.php';

class Sales {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getDailySales() {
        try {
            $query = "SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total 
                      FROM orderitems oi 
                      JOIN orders o ON oi.order_id = o.order_id 
                      WHERE DATE(o.order_date) = CURDATE()";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting daily sales: " . $e->getMessage());
            return 0;
        }
    }

    public function getWeeklySales() {
        try {
            $query = "SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total 
                      FROM orderitems oi 
                      JOIN orders o ON oi.order_id = o.order_id 
                      WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting weekly sales: " . $e->getMessage());
            return 0;
        }
    }

    public function getMonthlySales() {
        try {
            $query = "SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total 
                      FROM orderitems oi 
                      JOIN orders o ON oi.order_id = o.order_id 
                      WHERE MONTH(o.order_date) = MONTH(CURRENT_DATE()) 
                      AND YEAR(o.order_date) = YEAR(CURRENT_DATE())";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting monthly sales: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRevenue() {
        try {
            $query = "SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total 
                      FROM orderitems oi 
                      JOIN orders o ON oi.order_id = o.order_id 
                      WHERE o.order_status = 'completed'";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting total revenue: " . $e->getMessage());
            return 0;
        }
    }

    public function getAverageOrderValue() {
        try {
            $query = "SELECT COALESCE(AVG(order_total), 0) as average FROM (
                        SELECT o.order_id, SUM(oi.price * oi.quantity) as order_total 
                        FROM orders o 
                        JOIN orderitems oi ON o.order_id = oi.order_id 
                        GROUP BY o.order_id
                    ) as order_totals";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['average'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting average order value: " . $e->getMessage());
            return 0;
        }
    }

    public function getSalesByCategory() {
        try {
            $query = "SELECT p.category, COALESCE(SUM(oi.price * oi.quantity), 0) as total 
                      FROM products p 
                      JOIN orderitems oi ON p.product_id = oi.product_id 
                      JOIN orders o ON oi.order_id = o.order_id 
                      GROUP BY p.category";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log("Error getting sales by category: " . $e->getMessage());
            return [];
        }
    }

    public function getTopProducts($limit = 5) {
        try {
            $query = "SELECT p.name, p.category, 
                             COUNT(oi.product_id) as units_sold,
                             SUM(oi.price * oi.quantity) as revenue,
                             CASE 
                                WHEN SUM(oi.quantity) > (SELECT AVG(quantity) FROM orderitems) THEN 1 
                                ELSE -1 
                             END as trend
                      FROM products p 
                      JOIN orderitems oi ON p.product_id = oi.product_id 
                      JOIN orders o ON oi.order_id = o.order_id 
                      GROUP BY p.product_id, p.name, p.category 
                      ORDER BY revenue DESC 
                      LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log("Error getting top products: " . $e->getMessage());
            return [];
        }
    }

    public function getRevenueData() {
        try {
            $query = "SELECT DATE(o.order_date) as date, 
                             SUM(oi.price * oi.quantity) as amount 
                      FROM orders o 
                      JOIN orderitems oi ON o.order_id = oi.order_id 
                      WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                      GROUP BY DATE(o.order_date) 
                      ORDER BY date";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log("Error getting revenue data: " . $e->getMessage());
            return [];
        }
    }

    public function getSalesReport($startDate, $endDate) {
        try {
            $query = "SELECT 
                        o.order_date,
                        o.order_id,
                        p.name as product_name,
                        pc.category_name as category,
                        oi.quantity,
                        oi.price as unit_price,
                        (oi.quantity * oi.price) as total_amount,
                        COALESCE(pay.payment_method, 'N/A') as payment_method,
                        o.order_status
                    FROM orders o
                    JOIN orderitems oi ON o.order_id = oi.order_id
                    JOIN products p ON oi.product_id = p.product_id
                    LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
                    LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id
                    LEFT JOIN payments pay ON o.order_id = pay.order_id
                    WHERE DATE(o.order_date) BETWEEN :start_date AND :end_date
                    ORDER BY o.order_date DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSalesReport: " . $e->getMessage());
            throw $e;
        }
    }
}
