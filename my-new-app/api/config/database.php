<?php
// Database connection parameters
$host = "localhost";
$db_name = "farmersmarketdb";
$username = "root";
$password = "";

// Create both mysqli and PDO connections for backward compatibility
$conn = new mysqli($host, $username, $password, $db_name);

// Check mysqli connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]));
}

// Class for PDO connection
class Database {
    private $host = "localhost";
    private $db_name = "farmersmarketdb";
    private $username = "root";
    private $password = "";
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            $this->conn = null;
        }

        return $this->conn;
    }
}
?>
