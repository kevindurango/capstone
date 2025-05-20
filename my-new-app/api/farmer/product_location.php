<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database connection
require_once '../config/database.php';

// Response array
$response = [
    'success' => false,
    'message' => '',
    'barangay_id' => null,
    'field_id' => null
];

// Start timing for performance logging
$startTime = microtime(true);

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = null;
    
    try {
        // Check required params
        if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
            throw new Exception("Product ID is required");
        }
        
        $product_id = filter_var($_GET['product_id'], FILTER_SANITIZE_NUMBER_INT);
        error_log("[PRODUCT_LOCATION] Fetching location for product ID: " . $product_id);
        
        // Get database connection
        $conn = getConnection();
        
        // Check if connection is successful
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // First get the farmer_id for this product to validate ownership
        $farmer_query = $conn->prepare("SELECT farmer_id FROM products WHERE product_id = ?");
        $farmer_query->bind_param("i", $product_id);
        $farmer_query->execute();
        $farmer_result = $farmer_query->get_result();
        
        if ($farmer_result->num_rows === 0) {
            error_log("[PRODUCT_LOCATION] Product not found: " . $product_id);
            throw new Exception("Product not found");
        }
        
        $product_data = $farmer_result->fetch_assoc();
        $farmer_id = $product_data['farmer_id'];
        error_log("[PRODUCT_LOCATION] Product belongs to farmer ID: " . $farmer_id);
        
        // Query the barangay_products table to get the product's barangay and field
        $stmt = $conn->prepare("SELECT barangay_id, field_id FROM barangay_products WHERE product_id = ? LIMIT 1");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            
            // Verify that the field belongs to this farmer if a field_id is provided
            $field_id = $data['field_id'];
            $barangay_id = $data['barangay_id'];
            
            if ($field_id) {
                error_log("[PRODUCT_LOCATION] Verifying field ID: " . $field_id . " belongs to farmer: " . $farmer_id);
                $field_check = $conn->prepare("SELECT field_id FROM farmer_fields WHERE field_id = ? AND farmer_id = ?");
                $field_check->bind_param("ii", $field_id, $farmer_id);
                $field_check->execute();
                $field_result = $field_check->get_result();
                
                if ($field_result->num_rows === 0) {
                    error_log("[PRODUCT_LOCATION] Field does not belong to farmer. Setting field_id to null.");
                    $field_id = null;
                } else {
                    error_log("[PRODUCT_LOCATION] Field verified as belonging to farmer");
                }
            }
            
            $response['barangay_id'] = $barangay_id;
            $response['field_id'] = $field_id;
            $response['success'] = true;
            $response['message'] = "Product location retrieved successfully";
        } else {
            // No location data found, but not an error
            $response['success'] = true;
            $response['message'] = "No location data found for this product";
            error_log("[PRODUCT_LOCATION] No location data found for product: " . $product_id);
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("[PRODUCT_LOCATION] Error: " . $e->getMessage());
    } finally {
        // Close connection
        if (isset($conn) && $conn) {
            $conn->close();
        }
        
        // Log performance timing
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        error_log("[PRODUCT_LOCATION] Execution time: " . round($executionTime, 2) . "ms");
    }
} else {
    $response['message'] = "Invalid request method";
}

// Return response
echo json_encode($response);
?> 