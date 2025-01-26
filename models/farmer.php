<?php
require_once 'Database.php';

class Farmer {
    private $conn;

    // Constructor: Initialize the database connection
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /* ==================== DASHBOARD METHODS ==================== */

    // Get the total count of products for a specific farmer
    public function getProductCount($farmer_id) {
        $sql = "SELECT COUNT(*) as total FROM products WHERE farmer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$farmer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Get the total number of orders by status for a farmer
    public function getOrderCountByStatus($status, $farmer_id) {
        $sql = "SELECT COUNT(*) as total 
                FROM orders o 
                JOIN orderitems oi ON o.order_id = oi.order_id 
                JOIN products p ON oi.product_id = p.product_id
                WHERE o.order_status = ? AND p.farmer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$status, $farmer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Get the total count of feedback for a specific farmer
    public function getFeedbackCount($farmer_id) {
        $sql = "SELECT COUNT(*) as total 
                FROM feedback f 
                JOIN products p ON f.product_id = p.product_id 
                WHERE p.farmer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$farmer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /* ==================== PRODUCT METHODS ==================== */

    // Fetch all products for a specific farmer
    public function getProducts($farmer_id)
    {
        try {
            $query = "SELECT name, description, price, status, image FROM products WHERE farmer_id = :farmer_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching products: " . $e->getMessage());
        }
    }
    
    /* ==================== ORDER METHODS ==================== */

    // Fetch all orders for a farmer (joins to get customer details and total amount)
    public function getOrdersByFarmer($farmer_id) {
        try {
            $query = "SELECT o.order_id, u.username AS customer_name, 
                             SUM(oi.quantity * oi.price) AS total_amount, 
                             o.order_status AS status
                      FROM orders o
                      JOIN orderitems oi ON o.order_id = oi.order_id
                      JOIN products p ON oi.product_id = p.product_id
                      JOIN users u ON o.consumer_id = u.user_id
                      WHERE p.farmer_id = :farmer_id
                      GROUP BY o.order_id, u.username, o.order_status";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching orders: " . $e->getMessage());
        }
    }

    // Update the status of an order
    public function updateOrderStatus($order_id, $status) {
        try {
            $query = "UPDATE orders SET order_status = :status WHERE order_id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            die("Error updating order status: " . $e->getMessage());
        }
    }

    public function getFeedbackByFarmer($farmer_id)
{
    $sql = "SELECT f.feedback_text, f.rating, f.created_at, p.name AS product_name
            FROM feedback f
            JOIN products p ON f.product_id = p.product_id
            WHERE p.farmer_id = :farmer_id
            ORDER BY f.created_at DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function addProduct($name, $description, $price, $status, $image_path)
{
    try {
        $query = "INSERT INTO products (name, description, price, status, image, farmer_id, created_at) 
                  VALUES (:name, :description, :price, :status, :image, :farmer_id, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':image', $image_path);
        $stmt->bindParam(':farmer_id', $farmer_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        die("Error adding product: " . $e->getMessage());
    }
}

    /* ==================== CLEANUP ==================== */

    // Optional: Close the database connection
    public function close() {
        $this->conn = null;
    }

}
?>
