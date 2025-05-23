<?php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";
      // Object properties
    public $id;  // Will store user_id internally
    public $user_id; // Actual database column name
    public $name;
    public $email;
    public $password;
    public $role;
    public $created_at;
    
    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }
      // Method to check if email exists
    public function emailExists($email) {
        // Query to check if email exists
        $query = "SELECT user_id, first_name, last_name, email, password, role_id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        
        try {
            // Prepare and execute the query
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $email);
            $stmt->execute();
            
            // Check if email exists
            if($stmt->rowCount() > 0) {
                // Get user details
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                  // Set values to object properties
                $this->id = $row['user_id']; // Store user_id in id property for backwards compatibility
                $this->user_id = $row['user_id'];
                
                // Handle name (could be stored as first_name + last_name)
                if (isset($row['name'])) {
                    $this->name = $row['name'];
                } else if (isset($row['first_name'])) {
                    $this->name = $row['first_name'] . ' ' . ($row['last_name'] ?? '');
                }
                
                $this->email = $row['email'];
                $this->password = $row['password'];
                
                // Handle role which might be stored as role_id
                if (isset($row['role'])) {
                    $this->role = $row['role'];
                } else if (isset($row['role_id'])) {
                    $this->role = $row['role_id'];
                }
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error checking if email exists: " . $e->getMessage());
            return false;
        }
    }
    
    // Add more methods as needed for your application
}
?>
