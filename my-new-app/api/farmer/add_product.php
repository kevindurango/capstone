<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database connection
require_once '../config/database.php';

/**
 * Constants for image handling
 */
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Updated image path format to use public directory
define('IMAGE_PATH_FORMAT', 'public/uploads/products/%s');
define('DEFAULT_UPLOAD_DIR', '../../public/uploads/products/');
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']);

/**
 * Generate a unique filename for uploaded images
 * 
 * @param string $extension File extension
 * @return string Unique filename
 */
function generateUniqueFilename($extension) {
    return 'product_' . uniqid() . '.' . strtolower($extension);
}

/**
 * Normalize image path to ensure consistent format
 * 
 * @param string $path Image path
 * @return string|null Normalized path or null if empty
 */
function normalizeImagePath($path) {
    if (empty($path)) {
        return null;
    }
    
    // Remove any leading slashes
    $path = ltrim($path, '/');
    
    // If path already has correct format, return it
    if (preg_match('#^public/uploads/products/[^/]+$#', $path)) {
        return $path;
    }
    
    // Extract just the filename
    $filename = basename($path);
    
    // Reconstruct the path in the correct format
    return sprintf(IMAGE_PATH_FORMAT, $filename);
}

/**
 * Validate unit type
 * 
 * @param string $unit_type Unit type to validate
 * @return string Valid unit type or default
 */
function validateUnitType($unit_type) {
    $valid_types = [
        "kilogram", "gram", "piece", "bunch", "bundle", "liter", 
        "milliliter", "bag", "sack", "box", "can", "bottle", "dozen", "container"
    ];
    
    if (in_array(strtolower($unit_type), $valid_types)) {
        return strtolower($unit_type);
    }
    
    // Default to kilogram if invalid
    return "kilogram";
}

// Response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Start timing for performance logging
$startTime = microtime(true);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = null;
    $target_file = null;
    $image_path = null;
    
    try {
        // Debug log to capture all incoming data
        error_log("[ADD_PRODUCT] Raw POST data: " . print_r($_POST, true));
        error_log("[ADD_PRODUCT] Raw FILES data: " . print_r($_FILES, true));
        
        // Check required fields
        if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
            throw new Exception("User ID is required");
        }
        
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            throw new Exception("Product name is required");
        }
        
        if (!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
            throw new Exception("Valid price is required");
        }
        
        if (!isset($_POST['stock']) || !is_numeric($_POST['stock']) || $_POST['stock'] < 0) {
            throw new Exception("Valid stock quantity is required");
        }
        
        // Special handling for unit_type
        if (!isset($_POST['unit_type']) || empty($_POST['unit_type'])) {
            error_log("[ADD_PRODUCT] Unit type is missing or empty, defaulting to kilogram");
            $_POST['unit_type'] = "kilogram";
        } else {
            error_log("[ADD_PRODUCT] Received unit_type: " . $_POST['unit_type']);
        }
        
        // More flexible category validation
        $categories = [];
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $categories = $_POST['categories'];
        } else if (isset($_POST['categories[]']) && is_array($_POST['categories[]'])) {
            $categories = $_POST['categories[]']; 
        } else if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            $categories = [$_POST['category_id']];
        }
        
        if (empty($categories)) {
            throw new Exception("At least one category is required");
        }
        
        // Get parameters from POST request with simple sanitization
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
        $description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8') : '';
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $stock = filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT);
        $unit_type = validateUnitType(htmlspecialchars(trim($_POST['unit_type']), ENT_QUOTES, 'UTF-8'));
        
        // Get barangay and field information if provided
        $barangay_id = null;
        $field_id = null;
        
        if (isset($_POST['barangay_id']) && !empty($_POST['barangay_id'])) {
            $barangay_id = filter_var($_POST['barangay_id'], FILTER_SANITIZE_NUMBER_INT);
            error_log("[ADD_PRODUCT] Received barangay_id: " . $barangay_id);
        }
        
        if (isset($_POST['field_id'])) {
            // Check if field_id is non-empty rather than just isset
            if (!empty($_POST['field_id'])) {
                $field_id = filter_var($_POST['field_id'], FILTER_SANITIZE_NUMBER_INT);
                error_log("[ADD_PRODUCT] Received field_id: " . $field_id);
                
                // If field_id is provided, get its barangay_id to ensure consistency
                if ($field_id) {
                    $conn = getConnection();
                    $stmt = $conn->prepare("SELECT barangay_id, field_name FROM farmer_fields WHERE field_id = ? AND farmer_id = ?");
                    $stmt->bind_param("ii", $field_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $field_data = $result->fetch_assoc();
                        $barangay_id = $field_data['barangay_id']; // Override barangay_id with field's barangay
                        error_log("[ADD_PRODUCT] Updated barangay_id from field: " . $barangay_id . ", field name: " . $field_data['field_name']);
                    } else {
                        // Field doesn't exist or doesn't belong to farmer
                        error_log("[ADD_PRODUCT] Field ID $field_id not found or doesn't belong to farmer $user_id");
                        throw new Exception("Invalid field selected. Please choose a field that belongs to you.");
                    }
                }
            } else {
                // Explicitly set field_id to null when empty string is submitted
                $field_id = null;
                error_log("[ADD_PRODUCT] Field ID explicitly set to NULL");
            }
        }
        
        // Check for custom upload directory
        $upload_dir = DEFAULT_UPLOAD_DIR;
        if (isset($_POST['upload_directory']) && !empty($_POST['upload_directory'])) {
            // Sanitize the upload directory path
            $custom_dir = filter_var($_POST['upload_directory'], FILTER_SANITIZE_STRING);
            
            // Make sure it has the correct structure
            if (strpos($custom_dir, 'public/uploads/products/') !== false) {
                // If path is relative to server root (like public/uploads/products/)
                $upload_dir = '../../../' . $custom_dir;
            } else {
                // Default path with logging
                error_log("[ADD_PRODUCT] Using default upload directory: " . DEFAULT_UPLOAD_DIR);
            }
        }
        
        error_log("[ADD_PRODUCT] Using upload directory: " . $upload_dir);
        error_log("[ADD_PRODUCT] Validated unit_type: " . $unit_type);
        
        // Log the request for debugging
        error_log("[ADD_PRODUCT] Processing request from user $user_id: $name");
        
        // Get database connection
        $conn = getConnection();
        
        // Check if connection is successful
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Check if user is a farmer
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("User not found");
        }
        
        $user = $result->fetch_assoc();
        if ($user['role_id'] != 2) { // 2 is the role_id for 'Farmer'
            throw new Exception("Only farmers can add products");
        }
        
        // Handle image upload if present
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Debug the uploaded file information
            error_log("[ADD_PRODUCT] Image upload information:");
            error_log("[ADD_PRODUCT] Temp file: " . $_FILES['image']['tmp_name']);
            error_log("[ADD_PRODUCT] Size: " . $_FILES['image']['size']);
            error_log("[ADD_PRODUCT] Type: " . $_FILES['image']['type']);
            error_log("[ADD_PRODUCT] Name: " . $_FILES['image']['name']);
            
            // Validate file type - more lenient for mobile uploads
            $file_type = $_FILES['image']['type'];
            error_log("[ADD_PRODUCT] Image type received: " . $file_type);
            
            // More permissive mime type check for mobile uploads
            $allowed_types = ALLOWED_IMAGE_TYPES;
            $allowed_extensions = ['jpeg', 'jpg', 'png', 'gif'];
            
            $is_allowed = false;
            
            // Check by mime type
            if (in_array($file_type, $allowed_types)) {
                $is_allowed = true;
            }
            
            // Also check by file extension as fallback
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($file_extension, $allowed_extensions)) {
                $is_allowed = true;
                // Correct mime type based on extension if needed
                if ($file_extension == 'jpg' || $file_extension == 'jpeg') {
                    $file_type = 'image/jpeg';
                } else if ($file_extension == 'png') {
                    $file_type = 'image/png';
                } else if ($file_extension == 'gif') {
                    $file_type = 'image/gif';
                }
            }
            
            if (!$is_allowed) {
                throw new Exception("Invalid file type. Only JPEG, PNG, and GIF are allowed. Received type: " . $file_type);
            }
            
            // Validate file size
            if ($_FILES['image']['size'] > MAX_FILE_SIZE) {
                throw new Exception("File size too large. Maximum size is 5MB");
            }
            
            // For Android/React Native file uploads, sometimes getimagesize fails
            // So we'll be more lenient here and use file_exists and filesize instead
            if (!file_exists($_FILES['image']['tmp_name']) || filesize($_FILES['image']['tmp_name']) <= 0) {
                throw new Exception("Failed to receive uploaded file or file is empty");
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            if (empty($file_extension)) {
                // Default to jpeg if no extension found
                $file_extension = 'jpeg';
            }
            $new_filename = generateUniqueFilename($file_extension);
            
            // Calculate the absolute path to the upload directory based on server's document root
            $document_root = $_SERVER['DOCUMENT_ROOT'];
            // Remove any trailing slashes
            $document_root = rtrim($document_root, '/\\');
            
            // Build the absolute path to the upload directory
            $absolute_upload_dir = $document_root . '/capstone/public/uploads/products/';
            error_log("[ADD_PRODUCT] Document root: " . $document_root);
            error_log("[ADD_PRODUCT] Calculated upload directory: " . $absolute_upload_dir);
            
            // Fallback if the above doesn't work
            if (!is_dir($absolute_upload_dir)) {
                error_log("[ADD_PRODUCT] Primary path calculation failed, trying alternative method");
                $absolute_upload_dir = realpath(dirname(__FILE__) . '/../../..') . '/public/uploads/products/';
                error_log("[ADD_PRODUCT] Alternative upload directory: " . $absolute_upload_dir);
            }
            
            // Verify the absolute path
            if (!is_dir($absolute_upload_dir)) {
                error_log("[ADD_PRODUCT] Directory doesn't exist, creating: " . $absolute_upload_dir);
                if (!mkdir($absolute_upload_dir, 0777, true)) {
                    error_log("[ADD_PRODUCT] Failed to create directory: " . $absolute_upload_dir);
                    throw new Exception("Failed to create upload directory");
                }
                error_log("[ADD_PRODUCT] Directory created successfully");
            }
            
            // Ensure directory is writable
            if (!is_writable($absolute_upload_dir)) {
                error_log("[ADD_PRODUCT] Directory not writable, changing permissions: " . $absolute_upload_dir);
                chmod($absolute_upload_dir, 0777);
                if (!is_writable($absolute_upload_dir)) {
                    error_log("[ADD_PRODUCT] Directory still not writable: " . $absolute_upload_dir);
                    throw new Exception("Upload directory is not writable");
                }
            }
            
            // Set full target path for the file
            $target_file = $absolute_upload_dir . $new_filename;
            error_log("[ADD_PRODUCT] Target file: " . $target_file);
            
            // Try multiple methods to move the file
            $upload_success = false;
            
            // Method 1: move_uploaded_file (standard approach)
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                error_log("[ADD_PRODUCT] File moved successfully with move_uploaded_file");
                $upload_success = true;
            } else {
                $error = error_get_last();
                error_log("[ADD_PRODUCT] move_uploaded_file failed: " . ($error ? $error['message'] : 'unknown'));
                
                // Method 2: direct copy as fallback
                if (copy($_FILES['image']['tmp_name'], $target_file)) {
                    error_log("[ADD_PRODUCT] File copied successfully with copy()");
                    $upload_success = true;
                } else {
                    $error = error_get_last();
                    error_log("[ADD_PRODUCT] copy() failed: " . ($error ? $error['message'] : 'unknown'));
                    
                    // Method 3: read and write file contents as final fallback
                    try {
                        $file_content = file_get_contents($_FILES['image']['tmp_name']);
                        if ($file_content !== false) {
                            if (file_put_contents($target_file, $file_content) !== false) {
                                error_log("[ADD_PRODUCT] File written successfully with file_get_contents/file_put_contents");
                                $upload_success = true;
                            } else {
                                error_log("[ADD_PRODUCT] file_put_contents failed");
                            }
                        } else {
                            error_log("[ADD_PRODUCT] file_get_contents failed");
                        }
                    } catch (Exception $e) {
                        error_log("[ADD_PRODUCT] file_get_contents/file_put_contents exception: " . $e->getMessage());
                    }
                }
            }
            
            if (!$upload_success) {
                throw new Exception("Failed to upload image. Please try again.");
            }
            
            // Verify the file exists after upload
            if (!file_exists($target_file)) {
                error_log("[ADD_PRODUCT] File does not exist after upload: " . $target_file);
                throw new Exception("File does not exist after upload attempt");
            }
            
            // Check if the file has content
            if (filesize($target_file) <= 0) {
                error_log("[ADD_PRODUCT] Uploaded file is empty: " . $target_file);
                throw new Exception("Uploaded file is empty");
            }
            
            error_log("[ADD_PRODUCT] File successfully saved: " . $target_file . " (" . filesize($target_file) . " bytes)");
            
            // Ensure the file is readable
            chmod($target_file, 0644);
            
            // Set relative path for database storage
            $image_path = sprintf(IMAGE_PATH_FORMAT, $new_filename);
            
            // Log the path for debugging
            error_log("[ADD_PRODUCT] Generated image path for DB: " . $image_path);
        }
        
        // Insert product into products table
        error_log("[ADD_PRODUCT] Adding new product with unit_type: $unit_type");
        
        $stmt = $conn->prepare("INSERT INTO products (farmer_id, name, description, price, stock, unit_type, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issdiss", $user_id, $name, $description, $price, $stock, $unit_type, $image_path);
        
        if (!$stmt->execute()) {
            error_log("[ADD_PRODUCT] SQL Error: " . $stmt->error);
            throw new Exception("Failed to add product: " . $stmt->error);
        }
        
        $product_id = $conn->insert_id;
        
        // Insert product categories
        $categoryCount = 0;
        foreach ($categories as $category_id) {
            // Skip empty or non-numeric category IDs
            if (empty($category_id) || !is_numeric($category_id)) {
                continue;
            }
            
            $stmt = $conn->prepare("INSERT INTO productcategorymapping (product_id, category_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $product_id, $category_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add product category: " . $stmt->error);
            }
            $categoryCount++;
        }
        
        // Make sure at least one category was added
        if ($categoryCount === 0) {
            throw new Exception("No valid categories were provided");
        }
        
        // Handle barangay product mapping if barangay_id is provided
        if ($barangay_id) {
            error_log("[ADD_PRODUCT] Adding barangay product mapping with barangay_id: $barangay_id, product_id: $product_id, field_id: " . ($field_id === null ? "NULL" : $field_id));
            
            // Default values for new entries
            $estimated_production = 0;
            $production_unit = $unit_type;
            $year = date('Y');
            $season_id = 1; // Default season
            $planted_area = 0;
            $area_unit = 'hectare';
            
            // Insert with different SQL based on whether field_id is null
            if ($field_id === null) {
                $stmt = $conn->prepare("INSERT INTO barangay_products (barangay_id, product_id, estimated_production, production_unit, year, season_id, planted_area, area_unit, field_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)");
                $stmt->bind_param("iidsiiis", $barangay_id, $product_id, $estimated_production, $production_unit, $year, $season_id, $planted_area, $area_unit);
            } else {
                $stmt = $conn->prepare("INSERT INTO barangay_products (barangay_id, product_id, estimated_production, production_unit, year, season_id, planted_area, area_unit, field_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidsiiisi", $barangay_id, $product_id, $estimated_production, $production_unit, $year, $season_id, $planted_area, $area_unit, $field_id);
            }
            
            if (!$stmt->execute()) {
                error_log("[ADD_PRODUCT] Insert barangay product error: " . $stmt->error);
                throw new Exception("Failed to create product barangay mapping: " . $stmt->error);
            }
            
            error_log("[ADD_PRODUCT] Barangay product mapping created successfully with ID: " . $conn->insert_id);
        }
        
        // Add activity log entry
        $action = "Farmer ID: $user_id added new product: $name (ID: $product_id) with unit type: $unit_type";
        $stmt = $conn->prepare("INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Build response with product data
        $response['success'] = true;
        $response['message'] = "Product added successfully";
        $response['data'] = [
            'product_id' => $product_id,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'image_path' => $image_path,
            'unit_type' => $unit_type,
            'barangay_id' => $barangay_id,
            'field_id' => $field_id
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn && $conn->ping()) {
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
        }
        
        // Delete uploaded image if exists
        if (isset($target_file) && file_exists($target_file)) {
            @unlink($target_file);
        }
        
        $response['message'] = $e->getMessage();
        error_log("[ADD_PRODUCT] Error: " . $e->getMessage());
    } finally {
        // Close connection
        if (isset($conn) && $conn && $conn->ping()) {
            $conn->close();
        }
        
        // Log performance timing
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        error_log("[ADD_PRODUCT] Execution time: " . round($executionTime, 2) . "ms");
    }
} else {
    $response['message'] = "Invalid request method";
}

// Return response
echo json_encode($response);
?>