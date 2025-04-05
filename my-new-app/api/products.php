<?php
// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS, POST');
header('Access-Control-Allow-Headers: Content-Type, Accept, Origin, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_log("[PRODUCTS API] Request received from: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
error_log("[PRODUCTS API] Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown'));

// Include database connection file
require_once __DIR__ . '/config/database.php';

error_log("[PRODUCTS API] Starting products API request");

try {
    // Verify $conn variable exists
    if (!isset($conn)) {
        throw new Exception("Database connection not initialized.");
    }
    
    // Test database connection first
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get products with status 'approved' for public display
    $query = "SELECT p.*, u.first_name, u.last_name 
             FROM products p
             LEFT JOIN users u ON p.farmer_id = u.user_id
             WHERE p.status = 'approved'
             ORDER BY p.created_at DESC";
    
    error_log("[DEBUG] Products query: " . $query);
    
    $result = $conn->query($query);
    
    if (!$result) {
        error_log("[ERROR] Database query failed: " . $conn->error);
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $products = [];
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    
    while ($row = $result->fetch_assoc()) {
        // Add farmer name for display
        if ($row['farmer_id']) {
            $row['farmer_name'] = $row['first_name'] . ' ' . $row['last_name'];
        }
        
        // Clean up the response
        unset($row['first_name']);
        unset($row['last_name']);
        
        // Fix image path if needed
        if ($row['image'] && !filter_var($row['image'], FILTER_VALIDATE_URL)) {
            // Check if it's a relative path
            $row['image'] = $baseUrl . '/' . ltrim($row['image'], '/');
        }
        
        // Normalize field names for frontend consistency
        $row['id'] = $row['product_id'];
        
        // Ensure consistent data types for frontend
        $row['price'] = (float)$row['price'];
        $row['stock'] = (int)$row['stock'];
        $row['created_at'] = date('c', strtotime($row['created_at']));
        $row['updated_at'] = date('c', strtotime($row['updated_at']));
        
        // Add to products array
        $products[] = $row;
    }
    
    if (empty($products)) {
        error_log("[DEBUG] No products found.");
        // Return empty array with 200 status instead of 404
        // This is more RESTful for empty collections
        echo json_encode([
            'status' => 'success',
            'data' => [],
            'message' => 'No products found.'
        ]);
        exit();
    }
    
    error_log("[DEBUG] Products API: Found " . count($products) . " products");
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("[ERROR] Products API failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->close();
        error_log("[PRODUCTS API] Database connection closed");
    }
}
?>
