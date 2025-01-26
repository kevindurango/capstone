<?php
// File: C:\xampp\htdocs\capstone\models\Database.php

class Database {
    private $host = 'localhost';
    private $db = 'farmersmarketdb'; // Change this to your actual DB name
    private $user = 'root'; // Change if needed
    private $pass = ''; // Change if needed
    private $charset = 'utf8mb4';
    public $conn;

    public function connect() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
                $this->conn = new PDO($dsn, $this->user, $this->pass);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo 'Connection failed: ' . $e->getMessage();
                exit;
            }
        }
        return $this->conn;
    }
}
?>
