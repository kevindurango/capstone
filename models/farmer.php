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
        $query = "SELECT farm_name, farm_location, farm_type, certifications, crop_varieties, machinery_used, farm_size, income FROM farmer_details WHERE user_id = :farmer_id";
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
        $allowedFields = ['farm_name', 'farm_location', 'farm_type', 'certifications', 'crop_varieties', 'machinery_used', 'farm_size', 'income'];
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

}
?>
