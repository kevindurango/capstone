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

error_log("[CATEGORIES API] Request received from: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
error_log("[CATEGORIES API] Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown'));

// Include database connection file
require_once __DIR__ . '/config/database.php';

error_log("[CATEGORIES API] Starting categories API request");

try {
    // Verify $conn variable exists
    if (!isset($conn)) {
        throw new Exception("Database connection not initialized.");
    }
    
    // Test database connection first
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get all product categories
    $query = "SELECT * FROM productcategories ORDER BY category_name";
    
    error_log("[DEBUG] Categories query: " . $query);
    
    $result = $conn->query($query);
    
    if (!$result) {
        error_log("[ERROR] Database query failed: " . $conn->error);
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        // Add category_id as id for consistency with frontend expectations
        $row['id'] = $row['category_id'];
        $categories[] = $row;
    }
    
    if (empty($categories)) {
        error_log("[DEBUG] No categories found.");
        // Return empty array with 200 status instead of 404
        // This is more RESTful for empty collections
        echo json_encode([
            'status' => 'success',
            'data' => [],
            'message' => 'No categories found.'
        ]);
        exit();
    }
    
    error_log("[DEBUG] Categories API: Found " . count($categories) . " categories");
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => $categories,
        'count' => count($categories)
    ]);
    
} catch (Exception $e) {
    error_log("[ERROR] Categories API failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->close();
        error_log("[CATEGORIES API] Database connection closed");
    }
}
?>
