<?php
require_once 'Database.php';  // Include the Database class

class User
{
    private $conn;

    public function __construct()
    {
        // Instantiate the Database class and establish a connection
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Fetch all users from the database, including the new personal fields
    public function getUsers()
    {
        try {
            // Modify the query to fetch personal information fields
            $query = "SELECT user_id, username, email, role_id, first_name, last_name, contact_number, address FROM users";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching users: " . $e->getMessage());
        }
    }

    // Fetch all roles from the database
    public function getRoles()
    {
        try {
            $query = "SELECT * FROM roles";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching roles: " . $e->getMessage());
        }
    }

    // Add a new user to the database (with input validation and password hashing)
    public function addUser($username, $email, $password, $role_id, $first_name, $last_name, $contact_number = null, $address = null)
    {
        try {
            // Insert user into the users table with personal information
            $query = "INSERT INTO users (username, email, password, role_id, first_name, last_name, contact_number, address, created_at, updated_at) 
                      VALUES (:username, :email, :password, :role_id, :first_name, :last_name, :contact_number, :address, NOW(), NOW())";
            $stmt = $this->conn->prepare($query);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Bind parameters
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':address', $address);

            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error adding user: " . $e->getMessage();
            return false;
        }
    }

    // Update an existing user (Conditional Password Update)
    public function updateUser($user_id, $username, $email, $password, $role_id, $first_name, $last_name, $contact_number = null, $address = null)
    {
        try {
            $query = "UPDATE users SET username = :username, email = :email, role_id = :role_id, first_name = :first_name, 
                      last_name = :last_name, contact_number = :contact_number, address = :address, updated_at = NOW()";

            // Append password update only if provided
            if (!empty($password)) {
                $query .= ", password = :password";
            }
            $query .= " WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // Bind required parameters
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':address', $address);

            // Hash and bind the password if provided
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $hashedPassword);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error updating user: " . $e->getMessage();
            return false;
        }
    }

    // Delete a user from the database
    public function deleteUser($user_id)
    {
        try {
            // Begin transaction to ensure all operations succeed or fail together
            $this->conn->beginTransaction();
            
            // Try to delete any related entries in other tables first
            try {
                // Delete from activity_logs - fix the table name to match your database
                $query = "DELETE FROM activitylogs WHERE user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Delete from order_items where the order belongs to this user
                $query = "DELETE oi FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.order_id 
                          WHERE o.consumer_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Delete orders associated with this user
                $query = "DELETE FROM orders WHERE consumer_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Removed driver-related code that was handling pickups
            } catch (PDOException $e) {
                // Log the error but continue - some tables might not exist or have different structures
                error_log("Error deleting related records: " . $e->getMessage());
            }
            
            // Finally delete the user
            $query = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                $this->conn->commit();
                return true;
            } else {
                $this->conn->rollBack();
                error_log("Failed to delete user ID: $user_id");
                return false;
            }
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error deleting user: " . $e->getMessage());
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }

    // Check if a user exists by email (to prevent duplicates)
    public function userExists($email)
    {
        try {
            $query = "SELECT COUNT(*) AS count FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            die("Error checking user existence: " . $e->getMessage());
        }
    }

    // User login method
    public function login($username, $password, $role)
    {
        try {
            $query = "SELECT * FROM users WHERE username = :username AND role_id = (SELECT role_id FROM roles WHERE role_name = :role) LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo "Error logging in: " . $e->getMessage();
            return false;
        }
    }

    public function getUsersByRoles($roles) {
        try {
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $query = "SELECT u.*, r.role_name 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.role_id 
                     WHERE r.role_name IN ($placeholders)
                     ORDER BY u.user_id DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($roles);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users by roles: " . $e->getMessage());
            return [];
        }
    }

    public function getRolesByType($roleTypes) {
        try {
            $placeholders = str_repeat('?,', count($roleTypes) - 1) . '?';
            $query = "SELECT * FROM roles WHERE role_name IN ($placeholders)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($roleTypes);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching roles by type: " . $e->getMessage());
            return [];
        }
    }

    public function countUsersByRole($roleName) {
        try {
            $query = "SELECT COUNT(*) as count 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.role_id 
                     WHERE r.role_name = :role_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_name', $roleName);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error counting users by role: " . $e->getMessage());
            return 0;
        }
    }

    public function getUserRole($userId) {
        try {
            $query = "SELECT role_id FROM users WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['role_id'] : null;
        } catch (PDOException $e) {
            error_log("Error getting user role: " . $e->getMessage());
            return null;
        }
    }

    // Close the database connection (optional)
    public function close()
    {
        $this->conn = null;
    }
}
?>
