<?php
require_once 'Database.php';

class Product
{
    private $conn;
    private $lastError = '';

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
                  LEFT JOIN users u ON p.farmer_id = u.user_id";
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
    public function addProduct($name, $description, $price, $farmer_id, $stock, $image = null, $status = 'pending', $unit_type = 'piece')
    {
        try {
            // Validate required fields
            if (empty($name) || $price <= 0 || empty($unit_type)) {
                throw new Exception("Invalid product data: Name, price, and unit type are required.");
            }

            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status. Allowed values: " . implode(', ', $validStatuses));
            }

            $query = "INSERT INTO products (farmer_id, name, description, price, stock, image, status, unit_type, created_at, updated_at) 
                      VALUES (:farmer_id, :name, :description, :price, :stock, :image, :status, :unit_type, NOW(), NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
            $stmt->bindParam(':image', $image, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':unit_type', $unit_type, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            } else {
                throw new Exception("Failed to add product: " . implode(', ', $stmt->errorInfo()));
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Error adding product: " . $e->getMessage());
            return false;
        }
    }

    // Updated updateProduct method with proper error handling
    public function updateProduct($id, $productData = [])
    {
        try {
            // Get current product
            $currentProduct = $this->getProductById($id);
            if (!$currentProduct) {
                $this->lastError = "Product not found";
                return false;
            }

            $query = "UPDATE products SET 
                      name = :name,
                      description = :description,
                      price = :price,
                      stock = :stock,
                      farmer_id = :farmer_id,
                      image = :image,
                      status = :status,
                      unit_type = :unit_type,
                      updated_at = NOW()
                      WHERE product_id = :id";

            $stmt = $this->conn->prepare($query);

            // Use current values if not provided in update data
            $stmt->bindValue(':name', $productData['name'] ?? $currentProduct['name'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $productData['description'] ?? $currentProduct['description'], PDO::PARAM_STR);
            $stmt->bindValue(':price', $productData['price'] ?? $currentProduct['price'], PDO::PARAM_STR);
            $stmt->bindValue(':stock', $productData['stock'] ?? $currentProduct['stock'], PDO::PARAM_INT);
            $stmt->bindValue(':farmer_id', $productData['farmer_id'] ?? $currentProduct['farmer_id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', $productData['status'] ?? $currentProduct['status'], PDO::PARAM_STR);
            $stmt->bindValue(':unit_type', $productData['unit_type'] ?? $currentProduct['unit_type'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            // Handle image update
            if (isset($productData['image']) && !empty($productData['image'])) {
                $stmt->bindValue(':image', $productData['image'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':image', $currentProduct['image'], PDO::PARAM_STR);
            }

            $result = $stmt->execute();

            // Update category if provided
            if (isset($productData['category_id']) && !empty($productData['category_id'])) {
                $this->updateProductCategory($id, $productData['category_id']);
            }

            error_log("Product updated: " . $id . " - Result: " . ($result ? "Success" : "Failed"));
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }

    // Add a new method to handle updates from an associative array (more flexible)
    public function updateProductFromArray($id, $data)
    {
        try {
            // Get the current product
            $currentProduct = $this->getProductById($id);
            if (!$currentProduct) {
                $this->lastError = "Product with ID $id not found";
                error_log("Product with ID $id not found");
                return false;
            }

            // List of fields that should not be directly updated in SQL statement
            $excludedFields = ['product_id', 'category_id', 'created_at', 'updated_at', 'current_image'];
            
            $setFields = [];
            $params = [':id' => $id];

            // Build dynamic SET clause
            foreach ($data as $key => $value) {
                // Skip excluded fields and null values
                if ($value !== null && !in_array($key, $excludedFields)) {
                    // Properly escape column name with backticks
                    $setFields[] = "`$key` = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($setFields)) {
                $this->lastError = "No fields to update";
                error_log("No fields to update for product ID: $id");
                return false;
            }

            // Always add updated_at timestamp
            $sql = "UPDATE products SET " . implode(', ', $setFields) . 
                   ", `updated_at` = NOW() WHERE product_id = :id";

            error_log("Executing SQL: " . $sql);
            error_log("With params: " . print_r($params, true));

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);

            if (!$result) {
                $this->lastError = "Database update failed: " . implode(', ', $stmt->errorInfo());
                error_log($this->lastError);
                return false;
            }

            // Update category if provided
            if (isset($data['category_id']) && !empty($data['category_id'])) {
                $categoryResult = $this->updateProductCategory($id, $data['category_id']);
                error_log("Category update result for product $id: " . ($categoryResult ? "Success" : "Failed"));
            }

            // Log success
            error_log("Successfully updated product ID: $id with " . count($params) - 1 . " fields");
            return true;
        } catch (PDOException $e) {
            $this->lastError = "Database error: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    // Delete a product
    public function deleteProduct($id)
    {
        try {
            $this->conn->beginTransaction();

            // Nullify product_id in related tables
            $updateFeedbackQuery = "UPDATE feedback SET product_id = NULL WHERE product_id = :id";
            $feedbackStmt = $this->conn->prepare($updateFeedbackQuery);
            $feedbackStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $feedbackStmt->execute();

            $updateOrderItemsQuery = "UPDATE orderitems SET product_id = NULL WHERE product_id = :id";
            $orderItemsStmt = $this->conn->prepare($updateOrderItemsQuery);
            $orderItemsStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $orderItemsStmt->execute();

            // Delete from productcategorymapping
            $deleteMappingQuery = "DELETE FROM productcategorymapping WHERE product_id = :id";
            $mappingStmt = $this->conn->prepare($deleteMappingQuery);
            $mappingStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $mappingStmt->execute();

            // Delete the product
            $query = "DELETE FROM products WHERE product_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();

            $this->conn->commit();
            return $result;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in deleteProduct: " . $e->getMessage());
            return false;
        }
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

    public function getProductCount()
    {
        try {
            $query = "SELECT COUNT(*) as count FROM products";
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting product count: " . $e->getMessage());
            return 0;
        }
    }

    public function getProductCountByStatus($status)
    {
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

    public function getLowStockProducts($threshold = 10)
    {
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
            $query = "SELECT p.*, u.username as farmer_name, 
                     pc.category_name as category, pcm.category_id 
                     FROM products p 
                     LEFT JOIN users u ON p.farmer_id = u.user_id 
                     LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id 
                     LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id 
                     ORDER BY p.product_id DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize image paths for consistency
            foreach ($products as &$product) {
                if (!empty($product['image'])) {
                    // Handle 'Array' as image value by setting to empty string
                    if ($product['image'] === 'Array' || strpos($product['image'], 'Array') !== false) {
                        $product['image'] = '';
                        continue;
                    }

                    // Only modify path if it doesn't already have the correct prefix
                    if (strpos($product['image'], 'uploads/products/') !== 0) {
                        // Extract just the filename if it contains a path
                        $filename = basename($product['image']);

                        // Set standardized path format
                        $product['image'] = 'uploads/products/' . $filename;
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
            // Check if a transaction is already active
            $isTransactionActive = $this->conn->inTransaction();

            if (!$isTransactionActive) {
                $this->conn->beginTransaction();
            }

            // Validate inputs
            if (!$product_id || !$category_id) {
                throw new PDOException("Invalid product_id or category_id");
            }

            // Check if the mapping already exists
            $checkQuery = "SELECT 1 FROM productcategorymapping 
                          WHERE product_id = :product_id AND category_id = :category_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn()) {
                if (!$isTransactionActive) {
                    $this->conn->commit();
                }
                return true;
            }

            // Insert the new mapping
            $query = "INSERT INTO productcategorymapping (product_id, category_id) 
                      VALUES (:product_id, :category_id)";
            $insertStmt = $this->conn->prepare($query);
            $insertStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $result = $insertStmt->execute();

            if (!$isTransactionActive) {
                $this->conn->commit();
            }
            return $result;
        } catch (PDOException $e) {
            if (!$isTransactionActive && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log("Error assigning product category: " . $e->getMessage());
            return false;
        }
    }

    public function updateProductCategory($product_id, $category_id)
    {
        try {
            // Check if a transaction is already active
            $isTransactionActive = $this->conn->inTransaction();

            if (!$isTransactionActive) {
                $this->conn->beginTransaction();
            }

            // Validate inputs
            if (!$product_id || !$category_id) {
                throw new PDOException("Invalid product_id or category_id");
            }

            // Ensure product exists
            $checkProduct = $this->getProductById($product_id);
            if (!$checkProduct) {
                throw new PDOException("Product ID {$product_id} not found");
            }

            // Ensure category exists
            $checkCatQuery = "SELECT category_id FROM productcategories WHERE category_id = :category_id";
            $checkCatStmt = $this->conn->prepare($checkCatQuery);
            $checkCatStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $checkCatStmt->execute();
            if (!$checkCatStmt->fetch()) {
                throw new PDOException("Category ID {$category_id} not found");
            }

            // Delete existing category mappings
            $deleteQuery = "DELETE FROM productcategorymapping WHERE product_id = :product_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $deleteStmt->execute();

            // Add new category mapping
            $insertQuery = "INSERT INTO productcategorymapping (product_id, category_id) VALUES (:product_id, :category_id)";
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $result = $insertStmt->execute();

            if (!$isTransactionActive) {
                $this->conn->commit();
            }
            return $result;
        } catch (PDOException $e) {
            if (!$isTransactionActive && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log("Error updating product category: " . $e->getMessage());
            return false;
        }
    }

    // Get all categories for a specific product
    public function getProductCategories($product_id)
    {
        try {
            $query = "SELECT pc.category_id, pc.category_name
                      FROM productcategories pc
                      JOIN productcategorymapping pcm ON pc.category_id = pcm.category_id
                      WHERE pcm.product_id = :product_id
                      ORDER BY pc.category_name";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting product categories: " . $e->getMessage());
            return [];
        }
    }
    
    // Get a single product with all its details including category
    public function getProductWithFullDetails($product_id)
    {
        try {
            // First get the basic product information
            $product = $this->getProductById($product_id);
            
            if (!$product) {
                return null;
            }
            
            // Get the farmer information if available
            if ($product['farmer_id']) {
                $farmerQuery = "SELECT username as farmer_name FROM users WHERE user_id = :farmer_id";
                $farmerStmt = $this->conn->prepare($farmerQuery);
                $farmerStmt->bindParam(':farmer_id', $product['farmer_id'], PDO::PARAM_INT);
                $farmerStmt->execute();
                $farmer = $farmerStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($farmer) {
                    $product['farmer_name'] = $farmer['farmer_name'];
                }
            }
            
            // Get all categories for this product
            $categories = $this->getProductCategories($product_id);
            
            if (!empty($categories)) {
                $product['categories'] = $categories;
                // For convenience in forms, add the first category
                $product['category_id'] = $categories[0]['category_id'];
                $product['category_name'] = $categories[0]['category_name'];
            }
            
            return $product;
        } catch (PDOException $e) {
            error_log("Error in getProductWithFullDetails: " . $e->getMessage());
            return null;
        }
    }

    // FIXED: Get all available categories (this was the problematic method)
    public function getAllCategories()
    {
        try {
            // Simple query to get all categories
            $query = "SELECT * FROM productcategories ORDER BY category_name";
            
            // Properly prepare and execute the statement
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Use fetchAll() correctly
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    // Optional: Close database connection
    public function close()
    {
        $this->conn = null;
    }

    // Add this method to get the connection for the controller to use
    public function getConnection()
    {
        return $this->conn;
    }

    // Add getter for last error
    public function getLastError()
    {
        return $this->lastError;
    }
}