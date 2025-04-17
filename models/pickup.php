<?php
// Include Database class
require_once 'Database.php';

class Pickup {
    private $conn;
    private $table = 'pickups';

    // Constructor to initialize DB connection
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get all pickups with order details
    public function getPickupsWithOrderDetails() {
        // SQL query to fetch pickup details with related order info
        $query = "SELECT 
                    p.pickup_id, 
                    p.order_id, 
                    p.pickup_status, 
                    p.pickup_date, 
                    p.pickup_location, 
                    p.assigned_to, 
                    p.pickup_notes, 
                    o.order_date, 
                    u.first_name AS consumer_name
                  FROM 
                    " . $this->table . " p
                  JOIN orders o ON p.order_id = o.order_id
                  JOIN users u ON o.consumer_id = u.user_id
                  ORDER BY p.pickup_date DESC";

        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Return the results as an associative array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get pickups scheduled for today
    public function getTodayPickups() {
        // SQL query to fetch today's pickup details with related order info
        $query = "SELECT 
                    p.pickup_id, 
                    p.order_id, 
                    p.pickup_status, 
                    p.pickup_date, 
                    p.pickup_location, 
                    p.contact_person, 
                    p.pickup_notes, 
                    o.order_date, 
                    u.first_name AS consumer_name
                  FROM 
                    " . $this->table . " p
                  JOIN orders o ON p.order_id = o.order_id
                  JOIN users u ON o.consumer_id = u.user_id
                  WHERE DATE(p.pickup_date) = CURDATE()
                  ORDER BY p.pickup_date ASC";

        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Return the results as an associative array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update pickup status
    public function updatePickupStatus($order_id, $new_status) {
        // SQL query to update the pickup status
        $query = "UPDATE " . $this->table . " 
                  SET pickup_status = :new_status
                  WHERE order_id = :order_id";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind the parameters
        $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);

        // Execute the query and return success or failure
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get total pickup count
    public function getPickupCountTotal() {
        try {
            $query = "SELECT COUNT(*) FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total pickup count: " . $e->getMessage());
            return 0;
        }
    }

    // Get pickup count by status
    public function getPickupCountByStatus($status) {
        try {
            $query = "SELECT COUNT(*) FROM " . $this->table . " WHERE pickup_status = :status";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting pickup count by status: " . $e->getMessage());
            return 0;
        }
    }

    // Get pickup status distribution for charting
    public function getPickupStatusDistribution() {
        try {
            $query = "SELECT pickup_status, COUNT(*) as count FROM " . $this->table . " GROUP BY pickup_status";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting pickup status distribution: " . $e->getMessage());
            return [];
        }
    }
}
?>
