<?php

class Order {
    private $pdo;  // PDO database connection object

    public function __construct() {
        // Establish database connection in the constructor
        require_once '../../models/Database.php'; // Adjust the path if necessary
        $db = new Database();
        $this->pdo = $db->connect();  // Get the PDO connection
    }

    /**
     * Retrieves all orders from the database with pickup details.
     * @return array An array of associative arrays, each representing an order with pickup details.
     */
    public function getOrdersWithPickupDetails(): array {
        $stmt = $this->pdo->prepare("
            SELECT o.order_id, o.consumer_id, o.order_status, o.order_date, o.pickup_details,
                   u.username AS consumer_name,
                   COALESCE(p.pickup_status, 'pending') AS pickup_status
            FROM orders AS o
            JOIN users AS u ON o.consumer_id = u.user_id
            LEFT JOIN pickups AS p ON o.order_id = p.order_id
            WHERE o.pickup_details IS NOT NULL
            ORDER BY o.order_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}

?>