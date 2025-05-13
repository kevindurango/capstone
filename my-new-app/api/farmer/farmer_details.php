<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('../config/database.php');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'farm_details' => null
];

// Process based on request type
try {
    // Get database connection
    $conn = getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check for user_id parameter
        if (!isset($_GET['user_id'])) {
            throw new Exception('Missing required parameter: user_id');
        }

        $user_id = intval($_GET['user_id']);

        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if user exists and is a farmer
            $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2"; // role_id 2 is for farmers
            $stmt = $conn->prepare($user_check_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user_result = $stmt->get_result();

            if ($user_result->num_rows === 0) {
                throw new Exception('User is not found or not a farmer');
            }

            // Get farmer details with barangay name
            $details_query = "SELECT fd.*, b.barangay_name 
                            FROM farmer_details fd
                            LEFT JOIN barangays b ON fd.barangay_id = b.barangay_id
                            WHERE fd.user_id = ?";
            $stmt = $conn->prepare($details_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $farm_details = $result->fetch_assoc();
                
                // Format the farm detail data
                $response['farm_details'] = [
                    'detail_id' => (int)$farm_details['detail_id'],
                    'user_id' => (int)$farm_details['user_id'],
                    'farm_name' => $farm_details['farm_name'],
                    'farm_type' => $farm_details['farm_type'],
                    'certifications' => $farm_details['certifications'],
                    'crop_varieties' => $farm_details['crop_varieties'],
                    'machinery_used' => $farm_details['machinery_used'],
                    'farm_size' => (float)$farm_details['farm_size'],
                    'income' => $farm_details['income'] !== null ? (float)$farm_details['income'] : null,
                    'farm_location' => $farm_details['farm_location'],
                    'barangay_id' => isset($farm_details['barangay_id']) ? (int)$farm_details['barangay_id'] : null,
                    'barangay_name' => $farm_details['barangay_name'] ?? null
                ];
                
                // Get farmer's fields
                $fields_query = "SELECT ff.*, b.barangay_name 
                                FROM farmer_fields ff
                                LEFT JOIN barangays b ON ff.barangay_id = b.barangay_id
                                WHERE ff.farmer_id = ?";
                $stmt = $conn->prepare($fields_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $fields_result = $stmt->get_result();
                
                $fields = [];
                while ($field = $fields_result->fetch_assoc()) {
                    $fields[] = [
                        'field_id' => (int)$field['field_id'],
                        'field_name' => $field['field_name'],
                        'field_size' => (float)$field['field_size'],
                        'field_type' => $field['field_type'],
                        'barangay_id' => (int)$field['barangay_id'],
                        'barangay_name' => $field['barangay_name'],
                        'notes' => $field['notes'],
                        'coordinates' => $field['coordinates'],
                        'created_at' => $field['created_at']
                    ];
                }
                $response['farm_details']['fields'] = $fields;
                
                // Get farmer's products with categories
                $products_query = "SELECT p.*, 
                                        (SELECT COUNT(*) FROM orderitems oi WHERE oi.product_id = p.product_id) as order_count 
                                 FROM products p 
                                 WHERE p.farmer_id = ?";
                $stmt = $conn->prepare($products_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $products_result = $stmt->get_result();
                
                $products = [];
                while ($product = $products_result->fetch_assoc()) {
                    // Get categories for this product
                    $categories_query = "SELECT pc.category_id, pc.category_name 
                                       FROM productcategorymapping pcm
                                       JOIN productcategories pc ON pcm.category_id = pc.category_id
                                       WHERE pcm.product_id = ?";
                    $cat_stmt = $conn->prepare($categories_query);
                    $cat_stmt->bind_param('i', $product['product_id']);
                    $cat_stmt->execute();
                    $categories_result = $cat_stmt->get_result();
                    
                    $categories = [];
                    while ($category = $categories_result->fetch_assoc()) {
                        $categories[] = [
                            'category_id' => (int)$category['category_id'],
                            'category_name' => $category['category_name']
                        ];
                    }
                    
                    // Normalize image path if it exists
                    $image_path = $product['image'];
                    if (!empty($image_path)) {
                        // Remove any leading slashes
                        $image_path = ltrim($image_path, '/');
                        
                        // If path doesn't have correct format, extract just the filename
                        if (!preg_match('#^uploads/products/[^/]+$#', $image_path)) {
                            $filename = basename($image_path);
                            $image_path = 'uploads/products/' . $filename;
                        }
                    }
                    
                    $products[] = [
                        'product_id' => (int)$product['product_id'],
                        'name' => $product['name'],
                        'description' => $product['description'],
                        'price' => (float)$product['price'],
                        'status' => $product['status'],
                        'created_at' => $product['created_at'],
                        'updated_at' => $product['updated_at'],
                        'image' => $image_path,
                        'stock' => (int)$product['stock'],
                        'unit_type' => $product['unit_type'],
                        'order_count' => (int)$product['order_count'],
                        'categories' => $categories
                    ];
                }
                $response['farm_details']['products'] = $products;
                
                $response['success'] = true;
                $response['message'] = 'Farm details retrieved successfully';
            } else {
                // No details found, return empty object
                $response['success'] = true;
                $response['message'] = 'No farm details found for this farmer';
                $response['farm_details'] = null;
            }
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Handle creating or updating farm details
        
        // Check if data is coming from form data (multipart/form-data) or JSON
        $user_id = null;
        $data = [];
        
        // Check if data is coming as form data
        if (isset($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            $data = $_POST;
            error_log("Form data received with user_id: $user_id");
        } 
        // Check if data is coming as JSON
        else {
            $jsonData = json_decode(file_get_contents('php://input'), true);
            if (isset($jsonData['user_id'])) {
                $user_id = intval($jsonData['user_id']);
                $data = $jsonData;
                error_log("JSON data received with user_id: $user_id");
            }
        }
        
        // Validate user_id is present
        if (!$user_id) {
            throw new Exception('Missing required parameter: user_id');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if user exists and is a farmer
            $user_check_query = "SELECT role_id FROM users WHERE user_id = ? AND role_id = 2";
            $stmt = $conn->prepare($user_check_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user_result = $stmt->get_result();

            if ($user_result->num_rows === 0) {
                throw new Exception('User is not found or not a farmer');
            }
            
            // Check if farmer details already exist
            $check_query = "SELECT detail_id FROM farmer_details WHERE user_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Validate barangay_id if provided
            $barangay_id = null;
            if (isset($data['barangay_id']) && !empty($data['barangay_id'])) {
                $barangay_id = intval($data['barangay_id']);
                $barangay_check = "SELECT barangay_id FROM barangays WHERE barangay_id = ?";
                $stmt = $conn->prepare($barangay_check);
                $stmt->bind_param('i', $barangay_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Invalid barangay ID');
                }
            }
            
            // Set default values for optional fields
            $farm_name = isset($data['farm_name']) ? trim($data['farm_name']) : '';
            $farm_type = isset($data['farm_type']) ? trim($data['farm_type']) : '';
            $certifications = isset($data['certifications']) ? trim($data['certifications']) : '';
            $crop_varieties = isset($data['crop_varieties']) ? trim($data['crop_varieties']) : '';
            $machinery_used = isset($data['machinery_used']) ? trim($data['machinery_used']) : '';
            $farm_size = isset($data['farm_size']) ? floatval($data['farm_size']) : 0;
            $income = isset($data['income']) ? floatval($data['income']) : null;
            $farm_location = isset($data['farm_location']) ? trim($data['farm_location']) : '';
            
            if ($result->num_rows > 0) {
                // Update existing details
                $detail_id = $result->fetch_assoc()['detail_id'];
                
                $update_query = "UPDATE farmer_details SET 
                    farm_name = ?, 
                    farm_type = ?, 
                    certifications = ?, 
                    crop_varieties = ?, 
                    machinery_used = ?, 
                    farm_size = ?, 
                    income = ?, 
                    farm_location = ?,
                    barangay_id = ? 
                    WHERE detail_id = ?";
                    
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param(
                    'sssssddsii',
                    $farm_name,
                    $farm_type,
                    $certifications,
                    $crop_varieties,
                    $machinery_used,
                    $farm_size,
                    $income,
                    $farm_location,
                    $barangay_id,
                    $detail_id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update farm details: ' . $conn->error);
                }
                
                // Log activity
                $action = "Farmer ID: $user_id updated farm details";
                $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("is", $user_id, $action);
                $log_stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Farm details updated successfully';
                
            } else {
                // Create new details
                $insert_query = "INSERT INTO farmer_details (
                    user_id, farm_name, farm_type, certifications, crop_varieties, 
                    machinery_used, farm_size, income, farm_location, barangay_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param(
                    'isssssddsi',
                    $user_id,
                    $farm_name,
                    $farm_type,
                    $certifications,
                    $crop_varieties,
                    $machinery_used,
                    $farm_size,
                    $income,
                    $farm_location,
                    $barangay_id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create farm details: ' . $conn->error);
                }
                
                $new_detail_id = $conn->insert_id;
                
                // Log activity
                $action = "Farmer ID: $user_id created farm details";
                $log_query = "INSERT INTO activitylogs (user_id, action, action_date) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("is", $user_id, $action);
                $log_stmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Farm details created successfully';
                $response['farm_details'] = [
                    'detail_id' => $new_detail_id,
                    'user_id' => $user_id,
                    'farm_name' => $farm_name,
                    'farm_type' => $farm_type,
                    'certifications' => $certifications,
                    'crop_varieties' => $crop_varieties,
                    'machinery_used' => $machinery_used,
                    'farm_size' => $farm_size,
                    'income' => $income,
                    'farm_location' => $farm_location,
                    'barangay_id' => $barangay_id
                ];
            }
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            throw $e;
        }
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Farmer details API error: " . $e->getMessage());
} finally {
    // Close connections
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

// Return JSON response
echo json_encode($response);