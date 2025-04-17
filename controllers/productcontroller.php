<?php
/**
 * Product Controller
 * 
 * Handles all product-related operations including CRUD, categorization, 
 * status management, and image uploads.
 */

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../config/constants.php';

class ProductController
{
    /** @var Product Product model instance */
    private $product;
    
    /** @var string|null Last error message */
    private $lastError = null;
    
    /** @var array Valid unit types for products */
    private $validUnitTypes = ['piece', 'kilogram', 'gram', 'liter', 'milliliter', 'bunch', 'sack', 'pack'];
    
    /** @var string Base upload directory */
    private $uploadBaseDir;
    
    /** @var string Upload directory for product images */
    private $productImageDir;
    
    /** @var int Maximum allowed file size in bytes (5MB) */
    private const MAX_FILE_SIZE = 5242880; // 5MB
    
    /** @var string Standard image path format for database */
    private const IMAGE_PATH_FORMAT = 'uploads/products/%s';
    
    /**
     * Constructor initializes model and paths
     */
    public function __construct()
    {
        $this->product = new Product();
        
        // Define upload paths using constants or calculate them
        $this->uploadBaseDir = defined('UPLOAD_BASE_DIR') ? UPLOAD_BASE_DIR : dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'public';
        $this->productImageDir = $this->uploadBaseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
        
        // Log the actual path for debugging
        error_log("Product image directory: " . $this->productImageDir);
    }
    
    /**
     * Check if the current request is an AJAX request
     * 
     * @return bool
     */
    private function checkAjaxRequest()
    {
        return defined('AJAX_REQUEST') && AJAX_REQUEST === true;
    }
    
    /**
     * Validate product ID
     * 
     * @param mixed $id The product ID to validate
     * @return bool Whether the ID is valid
     */
    private function validateProductId($id) {
        return !empty($id) && is_numeric($id) && $id > 0;
    }
    
    /**
     * Set the last error message
     * 
     * @param string $message Error message
     * @return void
     */
    private function setLastError($message)
    {
        $this->lastError = $message;
        error_log("ProductController Error: " . $message);
    }
    
    /**
     * Get the last error message
     * 
     * @return string|null Last error message
     */
    public function getLastError()
    {
        return $this->lastError;
    }
    
    /**
     * Get all products with farmer details
     * 
     * @return array List of products with details
     */
    public function getAllProducts()
    {
        try {
            if (!$this->product) {
                throw new Exception("Product model not initialized");
            }
            $products = $this->product->getAllProductsWithDetails();
            
            // Normalize image paths
            foreach ($products as &$product) {
                if (!empty($product['image'])) {
                    $product['image'] = $this->normalizeImagePath($product['image']);
                }
            }
            
            error_log("ProductController: Got " . count($products) . " products from model");
            return $products;
        } catch (Exception $e) {
            $this->setLastError("Error fetching products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all farmers
     * 
     * @return array List of farmers
     */
    public function getAllFarmers()
    {
        try {
            return $this->product->getAllFarmers();
        } catch (Exception $e) {
            $this->setLastError("Error fetching farmers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Show the add product form
     * 
     * @return void
     */
    public function showAddForm()
    {
        try {
            // Don't include views for AJAX requests
            if ($this->checkAjaxRequest()) {
                return true;
            }
            
            // Get all farmers for the dropdown
            $farmers = $this->getAllFarmers();
            
            // Get categories with optimized memory approach
            $categories = $this->getCategoriesOptimized();
            
            // Pass valid unit types to the view
            $unitTypes = $this->validUnitTypes;
            
            // Include the add product view
            include_once '../../views/admin/add-product.php';
            exit;
        } catch (Exception $e) {
            $this->setLastError("Error showing add form: " . $e->getMessage());
            $this->redirectWithError('../../views/admin/manage-products.php', 'Error loading product form');
        }
    }
    
    /**
     * Show the edit product form
     * 
     * @param int $product_id Product ID to edit
     * @return void
     */
    public function showEditForm($product_id)
    {
        try {
            // Don't include views for AJAX requests
            if ($this->checkAjaxRequest()) {
                return true;
            }
            
            if (!$this->validateProductId($product_id)) {
                throw new Exception("Invalid product ID");
            }
            
            // Get the product details
            $product = $this->getProductWithDetails($product_id);
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            // Get all farmers for the dropdown
            $farmers = $this->getAllFarmers();
            
            // Get categories with optimized memory approach
            $categories = $this->getCategoriesOptimized();
            
            // Pass valid unit types to the view
            $unitTypes = $this->validUnitTypes;
            
            // Include the edit product view
            include_once '../../views/admin/edit-product.php';
            exit;
        } catch (Exception $e) {
            $this->setLastError("Error showing edit form: " . $e->getMessage());
            $this->redirectWithError('../../views/admin/manage-products.php', 'Error loading product form');
        }
    }
    
    /**
     * Add a new product
     * 
     * @param array $data Product data
     * @return int|false Product ID on success, false on failure
     */
    public function addProduct($data) {
        try {
            // Validate input data
            $errors = $this->validateProductData($data);
            if (!empty($errors)) {
                throw new Exception("Validation failed: " . implode(", ", $errors));
            }

            // Begin transaction
            $this->product->getConnection()->beginTransaction();

            // Handle image upload
            $image_path = null;
            if (isset($data['image']) && !empty($data['image'])) {
                $image_path = $this->handleImageUpload($data['image']);
                if ($image_path === false) {
                    throw new Exception("Image upload failed: " . $this->getLastError());
                }
            }

            // Set default unit type if not provided or invalid
            $unitType = !empty($data['unit_type']) && in_array($data['unit_type'], $this->validUnitTypes) 
                ? $data['unit_type'] 
                : 'piece';

            // Prepare product data
            $productData = [
                'name' => $this->sanitizeInput($data['name']),
                'description' => $this->sanitizeInput($data['description'] ?? ''),
                'price' => $this->sanitizeFloat($data['price']),
                'stock' => $this->sanitizeInt($data['stock']),
                'farmer_id' => $this->sanitizeInt($data['farmer_id']),
                'unit_type' => $unitType,
                'image' => $image_path,
                'status' => 'pending'
            ];

            // Add product
            $product_id = $this->product->addProduct(
                $productData['name'],
                $productData['description'],
                $productData['price'],
                $productData['farmer_id'],
                $productData['stock'],
                $productData['image'],
                $productData['status'],
                $productData['unit_type']
            );

            if (!$product_id) {
                throw new Exception("Failed to add product");
            }

            // Assign category if provided
            if (!empty($data['category_id'])) {
                if (!$this->assignProductCategory($product_id, $data['category_id'])) {
                    throw new Exception("Failed to assign category");
                }
            }

            $this->product->getConnection()->commit();
            return $product_id;

        } catch (Exception $e) {
            if ($this->product->getConnection()->inTransaction()) {
                $this->product->getConnection()->rollBack();
            }
            $this->setLastError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate if farmer exists in the database
     * 
     * @param int $farmer_id Farmer ID to validate
     * @return bool Whether the farmer exists
     */
    private function validateFarmerId($farmer_id) 
    {
        if (empty($farmer_id) || !is_numeric($farmer_id)) {
            return false;
        }
        
        try {
            $stmt = $this->product->getConnection()->prepare(
                "SELECT 1 FROM users WHERE user_id = ? AND role_id = (SELECT role_id FROM roles WHERE role_name = 'Farmer')"
            );
            $stmt->execute([$farmer_id]);
            return $stmt->fetchColumn() ? true : false;
        } catch (Exception $e) {
            $this->setLastError("Error validating farmer ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Edit an existing product
     * 
     * @param int $id Product ID
     * @param string $name Product name
     * @param string $description Product description
     * @param float $price Product price
     * @param int $stock Product stock quantity
     * @param int $farmer_id Farmer ID
     * @param array $image New image file data (optional)
     * @param string $current_image Current image path (optional)
     * @param string $unit_type Unit type (optional)
     * @return bool Whether the operation succeeded
     */
    public function editProduct($id, $name, $description, $price, $stock, $farmer_id, $image, $current_image, $unit_type = null)
    {
        try {
            if (!$this->validateProductId($id)) {
                throw new Exception("Invalid product ID");
            }
            
            // Validate unit type if provided
            if ($unit_type !== null && !in_array($unit_type, $this->validUnitTypes)) {
                throw new Exception("Invalid unit type. Valid types: " . implode(', ', $this->validUnitTypes));
            }
            
            // Handle image upload
            $image_path = $current_image;
            if (!empty($current_image)) {
                $image_path = $this->normalizeImagePath($current_image);
            }
            
            if ($image && isset($image['tmp_name']) && $image['tmp_name']) {
                $uploaded_image = $this->handleImageUpload($image);
                if ($uploaded_image) {
                    $image_path = $uploaded_image;
                    error_log("New image uploaded: $image_path");
                } else {
                    throw new Exception("Failed to upload image: " . $this->getLastError());
                }
            }

            // Create product data array with sanitized inputs
            $productData = [];
            
            if ($name !== null) $productData['name'] = $this->sanitizeInput($name);
            if ($description !== null) $productData['description'] = $this->sanitizeInput($description);
            if ($price !== null) $productData['price'] = $this->sanitizeFloat($price);
            if ($stock !== null) $productData['stock'] = $this->sanitizeInt($stock);
            if ($farmer_id !== null) $productData['farmer_id'] = $this->sanitizeInt($farmer_id);
            if ($image_path !== null) $productData['image'] = $image_path;
            if ($unit_type !== null) $productData['unit_type'] = $unit_type;

            // Update product
            return $this->product->updateProductFromArray($id, $productData);
        } catch (Exception $e) {
            $this->setLastError("Edit product error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign product to category
     * 
     * @param int $product_id Product ID
     * @param int $category_id Category ID
     * @return bool Whether the operation succeeded
     */
    public function assignProductCategory($product_id, $category_id)
    {
        try {
            return $this->product->assignProductCategory($product_id, $category_id);
        } catch (Exception $e) {
            $this->setLastError("Error assigning category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update product category
     * 
     * @param int $product_id Product ID
     * @param int $category_id Category ID
     * @return bool Whether the operation succeeded
     */
    public function updateProductCategory($product_id, $category_id)
    {
        try {
            if (!$this->validateProductId($product_id) || empty($category_id) || !is_numeric($category_id)) {
                throw new Exception("Invalid product or category ID");
            }
            
            $db = $this->product->getConnection();
            $db->beginTransaction();
            
            // Check if category exists to prevent foreign key constraint violations
            $checkCategoryStmt = $db->prepare("SELECT category_id FROM productcategories WHERE category_id = ?");
            $checkCategoryStmt->execute([$category_id]);
            if (!$checkCategoryStmt->fetch()) {
                throw new Exception("Category ID does not exist");
            }
            
            // Delete existing category associations
            $deleteStmt = $db->prepare("DELETE FROM productcategorymapping WHERE product_id = ?");
            $deleteStmt->execute([$product_id]);
            
            // Add new category association
            $insertStmt = $db->prepare("INSERT INTO productcategorymapping (product_id, category_id) VALUES (?, ?)");
            $result = $insertStmt->execute([$product_id, $category_id]);
            
            if ($result) {
                $db->commit();
                return true;
            } else {
                $db->rollBack();
                throw new Exception("Failed to insert new category mapping");
            }
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            // Handle specific constraint violation errors
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                if (strpos($e->getMessage(), 'productcategories') !== false) {
                    $this->setLastError("Invalid category selected. Category does not exist.");
                } else {
                    $this->setLastError("Database constraint violation. Please try again.");
                }
            } else {
                $this->setLastError("Database error: " . $e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->setLastError("Error updating product category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update product status and store notes
     * 
     * @param int $product_id Product ID
     * @param string $status New status
     * @param string $notes Optional notes about the status change
     * @param int|null $user_id User ID making the change
     * @return bool Whether the operation succeeded
     */
    public function updateProductStatus($product_id, $status, $notes = '', $user_id = null) {
        try {
            if (!$this->validateProductId($product_id)) {
                throw new Exception("Invalid product ID");
            }
            
            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status. Valid statuses: " . implode(', ', $validStatuses));
            }
            
            // Get database connection
            $db = $this->product->getConnection();
            
            // Start transaction
            $db->beginTransaction();
            
            // First, get the current status
            $stmt = $db->prepare("SELECT status FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldStatus = $result ? $result['status'] : null;
            
            // Update product status
            $stmt = $db->prepare("UPDATE products SET status = ? WHERE product_id = ?");
            $result = $stmt->execute([$status, $product_id]);
            
            // If status update successful and we have a user ID, log the activity
            if ($result && $user_id) {
                // Get user role to determine the log message format
                $roleStmt = $db->prepare(
                    "SELECT r.role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE u.user_id = ?"
                );
                $roleStmt->execute([$user_id]);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                $role = $roleResult ? $roleResult['role_name'] : 'User';
                
                // Format the action message with notes if provided
                $notes = $this->sanitizeInput($notes);
                $notesPart = !empty($notes) ? ". Notes: $notes" : "";
                $action = "$role " . strtolower($status) . "ed product ID: $product_id$notesPart";
                
                // Insert into activitylogs table
                $logStmt = $db->prepare(
                    "INSERT INTO activitylogs (user_id, action, action_date) 
                     VALUES (?, ?, CURRENT_TIMESTAMP)"
                );
                $logStmt->execute([$user_id, $action]);
            }
            
            $db->commit();
            return $result;
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->setLastError("Database error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->setLastError("Error updating product status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a product
     * 
     * @param int $id Product ID
     * @return bool Whether the operation succeeded
     */
    public function deleteProduct($id)
    {
        try {
            if (!$this->validateProductId($id)) {
                throw new Exception("Invalid product ID");
            }
            
            // Get product details before deleting
            $product = $this->product->getProductById($id);
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            // Check for image and delete if it exists
            if (!empty($product['image'])) {
                $normalizedPath = $this->normalizeImagePath($product['image']);
                $fullPath = $this->uploadBaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
                
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                    error_log("Deleted product image: $fullPath");
                } else {
                    error_log("Could not find image to delete at: $fullPath");
                }
            }
            
            // Delete the product from database
            return $this->product->deleteProduct($id);
        } catch (Exception $e) {
            $this->setLastError("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get product by ID
     * 
     * @param int $id Product ID
     * @return array|null Product data or null if not found
     */
    public function getProductById($id)
    {
        try {
            if (!$this->validateProductId($id)) {
                return null;
            }
            
            $product = $this->product->getProductById($id);
            
            // Normalize image path if it exists
            if ($product && !empty($product['image'])) {
                $product['image'] = $this->normalizeImagePath($product['image']);
            }
            
            // Also get the category information
            if ($product) {
                $category = $this->getProductCategories($id);
                if ($category) {
                    $product['category_id'] = $category['category_id'];
                    $product['category'] = $category['category_name'];
                }
            }
            
            return $product;
        } catch (Exception $e) {
            $this->setLastError("Error fetching product: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Alias for getProductById
     * 
     * @param int $id Product ID
     * @return array|null Product data or null if not found
     */
    public function getProduct($id) {
        return $this->getProductById($id);
    }
    
    /**
     * Update product with all details
     * 
     * @param int $id Product ID
     * @param array $data Product data
     * @return bool Whether the operation succeeded
     */
    public function updateProduct($id, $data) {
        try {
            if (!$this->validateProductId($id)) {
                throw new Exception("Invalid product ID");
            }

            // Validate input data
            $errors = $this->validateProductData($data);
            if (!empty($errors)) {
                throw new Exception("Validation failed: " . implode(", ", $errors));
            }

            // Begin transaction - IMPROVED TRANSACTION HANDLING
            $db = $this->product->getConnection();
            $transactionStarted = false;
            
            if (!$db->inTransaction()) {
                $db->beginTransaction();
                $transactionStarted = true;
            }

            // Handle image upload
            if (isset($data['image']) && is_array($data['image']) && !empty($data['image']['tmp_name'])) {
                $uploadedImage = $this->handleImageUpload($data['image']);
                if ($uploadedImage) {
                    $data['image'] = $uploadedImage;
                } else {
                    throw new Exception("Failed to upload image: " . $this->getLastError());
                }
            } else if (!empty($data['current_image'])) {
                // Normalize the current image path
                $data['image'] = $this->normalizeImagePath($data['current_image']);
            }
            
            // Set default unit type if not provided or invalid
            $unitType = !empty($data['unit_type']) && in_array($data['unit_type'], $this->validUnitTypes) 
                ? $data['unit_type'] 
                : 'piece';

            // Prepare update data matching database columns
            $updateData = [
                'name' => $this->sanitizeInput($data['name']),
                'description' => $this->sanitizeInput($data['description'] ?? ''),
                'price' => $this->sanitizeFloat($data['price']),
                'stock' => $this->sanitizeInt($data['stock']),
                'farmer_id' => $this->sanitizeInt($data['farmer_id']),
                'unit_type' => $unitType,
                'image' => $data['image'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Update product
            if (!$this->product->updateProductFromArray($id, $updateData)) {
                throw new Exception($this->product->getLastError() ?: "Failed to update product");
            }

            // Update category mapping
            if (isset($data['category_id']) && !empty($data['category_id'])) {
                // Check if updateProductCategory needs to be updated or use a separate implementation
                // that's transaction-aware
                $categoryResult = $this->updateProductCategoryInTransaction($id, $data['category_id'], $db);
                if (!$categoryResult) {
                    throw new Exception("Failed to update product category: " . $this->getLastError());
                }
            }

            // Only commit if we started the transaction
            if ($transactionStarted) {
                $db->commit();
            }
            return true;

        } catch (Exception $e) {
            // Only rollback if we started the transaction
            if (isset($db) && isset($transactionStarted) && $transactionStarted && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->setLastError($e->getMessage());
            return false;
        }
    }

    /**
     * Update product category within an existing transaction
     * 
     * @param int $product_id Product ID
     * @param int $category_id Category ID
     * @param PDO $db Database connection
     * @return bool Whether the operation succeeded
     */
    private function updateProductCategoryInTransaction($product_id, $category_id, $db)
    {
        try {
            if (!$this->validateProductId($product_id) || empty($category_id) || !is_numeric($category_id)) {
                throw new Exception("Invalid product or category ID");
            }
            
            // Check if category exists to prevent foreign key constraint violations
            $checkCategoryStmt = $db->prepare("SELECT category_id FROM productcategories WHERE category_id = ?");
            $checkCategoryStmt->execute([$category_id]);
            if (!$checkCategoryStmt->fetch()) {
                throw new Exception("Category ID does not exist");
            }
            
            // Delete existing category associations
            $deleteStmt = $db->prepare("DELETE FROM productcategorymapping WHERE product_id = ?");
            $deleteStmt->execute([$product_id]);
            
            // Add new category association
            $insertStmt = $db->prepare("INSERT INTO productcategorymapping (product_id, category_id) VALUES (?, ?)");
            $result = $insertStmt->execute([$product_id, $category_id]);
            
            if (!$result) {
                throw new Exception("Failed to insert new category mapping: " . implode(', ', $insertStmt->errorInfo()));
            }
            
            return true;
        } catch (PDOException $e) {
            // Handle specific constraint violation errors
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                if (strpos($e->getMessage(), 'productcategories') !== false) {
                    $this->setLastError("Invalid category selected. Category does not exist.");
                } else {
                    $this->setLastError("Database constraint violation. Please try again.");
                }
            } else {
                $this->setLastError("Database error: " . $e->getMessage());
            }
            return false;
        } catch (Exception $e) {
            $this->setLastError("Error updating product category: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Normalize image path to ensure consistent format: uploads/products/filename.ext
     * 
     * @param string $path Image path
     * @return string Normalized path
     */
    private function normalizeImagePath($path)
    {
        if (empty($path)) {
            return null;
        }
        
        // Remove any leading slashes
        $path = ltrim($path, '/');
        
        // If path already has correct format, return it
        if (preg_match('#^uploads/products/[^/]+$#', $path)) {
            return $path;
        }
        
        // Extract just the filename
        $filename = basename($path);
        
        // Reconstruct the path in the correct format
        return sprintf(self::IMAGE_PATH_FORMAT, $filename);
    }
    
    /**
     * Improved image upload handling
     * 
     * @param array $image Image data from $_FILES
     * @return string|false Relative path to uploaded image or false on failure
     */
    private function handleImageUpload($image)
    {
        try {
            if (!is_array($image)) {
                throw new Exception("Invalid image data: not an array");
            }
            
            // Check for upload errors
            if (isset($image['error']) && $image['error'] !== UPLOAD_ERR_OK) {
                $uploadErrorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive specified in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
                ];
                $errorMessage = $uploadErrorMessages[$image['error']] ?? 'Unknown upload error';
                throw new Exception("Upload error: " . $errorMessage);
            }
            
            // Check if the temp file exists and is readable
            if (!isset($image['tmp_name']) || !is_readable($image['tmp_name'])) {
                throw new Exception("Temp file doesn't exist or isn't readable");
            }
            
            // Ensure upload directory exists
            if (!$this->ensureDirectoryExists($this->productImageDir)) {
                throw new Exception("Failed to create or access upload directory");
            }
            
            // Generate a unique filename with sanitization
            $originalName = basename($image['name']);
            $originalName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
            $filename = uniqid() . '_' . $originalName;
            
            // IMPORTANT: Store database path in consistent format
            $relative_path = sprintf(self::IMAGE_PATH_FORMAT, $filename);
            
            // Physical file path for actual file operation
            $target_file = $this->productImageDir . DIRECTORY_SEPARATOR . $filename;
            
            // Log the path for debugging
            error_log("Generated image path for DB: " . $relative_path);
            error_log("Physical file path: " . $target_file);
            
            // Basic validation checks
            $this->validateImageFile($image);
            
            // Move the uploaded file
            if (!move_uploaded_file($image['tmp_name'], $target_file)) {
                $error = error_get_last();
                error_log("Failed to move uploaded file. Error: " . ($error ? $error['message'] : 'unknown'));
                
                // Try alternative approach
                if (!copy($image['tmp_name'], $target_file)) {
                    throw new Exception("Failed to save uploaded file");
                }
            }
            
            // Verify the final file exists
            if (!file_exists($target_file)) {
                throw new Exception("File does not exist after upload attempt");
            }
            
            // Ensure the file is readable
            chmod($target_file, 0644);
            
            return $relative_path;
        } catch (Exception $e) {
            $this->setLastError("Image upload error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure directory exists and is writable
     * 
     * @param string $directory Directory path
     * @return bool Whether the directory exists and is writable
     */
    private function ensureDirectoryExists($directory)
    {
        if (file_exists($directory)) {
            if (!is_writable($directory)) {
                @chmod($directory, 0777);
                if (!is_writable($directory)) {
                    $this->setLastError("Directory is not writable: " . $directory);
                    return false;
                }
            }
            return true;
        }
        
        if (!@mkdir($directory, 0777, true)) {
            $this->setLastError("Failed to create directory: " . $directory);
            return false;
        }
        
        @chmod($directory, 0777);
        return true;
    }
    
    /**
     * Validate image file
     * 
     * @param array $image Image data
     * @throws Exception If validation fails
     */
    private function validateImageFile($image)
    {
        // Check file size
        if ($image['size'] > self::MAX_FILE_SIZE) {
            throw new Exception("File size exceeds the maximum allowed size of " . (self::MAX_FILE_SIZE / 1048576) . "MB");
        }
        
        // Check if it's a valid image
        $imageInfo = @getimagesize($image['tmp_name']);
        if (!$imageInfo) {
            throw new Exception("File is not a valid image");
        }
        
        // Check image type
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            throw new Exception("Only JPG, PNG, GIF, and WEBP images are allowed");
        }
    }
    
    /**
     * Process the action parameter and call the appropriate method
     * 
     * @return void
     */
    public function processRequest()
    {
        try {
            // Don't redirect if this is an AJAX request
            if ($this->checkAjaxRequest()) {
                return;
            }
            
            // Check if action parameter is set
            if (!isset($_GET['action'])) {
                $this->redirectToManageProducts();
                return;
            }
            
            $action = $_GET['action'];
            
            switch ($action) {
                case 'showAddForm':
                    $this->showAddForm();
                    break;
                
                case 'showEditForm':
                    if (!isset($_GET['id'])) {
                        throw new Exception("Product ID is required");
                    }
                    $this->showEditForm((int)$_GET['id']);
                    break;
                
                case 'add':
                    $this->processAddRequest();
                    break;
                
                case 'update':
                    $this->processUpdateRequest();
                    break;
                
                case 'delete':
                    $this->processDeleteRequest();
                    break;
                    
                case 'updateStatus':
                    $this->processStatusUpdateRequest();
                    break;
                    
                default:
                    throw new Exception("Invalid action specified");
            }
        } catch (Exception $e) {
            $this->setLastError($e->getMessage());
            $this->redirectWithError('../../views/admin/manage-products.php', $e->getMessage());
        }
    }
    
    /**
     * Process add product request
     * 
     * @return void
     */
    private function processAddRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method");
        }
        
        // Sanitize and prepare input data
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'farmer_id' => $_POST['farmer_id'] ?? null,
            'stock' => $_POST['stock'] ?? 0,
            'category_id' => $_POST['category_id'] ?? null,
            'unit_type' => $_POST['unit_type'] ?? 'piece',
            'image' => $_FILES['image'] ?? null
        ];
        
        // Add product
        $result = $this->addProduct($data);
        
        // Return JSON response for AJAX request
        $this->sendJsonResponse([
            'success' => $result ? true : false,
            'message' => $result ? 'Product added successfully' : 'Failed to add product: ' . $this->getLastError(),
            'product_id' => $result ?: null
        ]);
    }
    
    /**
     * Process update product request
     * 
     * @return void
     */
    private function processUpdateRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
            throw new Exception("Invalid request or missing product ID");
        }
        
        // Sanitize and prepare input data
        $product_id = (int)$_POST['product_id'];
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock' => $_POST['stock'] ?? 0,
            'farmer_id' => $_POST['farmer_id'] ?? null,
            'category_id' => $_POST['category_id'] ?? null,
            'unit_type' => $_POST['unit_type'] ?? 'piece',
            'current_image' => $_POST['current_image'] ?? '',
            'image' => $_FILES['image'] ?? null
        ];
        
        // Update product
        $result = $this->updateProduct($product_id, $data);
        
        // Return JSON response for AJAX request
        $this->sendJsonResponse([
            'success' => $result ? true : false,
            'message' => $result ? 'Product updated successfully' : 'Failed to update product: ' . $this->getLastError()
        ]);
    }
    
    /**
     * Process delete product request
     * 
     * @return void
     */
    private function processDeleteRequest()
    {
        if (!isset($_GET['id'])) {
            throw new Exception("Product ID is required");
        }
        
        $id = (int)$_GET['id'];
        $result = $this->deleteProduct($id);
        
        if ($result) {
            $this->redirectWithSuccess('../../views/admin/manage-products.php', 'Product deleted successfully');
        } else {
            $this->redirectWithError('../../views/admin/manage-products.php', 'Failed to delete product: ' . $this->getLastError());
        }
    }
    
    /**
     * Process status update request
     * 
     * @return void
     */
    private function processStatusUpdateRequest()
    {
        if (!isset($_GET['id']) || !isset($_GET['status'])) {
            throw new Exception("Product ID and status are required");
        }
        
        $id = (int)$_GET['id'];
        $status = $_GET['status'];
        $notes = $_GET['notes'] ?? '';
        $user_id = $_SESSION['user_id'] ?? null;
        
        $result = $this->updateProductStatus($id, $status, $notes, $user_id);
        
        if ($result) {
            $this->redirectWithSuccess('../../views/admin/manage-products.php', 'Product status updated');
        } else {
            $this->redirectWithError('../../views/admin/manage-products.php', 'Failed to update status: ' . $this->getLastError());
        }
    }
    
    /**
     * Send JSON response
     * 
     * @param array $data Response data
     * @return void
     */
    private function sendJsonResponse($data)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
        }
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirect with success message
     * 
     * @param string $url URL to redirect to
     * @param string $message Success message
     * @return void
     */
    private function redirectWithSuccess($url, $message)
    {
        if ($this->checkAjaxRequest()) {
            $this->sendJsonResponse(['success' => true, 'message' => $message]);
            return;
        }
        
        header("Location: $url?success=" . urlencode($message));
        exit;
    }
    
    /**
     * Redirect with error message
     * 
     * @param string $url URL to redirect to
     * @param string $message Error message
     * @return void
     */
    private function redirectWithError($url, $message)
    {
        if ($this->checkAjaxRequest()) {
            $this->sendJsonResponse(['success' => false, 'message' => $message]);
            return;
        }
        
        header("Location: $url?error=" . urlencode($message));
        exit;
    }
    
    /**
     * Redirect to manage products page
     * 
     * @return void
     */
    private function redirectToManageProducts()
    {
        if ($this->checkAjaxRequest()) {
            return;
        }
        
        header("Location: ../../views/admin/manage-products.php");
        exit;
    }
    
    /**
     * Close the product model connection
     * 
     * @return void
     */
    public function close()
    {
        if ($this->product) {
            $this->product->close();
        }
    }
    
    /**
     * Get total product count
     * 
     * @return int Product count
     */
    public function getProductCount() {
        try {
            return $this->product->getProductCount();
        } catch (Exception $e) {
            $this->setLastError("Error in getProductCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get product count by status
     * 
     * @param string $status Status to count
     * @return int Product count
     */
    public function getProductCountByStatus($status) {
        try {
            // Validate status
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            return $this->product->getProductCountByStatus($status);
        } catch (Exception $e) {
            $this->setLastError("Error in getProductCountByStatus: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get low stock products
     * 
     * @param int $threshold Stock threshold
     * @return array Low stock products
     */
    public function getLowStockProducts($threshold = 10) {
        try {
            $products = $this->product->getLowStockProducts($threshold);
            
            // Normalize image paths in results
            foreach ($products as &$product) {
                if (!empty($product['image'])) {
                    $product['image'] = $this->normalizeImagePath($product['image']);
                }
            }
            
            return $products;
        } catch (Exception $e) {
            $this->setLastError("Error in getLowStockProducts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Optimized method to get all categories
     * 
     * @return array Categories
     */
    public function getCategoriesOptimized() {
        try {
            // Use direct query instead of calling Product model method (which was causing memory issues)
            $stmt = $this->product->getConnection()->prepare(
                "SELECT category_id, category_name FROM productcategories ORDER BY category_name"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->setLastError("Error in getCategoriesOptimized: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get product categories
     * 
     * @param int $product_id Product ID
     * @return array|null Categories or null if none
     */
    public function getProductCategories($product_id) {
        try {
            if (!$this->validateProductId($product_id)) {
                return null;
            }
            
            // First check if the product exists
            $product = $this->product->getProductById($product_id);
            if (!$product) {
                throw new Exception("Product not found");
            }

            $sql = "SELECT pc.category_id, pc.category_name 
                    FROM productcategories pc 
                    JOIN productcategorymapping pcm ON pc.category_id = pcm.category_id 
                    WHERE pcm.product_id = ?";
            
            $stmt = $this->product->getConnection()->prepare($sql);
            $stmt->execute([$product_id]);
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no categories found, return null
            if (empty($categories)) {
                return null;
            }
            
            // For backwards compatibility, if only one category, return it directly
            if (count($categories) === 1) {
                return $categories[0];
            }
            
            return $categories;
        } catch (Exception $e) {
            $this->setLastError("Error getting product categories: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all available unit types
     * 
     * @return array Unit types
     */
    public function getUnitTypes() {
        return $this->validUnitTypes;
    }
    
    /**
     * Get product with all details including category
     * 
     * @param int $product_id Product ID
     * @return array|null Product details or null if not found
     */
    public function getProductWithDetails($product_id) {
        try {
            if (!$this->validateProductId($product_id)) {
                return null;
            }
            
            // Get the basic product information
            $product = $this->product->getProductById($product_id);
            
            if (!$product) {
                return null;
            }
            
            // Normalize image path if it exists
            if (!empty($product['image'])) {
                $product['image'] = $this->normalizeImagePath($product['image']);
            }
            
            // Get the farmer information if available
            if (!empty($product['farmer_id'])) {
                $sql = "SELECT username as farmer_name FROM users WHERE user_id = ?";
                $stmt = $this->product->getConnection()->prepare($sql);
                $stmt->execute([$product['farmer_id']]);
                $farmer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($farmer) {
                    $product['farmer_name'] = $farmer['farmer_name'];
                }
            }
            
            // Get all categories for this product
            $categories = $this->getProductCategories($product_id);
            
            // Add categories to the product data
            if (is_array($categories) && isset($categories['category_id'])) {
                // Single category case (backward compatibility)
                $product['category_id'] = $categories['category_id'];
                $product['category_name'] = $categories['category_name'];
            } else if (is_array($categories) && !empty($categories)) {
                // Multiple categories case
                $product['categories'] = $categories;
                
                // For convenience in forms, provide the first category_id
                $product['category_id'] = $categories[0]['category_id'];
            }
            
            // Ensure unit_type is set
            if (empty($product['unit_type'])) {
                $product['unit_type'] = 'piece'; // Default value
            }
            
            return $product;
        } catch (Exception $e) {
            $this->setLastError("Error in getProductWithDetails: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate product data
     * 
     * @param array $data Product data
     * @return array Validation errors
     */
    private function validateProductData($data) {
        $errors = [];
        
        // Required fields validation
        if (empty($data['name'])) $errors[] = "Product name is required";
        if (!isset($data['price']) || $data['price'] < 0) $errors[] = "Valid price is required";
        if (!isset($data['stock']) || $data['stock'] < 0) $errors[] = "Valid stock quantity is required";
        
        // Validate unit_type
        if (!empty($data['unit_type']) && !in_array($data['unit_type'], $this->validUnitTypes)) {
            $errors[] = "Invalid unit type. Must be one of: " . implode(', ', $this->validUnitTypes);
        }

        // Validate status - matches database enum
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = "Invalid status. Must be one of: " . implode(', ', $validStatuses);
        }
        
        // Validate farmer_id exists if provided
        if (!empty($data['farmer_id']) && !$this->validateFarmerId($data['farmer_id'])) {
            $errors[] = "Invalid farmer selected. Farmer does not exist in the database.";
        }

        return $errors;
    }
    
    /**
     * Fix any empty status values in orders table
     * 
     * @return int Number of rows affected
     */
    public function fixEmptyOrderStatuses() {
        try {
            $db = $this->product->getConnection();
            $stmt = $db->prepare("UPDATE orders SET order_status = 'pending' WHERE order_status = ''");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->setLastError("Error fixing empty order statuses: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Sanitize string input
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    private function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    
    /**
     * Sanitize integer input
     * 
     * @param mixed $input Input to sanitize
     * @return int Sanitized integer
     */
    private function sanitizeInt($input) {
        return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : 0;
    }
    
    /**
     * Sanitize float input
     * 
     * @param mixed $input Input to sanitize
     * @return float Sanitized float
     */
    private function sanitizeFloat($input) {
        return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? (float)$input : 0;
    }

    public function getAllProductsWithDetails()
    {
        try {
            if (!$this->product) {
                throw new Exception("Product model not initialized");
            }
            return $this->product->getAllProductsWithDetails();
        } catch (Exception $e) {
            $this->setLastError("Error fetching products with details: " . $e->getMessage());
            return [];
        }
    }
}

// Only instantiate if this is the main file being executed
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    $controller = new ProductController();
    $controller->processRequest();
}
