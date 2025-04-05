<?php
require_once 'Database.php';

class DriverModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Create or update driver details
     * 
     * @param array $data Driver details data
     * @return bool Success or failure
     */
    public function saveDriverDetails($data) {
        try {
            // Check if driver details already exist
            $checkQuery = "SELECT detail_id FROM driver_details WHERE user_id = :user_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Update existing record
                $detailId = $checkStmt->fetch(PDO::FETCH_ASSOC)['detail_id'];
                
                $query = "UPDATE driver_details SET 
                          vehicle_type = :vehicle_type, 
                          license_number = :license_number,
                          vehicle_plate = :vehicle_plate,
                          availability_status = :availability_status,
                          max_load_capacity = :max_load_capacity,
                          current_location = :current_location,
                          contact_number = :contact_number
                          WHERE detail_id = :detail_id";
                          
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':detail_id', $detailId, PDO::PARAM_INT);
            } else {
                // Verify user exists before inserting
                $userCheck = "SELECT user_id FROM users WHERE user_id = :user_id";
                $userStmt = $this->conn->prepare($userCheck);
                $userStmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
                $userStmt->execute();
                
                if ($userStmt->rowCount() === 0) {
                    throw new PDOException("User does not exist");
                }
                
                // Insert new record
                $query = "INSERT INTO driver_details 
                          (user_id, vehicle_type, license_number, vehicle_plate, 
                           availability_status, max_load_capacity, current_location, contact_number) 
                          VALUES 
                          (:user_id, :vehicle_type, :license_number, :vehicle_plate, 
                           :availability_status, :max_load_capacity, :current_location, :contact_number)";
                           
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            }
            
            // Bind common parameters
            $stmt->bindParam(':vehicle_type', $data['vehicle_type'], PDO::PARAM_STR);
            $stmt->bindParam(':license_number', $data['license_number'], PDO::PARAM_STR);
            $stmt->bindParam(':vehicle_plate', $data['vehicle_plate'], PDO::PARAM_STR);
            $stmt->bindParam(':availability_status', $data['availability_status'], PDO::PARAM_STR);
            $stmt->bindParam(':max_load_capacity', $data['max_load_capacity'], PDO::PARAM_STR);
            $stmt->bindParam(':current_location', $data['current_location'], PDO::PARAM_STR);
            $stmt->bindParam(':contact_number', $data['contact_number'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in DriverModel::saveDriverDetails: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get driver details by user ID
     * 
     * @param int $userId User ID
     * @return array|false Driver details or false if not found
     */
    public function getDriverDetailsByUserId($userId) {
        $query = "SELECT d.*, u.username, u.first_name, u.last_name, u.email 
                 FROM driver_details d
                 JOIN users u ON d.user_id = u.user_id
                 WHERE d.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }
    
    /**
     * Get all available drivers
     * 
     * @return array List of available drivers
     */
    public function getAvailableDrivers() {
        $query = "SELECT d.*, u.username, u.first_name, u.last_name 
                 FROM driver_details d
                 JOIN users u ON d.user_id = u.user_id
                 WHERE d.availability_status = 'available'
                 ORDER BY d.rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update driver availability status
     * 
     * @param int $userId User ID
     * @param string $status New status
     * @return bool Success or failure
     */
    public function updateDriverStatus($driverId, $status) {
        if (!in_array($status, ['available', 'busy', 'offline'])) {
            return false;
        }
        
        try {
            $query = "UPDATE driver_details 
                      SET availability_status = :status 
                      WHERE user_id = :driver_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':driver_id', $driverId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("DriverModel::updateDriverStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update driver's completed pickups and ratings
     * 
     * @param int $userId User ID
     * @param int $newRating New rating to be averaged with existing
     * @return bool Success or failure
     */
    public function updateDriverStats($userId, $newRating = null) {
        // Increment completed pickups
        $query = "UPDATE driver_details 
                 SET completed_pickups = completed_pickups + 1";
        
        // If rating provided, update the average rating
        if ($newRating !== null) {
            $query .= ", rating = (rating * completed_pickups + :new_rating) / (completed_pickups + 1)";
        }
        
        $query .= " WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($newRating !== null) {
            $stmt->bindParam(':new_rating', $newRating, PDO::PARAM_STR);
        }
        
        return $stmt->execute();
    }

    /**
     * Get all drivers
     * 
     * @return array List of all drivers
     */
    public function getAllDrivers() {
        $query = "SELECT d.*, u.username, u.first_name, u.last_name, u.email,
                 (SELECT COUNT(*) FROM pickups 
                  WHERE assigned_to = d.user_id 
                  AND pickup_status IN ('assigned', 'in_transit')) as active_pickups,
                 COALESCE(d.completed_pickups, 0) as completed_pickups,
                 COALESCE(d.rating, 0) as rating
                 FROM users u
                 JOIN driver_details d ON u.user_id = d.user_id
                 WHERE u.role_id = 6
                 ORDER BY d.availability_status, d.rating DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add debug logging
            error_log("Found " . count($drivers) . " drivers");
            
            return $drivers;
        } catch (PDOException $e) {
            error_log("Error fetching drivers: " . $e->getMessage());
            return [];
        }
    }

    public function getAllDriversWithAssignments() {
        try {
            // Fetch all drivers with basic info
            $query = "SELECT d.*, u.username, u.first_name, u.last_name, u.email,
                     COALESCE(d.availability_status, 'offline') as availability_status
                     FROM users u
                     JOIN driver_details d ON u.user_id = d.user_id
                     WHERE u.role_id = 6
                     ORDER BY d.availability_status";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch active assignments for each driver
            foreach ($drivers as &$driver) {
                // Convert driver ID to string for consistent comparison with assigned_to field
                $driverId = $driver['user_id'];
                error_log("Processing driver #" . $driverId . " (type: " . gettype($driverId) . ")");
                
                $assignmentQuery = "SELECT 
                    p.pickup_id, 
                    p.pickup_status, 
                    p.pickup_date, 
                    p.pickup_location, 
                    o.order_id,
                    c.username as customer_name,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name
                    FROM pickups p 
                    JOIN orders o ON p.order_id = o.order_id
                    JOIN users c ON o.consumer_id = c.user_id
                    WHERE p.assigned_to = :driver_id 
                    AND p.pickup_status IN ('assigned', 'in_transit', 'pending')
                    ORDER BY p.pickup_date ASC";

                $assignmentStmt = $this->conn->prepare($assignmentQuery);
                $assignmentStmt->bindParam(':driver_id', $driverId);
                $assignmentStmt->execute();
                $assignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Driver #{$driverId} found " . count($assignments) . " assignments");
                
                $driver['active_assignments'] = $assignments;
                $driver['active_pickups'] = count($assignments);
            }
            
            return $drivers;
        } catch (PDOException $e) {
            error_log("Error fetching drivers with assignments: " . $e->getMessage());
            return [];
        }
    }

    // Get driver by ID with assignments
    public function getDriverById($driverId) {
        try {
            $query = "SELECT d.*, u.username, u.first_name, u.last_name, u.email, u.contact_number, u.address
                     FROM driver_details d
                     JOIN users u ON d.user_id = u.user_id
                     WHERE d.user_id = :driver_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':driver_id', $driverId);
            $stmt->execute();
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($driver) {
                // Get driver's active assignments - fixed the parameter binding
                $assignmentQuery = "SELECT p.pickup_id, p.pickup_status, p.pickup_date, p.pickup_location, 
                                   c.first_name as customer_first_name, c.last_name as customer_last_name
                                   FROM pickups p
                                   JOIN orders o ON p.order_id = o.order_id
                                   JOIN users c ON o.consumer_id = c.user_id
                                   WHERE p.assigned_to = :driver_id 
                                   AND p.pickup_status IN ('assigned', 'in_transit', 'pending')";
                $assignmentStmt = $this->conn->prepare($assignmentQuery);
                $assignmentStmt->bindParam(':driver_id', $driverId);
                $assignmentStmt->execute();
                $driver['active_assignments'] = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get driver's completed pickups history
                $historyQuery = "SELECT p.pickup_id, p.pickup_status, p.pickup_date, p.pickup_location, 
                               c.first_name as customer_first_name, c.last_name as customer_last_name
                               FROM pickups p
                               JOIN orders o ON p.order_id = o.order_id
                               JOIN users c ON o.consumer_id = c.user_id
                               WHERE p.assigned_to = :driver_id 
                               AND p.pickup_status IN ('completed')
                               ORDER BY p.pickup_date DESC LIMIT 10";
                $historyStmt = $this->conn->prepare($historyQuery);
                $historyStmt->bindParam(':driver_id', $driverId);
                $historyStmt->execute();
                $driver['completed_pickups'] = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $driver;
        } catch (PDOException $e) {
            error_log("DriverModel::getDriverById Error: " . $e->getMessage());
            return null;
        }
    }

    // Debug method to check all assigned pickups
    public function debugAllAssignedPickups() {
        try {
            $query = "SELECT 
                p.pickup_id, 
                p.assigned_to, 
                p.pickup_status,
                u.username,
                u.first_name,
                u.last_name
                FROM pickups p
                LEFT JOIN users u ON p.assigned_to = u.user_id
                WHERE p.assigned_to IS NOT NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error debugging assigned pickups: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update driver's current location
     * 
     * @param int $driverId Driver user ID
     * @param string $location New location
     * @return bool Success or failure
     */
    public function updateDriverLocation($driverId, $location) {
        try {
            $query = "UPDATE driver_details 
                      SET current_location = :location 
                      WHERE user_id = :driver_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating driver location: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get driver's performance metrics
     * 
     * @param int $driverId Driver user ID
     * @return array Performance metrics
     */
    public function getDriverMetrics($driverId) {
        try {
            $query = "SELECT 
                      COALESCE(completed_pickups, 0) as completed_pickups,
                      COALESCE(rating, 0) as rating
                      FROM driver_details
                      WHERE user_id = :driver_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting driver metrics: " . $e->getMessage());
            return [
                'completed_pickups' => 0,
                'rating' => 0
            ];
        }
    }

    /**
     * Get drivers filtered by capacity
     * 
     * @param float $minCapacity Minimum load capacity required
     * @return array List of drivers with sufficient capacity
     */
    public function getDriversByCapacity($minCapacity) {
        try {
            $query = "SELECT d.*, u.username, u.first_name, u.last_name, u.email 
                     FROM driver_details d
                     JOIN users u ON d.user_id = u.user_id
                     WHERE d.max_load_capacity >= :min_capacity
                     AND d.availability_status = 'available'
                     ORDER BY d.rating DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':min_capacity', $minCapacity, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching drivers by capacity: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign pickup to a driver
     * 
     * @param int $pickupId Pickup ID
     * @param int $driverId Driver user ID
     * @return bool Success or failure
     */
    public function assignPickupToDriver($pickupId, $driverId) {
        try {
            // First check if driver exists and is available
            $driverCheck = "SELECT availability_status FROM driver_details WHERE user_id = :driver_id";
            $checkStmt = $this->conn->prepare($driverCheck);
            $checkStmt->bindParam(':driver_id', $driverId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                error_log("Driver #$driverId does not exist");
                return false;
            }
            
            $status = $checkStmt->fetch(PDO::FETCH_ASSOC)['availability_status'];
            if ($status !== 'available') {
                error_log("Driver #$driverId is not available (status: $status)");
                return false;
            }
            
            // Assign pickup to driver
            $query = "UPDATE pickups 
                      SET assigned_to = :driver_id, 
                          pickup_status = 'assigned'
                      WHERE pickup_id = :pickup_id";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':driver_id', $driverId);
            $stmt->bindParam(':pickup_id', $pickupId);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning pickup to driver: " . $e->getMessage());
            return false;
        }
    }

    public function deleteDriverDetails($userId) {
        try {
            $query = "DELETE FROM driver_details WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $success = $stmt->execute();
            
            if (!$success) {
                error_log("Failed to delete driver details for user ID: $userId");
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Error deleting driver details: " . $e->getMessage());
            throw new Exception("Failed to delete driver details: " . $e->getMessage());
        }
    }
}
