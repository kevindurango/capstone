<?php
require_once 'Database.php';

class Farmer {
    private $conn;

    // Constructor: Initialize the database connection
    public function __construct() {
        $database = new Database();
        try {
            $this->conn = $database->connect();
        } catch (PDOException $e) {
            throw new Exception("Error connecting to database: " . $e->getMessage());
        }
    }

    /* ==================== DASHBOARD METHODS ==================== */

    // Get the total count of products for a specific farmer
    public function getProductCount($farmer_id) {
        $sql = "SELECT COUNT(*) as total FROM products WHERE farmer_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            throw new Exception("Error fetching product count: " . $e->getMessage());
        }
    }

    // Get the total number of orders by status for a farmer
    public function getOrderCountByStatus($status, $farmer_id) {
        $sql = "SELECT COUNT(*) as total
                FROM orders o
                JOIN orderitems oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE o.order_status = :status AND p.farmer_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            throw new Exception("Error fetching order count by status: " . $e->getMessage());
        }
    }

    // Get the total count of feedback for a specific farmer
    public function getFeedbackCount($farmer_id) {
        $sql = "SELECT COUNT(*) as total
                FROM feedback f
                JOIN products p ON f.product_id = p.product_id
                WHERE p.farmer_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            throw new Exception("Error fetching feedback count: " . $e->getMessage());
        }
    }

    // Method to calculate the average feedback rating for a farmer's products
    public function getAverageFeedbackRating($farmer_id) {
        $sql = "SELECT AVG(f.rating) as avg_rating
                FROM feedback f
                JOIN products p ON f.product_id = p.product_id
                WHERE p.farmer_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? null; // Return null if no ratings
        } catch (PDOException $e) {
            throw new Exception("Error calculating average feedback rating: " . $e->getMessage());
        }
    }

    /* ==================== PRODUCT METHODS ==================== */

    // Fetch all products for a specific farmer
    public function getProducts($farmer_id) {
        try {
            $query = "SELECT product_id, name, description, price, status, image FROM products WHERE farmer_id = :farmer_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching products: " . $e->getMessage());
        }
    }

    // Update product details
    public function updateProduct($productId, $productName, $description, $price, $status, $image = null) {
        try {
            // Prepare the base query
            $query = "UPDATE products SET name = :name, description = :description, price = :price, status = :status";

            // Only add the image part if $image is provided
            if (!empty($image)) {
                $query .= ", image = :image";
            }

            $query .= " WHERE product_id = :product_id";

            $stmt = $this->conn->prepare($query);

            // Bind parameters to the query
            $stmt->bindParam(':name', $productName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            // Bind the image only if it's provided
            if (!empty($image)) {
                $stmt->bindParam(':image', $image, PDO::PARAM_STR);
            }

            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);

            // Execute the query and check if successful
            if ($stmt->execute()) {
                return null;  // Success, no error
            } else {
                return "Error updating product.";  // Error message
            }
        } catch (PDOException $e) {
            throw new Exception("Error: " . $e->getMessage());  // Catch any PDO exceptions and return the error message
        }
    }

    // Add Product Method
    public function addProduct($farmer_id, $productName, $description, $price, $status, $image) {
        // Prepare SQL query to insert the product details
        $query = "INSERT INTO products (name, description, price, farmer_id, status, image)
                  VALUES (:name, :description, :price, :farmer_id, :status, :image)";

        try {
            $stmt = $this->conn->prepare($query);
            // Bind parameters to the query
            $stmt->bindParam(':name', $productName, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':image', $image, PDO::PARAM_STR);

            // Execute the query and check if successful
            if ($stmt->execute()) {
                return ['product_id' => $this->conn->lastInsertId()]; // Return the inserted product ID
            } else {
                return false; // Error
            }
        } catch (PDOException $e) {
            throw new Exception("Error inserting product: " . $e->getMessage());
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
            throw new Exception("Error fetching orders: " . $e->getMessage());
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
            throw new Exception("Error updating order status: " . $e->getMessage());
        }
    }

    // Method to get the number of orders per day for a specific farmer
    public function getOrderCountPerDay($farmer_id) {
        $sql = "SELECT DATE(o.order_date) AS order_day, COUNT(*) AS order_count
                FROM orders o
                JOIN orderitems oi ON o.order_id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE p.farmer_id = :farmer_id
                GROUP BY DATE(o.order_date)
                ORDER BY order_day ASC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching order count per day: " . $e->getMessage());
        }
    }

    /* ==================== FEEDBACK METHODS ==================== */

    // Fetch feedback for a specific farmer
    public function getFeedbackByFarmer($farmer_id) {
        $sql = "SELECT f.feedback_text, f.rating, f.created_at, p.name AS product_name
                FROM feedback f
                JOIN products p ON f.product_id = p.product_id
                WHERE p.farmer_id = :farmer_id
                ORDER BY f.created_at DESC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching feedback: " . $e->getMessage());
        }
    }

    /* ==================== FARMER PROFILE METHODS ==================== */

    public function getFarmerDetails($farmer_id) {
        $query = "SELECT fd.farm_name, fd.farm_location, fd.farm_type, fd.certifications, 
                         fd.crop_varieties, fd.machinery_used, fd.farm_size, fd.income, 
                         fd.barangay_id, b.barangay_name, b.municipality, b.province 
                  FROM farmer_details fd 
                  LEFT JOIN barangays b ON fd.barangay_id = b.barangay_id 
                  WHERE fd.user_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching farmer details: " . $e->getMessage());
        }
    }

    // Farmer login authentication
    public function checkLogin($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username AND role_id = 2";  // 2 is the role ID for Farmer
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists and password matches
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables after successful login
                $_SESSION['farmer_logged_in'] = true;
                $_SESSION['farmer_id'] = $user['user_id'];  // Store the farmer's ID in session
                return $user; // Return the user data if login is successful
            }
        } catch (PDOException $e) {
            throw new Exception("Error during login: " . $e->getMessage());
        }

        return false; // Return false if login fails
    }

    // Fixed method names
    public function getUserDetails($farmer_id) {
        $query = "SELECT first_name, last_name, contact_number, address FROM users WHERE user_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching user details: " . $e->getMessage());
        }
    }

    public function updateUserDetails($farmer_id, $firstName, $lastName, $contactNumber, $address) {
        // Use the existing connection
        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, contact_number = :contact_number, address = :address WHERE user_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
            $stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            return $stmt->execute(); // Return true if successful, false otherwise
        } catch (PDOException $e) {
            throw new Exception("Error updating user details: " . $e->getMessage());
        }
    }


    public function updateFarmerAdditionalDetails($farmer_id, $field, $value) {
        // Ensure $field is a valid column name to prevent SQL injection
        $allowedFields = ['farm_name', 'farm_location', 'farm_type', 'certifications', 'crop_varieties', 'machinery_used', 'farm_size', 'income', 'barangay_id'];
        if (!in_array($field, $allowedFields)) {
            throw new Exception("Invalid field name: " . $field);
        }

        $query = "UPDATE farmer_details SET $field = :value WHERE user_id = :farmer_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            return $stmt->execute(); // Return true if successful, false otherwise
        } catch (PDOException $e) {
            throw new Exception("Error updating farm details: " . $e->getMessage());
        }
    }

    public function deleteProduct($product_id) {
        try {
            // First, delete related records in orderitems and feedback (if necessary)
            $this->conn->beginTransaction();

            // Delete from orderitems (if applicable)
            $query1 = "DELETE FROM orderitems WHERE product_id = :product_id";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt1->execute();

            // Delete from feedback (if applicable)
            $query2 = "DELETE FROM feedback WHERE product_id = :product_id";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt2->execute();

            // Finally, delete from products
            $query3 = "DELETE FROM products WHERE product_id = :product_id";
            $stmt3 = $this->conn->prepare($query3);
            $stmt3->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt3->execute();

            $this->conn->commit();

            return true; // Return true if all operations were successful
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception("Error deleting product and related records: " . $e->getMessage());
        }
    }

    /**
     * Get all farmers
     */
    public function getAllFarmers() {
        $query = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.contact_number, u.address 
                 FROM users u
                 WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Farmer')
                 ORDER BY u.last_name, u.first_name";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching all farmers: " . $e->getMessage());
        }
    }

    /* ==================== BARANGAY METHODS ==================== */

    /**
     * Get all available barangays
     */
    public function getAllBarangays() {
        $query = "SELECT * FROM barangays ORDER BY barangay_name";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching barangays: " . $e->getMessage());
        }
    }

    /**
     * Get farmers by barangay
     */
    public function getFarmersByBarangay($barangay_id) {
        $query = "SELECT u.user_id, u.username, u.first_name, u.last_name, fd.farm_name, fd.farm_size 
                 FROM users u
                 JOIN farmer_details fd ON u.user_id = fd.user_id
                 WHERE fd.barangay_id = :barangay_id AND u.role_id = 2";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching farmers by barangay: " . $e->getMessage());
        }
    }

    /**
     * Update farmer's barangay association
     */
    public function updateFarmerBarangay($farmer_id, $barangay_id) {
        // First check if farmer details record exists
        $check_query = "SELECT 1 FROM farmer_details WHERE user_id = :farmer_id";
        try {
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing record
                $query = "UPDATE farmer_details SET barangay_id = :barangay_id WHERE user_id = :farmer_id";
            } else {
                // Insert new record
                $query = "INSERT INTO farmer_details (user_id, barangay_id) VALUES (:farmer_id, :barangay_id)";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error updating farmer barangay: " . $e->getMessage());
        }
    }

    /* ==================== CROP SEASON METHODS ==================== */
    
    /**
     * Get all crop seasons
     */
    public function getAllCropSeasons() {
        $query = "SELECT * FROM crop_seasons ORDER BY start_month";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching crop seasons: " . $e->getMessage());
        }
    }
    
    /**
     * Get the current active crop season
     */
    public function getCurrentCropSeason() {
        $currentMonth = date('n'); // Current month as a number (1-12)
        $query = "SELECT * FROM crop_seasons 
                  WHERE :current_month BETWEEN start_month AND end_month
                  OR (start_month > end_month AND 
                     (:current_month >= start_month OR :current_month <= end_month))";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':current_month', $currentMonth, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching current crop season: " . $e->getMessage());
        }
    }
    
    /**
     * Associate a product with a crop season
     */
    public function addProductSeason($product_id, $season_id, $yield_estimate = null, $notes = null) {
        $query = "INSERT INTO product_seasons (product_id, season_id, yield_estimate, notes)
                  VALUES (:product_id, :season_id, :yield_estimate, :notes)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
            $stmt->bindParam(':yield_estimate', $yield_estimate, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error adding product season: " . $e->getMessage());
        }
    }
    
    /**
     * Get all product seasons for a specific product
     */
    public function getProductSeasons($product_id) {
        $query = "SELECT ps.*, cs.season_name, cs.start_month, cs.end_month, cs.description
                  FROM product_seasons ps
                  JOIN crop_seasons cs ON ps.season_id = cs.season_id
                  WHERE ps.product_id = :product_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching product seasons: " . $e->getMessage());
        }
    }
    
    /* ==================== BARANGAY PRODUCTS METHODS ==================== */
    
    /**
     * Record product production in a barangay with area metrics
     */
    public function addBarangayProduct($barangay_id, $product_id, $estimated_production, $production_unit = 'kilogram', $planted_area = 0, $area_unit = 'hectare', $year = null, $season_id = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $query = "INSERT INTO barangay_products 
                  (barangay_id, product_id, estimated_production, production_unit, planted_area, area_unit, year, season_id)
                  VALUES (:barangay_id, :product_id, :estimated_production, :production_unit, :planted_area, :area_unit, :year, :season_id)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':estimated_production', $estimated_production, PDO::PARAM_STR);
            $stmt->bindParam(':production_unit', $production_unit, PDO::PARAM_STR);
            $stmt->bindParam(':planted_area', $planted_area, PDO::PARAM_STR);
            $stmt->bindParam(':area_unit', $area_unit, PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error adding barangay product: " . $e->getMessage());
        }
    }
    
    /**
     * Update barangay product data including area
     */
    public function updateBarangayProduct($id, $estimated_production, $production_unit, $planted_area, $area_unit, $season_id = null) {
        $query = "UPDATE barangay_products SET 
                  estimated_production = :estimated_production,
                  production_unit = :production_unit,
                  planted_area = :planted_area,
                  area_unit = :area_unit";
                  
        if ($season_id !== null) {
            $query .= ", season_id = :season_id";
        }
        
        $query .= " WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estimated_production', $estimated_production, PDO::PARAM_STR);
            $stmt->bindParam(':production_unit', $production_unit, PDO::PARAM_STR);
            $stmt->bindParam(':planted_area', $planted_area, PDO::PARAM_STR);
            $stmt->bindParam(':area_unit', $area_unit, PDO::PARAM_STR);
            
            if ($season_id !== null) {
                $stmt->bindParam(':season_id', $season_id, PDO::PARAM_INT);
            }
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error updating barangay product: " . $e->getMessage());
        }
    }
    
    /**
     * Get agricultural efficiency metrics for a specific farmer
     */
    public function getFarmerProductionMetrics($farmer_id) {
        $query = "SELECT 
                    p.name as product_name,
                    bp.estimated_production as total_production,
                    bp.production_unit,
                    bp.planted_area,
                    bp.area_unit,
                    cs.season_name,
                    CASE 
                        WHEN bp.planted_area > 0 
                        THEN bp.estimated_production / bp.planted_area 
                        ELSE 0 
                    END as yield_per_area
                  FROM products p
                  JOIN barangay_products bp ON p.product_id = bp.product_id
                  JOIN farmer_details fd ON fd.barangay_id = bp.barangay_id
                  LEFT JOIN crop_seasons cs ON bp.season_id = cs.season_id
                  WHERE p.farmer_id = :farmer_id AND fd.user_id = :farmer_id
                  ORDER BY yield_per_area DESC";
                  
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching farmer production metrics: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate total farm planted area for a specific farmer
     */
    public function getFarmerTotalPlantedArea($farmer_id) {
        $query = "SELECT 
                    SUM(bp.planted_area) as total_planted_area,
                    MAX(bp.area_unit) as area_unit
                  FROM products p
                  JOIN barangay_products bp ON p.product_id = bp.product_id
                  JOIN farmer_details fd ON fd.barangay_id = bp.barangay_id
                  WHERE p.farmer_id = :farmer_id AND fd.user_id = :farmer_id";
                  
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'total_planted_area' => $result['total_planted_area'] ? floatval($result['total_planted_area']) : 0,
                'area_unit' => $result['area_unit'] ?: 'hectare'
            ];
        } catch (PDOException $e) {
            throw new Exception("Error calculating farmer total planted area: " . $e->getMessage());
        }
    }
    
    /**
     * Get products produced in a specific barangay
     */
    public function getBarangayProducts($barangay_id, $year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $query = "SELECT bp.*, p.name as product_name, cs.season_name
                  FROM barangay_products bp
                  JOIN products p ON bp.product_id = p.product_id
                  LEFT JOIN crop_seasons cs ON bp.season_id = cs.season_id
                  WHERE bp.barangay_id = :barangay_id AND bp.year = :year";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching barangay products: " . $e->getMessage());
        }
    }
}
