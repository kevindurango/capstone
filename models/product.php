<?php
require_once 'Database.php';

class Product
{
    private $conn;

    // Constructor: Initialize the database connection
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Fetch all farmers (for dropdown selection in the Add Product modal)
    public function getAllFarmers()
    {
        $query = "SELECT user_id, username 
                  FROM users 
                  WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'farmer')";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all products
    public function getAllProducts()
    {
        $query = "SELECT * FROM products";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all products with associated farmer details
    public function getAllProductsWithFarmers()
    {
        $query = "SELECT p.*, u.username AS farmer_name
                  FROM products p
                  INNER JOIN users u ON p.farmer_id = u.user_id";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch products specific to a farmer
    public function getProductsByFarmer($farmer_id)
    {
        $query = "SELECT * FROM products WHERE farmer_id = :farmer_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a new product
    public function addProduct($name, $description, $price, $farmer_id, $stock, $image = null, $status = 'pending')
    {
        $query = "INSERT INTO products (farmer_id, name, description, price, stock, image, status, created_at, updated_at) 
                  VALUES (:farmer_id, :name, :description, :price, :stock, :image, :status, NOW(), NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':image', $image, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Update an existing product
    public function updateProduct($id, $name, $description, $price, $image, $status)
    {
        $query = "UPDATE products SET name = :name, description = :description, price = :price, image = :image, status = :status WHERE product_id = :id";
        
        // Prepare the statement
        $stmt = $this->conn->prepare($query);
    
        // Bind parameters using PDO's bindValue() method
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':price', $price, PDO::PARAM_STR); // or PDO::PARAM_INT, depending on price format
        $stmt->bindValue(':image', $image, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT); // Ensure product_id is an integer
    
        // Execute the query and return the result
        return $stmt->execute();
    }
    
    // Delete a product
    public function deleteProduct($id)
    {
        $query = "DELETE FROM products WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Get a single product by its ID
    public function getProductById($id)
    {
        $query = "SELECT * FROM products WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update stock for a product
    public function updateProductStock($id, $stock)
    {
        $query = "UPDATE products 
                  SET stock = :stock, updated_at = NOW() 
                  WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateProductStatus($product_id, $status)
    {
        $query = "UPDATE products SET status = ? WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status, $product_id]);

        return $stmt->rowCount() > 0; // Return true if the update was successful
    }


    // Optional: Close database connection
    public function close()
    {
        $this->conn = null;
    }
}
