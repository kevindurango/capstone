<?php

class Order {
    private $pdo;  // PDO database connection object

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
                SELECT 
                    o.order_id, 
                    o.consumer_id, 
                    o.order_status, 
                    o.order_date, 
                    o.pickup_details,
                    u.username AS consumer_name,
                    p.pickup_id,
                    p.pickup_status,
                    p.pickup_location,
                    p.pickup_date,
                    p.assigned_to,
                    p.pickup_notes
                FROM orders AS o
                JOIN users AS u ON o.consumer_id = u.user_id
                LEFT JOIN pickups AS p ON o.order_id = p.order_id
                WHERE o.pickup_details IS NOT NULL
                ORDER BY o.order_date DESC
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add default values for null fields
            return array_map(function($row) {
                return array_merge([
                    'pickup_id' => null,
                    'pickup_location' => 'Not set',
                    'pickup_date' => date('Y-m-d H:i:s'), // Current date as default
                    'assigned_to' => 'Unassigned',
                    'pickup_notes' => 'No notes',
                    'pickup_status' => 'pending'
                ], $row);
            }, $results);

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
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM orderitems
            WHERE order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the status of an existing order.
     *
     * @param int $order_id The ID of the order to update.
     * @param string $new_status The new status to set for the order.
     * @return bool True on success, false on failure.
     */
    public function updateOrderStatus(int $order_id, string $new_status): bool {
        // Validate the order status to avoid invalid input
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'canceled'];

        if (!in_array($new_status, $allowedStatuses)) {
            error_log("Invalid order status provided for order ID $order_id: " . htmlspecialchars($new_status));
            return false;
        }

        try {
            // Debugging statements
            error_log("Updating order ID $order_id to status $new_status");

            $stmt = $this->pdo->prepare("
                UPDATE orders
                SET order_status = :new_status
                WHERE order_id = :order_id
            ");
            $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $result = $stmt->execute();

            // Debugging statement to check if the update was successful
            if ($result) {
                error_log("Order ID $order_id updated to status $new_status successfully.");
            } else {
                error_log("Failed to update order ID $order_id to status $new_status.");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Database error updating order status for order ID $order_id: " . $e->getMessage());
            return false;
        }
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
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
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
        $query = "SELECT o.order_id, u.username AS consumer_name, o.order_status, o.order_date, p.pickup_status, p.pickup_location, p.pickup_date, p.assigned_to, p.pickup_notes
                  FROM orders AS o
                  JOIN users AS u ON o.consumer_id = u.user_id
                  LEFT JOIN pickups AS p ON o.order_id = p.order_id
                  WHERE o.order_id = :order_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrdersByStatus($status) {
        try {
            $query = "SELECT 
                        o.order_id,
                        o.order_status as status,
                        o.order_date,
                        u.first_name,
                        u.last_name,
                        u.email,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                        COUNT(oi.order_item_id) as item_count,
                        COALESCE(SUM(oi.price * oi.quantity), 0) as total_amount
                    FROM orders o
                    LEFT JOIN users u ON o.consumer_id = u.user_id
                    LEFT JOIN orderitems oi ON o.order_id = oi.order_id
                    WHERE o.order_status = :status
                    GROUP BY o.order_id, o.order_status, o.order_date, u.first_name, u.last_name, u.email
                    ORDER BY o.order_date DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting orders by status: " . $e->getMessage());
            return [];
        }
    }

    public function getTodayOrderCount() {
        try {
            $query = "SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()";
            $stmt = $this->pdo->query($query);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting today's order count: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalRevenue() {
        try {
            $query = "SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total
                      FROM orderitems oi
                      JOIN orders o ON oi.order_id = o.order_id
                      WHERE o.order_status = 'completed'";
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
    }

    public function getAssignedPickupCount() {
        try {
            $query = "SELECT COUNT(*) FROM pickups 
                     WHERE pickup_status = 'assigned' 
                     AND assigned_to IS NOT NULL";
            $stmt = $this->pdo->query($query);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting assigned pickup count: " . $e->getMessage());
            return 0;
        }
    }

    public function getPickupDetails($pickupId) {
        try {
            $query = "SELECT p.*, 
                            o.order_date, 
                            o.order_status,
                            o.pickup_details,
                            CONCAT(u.first_name, ' ', u.last_name) as consumer_name,
                            u.email,
                            u.contact_number,
                            d.username as driver_name
                     FROM pickups p
                     JOIN orders o ON p.order_id = o.order_id
                     JOIN users u ON o.consumer_id = u.user_id
                     LEFT JOIN users d ON p.assigned_to = d.user_id
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

    public function assignPickup($pickupId, $driverName, $notes = '') {
        try {
            // Ensure pickup record exists
            $pickup = $this->getPickupDetails($pickupId);
            if (!$pickup || !isset($pickup['pickup_id'])) {
                $this->createPickupRecord($pickupId);
            }

            // Update pickup assignment with driver name
            $query = "UPDATE pickups 
                     SET assigned_to = :driver_name, 
                         pickup_status = 'assigned',
                         pickup_notes = :notes,
                         pickup_date = NOW() 
                     WHERE pickup_id = :pickup_id";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':driver_name', $driverName, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->bindParam(':pickup_id', $pickupId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning pickup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a list of available drivers (static list for now)
     */
    public function getAvailableDrivers() {
        return [
            ['id' => 'Driver 1', 'name' => 'John Driver'],
            ['id' => 'Driver 2', 'name' => 'Mary Driver'],
            ['id' => 'Driver 3', 'name' => 'Sam Driver']
        ];
    }

    function validatePickupStatus($status) {
        $valid_statuses = ['pending', 'assigned', 'in_transit', 'completed', 'cancelled'];
        return in_array(strtolower($status), $valid_statuses);
    }
}

?>