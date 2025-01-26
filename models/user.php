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

    // Fetch all users from the database
    public function getUsers()
    {
        try {
            $query = "SELECT * FROM users";
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
    public function addUser($username, $email, $password, $role_id)
    {
        try {
            $query = "INSERT INTO users (username, email, password, role_id, created_at, updated_at) 
                      VALUES (:username, :email, :password, :role_id, NOW(), NOW())";
            $stmt = $this->conn->prepare($query);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Bind parameters
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role_id', $role_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error adding user: " . $e->getMessage();
            return false;
        }
    }

    // Get role name by role_id
    public function getRoleName($role_id)
    {
        try {
            $query = "SELECT role_name FROM roles WHERE role_id = :role_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->execute();
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ? $role['role_name'] : 'Unknown Role';
        } catch (PDOException $e) {
            die("Error fetching role: " . $e->getMessage());
        }
    }

    // Update an existing user (Conditional Password Update)
    public function updateUser($user_id, $username, $email, $password, $role_id)
    {
        try {
            $query = "UPDATE users SET username = :username, email = :email, role_id = :role_id, updated_at = NOW()";

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
            $query = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error deleting user: " . $e->getMessage();
            return false;
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

    // Close the database connection (optional)
    public function close()
    {
        $this->conn = null;
    }
}
?>
