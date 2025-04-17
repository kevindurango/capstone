<?php
// Enable error reporting for debugging
ini_set('display_errors', 1); // Enable error display for debugging (remove in production)
error_reporting(E_ALL); // Log all types of errors

// Ensure no output before JSON response
ob_start();

// Register shutdown function to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        error_log("[ERROR] Fatal error: " . $error['message']);
        sendErrorResponse("A server error occurred. Please check the logs.");
    }
});

// Set proper headers for CORS and content type
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Origin');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// For OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to send proper error response
function sendErrorResponse($message, $statusCode = 500) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

// Include database connection file
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    error_log("[ERROR] Database connection failed: " . $e->getMessage());
    sendErrorResponse("Database connection error. Please check server logs.");
}

error_log("[DEBUG] Starting market products fetch");

try {
    // Check if database connection is established properly
    if (!isset($conn) || $conn->connect_error) {
        error_log("[ERROR] Database connection failed: " . ($conn->connect_error ?? "Connection not established"));
        sendErrorResponse("Database connection error. Please check server logs.");
    }

    // Get query parameters
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    error_log("[DEBUG] Market query params - category: " . ($category ?? "null") . 
              ", search: " . ($search ?? "null") . 
              ", limit: $limit, offset: $offset");
    
    // Update the query to include category filtering
    $query = "SELECT p.*, 
                     fd.farm_name, 
                     u.first_name, 
                     u.last_name, 
                     u.contact_number, 
                     pc.category_name AS category
              FROM products p
              LEFT JOIN users u ON p.farmer_id = u.user_id
              LEFT JOIN farmer_details fd ON u.user_id = fd.user_id
              LEFT JOIN productcategorymapping pcm ON p.product_id = pcm.product_id
              LEFT JOIN productcategories pc ON pcm.category_id = pc.category_id
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // We don't need to check for is_active as it doesn't exist
    // Instead, filter by status = 'approved' since that's in the schema
    $query .= " AND p.status = 'approved'";
    
    // Add category filter if provided
    if ($category && $category !== '') {
        $query .= " AND pc.category_name = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    // Add search filter if provided
    if ($search && $search !== '') {
        $searchTerm = "%$search%";
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    // Add ordering and pagination - note: we need to handle if the created_at column exists
    $checkCreatedAtColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'created_at'");
    if ($checkCreatedAtColumn->num_rows > 0) {
        $query .= " ORDER BY p.created_at DESC";
    } else {
        $query .= " ORDER BY p.product_id DESC"; // Fallback to ID sorting
    }
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    error_log("[DEBUG] Market query: $query");
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("[ERROR] Query preparation failed: " . $conn->error);
        sendErrorResponse("Failed to prepare query. Check server logs.");
    }
    
    // Bind parameters if any
    if (!empty($params)) {
        error_log("[DEBUG] Binding parameters with types: $types");
        $stmt->bind_param($types, ...$params);
    }
    
    // Execute query
    if (!$stmt->execute()) {
        error_log("[ERROR] Query execution failed: " . $stmt->error);
        sendErrorResponse("Failed to execute query. Check server logs.");
    }
    
    $result = $stmt->get_result();
    
    // Process results
    $products = [];

    // Get the host from the request
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $imageBaseUrl = "{$protocol}://{$host}/capstone/public";

    error_log("[DEBUG] Using dynamic image base URL: $imageBaseUrl");

    while ($row = $result->fetch_assoc()) {
        // Handle missing columns with defaults
        $farmName = isset($row['farm_name']) && !empty($row['farm_name']) ? $row['farm_name'] : 'Farm Unknown';
        $firstName = isset($row['first_name']) ? $row['first_name'] : '';
        $lastName = isset($row['last_name']) ? $row['last_name'] : '';
        $farmerName = trim("$firstName $lastName") ?: 'Unknown Farmer';
        $contact = isset($row['contact_number']) ? $row['contact_number'] : 'No contact provided';
        
        // Get actual column for stock quantity
        $quantity = isset($row['stock']) ? (int)$row['stock'] : 0;
        
        // Get unit or provide default
        $unit = isset($row['unit']) ? $row['unit'] : 'item';
        
        // Handle image URL with dynamic base URL
        $imageUrl = null;
        if (!empty($row['image']) && $row['image'] != 'Array') {
            $image = $row['image'];
            // Log the raw image value for debugging
            error_log("[DEBUG] Raw image value for product ID {$row['product_id']}: " . $image);
            
            // Normalize the image path
            if (strpos($image, 'uploads/') === 0) {
                // Image path already starts with uploads/
                $imagePath = $image;
            } elseif (strpos($image, '/') === false) {
                // Image is just a filename, prefix with uploads/
                $imagePath = 'uploads/' . $image;
            } else {
                // Other format, use as is
                $imagePath = $image;
            }
            
            // Construct complete URL
            $imageUrl = $imageBaseUrl . '/' . $imagePath;
            error_log("[DEBUG] Constructed image URL: " . $imageUrl);
            
            // Optional: Check if file exists
            // This can be commented out if it affects performance
            $serverPath = $_SERVER['DOCUMENT_ROOT'] . '/capstone/public/' . $imagePath;
            if (!file_exists($serverPath)) {
                error_log("[WARNING] Image file not found at server path: " . $serverPath);
                $imageUrl = $imageBaseUrl . '/uploads/default-placeholder.png';
            }
        } else {
            // Handle empty or 'Array' values
            error_log("[DEBUG] Invalid or missing image for product ID {$row['product_id']}: " . ($row['image'] ?? 'NULL'));
            $imageUrl = $imageBaseUrl . '/uploads/default-placeholder.png';
        }
        
        // Format the product data
        $productData = [
            'id' => $row['product_id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'price' => (float)$row['price'],
            'unit' => $unit, 
            'quantity_available' => $quantity,
            'category' => $row['category'] ?? 'Uncategorized',
            'farm_name' => $farmName,
            'farmer' => $farmerName,
            'contact' => $contact,
            'image_url' => $imageUrl
        ];
        
        $products[] = $productData;
    }
    
    // Get total count for pagination
    $countQuery = preg_replace('/SELECT p\.\*, fd\.farm_name, u\.first_name, u\.last_name, u\.contact_number, pc\.category_name AS category/i', 'SELECT COUNT(*) as total', $query);
    $countQuery = preg_replace('/ORDER BY.*$/i', '', $countQuery);
    
    error_log("[DEBUG] Count query: $countQuery");
    
    $countStmt = $conn->prepare($countQuery);
    if (!$countStmt) {
        error_log("[ERROR] Count query preparation failed: " . $conn->error);
        sendErrorResponse("Count query preparation failed. Check server logs.");
    }
    
    if (!empty($params)) {
        // Remove the limit and offset parameters for the count query
        array_pop($params);
        array_pop($params);
        $countTypes = substr($types, 0, -2);
        if ($countTypes) {
            $countStmt->bind_param($countTypes, ...$params);
        }
    }
    
    if (!$countStmt->execute()) {
        error_log("[ERROR] Count query execution failed: " . $countStmt->error);
        sendErrorResponse("Count query execution failed. Check server logs.");
    }
    
    $countResult = $countStmt->get_result();
    $totalCount = $countResult ? $countResult->fetch_assoc()['total'] ?? 0 : 0; // Ensure totalCount is set
    
    error_log("[DEBUG] Found $totalCount total products");
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Products retrieved successfully',
        'data' => [
            'products' => $products,
            'total' => (int)$totalCount,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    ob_end_flush(); // Flush output buffer
    
} catch (Exception $e) {
    error_log("[ERROR] Market fetch failed: " . $e->getMessage());
    ob_end_clean(); // Clean output buffer
    sendErrorResponse('Failed to retrieve products: ' . $e->getMessage());
}
?>
