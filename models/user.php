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
