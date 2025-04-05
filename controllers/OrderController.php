<?php
// filepath: /c:/xampp/htdocs/capstone/controllers/OrderController.php
require_once __DIR__ . '/../models/Database.php';

class OrderController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get all orders (for organization head to manage consumer orders)
    public function getOrders() {
        try {
            $query = "SELECT o.order_id, o.order_date, o.order_status as status, 
                      CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                      u.user_id as consumer_id
                      FROM orders o
                      JOIN users u ON o.consumer_id = u.user_id
                      ORDER BY o.order_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total amount for each order
            foreach ($orders as &$order) {
                $order['total_amount'] = $this->calculateOrderTotal($order['order_id']);
            }
            
            return $orders;
        } catch (PDOException $e) {
            error_log("Database error in getOrders: " . $e->getMessage());
            return [];
        }
    }
    
    // Calculate order total
    private function calculateOrderTotal($order_id) {
        try {
            $query = "SELECT SUM(quantity * price) as total 
                      FROM orderitems 
                      WHERE order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in calculateOrderTotal: " . $e->getMessage());
            return 0;
        }
    }

    // Get details for a specific order
    public function getOrderById($order_id) {
        try {
            $query = "SELECT o.order_id, o.order_date, o.order_status as status,
                      CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                      u.email, u.contact_number, u.address
                      FROM orders o
                      JOIN users u ON o.consumer_id = u.user_id
                      WHERE o.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($order) {
                $order['total_amount'] = $this->calculateOrderTotal($order_id);
                
                // Get shipping info if available
                $shippingInfo = $this->getShippingInfo($order_id);
                if ($shippingInfo) {
                    $order['shipping_address'] = $shippingInfo['address'];
                    $order['shipping_status'] = $shippingInfo['shipping_status'];
                }
                
                // Get payment info if available
                $paymentInfo = $this->getPaymentInfo($order_id);
                if ($paymentInfo) {
                    $order['payment_method'] = $paymentInfo['payment_method'];
                    $order['payment_status'] = $paymentInfo['payment_status'];
                }
            }
            
            return $order;
        } catch (PDOException $e) {
            error_log("Database error in getOrderById: " . $e->getMessage());
            return null;
        }
    }
    
    // Get shipping info for an order
    private function getShippingInfo($order_id) {
        try {
            $query = "SELECT * FROM shippinginfo WHERE order_id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getShippingInfo: " . $e->getMessage());
            return null;
        }
    }
    
    // Get payment info for an order
    private function getPaymentInfo($order_id) {
        try {
            $query = "SELECT * FROM payments WHERE order_id = :order_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getPaymentInfo: " . $e->getMessage());
            return null;
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
            error_log("Database error in getOrderItems: " . $e->getMessage());
            return [];
        }
    }

    // Update order status
    public function updateOrderStatus($order_id, $new_status) {
        try {
            // Match the database enum values exactly - these are the only allowed values
            $validStatuses = ['pending', 'completed', 'canceled'];
            
            // Map 'cancelled' to 'canceled' for compatibility
            if ($new_status === 'cancelled') {
                $new_status = 'canceled';
            }
            
            // Verify status is valid
            if (!in_array($new_status, $validStatuses)) {
                error_log("Invalid status: $new_status. Valid statuses are: " . implode(', ', $validStatuses));
                throw new Exception("Invalid status provided: $new_status");
            }

            $query = "UPDATE orders SET order_status = :status 
                      WHERE order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Log the update attempt with detailed info for debugging
            error_log("Order status update: Order ID $order_id, New Status: $new_status, Result: " . 
                      ($result ? "Success (Rows affected: " . $stmt->rowCount() . ")" : "Failed"));
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in updateOrderStatus: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error in updateOrderStatus: " . $e->getMessage());
            throw $e;
        }
    }
}