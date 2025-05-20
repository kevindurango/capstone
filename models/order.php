<?php

class Order {
    private $pdo;  // PDO database connection object
    private $lastError = '';  // Add this property to store the last error message

    public function __construct() {
        // Establish database connection in the constructor
        require_once 'Database.php'; // Adjust the path if necessary
        $db = new Database();
        $this->pdo = $db->connect();  // Get the PDO connection
    }

    /**
     * Retrieves all orders from the database with pickup details.
     * @return array An array of associative arrays, each representing an order with pickup details.
     */
    public function getOrdersWithPickupDetails(): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, u.username, u.email, u.contact_number,
                       p.pickup_id, p.pickup_status, p.pickup_date, 
                       p.pickup_location, p.pickup_notes, p.contact_person,
                       pm.payment_status, pm.amount
                FROM orders o
                JOIN users u ON o.consumer_id = u.user_id
                LEFT JOIN pickups p ON o.order_id = p.order_id
                LEFT JOIN payments pm ON o.order_id = pm.order_id
                ORDER BY o.order_date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getOrdersWithPickupDetails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single order from the database by order ID, including order items.
     *
     * @param int $order_id The ID of the order to retrieve.
     * @return array|false An associative array representing the order, or false if not found.
     */
    public function getOrderById(int $order_id) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM orders
            WHERE order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // If the order is found, get the order items
            $order['items'] = $this->getOrderItemsByOrderId($order_id);
        }

        return $order ?: false;  // Return false if order is not found
    }

    /**
     * Retrieves order items for a specific order ID.
     *
     * @param int $order_id The ID of the order.
     * @return array An array of associative arrays, each representing an order item.
     */
    public function getOrderItemsByOrderId(int $order_id): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT oi.*, p.name as product_name, p.price as unit_price,
                       p.unit_type, p.image as product_image
                FROM orderitems oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting order items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates the status of an existing order.
     *
     * @param int $order_id The ID of the order to update.
     * @param string $new_status The new status to set for the order.
     * @return bool True on success, false on failure.
     */    
    public function updateOrderStatus(int $order_id, string $new_status): bool {
        // Validate the status value based on the database enum values
        $allowedStatuses = ['pending', 'processing', 'ready', 'completed', 'canceled'];

        if (!in_array($new_status, $allowedStatuses)) {
            $this->lastError = "Invalid order status: $new_status. Allowed values are: " . implode(", ", $allowedStatuses);
            error_log($this->lastError);
            return false;
        }

        try {
            // Debug the query and values
            error_log("Executing SQL: UPDATE orders SET order_status = '$new_status' WHERE order_id = $order_id");

            $stmt = $this->pdo->prepare("UPDATE orders SET order_status = :new_status WHERE order_id = :order_id");
            $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $result = $stmt->execute();

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = "Database error: " . ($errorInfo[2] ?? 'Unknown error');
                error_log($this->lastError);
                return false;
            }

            // Check if any rows were affected
            if ($stmt->rowCount() === 0) {
                $this->lastError = "Order not found or status already set to '$new_status'";
                error_log($this->lastError);
                // We'll still return true if the order exists but status was already set
                // Let's verify order exists
                $checkStmt = $this->pdo->prepare("SELECT 1 FROM orders WHERE order_id = :id");
                $checkStmt->bindParam(':id', $order_id, PDO::PARAM_INT);
                $checkStmt->execute();
                if ($checkStmt->fetchColumn() === false) {
                    // Order doesn't exist
                    $this->lastError = "Order ID $order_id not found";
                    return false;
                }
            }

            return true;
        } catch (PDOException $e) {
            $this->lastError = "Database error: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    /**
     * Returns the last error that occurred.
     * @return string The last error message.
     */
    public function getLastError(): string {
        return $this->lastError;
    }

    /**
     * Updates the pickup status of a specific order.
     *
     * @param int $order_id The ID of the order.
     * @param string $pickup_status The new pickup status to set.
     * @return bool True on success, false on failure.
     */
    public function updatePickUpStatus(int $order_id, string $pickup_status): bool {
        // Validate pickup status values before update
        $allowedStatuses = ['pending', 'scheduled', 'in_transit', 'picked_up', 'completed', 'cancelled'];
        if (!in_array($pickup_status, $allowedStatuses)) {
            error_log("Invalid pickup status provided for order ID $order_id: " . htmlspecialchars($pickup_status));
            return false;
        }

        try {
            $sql = "UPDATE pickups SET pickup_status = :pickup_status WHERE order_id = :order_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':pickup_status', $pickup_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error updating pickup status for order ID $order_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes an order from the database.
     *
     * @param int $order_id The ID of the order to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteOrder(int $order_id): bool {
        try {
            // Check if the order exists before attempting to delete
            if (!$this->getOrderById($order_id)) {
                error_log("Attempted to delete non-existent order ID $order_id.");
                return false;
            }

            $stmt = $this->pdo->prepare("
                DELETE FROM orders
                WHERE order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error deleting order ID $order_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new order in the database.
     *
     * @param int $consumer_id The ID of the consumer placing the order.
     * @param string $order_status The initial status of the order (e.g., 'pending').
     * @param string $order_date The date the order was placed.
     * @param string|null $pickup_details Optional details about order pickup.
     * @return int|false The ID of the newly created order, or false on failure.
     */
    public function createOrder(int $consumer_id, string $order_status, string $order_date, ?string $pickup_details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (consumer_id, order_status, order_date, pickup_details)
                VALUES (:consumer_id, :order_status, :order_date, :pickup_details)
            ");
            $stmt->bindParam(':consumer_id', $consumer_id, PDO::PARAM_INT);
            $stmt->bindParam(':order_status', $order_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_date', $order_date, PDO::PARAM_STR);
            $stmt->bindParam(':pickup_details', $pickup_details, PDO::PARAM_STR);
            $stmt->execute();

            return $this->pdo->lastInsertId();  // Return last inserted ID
        } catch (PDOException $e) {
            error_log("Database error creating order: " . $e->getMessage());
            return false;  // Return false if creation fails
        }
    }

    /**
     * Get the total number of orders for reporting purposes.
     * @return int The total number of orders.
     */
    public function getTotalOrderCount(): int {
        try {
            $query = "SELECT COUNT(*) FROM orders";
            return $this->pdo->query($query)->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total order count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Gets the count of orders by a specific status (e.g., 'pending', 'completed').
     * @param string $status The status to filter orders by.
     * @return int The number of orders with the specified status.
     */
    public function getOrderCountByStatus(string $status): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_status = :status");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Method to fetch order details by order ID
     * @param int $order_id The ID of the order to fetch details for.
     * @return array|false An associative array representing the order details, or false if not found.
     */
    public function getOrderDetails(int $order_id) {
        try {
            $query = "SELECT o.*, u.username, u.email, u.contact_number,
                           p.pickup_id, p.pickup_status, p.pickup_date, 
                           p.pickup_location, p.pickup_notes, p.contact_person,
                           pm.payment_id, pm.payment_status, pm.amount, pm.payment_date,
                           pm.payment_method
                    FROM orders o
                    JOIN users u ON o.consumer_id = u.user_id
                    LEFT JOIN pickups p ON o.order_id = p.order_id
                    LEFT JOIN payments pm ON o.order_id = pm.order_id
                    WHERE o.order_id = :order_id";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($order) {
                // Get order items
                $order['items'] = $this->getOrderItemsByOrderId($order_id);
                // Calculate total
                $order['total'] = array_reduce($order['items'], function($carry, $item) {
                    return $carry + ($item['price'] * $item['quantity']);
                }, 0);
            }
            return $order;
        } catch (PDOException $e) {
            error_log("Error getting order details: " . $e->getMessage());
            return false;
        }
    }

    public function getOrdersByStatus($status) {
        try {
            error_log("Fetching orders with status: " . $status);
            
            $query = "SELECT o.*, 
                      u.username as customer_name, 
                      u.email,
                      u.first_name,
                      u.last_name,
                      (SELECT COUNT(*) FROM orderitems oi WHERE oi.order_id = o.order_id) as item_count,
                      (SELECT SUM(oi.quantity * oi.price) FROM orderitems oi WHERE oi.order_id = o.order_id) as total_amount
                      FROM orders o
                      LEFT JOIN users u ON o.consumer_id = u.user_id";

            $params = [];
            if ($status !== 'all') {
                $query .= " WHERE o.order_status = :status";
                $params[':status'] = $status;
            }

            $query .= " ORDER BY o.order_date DESC";

            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($results) . " orders");
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching orders: " . $e->getMessage());
            return [];
        }
    }

    public function getTodayOrderCount() {
        try {
            $query = "SELECT COUNT(*) FROM orders 
                     WHERE DATE(order_date) = CURDATE()";
            $stmt = $this->pdo->query($query);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting today's order count: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRevenue() {
        try {
            $query = "SELECT COALESCE(SUM(total_amount), 0) FROM orders 
                     WHERE order_status = 'completed'";
            $stmt = $this->pdo->query($query);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total revenue: " . $e->getMessage());
            return 0;
        }
    }

    public function getOrderWithItems($orderId) {
        try {
            // Get order details
            $query = "SELECT o.*, 
                            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                            u.email 
                     FROM orders o 
                     LEFT JOIN users u ON o.consumer_id = u.user_id 
                     WHERE o.order_id = :order_id";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return null;
            }

            // Get order items
            $itemsQuery = "SELECT oi.*, p.name as product_name 
                          FROM orderitems oi 
                          LEFT JOIN products p ON oi.product_id = p.product_id 
                          WHERE oi.order_id = :order_id";
            
            $stmt = $this->pdo->prepare($itemsQuery);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total amount
            $order['total_amount'] = array_reduce($order['items'], function($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);
            
            return $order;
        } catch (PDOException $e) {
            error_log("Error getting order details: " . $e->getMessage());
            return null;
        }
    }

    public function getPickupsByStatus($status) {
        try {
            $query = "SELECT p.*, o.order_date, u.username as consumer_name 
                     FROM pickups p
                     JOIN orders o ON p.order_id = o.order_id
                     JOIN users u ON o.consumer_id = u.user_id
                     WHERE p.pickup_status = :status
                     ORDER BY p.pickup_date DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pickups by status: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalPickupCount() {
        try {
            $query = "SELECT COUNT(*) FROM pickups";
            $stmt = $this->pdo->query($query);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total pickup count: " . $e->getMessage());
            return 0;
        }
    }

    public function getTodayPickupCount() {
        try {
            $query = "SELECT COUNT(*) FROM pickups 
                     WHERE DATE(pickup_date) = CURDATE()";
            $stmt = $this->pdo->query($query);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting today's pickup count: " . $e->getMessage());
            return 0;
        }
    }    public function getPendingPickupCount() {
        try {
            $query = "SELECT COUNT(*) FROM pickups 
                     WHERE pickup_status = 'pending'";
            $stmt = $this->pdo->query($query);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting pending pickup count: " . $e->getMessage());
            return 0;
        }
    }

    public function getPickupDetails($pickupId) {
        try {            $query = "SELECT p.*, 
                            o.order_date, 
                            o.order_status,
                            o.pickup_details,
                            CONCAT(u.first_name, ' ', u.last_name) as consumer_name,
                            u.email,
                            u.contact_number
                     FROM pickups p
                     JOIN orders o ON p.order_id = o.order_id
                     JOIN users u ON o.consumer_id = u.user_id
                     WHERE p.pickup_id = :pickup_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':pickup_id', $pickupId, PDO::PARAM_INT);
            $stmt->execute();
            
            $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pickup) {
                // Add any additional order items if needed
                $itemsQuery = "SELECT oi.*, p.name as product_name 
                             FROM orderitems oi 
                             JOIN products p ON oi.product_id = p.product_id 
                             WHERE oi.order_id = :order_id";
                             
                $stmt = $this->pdo->prepare($itemsQuery);
                $stmt->bindParam(':order_id', $pickup['order_id'], PDO::PARAM_INT);
                $stmt->execute();
                $pickup['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $pickup;
        } catch (PDOException $e) {
            error_log("Error getting pickup details: " . $e->getMessage());
            return null;
        }
    }    

    function validatePickupStatus($status) {
        $valid_statuses = ['pending', 'scheduled', 'in_transit', 'picked_up', 'completed', 'cancelled'];
        return in_array(strtolower($status), $valid_statuses);
    }

    /**
     * Get orders within a specific date range
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Orders within the date range
     */
    public function getOrdersByDateRange($startDate, $endDate) {
        try {
            // Add one day to end date to include orders from that day
            $adjustedEndDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
            
            $sql = "SELECT o.*, 
                    u.first_name, u.last_name, u.email, 
                    COUNT(oi.order_item_id) as item_count,
                    SUM(oi.price * oi.quantity) as total_amount
                    FROM orders o
                    LEFT JOIN users u ON o.consumer_id = u.user_id
                    LEFT JOIN orderitems oi ON o.order_id = oi.order_id
                    WHERE o.order_date BETWEEN :start_date AND :end_date
                    GROUP BY o.order_id
                    ORDER BY o.order_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $adjustedEndDate);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add customer name field for better display
            foreach ($orders as &$order) {
                $order['customer_name'] = trim($order['first_name'] . ' ' . $order['last_name']);
            }
            
            return $orders;
        } catch (PDOException $e) {
            error_log("Error getting orders by date range: " . $e->getMessage());
            return [];
        }
    }
}

?>