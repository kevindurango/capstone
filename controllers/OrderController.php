<?php
// filepath: /c:/xampp/htdocs/capstone/controllers/OrderController.php
require_once __DIR__ . '/../models/Database.php';

class OrderController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get all orders for an organization
    public function getOrdersByOrganization($organization_id) {
        try {
            $query = "SELECT o.order_id, o.order_date, o.total_amount, o.status, 
                      CONCAT(u.first_name, ' ', u.last_name) as customer_name
                      FROM orders o
                      JOIN users u ON o.consumer_id = u.user_id
                      JOIN orderitems oi ON o.order_id = oi.order_id
                      JOIN products p ON oi.product_id = p.product_id
                      JOIN users f ON p.farmer_id = f.user_id
                      JOIN user_organizations uo ON f.user_id = uo.user_id
                      WHERE uo.organization_id = :organization_id
                      GROUP BY o.order_id
                      ORDER BY o.order_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':organization_id', $organization_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to get orders for organization");
        }
    }

    // Get detailed information for a specific order
    public function getOrderDetails($order_id) {
        try {
            $query = "SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_details,
                      CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                      u.email as customer_email, u.contact_number as customer_phone
                      FROM orders o
                      JOIN users u ON o.consumer_id = u.user_id
                      WHERE o.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $orderDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($orderDetails) {
                $orderDetails['items'] = $this->getOrderItems($order_id);
            }
            return $orderDetails;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to get order details");
        }
    }

    // Get items for a specific order
    public function getOrderItems($order_id) {
        try {
            $query = "SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price,
                      p.name as product_name
                      FROM orderitems oi
                      JOIN products p ON oi.product_id = p.product_id
                      WHERE oi.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to get order items");
        }
    }

    // Update order status
    public function updateOrderStatus($order_id, $new_status) {
        try {
            $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
            if (!in_array($new_status, $validStatuses)) {
                throw new Exception("Invalid status provided");
            }

            $query = "UPDATE orders SET order_status = :status, updated_at = NOW() 
                      WHERE order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Failed to update order status");
        }
    }
}