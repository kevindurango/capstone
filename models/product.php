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
                  WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Farmer')";
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

    // Updated updateProduct method with proper parameters
    public function updateProduct($id, $name, $description, $price, $stock, $farmer_id, $image, $status)
    {
        try {
            $query = "UPDATE products SET 
                      name = :name, 
                      description = :description, 
                      price = :price, 
                      stock = :stock,
                      farmer_id = :farmer_id,
                      image = :image, 
                      status = :status, 
                      updated_at = NOW() 
                      WHERE product_id = :id";
            
            // Prepare the statement
            $stmt = $this->conn->prepare($query);
        
            // Bind parameters using PDO's bindValue() method
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':price', $price, PDO::PARAM_STR);
            $stmt->bindValue(':stock', $stock, PDO::PARAM_INT);
            $stmt->bindValue(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->bindValue(':image', $image, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
            // Execute the query and return the result
            $result = $stmt->execute();
            
            error_log("Product updated: " . $id . " - Result: " . ($result ? "Success" : "Failed"));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
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

    public function getProductCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM products";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting product count: " . $e->getMessage());
            return 0;
        }
    }

    public function getProductCountByStatus($status) {
        try {
            $query = "SELECT COUNT(*) as count FROM products WHERE status = :status";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting product count by status: " . $e->getMessage());
            return 0;
        }
    }

    public function getLowStockProducts($threshold = 10) {
        try {
            $query = "SELECT * FROM products WHERE stock <= :threshold";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log("Error getting low stock products: " . $e->getMessage());
            return [];
        }
    }

    public function getAllProductsWithDetails()
    {
        try {
            // More efficient query with LEFT JOINs to handle potential missing relationships
            $query = "SELECT p.*, u.username as farmer_name, 
                     pc.category_name as category 
                     FROM products p 
                     LEFT JOIN users u ON p.farmer_id = u.user_id 
                     LEFT JOIN (
                         SELECT pcm.product_id, GROUP_CONCAT(pc.category_name) as category_name 
                         FROM productcategorymapping pcm 
                         JOIN productcategories pc ON pcm.category_id = pc.category_id 
                         GROUP BY pcm.product_id
                     ) pc ON p.product_id = pc.product_id 
                     ORDER BY p.product_id DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Clean up image paths
            foreach ($products as &$product) {
                if (!empty($product['image'])) {
                    // For relative paths in database, ensure they are properly formatted
                    if (strpos($product['image'], 'uploads/') !== false) {
                        $product['image'] = substr($product['image'], strpos($product['image'], 'uploads/'));
                    }
                    
                    // If image path is just a filename, prefix with uploads/
                    if (!strpos($product['image'], '/')) {
                        $product['image'] = 'uploads/' . $product['image'];
                    }
                }
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Error in getAllProductsWithDetails: " . $e->getMessage());
            return [];
        }
    }

    // Add methods for category management
    public function assignProductCategory($product_id, $category_id)
    {
        try {
            // First, check if the mapping already exists
            $checkQuery = "SELECT 1 FROM productcategorymapping 
                          WHERE product_id = :product_id AND category_id = :category_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn()) {
                // Mapping already exists
                return true;
            }
            
            // Insert the new mapping
            $query = "INSERT INTO productcategorymapping (product_id, category_id) 
                     VALUES (:product_id, :category_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning product category: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateProductCategory($product_id, $category_id)
    {
        try {
            // First, remove all existing category mappings for this product
            $deleteQuery = "DELETE FROM productcategorymapping WHERE product_id = :product_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            // Then, add the new category mapping
            return $this->assignProductCategory($product_id, $category_id);
        } catch (PDOException $e) {
            error_log("Error updating product category: " . $e->getMessage());
            return false;
        }
    }

    // Get all available categories
    public function getAllCategories() 
    {
        try {
            $query = "SELECT * FROM productcategories ORDER BY category_name";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    // Optional: Close database connection
    public function close()
    {
        $this->conn = null;
    }

    public function updateStock($productId, $newStock) {
        try {
            $query = "UPDATE products SET stock = :stock, updated_at = NOW() WHERE product_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':stock', $newStock, PDO::PARAM_INT);
            $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating stock: " . $e->getMessage());
            return false;
        }
    }
}
