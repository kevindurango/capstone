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
}
?>
