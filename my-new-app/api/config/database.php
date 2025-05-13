<?php
// Database connection parameters
$host = "localhost";
$dbname = "farmersmarketdb";
$username = "root";
$password = "";

// Global function to get a database connection
function getConnection() {
    global $host, $username, $password, $dbname;
    
    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return null;
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]));
}

// PDO connection class (if needed)
class Database {
    private $host = "localhost";
    private $dbname = "farmersmarketdb";
    private $username = "root";
    private $password = "";
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname,
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
        }

        return $this->conn;
    }
    
    // Adding getConnection method for backward compatibility
    public function getConnection() {
        // Create a mysqli connection for compatibility with existing code
        $conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Connection Error: " . $conn->connect_error);
            return null;
        }
        
        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");
        
        return $conn;
    }
}
?>
